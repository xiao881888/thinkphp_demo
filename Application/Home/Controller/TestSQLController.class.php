<?php
namespace Home\Controller;
use Home\Util\Pack;
use Think\Controller;

class TestSQLController extends Controller
{

    const TOKEN = '4fca1e2d876c57dd76a5e17a5414c288';

    public function unPackReq(){
        $request 	= self::unpackRequest();
        print_r($request);die;
    }

    public function unPackRes(){
        $response 	= self::_unResponsePack();
        print_r($response);die;
    }


    /* ================= 解包 ================= */

    public static function unpackRequest() {
        $packetInfo	 	= self::_unRequestpack();
        $header		 	= $packetInfo['header'];
        $requestBody 	= $packetInfo['requestBody'];
        $encryptTypes 	= decToBinArray($header['encrypt_type']);
        $gzBit = array_shift($encryptTypes);
        $encryptBit = array_pop($encryptTypes);

        $requestBody = base64_decode($requestBody);
        if( $gzBit == 1 ) {
            //FIXME 待测试
            $requestBody = gzdecode_for_tiger($requestBody, 3);
        }
        echo $encryptBit;


        if ($encryptBit != 0) {
            $encryptKey = self::_getUserEncryptKey($header['token']);


            $aesBit = $encryptTypes[4];
            $desBit = $encryptTypes[5];

            if ($aesBit == 1) {
                // $sign = decryptRsa($encryptKey[1]['sign']);
                $sign = $encryptKey[1]['sign'];
                $requestBody = decryptAes($sign, $requestBody);
            }
            if ($desBit == 1) {
                // $sign = decryptRsa($encryptKey[0]['sign']);
                // $signIv = decryptRsa($encryptKey[0]['sign_iv']);

                $sign = $encryptKey[0]['sign'];
                $signIv = $encryptKey[0]['sign_iv'];

                ApiLog('unpack request sig: ' . $sign . '===' . $signIv, 'testsqlpack');

                $requestBody = decrypt3des($sign, $signIv, $requestBody);
            }
        }
        $requestBody = json_decode($requestBody, true);

        ApiLog('unpack request body decrypt : '.print_r($requestBody,true), 'testsqlpack');

        return array_merge($header, $requestBody);
    }


    /*
     * 获取加密密钥
     */
    private function _getUserEncryptKey($token){
        $encryptKey = D('Session')->getEncryptKey($token);

        return json_decode($encryptKey ,true);
    }

    private function _unResponsePack() {
        ApiLog(PHP_EOL.PHP_EOL.PHP_EOL.PHP_EOL.PHP_EOL.'unpack begin', 'testsqlpack');
        $fp = fopen ( 'php://input', 'rb' );
//         fseek($fp, 8, SEEK_CUR);
        //fseek($fp, 8, SEEK_CUR);
        fread($fp, 8);

        $header = array();
        $act_arr			= unpack ( 'n*', fread ( $fp, 2 ) );		//接口编号
        $header['act'] 		= intval($act_arr[1]);
        $length_arr 		= unpack ( 'N*', fread ( $fp, 4 ) );		//包体长度
        $header['length'] 	= intval($length_arr[1]);
        $error_code         = unpack('N*', fread($fp, 4));
        $header['error_code'] = $error_code[1];
        $type_arr = unpack( 'C*',  fread( $fp, 1));
        $header['encrypt_type'] = $type_arr[1];
        fseek( $fp, 13, SEEK_CUR);		//跳过保留填充位

        $requestBody = '';
        if($header['length']){
            while(!feof($fp)){
                $requestBody .= fread($fp, $header['length']);
            }
        }
        ApiLog('$header:'.print_r($header,true), 'testsqlpack');

        $encryptTypes 	= decToBinArray($header['encrypt_type']);
        $gzBit = array_shift($encryptTypes);
        $encryptBit = array_pop($encryptTypes);
        $requestBody = base64_decode($requestBody);

        if( $gzBit == 1 ) {
            //FIXME 待测试
            $requestBody = gzdecode_for_tiger($requestBody, 3);
        }
        if ($encryptBit != 0) {
            $encryptKey = self::_getUserEncryptKey(self::TOKEN);
            $aesBit = $encryptTypes[4];
            $desBit = $encryptTypes[5];
            if ($aesBit == 1) {
                // $sign = decryptRsa($encryptKey[1]['sign']);
                $sign = $encryptKey[1]['sign'];
                $requestBody = decryptAes($sign, $requestBody);
            }
            if ($desBit == 1) {
                // $sign = decryptRsa($encryptKey[0]['sign']);
                // $signIv = decryptRsa($encryptKey[0]['sign_iv']);

                $sign = $encryptKey[0]['sign'];
                $signIv = $encryptKey[0]['sign_iv'];
                ApiLog('unpack request sig: ' . $sign . '===' . $signIv, 'testsqlpack');

                $requestBody = decrypt3des($sign, $signIv, $requestBody);
            }
        }
        $requestBody = json_decode($requestBody, true);
        ApiLog('$requestBody2:'.print_r($requestBody,true), 'testsqlpack');

        return array_merge($header, $requestBody);
    }


