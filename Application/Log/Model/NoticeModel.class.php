<?php
namespace Log\Model;
/**
 * @date 2014-12-25
 * @author tww <merry2014@vip.qq.com>
 */
abstract class NoticeModel{
	abstract public function send($event_id, $message, $el_launch);
	
	public function saveLog($event_id, $el_type, $el_content, $el_launch, $el_receive, $el_send_status){
		$log = array();
		$log['event_id']		= $event_id;
		$log['el_type'] 		= $el_type;
		$log['el_content'] 		= $el_content;
		$log['el_launch'] 		= $el_launch;
		$log['el_receive'] 		= $el_receive;
		$log['el_send_status'] 	= $el_send_status;
		return M('EventLog')->add($log);
	}
}