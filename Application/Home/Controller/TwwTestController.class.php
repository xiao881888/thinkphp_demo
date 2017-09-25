<?php
namespace Home\Controller;
use Think\Controller;
use Home\Util\Factory;

class TwwTestController extends Controller {
    
	private $sdk_version = 1;
	private $act = 0;
	private $header_encrypt_type = 0;
	private $header_length = 0;
	private $header_error;
	private $header_client_id = '';
	private $client_info = array();
	private $packet_body = '';
	
	private $response_error = 0;
	private $encrypt_type = 0;
	
	private $client_encrypt_key = array(
		array(
		    "sign_iv"=>"2BF3F51C" ,
		    "sign"=>"2BF3F51CB04AA2F6E916CC59",
		),
    );
	
	public function test11(){
		die();
		if(empty($_REQUEST['u']) ){
			exit('error');
		}
		$uid = intval($_REQUEST['u']);
		$begin = 0;
		$sql = "SELECT cp_user_account.uid,sum(winnings_bonus) as bonus,user_account_balance FROM `cp_jczq_ticket` LEFT JOIN cp_user_account on cp_user_account.uid=cp_jczq_ticket.uid where issue_nos like '%160815-004%' and winnings_status = 1 and cp_user_account.uid=".$uid." group by cp_user_account.uid HAVING user_account_balance>=sum(winnings_bonus)  limit 1";
		$list = M()->query($sql);
		foreach($list as $item){
			print_r($item);
			echo "<br>";
			M()->startTrans();
			$log_res = $this->updatelog($item);
			$acc_res = $this->updateAcc($item);
			if(!$log_res || !$acc_res){
				echo "error:<br>";
				print_r($item);
				M()->rollback();
				continue;
			}
			M()->commit();
		}
	}
	
	private function updatelog($item){
		$data['uid'] = $item['uid'];
		$data['ual_type'] = 13;
		$data['ual_amount'] = -($item['bonus']);
		$data['ual_balance'] = floatval($item['user_account_balance']-$item['bonus']);
		$data['ual_create_time'] = getCurrentTime();
		$data['ual_remark'] = '0815004比赛中断扣款';
		return D('UserAccountLog')->add($data);
	} 
	
	private function updateAcc($item){
		$map['uid'] = $item['uid'];
		$map['user_account_balance'] = array('egt',$item['bonus']);
		$data['user_account_balance'] = $item['user_account_balance']-$item['bonus'];
		return D('UserAccount')->where($map)->save($data);
	}
	
	/*
	 * 入口函数
	 */
	public function index() {
		dump($_SERVER['HTTP_HOST']);
		$lottery_list = R('Issue/lotteryList');
		$lottery_list = $lottery_list['result']['list'];
		$this->assign('lottery_list', $lottery_list);
		$this->display();
	}

	public function buy(){
	
		$lottery_id = I('lottery_id');
		$request_body = $this->_add($lottery_id);		
	
		$this->buildRequestEncryptType();
		$request_packet = $this->buildRequestPacket($request_body);
		// 		$result = $this->request_by_curl("http://lottery.cn/", $request_packet);
		$result = $this->request_by_curl("http://$_SERVER[HTTP_HOST]/", $request_packet);
		$filePath = './input.txt';
		file_put_contents($filePath, $result);
		
		$fp = fopen ($filePath, 'rb' );
		fseek($fp, 8, SEEK_CUR);
		
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

		@unlink($filePath);
		if($header['error_code'] == 0){
			$this->success('下注成功！') ;
		}else{
			$this->error('下注失败！错误码：'.$header['error_code']);
		}
		
	}
	
	
	public function getStakeCount(){
		$lottery_id = I('lottery_id');
		$bet_number = I('bet_number');
		$play_type	= I('play_type');
		$verifyNumber = Factory::createVerifyObj($lottery_id);
		$quantity = $verifyNumber->getTicketQuantity($bet_number, $play_type);
		echo $quantity;
	}
	
	
	public function getPlayType(){
		$conf = array(
			'1' => array(
				'标准' => 1					
			),
			'2' => array(
				'直选' => 11,
				'组三' => 12,
				'组六' => 13					 
			),
			'3' => array(
				'标准' => 1,
				'追加' => 2
			),
			'4' => array(
				'任二' 	=> 22,
				'任三' 	=> 23,
				'任四' 	=> 24,
				'任五' 	=> 25,
				'任六' 	=> 26,
				'任七' 	=> 27,
				'任八' 	=> 28,
				'前一' 	=> 29,
				'前二组选' => 30,
				'前二直选' => 31,
				'前三组选' => 32,
				'前三直选' => 33,
				
			),
			'5' => array(
				'和值' 		=> 41,
				'三同号单选' 	=> 42,
				'三同号通选' 	=> 43,
				'三连号通选' 	=> 44,
				'三不同号投注' 	=> 45,
				'二同号单选' 	=> 46,
				'二同号复选' 	=> 47,
				'二不同号投注' 	=> 48,
			)			
		);
		
		$lottery_id = I('lottery_id');
		$play_type_list = $conf[$lottery_id];
		$this->assign('play_type_list', $play_type_list);
		$this->display();
	}
	
