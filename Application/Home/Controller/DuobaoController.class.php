<?php
namespace Home\Controller;

use Home\Util\Factory;
use Think\Cache\Driver\Redis;
use Think\Controller;

class DuobaoController extends Controller
{

    private $_switch_on = true;
    private $_act = '';
    private $_act_map = '';
    private $_request_data = '';

    const STATUS_OF_SUCCESS = 1;
    const STATUS_OF_FAIL = 2;
    const STATUS_OF_REGISTERED = 3;
    private $_key = '19a45f1aa833a630373a6c2277e1f6df';

    //private $_duoBaoGainPointURL = 'http://192.168.3.93/duobao2/trunk/index.php?s=/Home/TigerRequest/index';
    private $_duoBaoGainPointURL = 'http://db.tigercai.com/index.php?s=/Home/TigerRequest/index';//夺宝增加积分的接口地址

    public function __construct()
    {
        $this->_act_map = array(
            101 => 'verifyUserPassword',
            102 => 'register',
            103 => 'resetLoginPassword',
            104 => 'sendSmsVerifyCode',
            105 => 'decryptSession',
            106 => 'autoLogin',
            107 => 'exchangeCoupon',
            108 => 'checkAddPointOrder',
            109 => 'getRechargeAmount',
        );
        parent::__construct();
    }

    public function index()
    {
        if ($this->_switch_on) {
            $this->_analyzeRequestPacket();
            $method_name = $this->_act_map[$this->_act];
            if (!method_exists($this, $method_name)) {
                exit('error');
            }
            $result = $this->$method_name($this->_request_data);
        } else {
            $result['status'] = self::STATUS_OF_FAIL;
            $result['data'] = '夺宝接口已关闭';
        }
        $response_packet = $this->_buildResponsePacket($result);
        echo $response_packet;
    }

    //解包
    private function _analyzeRequestPacket()
    {
        $raw_post_json_string = file_get_contents('php://input');
        $decrypt_json = think_decrypt($raw_post_json_string, $this->_key);
        ApiLog('request:' . $decrypt_json, 'duobao');
        $request_params = json_decode($decrypt_json, true);

        $this->_act = intval($request_params['action']);
        $this->_request_data = $request_params['data'];
    }

    //打包
    private function _buildResponsePacket($result)
    {
        $response_packet['status'] = $result['status'];
        $response_packet['data'] = $result['data'];
        $response = json_encode($response_packet);
        ApiLog('response packet:' . $response, 'duobao');
        return think_encrypt($response, $this->_key);
    }

    //return失败状态
    private function _returnFailStatus($other_status = "")
    {
        $status = self::STATUS_OF_FAIL;
        if ($other_status) {
            $status = $other_status;
        }
        $result['status'] = $status;
        return $result;
    }

    //return成功状态
    private function _returnSuccessStatus()
    {
        $status = self::STATUS_OF_SUCCESS;
        $result['status'] = $status;
        return $result;
    }

    /**
     * 自动登陆
     * @param $result
     */
    public function autoLogin($result)
    {
        $sessionArr = decryptRsa($result['session']);
        ApiLog('$sessionArr:' . print_r($sessionArr, true) . '请求自动登陆', 'Duobao');
        $sessionArr = explode('_', $sessionArr);
        $session = $sessionArr[1];
        $expire_time = $sessionArr[0] + 24 * 60 * 60;
        if ($expire_time <= time()) {
            ApiLog('session:' . $session . '过期了的session', 'Duobao');
            $result = $this->_returnFailStatus();
            $result['data'] = array(
                'userSession' => $session,
            );
        }

        $userInfo = $this->_getAvailableUser($session);
        if (empty($userInfo) || $userInfo['user_status'] == 0) {
            ApiLog('请求登陆失败:' . print_r($result, true), 'Duobao');
            $result = $this->_returnFailStatus();
            $result['data'] = array(
                'userSession' => $session,
            );
        } else {
            ApiLog('请求登陆成功:' . print_r($result, true), 'Duobao');
            $result = $this->_returnSuccessStatus();
            $result['data'] = array(
                'user_telephone' => $userInfo['user_telephone'],
                'userSession' => $session,
                'is_vip' => $userInfo['user_big_vip'],
            );
        }
        return $result;
    }

    public function decryptSession($result)
    {
        $sessionArr = decryptRsa($result['decryptSession']);
        ApiLog('$sessionArr:' . print_r($sessionArr, true) . '请求自动登陆', 'Duobao');
        $session = $sessionArr[1];
        $expire_time = $sessionArr[0];
        if ($expire_time <= time()) {
            ApiLog('session:' . $session . '过期了的session', 'Duobao');
            $result = $this->_returnFailStatus();
        } else {
            $result = $this->_returnSuccessStatus();
            $result['data'] = array(
                'session' => $session,
            );
        }
    }

