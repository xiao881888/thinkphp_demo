<?php
namespace Home\Controller;

use Home\Controller\GlobalController;
use Home\Util\Factory;
use Think\Cache\Driver\Redis;

class FullReducedCouponConfigController extends GlobalController
{

    private $_first_loading_coupon_id = 2;
    private $_order_coupon_id = 1;

    public function buyOrder($order_id){
        $activity_status = D('FullReducedCouponConfig')->getActivityStatusById($this->_order_coupon_id);
        if($activity_status){
            if($this->_isAreadyAddOrderList($order_id)){
                ApiLog('该订单已经添加','FullReducedCouponConfig');
                exit;
            }

            $order_info = D('Order')->getOrderInfo($order_id);
            ApiLog('$order_info:'.print_r($order_info,true),'FullReducedCouponConfig');

            if(empty($order_info)){
                ApiLog('订单信息为空','FullReducedCouponConfig');
                exit;
            }
            $order_total_amount = $order_info['order_total_amount'] - $order_info['order_refund_amount'] ;
            $full_reduced_coupon_info = D('FullReducedCouponConfig')->getFullReducedCouponInfoById($this->_order_coupon_id);
            if(empty($full_reduced_coupon_info)){
                ApiLog('下单红包活动为空','FullReducedCouponConfig');
                exit;
            }

            $consume_order_amount = $full_reduced_coupon_info['frcc_consume_order_amount'];
            if($order_total_amount <= $consume_order_amount ){
                ApiLog('消费订单金额：'.$order_total_amount.'不满足：'.$consume_order_amount,'FullReducedCouponConfig');
                exit;
            }

            $grand_status = $this->_grantCouponToUser($order_info['uid'],$this->_order_coupon_id);
            if(!$grand_status){
                ApiLog('增加用户红包失败','FullReducedCouponConfig');
                exit;
            }

            $this->_setAreadyAddOrderList($order_id);
            ApiLog('增加用户红包成功','FullReducedCouponConfig');
            exit;

        }
    }
    
    public function genUserCouponEndTime($coupon_info){
    	return $this->_getUserCouponEndTime($coupon_info);
    }
    
    public function genUserCouponDesc($coupon_info){
    	return $this->_getUserCouponDesc($coupon_info);
    }

    private function _isAreadyAddOrderList($order_id){
        $redis = Factory::createAliRedisObj();
        if($redis){
            $redis->select(0);
            return $redis->sContains('tiger_api:full_reduced_coupon:buy_order_list',$order_id);
        }
        ApiLog('redis未连接','FullReducedCouponConfig');
        return true;
    }

    private function _setAreadyAddOrderList($order_id){
        $redis = Factory::createAliRedisObj();
        if($redis){
            $redis->select(0);
            $redis->sAdd('tiger_api:full_reduced_coupon:buy_order_list',$order_id);
        }else{
            ApiLog('redis未连接','FullReducedCouponConfig');
        }
    }


    public function firstLoading($api){
        $activity_status = D('FullReducedCouponConfig')->getActivityStatusById($this->_first_loading_coupon_id);
        if(!$activity_status){
            ApiLog('获取首次登陆派发满减红包活动未开启','FullReducedCouponConfig');
            return false;
        }
        $uid = D('Session')->getUid($api->session);
        if(!$uid){
            ApiLog('用户未登录','FullReducedCouponConfig');
            return false;
        }

        $today = getTodayString();
        $today_is_loading = $this->_getTodayIsLoading($today,$uid);
        if($today_is_loading){
            return false;
        }
        $grand_status = $this->_grantCouponToUser($uid,$this->_first_loading_coupon_id);
        if($grand_status){
            $this->_setTodayIsLoading($today,$uid);
        }
        return true;

    }

    private function _setTodayIsLoading($today,$uid){
        $redis = Factory::createAliRedisObj();
        if($redis){
            $redis->select(0);
            $redis->sAdd('tiger_api:full_reduced_coupon:first_loading_user_list_'.$today,$uid);
        }else{
            ApiLog('redis未连接','FullReducedCouponConfig');
        }
    }

    private function _getTodayIsLoading($today,$uid){
        $redis = Factory::createAliRedisObj();
        if($redis){
            $redis->select(0);
            return  $redis->scontains('tiger_api:full_reduced_coupon:first_loading_user_list_'.$today,$uid);
        }
        ApiLog('redis未连接','FullReducedCouponConfig');
        return true;
    }

