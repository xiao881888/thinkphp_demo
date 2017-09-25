<?php 

namespace Home\Controller;
use Home\Controller\GlobalController;
use Home\Util\Factory;

class RechargeController extends GlobalController {
    
    public function getPlatformList($api) {
       return $this->getChannelList($api);
    }

    public function test(){
        exit();
        $recharge_id = 305489;
        A('UserCoupon')->rewardCouponForFirstRecharge($recharge_id);
    }
    
    public function getChannelList($api){
    	$userInfo = $this->getAvailableUser($api->session);
    	$uid = $userInfo['uid'];

    	$platformList = D('RechargeChannel')->getPlatformList($api->os);
    	\AppException::ifNoExistThrowException($platformList, C('ERROR_CODE.RECHARGE_CHANNEL_INVALID'));

        $channel_list = array();
    	foreach ($platformList as $platform) {

    		$is_hide = $this->_checkChannelNeedToHide($platform['recharge_channel_id'], $uid, $userInfo['user_telephone'], get_client_ip(0, true), $api->os, $api->sdk_version, $userInfo, $api);
    		if($is_hide){
    			continue;
    		}

    		$channel_list[$platform['recharge_channel_id']] = array(
    				'id' => $platform['recharge_channel_id'],
    				'type' => $platform['recharge_channel_type'],
    				'name' => $platform['recharge_channel_name'],
    				'image' => $platform['recharge_channel_image'],
    				'description' => $platform['recharge_channel_descript'],
    		);
    	}

/*        if (array_key_exists(PAYMENT_CHANNEL_ID_OF_ZWX, $channel_list) && array_key_exists(PAYMENT_CHANNEL_ID_OF_WFTWX, $channel_list)) {
            if (!$this->_isTestUser($uid)) {
                $uid_mod = $uid%10;
                $zwx_mod_uid = array(0,1);
                $wft_mod_uid = array(2,3,4,5,6,7,8,9);

                if (in_array($uid_mod, $zwx_mod_uid)) {
                    unset($channel_list[PAYMENT_CHANNEL_ID_OF_WFTWX]);
                } elseif(in_array($uid_mod, $wft_mod_uid)) {
                    unset($channel_list[PAYMENT_CHANNEL_ID_OF_ZWX]);
                }
            }
        }*/

        $list = array();
        foreach ($channel_list as $platform) {
            $list[] = $platform;
        }
    	
    	return array(
                'result' => array(
                    'list'=>$list
                    ),
                'code'   => C('ERROR_CODE.SUCCESS')
                );
    }

	private function _checkChannelNeedToHide($recharge_channel_id, $uid, $phone, $ip, $os, $sdk_version, $user_info, $api){
    	if(!$recharge_channel_id){
    		return true;
    	}

    	$is_hide = $this->_checkIsHideByOsAndVersion($recharge_channel_id, $os, $sdk_version);
    	if($is_hide){
    		return true;
    	}
    	

    	$is_test_user = $this->_isTestUser($uid,$phone);
    	if(!$is_test_user){
			$hide_new_channel_for_test = $this->_hideNewChannelForTest($recharge_channel_id, $ip);
    		if($hide_new_channel_for_test){
    			return true;
    		}
    	}
    	 
    	$bank_is_hide = $this->_hideBankCardByForbiddenUser($recharge_channel_id, $uid, $user_info['user_register_time'], $phone);
    	if($bank_is_hide){
    		return true;
    	}
    	$recharge_under_limit_is_hide = $this->_hideRechargeChannelByUserInfo($recharge_channel_id, $user_info);
        if($recharge_under_limit_is_hide){
            return true;
        }

        //百万彩票、新彩票均布显示
        if ($recharge_channel_id == 18) {
            if ($api->bundleId == 'com.xincai.tigerlottery' || $api->bundleId == 'com.yingqiu.xincai' || $api->bundleId == 'com.baiwan.caipiao') {
                return true;
            }
        }

    	
    	return false;
    }

    private function _hideRechargeChannelByUserInfo($recharge_channel_id, $user_info){
        if ($recharge_channel_id == 18) {
            $map['uid'] = $user_info['uid'];
            $user_account_info = D('UserAccount')->where($map)->find();
            if ($user_account_info['user_account_recharge_amount'] < 8000) {
                return true;
            }
        }
        return false;
    }
    