    /**
     * 获取用户session
     * @param $result
     */
    public function getUserSession($result)
    {
        $sessionArr = decryptRsa($result['session']);
        ApiLog('$sessionArr:' . print_r($sessionArr, true) . '请求自动登陆', 'Duobao');
        $sessionArr = explode('_', $sessionArr);
        $session = $sessionArr[1];
        $expire_time = $sessionArr[0];
        if ($expire_time <= time()) {
            ApiLog('$expire_time:' . date('Y-m-d H:i:s',$expire_time) . '过期了的session', 'Duobao');
            ApiLog('session:' . $session . '过期了的session', 'Duobao');
            $result = $this->_returnFailStatus();
        }
        ApiLog('session:' . $session , 'Duobao');
        $userInfo = $this->_getAvailableUser($session);
        if (empty($userInfo) || $userInfo['user_status'] == 0) {
            ApiLog('请求登陆失败:' . print_r($result, true), 'Duobao');
            $result = $this->_returnFailStatus();
        } else {
            ApiLog('请求登陆成功:' . print_r($result, true), 'Duobao');
            $result = $this->_returnSuccessStatus();
            $result['data'] = array(
                'user_telephone' => $userInfo['user_telephone'],
            );
        }
        return $result;

    }


    /**
     * 根据session获取用户信息
     * @param $session
     * @return mixed
     * @throws \Think\Exception
     */
    private function _getAvailableUser($session)
    {
        $userInfo = $this->_getUserInfoFromRedis($session);
        ApiLog('$userInfo1:' . print_r($userInfo,true) , 'Duobao');
        if(empty($userInfo)){
            $uid = D('Session')->getUid($session);
            $userInfo = D('User')->getUserInfo($uid);
            $this->_setUserInfoToRedis($session,$userInfo);
        }
        ApiLog('$userInfo2:' . print_r($userInfo,true) , 'Duobao');
        return $userInfo;
    }

    private function _getUserInfoFromRedis($session){
        $redis = $this->_getRedis();
        if($redis){
            $userInfo = $redis->get('duobao_request:user_session:'.$session);
            return json_decode($userInfo,true);
        }
    }

    private function _setUserInfoToRedis($session,$userInfo){
        $redis = $this->_getRedis();
        if($redis && !empty($userInfo)){
            $redis->set('duobao_request:user_session:'.$session,json_encode($userInfo),60*60);
        }

    }

    public function delUserInfoFromActivityRedis($session){
        $redis = $this->_getRedis();
        if($redis){
            $redis->del('duobao_request:user_session:'.$session);
        }
    }

    public function setUserInfoToActivityRedis($session,$userInfo){
        $redis = $this->_getRedis();
        if($redis && !empty($userInfo)){
            $redis->set('duobao_request:user_session:'.$session,json_encode($userInfo),60*60);
        }

    }


    private function _getRedis(){
        $redis = Factory::createAliRedisObj();
        if($redis){
            $redis->select(0);
        }
        return $redis;
    }