	private function _add($lottery_id){
		$this->act = 10501;
		$issueId = D('Issue')->getCurrentIssueId($lottery_id);
		
		$data = array(
				'multiple' => intval(I('multiple' ,1)),
				'follow_times' => I('follow_times' ,1),
				'issue_id' => $issueId,
				'coupon_id' => 0,
				'order_identity' => random_string(20),
				'tickets' => array(
						array(
								'bet_number' 	=> I('bet_number'),
								'play_type'  	=> I('play_type') ? I('play_type') : '1',  
								'bet_type'   	=> I('bet_type'), 
								'stake_count'	=> I('stake_count'),
								'total_amount'	=> I('total_amount'),
						),
				),
		);
		$public_para = $this->getPublicParameters();
		error_log(var_export(array_merge($data, $public_para),true).PHP_EOL, 3, './log.txt');
		return array_merge($data, $public_para);
	}
	
	private function _addDltOrder() {
		$this->act = 10501;
	
		$issueId = D('Issue')->getCurrentIssueId(3);
	
		$data = array(
				'multiple' => I('multiple') ? I('multiple') : 1,
				'follow_times' => I('follow_times') ? I('follow_times') : 1,
				'issue_id' => $issueId,
				'coupon_id' => 0,
				'tickets' => array(
						array(
								'bet_number' => I('bet_num'),
								'play_type'  => 1,  // 玩法
								'bet_type'   => 3,  // 选好方式
								'stake_count'=> 8,
								'total_amount'=> 16,
						),
				),
		);
		$public_para = $this->getPublicParameters();
		return array_merge($data, $public_para);
	}
	
	private function _addJsksOrder(){
		$this->act = 10501;
	
		$issueId = D('Issue')->getCurrentIssueId(5);
	
		$data = array(
				'multiple' => 1,
				'follow_times' => 1,
				'issue_id' => $issueId,
				'coupon_id' => 0,
				'tickets' => array(
						array(
								'bet_number' => 4,
								'play_type'  => 41,  // 玩法
								'bet_type'   => 1,  // 选好方式
								'stake_count'=> 1,
								'total_amount'=> 2,
						),
				),
		);
		$public_para = $this->getPublicParameters();
		return array_merge($data, $public_para);
	}
	
	private function _addSsqOrder() {
		$this->act = 10501;
	
		$issueId = D('Issue')->getCurrentIssueId(1);
	
		$data = array(
				'multiple' => 1,
				'follow_times' => 2,
				'issue_id' => $issueId,
				'coupon_id' => 36,
				'tickets' => array(
						 
						// 单式、复式 要实现程序自动判断
						 
						array(
								'bet_number' => rand(10, 13).',02,'.rand(14, 17).',04@'.rand(30, 33).',06,'.rand(25, 29).',08#01,'.rand(10, 13).','.rand(14, 16),
								'play_type'  => 1,  // 玩法
								'bet_type'   => 3,  // 选好方式
								'stake_count'=> 18,
								'total_amount'=> 36,
						),
						array(
								'bet_number' => rand(10, 13).',09,'.rand(14, 17).','.rand(30, 33).',03,'.rand(25, 29).'#'.rand(10, 16),
								'play_type'  => 1,
								'bet_type'   => 1,
								'stake_count'=> 1,
								'total_amount'=>2,
						),
						array(
								'bet_number' => rand(10, 13).',04,'.rand(14, 17).','.rand(30, 33).',05,'.rand(25, 29).'#'.rand(10, 16),
								'play_type'  => 1,
								'bet_type'   => 1,
								'stake_count'=> 1,
								'total_amount'=>2,
						),
						array(
								'bet_number' => rand(10, 13).',07,08,'.rand(14, 17).','.rand(18, 22).','.rand(23, 27).',09,'.rand(30, 33).'#'.rand(10, 16),
								'play_type'  => 1,
								'bet_type'   => 2,
								'stake_count'=> 28,
								'total_amount'=>56,
						),
						array(
								'bet_number' => rand(10, 13).',06,'.rand(14, 17).',05,02,'.rand(30, 33).'#03,'.rand(10, 16),
								'play_type'  => 1,
								'bet_type'   => 2,
								'stake_count'=> 2,
								'total_amount'=>4,
						),
				),
		);
		$public_para = $this->getPublicParameters();
		return array_merge($data, $public_para);
	}
	