    private function _isTestUser($uid,$phone){
    	$test_uids = array(1,2,3,4,6,8,9,25351,125);
    	if(in_array($uid, $test_uids)){
    		return true;
    	}
    	return false;
    }
    
    private function _checkIsHideByOsAndVersion($recharge_channel_id, $os, $sdk_version){
    	//安卓版本暂时不支持微信公众号支付，先屏蔽掉
    	if ($recharge_channel_id == PAYMENT_CHANNEL_ID_OF_WEIXINGONGZHONG) {
    		if ($os == OS_OF_ANDROID) {
    			return true;
    		}
    	}
    	
    	//小于8的版本不显示兴业微信支付
    	if ($recharge_channel_id == PAYMENT_CHANNEL_ID_OF_XYWXSDK && $sdk_version < 8 ) {
    		return true;
    	}

        if ($recharge_channel_id == PAYMENT_CHANNEL_ID_OF_LIANLIANSDK && $sdk_version < 9) {
            return true;
        }
    	return false;
    }
    
    private function _hideBankCardByForbiddenUser($recharge_channel_id, $uid, $user_register_time, $phone){
    	$bank_card_channel_ids = array(PAYMENT_CHANNEL_ID_OF_YEEPAY,PAYMENT_CHANNEL_ID_OF_BAOFU);
    	$forbid_uids = array(139342,139591,139192,139223,42892);
    	$forbid_phones = array('13303963384','18790327938');
    	return false;
    	if(in_array($recharge_channel_id,$bank_card_channel_ids)){
            $user_info = D('User')->getUserInfo($uid);
            if ($user_info['user_identity_card_status'] == C('IDENTITY_CARD_STATUS.VERIFY') && !empty($user_info['user_bank_card_number']) && !empty($user_info['user_bank_card_account_name'])) {
                return false;
            }
    		if (in_array($uid, $forbid_uids)) {
    			return true;
    		}
    		if (in_array($phone, $forbid_phones)) {
    			return true;
    		}
    	}
    	 
    	return false;
    }

	private function _hideNewChannelForTest($recharge_channel_id, $ip){
    	if(!$this->_isTestIp($ip) && $this->_isTestNewChannel($recharge_channel_id)){
    		return true;
    	}
    	return false;
    }
    
    private function _isTestIp($client_ip){
//     	$client_ip = get_client_ip(0, true);
    	$test_ips = array('110.83.28.97', '124.72.226.65');
    	if(in_array($client_ip, $test_ips)){
    		return true;
    	}
    	return false;
    }
    
    private function _isTestNewChannel($recharge_channel_id){
    	$test_channel_ids = array(
				PAYMENT_CHANNEL_ID_OF_ZXWXH5,
				PAYMENT_CHANNEL_ID_OF_ZXWXSDK,
				PAYMENT_CHANNEL_ID_OF_ZWXSDK,
                PAYMENT_CHANNEL_ID_OF_XYWXSDK ,
                11,19
                // PAYMENT_CHANNEL_ID_OF_ZWX ,
                // PAYMENT_CHANNEL_ID_OF_ZWX
		);
    	if(in_array($recharge_channel_id, $test_channel_ids)){
    		return true;
    	}
    	return false;
    }
    
    public function ip(){
        echo get_client_ip(0, true);
        exit;
    }
    