    /**
     * 夺宝金币兑换红包
     * @param $result
     * @return mixed
     */
    public function exchangeCoupon($result)
    {
        $userTelephone = $result['user_telephone'];
        $couponId = $result['coupon_id'];
        $couponLogId = $result['coupon_log_id'];
        $sign_str = $result['sign_str'];

        $checkSign = $this->_checkCouponSign($userTelephone, $couponId, $couponLogId, $sign_str);

        if (!$checkSign) {
            ApiLog($userTelephone . '签名验证失败!', 'Duobao');
            return $this->_returnFailStatus();
        }


        $couponInfo = D('Coupon')->getCouponInfo($couponId);
        if (empty($couponInfo) || $couponInfo['coupon_status'] == 0) {
            ApiLog($userTelephone . '夺宝请求兑换红包不存在!' . print_r($couponInfo, true), 'Duobao');
            return $this->_returnFailStatus();
        }

        $uid = D('User')->getUserId($userTelephone);
        if (empty($uid)) {
            ApiLog($uid . '用户ID不存在!' . print_r($uid, true), 'Duobao');
            return $this->_returnFailStatus();
        }
        ApiLog($userTelephone . '用户请求兑换红包!' . print_r($couponInfo, true), 'Duobao');


        //请求夺宝红包ID是否存在

        $requestCoupon = array(
            'action' => 102,
            'data' => array(
                'user_telephone' => $userTelephone,
                'coupon_log_id' => $couponLogId,
                'sign_str' => $this->_addCouponSign($userTelephone, $couponLogId)
            )
        );
        $requestCouponStatus = $this->_requestDuobaoCouponIdStatus($requestCoupon);
        if ($requestCouponStatus !== 1) {
            ApiLog($userTelephone . '请求夺宝红包状态接口失败!' . print_r($requestCouponStatus, true), 'Duobao');
            return $this->_returnFailStatus();
        }

        ApiLog($userTelephone . '请求夺宝红包状态正常!' . print_r($requestCouponStatus, true), 'Duobao');


        $user_coupon_data['uid'] = $uid;
        $user_coupon_data['coupon_id'] = $couponInfo['coupon_id'];
        $user_coupon_data['user_coupon_balance'] = $couponInfo['coupon_value'];
        $user_coupon_data['user_coupon_status'] = C('USER_COUPON_STATUS.AVAILABLE');
        $user_coupon_data['user_coupon_amount'] = $couponInfo['coupon_value'];
        $user_coupon_data['user_coupon_desc'] = $couponInfo['coupon_name'] . $couponInfo['coupon_value'] . '元';
        $user_coupon_data['user_coupon_start_time'] = date("Y-m-d H:i:s");
        $user_coupon_data['user_coupon_create_time'] = date("Y-m-d H:i:s");
        $user_coupon_data['user_coupon_end_time'] = '2099-12-31 23:59:59';

        M()->startTrans();
        $userCouponId = M('UserCoupon')->add($user_coupon_data);
        $consumeCoupon = D('UserAccount')->updateBuyCouponStatics($uid, $user_coupon_data['user_coupon_balance']);
        $log_result = D('UserCouponLog')->addUserCouponLog($uid, $userCouponId, $user_coupon_data['user_coupon_balance'], $user_coupon_data['user_coupon_balance'], C('USER_ACCOUNT_LOG_TYPE.DUOBAO_COUPON_REWARD'), $uid);
        if ($userCouponId && $consumeCoupon && $log_result) {
            ApiLog($userTelephone . '用户请求兑换红包请求成功!$userCouponId:' . print_r($userCouponId, true), 'Duobao');
            M()->commit();
            return $this->_returnSuccessStatus();
        } else {
            ApiLog($userTelephone . '用户请求兑换红包请求失败!$userCouponId:' . print_r($userCouponId, true), 'Duobao');
            M()->rollback();
            return $this->_returnFailStatus();
        }
    }

    private function _requestDuobaoCouponIdStatus($request)
    {
        ApiLog('request:' . print_r($request, true), 'DuobaoCouponId');
        $request_params = $this->_encryptJsonData($request);
        $result = requestByCurl($this->_duoBaoGainPointURL, $request_params);
        $result = $this->_decryptJsonData($result);
        ApiLog('response:' . print_r($result, true), 'DuobaoCouponId');
        if ($result['status'] === 0) {
            ApiLog('请求成功:' . $result['data'], 'DuobaoCouponId');
            return self::STATUS_OF_SUCCESS;
        } else {
            ApiLog('请求失败:' . $result['data'], 'DuobaoCouponId');
            return $this->STATUS_OF_FAIL;
        }
    }

    //数据加密
    private function _encryptJsonData($data)
    {
        return think_encrypt(json_encode($data), $this->_key);
    }

    //数据解密
    private function _decryptJsonData($data)
    {
        return json_decode(think_decrypt($data, $this->_key), true);
    }


    private function _addCouponSign($user_telephone, $coupon_log_id)
    {
        $sign_str = $this->_key;
        $sign_str .= ',user_telephone:' . $user_telephone;
        $sign_str .= ',coupon_log_id:' . $coupon_log_id;
        return md5($sign_str);
    }


    private function _checkCouponSign($userTelephone, $couponId, $couponLogId, $sign_str)
    {
        $recive_str = $this->_key;
        $recive_str .= '&user_telephone:' . $userTelephone;
        $recive_str .= '&coupon_id:' . $couponId;
        $recive_str .= '&coupon_log_id:' . $couponLogId;
        if ($sign_str === md5($recive_str)) {
            return true;
        } else {
            return false;
        }

    }


