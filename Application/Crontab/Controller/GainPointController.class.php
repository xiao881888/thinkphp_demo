<?php
namespace Crontab\Controller;

use Think\Controller;

class GainPointController extends Controller
{
    private $_switch_on = true;
    private $_key = '19a45f1aa833a630373a6c2277e1f6df';//加密KEY
    private $_duoBaoGainPointURL = 'http://db.tigercai.com/index.php?s=/Home/TigerRequest/index';//夺宝增加积分的接口地址
    //private $_duoBaoGainPointURL = 'http://192.168.3.93/duobao2/trunk/index.php?s=/Home/TigerRequest/index';
    private $_SUCCESS_STATUS = 1;
    private $_FAIL_STATUS = 0;

    public function index()
    {
        if($this->_switch_on){
            //设置程序执行时间的函数
            set_time_limit(0);

            //获取可以得到积分的红包列表
            $couponList = D('Coupon')->getCouponList();

            $firstCurrDate = $this->_getFirstCurrDate();//今天的零点
            $lastCurrDate = $this->_getLastCurrDate();//今天的24点

            //获取已经增加过积分的订单
            $alreadyAddPointOrderList = D('AlreadyAddPointOrder')->getAlreadyAddPointOrder($firstCurrDate, $lastCurrDate);

            //获取未处理的订单
            $dealOrderList = D('Order')->getTodayOrderList($firstCurrDate, $lastCurrDate, $alreadyAddPointOrderList);

            foreach ($dealOrderList as $dealOrder) {
                $order_id = $dealOrder['order_id'];
                if (!empty($dealOrder['user_coupon_id'])) {
                    //获取红包是否可以获得积分
                    $coupon_id = $this->_getCouponIdByUserCouponId($dealOrder['user_coupon_id']);

                    if (!in_array($coupon_id, $couponList)) {
                        $payMoney = $dealOrder['order_total_amount'] - $dealOrder['order_refund_amount'] - $dealOrder['user_coupon_amount'];
                    } else {
                        $payMoney = $dealOrder['order_total_amount'] - $dealOrder['order_refund_amount'];
                    }
                } else {
                    $payMoney = $dealOrder['order_total_amount'] - $dealOrder['order_refund_amount'];
                }

                $user_telephone = M('User')->where(array('uid' => $dealOrder['uid']))->getField('user_telephone');


                if($payMoney > 0){
                    $request = array(
                        'action' => 101,
                        'data' => array(
                            'user_telephone' => $user_telephone,
                            'pay_money' => $payMoney,
                            'order_id' => $order_id,
                            'sign_str' => $this->_addSign($user_telephone, $payMoney, $order_id),
                        )
                    );

                    //请求夺宝获取积分的接口
                    $result = $this->_requestDuobaoGainPoint($request);
                    if ($result) {
                        $data = array();
                        $data['aapo_uid'] = $dealOrder['uid'];
                        $data['aapo_user_telephone'] = $user_telephone;
                        $data['aapo_order_id'] = $dealOrder['order_id'];
                        $data['aapo_order_status'] = $dealOrder['order_status'];
                        $data['aapo_order_createtime'] = $dealOrder['order_create_time'];
                        $data['aapo_createtime'] = getCurrentTime();
                        $data['aapo_pay_money'] = $payMoney;
                        $alreadyAddPointOrderId = M('AlreadyAddPointOrder')->add($data);
                        if ($alreadyAddPointOrderId) {
                            ApiLog($dealOrder['uid'] . '-' . $user_telephone . '消费' . $payMoney . '增加临时表成功： ', 'GainPoint');
                        } else {
                            ApiLog($dealOrder['uid'] . '-' . $user_telephone . '消费' . $payMoney . '增加临时表失败： ', 'GainPoint');
                        }
                    }
                }else{
                    $data = array();
                    $data['aapo_uid'] = $dealOrder['uid'];
                    $data['aapo_user_telephone'] = $user_telephone;
                    $data['aapo_order_id'] = $dealOrder['order_id'];
                    $data['aapo_order_status'] = $dealOrder['order_status'];
                    $data['aapo_order_createtime'] = $dealOrder['order_create_time'];
                    $data['aapo_createtime'] = getCurrentTime();
                    $data['aapo_pay_money'] = $payMoney;
                    $alreadyAddPointOrderId = M('AlreadyAddPointOrder')->add($data);
                    if ($alreadyAddPointOrderId) {
                        ApiLog($dealOrder['uid'] . '-' . $user_telephone . '消费' . $payMoney . '增加临时表成功： ', 'GainPoint');
                    } else {
                        ApiLog($dealOrder['uid'] . '-' . $user_telephone . '消费' . $payMoney . '增加临时表失败： ', 'GainPoint');
                    }
                }
            }
        }else{
            ApiLog('获取积分通道已关闭', 'GainPoint');
        }

    }

    private function _getCouponIdByUserCouponId($userCouponId)
    {
        return  M('UserCoupon')->where(array('user_coupon_id' => $userCouponId))->getField('coupon_id');
    }

    private function _requestDuobaoGainPoint($request)
    {
        ApiLog('request:' . print_r($request, true), 'GainPoint');
        $request_params = $this->_encryptJsonData($request);
        $result = postByCurl($this->_duoBaoGainPointURL, $request_params);
        $result = $this->_decryptJsonData($result);
        ApiLog('response:' . print_r($result, true), 'GainPoint');
        if ($result['status'] === 0) {
            ApiLog('请求成功:' . $result['data'], 'GainPoint');
            return $this->_SUCCESS_STATUS;
        } else {
            ApiLog('请求失败:' . $result['data'], 'GainPoint');
            return $this->_FAIL_STATUS;
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


    private function _addSign($user_telephone, $pay_money, $order_id)
    {
        $sign_str = $this->_key;
        $sign_str .= ',user_telephone:' . $user_telephone;
        $sign_str .= ',pay_money:' . $pay_money;
        $sign_str .= ',order_id:' . $order_id;
        return md5($sign_str);
    }


    private function _getFirstCurrDate()
    {
        //获取当天的年份
        $y = date("Y");
        //获取当天的月份
        $m = date("m");
        //获取当天的号数
        $d = date("d");
        //将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
        $firstTodayTime = mktime(0, 0, 0, $m, $d, $y);
        return date('Y-m-d H:i:s', $firstTodayTime);
    }

    private function _getLastCurrDate()
    {
        //获取当天的年份
        $y = date("Y");
        //获取当天的月份
        $m = date("m");
        //获取当天的号数
        $d = date("d");
        //将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
        $LastTodayTime = mktime(0, 0, 0, $m, $d + 1, $y);
        return date('Y-m-d H:i:s', $LastTodayTime);
    }


}
