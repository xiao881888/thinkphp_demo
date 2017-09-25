<?php
namespace Crontab\Controller;
use Think\Controller;

class CouponGiftActivityController extends Controller {
	const USER_COUPON_STATUS_OF_AVAILABLE = 3;
	const USER_COUPON_LOG_TYPE_OF_GIFT = 5;
	private $_activity_id = 1;
	private $_redis_instance = null;
    private $_cache_name_prefix = 'tiger_activity:';
    private $_activity_coupon_ids = array();
	
	public function __construct(){
		$this->_redis_instance = $this->_getRedisInstance();
		$this->_activity_id = 1; 
		$this->_activity_coupon_ids = array(
            1 => array(153,154,155),
            2 => array(153,154,155),
            3 => array(153,154,155),
            4 => array(154,156,155),
            5 => array(154,156,155),
            6 => array(157,158,159,),
            7 => array(157,158,159,),
		);
		
		parent::__construct();
	}

	public function test(){
// 		$activity_coupon_info = $this->_getCouponInfoById(51);
// 		$user_coupon_data['user_coupon_desc'] = A('Home/FullReducedCouponConfig')->genUserCouponDesc($activity_coupon_info);
// 		print_r($user_coupon_data);
		//$map['coupon_id'] = array('IN',$this->_activity_coupon_ids);
		//D('UserCoupon')->where($map)->delete();
		die();
		$cache_name_for_no_received_uids = $this->_buildCacheNameForNoReceivedUids();
		$uids = array(1,2,3,4);
		$coupon_ids = array(33,51,52,53);
		foreach($uids as $uid){
			$this->_redis_instance->lPush($cache_name_for_no_received_uids, $uid);
			
			$cache_name_for_user_coupon_info = $this->_buildCacheNameForCouponInfoByUid($uid);
			$pos = array_rand($coupon_ids, 1);
				
			$x['coupon_id'] = $coupon_ids[$pos];
			$this->_redis_instance->hMset($cache_name_for_user_coupon_info, $x);
		}
		
	}
	
	public function runTigerActivity(){
		foreach ($this->_activity_coupon_ids as $issue_no => $coupon_ids) {
			$this->_runTigerActivityByIssueNo($issue_no);
		}
	}

	private function _runTigerActivityByIssueNo($issue_no=''){
		ApiLog('$issue_no:'.$issue_no, 'oct_acti');
		
		if(!$issue_no){
			return false;
		}
		$cache_name_for_no_received_user_phones = $this->_buildCacheNameForNoReceivedUserPhones($issue_no);
		ApiLog('$cache_name_for_no_received_user_phones:'.$cache_name_for_no_received_user_phones.'==='.$this->_redis_instance->lSize($cache_name_for_no_received_user_phones), 'oct_acti');
		
		if($this->_redis_instance->lSize($cache_name_for_no_received_user_phones)){
			while ($user_phone = $this->_redis_instance->lPop($cache_name_for_no_received_user_phones)) {
				$uid = D('Home/User')->getUserId($user_phone);
				if(!$uid){
					continue;
				}
	
				$cache_name_for_user_coupon_info = $this->_buildCacheNameForCouponInfoByUserPhone($user_phone, $issue_no);
				$coupon_gift_info = $this->_redis_instance->hGetAll($cache_name_for_user_coupon_info);
				ApiLog('$coupon_gift_info:'.print_r($coupon_gift_info,true), 'oct_acti');
	
				if($coupon_gift_info['user_coupon_id']){
					continue;
				}
				if($user_coupon_id = $this->_queryUserCouponIdForActivity($uid, $coupon_gift_info['coupon_id'], $issue_no)){
					ApiLog('$$user_coupon_id:'.print_r($user_coupon_id,true), 'oct_acti');
					$this->_updateCacheForUserCouponInfo($user_phone, $user_coupon_id, $issue_no);
					continue;
				}
				ApiLog('$$uid:'.print_r($user_phone,true), 'oct_acti');
	
				$this->_giveActivityCoupon($user_phone, $uid, $coupon_gift_info['coupon_id'], $issue_no);
			}
		}
	}
	
	private function _queryUserCouponIdForActivity($uid, $coupon_category_id, $issue_no=''){
		if(!$issue_no){
			$activity_coupon_ids = $this->_activity_coupon_ids;
		}else{
			$activity_coupon_ids = $this->_activity_coupon_ids[$issue_no];
            $map['issue_id'] = $issue_no;
		}
		$map['coupon_id'] = array('IN',$activity_coupon_ids);
		$map['uid'] = $uid;
        $map['activity_id'] = $this->_activity_id;
        return D('UserCoupon')->where($map)->getField('user_coupon_id');
	}
	
	private function _getCouponInfoById($coupon_category_id){
		$map['activity_id'] = $this->_activity_id;
		$map['coupon_id'] = $coupon_category_id;
		$activity_coupon_info = D('Coupon')->where($map)->find();
		return $activity_coupon_info;
	}


