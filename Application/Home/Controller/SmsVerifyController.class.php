<?php

namespace Home\Controller;

use Think\Controller;

class SmsVerifyController extends Controller{

	public function sendVerificationCodeBySMS($api){
		$verifyCode = random_string(6, 'int');
		$smsTempId = $this->_getSmsTempId($api);
		ApiLog('sms test:' . print_r($smsTempId, true) . '===' . $api->tel . '==' . $verifyCode . '===' . $api->type, 'sms');
		
		$sendSuccess = $this->_sendSmsVerifyToUser($api->tel, $verifyCode, $smsTempId);
		\AppException::ifNoExistThrowException($sendSuccess, C('ERROR_CODE.SMS_SEND_ERROR'));
		
		$result = D('SmsVerify')->saveVerificationSms($api->tel, $verifyCode, $api->type);
		\AppException::ifNoExistThrowException($result, C('ERROR_CODE.DATABASE_ERROR'));
		return array(
				'result' => '',
				'code' => C('ERROR_CODE.SUCCESS') 
		);
	}

	private function _getSmsTempId($api){
        $app_name = getRequestAppId($api->bundleId);
        if($app_name == C('APP_ID_LIST.BAIWAN')){
            $smsTempId = getBaiWanSmsTempId($api->type);
        }elseif($app_name ==  C('APP_ID_LIST.TIGER')){
            $smsTempId = getSmsTempId($api->type);
        }elseif($app_name ==  C('APP_ID_LIST.NEW')){
            $smsTempId = getNewSmsTempId($api->type);
        }
        return $smsTempId;
    }

	public function checkVerificationCode($telephone, $smsVerify, $type){
		if( get_cfg_var('PROJECT_RUN_MODE') == 'TEST' ){
			return array(
				'inLifetime' => true,
				'equal' => true
			); // 测试代码
		}
		
		$smsInfo = D('SmsVerify')->getVerifyCode($telephone, $type);
		$inLifetime = (time() - strtotime($smsInfo['sv_create_time'])) < C('SMS_CONFIG.SMS_LIFETIME');
		$isEqual = ($smsInfo['sv_verify_code'] == $smsVerify);
		return array(
				'inLifetime' => $inLifetime,
				'equal' => $isEqual 
		);
	}

	private function _sendSmsVerifyToUser($telephone, $verifyCode, $tempId){
		/*if( get_cfg_var('PROJECT_RUN_MODE') == 'TEST' ){
			return true; // 测试代码
		}*/
		$message = array(
				$verifyCode,
				30 
		);
		$result = sendTemplateSMS($telephone, $message, $tempId);
		ApiLog('sms:' . print_r($result, true) . '===' . $telephone . '==' . $message . '===' . $tempId, 'sms');
		return ($result['errorCode'] == C('SMS_ERROR_CODE.SUCCESS'));
	}
}

?>