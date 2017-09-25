<?php
namespace Home\Controller;
use Think\Controller;
use Home\Util\VerifyKsNumber;
/**
 * @date 2014-12-5
 * @author tww <merry2014@vip.qq.com>
 */
class TwwController extends  Controller{

	public function test(){
	    
	    
	    $attrs = 'name="Public/base"';
	    $str = '<tpl><tag ' . $attrs . ' />sdf</tpl>';
	    $xml = simplexml_load_string($str);
	    dump($xml);
	    $a = $xml->tag->attributes();
	 
	    dump((array)$xml->tag->attributes());
	    
	    foreach($a as $key=>$value){
	        $xml_to_list1[(string)$key] = (string)$value;
	    }
	    
	    
// 	    $xml_to_list1    =   current($a);
	   

	    dump($xml_to_list1);
	
	   
      
// 	    $api = array();p
// 	    $api['money'] = '1';
// 	    $api['recharge_channel_id'] = 5;
// 	    $api['model']   = 'LG-P880';
// 	    $api['version']   = '4.0.3';
// 	    $api['network']   = 1;
// 	    $api['os']   = 1;
// 	    $api['mode']   = 0;
// 	    $api['session'] = '044e784416a332ec249194fffffa9c57';
// 	    $api['act'] = '10403';
// 	    $api['remark'] = 'ttt';
// 	    $api = $this->array2object($api);
// 	    $result = R('Recharge/userRecharge', array($api));
// 	    dump($result);
	}
	
	function getDataForXML($res_data,$node)
	{
	    $xml = simplexml_load_string($res_data);
	    $result = $xml->xpath($node);
	
	    while(list( , $node) = each($result))
	    {
	        return $node;
	    }
	}
	
	function array2object($array) {
	
	    if (is_array($array)) {
	        $obj = new \stdClass();
	
	        foreach ($array as $key => $val){
	            $obj->$key = $val;
	        }
	    }
	    else { $obj = $array; }
	
	    return $obj;
	}
	public function index(){
		header("Content-type: text/html; charset=utf-8");
		$ks = new VerifyKsNumber();
		$ks_case = $this->ks_case();
		foreach ($ks_case as $key=>$cases){
			$play_type = $key;
			foreach ($cases as $bet_num => $quantity){			
				$result = $ks->getTicketQuantity($bet_num, $play_type);
				$verify_resulr = $ks->verify($bet_num, $play_type);
				
				if(!$verify_resulr){
					dump('格式错误：'.$bet_num);
				}
				
				if($result != $quantity){
					dump('注数不相等：'.$result.'!='.$quantity);
				}
			}
			
		}
		echo PHP_EOL.'complete!!!';
	}

	public function ks_case(){
		$test_demo = array(
			C('KS_PLAY_TYPE.SUM') 					=> array('4'=>1),
			C('KS_PLAY_TYPE.THREE_SAME_NUM_SINGLE') => array('1,1,1'=>1),
			C('KS_PLAY_TYPE.THREE_SAME_NUM_ALL') 	=> array('A,A,A'=>1),
			C('KS_PLAY_TYPE.THREE_SEQUENCE_ALL') 	=> array('A,B,C'=>1),
			C('KS_PLAY_TYPE.THREE_DIFF_NUM') 		=> array('1,2,5'=>1),
			C('KS_PLAY_TYPE.TWO_SAME_NUM_SINGLE') 	=> array('2,2,1'=>1 ,'1,1,3'=>1),
			C('KS_PLAY_TYPE.TWO_SAME_NUM_ALL') 		=> array('2,2,*'=>1, '1,1,*'=>1),
			C('KS_PLAY_TYPE.TWO_DIFF_NUM') 			=> array('1,2'=>1)
		);
		return $test_demo;
	}
	
	
	public function getWinning(){
		$request_params = $_POST;
		$resp = array();
		foreach($request_params['ticket_list'] as $order_id=>$ticket_list){
			$map['order_id'] = $order_id;
			$map['ticket_seq'] = array('IN',$ticket_list);
			$res_list = D('CalculateResult')->where($map)->select();
			if($order_id==111){
				ApiLog('res:'.M()->_sql().print_r($res_list,true),'mock');
			}
			foreach($res_list as $res_info){
				$ticket_item['order_id'] = $res_info['order_id'];
				$ticket_item['ticket_seq'] = $res_info['ticket_seq'];
				$ticket_item['bonus_amount'] = $res_info['cr_bonus_amount'];
				$ticket_item['status'] = 1;
				$ticket_item['is_big'] = 0;
				$resp[] = $ticket_item;
			}
			if($order_id==111){
				ApiLog('resp:'.print_r($resp,true),'mock');
			}
		}
		ApiLog('ress resp:'.print_r($resp,true),'mock');
		
		echo json_encode(array('error'=>0,'data'=>array('ticket_list'=>$resp)));
		
	}
}