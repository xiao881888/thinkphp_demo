<?php

namespace Home\Controller;

use Home\Util\Factory;
use Think\Controller;
use Think\Exception;

class RegisterActivityController extends Controller{
    const ERROR_CODE_OF_SUCCESS = 0;
    const ERROR_CODE_OF_FAIL = 1;

    const K_MI_ACTIVITY_COUPON_ID = 166;
    const K_MI_ACTIVITY_CHANNEL_CODE = 'HiZvA7u4';

    const SZC_LOTTERY_ACTIVITY_START_TIME = '2017-07-14 00:00:00';
    const SZC_LOTTERY_ACTIVITY_END_TIME = '2017-08-14 00:00:00';
    const SZC_LOTTERY_ACTIVITY_COUPON_ID = 221;
    const SZC_LOTTERY_ACTIVITY_LOTTERY_IDS = array('4','8','18');

    protected $redis;
    public function __construct(){
        if(!$this->redis){
            $this->redis = $this->getRedis();
        }
        parent::__construct();
    }
    private $_ios_download_num = 'kmi_activity:ios_download:total_num';
    private $_android_download_num = 'kmi_activity:android_download:total_num';

    private function getRedis(){
        $redis = Factory::createAliRedisObj();
        if(!$redis){
            throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.REDIS_NOT_COMMIT'),C('UNITE_PUSH_EXCEPTION_CODE.REDIS_NOT_COMMIT'));
        }
        $redis->select(0);
        return $redis;

    }

    private $_error_msg = array(
        'TEL_IS_EMPTY' => '请输入手机号码',
        'VERIFY_CODE_IS_EMPTY' => '请输入验证码',
        'TEL_IS_USED' => '手机号码已注册',
        'GET_VERIFY_CODE_ERROR' => '获取验证码失败',
        'VERIFY_CODE_IS_ERROR' => '验证码错误',
        'REGISTER_ERROR' => '注册失败'
    );

    public function kMiActivityIndex(){
        $this->display();
    }

    public function kMiActivitySuccess(){
        $this->display();
    }

    public function kMiActivityDownLoad(){
        $os = I('os');
        if($os == OS_OF_ANDROID){
            $redis_key = $this->_android_download_num;
        }elseif($os == OS_OF_IOS){
            $redis_key = $this->_ios_download_num;
        }
        if($this->redis){
            $is_set = $this->redis->setnx($redis_key,1);
            if(!$is_set){
                $this->redis->incrBy($redis_key,1);
            }
        }
        if($os == OS_OF_ANDROID){
            //重定向浏览器
            header("Location: http://oss.aliyuncs.com/tclottery/apk/lhcp-gougou.apk");
        }elseif($os == OS_OF_IOS){
            header("Location: https://itunes.apple.com/cn/app/id1116902507?mt=8");
        }

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
        $result['error_code'] = $error_code;
        $result['data'] = $info;
        exit(json_encode($result));
    }

