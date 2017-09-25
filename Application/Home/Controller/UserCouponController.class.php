<?php

namespace Home\Controller;

use Home\Controller\GlobalController;

class UserCouponController extends GlobalController{
	const FIRST_RECHARGE_AMOUNT = 20;
	const FIRST_RECHARGE_COUPON_ID = 40;
	const COUPON_VALID_DATE = 10;

	public function rewardCouponForFirstRecharge($recharge_id){
	    $current_time = getCurrentTime();
	    $new_first_recharge_start_time = C('NEW_FIRST_RECHARGE_START_TIME');
	    if($current_time < $new_first_recharge_start_time){
            $recharge_info = D('Recharge')->getRechargeInfo($recharge_id);
            if (!$this->_checkValidForFirstRechargeReward($recharge_info)) {
                return false;
            }

            $coupon_info = D('Coupon')->getCouponInfo(self::FIRST_RECHARGE_COUPON_ID);

            $exchange_id = 0;
            $activity_id = 0;
            $this->_addFirstRechargeCoupon($recharge_info['uid'], $coupon_info, C('USER_COUPON_LOG_TYPE.FIRST_RECHARGE_REWARD'), C('USER_COUPON_STATUS.AVAILABLE'), $exchange_id, $activity_id);
        }else{
            $first_recharge_activity = new FirstRechargeActController();
            $first_recharge_activity->rewardCouponForFirstRecharge($recharge_id);
        }

	}

	private function _buildCouponDateRangeList(){
		$date_list[0]['start_time'] = getCurrentTime();
		$date_list[0]['end_time'] = date('Y-m-d H:i:s', time() + self::COUPON_VALID_DATE * 86400);
		
		$day_num = self::COUPON_VALID_DATE;
		for($i = 1; $i < 4; $i++) {
			$date_list[$i]['start_time'] = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m') + $i, 1, date('y')));
			$date_list[$i]['end_time'] = date('Y-m-d H:i:s', mktime(23, 59, 59, date('m') + $i, $day_num, date('y')));
		}
		return $date_list;
	}

	
	private function _buildUserCouponDesc($log_type,$coupon_info){
		return $coupon_info['coupon_name'];
	}
	
	private function _addFirstRechargeCoupon($uid, $coupon_info, $log_type, $status, $couponExchangeId = 0, $activityId = 0){
		$user_coupon_data['uid'] = $uid;
		$user_coupon_data['coupon_id'] = $coupon_info['coupon_id'];
		$user_coupon_data['user_coupon_balance'] = $coupon_info['coupon_value'];
		$user_coupon_data['user_coupon_amount'] = $coupon_info['coupon_value'];
		$user_coupon_data['activity_id'] = $activityId;
		$user_coupon_data['user_coupon_create_time'] = getCurrentTime();
		$user_coupon_data['ce_id'] = $couponExchangeId;
		$user_coupon_data['user_coupon_status'] = $status;
		
		$valid_date_list = $this->_buildCouponDateRangeList();
		M()->startTrans();
		foreach ($valid_date_list as $valid_date) {
			$user_coupon_data['user_coupon_start_time'] = $valid_date['start_time'];
			$user_coupon_data['user_coupon_end_time'] = $valid_date['end_time'];
			$user_coupon_data['user_coupon_desc'] = $this->_buildUserCouponDesc($log_type, $coupon_info);
			
			$userCouponId = D('UserCoupon')->add($user_coupon_data);
			$consumeCoupon = D('UserAccount')->updateBuyCouponStatics($uid, $coupon_info['coupon_value']);
			$log_result = D('UserCouponLog')->addUserCouponLog($uid, $userCouponId, $coupon_info['coupon_value'], $coupon_info['coupon_value'], $log_type, $uid);
			if ($userCouponId && $consumeCoupon && $log_result) {
			} else {
				M()->rollback();
				return false;
			}
		}
		M()->commit();
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

    public function grantCouponToUser($coupon_id,$uid,$log_type,$issue_no=0){
        $coupon_info = D('Coupon')->getCouponInfo($coupon_id);
        $user_coupon_data['uid'] = $uid;
        $user_coupon_data['coupon_id'] = $coupon_info['coupon_id'];
        $user_coupon_data['user_coupon_balance'] = $coupon_info['coupon_value'];
        $user_coupon_data['user_coupon_status'] = C('USER_COUPON_STATUS.AVAILABLE');
        $user_coupon_data['user_coupon_amount'] = $coupon_info['coupon_value'];
        $user_coupon_data['coupon_type'] = $coupon_info['coupon_type'];
        $user_coupon_data['user_coupon_desc'] = $this->_getUserCouponDesc($coupon_info);
        $user_coupon_data['user_coupon_start_time'] = getCurrentTime();
        $user_coupon_data['user_coupon_create_time'] = getCurrentTime();
        $user_coupon_data['user_coupon_end_time'] = getUserCouponEndTime($coupon_info);
        $user_coupon_data['coupon_min_consume_price'] = $coupon_info['coupon_min_consume_price'];
        $user_coupon_data['coupon_lottery_ids'] = empty($coupon_info['coupon_lottery_ids']) ? '' : $coupon_info['coupon_lottery_ids'];
        $user_coupon_data['play_type'] = $coupon_info['play_type'];
        $user_coupon_data['bet_type'] = $coupon_info['bet_type'];
        $user_coupon_data['coupon_type'] = $coupon_info['coupon_type'];
        $user_coupon_data['activity_id'] = $coupon_info['activity_id'];
        $user_coupon_data['issue_id'] = $issue_no;

        M()->startTrans();
        if(!empty($issue_no)){
            $user_coupon_info = D('UserCoupon')->getUserCouponInfoByAndUidIssueId($uid,$coupon_id,$issue_no);
            if(!empty($user_coupon_info)){
                ApiLog($uid . '彩期:'.$issue_no . '$coupon_id:'.$coupon_id.'不能重复发放', 'grantUserCoupon');
                M()->rollback();
                return false;
            }
        }
        $userCouponId = M('UserCoupon')->add($user_coupon_data);
        $log_result = D('UserCouponLog')->addUserCouponLog($uid, $userCouponId, $user_coupon_data['user_coupon_balance'], $user_coupon_data['user_coupon_balance'], $log_type, $uid);
        $consumeCoupon = D('UserAccount')->updateBuyCouponStatics($uid, $user_coupon_data['user_coupon_balance']);
        if ($userCouponId && $consumeCoupon && $log_result) {
            ApiLog($uid . '用户请求兑换红包请求成功!$user_coupon_data:' . print_r($user_coupon_data, true), 'grantUserCoupon');
            M()->commit();
            return true;
        } else {
            ApiLog($uid . '用户请求兑换红包请求失败!$user_coupon_data:' . print_r($user_coupon_data, true), 'grantUserCoupon');
            M()->rollback();
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

