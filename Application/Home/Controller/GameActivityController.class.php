<?php

namespace Home\Controller;

use Home\Controller\GlobalController;

class GameActivityController extends GlobalController{
	public function __construct(){
		parent::__construct();
	}
	
	private function _queryActivityPlanList(){
		$today = date('Y-m-d H:i:s');
		$map['activity_plan_status'] = 1;
		$map['activity_plan_start_time'] = array('elt', $today);
		$map['activity_plan_end_time'] = array('egt', $today);
		return D('ActivityPlan')->where($map)->select();
	}
	
	public function runActivity($p_request_data){
		$activity_plan_list = $this->_queryActivityPlanList();
		if(empty($activity_plan_list)){
			return false;
		}
		foreach($activity_plan_list as $activity_plan_info){
			$type = $activity_plan_info['activity_plan_type'];
			if($type==1){
				$this->doCashBackActivity($p_request_data, $activity_plan_info);
			}
		}
	}	

	public function doCashBackActivity($p_request_data, $activity_plan_info){
		$request_data = json_decode($p_request_data, true);

		$order_id = intval($request_data['order_id']);
		$order_info = D('Order')->getOrderInfo($order_id);
		$uid = $order_info['uid'];
		$order_create_date = date('Ymd', strtotime($order_info['order_create_time']));
		// 有出票的才处理
		if($order_info['order_status']!=3 && $order_info['order_status']!=8){
			return false;
		}			
		$activity_plan_id = $activity_plan_info['activity_plan_id'];
		$activity_plan_config = json_decode($activity_plan_info['activity_plan_config'],true);
			
		$config_lottery_id = $activity_plan_config['lottery_id'];
		$is_activity_lottery_id = $this->_checkLotteryId($order_info['lottery_id'], $config_lottery_id);
		if (!$is_activity_lottery_id) {
			return false;
		}
		$schedule_id_list = $this->_queryScheduleIdList($activity_plan_config['schedule_no_list']);

		if($order_info['order_type']==2){
			$is_activity_schedule_id = $this->_checkScheduleIdForOptimize($order_info['order_id'], $schedule_id_list);
		}else{
			$is_activity_schedule_id = $this->_checkScheduleId($order_info['order_content'], $schedule_id_list);
		}
		// $is_activity_schedule_id = $this->_checkScheduleId($order_info['order_content'], $schedule_id_list);
		if (!$is_activity_schedule_id) {
			return false;
		}
		$activity_date_list = $this->_buildActivityDateRangeList($activity_plan_info['activity_plan_start_time'], $activity_plan_info['activity_plan_end_time']);

		sort($activity_date_list);
		foreach ($activity_date_list as $idx => $activity_date) {
			if ($order_create_date != $activity_date) {
				continue;
			}
			$issue_id = $activity_date;
			$is_cash_back = $this->_checkIsCashback($order_id, $uid, $activity_plan_id, $issue_id);
			if ($is_cash_back) {
				return false;
			}
			$is_not_first_order = $this->_checkIsNotFirstOrder($order_id, $uid, $activity_date, $schedule_id_list);
			if ($is_not_first_order) {
				return false;
			}
			$this->_doCashback($order_id, $issue_id, $activity_plan_id, $order_info, $activity_plan_config);
		}
	}
	
	private function _buildActivityDateRangeList($start_time,$end_time){
		$date_range = array();
		$start = strtotime($start_time);
		$end = strtotime($end_time);
		for($i=$start;$i<=$end;$i=$i+86400){
			$date_range[] = date('Ymd',$i);
		}
		return $date_range;
	}

	private function _checkLotteryId($order_lottery_id, $config_lottery_id){

		if ($config_lottery_id == C('JC.JCZQ')) {
			if (isJczq($order_lottery_id)) {
				return true;
			}
		}
		return false;
	}

	private function _queryScheduleIdList($schedule_no_list){
		$schedule_id_list = array();
		foreach ($schedule_no_list as $schedule_no_item) {
			$date = $schedule_no_item['date'];
			$no = $schedule_no_item['no'];
			$schedule_ids = D('JcSchedule')->queryScheduleIdsFromDateAndNo($date, $no);
			$schedule_id_list = array_merge($schedule_id_list, $schedule_ids);
		}
		return $schedule_id_list;
	}

	private function _checkScheduleId($order_content_string, $schedule_id_list){
		$bet_item_list = json_decode($order_content_string, true);
		foreach ($bet_item_list as $bet_item) {
			$schedule_id = $bet_item['schedule_id'];
			if (in_array($schedule_id, $schedule_id_list)) {
				return true;
			}
		}
		return false;
	}

	private function _checkScheduleIdForOptimize($order_id, $schedule_id_list){
		$map['order_id'] = $order_id;
		$bet_item_list = D('JcOrderDetail')->where($map)->field('schedule_id')->select();
		foreach ($bet_item_list as $bet_item) {
			$schedule_id = $bet_item['schedule_id'];
			if (in_array($schedule_id, $schedule_id_list)) {
				return true;
			}
		}
		return false;
	}