    private function _buildUserCouponData($uid, $activity_coupon_info, $issue_no=''){
        $user_coupon_data['uid'] = $uid;
		$user_coupon_data['coupon_id'] = $activity_coupon_info['coupon_id'];
		$user_coupon_data['user_coupon_balance'] = $activity_coupon_info['coupon_value'];
		$user_coupon_data['user_coupon_status'] = self::USER_COUPON_STATUS_OF_AVAILABLE;
		$user_coupon_data['user_coupon_amount'] = $activity_coupon_info['coupon_value'];
		$user_coupon_data['coupon_type'] = $activity_coupon_info['coupon_type'];
		$user_coupon_data['user_coupon_desc'] = A('Home/FullReducedCouponConfig')->genUserCouponDesc($activity_coupon_info);
		$user_coupon_data['user_coupon_start_time'] = date("Y-m-d H:i:s");
		$user_coupon_data['user_coupon_create_time'] = date("Y-m-d H:i:s");
		$user_coupon_data['user_coupon_end_time'] = A('Home/FullReducedCouponConfig')->genUserCouponEndTime($activity_coupon_info);
		$user_coupon_data['coupon_min_consume_price'] = $activity_coupon_info['coupon_min_consume_price'];
		$user_coupon_data['coupon_lottery_ids'] = empty($activity_coupon_info['coupon_lottery_ids']) ? '' : $activity_coupon_info['coupon_lottery_ids'];
		$user_coupon_data['play_type'] = $activity_coupon_info['play_type'];
		$user_coupon_data['bet_type'] = $activity_coupon_info['bet_type'];
        $user_coupon_data['coupon_type'] = $activity_coupon_info['coupon_type'];
        $user_coupon_data['activity_id'] = $this->_activity_id;
        $user_coupon_data['issue_id'] = $issue_no;
        return $user_coupon_data;
	}
	
	private function _giveActivityCoupon($user_phone, $uid, $coupon_category_id, $issue_no=''){
		$activity_coupon_info = $this->_getCouponInfoById($coupon_category_id);
		if(empty($activity_coupon_info)){
			return false;
		}
            $user_coupon_data = $this->_buildUserCouponData($uid, $activity_coupon_info ,$issue_no);
            M()->startTrans();
            //test1
            $user_coupon_id = M('UserCoupon')->add($user_coupon_data);
            $log_result = D('Home/UserCouponLog')->addUserCouponLog($uid, $user_coupon_id, $user_coupon_data['user_coupon_balance'], $user_coupon_data['user_coupon_balance'], self::USER_COUPON_LOG_TYPE_OF_GIFT, $uid);
            $increase_result = D('Home/UserAccount')->increaseUserAccountCouponAmount($uid, $user_coupon_data['user_coupon_balance']);

            /*$user_coupon_id = 1;
            $log_result = 1;
            $increase_result = 1;*/

            ApiLog('oct:' . $user_coupon_id . '===' . $log_result . '===' . $increase_result . '===' . $uid, 'oct_acti');

            if ($user_coupon_id && $increase_result && $log_result) {
                ApiLog('oct:' . $user_coupon_id . '===' . $log_result . '===' . $increase_result . '===' . $uid, 'oct_acti');
                M()->commit();
                $this->_updateCacheForUserCouponInfo($user_phone, $user_coupon_id, $issue_no);
                $this->_addToShowUserList($user_phone, $issue_no);
                return true;
            } else {
			ApiLog('failed :user_coupon_data:' . print_r($user_coupon_data, true), 'oct_acti');
			M()->rollback();
			$push_result = $this->_pushBackToNoReceivedList($user_phone, $issue_no);
			if(!$push_result){
				//sms $uid
				ApiLog('failed phone:' . $user_phone.'==='.$coupon_category_id, 'oct_acti_fail');
			}
			return false;
		}
	}
	
	private function _updateCacheForUserCouponInfo($user_phone,$user_coupon_id, $issue_no=''){
		$cache_name_for_user_coupon_info = $this->_buildCacheNameForCouponInfoByUserPhone($user_phone, $issue_no);
		$user_info = D('Home/User')->queryUserInfoByPhone($user_phone);
		$hash_info['user_coupon_id'] = $user_coupon_id;
		return $this->_redis_instance->hMset($cache_name_for_user_coupon_info, $hash_info);
	}

	private function _addToShowUserList($user_phone, $issue_no = ''){
		$cache_name_for_show_user_list = $this->_buildCacheNameForShowUserList($issue_no);
		return $this->_redis_instance->lPush($cache_name_for_show_user_list, $user_phone);
	}
	
	private function _pushBackToNoReceivedList($user_phone, $issue_no = ''){
		$cache_name_for_no_received_user_phones = $this->_buildCacheNameForNoReceivedUserPhones($issue_no);
		return $this->_redis_instance->lPush($cache_name_for_no_received_user_phones, $user_phone);
	}
	
	private function _buildCacheNameForNoReceivedUserPhones($issue_no = ''){
		$cache_name = $this->_cache_name_prefix;
		if ($issue_no) {
			$cache_name .=  $this->_activity_id.'-'.$issue_no . ':';
		}
		$cache_name .= 'user:coupon_no_received';
		return $cache_name;
	}
	
	private function _buildCacheNameForCouponInfoByUserPhone($user_phone, $issue_no = ''){
		$cache_name = $this->_cache_name_prefix;
		if ($issue_no) {
			$cache_name .= $this->_activity_id.'-'.$issue_no . ':';
		}
		$cache_name .= 'coupon_info:' . $user_phone;
		return $cache_name;
	}

	private function _buildCacheNameForShowUserList($issue_no = ''){
		$cache_name = $this->_cache_name_prefix;
		if ($issue_no) {
			$cache_name .= $this->_activity_id.'-'.$issue_no . ':';
		}
		$cache_name .= 'show_user_list';
		return $cache_name;
	}
	
	private function _getRedisInstance(){
		$redis_instance = new \Redis();
		$is_connected = $redis_instance->connect(C('ALI_REDIS_HOST'), C('ALI_REDIS_PORT'));
		if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
			$redis_instance->auth('Mg3ZHemsH04cVxon');
		}
		ApiLog('$$is_connected:' . get_cfg_var('PROJECT_RUN_MODE') . '===' . $is_connected, 'oct_acti');
		
		if (!$is_connected) {
			$redis_instance = false;
			// sms
		}
		ApiLog('$redis_instance:' . print_r($redis_instance, true), 'oct_acti');
		
		$redis_instance->select(2);
		return $redis_instance;
	}
	
}
