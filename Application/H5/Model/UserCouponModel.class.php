<?php 
namespace H5\Model;
use Think\Model;

class UserCouponModel extends Model {
    
    public function owenedByUser($uid, $userCouponId) {
        $userCouponInfo = $this->getUserCouponInfo($userCouponId);
        $ownerId = $userCouponInfo['uid'];
        $checkOwner = ($uid == $ownerId);
        $startTime = strtotime($userCouponInfo['user_coupon_start_time']);
        $endTime = strtotime($userCouponInfo['user_coupon_end_time']);
        $checkLifetime = ( (time()-$startTime>=0) && ($endTime-time()>=0) );
        $checkStatus = ( $userCouponInfo['user_coupon_status'] == C('USER_COUPON_STATUS.AVAILABLE') );
        return ( $checkOwner && $checkLifetime && $checkStatus );
    }
    
    public function getUserCouponInfoByUid($uid, $userCouponId){
    	$condition = array(
    			'user_coupon_id' => $userCouponId,
    			'uid' => $uid
    	);
    	return $this->where($condition)->find();
    }
    
    public function getUserCouponInfo($userCouponId){
		$condition = array(
				'user_coupon_id' => $userCouponId 
		);
		return $this->where($condition)->find();
	}
    
    public function getCouponBalance($userCouponId) {
    	if(!$userCouponId){
    		return 0;
    	}
        $condition = array('user_coupon_id'=>$userCouponId);
        return $this->where($condition)
                    ->getField('user_coupon_balance');
    }

	public function sumUserCouponBalance($uid){
		$map['uid'] = $uid;
		$map['user_coupon_status'] = C('USER_COUPON_STATUS.AVAILABLE');
// 		$map['user_coupon_start_time'] = array(
// 				'ELT',
// 				getCurrentTime() 
// 		);
		$map['user_coupon_end_time'] = array(
				'EGT',
				getCurrentTime() 
		);
		return $this->where($map)->sum('user_coupon_balance');
	}
    
    
	public function addUserCoupon($uid, $coupon_info, $log_type, $status, $couponExchangeId = 0, $activityId = 0, $coupon_desc = ''){
		$user_coupon_data['uid'] = $uid;
		$user_coupon_data['coupon_id'] = $coupon_info['coupon_id'];
		$user_coupon_data['user_coupon_balance'] = $coupon_info['coupon_value'];
		$user_coupon_data['user_coupon_amount'] = $coupon_info['coupon_value'];
		$user_coupon_data['user_coupon_status'] = $status;
		$user_coupon_data['activity_id'] = $activityId;
		$user_coupon_data['user_coupon_create_time'] = getCurrentTime();
		
		$user_coupon_data['ce_id'] = $couponExchangeId;
		$coupon_valid_date_type = $coupon_info['coupon_valid_date_type'];
		if ($coupon_valid_date_type==COUPON_VALID_DATE_OF_FOREVER){
			$start_time = getCurrentTime();
			$end_time = COUPON_DATE_FOREVER;
		}else if($coupon_valid_date_type==COUPON_VALID_DATE_OF_DATE_RANGE){
			$start_time = $coupon_info['coupon_sell_start_time'];
			$end_time = $coupon_info['coupon_sell_end_time'];
		}else if($coupon_valid_date_type==COUPON_VALID_DATE_OF_DAY_NUMBER){
			$start_time = getCurrentTime();
			$end_time = date('Y-m-d H:i:s', time() + $coupon_info['coupon_duration_time']);
		}else{
			return false;
		}
		$user_coupon_data['user_coupon_start_time'] = $start_time;
		$user_coupon_data['user_coupon_end_time'] = $end_time;
		$user_coupon_data['user_coupon_desc'] = empty($coupon_desc) ? $this->_buildUserCouponDesc($log_type, $coupon_info) : $coupon_desc;
        $user_coupon_data['coupon_min_consume_price'] = $coupon_info['coupon_min_consume_price'];
        $user_coupon_data['coupon_lottery_ids'] = $coupon_info['coupon_lottery_ids'];
        $user_coupon_data['play_type'] = $coupon_info['play_type'];
        $user_coupon_data['bet_type'] = $coupon_info['bet_type'];
        $user_coupon_data['coupon_type'] = $coupon_info['coupon_type'];
		$userCouponId = $this->add($user_coupon_data);
		$consumeCoupon = D('UserAccount')->updateBuyCouponStatics($uid, $coupon_info['coupon_value']);
		$log_result = D('UserCouponLog')->addUserCouponLog($uid, $userCouponId, $coupon_info['coupon_value'], $coupon_info['coupon_value'], $log_type, $uid);
		if ($userCouponId && $consumeCoupon && $log_result) {
			return $userCouponId;
		} else {
			return false;
		}
	}
	
	private function _buildUserCouponDesc($log_type,$coupon_info){
		$type_desc = C('USER_COUPON_LOG_TYPE_DESC.'.$log_type);
		return $type_desc.':'.$coupon_info['coupon_name'];
	}
    
    public function deductCoupon($uid, $userCouponId, $money) {
        $couponBalance = $this->getCouponBalance($userCouponId);
        
        if($couponBalance>=$money) {
            $deduct = $money;
        } else {
            $deduct = $couponBalance;
        }
        
        $condition = array('user_coupon_id'=>$userCouponId);
        $condition['user_coupon_balance'] = array('egt',$deduct);
        
        if($deduct>0) {
            $result = $this->where($condition)
                           ->setDec('user_coupon_balance', $deduct);
            $consume = D('UserAccount')->updateConsumptionCouponStatics($uid, $deduct);
            $log_result = D('UserCouponLog')->addUserCouponLog($uid, $userCouponId, $deduct, $couponBalance-$deduct, C('USER_COUPON_LOG_TYPE.USE'), $uid);
        } else {
            return 0;
        }
        
        if($result && $consume && $log_result) {
            return $deduct;
        } else {
            return false;
        }
    }
    
    
    public function increaseCoupon($userCouponId, $money) {
        $condition = array('user_coupon_id'=>$userCouponId);
        $result = $this ->where($condition)
                        ->setInc('user_coupon_balance', $money);
        
        if($result) {
            return $money;
        } else {
            return false;
        }
    }
    
    public function refundCoupon($userCouponId, $money){
    	return $this->increaseCoupon($userCouponId, $money);
    }
    
    
    public function saveCouponExchangeId($userCouponId, $exchangeId) {
        $condition = array('user_coupon_id'=>$userCouponId);
        $data = array('ce_id'=>$exchangeId);
        return $this->where($condition)
                    ->save($data);
    }
    
    public function getMaxCouponBalanceInfo($uid){
    	$map['uid'] = $uid;
    	$map['user_coupon_status'] = C('USER_COUPON_STATUS.AVAILABLE');
    	$map['user_coupon_start_time'] = array('lt', getCurrentTime());
    	$map['user_coupon_end_time'] = array('gt', getCurrentTime());
    	$order_by = 'user_coupon_balance DESC';
    	return $this->where($map)->order($order_by)->find();
    }

	public function getAvailableCouponList($uid){
		$map['uid'] = $uid;
		$map['user_coupon_status'] = C('USER_COUPON_STATUS.AVAILABLE');
		$map['user_coupon_start_time'] = array('lt', getCurrentTime());
		$map['user_coupon_end_time'] = array('gt', getCurrentTime());
		$order_by = 'user_coupon_balance DESC';
		return $this->where($map)->order($order_by)->select();
	}

}