    public function getRechargeInfo($api) {
        $userInfo = $this->getAvailableUser($api->session);
        $uid = $userInfo['uid'];
        
        $rechargeInfo = D('Recharge')->getRechargeInfo($api->recharge_order_id);
        \AppException::ifNoExistThrowException($rechargeInfo, C('ERROR_CODE.RECHARGE_NO_EXIST'));
        
        $userOwen = ($rechargeInfo['uid'] == $uid);
        \AppException::ifNoExistThrowException($rechargeInfo, C('ERROR_CODE.RECHARGE_OWEN_ERROR'));
        
        $result = array(
            'id' => $rechargeInfo['recharge_id'],
            'sku' => $rechargeInfo['recharge_sku'],
            'money' => $rechargeInfo['recharge_amount'],
            'status' => $rechargeInfo['recharge_status'],
            'extra' => $rechargeInfo['recharge_remark'],
        );
        
        return array(   'result' => $result,
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }
    
    public function doCharge($api){
    	$userInfo = $this->getAvailableUser($api->session);
    	$uid = $userInfo['uid'];
    	
/*    	//梓微兴微信支付限额较为严重，而威富通限额不太严重，但是威富通点位较高，所以为了保证大额充值的成功率，同时降低点位，对超过1000的梓微兴充值都走威富通通道
        if ($api->recharge_channel_id == PAYMENT_CHANNEL_ID_OF_ZWX && $api->money >= 1000 && $api->money < 3000) {
            $api->recharge_channel_id = PAYMENT_CHANNEL_ID_OF_WFTWX;
        } else if ($api->recharge_channel_id == PAYMENT_CHANNEL_ID_OF_WFTWX && $api->money >= 3000) {
            $api->recharge_channel_id = PAYMENT_CHANNEL_ID_OF_ZWX;
        }*/

        $rechargeChannelInfo = D('RechargeChannel')->getPlatformInfo($api->recharge_channel_id);
    	\AppException::ifNoExistThrowException($rechargeChannelInfo, C('ERROR_CODE.RECHARGE_CHANNEL_INVALID'));
    	
    	$rechargeSku = getRechargeSku($uid, $api->recharge_channel_id);
        $recharge_source = $api->os == 1 ? C('RECHARGE_SOURCE.ANDROID') : C('RECHARGE_SOURCE.IPHONE');
    	
    	$rechargeId = D('Recharge')->addRecharge($uid, $api->recharge_channel_id, $api->money, $api->remark, $rechargeSku, $recharge_source);
    	\AppException::ifNoExistThrowException($rechargeId, C('ERROR_CODE.DATABASE_ERROR'));
		$params = $this->buildRechargeChannelParams($api->recharge_channel_id, $rechargeId, $uid, $api);
    	// $target_url = $this->_getTargetUrl($api->recharge_channel_id, $rechargeId, $uid, $rechargeSku, $api->money,$api->os);
        $target_url = $this->_getTargetUrl($api->recharge_channel_id, $rechargeId, $userInfo, $rechargeSku, $api->money, $api->os, $api);

    	$result = array(
    			'target_url' => $target_url,
    			'recharge_channel_id' => $rechargeChannelInfo['recharge_channel_id'],
    			'recharge_channel_type' => $rechargeChannelInfo['recharge_channel_type'],
    			'sku' => $rechargeSku,
    			'money' => $api->money,
    			'id' => $rechargeId,
    			'params' => $params,
    			'recharge_channel_name'=>$rechargeChannelInfo['recharge_channel_name'],
    	);
    	return array(   'result' => $result,
    			'code'   => C('ERROR_CODE.SUCCESS'));
    }

	protected function buildRechargeChannelParams($channel_id, $rechange_id, $user_id, $request_params){
		$params = array();
		if ($channel_id == PAYMENT_CHANNEL_ID_OF_ALIPAY) {
			$params = A('Alipay')->genAlipayParams($request_params, $rechange_id, $user_id);
		} elseif ($channel_id == PAYMENT_CHANNEL_ID_OF_LIANLIANSDK) {
			$params = A('LianLianSdk')->getParams($rechange_id, $user_id);
		} elseif ($channel_id == PAYMENT_CHANNEL_ID_OF_ZXWXSDK) {
			$params = A('ZXWXSdk')->buildParams($request_params, $rechange_id, $user_id);
		} elseif ($channel_id == PAYMENT_CHANNEL_ID_OF_ZWXSDK) {
			$params = A('ZWXSdk')->buildParams($request_params, $rechange_id, $user_id);
		} elseif ($channel_id == PAYMENT_CHANNEL_ID_OF_XYWXSDK) {
			$params = A('XYWXSdk')->buildParams($request_params, $rechange_id, $user_id);
		}
		return $params;
	}
    
    public function userRecharge($api) {
    	return $this->doCharge($api);
    }
    
    private function _buildUserIdentifyUrl($rechargeChannelId, $rechargeId, $user_info, $rechargeSku = '', $money = 0, $api=''){
        $recharge_params['recharge_channel_id'] = $rechargeChannelId;
        $recharge_params['recharge_id'] = $rechargeId;
        $recharge_params['recharge_sku'] = $rechargeSku;
        $recharge_params['money'] = $money;
        $url_params['r'] = $this->_genRechargeUniqueCode(json_encode($recharge_params), $user_info, $api);
        $url_params['s'] = $api->session;
        if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/' . U('WU/identify', $url_params);
        }elseif(get_cfg_var('PROJECT_RUN_MODE') == 'TEST'){
            $url = 'http://test.phone.api.tigercai.com/' . U('WU/identify', $url_params);
        }else {
            $url = 'http://192.168.1.171:81/index.php?s=/Home/' . U('WU/identify', $url_params);
        }
        return $url;
    }
    
