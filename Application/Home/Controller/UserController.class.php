<?php 
namespace Home\Controller;
use Home\Controller\GlobalController;

class UserController extends GlobalController {
    
    public function register($api) {
        $verifyResult = A('SmsVerify')->checkVerificationCode($api->tel, $api->sms_validation, C('SMS_MESSAGE_TYPE.REGISTER'));
        \AppException::ifNoExistThrowException($verifyResult['equal'], C('ERROR_CODE.SMS_VERIFY_ERROR'));
        \AppException::ifNoExistThrowException($verifyResult['inLifetime'], C('ERROR_CODE.SMS_LIFETIME_ERROR'));
        
        $uid = D('User')->getUserId($api->tel);
        \AppException::ifExistThrowException($uid, C('ERROR_CODE.TELEPHONE_REGISTED'));
        
        $session = D('Session')->getSessionInfo($api->session);
        
        $channel_info = $this->_getChannelInfo($api);

        $extra_channel_info = $this->_getExtraChannelInfo($api);
        
        $uid = $this->registerCommon($api->tel, $api->passwd, $session, $channel_info,$extra_channel_info);
        \AppException::ifNoExistThrowException($uid, C('ERROR_CODE.DATABASE_ERROR'));
        
        if (!empty($session['device_id']) && $api->os == OS_OF_IOS) {
            $device_info = D('Device')->find($session['device_id']);
            $this->activeDevice($device_info);
        }

        $this->_autoLogin($api->session, $uid);
        $addUserAccount = D('UserAccount')->addUserAccount($uid);
        \AppException::ifNoExistThrowException($addUserAccount, C('ERROR_CODE.DATABASE_ERROR'));

        //注册就送5元红包活动，我们就是这么土豪！！！
        // $coupon_id = 8;
        // $coupon_info = D('Coupon')->getCouponInfo($coupon_id);
        // $give_coupon_result = D('UserCoupon')->addUserCoupon($uid, $coupon_info, C('USER_COUPON_LOG_TYPE.GIFT'), C('USER_COUPON_STATUS.AVAILABLE'), 0, 0, '注册就送红包');
        // if (!$give_coupon_result) {
        //     error_log(date('Y-m-d H:i:s').': 赠送红包失败！tel='.$api->tel.';uid='.$uid.PHP_EOL, 3, LOG_PATH.'register_falie.log');            
        // }

        $result = array('user'=>$this->_getUserInfo($uid));
        return array(   'result' => $result,
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }

    private function _getExtraChannelInfo($api){
        $extra_channel_info = array();
        $app_id = getRequestAppId($api->bundleId);
        if($app_id == C('APP_ID_LIST.BAIWAN')){
            $extra_channel_info['channel_type'] = C('BAIWAN_CHANNEL_TYPE');
            $extra_channel_info['extra_channel_id'] = C('BAIWAN_CHANNEL_ID');
        }
        if($app_id == C('APP_ID_LIST.NEW')){
            $extra_channel_info['channel_type'] = C('NEW_CHANNEL_TYPE');
            $extra_channel_info['extra_channel_id'] = C('NEW_CHANNEL_ID');
        }
        return $extra_channel_info;
    }

    public function registerCommon($telephone, $password, $session = array(), $channel_info = array(), $extra_channel_info = array()){
        $uid = D('User')->register($telephone, $password, $session, $channel_info, $extra_channel_info);
        if($uid){
            A('MsgQueueOfUser')->notifyUserRegister($uid);
        }
        return $uid;
    }

    public function registerCommonForH5($telephone, $password, $session = array(), $channel_info = array(), $extra_channel_info = array()){
        $uid = D('Home/User')->register($telephone, $password, $session, $channel_info, $extra_channel_info);
        if($uid){
            A('Home/MsgQueueOfUser')->notifyUserRegister($uid);
        }
        return $uid;
    }

    private function _getChannelInfo($api){
    	$channel_id = 0;
    	if($api->channel_id){
    		$channel_id = $api->channel_id;
    	}elseif($api->bundleId=='com.tigercai.TigerHGY'){
    		
    	}else{
    		
    	}
    	if(isset($api->bundleId)){
    		$channel_info['app_package'] = $api->bundleId;
    	}else{
    		$channel_info['app_package'] = '';
    	}
    	$channel_info['app_channel_id'] = $channel_id;
    	$channel_info['app_os'] = $api->os;
    	return $channel_info;
    }

    public function login($api) {
        $uid = D('User')->getUserId($api->tel);
        \AppException::ifNoExistThrowException($uid, C('ERROR_CODE.USER_NOT_EXIST'));
        
        $userLogin = D('User')->checkUserPassword($uid, $api->passwd);
        \AppException::ifNoExistThrowException($userLogin, C('ERROR_CODE.PASSWORD_ERROR'));
        
        $user_info = D('User')->getUserInfo($uid);
        if ($user_info['user_status'] == C('USER_STATUS.DISABLE')) {
        	\AppException::throwException(C('ERROR_CODE.USER_FORBIDDEN'));
//             \AppException::ifNoExistThrowException($allow, C('ERROR_CODE.USER_FORBIDDEN'));
        }

        $this->_autoLogin($api->session, $uid);
        $result = array('user'=>$this->_getUserInfo($uid));
        
        $session = D('Session')->getSessionInfo($api->session);
        D('User')->updateUserLoginDevice($uid, $session);

        A('Duobao')->setUserInfoToActivityRedis($api->session,$user_info);

        return array(   'result' => $result,
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }
    
    
    public function getFreePasswordInfo($api) {
        $userInfo = $this->getAvailableUser($api->session);
        $uid = $userInfo['uid'];
        $result = array(
            'switch' => $userInfo['user_password_free'],
            'order_limit' => $userInfo['user_pre_order_limit'],
            'day_limit' => $userInfo['user_pre_day_limit'],
        );
        
        return array(   'result' => $result,
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }
    
    
    public function logout($api) {
    	$uid = D('Session')->getUid($api->session);
    	D('PushDevice')->deleteConfig($uid);
        D('Session')->deleteSession($api->session);
        A('Duobao')->delUserInfoFromActivityRedis($api->session);
        return array(   'result' => '',
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }
    
    
    public function findLoginPassword($api) {
        $uid = D('User')->getUserId($api->tel);
        \AppException::ifNoExistThrowException($uid, C('ERROR_CODE.USER_NOT_EXIST'));
        
        $this->_resetLoginPassword($uid, $api->tel, $api->sms_validation, $api->passwd, C('SMS_MESSAGE_TYPE.FIND_LOGIN_PASSWORD'));
    
        return array(   'result' => '',
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }
    
    
    public function resetLoginPassword($api) {
        $userInfo = $this->getAvailableUser($api->session);
        $uid = $userInfo['uid'];
        $this->_resetLoginPassword($uid, $userInfo['user_telephone'], $api->sms_validation, $api->passwd, C('SMS_MESSAGE_TYPE.RESET_LOGIN_PASSWORD'));
        
        D('PushDevice')->deleteConfig($uid);
        D('Session')->deleteSession($api->session);
                
        return array(   'result' => '',
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }
    
    
    public function setFreePassword($api) {
        $userInfo = $this->_verificationSms($api->session, $api->sms_validation, C('SMS_MESSAGE_TYPE.SET_FREE_PASSWORD'));
        $uid = $userInfo['uid'];
        $checkPayPasswd = D('User')->checkPayPassword($uid, $api->pay_passwd, $userInfo['user_payment_password'], $userInfo['user_payment_salt']);
        \AppException::ifNoExistThrowException($checkPayPasswd, C('ERROR_CODE.PAY_PASSWORD_ERROR'));
        
        $saveResult = D('User')->setFreePasswordInfo($uid, $api->order_limit, $api->day_limit);
        \AppException::ifNoExistThrowException($saveResult, C('ERROR_CODE.DATABASE_ERROR'));
    }
    
    
    public function resetPaymentPassword($api) {
        $userInfo = $this->_verificationSms($api->session, $api->sms_validation, C('SMS_MESSAGE_TYPE.RESET_PAYMENT_PASSWORD'));
        $uid = $userInfo['uid'];
        $result = D('User')->setPaymentPassword($uid, $api->pay_passwd);
        \AppException::ifNoExistThrowException($uid, C('ERROR_CODE.DATABASE_ERROR'));
    
        return array(   'result' => '',
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }
    

    public function setPasswordFree($api) {
        $userInfo = $this->getAvailableUser($api->session);
        $uid = $userInfo['uid'];
        $checkPayPasswd = D('User')->checkPayPassword($uid, $api->pay_passwd, $userInfo['user_payment_password'], $userInfo['user_payment_salt']);
        \AppException::ifNoExistThrowException($checkPayPasswd, C('ERROR_CODE.PAY_PASSWORD_ERROR'));
        
        $saveResult = D('User')->swithFreePassword($uid, $api->switch);
        \AppException::ifExistThrowException($saveResult===false, C('ERROR_CODE.DATABASE_ERROR'));
        
        return array(   'result' => '',
        				'code'   => C('ERROR_CODE.SUCCESS'));
    }
    
    
    public function getUserInfo($api) {
        $userInfo = $this->getAvailableUser($api->session);
        $result = $this->_getUserInfo($userInfo);
        return array(   'result' => $result,
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }
    
    
    public function getUserAccount($api) {
        $userInfo = $this->getAvailableUser($api->session);
        $uid = $userInfo['uid'];
        $result = $this->_getUserAccount($uid);
        return array(   'result' => $result,
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }
    

    public function saveIDCard($api) {
        $userInfo = $this->getAvailableUser($api->session);
        $uid = $userInfo['uid'];
        $checkIdentityCard = ($userInfo['user_identity_card_status'] == C('IDENTITY_CARD_STATUS.VERIFY'));
        \AppException::ifExistThrowException($checkIdentityCard, C('ERROR_CODE.HAS_CHECK_IDENTITY'));
        
        $id_card_user_count = D('User')->countUserByIdCard($api->identity_no);
        if ($id_card_user_count > 1) {
            throw new \Think\Exception('', C('ERROR_CODE.IDENTITY_BIND_TOO_MUCH'));
        }

        $saveResult = D('User')->saveIdentityCard($uid, $api->realname, $api->identity_no);
        \AppException::ifNoExistThrowException($saveResult, C('ERROR_CODE.DATABASE_ERROR'));
        return array(   'result' => '',
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }
    
    
    public function getUserBanKCard($api) {
        $userInfo = $this->getAvailableUser($api->session);
        $uid = $userInfo['uid'];
        $result = $this->_getUserBankCardInfo($uid);
        return array(   'result' => $result,
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }
    
    
    public function saveBankCardInfo($api) {
        $userInfo = $this->getAvailableUser($api->session);
        $uid = $userInfo['uid'];
        if(empty($userInfo['user_identity_card'])){
        	\AppException::throwException(C('ERROR_CODE.HAS_NO_ID_CARD'));
        }
        $checkBankCard = ($userInfo['user_bank_card_status'] == C('BANK_CARD_STATUS.CHECK'));
        \AppException::ifExistThrowException($checkBankCard, C('ERROR_CODE.HAS_CHECK_BANK_CARD'));

        if($api->sdk_version==8 && $api->os==OS_OF_ANDROID){
            $no = $api->issueNo;
        }else{
            $no = $api->no;
        }
        $bank_card_bind_count = D('User')->countUserByBankNumber($no);
        if ($bank_card_bind_count > 1) {
            throw new \Think\Exception('', C('ERROR_CODE.BANK_CARD_BIND_TOO_MUCH'));
        }
        
        $result = D('User')->saveBankCardInfo($uid, $api->type, $api->address, $no, $api->account);
        \AppException::ifExistThrowException($result===false, C('ERROR_CODE.DATABASE_ERROR'));
        return array(   'result' => '',
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }

    public function saveUserAvatar($api){
        $userInfo = $this->getAvailableUser($api->session);
        $uid = $userInfo['uid'];
        $avatar = $api->avatar;
        $set_status = D('User')->setUserAvatar($uid,$avatar);
        if(!$set_status){
            \AppException::throwException(C('ERROR_CODE.SAVE_USER_AVATAR_FAIL'));
        }
        return array(   'result' => '',
            'code'   => C('ERROR_CODE.SUCCESS'));

    }
    

    private function _autoLogin($session, $uid) {
        D('UserLogin')->saveUserLogin($uid);
        $saveSession = D('Session')->saveSession($uid, $session);
        \AppException::ifExistThrowException($saveSession===false, C('ERROR_CODE.DATABASE_ERROR'));
        
        $otherLoginUserIds = D('Session')->getOtherLoginUser($uid, $session);
        if(!empty($otherLoginUserIds)){
        	D('Session')->deleteSessionById($otherLoginUserIds);
        }
        
        $deviceId    = D('Session')->getDeviceId($session);
        $saveResult  = D('PushDevice')->saveUserId($deviceId, $uid);
        \AppException::ifExistThrowException($saveResult===false, C('ERROR_CODE.DATABASE_ERROR'));
        
        return $uid;
    }


    private function _resetLoginPassword($uid, $telephone, $validation, $passwd, $type) {
        $verifySms = A('SmsVerify')->checkVerificationCode($telephone, $validation, $type);
        \AppException::ifNoExistThrowException($verifySms['equal'], C('ERROR_CODE.SMS_VERIFY_ERROR'));
        \AppException::ifNoExistThrowException($verifySms['inLifetime'], C('ERROR_CODE.SMS_LIFETIME_ERROR'));
    
        $result = D('User')->setLoginPassword($uid, $passwd);
        \AppException::ifNoExistThrowException($result, C('ERROR_CODE.DATABASE_ERROR'));
    }


    private function _verificationSms($session, $verification, $code) {
        $userInfo = $this->getAvailableUser($session);
        $uid = $userInfo['uid'];
        $verifySms = A('SmsVerify')->checkVerificationCode($userInfo['user_telephone'], $verification, $code);
        \AppException::ifNoExistThrowException($verifySms['equal'], C('ERROR_CODE.SMS_VERIFY_ERROR'));
        \AppException::ifNoExistThrowException($verifySms['inLifetime'], C('ERROR_CODE.SMS_LIFETIME_ERROR'));
        return $userInfo;
    }
    

    private function _getUserInfo($user) {
        if(is_array($user)) {
            $userInfo = $user;
        } else {
            $userInfo = D('User')->getUserInfo($user);
        }
        
        $userAccount = $this->_getUserAccount($userInfo['uid']);
		$userInfo = array(
                'id' => $userInfo['uid'],
				'sex' => $userInfo['user_sex'],
				'tel' => $userInfo['user_telephone'],
				'email' => $userInfo['user_email'],
				'username' => $userInfo['user_name'],
                'avatar' => $userInfo['user_avatar'],
                'nick_name' => $userInfo['user_nick_name'],
				'identity_status' => $userInfo['user_identity_card_status'],
				'realname' => $userInfo['user_real_name'],
				// 'identity_no' => $userInfo['user_identity_card'],
				'identity_no' => $this->_showSecretString($userInfo['user_identity_card'], 6, 4),
				'pay_passwd_free_switch' => $userInfo['user_password_free'] 
		);
        $userInfo = array_map('emptyToStr', $userInfo);
        return array_merge($userAccount, $userInfo);
    }


    private function _getUserBankCardInfo($uid) {
        $bankCardInfo = D('User')->getBankCardInfo($uid);
        $bank_image   = D('Bank')->where(array('bank_name'=>$bankCardInfo['user_bank_card_type']))->getField('bank_image');
		$bankCard = array(
				'status' => $bankCardInfo['user_bank_card_status'],
				'type' => $bankCardInfo['user_bank_card_type'],
                'image' => (string)$bank_image,
				'address' => $bankCardInfo['user_bank_card_address'],
				'no' => $this->_showSecretString($bankCardInfo['user_bank_card_number'], 4, 4),
				'account' => $bankCardInfo['user_bank_card_account_name'] 
		);
        
        return array_map('emptyToStr', $bankCard);
    }

	private function _showSecretString($string, $start_length, $end_length){
		$is_id_card = preg_match("/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/",$string);
		$is_bank_card = preg_match("/^\d{16,19}$/", $string);
		if(!$is_id_card && !$is_bank_card){
			return $string;
		}
// 		ApiLog('validate idcard:'.$string.'=='.validation_filter_id_card($string).'---'.preg_match("/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/",$string), 'secr');
// 		ApiLog('validate bankcard:'.$string.'=='.validate_bankcard_by_luhm($string).'---'.preg_match("/^\d{19}$/", $string), 'secr');
// 		return $string;
		$total_length = strlen($string);
		$star = '';
		$star_length = $total_length - $start_length - $end_length;
		for($i = 0; $i < $star_length; $i++) {
			$star .= '*';
		}
		return substr($string, 0, $start_length) . $star . substr($string, -$end_length);
	}


    private function _getUserAccount($uid) {
        $userAccount = D('UserAccount')->getUserAccount($uid);
        $totalCouponBalance = D('UserCoupon')->sumUserCouponBalance($uid);
        $userAccountInfo = array(
            'balance' => $userAccount['user_account_balance'],
            'frozen_balance' => $userAccount['user_account_frozen_balance'],
            'recharge_amount' => $userAccount['user_account_recharge_amount'],
            'consume_amount' => $userAccount['user_account_consume_amount'],
            'coupon_balance' => ( $totalCouponBalance ? $totalCouponBalance : 0 ),
            'coupon_amount' => $userAccount['user_account_coupon_amount'],
        );
        return array_map('emptyToStr', $userAccountInfo);
    }

    private function activeDevice($device_info){
        $device = array();
        $device['idfa'] = strtolower($device_info['iphone_adfa']);
        $device['mac']  = strtolower($device_info['mac']);
        $device['device_id'] = $device_info['device_id'];
        $device['type'] = 'register';

        R('Content/Ad/activeDevice', $device);
    }

    public function saveUserNickName($api){
        $userInfo = $this->getAvailableUser($api->session);
        $uid = $userInfo['uid'];
        $nick_name = $api->nick_name;
        $set_status = D('User')->setUserNickName($uid,$nick_name);
        if(!$set_status){
            \AppException::throwException(C('ERROR_CODE.NICK_NAME_IS_EXIST'));
        }
        return array(   'result' => '',
            'code'   => C('ERROR_CODE.SUCCESS'));

    }


}

?>