    private function _unRequestpack() {
        ApiLog(PHP_EOL.PHP_EOL.PHP_EOL.PHP_EOL.PHP_EOL.'unpack begin', 'testsqlpack');
        $fp = fopen ( 'php://input', 'rb' );
//         fseek($fp, 8, SEEK_CUR);
        fread($fp, 8);

        $version_arr = unpack('n*', fread($fp, 2)); // 接口版本
        $act_arr = unpack('n*', fread($fp, 2)); // 接口编号
        $length_arr = unpack('N*', fread($fp, 4)); // 包体长度
        $token_id_arr = unpack('a*', fread($fp, 32)); // Token
        $type_arr = unpack('C*', fread($fp, 1));

        $header = array();
        $header['sdk_version']	= $version_arr[1];
        $header['act'] 		= intval($act_arr[1]);
        $header['length'] 	= intval($length_arr[1]);
        $header['token']  = $token_id_arr[1];
        $header['encrypt_type'] = $type_arr[1];

        fseek( $fp, 15, SEEK_CUR);		//跳过保留填充位

        ApiLog('unpack request: '.print_r($header,true), 'testsqlpack');

        # 包体数据处理
        $requestBody = '';

        if($header['length']){
            while(!feof($fp)){
                $requestBody .= fread($fp, $header['length']);
            }
        }

        fclose ($fp);
        return array(   'header'        => $header,
            'requestBody'	=> $requestBody);
    }

    public function test_echo(){
        echo 1111;
    }

	public function run(){
		set_time_limit(0);
		$this->getTicketList(1);
		$this->getTicketList(2);
	}
	
	public function run1(){
		set_time_limit(0);
		$this->getTicketList1(1);
		$this->getTicketList1(2);
		
	}
	
	public function runtest(){		
		$map['order_id'] = I('i');
		$map['order_status'] = array('in',array(3,8));
		
		
		$order_list = D('Order')->where($map)->select();
		echo M()->_sql();
		echo "<br>\n";
		foreach($order_list as $order_info){
			$lo_id = $order_info['lottery_id'];
			if(in_array($lo_id,array(601,602,603,604,605,606))){
				$model = D('JczqTicket');

			}elseif(in_array($lo_id,array(701,702,703,704,705))){

				$model = D('JclqTicket');
			}
			$zq_order_ids[]= $order_info['order_id'];
			$ticket_map['order_id'] =  $order_info['order_id'];
			$ticket_map['ticket_status'] =  1;
			$ticket_info = $model->where($ticket_map)->find();
			echo M()->_sql();
			echo "<br>\n";
			if(isset($order[$ticket_info['order_id']])){
				continue;
			}
			 $post['tid'] = $ticket_info['ticket_id'];			
			  $ticketaaa[$ticket_info['order_id']] = $ticket_info['ticket_id'];

			 $proxy = requestByCurl('http://114.215.254.94/Home/Test/test', $post);
			 if($proxy){
				 $order[$ticket_info['order_id']] = $proxy;
				 $max[] = $ticket_info['order_id'];
			 }else{
				 $ext[] = $ticket_info['order_id'];
				 $max[] = $ticket_info['order_id'];
			 }		
		}
		
		echo 'order:'.count($order);
		echo '====';
		print_r($order);
		echo "<br>\n";
		echo 'ticketaaa:'.count($ticketaaa);
		echo '====';
		print_r($ticketaaa);
		echo "<br>\n";
		echo 'ext:'.count($ext);
		echo '====';
		print_r($ext);
		echo "<br>\n";
		if(count($order)){
			foreach($order as $order_id=>$proxy){
				$p_map['order_id'] = $order_id;
				$p_map['order_proxy_channel'] = 'D1';
				$o_data['order_proxy_channel'] = trim($proxy);
				D('Order')->where($p_map)->save($o_data);
				echo 'update:'.M()->_sql();
				echo "<br>\n";
			}
		}		
		
	}
	
