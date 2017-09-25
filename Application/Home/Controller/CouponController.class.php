<?php
namespace Home\Controller;
use Home\Controller\GlobalController;

class CouponController extends GlobalController {
    
    public function buyCoupon($api) {
        $couponInfo = D('Coupon')->getCouponInfo($api->coupon_id);
        \AppException::ifNoExistThrowException($couponInfo, C('ERROR_CODE.COUPON_NO_EXIST'));
        $couponValid = $this->_verifyCouponValidForSale($couponInfo);
        \AppException::ifNoExistThrowException($couponValid, C('ERROR_CODE.COUPON_INVALID'));
        
        $userInfo = $this->getAvailableUser($api->session);
        $uid = $userInfo['uid'];
        $userAccount = D('UserAccount')->getUserAccount($uid);
        $remain = $userAccount['user_account_balance'] - $couponInfo['coupon_price'];
        
        if($remain >= 0) {
            $this->_buyUserCoupon($uid, $couponInfo, $couponInfo['coupon_price']);
        } else {
            return array(   'result' => array( 'money' => abs($remain), ),
                            'code'   => C('ERROR_CODE.INSUFFICIENT_FUND'));
        }
        
        return array(   'result' => '',
	                    'code'   => C('ERROR_CODE.SUCCESS'));
    }
    
    private function _isChannelCouponCode($coupon_code){
    	$coupon_code = strtoupper($coupon_code);
    	$coupon_id = D('ChannelCoupon')->queryInfoByCode($coupon_code);
    	return $coupon_id;
    }
    
    public function exchangeCoupon($api) {
    	$is_channel_coupon = $this->_isChannelCouponCode($api->coupon_code);
    	if($is_channel_coupon){
    		return A('ChannelCoupon')->exchange($api);
    	}else{
    		$userInfo = $this->getAvailableUser($api->session);
    		$uid = $userInfo['uid'];

			$id = D('CouponExchange')->getCouponExchangeId($api->coupon_code);
    		\AppException::ifNoExistThrowException($id, C('ERROR_CODE.COUPON_EXCHANGE_NO_EXIST'));
    		
    		$verify = D('CouponExchange')->checkExchangeValid($id);
    		\AppException::ifNoExistThrowException($verify, C('ERROR_CODE.COUPON_EXCHANGE_INVALID'));

            $verify_exchange_limit_time = $this->_verifyCouponExchangeLimitTime($id,$uid);
            if(!$verify_exchange_limit_time){
                \AppException::throwException(C('ERROR_CODE.COUPON_EXCHANGE_LIMIT_TIMES'));
            }
    		
    		$couponExchange = D('CouponExchange')->getCouponExchangeInfo($id);
    		$coupon_info = D('Coupon')->getCouponInfo($couponExchange['coupon_id']);
    		
    		$couponValid = $this->_verifyCouponValidForExchange($coupon_info);
    		\AppException::ifNoExistThrowException($couponValid, C('ERROR_CODE.COUPON_INVALID'));


			if(in_array($couponExchange['coupon_id'],array(84,85,86,87,88,89,90,94))){
				if(!empty($userInfo['extra_channel_id']) && $userInfo['channel_type'] == 2){
					\AppException::throwException(C('ERROR_CODE.THIS_COUPON_IS_EXCHANGEED'));
				}else{
					$this->_exchangeRcyktCoupon($uid, $coupon_info, $id);
				}
			}else{
				$this->_exchangeCoupon($uid, $coupon_info, $id);
			}

    	}
    	return array(   'result' => '',
    			'code'   => C('ERROR_CODE.SUCCESS'));
    }