    private function _verifyParamsForRegister(){
        $params['verify_code'] = $_POST['code'];
        $params['tel'] = $_POST['tel'];
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

    public function kMiActivityRegister(){
        $params = $this->_verifyParamsForRegister();
        if (!$params) {
            $this->_exitAndReturnFailJson($this->_error_msg['VERIFY_CODE_IS_ERROR']);
        }
        $session = array();
        $channel_info = array();
        $channel_code = self::K_MI_ACTIVITY_CHANNEL_CODE;
        $extra_channel_info = $this->_getExtraChannelInfo($channel_code);

        $password = $this->_buildDefaultPassword($params['tel']);
        M()->startTrans();
        $uid = A('User')->registerCommon($params['tel'], $password, $session, $channel_info, $extra_channel_info);
        if (!$uid) {
            M()->rollback();
            $this->_exitAndReturnFailJson($this->_error_msg['REGISTER_ERROR']);
        }

        $user_account_id = D('UserAccount')->addUserAccount($uid);
        if (!$user_account_id) {
            M()->rollback();
            $this->_exitAndReturnFailJson($this->_error_msg['REGISTER_ERROR']);
        }

        $grant_status = $this->_grantCouponToUser($uid,self::K_MI_ACTIVITY_COUPON_ID);
        if(!$grant_status){
            M()->rollback();
            $this->_exitAndReturnFailJson($this->_error_msg['REGISTER_ERROR']);
        }

        $res = $this->_noticeUserBySms($params['tel'], $password);
        if (!$res) {
            M()->rollback();
            $this->_exitAndReturnFailJson($this->_error_msg['REGISTER_ERROR']);
        }
        M()->commit();

        $this->_exitAndReturnSuccessJson('');
    }

    private function _grantCouponToUser($uid,$coupon_id){
        $couponInfo = D('Coupon')->getCouponInfo($coupon_id);
        $user_coupon_data['uid'] = $uid;
        $user_coupon_data['coupon_id'] = $couponInfo['coupon_id'];
        $user_coupon_data['user_coupon_balance'] = $couponInfo['coupon_value'];
        $user_coupon_data['user_coupon_status'] = C('USER_COUPON_STATUS.AVAILABLE');
        $user_coupon_data['user_coupon_amount'] = $couponInfo['coupon_value'];
        $user_coupon_data['coupon_type'] = $couponInfo['coupon_type'];
        $user_coupon_data['user_coupon_desc'] = $couponInfo['coupon_name'].$couponInfo['coupon_value'].'元';
        $user_coupon_data['user_coupon_start_time'] = date("Y-m-d H:i:s");
        $user_coupon_data['user_coupon_create_time'] = date("Y-m-d H:i:s");
        $user_coupon_data['user_coupon_end_time'] = '2099-12-31 23:59:59';
        $user_coupon_data['coupon_min_consume_price'] = $couponInfo['coupon_min_consume_price'];
        $user_coupon_data['coupon_lottery_ids'] = empty($couponInfo['coupon_lottery_ids']) ? '' : $couponInfo['coupon_lottery_ids'];
        $user_coupon_data['play_type'] = $couponInfo['play_type'];
        $user_coupon_data['bet_type'] = $couponInfo['bet_type'];

        $userCouponId = M('UserCoupon')->add($user_coupon_data);
        $log_result = D('UserCouponLog')->addUserCouponLog($uid, $userCouponId, $user_coupon_data['user_coupon_balance'], $user_coupon_data['user_coupon_balance'], C('USER_COUPON_LOG_TYPE.GIFT'), $uid,'k_mi_coupon');
        $consumeCoupon = D('UserAccount')->updateBuyCouponStatics($uid, $user_coupon_data['user_coupon_balance']);
        if ($userCouponId && $consumeCoupon && $log_result) {
            return true;
        } else {
            return false;
        }

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

    private function _getExtraChannelInfo($channel_code){
        $channel_info['channel_type'] = 1;
        $channel_info['extra_channel_id'] = 0;
        if ($channel_code) {
            $map['web_channel_key'] = $channel_code;
            $web_channel_info = D('WebChannel')->where($map)->find();
            $channel_info['extra_channel_id'] = $web_channel_info['web_channel_id'];
        }
        return $channel_info;
    }

    public function szcLotteryActivity($uid,$lottery_id){
        if(!$this->_hasQualify($uid,$lottery_id)){
            ApiLog('没有资格参加活动:$uid:'.$uid,'szcLotteryActivity');
            return false;
        }
        $is_deal = $this->_isDealData($uid,$lottery_id);
        if($is_deal){
            return false;
        }

        $user_coupon_obj = new UserCouponController();
        $user_coupon_obj->grantCouponToUser(self::SZC_LOTTERY_ACTIVITY_COUPON_ID,$uid,C('USER_COUPON_LOG_TYPE.DUOBAO_COUPON_REWARD'),1);

        $this->_addJoinedActivity($uid);
        $this->_addDealData($uid,$lottery_id);

    }

    private function _isDealData($uid,$lottery_id){
        return $this->redis->sContains($this->_getDealKey(),$uid.'-'.$lottery_id);
    }

    private function _addDealData($uid,$lottery_id){
        $this->redis->sAdd($this->_getDealKey(),$uid.'-'.$lottery_id);
    }

    private function _getDealKey(){
        return 'activity:is_joined';
    }

    private function _hasQualify($uid,$lottery_id){
        if(!in_array($lottery_id,self::SZC_LOTTERY_ACTIVITY_LOTTERY_IDS)){
            ApiLog('彩种不满足:$lottery_id:'.$lottery_id,'szcLotteryActivity');
            return false;
        }

        if(getCurrentTime() >= self::SZC_LOTTERY_ACTIVITY_END_TIME){
            ApiLog('活动时间结束:$uid:'.$uid,'szcLotteryActivity');
            return false;
        }

        $user_info = D('User')->getUserInfo($uid);
        $app_id = getRegAppId($user_info);
        if($app_id != C('APP_ID_LIST.TIGER')) {
            ApiLog('不是老虎包:$uid:'.$uid,'szcLotteryActivity');
            return false;
        }
        $user_info = D('User')->getUserInfo($uid);
        if($user_info['user_register_time'] < self::SZC_LOTTERY_ACTIVITY_START_TIME){
            ApiLog('注册时间不满足:$uid:'.$uid,'szcLotteryActivity');
            return false;
        }



        $is_joined = $this->_isJoinedActivity($uid);
        if($is_joined){
            ApiLog('已经参与:$uid:'.$uid,'szcLotteryActivity');
            return false;
        }
        return true;
    }

    private function _isJoinedActivity($uid){
        return $this->redis->sContains($this->_getActivityRedisKey(),$uid);
    }

    private function _addJoinedActivity($uid){
        $this->redis->sAdd($this->_getActivityRedisKey(),$uid);
    }

    private function _getActivityRedisKey(){
        return 'tiger_activity:szc_lottery:joined_uid';
    }

}