	private function getTicketList1($t_map_id = 1){
		$t_map['id'] =$t_map_id;
		
		$now = date('Y-m-d H:i:s');
		$from = date('Y-m-d H:i:s',time()-600);
		$map['order_modify_time'] = array('between',array($from,$now));;
		$map['order_status'] = array('in',array(3,8));
		if(I('ch')){
			$map['order_proxy_channel'] = I('ch');
		}else{
			$map['order_proxy_channel'] = 'D1';
		}
		
		if($t_map_id==1){
			$model = D('JczqTicket');
			$map['lottery_id'] = array('in',array(601,602,603,604,605,606));

		}else{
			$map['lottery_id'] = array('in',array(701,702,703,704,705));

			$model = D('JclqTicket');
		}
		$limit = I('limit')?I('limit'):200;
		$order_list = D('Order')->where($map)->limit($limit)->select();
		echo M()->_sql();
		echo "<br>\n";
		foreach($order_list as $order_info){
			$zq_order_ids[]= $order_info['order_id'];
			$ticket_map['order_id'] =  $order_info['order_id'];
			$ticket_map['ticket_status'] =  1;
			$ticket_info = $model->where($ticket_map)->find();
			echo M()->_sql();
			echo "<br>\n";
			if(isset($order[$ticket_info['order_id']])){
				continue;
			}
			 $post['tid'] = $ticket_info['ticket_id'];			
			  $ticketaaa[$ticket_info['order_id']] = $ticket_info['ticket_id'];

			 $proxy = requestByCurl('http://114.215.254.94/Home/Test/test', $post);
			 if($proxy){
				 $order[$ticket_info['order_id']] = $proxy;
				 $max[] = $ticket_info['order_id'];
			 }else{
				 $ext[] = $ticket_info['order_id'];
				 $max[] = $ticket_info['order_id'];
			 }		
		}
		
		echo 'order:'.count($order);
		echo '====';
		print_r($order);
		echo "<br>\n";
		echo 'ticketaaa:'.count($ticketaaa);
		echo '====';
		print_r($ticketaaa);
		echo "<br>\n";
		echo 'ext:'.count($ext);
		echo '====';
		print_r($ext);
		echo "<br>\n";
		if(count($order)){
			foreach($order as $order_id=>$proxy){
				$p_map['order_id'] = $order_id;
				$p_map['order_proxy_channel'] = 'D1';
				$o_data['order_proxy_channel'] = trim($proxy);
				D('Order')->where($p_map)->save($o_data);
				echo 'update:'.M()->_sql();
				echo "<br>\n";
			}
		}
		
		
	}
	
	private function getTicketList($t_map_id = 1){
		$t_map['id'] =$t_map_id;
		$test_info = D('Test')->where($t_map)->find();
		if($test_info){
			$map['order_id'] =  array('egt',$test_info['order_id']);
		}
		$map['order_create_time'] = array('egt','2017-09-09 00:00:00');
		$map['order_status'] = array('in',array(3,8));
		if(I('ch')){
			$map['order_proxy_channel'] = I('ch');
		}else{
			$map['order_proxy_channel'] = 'D1';
		}
		
		if($t_map_id==1){
			$model = D('JczqTicket');
			$map['lottery_id'] = array('in',array(601,602,603,604,605,606));

		}else{
			$map['lottery_id'] = array('in',array(701,702,703,704,705));

			$model = D('JclqTicket');
		}
		$limit = I('limit')?I('limit'):200;
		$order_list = D('Order')->where($map)->limit($limit)->select();
		echo M()->_sql();
		echo "<br>\n";
		foreach($order_list as $order_info){
			$zq_order_ids[]= $order_info['order_id'];
			$ticket_map['order_id'] =  $order_info['order_id'];
			$ticket_map['ticket_status'] =  1;
			$ticket_info = $model->where($ticket_map)->find();
			echo M()->_sql();
			echo "<br>\n";
			if(isset($order[$ticket_info['order_id']])){
				continue;
			}
			 $post['tid'] = $ticket_info['ticket_id'];			
			  $ticketaaa[$ticket_info['order_id']] = $ticket_info['ticket_id'];

			 $proxy = requestByCurl('http://114.215.254.94/Home/Test/test', $post);
			 if($proxy){
				 $order[$ticket_info['order_id']] = $proxy;
				 $max[] = $ticket_info['order_id'];
			 }else{
				 $ext[] = $ticket_info['order_id'];
				 $max[] = $ticket_info['order_id'];
			 }		
		}
		
		echo 'order:'.count($order);
		echo '====';
		print_r($order);
		echo "<br>\n";
		echo 'ticketaaa:'.count($ticketaaa);
		echo '====';
		print_r($ticketaaa);
		echo "<br>\n";
		echo 'ext:'.count($ext);
		echo '====';
		print_r($ext);
		echo "<br>\n";
		if(count($order)){
			foreach($order as $order_id=>$proxy){
				$p_map['order_id'] = $order_id;
				$p_map['order_proxy_channel'] = 'D1';
				$o_data['order_proxy_channel'] = trim($proxy);
				D('Order')->where($p_map)->save($o_data);
				echo 'update:'.M()->_sql();
				echo "<br>\n";
			}
		}
		if($max){
			$test_data['order_id'] = max($max);
		D('Test')->where($t_map)->save($test_data);
		print_r($test_data);
		echo "<br>\n";
		echo M()->_sql();
		echo "<br>\n";
		}
		
	}
}

