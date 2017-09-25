<?php

namespace Home\Controller;

use Home\Controller\GlobalController;

class ChannelCouponController extends GlobalController{

	public function exchange($api){
		$userInfo = $this->getAvailableUser($api->session);
		if ($userInfo['channel_id']) {
			if($api->sdk_version>1){
				\AppException::throwException(C('ERROR_CODE.THIS_COUPON_IS_EXCHANGEED'));
			}else{
				\AppException::throwException(C('ERROR_CODE.COUPON_EXCHANGE_INVALID'));
			}
		}
		$coupon_code = strtoupper($api->coupon_code);
		$channel_coupon_info = D('ChannelCoupon')->queryInfoByCode($coupon_code);
		if (empty($channel_coupon_info)) {
			\AppException::throwException(C('ERROR_CODE.COUPON_EXCHANGE_NO_EXIST'));
		}
		
		$is_used = ($channel_coupon_info['cc_status'] > 0);
		if($channel_coupon_info['cc_start_time']=='0000-00-00 00:00:00' && $channel_coupon_info['cc_end_time']=='0000-00-00 00:00:00'){
			$out_of_time = 0;
		}else{
			$out_of_time = (time() < strtotime($channel_coupon_info['cc_start_time'])) || (time() > strtotime($channel_coupon_info['cc_end_time']));
		}
		if ($is_used || $out_of_time) {
			\AppException::throwException(C('ERROR_CODE.COUPON_EXCHANGE_INVALID'));
		}
		
		$this->_exchangeAndGiveCoupon($userInfo['uid'], $channel_coupon_info);
		
		return array(
				'result' => '',
				'code' => C('ERROR_CODE.SUCCESS') 
		);
	}

	private function _genChannelCouponCategory($coupon_value_list, $plan_id){
		ApiLog('cou value list:'.print_r($coupon_value_list,true), 'cou');
		$coupon_value_list = array_unique($coupon_value_list);
		foreach ($coupon_value_list as $coupon_value) {
			$map['coupon_value'] = $coupon_value;
			$map['plan_id'] = $plan_id;
			ApiLog('cou map:'.print_r($map,true), 'cou');
				
			$coupon_info = D('Coupon')->where($map)->find();
			ApiLog('cou value:'.M()->_sql(), 'cou');
			if (empty($coupon_info)) {
				$coupon_data['coupon_name'] = '新人专享活动红包' . $coupon_value . '元';
				$coupon_data['coupon_value'] = $coupon_value;
				$coupon_data['coupon_is_sell'] = 0;
				$coupon_data['coupon_status'] = 0;
				$coupon_data['coupon_image'] = 'http://mg.tigercai.com/Application/Runtime/Uploads/Picture/2016-04-25/571d7d72480cf.png';
				$coupon_data['plan_id'] = $plan_id;
				D('Coupon')->add($coupon_data);
				
				$map['coupon_value'] = $coupon_value;
				$map['plan_id'] = $plan_id;
				$coupon_info = D('Coupon')->where($map)->find();
			}
			$coupon_list[$coupon_value] = $coupon_info;
		}
		return $coupon_list;
	}

