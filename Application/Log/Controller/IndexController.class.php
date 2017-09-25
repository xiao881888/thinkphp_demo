<?php
namespace Log\Controller;
use Think\Controller;
/**
 * @date 2014-12-25
 * @author tww <merry2014@vip.qq.com>
 */
class IndexController extends Controller{
	public function index(){

		$event_id 	= I('event_id');
		$name 		= I('name');
		$message 	= I('message');

		if($event_id && $name && $message){
			$event_info  = D('Event')->getInfo($event_id);
			$event_level = $event_info['event_level'];
			
			$model = D(C('NOTICE_MODEL.'.$event_level));
			$result = $model->send($event_id, $message, $name);
			$respone = array('code'=>0);
			echo json_decode($respone);
		}
	}
	
	public function test(){
		$event_id 	= 1501;
		$name 		= 'tww';
		$message 	= '测试错误邮件！';
		
		if($event_id && $name && $message){
			$event_info  = D('Event')->getInfo($event_id);
			$event_level = $event_info['event_level'];
				
			$model = D(C('NOTICE_MODEL.'.$event_level));
			$result = $model->send($event_id, $message, $name);
			$respone = array('code'=>0);
			echo json_decode($respone);
		}
	}
}