    private function _verifyCouponExchangeLimitTime($id,$uid){
        $coupon_info = D('CouponExchange')->getExchangeCouponInfo($id);
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

	private function _exchangeRcyktCoupon($uid, $coupon_info, $couponExchangeId) {
		try {
			$activity_id = 0;
			$userCouponId = D('UserCoupon')->addUserCoupon($uid, $coupon_info, C('USER_COUPON_LOG_TYPE.EXCHANGE') ,C('USER_COUPON_STATUS.AVAILABLE'), $couponExchangeId, $activity_id);
			\AppException::ifNoExistThrowException($userCouponId, C('ERROR_CODE.DATABASE_ERROR'));

			$exchangeResult = D('CouponExchange')->saveExchangeStatus($couponExchangeId, $uid);
			\AppException::ifNoExistThrowException($exchangeResult, C('ERROR_CODE.DATABASE_ERROR'));

			$save_data['channel_type'] = 2;
			$save_data['extra_channel_id'] = 1;
			M('User')->where(array('uid'=>$uid))->save($save_data);

			M()->commit();
		} catch (\Think\Exception $e) {
			M()->rollback();
			throw new \Think\Exception($e->getMessage(), $e->getCode());
		}
	}

    
    private function _exchangeCoupon($uid, $coupon_info, $couponExchangeId) {
    	try {
    		$activity_id = 0;
    		$userCouponId = D('UserCoupon')->addUserCoupon($uid, $coupon_info, C('USER_COUPON_LOG_TYPE.EXCHANGE') ,C('USER_COUPON_STATUS.AVAILABLE'), $couponExchangeId, $activity_id);
    		\AppException::ifNoExistThrowException($userCouponId, C('ERROR_CODE.DATABASE_ERROR'));
    
    		$exchangeResult = D('CouponExchange')->saveExchangeStatus($couponExchangeId, $uid);
    		\AppException::ifNoExistThrowException($exchangeResult, C('ERROR_CODE.DATABASE_ERROR'));
    		M()->commit();
    	} catch (\Think\Exception $e) {
    		M()->rollback();
    		throw new \Think\Exception($e->getMessage(), $e->getCode());
    	}
    }
    
    public function getCouponList($api) {
        $couponList = D('Coupon')->getCouponListForSale($api->offset, $api->limit);
        \AppException::ifExistThrowException($couponList===false, C('ERROR_CODE.DATABASE_ERROR'));
        
        return array(   'result' => array('list'=>$couponList),
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }
    
    
    public function getUserCouponList($api) {
        $userInfo = $this->getAvailableUser($api->session);
        $uid = $userInfo['uid'];
		$userCouponList = D('UserCouponView')->getUserCouponList($uid, $api->type, $api->offset, $api->limit, $api->sort,$api->sdk_version,$api->os);
        $userCouponList = ($userCouponList ? $userCouponList : array());
        return array(   'result' => array('list'=>$userCouponList),
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }

	public function getUserCouponListForNativePay($api) {
		$userInfo = $this->getAvailableUser($api->session);
		$uid = $userInfo['uid'];
		$userCouponList = D('UserCouponView')->getUserCouponListForNativePay($uid);
		$userCouponList = $this->_filterCouponList($userCouponList,$api->lottery_id,$api->total_amount,$api->suite_id,$api->is_copurchase);

		$userCouponList = D('UserCouponView')->formatCouponListForNativePay($userCouponList);

		$userCouponList = ($userCouponList ? $userCouponList : array());
		return array(   'result' => array('list'=>$userCouponList),
						'code'   => C('ERROR_CODE.SUCCESS'));
	}

	private function _filterCouponList($user_coupon_list,$lottery_id,$order_amount,$suite_id = 0,$is_cobet = 0){
		foreach($user_coupon_list as $key => $user_coupon_info){
		    if($suite_id){
		        if($user_coupon_info['user_coupon_type'] == 2){
                    unset($user_coupon_list[$key]);
                    continue;
                }
            }
            if($is_cobet){
                if($user_coupon_info['user_coupon_type'] == 2){
                    unset($user_coupon_list[$key]);
                    continue;
                }
                if(!$user_coupon_info['coupon_is_cobet']){
                    unset($user_coupon_list[$key]);
                    continue;
                }
            }

			if(!empty($user_coupon_info['coupon_lottery_ids'])){
				$lottery_list = explode(',',$user_coupon_info['coupon_lottery_ids']);
				if(!in_array($lottery_id,$lottery_list)){
					unset($user_coupon_list[$key]);
					continue;
				}
			}

            if( bccomp($order_amount, $user_coupon_info['coupon_min_consume_price']) < 0){
				unset($user_coupon_list[$key]);
				continue;
			}
		}
		return $user_coupon_list;
	}

	public function calcUserCouponNumber($api){
		$userInfo = $this->getAvailableUser($api->session);
		$uid = $userInfo['uid'];
		$type = C('USER_OWEN_COUPON.WAIT');
		$condition = array(
				'user_coupon_status' => C('USER_COUPON_STATUS.AVAILABLE'),
				'user_coupon_balance' => array(
						'gt',
						0 
				),
				'user_coupon_start_time' => array(
						'gt',
						getCurrentTime() 
				) 
		);
		
		$condition['uid'] = $uid;
		$result['waiting_coupon_number'] = D('UserCoupon')->where($condition)->count();
		
		$type =C('USER_OWEN_COUPON.AVAILABLE');
		$condition = array(
				'user_coupon_status' => C('USER_COUPON_STATUS.AVAILABLE'),
				'user_coupon_balance' => array('gt', 0),
				'user_coupon_start_time' => array('lt', getCurrentTime()),
				'user_coupon_end_time' => array('gt', getCurrentTime()),
		);
		$condition['uid'] = $uid;
		$result['available_coupon_number'] = D('UserCoupon')->where($condition)->count();
		
		return array(   'result' => $result,
                        'code'   => C('ERROR_CODE.SUCCESS'));
	}

    private function _buyUserCoupon($uid, $coupon_info, $couponPrice) {
        try {
            M()->startTrans();
            $deductResult = D('UserAccount')->deductMoney($uid, $couponPrice, $coupon_info['coupon_id'], C('USER_ACCOUNT_LOG_TYPE.BUY_COUPON'));
            \AppException::ifExistThrowException($deductResult===false, C('ERROR_CODE.DATABASE_ERROR'));

            $exchange_id = 0;
			$activity_id = 0;
            $userCouponId = D('UserCoupon')->addUserCoupon($uid, $coupon_info, C('USER_COUPON_LOG_TYPE.BUY'), C('USER_COUPON_STATUS.AVAILABLE'), $exchange_id, $activity_id);
            \AppException::ifNoExistThrowException($userCouponId, C('ERROR_CODE.DATABASE_ERROR'));
            M()->commit();
        } catch (\Think\Exception $e) {
            M()->rollback();
            throw new \Think\Exception($e->getMessage(), $e->getCode());
        }
    }
    
    
    private function _verifyCouponValidForExchange($coupon_info) {  // 验证兑换
        if($coupon_info['coupon_status'] != C('COUPON_STATUS.AVAILABLE')){
        	return false;
        }
        if($coupon_info['coupon_valid_date_type'] == COUPON_VALID_DATE_OF_DATE_RANGE){
        	if(time()< strtotime($coupon_info['coupon_sell_start_time']) || time()>strtotime($coupon_info['coupon_sell_end_time'])){
        		return false;
        	}
        }
        return true;
    }
    
    private function _verifyCouponValidForSale($coupon_info) {  
    	$available = $coupon_info['coupon_status'] == C('COUPON_STATUS.AVAILABLE') ;
    	$is_for_sale = $coupon_info['coupon_is_sell'] == COUPON_IS_FOR_SALE ;
//     	$valid_date_forever = $coupon_info['coupon_valid_date_type'] == COUPON_VALID_DATE_OF_FOREVER ;
//     	return $is_for_sale && $valid_date_forever && $available;
    	return $is_for_sale && $available;
    }
    
}

