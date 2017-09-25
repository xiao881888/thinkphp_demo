<?php

namespace Home\Controller;

use Home\Util\Factory;
use Think\Controller;

class WUController extends Controller{
	const JUMP_TYPE_FOR_APP = 0;
	private $_request_params = null;
	private $_session_code = null;
	private $_user_info = null;
	private $_uid = null;
	private $_msg_map = null;

	public function __construct(){
		import('@.Util.AppException');
		parent::__construct();
		$this->_msg_map = C('WEB_IDENTIFY_MESSAGE');
		$this->_request_params = $this->_parseWebEncryptedParams();
		$this->_user_info = $this->queryUserInfoBySessionCode($this->_session_code);
		$this->_uid = $this->_user_info['uid'];
	}

	protected function queryUserInfoBySessionCode($session_code){
		$uid = D('Session')->getUid($session_code);
		ApiLog('uid:' . M()->_sql() . '====' . $uid, 'wsess');
		if (empty($uid)) {
			$this->_redirectToFailPageAndExit();
		}
		$user_info = D('User')->getUserInfo($uid);
		$user_is_available = $user_info['user_status'] == C('USER_STATUS.ENABLE');
		ApiLog('$user_info:' . M()->_sql() . '==' . $user_is_available . '==' . print_r($user_info, true), 'wsess');
		
		if (!$user_is_available) {
			$this->_redirectToFailPageAndExit();
		}
		$user_is_identified = $user_info['user_identity_card_status'] ;
		if($user_is_identified){
			//已绑定过
			$this->_redirectToFailPageAndExit();
		}
		return $user_info;
	}

	public function identify(){
		if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
			$pay_url = 'http://'.$_SERVER['HTTP_HOST'].'/' . U('WU/submitIdentifyInfo');
		}elseif(get_cfg_var('PROJECT_RUN_MODE') == 'TEST' ){
			/*$pay_url = 'http://192.168.3.171:81/index.php?s=/Home/' . U('WP/payOrder');*/
			$pay_url = 'http://test.phone.api.tigercai.com/index.php?s=/Home/' . U('WU/submitIdentifyInfo');
		}else {
			$pay_url = 'http://192.168.1.171:81/index.php?s=/Home/' . U('WU/submitIdentifyInfo');
		}
		$this->display();
	}

	private function _parseWebEncryptedParams(){
		$session_code = $_REQUEST['s'];
		$this->_session_code = $session_code;
		$decrypt_params = $this->_getParamsFromRechargeUniqueCode($_REQUEST['r']);
		
		$request_params = json_decode($decrypt_params, true);
		ApiLog('parseParams:' . print_r($request_params, true), 'reuni');
		return $request_params;
	}
	
	private function _getParamsFromRechargeUniqueCode($recharge_code){
		ApiLog('$$$recharge_code:' . print_r($recharge_code, true), 'reuni');
		if(!$recharge_code){
			$this->_redirectToFailPageAndExit();
		}
		$key = 'rccode:'.$recharge_code;
		$redis_instance = Factory::createRedisObj();
		$raw_json_params = $redis_instance->get($key);
		ApiLog('$key:' . print_r($raw_json_params, true), 'reuni');
		if(empty($raw_json_params)){
			$this->_redirectToFailPageAndExit();
		}
		return $raw_json_params;
	}

	private function _redirectToFailPageAndExit(){
		$this->_redirectToFailPage($this->_msg_map['NETWORK_ERROR'], self::JUMP_TYPE_FOR_APP);
		exit();
	}

	private function _getUserEncryptKey($session_code){
		$encrypt_key = D('Session')->getEncryptKey($session_code);
		ApiLog('$encrypt_key:' . $session_code . '===' . print_r($encrypt_key, true), 'wsess');
		if (empty($encrypt_key)) {
			$this->_redirectToFailPageAndExit();
		}
		return json_decode($encrypt_key, true);
	}

	private function _exitAndReturnJson($error_code = '', $data = array()){
		if ($error_code) {
			$response['error'] = 1;
			$response['msg'] = $this->_msg_map[$error_code];
		} else {
			$response['error'] = 0;
			$response['msg'] = '';
		}
		$response['data'] = $data;
		echo json_encode($response);
		exit();
	}
	
	public function submitIdentifyInfo(){
		$user_real_name = I('name');
		$id_card_number = I('idCard');
		
		$id_card_user_count = D('User')->countUserByIdCard($id_card_number);
		if ($id_card_user_count >= 1) {
			$this->_exitAndReturnJson('IDENTITY_BIND_TOO_MUCH');
		}
		
		$save_result = D('User')->saveIdentityCard($this->_uid, $user_real_name, $id_card_number);
		ApiLog('saveIDCard'.$user_real_name.'===='.$id_card_number.'===='.var_export($save_result, true), 'HGYDEBUG');
		if(!$save_result){
			$this->_exitAndReturnJson('DATABASE_ERROR');
		}
		$url = $this->_buildRechargeUrl($this->_request_params,$this->_uid);
		$response_data['success_url'] = $url;
		$this->_exitAndReturnJson('', $response_data);
	}

	private function _buildRechargeUrl($request_params,$uid){
		$recharge_channel_id = $request_params['recharge_channel_id'];
		$recharge_id = $request_params['recharge_id'];
		$recharge_sku = $request_params['recharge_sku'];
		$money = $request_params['money'];
		if($recharge_channel_id==PAYMENT_CHANNEL_ID_OF_YEEPAY){
			return A('Yeepay')->genYeepayTargetUrl($recharge_sku, $money, $recharge_id, $uid);
		}
		
		if ($recharge_channel_id == PAYMENT_CHANNEL_ID_OF_BAOFU) {
			$params = array();
			$params['id'] = $recharge_id;
			$params['rd'] = random_string(8);
			$params['md'] = md5($params['id'].$params['rd'].RECHARGE_URL_MD5_SALT);
		
			return U('Home/Baofu/recharge@'.$_SERVER['HTTP_HOST'], $params, '', true);
		}
	}
			
	private function _redirectToFailPage($error_msg, $jump_page = ''){
		$this->assign('error_msg', $error_msg);
		$this->assign('jump_page', $jump_page);
		$this->display('error');
	}

	private function _redirectToSuccessPage($success_msg){
		if ($this->_request_params['lottery_id']) {
			$this->assign('lottery_id', intval($this->_request_params['lottery_id']));
		}
		$this->assign('success_msg', $success_msg);
		$this->display('success');
	}
}