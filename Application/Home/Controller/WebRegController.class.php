<?php

namespace Home\Controller;

use Think\Controller;

class WebRegController extends Controller{
	const ERROR_CODE_OF_SUCCESS = 0;
	const ERROR_CODE_OF_FAIL = 1;
	private $_error_msg = array(
			'TEL_IS_EMPTY' => '请输入手机号码',
			'VERIFY_CODE_IS_EMPTY' => '请输入验证码',
			'TEL_IS_USED' => '手机号码已注册',
			'GET_VERIFY_CODE_ERROR' => '获取验证码失败',
			'VERIFY_CODE_IS_ERROR' => '验证码错误',
			'REGISTER_ERROR' => '注册失败' 
	);
	
	public function reg(){
		$this->display();
	}

    public function chinaVSIranReg(){
        $this->display();
    }

    public function chinaVSIranDownLoad(){
        $this->display();
    }

    public function chinaVSkoreaReg(){
        $this->display();
    }

	public function chinaVSkoreaDownLoad(){
        $this->display();
    }

	public function index(){
		$this->display();
	}
	
	public function success(){
		$this->display();
	}

	private function _verifyParamsForSmsVerifyCode(){
		$params['type'] = intval($_POST['type']);
		$params['tel'] = $_POST['tel'];
		if ($params['type'] != C('SMS_TYPE.REGISTER')) {
			$this->_exitAndReturnFailJson($this->_error_msg['GET_VERIFY_CODE_ERROR']);
		}
		if (!$params['tel']) {
			$this->_exitAndReturnFailJson($this->_error_msg['TEL_IS_EMPTY']);
		}
		
		$uid = D('User')->getUserId($params['tel']);
		if ($uid) {
			$this->_exitAndReturnFailJson($this->_error_msg['TEL_IS_USED']);
		}
		return $params;
	}

	public function sendSmsVerifyCode(){
		$params = $this->_verifyParamsForSmsVerifyCode();
		if (!$params) {
			$this->_exitAndReturnFailJson();
		}
		$type = $params['type'];
		$tel = $params['tel'];
		$verifyCode = random_string(6, 'int');
		$smsTempId = getSmsTempId($type);
		
		$send_sms_result = $this->_sendSmsVerifyToUser($tel, $verifyCode, $smsTempId);
		if (!$send_sms_result) {
			$this->_exitAndReturnFailJson($this->_error_msg['GET_VERIFY_CODE_ERROR']);
		}
		
		$result = D('SmsVerify')->saveVerificationSms($tel, $verifyCode, $type);
		if (!$result) {
			$this->_exitAndReturnFailJson($this->_error_msg['GET_VERIFY_CODE_ERROR']);
		}
		$this->_exitAndReturnSuccessJson();
	}

	private function _sendSmsVerifyToUser($tel, $verifyCode, $smsTempId){
		$message = array(
				$verifyCode,
				30 
		);
		$result = sendTemplateSMS($tel, $message, $smsTempId);
		return ($result['errorCode'] == C('SMS_ERROR_CODE.SUCCESS'));
	}

	private function _exitAndReturnFailJson($error_msg = ''){
		$this->_exitAndReturnJson(self::ERROR_CODE_OF_FAIL, $error_msg);
	}

	private function _exitAndReturnSuccessJson($data = ''){
		$this->_exitAndReturnJson(self::ERROR_CODE_OF_SUCCESS, $data);
	}

	private function _exitAndReturnJson($error_code, $info){
		$result['error'] = $error_code;
		$result['info'] = $info;
		exit(json_encode($result));
	}

	private function _verifyParamsForRegister(){
		$params['verify_code'] = $_POST['user_password'];
		$params['tel'] = $_POST['user_tel'];
		$params['cc'] = $_POST['cc'];
		if (empty($params['verify_code'])) {
			$this->_exitAndReturnFailJson($this->_error_msg['VERIFY_CODE_IS_EMPTY']);
		}
		
		if (empty($params['tel'])) {
			$this->_exitAndReturnFailJson($this->_error_msg['TEL_IS_EMPTY']);
		}
		
		$verifyResult = A('SmsVerify')->checkVerificationCode($params['tel'], $params['verify_code'], C('SMS_MESSAGE_TYPE.REGISTER'));
		if (!$verifyResult['equal'] || !$verifyResult['inLifetime']) {
			$this->_exitAndReturnFailJson($this->_error_msg['VERIFY_CODE_IS_ERROR']);
		}
		
		$uid = D('User')->getUserId($params['tel']);
		if ($uid) {
			$this->_exitAndReturnFailJson($this->_error_msg['TEL_IS_USED']);
		}
		return $params;
	}

	private function _buildDefaultPassword($telephone){
		$begin = rand(0, 4);
		return substr($telephone, $begin, 6);
	}

	public function register(){
		$params = $this->_verifyParamsForRegister();
		if (!$params) {
			$this->_exitAndReturnFailJson($this->_error_msg['VERIFY_CODE_IS_ERROR']);
		}
		$session = array();
		$channel_info = array();
		$extra_channel_info = $this->_getExtraChannelInfo($params);
		
		$password = $this->_buildDefaultPassword($params['tel']);
		ApiLog('pass:'.$password,'webreg');
		M()->startTrans();
		$uid = A('User')->registerCommon($params['tel'], $password, $session, $channel_info, $extra_channel_info);
        //$uid = D('User')->register($params['tel'], $password, $session, $channel_info, $extra_channel_info);
		if (!$uid) {
			M()->rollback();
			$this->_exitAndReturnFailJson($this->_error_msg['REGISTER_ERROR']);
		}
		
		$user_account_id = D('UserAccount')->addUserAccount($uid);
		if (!$user_account_id) {
			M()->rollback();
			$this->_exitAndReturnFailJson($this->_error_msg['REGISTER_ERROR']);
		}
		$res = $this->_noticeUserBySms($params['tel'], $password);
		if (!$res) {
			M()->rollback();
			$this->_exitAndReturnFailJson($this->_error_msg['REGISTER_ERROR']);
		}
		M()->commit();
		
		$this->_exitAndReturnSuccessJson();
	}

	private function _noticeUserBySms($user_telephone, $password){
		// web页面注册成功
		$smsTempId = 120237;
		$message = array(
				$password 
		);
		
		$result = sendTemplateSMS($user_telephone, $message, $smsTempId);
		return ($result['errorCode'] == C('SMS_ERROR_CODE.SUCCESS'));
	}

	private function _getExtraChannelInfo($params){
		$channel_code = $params['cc'];
		$channel_info['channel_type'] = 1;
		$channel_info['extra_channel_id'] = 0;
		if ($channel_code) {
			$map['web_channel_key'] = $channel_code;
			$web_channel_info = D('WebChannel')->where($map)->find();
			$channel_info['extra_channel_id'] = $web_channel_info['web_channel_id'];
		}
		return $channel_info;
	}
}