    private function _genRechargeUniqueCode($recharge_params_json, $user_info, $api){
        $recharge_code = $user_info['uid'].md5($api->session . uniqid());
        $key = 'rccode:'.$recharge_code;
        $redis_instance = Factory::createRedisObj();
        $result = $redis_instance->setex($key, 3600, $recharge_params_json);
        if(!$result){
            $redis_instance->setex($key, 3600, $recharge_params_json);
        }
        return $recharge_code;
    }

    private function _getTargetUrl($rechargeChannelId, $rechargeId, $user_info, $rechargeSku = '', $money = 0, $os = 0, $api = ''){
        $uid = $user_info['uid'];
        $bank_channel_ids = array(
                PAYMENT_CHANNEL_ID_OF_BAOFU,
                PAYMENT_CHANNEL_ID_OF_YEEPAY 
        );
        if (in_array($rechargeChannelId, $bank_channel_ids)) {
            if (!$user_info['user_real_name'] || !$user_info['user_identity_card'] || !$user_info['user_identity_card_status']) {
                if ($api->sdk_version > 8) {
                    \AppException::throwException(C('ERROR_CODE.BANK_RECHARGE_CHANNEL_NEED_IDENTIFY'));
                }else{
                    return $this->_buildUserIdentifyUrl($rechargeChannelId, $rechargeId, $user_info, $rechargeSku, $money, $api);
                }
            }
        }
    	if($rechargeChannelId==PAYMENT_CHANNEL_ID_OF_ALIPAY){
    		return '';
    	}
    	if($rechargeChannelId==PAYMENT_CHANNEL_ID_OF_WEIXINGONGZHONG){
            return U('Home/Weixin/WeChatTransfer@'.$_SERVER['HTTP_HOST'],array('id'=>$rechargeId, 'sku'=>$rechargeSku,'money'=>$money),'',true);
    		// return U('Home/Weixin/showQr@'.$_SERVER['HTTP_HOST'],array('id'=>$rechargeId, 'sku'=>$rechargeSku,'money'=>$money),'',true);
    	}

        if($rechargeChannelId==18){
            return U('Home/Weixin/WeChatTransfer@'.$_SERVER['HTTP_HOST'],array('id'=>$rechargeId, 'sku'=>$rechargeSku,'money'=>$money),'',true);
            // return U('Home/Weixin/showQr@'.$_SERVER['HTTP_HOST'],array('id'=>$rechargeId, 'sku'=>$rechargeSku,'money'=>$money),'',true);
        }
    	
        if($rechargeChannelId==PAYMENT_CHANNEL_ID_OF_MANUAL){
    		//return 'http://phone.api.tigercai.com/index.php/Content/Recharge/bank.html';
            return 'http://phone.api.tigercai.com/index.php/Home/LargeRecharge/index';
    	}

        if ($rechargeChannelId==PAYMENT_CHANNEL_ID_OF_MANUAL_ALIPAY) {
            return 'http://phone.api.tigercai.com/index.php/Content/Recharge/alipay.html';
        }

    	if($rechargeChannelId==PAYMENT_CHANNEL_ID_OF_HEEPAY){
			return A('Heepay')->genHeepayTargetUrl($rechargeSku, $money, $rechargeId, $uid);
    	}
    	
    	if($rechargeChannelId==PAYMENT_CHANNEL_ID_OF_YEEPAY){
    		return A('Yeepay')->genYeepayTargetUrl($rechargeSku, $money, $rechargeId, $uid);
    	}

        if ($rechargeChannelId == PAYMENT_CHANNEL_ID_OF_ZWX) {
            // return A('ZWX')->buildPayUrl($rechargeSku, $money, $rechargeId, $uid);
            $data = array();
            $data['money'] = $money;
            $data['recharge_sku'] = $rechargeSku;
            $data['uid'] = $uid;
            $data['os'] = $os;
            return U('Home/ZWX/payRedirect@'.$_SERVER['HTTP_HOST'], $data ,'',true);
        }

         if ($rechargeChannelId == 17) {
            // return A('ZWX')->buildPayUrl($rechargeSku, $money, $rechargeId, $uid);
            $data = array();
            $data['money'] = $money;
            $data['recharge_sku'] = $rechargeSku;
            $data['uid'] = $uid;
            $data['os'] = $os;
            return U('Home/ZWX/payRedirectByQrcode@'.$_SERVER['HTTP_HOST'], $data ,'',true);
        }

        if ($rechargeChannelId == PAYMENT_CHANNEL_ID_OF_BAOFU) {
            $params = array();
            $params['id'] = $rechargeId;
            $params['rd'] = random_string(8);
            $params['md'] = md5($params['id'].$params['rd'].RECHARGE_URL_MD5_SALT);

            return U('Home/Baofu/recharge@'.$_SERVER['HTTP_HOST'], $params, '', true);
        }

        if ($rechargeChannelId == PAYMENT_CHANNEL_ID_OF_ZXWXH5) {
            $params = array();
            $params['recharge_sku'] = $rechargeSku;
            $params['money']        = $money;
            $params['uid']          = $uid;
            $params['os']           = $os;
            $params['recharge_id']  = $rechargeId;

            $params = signUrlData($params, RECHARGE_URL_MD5_SALT);

            return U('Home/ZXWXH5/payRedirect@'.$_SERVER['HTTP_HOST'], $params, '', true);
        }

        if ($rechargeChannelId == PAYMENT_CHANNEL_ID_OF_DZFWXH5) {
            $params = array();
            $params['recharge_sku'] = $rechargeSku;
            $params['money']        = $money;
            $params['uid']          = $uid;
            $params['os']           = $os;
            $params['recharge_id']  = $rechargeId;

            return U('Home/DZFWXH5/payRedirect@'.$_SERVER['HTTP_HOST'], $params, '', true);
        }

        if ($rechargeChannelId == PAYMENT_CHANNEL_ID_OF_WFTWX) {
            $params = array();
            $params['recharge_sku'] = $rechargeSku;
            $params['money']        = $money;
            $params['uid']          = $uid;
            $params['os']           = $os;
            $params['recharge_id']  = $rechargeId;

            return U('Home/WFTWX/payRedirect@'.$_SERVER['HTTP_HOST'], $params, '', true);
        }

        if ($rechargeChannelId == 21) {
            $params = array();

            $params['recharge_sku'] = $rechargeSku;
            $params['money']        = $money;
            $params['uid']          = $uid;
            $params['os']           = $os;
            $params['recharge_id']  = $rechargeId;

            return U('Home/PLBZFB/payRedirect@'.$_SERVER['HTTP_HOST'], $params, '', true);
        }


        $function = C("RECHARGE_DIPOSE.$rechargeChannelId");
        return U("$function@$_SERVER[HTTP_HOST]", array('id'=>$rechargeId, 'uid'=>$uid, 'sign'=>getRechargeSign($rechargeId,$uid)));
    }
    
