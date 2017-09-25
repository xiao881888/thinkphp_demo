<?php
namespace Crontab\Controller;

use Integral\Controller\UserController;
use Think\Controller;

class InitUserIntegralDataController extends Controller
{
    //TODO 分段跑
    public function initIntegralOfUser(){
        $index = I('index');
        set_time_limit(0);
        $users = $this->_getUserList($index);
        foreach($users as $user){
            $user_telephone = $user['user_telephone'];
            $uid = $user['uid'];
            $user_exp_value = $this->_getUserExp($uid);
            if($user_exp_value >= 500000){
                $user_exp_value = 500000;
            }
            $user_vip_level = $this->_initUserVipLevel($user_exp_value);
            $user = array(
                'uid' => $uid,
                'user_telephone' => $user_telephone,
                'user_exp_value' => $user_exp_value,
                'user_vip_level' => $user_vip_level,
            );
            $request_data['data'] = json_encode($user);
            $request_data['act_code'] = 1001;
            $request_url = C('REQUEST_HOST').U('Integral/Index/index');
            $result = postByCurl($request_url,$request_data);
            $result = json_decode($result,true);
            if($result['error_code'] == 1){
                ApiLog('$uid:'.$uid.';添加失败:'.$result['data'],'InitUserIntegral');
            }
        }
        echo '结束';
    }

    private function _initUserVipLevel($user_exp_value){
        if($user_exp_value < 5000){
            return 1;
        }elseif($user_exp_value >= 5000 && $user_exp_value < 50000){
            return 2;
        }elseif($user_exp_value >= 50000 && $user_exp_value < 500000){
            return 3;
        }elseif($user_exp_value >= 500000){
            return 4;
        }
    }

    private function _getUserList($index=1){
        $where['uid'] = array('BETWEEN',array(5000*($index-1)+1,5000*$index));
        if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
            $users = M('User')->db(1,C('READ_DB'),true)->where($where)->field('uid,user_telephone')->select();
        }elseif( get_cfg_var('PROJECT_RUN_MODE') == 'TEST' ){
            $users = M('User')->where($where)->field('uid,user_telephone')->select();
        }else {
            $users = M('User')->where($where)->field('uid,user_telephone')->select();
        }
        return $users;
    }

    private function _getUserExp($uid){
        //TODO  IN
        $where['order_status'] = array('IN',array(3,8));
        $where['uid'] = $uid;
        if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
            $order_total_amount1 = M('Order')->db(1,C('READ_DB'),true)->where($where)->sum('order_total_amount');
            $order_total_amount2 = M('OrderBackup')->db(1,C('READ_DB'),true)->where($where)->sum('order_total_amount');
            $order_total_amount = $order_total_amount1 + $order_total_amount2;
            $order_refund_amount1 = M('Order')->db(1,C('READ_DB'),true)->where($where)->sum('order_refund_amount');
            $order_refund_amount2 = M('OrderBackup')->db(1,C('READ_DB'),true)->where($where)->sum('order_refund_amount');
            $order_refund_amount = $order_refund_amount1 + $order_refund_amount2;
        }elseif( get_cfg_var('PROJECT_RUN_MODE') == 'TEST' ){
            $order_total_amount = M('Order')->where($where)->sum('order_total_amount');
            $order_refund_amount = M('Order')->where($where)->sum('order_refund_amount');
        }else {
            $order_total_amount = M('Order')->where($where)->sum('order_total_amount');
            $order_refund_amount = M('Order')->where($where)->sum('order_refund_amount');
        }
        return round($order_total_amount - $order_refund_amount);
    }

    public function testInit(){
        $uid = 36;
        $user_telephone = 15659033493;
        $user_exp_value = $this->_getUserExp($uid);
        if($user_exp_value >= 500000){
            $user_exp_value = 500000;
        }
        $user_vip_level = $this->_initUserVipLevel($user_exp_value);
        $user = array(
            'uid' => $uid,
            'user_telephone' => $user_telephone,
            'user_exp_value' => $user_exp_value,
            'user_vip_level' => $user_vip_level,
        );
        $request_data['data'] = json_encode($user);
        $request_data['act_code'] = 1001;
        $request_url = C('REQUEST_HOST').U('Integral/Index/index');
        $result = postByCurl($request_url,$request_data);
        $result = json_decode($result,true);
        if($result['error_code'] == 1){
            ApiLog('$uid:'.$uid.';添加失败:'.$result['data'],'InitUserIntegral');
        }
    }
}