	private function _addFc3dOrder() {
		$this->act = 10501;
		$issueId = D('Issue')->getCurrentIssueId(2);
		$data = array(
				'multiple' => 1,
				'follow_times' => 1,
				'issue_id' => $issueId,
				'coupon_id' => 36,
				'tickets' => array(
						array(
								'bet_number' => '01,00#00,01,03#01,00',
								'play_type'  => 11,  // 玩法
								'bet_type'   => 2,  // 选号方式
								'stake_count'=> 12,
								'total_amount'=> 24,
						),
						array(
								'bet_number' => '01,00#00,01,03#01,00',
								'play_type'  => 11,  // 玩法
								'bet_type'   => 2,  // 选号方式
								'stake_count'=> 12,
								'total_amount'=> 24,
						),
				),
		);
		$public_para = $this->getPublicParameters();
		return array_merge($data, $public_para);
	}
	
	private function _addSyxwOrder() {
	    $this->act = 10501;
	    $issueId = D('Issue')->getCurrentIssueId(4);
	    $data = array(
	        'multiple' => 1,
	        'follow_times' => 2,
	        'issue_id' => $issueId,
	        'coupon_id' => 36,
	        'tickets' => array(
	            array(
	                'bet_number' => '02,03,01@04,06,07,05',
	                'play_type'  => '24',  // 玩法
	                'bet_type'   => 3,  // 选号方式
	                'stake_count'=> 4,
	                'total_amount'=> 8,
	            ),
	            array(
	                'bet_number' => '01,02#03,04#05,06,07',
	                'play_type'  => '32',  // 玩法
	                'bet_type'   => 2,  // 选号方式
	                'stake_count'=> 12,
	                'total_amount'=> 24,
	            ),
	        ),
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _getlotteryList() {
	    $this->act = 10301;
	    $data = array(
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _getCurrentIssue() {
	    $this->act = 10305;
	    $data = array(
	        'lottery_id' => 1,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _cancelFollow() {
	    $this->act = 10506;
	    $data = array(
	        'order_id' => 340,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _deductOrder() {
	    $this->act = 10505;
	    $data = array(
// 	        'recharge_order_id' => 47,
	        'pay_passwd' => '123456',
	        'order_id' => 72,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _getRechargeInfo() {
	    $this->act = 10404;
	    $data = array(
	        'recharge_order_id' => 47,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _userRecharge() {
	    $this->act = 10403;
	    $data = array(
	        'money' => 112,
	        'recharge_channel_id' => 1,
	        'remark' => 'adfasdf'
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _getPlatformList() {
	    $this->act = 10401;
	    $data = array(
	       
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _userWithdraw() {
	    $this->act = 10402;
	    $data = array(
	        'money' => 1000,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _getActivityList() {
	    $this->act = 10304;
	    $data = array(
	        'offset' => 0,
	        'limit' => 10,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _getWinningsList() {
	    $this->act = 10303;
	    $data = array(
	        'issue_id' => 1403,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _saveIdentityCardInfo() {
	    $this->act = 10214;
	    $data = array(
	        'realname' => '小明',
	        'identity_no' => '999888777666555',
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _switchFreePassword() {
	    $this->act = 10213;
	    $data = array(
	        'switch' => 1,
	        'pay_passwd' => 123456,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _resetPaymentPassword() {
	    $this->act = 10205;
	    $data = array(
	        'sms_validation' => 123456,
	        'pay_passwd' => 123456,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _setFreePassword() {
	    $this->act = 10212;
	    $data = array(
	        'sms_validation' => 123456,
	        'order_limit' => 51,
	        'day_limit' => 201,
	        'pay_passwd' => 123456,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _getFreePasswordInfo() {
	    $this->act = 10211;
	    $data = array();
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _getUserCouponList() {
	    $this->act = 10604;
	    $data = array('type'=>1, 'offset'=>0, 'limit'=>10);
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _exchangeCoupon() {
	    $this->act = 10602;
	    $data = array('coupon_code'=>'DDCDEFGHIJKL');
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _getCouponList() {
	    $this->act = 10603;
	    $data = array('offset'=>0, 'limit'=>10);
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _buyCoupon() {
	    $this->act = 10601;
	    $data = array('coupon_id'=>1);
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _resetLoginPassword() {
	    $this->act = 10204;
	    $data = array('passwd'=>'654321', 'sms_validation'=>'555555');
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _getIssueInfo() {
	    $this->act = 10302;
	    $data = array('lottery_id'=>0, 'offset'=>0, 'limit'=>10);
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _deleteOrder() {
	    $this->act = 10504;
	    $data = array('order_id'=>1205);
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	

	private function _saveBankCardInfo() {
	    $this->act = 10208;
	    $data = array('type'=>'招商银行', 'no'=>'11111111111111111111111', 'account'=>'Funsion', 'address'=>'大天朝');
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _orders() {
	    $this->act = 10502;
	    $data = array('lottery_id'=>'001', 'order_type'=>0, 'offset'=>0, 'limit'=>10);
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _detail() {
	    $this->act = 10503;
	    $data = array('order_id'=>1205);
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _getUserAccount() {
	    $this->act = 10209;
	    $data = array();
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _getBanKCardInfo() {
	    $this->act = 10207;
	    $data = array();
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _getUserInfo() {
	    $this->act = 10206;
	    $data = array();
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _setLoginPassword() {
	    $this->act = 10210;
	    $data = array('tel'=>'13850178037', 'passwd'=>'333333', 'sms_validation'=>'489510');
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _setPaymentPassword() {
	    $this->act = 10205;
	    $data = array('tel'=>'13850178037', 'passwd'=>'222222', 'sms_validation'=>'583412');
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _verifySms() {
	    $this->act = 10102;
	    $data = array('tel'=>'13850178037', 'type'=>2);
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _userLogout() {
	    $this->act = 10202;
	    $data = array('token'=>'987351998bd49a2f327822450ca74584');
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	
	
	private function _userLogin(){
	    $this->act = 10201;
	
	    $ads = array(
	        'tel' => '13777777777',
	        'passwd' => '123456',
	        'sms_validation' => '123456',
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($ads, $public_para);
	}
	
	
    private function _userRegister(){
		$this->act = 10203;
		
		$ads = array(
					'tel' => '138501'.random_string(5,'int'),
					'passwd' => '123456',
					'sms_validation' => '706431',
				);
		$public_para = $this->getPublicParameters();
		return array_merge($ads, $public_para);
	}
	
	
	private function getGetClientIdParameters(){
	    $this->act = 10101;
	    
		$data['public_key'] = 'BC8F90EE5F74B18DAFB1DB14B017F56E2D322312B94D7FA1E9ED51FADA13F4820EB9BDC04A0848722025C49D0B6ABC935E200EE937261203E36805C6CA8EADB3';
		
		$sign = 'mFNffmq5uw5aibqwngMcnjURlAFsDN2yTrcOPOxwcfZERcfuK1N9bDf9IeSg9Z+7a9qOCu/sCJNFD9JFG7s8AQ==';        // HksQmiPhUEl4DfdoqIM5CbaX
		$sign_iv = 'PneWUk76t4YuIGkJloDsIHZBb7GncibO7AElTX5xbiUgbqcJaDPOq1owNESQ3mHaW8LNnWlrzq50BOUEk/jaXg==';	   // 75G6pW2k
		$data['key'] = array(
				           	array('sign' => $sign,'sign_iv' => $sign_iv),
							array("sign"=>'wangsigui',"sign_iv"=>''), );
		$body = array(
					'device_info' => array(
						'country' => 'cn',
						'root' => 0,
						'imsi' => '460027059205386x',
						'imei' => 'imei-2jifdalfjioooofd333',
						'mac' => 'mac-2460027059205333',
						'language' => 'zh',
						'manufacturer' => 'LGE',
						'network_operator' => 'CMCC',
						'os_id' => 15,
						'os_version' => '4.0.3',
						'phone_number' => '18705927637',
						'screenDensity' => '2.0',
						'screen_size' => '720_1280',
						'sim_serial_number' => '898600',
						'unknown_source' => '1',
						),
					'device_sign' => 'fffffffffff',
					'package_name' => 'com.adcocoa.demo',
					'platform_id' => '13872c5a3cfafb1e639b619c3a25c7ab'
				);
		$data['body'] = base64_encode(encrypt3des($this->client_encrypt_key[0]['sign'], $this->client_encrypt_key[0]['sign_iv'], json_encode($body)));	
		$public_para = $this->getPublicParameters();		
		return array_merge($data, $public_para);
	}
	
	private function getPublicParameters(){
		return array(
			'model' => 'LG-P880',
		    'version' => '4.0.3',
		    'network' => 1,
		    'os' => 1,
		    'mode' => 0
		  );
	}

	private	function request_by_curl($remote_server, $post_string) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $remote_server);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "");
		$data = curl_exec($ch);
// 		print_r($data);
		curl_close($ch);
		return $data;
	}
	
	/*
	 * 构造一个合适的返回加密方式
	 */
	private function buildRequestEncryptType(){
		if($this->act == 10101){
			$this->client_id = md5(rand(1,100));	
			$this->encrypt_type = bindec('10000000');
		}else{
			$this->client_id = '219bfb0845ba30d65e8152389feece3a';	//session_id
			$this->encrypt_type = bindec('00000011');
		}
	}
	
	/*
	 * 封装请求数据包
	 */
	private function buildRequestPacket($data){
		//封装包体数据
		$request_body   = $this->buildRequestPacketBody($data);
		$length = empty($request_body) ? 0 : strlen($request_body);

		$request_header = $this->buildRequestPacketHeader($length);
		return $request_header.$request_body;
	}

	/*
	 * 封装请求数据包头
	 */
	private function buildRequestPacketHeader($length) {
		Load('extend');
		$header = pack ( 'a8', random_string(4) );
		$header .= pack ( 'n', $this->sdk_version );
		$header .= pack ( 'n', $this->act );
		$header .= pack ( 'N', $length );
		$header .= pack ( 'a32', $this->client_id );
		$header .= pack ( 'C', $this->encrypt_type );
		$header .= pack ( 'a15', random_string(15) );
		return $header;
	}

	/*
	 * 封装请求数据包体
	 */
	private function buildRequestPacketBody($body) {
		if (empty($body))return '';
		$body  = json_encode($body);
		
		$encrypt_array = DecToBinArray($this->encrypt_type);
		if($encrypt_array[7] == 1){			
			$client_encrypt_key = $this->client_encrypt_key;
			if($encrypt_array[5] == 1){//aes加密
				$body = encryptAes($client_encrypt_key[1]['sign'], $body);
			}elseif($encrypt_array[6] == 1){//3des加密
				$body = encrypt3des($client_encrypt_key[0]['sign'], $client_encrypt_key[0]['sign_iv'], $body);
			}
		}
		if($encrypt_array[0] == 1){
			$body = gzencode($body, 3);
		}
		$body = base64_encode($body);
		return $body;
	}
    
	public function csRpt(){
		$x = $_REQUEST['x'];
		$from = $_REQUEST['f'];
		$to = $_REQUEST['t'];
		$p = $_REQUEST['p'];
		if($x){
			if($x!='hc001'){
				die();
			}

			$_readDB = 'mysql://tigercai_server:e4huY8J7e4@rr-bp1y62h5vwc8o62yt.mysql.rds.aliyuncs.com:3306/tigercai';
			$sql = 'select user_real_name,user_telephone,sum(order_total_amount) as total,sum(order_refund_amount) as refund,(sum(order_total_amount)-sum(order_refund_amount)) as real1 from cp_order LEFT JOIN cp_user on cp_order.uid=cp_user.uid WHERE
order_status>=3 AND
order_create_time >="'.$from.'" AND
order_create_time <="'.$to.'" AND
user_telephone in(
'.$p.') GROUP BY cp_user.uid';
			$data = M()->db(1,$this->_readDB,true)->query($sql);
			echo 'result:';
			echo '<table border="1">';
			foreach($data as $user_info){
				echo "<tr>";
				echo "<td>".$user_info['user_real_name']."</td>";
				echo "<td>".$user_info['user_telephone']."</td>";
				echo "<td>".$user_info['total']."</td>";
				echo "<td>".$user_info['refund']."</td>";
				echo "<td>".$user_info['real1']."</td>";
				echo "</tr>";
				
			}
			echo '</table>';
				
		}
			echo <<<TTT
<!DOCTYPE html>
<html>
<body>
<form action='http://phone.api.tigercai.com/Home/TwwTest/csRpt/' method='post'>
password:<br>
<input type='text' name='x' value=''>
<br>
from:<br>
<input type='text' name='f' value=''>
<br>
to:<br>
<input type='text' name='t' value=''>
<br>
phone:<br>
<textarea name='p'></textarea>
<br>
					<input type="submit" value="submit">
					</form>
</body>
</html>
TTT;
		
		
	}
}