    public function userWithdraw($api) {
        $userInfo = $this->getAvailableUser($api->session);
        $uid = $userInfo['uid'];
        $user_account = D('UserAccount')->getUserAccount($uid);
        if ($user_account['user_account_balance'] < $api->money) {
            return array(
                'result' => '',
                'code'   => C('ERROR_CODE.DATABASE_ERROR')
                );
        }

        $pay_fee = 2;

        if ($api->money >= 50) {
            $today = date('Y-m-d 00:00:00');
            $withdraw_map = array();
            $withdraw_map['uid'] = $uid;
            $withdraw_map['withdraw_request_time'] = array('egt', $today);
            $withdraw_count = D('Withdraw')->where($withdraw_map)->count();
            if ($withdraw_count < 1) {
                $pay_fee = 0;
            }
        }

        $withdraw_id = $this->_addWithdraw($uid, $api->money, $userInfo['user_bank_card_number'], $userInfo['user_bank_card_address'], $userInfo['user_bank_card_account_name'], $userInfo['user_bank_card_type'], $pay_fee);

        if ($this->_isEffectWithdraw($api->money, $uid)) {
            sendWarningMessage('SMS_WARNING_CONFIG.NEW_WITHDRAW_APPLY_WARNING');
        } else {
            $this->_refuseWithdraw($withdraw_id, $uid);
        }

        
        return array(   'result' => '',
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }
    
    private function _addWithdraw($uid, $money, $bankCardNo, $bankCardAddress, $accountName, $bankCardType, $pay_fee=0) {
        try {
            M()->startTrans();
            $withdrawId = D('Withdraw')->addWithdraw($uid, $money, $bankCardNo, $bankCardAddress, $accountName, $bankCardType, $pay_fee);
            \AppException::ifNoExistThrowException($withdrawId, C('ERROR_CODE.DATABASE_ERROR'));
            M()->commit();
            return $withdrawId;
        } catch (\Think\Exception $e) {
            M()->rollback();
            throw new \Think\Exception($e->getMessage(), $e->getCode());
        }
    }

    private function _isEffectWithdraw($withdraw_amount, $uid){
        $diaobi_uids = array(8, 116350, 135074, 26378,191288, 191288);
        if (in_array($uid, $diaobi_uids)) {
            return true;
        }

        //提现少于2元，直接拒绝
        if ($withdraw_amount < 2) {
            return false;
        }

        $user_account = D('UserAccount')->getUserAccount($uid);
        if ($user_account['user_account_recharge_amount'] != 0 && ($user_account['user_account_consume_amount']/$user_account['user_account_recharge_amount'] < 0.3)) {
            $user_wining_amount = D('Order')->getUserWinningAmount($uid);
            $user_withdraw_amount = D('Withdraw')->getUserWithdrawAmount($uid);
            if ($user_wining_amount >= ($withdraw_amount + $user_withdraw_amount) ) {
                $return = true;
            } else {
                $return = false;
            }
        } else {
            $return  = true;
        }

        return $return;
    }

    private function _refuseWithdraw($withdraw_id, $uid){
        $withdraw_data = D('Withdraw')->find($withdraw_id);
        
        if ($withdraw_data['withdraw_status'] != C('WITHDRAW_STATUS.NO_AUDIT')) {
            return false;
        }

        $user_account = D('UserAccount')->getUserAccount($uid);
        if ($user_account['user_account_frozen_balance'] < $withdraw_data['withdraw_amount']) {
            return false;
        }

        $refuse_withdraw = D('UserAccount')->refuseWithdraw($withdraw_data);

        if ($refuse_withdraw) {
            $user_info = D('User')->getUserInfo($uid);
            $message_data = array(
                $withdraw_data['withdraw_request_time'],
                $withdraw_data['withdraw_amount'] > 2 ? C('AUTO_REFUSE_WITHDRAW_REMARK') : C('AUTO_REFUSE_WITHDRAW_REMARK_2'),
                );

            $app_id = getRegAppId($user_info);
            if($app_id == C('APP_ID_LIST.TIGER')){
                $template_id = 77050;
            }elseif($app_id == C('APP_ID_LIST.BAIWAN')){
                $template_id = 173943;
            }elseif($app_id == C('APP_ID_LIST.NEW')){
                $template_id = 174149;
            }
            sendTemplateSMS($user_info['user_telephone'], $message_data, $template_id);
        }

        return $refuse_withdraw;
    }
    
    public function receiveClientReport($api){
    	$userInfo = $this->getAvailableUser($api->session);
    	$uid = $userInfo['uid'];
    	 
    	$rechargeInfo = D('Recharge')->getRechargeInfo($api->recharge_order_id);
        \AppException::ifNoExistThrowException($rechargeInfo, C('ERROR_CODE.RECHARGE_NO_EXIST'));
        
        $userOwen = ($rechargeInfo['uid'] == $uid);
        \AppException::ifNoExistThrowException($rechargeInfo, C('ERROR_CODE.RECHARGE_OWEN_ERROR'));
        
    	$recharge_map['recharge_id'] = $api->recharge_order_id;
    	$recharge_data['recharge_client_code'] = $api->result_code;
    	$recharge_data['recharge_client_message'] = json_encode($api->result_info);
    	D('Recharge')->where($recharge_map)->save($recharge_data);
    	return array(   'result' => '',
    			'code'   => C('ERROR_CODE.SUCCESS'));
    }
    
    public function getManualTransfer(){
    	$this->display('bank');
    }
}

