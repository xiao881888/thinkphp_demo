<?php

namespace Home\Controller;

use Home\Controller\GlobalController;

class FirstRechargeActController extends GlobalController{
	const FIRST_RECHARGE_AMOUNT = 20;

	public function testFirstRecharge(){
        $recharge_id = I('id');
        A('UserCoupon')->rewardCouponForFirstRecharge($recharge_id);
    }

	public function rewardCouponForFirstRecharge($recharge_id){
		$recharge_info = D('Recharge')->getRechargeInfo($recharge_id);

		if(!$this->_isAddCoupon($recharge_info['uid'])){
            ApiLog('uid:'.$recharge_info['uid'].'不是老虎用户','no_tiger_user1');
            return false;
        }

		if (!$this->_checkValidForFirstRechargeReward($recharge_info)) {
			return false;
		}
		$coupon_list = C('NEW_FIRST_RECHARGE_COUPON_LIST');
        $time_diff_list = C('NEW_FIRST_RECHARGE_TIME_DIFF');
        $expire_time_list = C('NEW_FIRST_RECHARGE_EXPIRE_TIME');
        M()->startTrans();
        foreach($coupon_list as $coupon_issue => $coupon_ids){
            foreach($coupon_ids as $coupon_id){
                $coupon_start_time = $this->_getCouponStartTime($time_diff_list[$coupon_issue]);
                $coupon_end_time = $this->_getCouponEndTime($coupon_start_time,$expire_time_list[$coupon_issue]);
                $grant_status = $this->_grantCouponToUser($coupon_id,$recharge_info['uid'],C('USER_COUPON_LOG_TYPE.FIRST_RECHARGE_REWARD'),$coupon_start_time,$coupon_end_time);
                ApiLog('uid:'.$recharge_info['uid'].';$grant_status:'.$grant_status,'newFirstRecharge');
                if(!$grant_status){
                    M()->rollback();
                    return false;
                }
            }
        }
        M()->commit();
        return true;
	}

    private function _isAddCoupon($uid){
        $user_info = D('User')->getUserInfo($uid);
        $app_id = getRegAppId($user_info);
        if($app_id == C('APP_ID_LIST.BAIWAN')) {
            return false;
        }
        return true;
    }

	private function _getCouponStartTime($time_diff){
        return date('Y-m-d H:i:s',time()+$time_diff*24*60*60);
    }

    private function _getCouponEndTime($coupon_start_time,$expire_time){
        return date('Y-m-d H:i:s',strtotime($coupon_start_time)+$expire_time*24*60*60);
    }

    private function _checkValidForFirstRechargeReward($recharge_info){
        if($recharge_info['recharge_status']!=C('RECHARGE_STATUS.PAID')){
            return false;
        }
        if ($recharge_info['recharge_amount'] < self::FIRST_RECHARGE_AMOUNT) {
            return false;
        }
        $uid = $recharge_info['uid'];
        if (empty($uid)){
            return false;
        }

        $recharge_map['recharge_id'] = array(
            'NEQ',
            $recharge_info['recharge_id']
        );
        $recharge_map['recharge_status'] = C('RECHARGE_STATUS.PAID');
        $recharge_map['uid'] = $uid;
        $recharge_map['recharge_create_time'] = array(
            'LT',
            $recharge_info['recharge_create_time']
        );
        $recharge_count = D('Recharge')->where($recharge_map)->count();
        if ($recharge_count > 0) {
            return false;
        }

        // 首单规则
        $map['uid'] = $uid;
        $map['ucl_type'] = C('USER_COUPON_LOG_TYPE.FIRST_RECHARGE_REWARD');
        $count = D('UserCouponLog')->where($map)->count();
        if ($count > 0) {
            return false;
        }
        return true;
    }

    private function _grantCouponToUser($coupon_id,$uid,$log_type,$start_time='',$end_time=''){
        $coupon_info = D('Coupon')->getCouponInfo($coupon_id);
        $user_coupon_data['uid'] = $uid;
        $user_coupon_data['coupon_id'] = $coupon_info['coupon_id'];
        $user_coupon_data['user_coupon_balance'] = $coupon_info['coupon_value'];
        $user_coupon_data['user_coupon_status'] = C('USER_COUPON_STATUS.AVAILABLE');
        $user_coupon_data['user_coupon_amount'] = $coupon_info['coupon_value'];
        $user_coupon_data['coupon_type'] = $coupon_info['coupon_type'];
        $user_coupon_data['user_coupon_desc'] = $this->_getUserCouponDesc($coupon_info);
        $user_coupon_data['user_coupon_start_time'] = !empty($start_time) ? $start_time : getCurrentTime();
        $user_coupon_data['user_coupon_create_time'] = getCurrentTime();
        $user_coupon_data['user_coupon_end_time'] = $end_time;
        $user_coupon_data['coupon_min_consume_price'] = $coupon_info['coupon_min_consume_price'];
        $user_coupon_data['coupon_lottery_ids'] = empty($coupon_info['coupon_lottery_ids']) ? '' : $coupon_info['coupon_lottery_ids'];
        $user_coupon_data['play_type'] = $coupon_info['play_type'];
        $user_coupon_data['bet_type'] = $coupon_info['bet_type'];
        $user_coupon_data['coupon_type'] = $coupon_info['coupon_type'];
        $user_coupon_data['activity_id'] = $coupon_info['activity_id'];

        $userCouponId = M('UserCoupon')->add($user_coupon_data);
        $log_result = D('UserCouponLog')->addUserCouponLog($uid, $userCouponId, $user_coupon_data['user_coupon_balance'], $user_coupon_data['user_coupon_balance'], $log_type, $uid);
        $consumeCoupon = D('UserAccount')->updateBuyCouponStatics($uid, $user_coupon_data['user_coupon_balance']);
        if ($userCouponId && $consumeCoupon && $log_result) {
            return true;
        } else {
            return false;
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

}