	private function _exchangeAndGiveCoupon($uid, $channel_coupon_info){
		$plan_info = D('ChannelCouponPlan')->queryInfoById($channel_coupon_info['plan_id']);
		$day_step = $plan_info['plan_devide_step'];
		ApiLog('info:'.print_r($plan_info,true), 'cou');
		$coupon_value_list = explode(',', $plan_info['plan_devide_section']);
		ApiLog('$coupon_value_list:'.print_r($coupon_value_list,true), 'cou');
		
		$coupon_list = $this->_genChannelCouponCategory($coupon_value_list, $channel_coupon_info['plan_id']);
		ApiLog('$coupon_list:'.print_r($coupon_list,true), 'cou');
		
		foreach ($coupon_value_list as $i => $coupon_value) {
			$coupon_info = $coupon_list[$coupon_value];

            $verify_exchange_limit_time = $this->_verifyCouponExchangeLimitTime($coupon_info,$uid);
            if(!$verify_exchange_limit_time){
                \AppException::throwException(C('ERROR_CODE.COUPON_EXCHANGE_LIMIT_TIMES'));
            }

			$user_coupon_data['uid'] = $uid;
			$user_coupon_data['coupon_id'] = $coupon_info['coupon_id'];
			$user_coupon_data['user_coupon_balance'] = $coupon_info['coupon_value'];
			$user_coupon_data['user_coupon_status'] = C('USER_COUPON_STATUS.AVAILABLE');
			
			$user_coupon_data['user_coupon_amount'] = $coupon_info['coupon_value'];
			$user_coupon_data['cc_id'] = $channel_coupon_info['cc_id'];
			$user_coupon_data['user_coupon_desc'] = $coupon_info['coupon_name'];
			if ($i == 0) {
				$end_hour = $start_hour = date("H");
				$end_min = $start_min = date("i");
				$end_sec = $start_sec = date("s");
				$start_day = date("d") + $i * $day_step;
				$end_day = date("d") + ($i + 1) * $day_step;
			} else {
				$start_hour = 0;
				$start_min = 0;
				$start_sec = 0;
				$end_hour = 23;
				$end_min = 59;
				$end_sec = 59;
				$start_day = date("d") + $i * $day_step;
				$end_day = date("d") + ($i + 1) * $day_step - 1 ;
			}
			
			if ($plan_info['plan_devide_date_type'] == 1) {
				$start_time = date("Y-m-d H:i:s", mktime($start_hour, $start_min, $start_sec, date("m"), $start_day, date("Y")));
				$end_time = date("Y-m-d H:i:s", mktime($end_hour, $end_min, $end_sec, date("m"), $end_day, date("Y")));
			} else if ($plan_info['plan_devide_date_type'] == 2) {
				\AppException::throwException(C('ERROR_CODE.COUPON_EXCHANGE_NO_EXIST'));
				return false;
// 				$start_time = date("Y-m-d H:i:s", mktime($start_hour, $start_min, $start_sec, date("m") + $i, date("d"), date("Y")));
// 				$end_time = date("Y-m-d H:i:s", mktime($end_hour, $end_min, $end_sec, date("m") + $i , date("d") , date("Y")));
			} else {
				\AppException::throwException(C('ERROR_CODE.COUPON_EXCHANGE_NO_EXIST'));
				return false;
			}
			$user_coupon_data['user_coupon_create_time'] = date("Y-m-d H:i:s");
			$user_coupon_data['user_coupon_start_time'] = $start_time;
			$user_coupon_data['user_coupon_end_time'] = $end_time;
			$user_coupon_list[] = $user_coupon_data;
		}
		$channel_info = D('Channel')->find($channel_coupon_info['channel_id']);

		M()->startTrans();
		ApiLog('user list:'.print_r($user_coupon_list,true),'cou');
		$add_result = D('UserCoupon')->addAll($user_coupon_list);
		ApiLog('add_result list:'.print_r($add_result,true),'cou');
		
		$user_data['channel_id'] = $channel_coupon_info['channel_id'];
		$user_data['saler_id'] = $channel_info['saler_id'];
		$user_map['uid'] = $uid;
		$user_map['channel_id'] = 0;
		$user_result = D('User')->where($user_map)->save($user_data);
		ApiLog('$user_result :'.print_r($user_result,true),'cou');
		
		$cc_data['cc_status'] = 1;
		$cc_data['uid'] = $uid;
		$cc_map['cc_id'] = $channel_coupon_info['cc_id'];
		$cc_result = D('ChannelCoupon')->where($cc_map)->save($cc_data);
		ApiLog('$$cc_result :'.print_r($cc_result,true),'cou');
		
		if ($add_result && $user_result && $cc_result) {
			M()->commit();
			return array(   'result' => '',
	                    'code'   => C('ERROR_CODE.SUCCESS'));
		} else {
			M()->rollback();
			\AppException::throwException(C('ERROR_CODE.DATABASE_ERROR'));
		}
	}

    private function _verifyCouponExchangeLimitTime($coupon_info,$uid){
        $exchange_limit_times = $coupon_info['coupon_exchange_limit_times'];
        if(empty($exchange_limit_times)){
            return true;
        }
        $coupon_id = $coupon_info['coupon_id'];
        $user_coupon_count = D('UserCoupon')->getUserCouponCountById($coupon_id,$uid);
        $user_coupon_count = empty($user_coupon_count) ? 0 : $user_coupon_count;
        if($user_coupon_count >= $exchange_limit_times){
            return false;
        }
        return true;
    }
}