	private function _checkIsCashback($order_id, $uid, $activity_id, $issue_id){
		$map['uid'] = $uid;
		$map['order_id'] = $order_id;
		$map['activity_id'] = $activity_id;
		$map['issue_id'] = $issue_id;
		$count = D('UserCoupon')->where($map)->count();
		if ($count) {
			return true;
		}
		return false;
	}

	private function _checkIsNotFirstOrder($order_id, $uid, $activity_date, $schedule_id_list){
		$map['uid'] = $uid;
		$map['order_status'] = array(
				'neq',
				5 
		);
		$map['order_id'] = array(
				'lt',
				$order_id 
		);
		$time = date('Y-m-d H:i:s', strtotime($activity_date));
		$map['order_create_time'] = array(
				'egt',
				$time 
		);
		$order_list = D('Order')->where($map)->select();
		foreach ($order_list as $order_info) {
			if($order_info['order_type']==2){
				$is_not_first = $this->_checkScheduleIdForOptimize($order_info['order_id'], $schedule_id_list);
			}else{
				$is_not_first = $this->_checkScheduleId($order_info['order_content'], $schedule_id_list);
			}

			// $is_not_first = $this->_checkScheduleId($order_info['order_content'], $schedule_id_list);
			if ($is_not_first) {
				return true;
			}
		}
		return false;
	}
	
	private function _buildCashBackCouponValue($order_total_amount, $config_info){
		$coupon_value = number_format($order_total_amount * $config_info['cash_back_rate'], 2, '.', '');
		
		if(bcsub($coupon_value, $config_info['cash_back_limit'])>=0){
			$coupon_value = $config_info['cash_back_limit'];
		}

		return $coupon_value;
	}
	
	private function _buildCouponStartTime($config_info){
		return getCurrentTime();
	}
	
	private function _buildCouponEndTime($start_time, $config_info){
		return date('Y-m-d H:i:s', strtotime($start_time) + $config_info['expire_time']);
	}
	
	private function _buildMinConsumePrice($coupon_min_consume_price, $config_info){
		return $coupon_min_consume_price;
	}

	private function _doCashback($order_id, $issue_id, $activity_id, $order_info, $config_info){
		$coupon_value = $this->_buildCashBackCouponValue($order_info['order_total_amount'], $config_info);
		if (!$coupon_value) {
			return false;
		}
		$map['coupon_id'] = $config_info['coupon_id'];
		$game_activity_coupon_info = D('Coupon')->where($map)->find();
		if (empty($game_activity_coupon_info)) {
			return false;
		}
		$user_coupon_data['uid'] = $order_info['uid'];
		$user_coupon_data['coupon_id'] = $game_activity_coupon_info['coupon_id'];
		$user_coupon_data['user_coupon_balance'] = $coupon_value;
		$user_coupon_data['user_coupon_status'] = C('USER_COUPON_STATUS.AVAILABLE');
		$user_coupon_data['user_coupon_amount'] = $coupon_value;
		$user_coupon_data['user_coupon_desc'] = $game_activity_coupon_info['coupon_name'] . $coupon_value . '元';
		$user_coupon_data['user_coupon_start_time'] = $this->_buildCouponStartTime($config_info);
		$user_coupon_data['user_coupon_create_time'] = getCurrentTime();
		$user_coupon_data['user_coupon_end_time'] = $this->_buildCouponEndTime($user_coupon_data['user_coupon_start_time'], $config_info);;
		$user_coupon_data['coupon_min_consume_price'] = $this->_buildMinConsumePrice($game_activity_coupon_info['coupon_min_consume_price'],$config_info);
		$user_coupon_data['coupon_lottery_ids'] = $game_activity_coupon_info['coupon_lottery_ids'];
		$user_coupon_data['activity_id'] = $activity_id;
		$user_coupon_data['issue_id'] = $issue_id;
		$user_coupon_data['order_id'] = $order_id;
		$user_coupon_data['coupon_type'] = $game_activity_coupon_info['coupon_type'];
		M()->startTrans();
		$userCouponId = D('UserCoupon')->add($user_coupon_data);
		
		$consumeCoupon = D('UserAccount')->updateBuyCouponStatics($order_info['uid'], $user_coupon_data['user_coupon_balance']);
		$log_result = D('UserCouponLog')->addUserCouponLog($order_info['uid'], $userCouponId, $user_coupon_data['user_coupon_balance'], $user_coupon_data['user_coupon_balance'], C('USER_COUPON_LOG_TYPE.GIFT'), $order_info['uid']);

		if ($userCouponId && $consumeCoupon && $log_result) {
			M()->commit();
		} else {
			M()->rollback();
		}
	}
}