    private function _grantCouponToUser($uid,$full_reduced_coupon_config_id){
        $coupon_id = D('FullReducedCouponConfig')->getCouponId($full_reduced_coupon_config_id);

        $couponInfo = D('Coupon')->getCouponInfo($coupon_id);

        $user_coupon_data['uid'] = $uid;
        $user_coupon_data['coupon_id'] = $couponInfo['coupon_id'];
        $user_coupon_data['user_coupon_balance'] = $couponInfo['coupon_value'];
        $user_coupon_data['user_coupon_status'] = C('USER_COUPON_STATUS.AVAILABLE');
        $user_coupon_data['user_coupon_amount'] = $couponInfo['coupon_value'];
        $user_coupon_data['coupon_type'] = $couponInfo['coupon_type'];
        $user_coupon_data['user_coupon_desc'] = $this->_getUserCouponDesc($couponInfo);
        $user_coupon_data['user_coupon_start_time'] = date("Y-m-d H:i:s");
        $user_coupon_data['user_coupon_create_time'] = date("Y-m-d H:i:s");
        $user_coupon_data['user_coupon_end_time'] = $this->_getUserCouponEndTime($couponInfo);
        $user_coupon_data['coupon_min_consume_price'] = $couponInfo['coupon_min_consume_price'];
        $user_coupon_data['coupon_lottery_ids'] = empty($couponInfo['coupon_lottery_ids']) ? '' : $couponInfo['coupon_lottery_ids'];
        $user_coupon_data['play_type'] = $couponInfo['play_type'];
        $user_coupon_data['bet_type'] = $couponInfo['bet_type'];
        $user_coupon_data['coupon_type'] = $couponInfo['coupon_type'];

        M()->startTrans();
        $userCouponId = M('UserCoupon')->add($user_coupon_data);
        $log_result = D('UserCouponLog')->addUserCouponLog($uid, $userCouponId, $user_coupon_data['user_coupon_balance'], $user_coupon_data['user_coupon_balance'], C('USER_ACCOUNT_LOG_TYPE.DUOBAO_COUPON_REWARD'), $uid);
        $consumeCoupon = D('UserAccount')->updateBuyCouponStatics($uid, $user_coupon_data['user_coupon_balance']);
        if ($userCouponId && $consumeCoupon && $log_result) {
            ApiLog($uid . '用户请求兑换红包请求成功!$user_coupon_data:' . print_r($user_coupon_data, true), 'FullReducedCouponConfig');
            M()->commit();
            //A('UnitePush')->pushFullReducedCouponInfo($uid,$couponInfo['coupon_value']);
            return true;
        } else {
            ApiLog($uid . '用户请求兑换红包请求失败!$user_coupon_data:' . print_r($user_coupon_data, true), 'FullReducedCouponConfig');
            M()->rollback();
            return false;
        }

    }

    private function _getUserCouponEndTime($coupon_info){
        if($coupon_info['coupon_valid_date_type'] == 0){
            return '2099-12-31 23:59:59';
        }elseif($coupon_info['coupon_valid_date_type'] == 1){
            return date('Y-m-d H:i:s',time()+$coupon_info['coupon_duration_time']);
        }elseif($coupon_info['coupon_valid_date_type'] == 2){
            return $coupon_info['coupon_sell_end_time'];
        }

    }

    private function _getUserCouponDesc($coupon_info){
        $limit_lottery_str = '';
        if(empty($coupon_info['coupon_lottery_ids'])){
            $limit_lottery_str =  '可用彩种: 通用';
        }else{
            $coupon_lottery_ids = explode(',',$coupon_info['coupon_lottery_ids']);
            if(count($coupon_lottery_ids) <= 0){
                $limit_lottery_str =  '可用彩种: 通用';
            }else{
                $limit_lottery_str =  '可用彩种: ';
                foreach($coupon_lottery_ids as $lottery_id){
                    $limit_lottery_str .= $this->_getLotteryName($lottery_id).'  ';
                }
            }
        }
        return $limit_lottery_str;
    }

    private function _getLotteryName($lottery_id){
        return M('Lottery')->where(array('lottery_id'=>$lottery_id))->getField('lottery_name');
    }

    public function grantCouponToUser(){
        $uids = I('uids','');
        $full_reduced_coupon_config_id = I('full_reduced_coupon_config_id',0);
        $user_list = explode(',',$uids);
        foreach($user_list as $uid){
            ApiLog('$uid:'.$uid,'AdminFullReducedCouponConfig');
            if($uid && $full_reduced_coupon_config_id){
                $grant_status = $this->_grantCouponToUser($uid,$full_reduced_coupon_config_id);
                ApiLog('$grant_status:'.$grant_status,'AdminFullReducedCouponConfig');
            }
        }
        exit;
    }




}