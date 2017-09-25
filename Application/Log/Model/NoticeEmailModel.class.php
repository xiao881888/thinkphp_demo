<?php
namespace Log\Model;
use Log\Model\NoticeModel;
/**
 * @date 2014-12-25
 * @author tww <merry2014@vip.qq.com>
 */
class NoticeEmailModel extends NoticeModel{
	public function send($event_id, $message, $el_launch){
		$contacts = D('EventUser')->getInfos($event_id);
		$title = '日志系统邮件通知';
		if($contacts){
			foreach ($contacts as $contact){
				$email = $contact['eu_user_email'];
				$result = SendMail($email, $title, $message);
				$this->saveLog($event_id, C('SEND_TYPE.EMAIL'), $message, $el_launch, $email, $result);
			}
		}else{
			$this->saveLog($event_id, C('SEND_TYPE.EMAIL'), $message, $el_launch, '未指定', false);
		}
		return $result;
	}
}