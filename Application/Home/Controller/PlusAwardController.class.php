<?php
namespace Home\Controller;
use Home\Controller\GlobalController;

class PlusAwardController extends GlobalController {

	private function _queryPlusAwardConfigListByOrder($order_info){
		$lottery_id = $order_info['lottery_id'];
		$issue_id = $order_info['issue_id'];
		$create_time = $order_info['order_create_time'];
		$map['lottery_id'] = $lottery_id;
		$map['pac_start_time'] = array('elt',$create_time);
		$map['pac_end_time'] = array('egt',$create_time);
		if(isJCLottery($lottery_id)){
			$plus_award_config_list = D('PlusAwardConfig')->where($map)->select();
		}else{
			$map['issue_id'] = $issue_id;
		}
		ApiLog('config list:'.print_r($plus_award_config_list,true), 'notice');
		return $plus_award_config_list;
	}
	
	private function _checkNeedToPlusAward($order_info, $plus_award_config_info){
		$order_is_prized = $order_info['order_winnings_status']==C("ORDER_WINNINGS_STATUS.YES");
		$order_prize_is_distribute = $order_info['order_distribute_status'] == C("ORDER_DISTRIBUTE_STATUS.PRIZED");
		$order_is_plus_awarded = $order_info['order_plus_award_status'] > 0 ;
		ApiLog('order:'.$order_info['order_distribute_status'].'----'.C("ORDER_DISTRIBUTE_STATUS.PRIZED").'==??=='.$order_is_prized.'==='.$order_prize_is_distribute.'==='.$order_is_plus_awarded, 'notice');
		
		if(!$order_is_prized || !$order_prize_is_distribute || $order_is_plus_awarded){
			return false;
		}
		$create_time = $order_info['order_create_time'];
		$lottery_is_match = $plus_award_config_info['lottery_id'] == $order_info['lottery_id'];
		$time_is_match = ($create_time >= $plus_award_config_info['pac_start_time']) && ($create_time <= $plus_award_config_info['pac_end_time']);
		$play_type_is_match = true;
		if($plus_award_config_info['play_type']){
			$play_type_is_match = $order_info['play_type']==$plus_award_config_info['play_type'];
		}
		$bet_type_is_match = true;
		if($plus_award_config_info['bet_type']){
			$bet_type_is_match = $order_info['bet_type']==$plus_award_config_info['bet_type'];
		}
		ApiLog('time:'.$time_is_match.'----'.($lottery_is_match && $time_is_match && $play_type_is_match && $bet_type_is_match), 'notice');
		
		if($plus_award_config_info['pas_id']){
			ApiLog('pas_id:'.$plus_award_config_info['pas_id'], 'notice');
		}
		
		return $lottery_is_match && $time_is_match && $play_type_is_match && $bet_type_is_match;
	}
	
	public function doOrderPlusAward($order_info){
	    if(!$this->_isAddPlusAward($order_info['uid'])){
            ApiLog('uid:'.$order_info['uid'].'不是老虎用户','no_tiger_user3');
            return false;
        }
        if($order_info['order_type'] == ORDER_TYPE_OF_COBET){
	        return false;
        }
		$plus_award_config_list = $this->_queryPlusAwardConfigListByOrder($order_info);
		
		foreach($plus_award_config_list as $plus_award_config_info){
			$need_to_plus_award = $this->_checkNeedToPlusAward($order_info,$plus_award_config_info);
			if(!$need_to_plus_award){
				continue;
			}
			$this->_calcOrderPlusAwardAndCoupon($order_info, $plus_award_config_info);
		}
		
	}

    private function _isAddPlusAward($uid){
        $user_info = D('User')->getUserInfo($uid);
        $app_id = getRegAppId($user_info);
        if($app_id != C('APP_ID_LIST.TIGER')) {
            return false;
        }
        return true;
    }
	
	private function _queryPlusAwardSchemaDetail($pas_id){
		$map['pas_id'] = $pas_id;
		$order_by = 'pasd_weight ASC';
		return D('PlusAwardSchemaDetail')->where($map)->order($order_by)->select();
	}
	
