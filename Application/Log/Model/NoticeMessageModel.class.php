<?php
namespace Log\Model;
use Log\Model\NoticeModel;
/**
 * @date 2014-12-25
 * @author tww <merry2014@vip.qq.com>
 */
class NoticeMessageModel extends NoticeModel{
	public function send($event_id, $message, $el_launch){
		$contacts = D('EventUser')->getInfos($event_id);
		$phones = array();

		foreach ($contacts as $contact){
			$phones[] = $contact['eu_user_phone'];
		}
		
		
		if($phones){
			$datas = array($message);
			$tempId = 82542;
			foreach ($phones as $phone){
				$result = sendTemplateSMS($phone, $datas, $tempId);
				$send_result = 0;
				if($result['errorCode'] == '000000'){
					$send_result = 1;
				}
				$this->saveLog($event_id, C('SEND_TYPE.MESSAGE'), $message, $el_launch, $phone, $send_result);
			}
		}else{
			$this->saveLog($event_id, C('SEND_TYPE.MESSAGE'), $message, $el_launch, '未指定', false);
		}
		
	}
}