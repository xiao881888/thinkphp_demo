<?php
namespace Integral\Controller;

use Integral\Util\AppException;
use Think\Controller;
use Think\Exception;

class TurntableController extends GlobalController
{

    protected $uid = 0;
    protected $user_info = [];
    protected $code;
    protected $response_data = [];
    static $pool;

    public function index()
    {
        $this->_checkLogin();
        $session_code = $this->_getSessionCode();
        if (!$this->uid){
            $session_code = "";
        }
        $user_integral = D('UserIntegral')->getUserIntegralInfo($this->uid);

        if ($this->uid){
            $my_log = D('TurntableLog')->getLogList($this->uid);

        }else{
            $my_log = [];
        }
        $history =  D('TurntableLog')->getLogList(0,30,true);

        $is_login = $this->uid > 0 ? true : false;
        $this->assign('uid', $this->uid > 0 ? 1 : 0);
//        $this->assign('is_login',$is_login);
        $this->assign('my_integral', (int)$user_integral['user_integral_balance']);
        $this->_getTurntableList();
        $this->assign('my_log', $my_log);
        $this->assign('history', $history);
        $this->assign('session_code', $session_code);
        $this->display('index');
    }

    private function _getSessionCode()
    {
        $session_code = I('session_code',false);
        if (empty($session_code)){
            $session_code = $_COOKIE['session_code'];
        }

        return (string)$session_code;
    }

    private function _getTurntableList()
    {
        $turntable_list = D('Turntable')->getList();
        $this->assign('turntable_list', $turntable_list);
    }

    public function getSession()
    {
        $sessionArr = explode('_',decryptRsa(I('encrypt_str')));
        $session = $sessionArr[1];
        $uid = D('Session')->getTigerUid($session);
        if (!$uid){
            $session = "";
        }
        $this->ajaxReturn([
            'code' => C('RESPONSE_CODE.SUCCESS'),
            'data' => [
                'session' => (string)$session,
            ],
        ]);
    }

    public function lotto()
    {
        try {
            $this->_shake();
        } catch (Exception $e) {
            $this->ajaxReturn([
                'code' => C('RESPONSE_CODE.ERROR'),
                'msg' => $e->getMessage(),
            ]);
        }

    }

    private function _shake()
    {
        if ($this->_checkLogin()) {
            $user_integral = D('UserIntegral')->getUserIntegralInfo($this->uid);
            if ((int)$user_integral['user_integral_balance'] < C('LOTTO_INTEGRAL_VALUE')) {
                throw new Exception('积分不足');
            }

            $lotto_time = date('Y-m-d H:i:s');
            $prize_info = $this->_getPrizeInfo();

            if ($prize_info['coupon_id']) {
                $coupon = D('Coupon')->getCouponById($prize_info['coupon_id']);
            }

            $this->_grantPrize($prize_info, $user_integral);

            $user_integral = D('UserIntegral')->getUserIntegralInfo($this->uid);

            $this->ajaxReturn([
                'code' => C('RESPONSE_CODE.SUCCESS'),
                'data' => [
                    'id' => (int)$prize_info['turntable_id'],
                    'hit_time' => $lotto_time,
                    'nickname' => hiddenMobile($this->user_info['user_telephone']),
                    'integral_balance' => $user_integral['user_integral_balance'],
                    'prize' => $prize_info['turntable_name'],
                    'type' => (int)$prize_info['turntable_type'],
                    'min_consume_price' => isset($coupon) ? (int)$coupon['coupon_min_consume_price'] : 0,
                ],
            ]);
        }

        throw new Exception('用户未登录');
    }

    private function _grantPrize($prize_info, $user_integral)
    {
        M()->startTrans();
        $log_id = D('TurntableLog')->addLog($this->uid, $prize_info, $user_integral);
        if (!$log_id) {
            throw new Exception('未知错误');
        }

        //扣除相应积分
        $user_integral_instance = new UserIntegralController();
        $update_integral = $user_integral_instance->addUserIntegral($this->uid, C('LOTTO_INTEGRAL_VALUE'), false, C('TURNTABLE_EXPEND_TYPE'));
        if (!$update_integral) {
            M()->rollback();
            throw new Exception('未知错误');
        }

//        $update_log = D('TurntableLog')->updateAfterIntegral($log_id, $this->uid);
//        ApiLog('$update_log:' . print_r($update_log, true), 'turntable');
//        if (!$update_log) {
//            M()->rollback();
//            throw new Exception('未知错误');
//        }

        M()->commit();

        if ($prize_info['turntable_type']) {
            // 发放奖品
            switch ($prize_info['turntable_type']) {
                case C('TURNTABLE_TYPE.COUPON'):
                    $this->grantCoupon($this->uid,$prize_info['coupon_id']);
                    break;
                case C('TURNTABLE_TYPE.INTEGRAL'):
                    $user_integral_instance->addUserIntegral($this->uid, $prize_info['interger_value'], true, C('TURNTABLE_INCOME_TYPE'));
                    break;
                case C('TURNTABLE_TYPE.ENTITY'):
                    break;
                default:
                    throw new Exception('type值无效');
                    break;
            }
        }

    }

    public function grantCoupon($uid,$coupon_id)
    {
        $data['uid'] = $uid;
        $data['coupon_id'] = $coupon_id;
        $data['log_type'] = 14;
        $msgQueueOfCoupon = new MsgQueueOfCouponController();
        $response_data = $msgQueueOfCoupon->send($coupon_id,$data);
    }

    private function _getPrizeInfo()
    {
        $turntable_list = D('Turntable')->getList();
        $max_number = 0;
        $prize_info = [];
        foreach ($turntable_list as $item){
            $max_number += $item['turntable_n'];
            $prize_info[$item['turntable_id']] = $item;

            for ($i = 1; $i <= $item['turntable_n']; $i++){
                $this->_pushPool($item['turntable_id']);
            }
        }

        $rand_number = mt_rand(0,$max_number - 1);
        return $prize_info[self::$pool[$rand_number]];
    }

    private function _pushPool($turntable_id)
    {
        self::$pool[] = $turntable_id;
    }

    private function _checkLogin()
    {
        $session_code = $this->_getSessionCode();

        if (!$session_code){
            return false;
        }
        $uid = D('Session')->getTigerUid($session_code);

        if (empty($uid)){
            return false;
        }
        $this->uid = $uid;
        $this->user_info = D('User')->getUserInfo($uid);

        return true;
    }

}