	private function _buildPlusAwardCouponValue($order_winning_bonus, $plus_award_config_info){
		if($plus_award_config_info['pas_id']){
			$schema_detail_list = $this->_queryPlusAwardSchemaDetail($plus_award_config_info['pas_id']);
			foreach($schema_detail_list as $schema_detail_info){
				$include_type = $schema_detail_info['pasd_include_type'];
				$stop_amount = floatval($schema_detail_info['pasd_stop_amount']);
				if($include_type==1){
					$in_start_range = $order_winning_bonus >= $schema_detail_info['pasd_start_amount'];
					if($stop_amount){
						$in_stop_range = $order_winning_bonus < $schema_detail_info['pasd_stop_amount'];
					}else{
						$in_stop_range = true;
					}
				}elseif($include_type==0){
					$in_start_range = $order_winning_bonus > $schema_detail_info['pasd_start_amount'];
					if($stop_amount){
						$in_stop_range = $order_winning_bonus <= $schema_detail_info['pasd_stop_amount'];
					}else{
						$in_stop_range = true;
					}
				}
				ApiLog('in_stop_range:'.$in_start_range.'==='.$in_stop_range, 'notice');
				
				if(!$in_start_range || !$in_stop_range){
					continue;
				}
				
				$award_type = $schema_detail_info['pasd_type'];
				if($award_type==1){
					return $schema_detail_info['pasd_money'];
				}elseif($award_type==0){
					if($schema_detail_info['pasd_rate']){
						$plus_coupon_value = number_format($order_winning_bonus*$schema_detail_info['pasd_rate'], 2, '.', '');
						return $plus_coupon_value;
					}
				}
			}
			return 0;
		}else{
			$plus_coupon_value = number_format($order_winning_bonus*$plus_award_config_info['pac_rate'], 2, '.', '');
			return $plus_coupon_value;
		}
	}
	
	private function _calcOrderPlusAwardAndCoupon($order_info, $plus_award_config_info){
		$plus_coupon_value = $this->_buildPlusAwardCouponValue($order_info['order_winnings_bonus'], $plus_award_config_info);
		if(!$plus_coupon_value){
			ApiLog('$plus_coupon_value:'.$plus_coupon_value, 'notice');
			return false;
		}
		$map['coupon_is_plus_award'] = 1;
		$plus_award_coupon_info = D('Coupon')->where($map)->find();
		if(empty($plus_award_coupon_info)){
			return false;
		}
		$user_coupon_data['uid'] = $order_info['uid'];
		$user_coupon_data['coupon_id'] = $plus_award_coupon_info['coupon_id'];
		$user_coupon_data['user_coupon_balance'] = $plus_coupon_value;
		$user_coupon_data['user_coupon_status'] = C('USER_COUPON_STATUS.AVAILABLE');
		$user_coupon_data['user_coupon_amount'] = $plus_coupon_value;
		$user_coupon_data['user_coupon_desc'] = $plus_award_coupon_info['coupon_name'].$plus_coupon_value.'元';
		$user_coupon_data['user_coupon_start_time'] = date("Y-m-d H:i:s");
		$user_coupon_data['user_coupon_create_time'] = date("Y-m-d H:i:s");
		$user_coupon_data['user_coupon_end_time'] = '2099-12-31 23:59:59';
		M()->startTrans();
		$userCouponId = D('UserCoupon')->add($user_coupon_data);
		
		$order_data['order_plus_award_status'] = 1;
		$order_data['order_plus_award_amount'] = $plus_coupon_value;
		$order_map['order_id'] = $order_info['order_id'];
		$order_result = D('Order')->where($order_map)->save($order_data);

		$consumeCoupon = D('UserAccount')->updateBuyCouponStatics($order_info['uid'], $user_coupon_data['user_coupon_balance']);
		$log_result = D('UserCouponLog')->addUserCouponLog($order_info['uid'], $userCouponId, $user_coupon_data['user_coupon_balance'], $user_coupon_data['user_coupon_balance'], C('USER_ACCOUNT_LOG_TYPE.PLUS_COUPON_REWARD'), $order_info['uid']);
		ApiLog('puls award:'.$userCouponId.'==='.$order_result.'==='.$consumeCoupon.'==='.$log_result,'notice');
		
		if ($userCouponId && $order_result && $consumeCoupon && $log_result) {
			ApiLog('puls award commit','notice');
			M()->commit();
		}else{
			ApiLog('puls award rollback','notice');
			M()->rollback();
		}
	}
	
}