    private function _requestDuobaoExchangeId($id)
    {
        $target_url = 'http://db.tigercai.com/index.php?s=/Home/User/checkExchangeRecord';
        $request_params['id'] = think_encrypt($id, $this->_key);
        ApiLog('aaa:' . $target_url . '===' . $id, 'duobao');
        $res = requestByCurl($target_url, $request_params);
        ApiLog('res:' . print_r($res, true), 'duobao');
        $decrypt_res = think_decrypt($res, $this->_key);
        ApiLog('$decrypt_res:' . print_r($decrypt_res, true), 'duobao');
        $response = json_decode($decrypt_res, true);
        if ($response['status'] == self::STATUS_OF_SUCCESS) {
            return true;
        }
        return false;
    }

    public function checkAddPointOrder($request_data)
    {
        $user_telephone = $request_data['user_telephone'];
        $order_id = $request_data['order_id'];
        $payMoney = $request_data['pay_money'];

        $uid = D('User')->getUserId($user_telephone);

        $order_info = D('Order')->getOrderInfo($order_id);

        ApiLog('user_telephone:' . $user_telephone . 'order_info:' . print_r($order_info, true) . '审核手动添加积分的信息', 'Duobao');
        if ($uid != $order_info['uid']) {
            ApiLog('用户ID不一致', 'Duobao');
            return $this->_returnFailStatus();
        }
        $where['aapo_order_id'] = $order_id;
        $isAdd = M('AlreadyAddPointOrder')->where($where)->count();
        if (!empty($isAdd)) {
            ApiLog('该订单已经增加过积分', 'Duobao');
            return $this->_returnFailStatus();
        }

        $data = array();
        $data['aapo_uid'] = $order_info['uid'];
        $data['aapo_user_telephone'] = $request_data['user_telephone'];
        $data['aapo_order_id'] = $order_info['order_id'];
        $data['aapo_order_status'] = $order_info['order_status'];
        $data['aapo_order_createtime'] = $order_info['order_create_time'];
        $data['aapo_createtime'] = getCurrentTime();
        $data['aapo_pay_money'] = $payMoney;
        $alreadyAddPointOrderId = M('AlreadyAddPointOrder')->add($data);
        if ($alreadyAddPointOrderId) {
            ApiLog($order_info['uid'] . '-' . $user_telephone . '手动增加积分增加临时表成功', 'Duobao');
            return $this->_returnSuccessStatus();
        } else {
            ApiLog($order_info['uid'] . '-' . $user_telephone . '手动增加积分增加临时表失败', 'Duobao');
            return $this->_returnFailStatus();
        }
    }


    public function verifyUserPassword($request_data)
    {
        $uid = D('User')->getUserId($request_data['tel']);
        if (empty($uid)) {
            return $this->_returnFailStatus();
        } else {
            $password_is_correct = D('User')->checkUserPassword($uid, $request_data['password']);
            if (empty($password_is_correct)) {
                return $this->_returnFailStatus();
            } else {
                $userSession = $request_data['user_session'];
                if (!empty($userSession)) {
                    if ($this->_tigerAutoLogin($userSession, $uid)) {
                        return $this->_returnSuccessStatus();
                    } else {
                        return $this->_returnFailStatus();
                    }
                } else {
                    return $this->_returnSuccessStatus();
                }
            }
        }
    }

    private function _tigerAutoLogin($session, $uid)
    {
        D('UserLogin')->saveUserLogin($uid);
        $saveSession = D('Session')->saveSession($uid, $session);
        $otherLoginUserIds = D('Session')->getOtherLoginUser($uid, $session);
        if (!empty($otherLoginUserIds)) {
            D('Session')->deleteSessionById($otherLoginUserIds);
        }
        $deviceId = D('Session')->getDeviceId($session);
        $saveResult = D('PushDevice')->saveUserId($deviceId, $uid);
        return true;
    }


    public function getRechargeAmount($result)
    {
        $user_tel = $result['user_tel'];
        $userInfo = D('User')->queryUserInfoByPhone($user_tel);
        if (empty($userInfo) || $userInfo['user_status'] == 0) {
            $result = $this->_returnFailStatus();
            $result['data'] = array(
                'user_tel' => $user_tel,
            );
        } else {
            $start_time = $result['start_time'];
            $end_time = $result['end_time'];
            $recharge_amount = D('Recharge')->getRechargeAmountByUidAndTime($userInfo['uid'],$start_time,$end_time);
            ApiLog('SQL:'.D('Recharge')->getLastSql(),'testlifeng2');
            $result = $this->_returnSuccessStatus();
            $result['data'] = array(
                'user_tel' => $userInfo['user_telephone'],
                'recharge_amount' => $recharge_amount,
            );
        }
        return $result;
    }


}

