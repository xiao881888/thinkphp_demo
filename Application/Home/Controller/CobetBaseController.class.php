<?php

namespace Home\Controller;

use Home\Controller\GlobalController;
use Home\Util\Factory;

class CobetBaseController extends GlobalController{
	const JUMP_TYPE_FOR_APP = 0;
	const JUMP_TYPE_FOR_ORDER = 1;
	const JUMP_TYPE_FOR_BET = 2;
	const JUMP_TYPE_FOR_RECHARGE = 3;
	private $_request_params = null;
	private $_session_code = null;
	private $_user_info = null;
	private $_uid = null;

	public function __construct(){
		import ( '@.Util.AppException' );
		parent::__construct();
		$this->_msg_map = C('WEB_PAY_MESSAGE');
	}
		
	// web支付的重写这个方法
	protected function _throwExcepiton($error_code, $error_msg = ''){
		\AppException::throwException($error_code, $error_msg);
	}

	protected function buildEncryptPayUrl($uid, $params, $raw_json_params){
		$url_params['t'] = $this->_genPayUniqueCode($uid, $params, $raw_json_params);
		$url_params['s'] = $params->session;
		$url = U('WB/index', $url_params, true ,true);
	
		if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
			$url = 'http://'.$_SERVER['HTTP_HOST'].'/' . U('WB/index', $url_params);
		}elseif(get_cfg_var('PROJECT_RUN_MODE') == 'TEST'){
			$url = 'http://test.phone.api.tigercai.com/' . U('WB/index', $url_params);
		}else {
			$url = 'http://192.168.1.171:81/index.php?s=/Home/' . U('WB/index', $url_params);
		}
		return $url;
	}
	
	private function _genPayUniqueCode($uid, $params, $raw_json_params){
		$pay_code = $uid.md5($params->session . uniqid());
		$key = 'cobet:'.$pay_code;
		$redis_instance = Factory::createRedisObj();
		$result = $redis_instance->setex($key, 3600, $raw_json_params);
		if(!$result){
			$redis_instance->setex($key, 3600, $raw_json_params);
		}
		return $pay_code;
	}
	
	protected function buildResponseForPayScheme($orderId, $orderSku, $amount, $code){
		if($orderId){
			$result['order_id'] = $orderId;
		}
		if($orderSku){
			$result['order_sku'] = $orderSku;
		}
		$result['money'] = $amount;
		return array(
				'result' => $result,
				'code' => $code 
		);
	}

	protected function getRemainMoney($uid, $couponId,$lottery_id,$totalPayMoney,$orderTotalAmount,$suite_id = 0) {
		$couponBalance 	= $this->_getSelectedCouponBalance($uid, $couponId,$lottery_id,$orderTotalAmount,$suite_id);
		$userBalance 	= D('UserAccount')->getUserBalance($uid);
		ApiLog('get reamin:'.$couponBalance.'===='.$userBalance, 'cobetpay');
		return ($userBalance + $couponBalance - $totalPayMoney);
	}

    protected function getRemainMoneyForWebPay($uid, $lottery_id,$total_amount,$orderTotalAmount,$suite_id = 0) {
        $user_balance 	= D('UserAccount')->getUserBalance($uid);
        $user_coupon_list = D('UserCoupon')->getAvailableCouponList($uid);
        $max_coupon_balance_info = $this->_filterCouponList($user_coupon_list,$lottery_id,$orderTotalAmount,$suite_id);
        $lack_money = 0;
        if($max_coupon_balance_info){
            if(($max_coupon_balance_info['user_coupon_balance']+$user_balance)<$total_amount){
                $has_amount = bcadd($max_coupon_balance_info['user_coupon_balance'],$user_balance,2);
                $lack_money = bcsub($total_amount,$has_amount,2);
            }
        }else{
            if($user_balance<$total_amount){
                $lack_money = bcsub($total_amount,$user_balance,2);
            }
        }
        ApiLog("max_coupon lack:".$lack_money.'===='.$user_balance.'===='.$total_amount.'===='.print_r($max_coupon_balance_info,true),'cobetpay');
        return $lack_money;
    }

    private function _filterCouponList($user_coupon_list,$lottery_id,$order_amount,$suite_id = 0){
        $max_coupon_balance_info = array();
        foreach($user_coupon_list as $key => $user_coupon_info){
            if($user_coupon_info['coupon_type'] == 2){
                unset($user_coupon_list[$key]);
                continue;
            }
            if(!empty($user_coupon_info['coupon_lottery_ids'])){
                $lottery_list = explode(',',$user_coupon_info['coupon_lottery_ids']);
                if(!in_array($lottery_id,$lottery_list)){
                    unset($user_coupon_list[$key]);
                    continue;
                }
            }
            if(bccomp($order_amount, $user_coupon_info['coupon_min_consume_price']) < 0){
                unset($user_coupon_list[$key]);
                continue;
            }
        }
        $user_coupon_list = array_values($user_coupon_list);
        $max_coupon_balance = 0;
        $curr_coupon_balance = 0;
        foreach($user_coupon_list as $key => $user_coupon_info){
            $curr_coupon_balance = $user_coupon_info['user_coupon_balance'];
            if($curr_coupon_balance > $max_coupon_balance){
                $max_coupon_balance = $curr_coupon_balance;
                $max_coupon_balance_info = $user_coupon_info;
            }
        }
        return $max_coupon_balance_info;

    }
	
	protected function verifyCobetParams($params, $user_info){
		if ($params->total_amount <= 0) {
			return false;
		}
		ApiLog('sssaaaaaaaa:'.bccomp($params->total_amount, $params->unit_amount * intval($params->total_unit)), 'cobet');
		if (bccomp($params->total_amount, $params->unit_amount * intval($params->total_unit)) != 0) {
			return false;
		}
		ApiLog('sssaaa:'.bccomp($params->pay_total_amount, $params->unit_amount * intval($params->ensure+$params->subscribe)), 'cobet');
		ApiLog('sssaaa:'.$params->pay_total_amount.'===='.$params->unit_amount * intval($params->ensure+$params->subscribe), 'cobet');
		
		if (bccomp($params->pay_total_amount, $params->unit_amount * intval($params->ensure+$params->subscribe)) != 0) {
			return false;
		}

		$over_time_status = $this->_checkCobetOverTime($params);
		if(!$over_time_status){
            $this->_throwExcepiton(C('ERROR_CODE.OUT_OF_ISSUE_TIME'));
        }
	
		$lottery_info = D('Lottery')->getLotteryInfo($params->lottery_id);
		if (empty($lottery_info) || !$lottery_info['lottery_status']) {
			return false;
		}
	
		if ($params->commission > 1) {
			return false;
		}
	
		if ($params->subscribe < 0 || $params->ensure < 0) {
			return false;
		}
	
		if ($params->subscribe > $params->total_unit || $params->ensure > $params->total_unit) {
			return false;
		}
		ApiLog('sss:'.isset($params->type), 'cobet');
		if (!isset($params->type)){
			return false;
		}
		
		$coupon_is_useable = $this->_checkUserCouponUseable($user_info['uid'], $params->user_coupon_id);
		ApiLog('$coupon_is_useable:'.$coupon_is_useable, 'cobet');
		
		if (!$coupon_is_useable) {
			return false;
		}
		$lottery_obj_instance = $this->_getLotteryInstance($params->lottery_id);
		$order_verified_params = $lottery_obj_instance->verifyParams($params, $user_info);
		if (!$order_verified_params) {
			return false;
		}
		$verified_params['lottery_id'] = $params->lottery_id;
		$verified_params['total_amount'] = $params->total_amount;
		$verified_params['unit_amount'] = $params->unit_amount;
		$verified_params['total_unit'] = $params->total_unit;
		$verified_params['scheme_total_unit'] = $params->total_unit;
		$verified_params['scheme_identity'] = $order_verified_params['order_identity'];
		$verified_params['commission'] = $params->commission;
		$verified_params['subscribe'] = $params->subscribe;
		$verified_params['ensure'] = $params->ensure;
		$verified_params['scheme_bought_unit'] = $params->subscribe;
		$verified_params['scheme_guarantee_unit'] = $params->ensure;
		$verified_params['scheme_amount_per_unit'] = $params->unit_amount;
		$verified_params['type'] = $params->type;
		$verified_params['user_coupon_id'] = $params->user_coupon_id;
		$verified_params['issue_id'] = $order_verified_params['issue_id'];
		$verified_params['order_info'] = json_encode($params);
		return $verified_params;
	}

    private function _checkJoinOverTime($lottery_id,$scheme_issue_id){
        if (isJc($lottery_id)) {
            $end_time = D('JcSchedule')->getEndTime($scheme_issue_id);
        } else {
            $end_time = D('Issue')->getEndTime($scheme_issue_id);
        }
        $lottery_info = D('Lottery')->getLotteryInfo($lottery_id);

        $scheme_end_timestamp = strtotime($end_time) - $lottery_info['lottery_ahead_endtime'] - C('COBET_SCHEME_AHEAD_END_TIME');
        if(time() > $scheme_end_timestamp){
            $this->_throwExcepiton(C('ERROR_CODE.OUT_OF_ISSUE_TIME'));
        }
        return true;

    }

	private function _checkCobetOverTime($param){
        $lottery_info = D('Lottery')->getLotteryInfo($param->lottery_id);
	    if(isJc($param->lottery_id)){
	        $scheme_orders = (array)$param->schedule_orders;
            foreach($scheme_orders as $scheme_order){
                $scheme_order = (array)$scheme_order;
                $schedule_ids[] = $scheme_order['schedule_id'];
            }
            $schedule_infos 	= D('JcSchedule')->getScheduleIssueNo($schedule_ids);
            if(!$schedule_infos){
                return false;
            }

            $check_code = $this->_checkScheduleOutOfTime($schedule_infos,$lottery_info);
            if(!$check_code){
                return false;
            }

        }else{

            if(isZcsfc($param->lottery_id)){
                $issue_info = D('Issue')->queryIssueInfoByIssueNo($param->lottery_id,$param->issue_no);
                $end_time = D('Issue')->getEndTime($issue_info['issue_id']);
            }else{
                $end_time = D('Issue')->getEndTime($param->issue_id);
            }

            ApiLog('$end_time:'.D('Issue')->getLastSql(), 'cobet');
            $scheme_end_timestamp = strtotime($end_time) - $lottery_info['lottery_ahead_endtime'] - C('COBET_SCHEME_AHEAD_END_TIME');
            if(time() > $scheme_end_timestamp){
                return false;
            }
        }
        return true;

    }

     private function _checkScheduleOutOfTime($schedule_infos, $lottery_info){
        foreach ($schedule_infos as $schedule_info) {
            $out_of_time = strtotime($schedule_info['schedule_end_time']) < (time() + intval($lottery_info['lottery_ahead_endtime']) + C('COBET_SCHEME_AHEAD_END_TIME'));
            ApiLog('mix end time :' . $schedule_info['schedule_end_time'] . '======' . date('Y-m-d H:i:s'), 'cobet_bet');
            ApiLog('mix end sss time :' . $out_of_time . '========' . strtotime($schedule_info['schedule_end_time']) . '======' . (time() + intval($lottery_info['lottery_ahead_endtime'])), 'cobet_bet');
            if($out_of_time){
                return false;
            }
        }
        return true;
    }
	
	private function _checkUserCouponUseable($uid, $user_coupon_id){
		if (!$user_coupon_id) {
			return true;
		}
		$user_coupon_info = D('UserCoupon')->getUserCouponInfo($user_coupon_id);
		if (strtotime($user_coupon_info['user_coupon_end_time']) < time()) {
			return false;
		}
		if (strtotime($user_coupon_info['user_coupon_start_time']) > time()) {
			return false;
		}
		if ($user_coupon_info['uid'] != $uid) {
			return false;
		}
		if ($user_coupon_info['user_coupon_status'] != C('USER_COUPON_STATUS.AVAILABLE')) {
			return false;
		}
		return true;
	}

	private function _getLotteryInstance($lottery_id){
		if (isJCLottery($lottery_id)) {
			$lottery_prefix = 'JC';
		} elseif (isZcsfc($lottery_id)) {
			$lottery_prefix = 'SFC';
		} else {
			$lottery_prefix = 'SZC';
		}
		return A($lottery_prefix, 'Lottery');
	}
	
	private function _getSelectedCouponBalance($uid, $couponId, $lottery_id = '', $orderTotalAmount = '',$suite_id = 0) {
		if($couponId) {
			$is_owner = D('UserCoupon')->owenedByUser($uid, $couponId);
			if(!$is_owner){
				$this->_throwExcepiton(C('ERROR_CODE.USER_COUPON_ERROR'));
			}
			ApiLog('$lottery_id.'.$lottery_id,'UserCoupon');

			$user_coupon_info = D('UserCoupon')->getUserCouponInfoByUid($uid, $couponId);

            if($suite_id){
                if($user_coupon_info['coupon_type'] == 2){
                    $this->_throwExcepiton(C('ERROR_CODE.COUPON_INVALID'),C('WEB_PAY_MESSAGE.COUPON_NOT_USEABLE'));
                }
            }

			if(!empty($lottery_id) && !empty($user_coupon_info['coupon_lottery_ids'])){
				$lottery_list = explode(',',$user_coupon_info['coupon_lottery_ids']);
				if(count($lottery_list) > 0){
					if(!in_array($lottery_id,$lottery_list)){
						$this->_throwExcepiton(C('ERROR_CODE.COUPON_ERROR_FOR_LIMIT_LOTTERY_IDS'),C('WEB_PAY_MESSAGE.ORDER_LIMIT_LOTTERY'));
					}
				}
			}


            if(!empty($orderTotalAmount) && bccomp($orderTotalAmount, $user_coupon_info['coupon_min_consume_price']) < 0){
				ApiLog('支付金额:'.$orderTotalAmount.'小于红包规定最小金额:'.$user_coupon_info['coupon_min_consume_price'],'UserCoupon');
				$this->_throwExcepiton(C('ERROR_CODE.COUPON_ERROR_FOR_ORDER_MIN_CONSUME'),C('WEB_PAY_MESSAGE.ORDER_MONEY_TOO_SMALL'));
			}


			return D('UserCoupon')->getCouponBalance($couponId);
		} else {
			return 0;
		}
	}
	
	protected function checkScheduleOutOfTime($scheduleInfos, $lottery_info){
		foreach ($scheduleInfos as $scheduleInfo) {
			$out_of_time = strtotime($scheduleInfo['schedule_end_time']) < (time() + intval($lottery_info['lottery_ahead_endtime']));
			ApiLog('mix end time :' . $scheduleInfo['schedule_end_time'] . '======' . date('Y-m-d H:i:s'), 'opay');
			ApiLog('mix end sss time :' . $out_of_time . '========' . strtotime($scheduleInfo['schedule_end_time']) . '======' . (time() + intval($lottery_info['lottery_ahead_endtime'])), 'opay');
			if($out_of_time){
				$this->_throwExcepiton(C('ERROR_CODE.OUT_OF_ISSUE_TIME'));
			}
		}
	}
	
	protected function checkScheduleTimeRangeInfo(array $scheduleInfos) {
		$last_schedule_game_start_time = 0;
// 		$first_schedule_end_time = strtotime($scheduleInfos[0]['schedule_end_time']);
		$first_schedule_end_time = 0;
		ApiLog('$first_schedule_end_time:'.$first_schedule_end_time, 'opay');
		ApiLog('$$scheduleInfos:'.print_r($scheduleInfos,true), 'opay');
		
		$last_schedule_info = array();
		$first_schedule_info = array();
		foreach ($scheduleInfos as $scheduleInfo) {
			$schedule_game_start_time_unix_timestamp = strtotime($scheduleInfo['schedule_game_start_time']);
			$schedule_end_time_unix_timestamp = strtotime($scheduleInfo['schedule_end_time']);
			if ($schedule_game_start_time_unix_timestamp >= $last_schedule_game_start_time) {
				$last_schedule_info = $scheduleInfo;
				$last_schedule_game_start_time = $schedule_game_start_time_unix_timestamp;
			}
			if($first_schedule_end_time==0){
				$first_schedule_end_time = $schedule_end_time_unix_timestamp;
				$first_schedule_info = $scheduleInfo;
			}
			
			if ($schedule_end_time_unix_timestamp < $first_schedule_end_time) {
				$first_schedule_info = $scheduleInfo;
				$first_schedule_end_time = $schedule_end_time_unix_timestamp;
			}
		}
		$schedule_range_info['last_schedule_info'] = $last_schedule_info;
		$schedule_range_info['first_schedule_info'] = $first_schedule_info;
		
		ApiLog('$$$schedule_range_info:'.print_r($schedule_range_info,true), 'opay');
		
		return $schedule_range_info;
	}

	protected function formatRequestScheduleOrders($schedule_orders){
		$formated_schedule_orders = array();
		foreach ($schedule_orders as $schedule){
			if(is_object($schedule)){
				$schedule = (array)$schedule;
			}
			$new_schedule = $schedule;
			$bet_number_string = $new_schedule['bet_number'];
			$bet_numbers = explode('|', $bet_number_string);
			$bet_content = array();
			foreach($bet_numbers as $bet_number){
				$bet_content_info = explode(':', $bet_number);
				$lottery_id = intval($bet_content_info[0]);
				$bet_options = explode(',', $bet_content_info[1]);
				$bet_content[$lottery_id]['bet_number_string'] = $bet_number;
				$bet_content[$lottery_id]['bet_options'] = $bet_options;
			}
			$new_schedule['bet_contents'] = $bet_content;
			$formated_schedule_orders[] = $new_schedule;
		}
		return $formated_schedule_orders;
	}
	
	protected function payAndRecord($uid, $scheme_id, $user_coupon_id, $scheme_data, $guarantee_amount = 0, $self_buy_amount = 0){
		$frozen_money = $guarantee_amount + $self_buy_amount;
		if ($user_coupon_id) {
			$user_coupon_balance = D('UserCoupon')->getCouponBalance($user_coupon_id);
			$is_bigger = ApiBcComp($user_coupon_balance, $frozen_money);
			if ($is_bigger) {
				$coupon_consume_amount = $frozen_money;
				$account_consume_amount = 0;
			} else {
				$coupon_consume_amount = $user_coupon_balance;
				$account_consume_amount = bcsub($frozen_money, $user_coupon_balance, 2);
			}
		} else {
			$account_consume_amount = $frozen_money;
		}
	
		$total_pay_amount = bcadd($coupon_consume_amount, $account_consume_amount);
		$amount_is_correct = bccomp($total_pay_amount, $frozen_money, 2) == 0; // 扣款数要等于订单价格，注意浮点数比较
		if (!$amount_is_correct) {
			ApiLog('$deductResult:' . $amount_is_correct, 'chase');
			$this->_throwExcepiton(C('ERROR_CODE.DEDUCT_MONEY_ERROR'));
		}
	
		if (!$guarantee_amount) {
			$buy_record_data['type'] = COBET_TYPE_OF_BOUGHT;
			$buy_record_data['record_bought_unit'] = $scheme_data['scheme_bought_unit'];
			$buy_record_data['record_bought_amount'] = $self_buy_amount;
			if ($coupon_consume_amount) {
				$buy_record_data['user_coupon_id'] = $user_coupon_id;
				$buy_record_data['record_user_coupon_consume_amount'] = $coupon_consume_amount;
				$buy_record_data['record_user_cash_amount'] = bcsub($self_buy_amount, $coupon_consume_amount, 2);
			} else {
				$buy_record_data['user_coupon_id'] = 0;
				$buy_record_data['record_user_coupon_consume_amount'] = 0;
				$buy_record_data['record_user_cash_amount'] = $self_buy_amount;
			}
		} elseif (!$self_buy_amount) {
			$guarantee_record_data['type'] = COBET_TYPE_OF_GUARANTEE_FROZEN;
			$guarantee_record_data['record_bought_unit'] = $scheme_data['scheme_guarantee_unit'];
			$guarantee_record_data['record_bought_amount'] = $guarantee_amount;
			if ($coupon_consume_amount) {
				$guarantee_record_data['user_coupon_id'] = $user_coupon_id;
				$guarantee_record_data['record_user_coupon_consume_amount'] = $coupon_consume_amount;
				$guarantee_record_data['record_user_cash_amount'] = bcsub($guarantee_amount, $coupon_consume_amount, 2);
			} else {
				$guarantee_record_data['user_coupon_id'] = 0;
				$guarantee_record_data['record_user_coupon_consume_amount'] = 0;
				$guarantee_record_data['record_user_cash_amount'] = $guarantee_amount;
			}
		} else {
			$buy_record_data['type'] = COBET_TYPE_OF_BOUGHT;
			$buy_record_data['record_bought_unit'] = $scheme_data['scheme_bought_unit'];
			$buy_record_data['record_bought_amount'] = $self_buy_amount;
			$guarantee_record_data['type'] = COBET_TYPE_OF_GUARANTEE_FROZEN;
			$guarantee_record_data['record_bought_unit'] = $scheme_data['scheme_guarantee_unit'];
			$guarantee_record_data['record_bought_amount'] = $guarantee_amount;
				
			$guarantee_record_data['record_bought_unit'] = $scheme_data['scheme_guarantee_unit'];
			$guarantee_record_data['record_bought_amount'] = $guarantee_amount;
			$buy_record_data['record_bought_unit'] = $scheme_data['scheme_bought_unit'];
			$buy_record_data['record_bought_amount'] = $self_buy_amount;
			if ($coupon_consume_amount) {
				$guarantee_record_data['user_coupon_id'] = $user_coupon_id;
				if (ApiBcComp($coupon_consume_amount, $guarantee_amount)) {
					$guarantee_record_data['record_user_coupon_consume_amount'] = $guarantee_amount;
					$guarantee_record_data['record_user_cash_amount'] = 0;
					$buy_record_data['record_user_coupon_consume_amount'] = bcsub($coupon_consume_amount, $guarantee_amount, 2);
					if($buy_record_data['record_user_coupon_consume_amount']){
						$buy_record_data['user_coupon_id'] = $user_coupon_id;
					}
					$buy_record_data['record_user_cash_amount'] = $account_consume_amount;
				} else {
					$guarantee_record_data['record_user_coupon_consume_amount'] = $coupon_consume_amount;
					$guarantee_record_data['record_user_cash_amount'] = bcsub($guarantee_amount, $coupon_consume_amount, 2);
					$buy_record_data['record_user_coupon_consume_amount'] = 0;
					$buy_record_data['record_user_cash_amount'] = bcsub($account_consume_amount, $guarantee_record_data['record_user_cash_amount'], 2);
				}
			} else {
				$guarantee_record_data['record_user_coupon_consume_amount'] = 0;
				$guarantee_record_data['record_user_cash_amount'] = $guarantee_amount;
				$buy_record_data['record_user_coupon_consume_amount'] = 0;
				$buy_record_data['record_user_cash_amount'] = $self_buy_amount;
			}
		}
		$remark = '';
		if ($guarantee_record_data) {
			if ($guarantee_record_data['record_user_coupon_consume_amount'] > 0) {
				$coupon_deduct_result = D('UserCoupon')->deductCouponBalance($uid, $user_coupon_id, $guarantee_record_data['record_user_coupon_consume_amount'], C('USER_COUPON_LOG_TYPE.COBET_GUARANTEE_FROZEN'), $remark);
				if ($coupon_deduct_result === false) {
					$this->_throwExcepiton(C('ERROR_CODE.USER_COUPON_ERROR'));
				}
			}
			if ($guarantee_record_data['record_user_cash_amount'] > 0) {
				$money_deduct_result = D('UserAccount')->deductMoney($uid, $guarantee_record_data['record_user_cash_amount'], $scheme_id, C('USER_ACCOUNT_LOG_TYPE.COBET_GUARANTEE_FROZEN'));
				ApiLog('deduct:' . $money_deduct_result . '====' . $account_consume_amount, 'chase');
				if ($money_deduct_result === false) {
					ApiLog('deduct false:' . $money_deduct_result . '====' . bcsub($frozen_money, $coupon_consume_amount, 2), 'chase');
					$this->_throwExcepiton(C('ERROR_CODE.INSUFFICIENT_FUND'));
				}
			}
				
			$add_guarantee_result = D('CobetRecord')->add($this->_buildCobetRecord($uid, $scheme_id, $guarantee_record_data));
			if (!$add_guarantee_result) {
				$this->_throwExcepiton(C('ERROR_CODE.DATABASE_ERROR'));
			}
		}
		if ($buy_record_data) {
			if ($buy_record_data['record_user_coupon_consume_amount'] > 0) {
				$coupon_deduct_result = D('UserCoupon')->deductCouponBalance($uid, $user_coupon_id, $buy_record_data['record_user_coupon_consume_amount'], C('USER_COUPON_LOG_TYPE.COBET_BOUGHT_FROZEN'), $remark);
				if ($coupon_deduct_result === false) {
					$this->_throwExcepiton(C('ERROR_CODE.USER_COUPON_ERROR'));
				}
			}
			if ($buy_record_data['record_user_cash_amount'] > 0) {
				$money_deduct_result = D('UserAccount')->deductMoney($uid, $buy_record_data['record_user_cash_amount'], $scheme_id, C('USER_ACCOUNT_LOG_TYPE.COBET_BOUGHT_FROZEN'));
				ApiLog('deduct:' . $money_deduct_result . '====' . $account_consume_amount, 'chase');
				if ($money_deduct_result === false) {
					ApiLog('deduct false:' . $money_deduct_result . '====' . bcsub($frozen_money, $coupon_consume_amount, 2), 'chase');
					$this->_throwExcepiton(C('ERROR_CODE.INSUFFICIENT_FUND'));
				}
			}
			$add_buy_result = D('CobetRecord')->add($this->_buildCobetRecord($uid, $scheme_id, $buy_record_data));
			if (!$add_buy_result) {
				$this->_throwExcepiton(C('ERROR_CODE.DATABASE_ERROR'));
			}
		}
	
		if ($buy_record_data || $guarantee_record_data) {
			return true;
		}
	}

	protected function updateStatusForBegin($scheme_id, $scheme_data){
		$map['scheme_id'] = $scheme_id;
		$update_data['scheme_status'] = COBET_SCHEME_STATUS_OF_NO_BEGIN_BOUGHT;
		$pay_result = D('CobetScheme')->where($map)->save($update_data);
		if ($pay_result) {
			return true;
		}
		return false;
	}
	
	private function _buildCobetRecord($uid, $scheme_id, $guarantee_record_data){
		$record_data = $guarantee_record_data;
		$record_data['scheme_id'] = $scheme_id;
		$record_data['uid'] = $uid;
		$record_data['record_createtime'] = getCurrentTime();
		$record_data['record_status'] = 1;
		return $record_data;
	}
	
	protected function verifyParamsForJoin($params, $user_info, $scheme_info){
		if ($params->pay_total_amount <= 0) {
			return false;
		}
	
		$lottery_info = D('Lottery')->getLotteryInfo($scheme_info['lottery_id']);
		if (empty($lottery_info) || !$lottery_info['lottery_status']) {
			return false;
		}
	
		if ($params->total_unit <= 0) {
			return false;
		}
		if (($scheme_info['scheme_total_unit'] - $scheme_info['scheme_bought_unit']) < $params->total_unit) {
            $this->_throwExcepiton(C('ERROR_CODE.SCHEME_UNIT_IS_NOT_ENOUGH'));
		    return false;
		}
	
		if (bccomp($scheme_info['scheme_amount_per_unit'], bcdiv($params->pay_total_amount, $params->total_unit)) != 0) {
			return false;
		}
	
		if ($scheme_info['scheme_status'] != C('COBET_SCHEME_STATUS.NO_BEGIN_BOUGHT') && $scheme_info['scheme_status'] != C('COBET_SCHEME_STATUS.ONGOING')) {
            $this->_throwExcepiton(C('ERROR_CODE.SCHEME_STATUS_IS_END'));
			return false;
		}

        $over_time_status = $this->_checkJoinOverTime($scheme_info['lottery_id'],$scheme_info['scheme_issue_id']);
		if(!$over_time_status){
		    return false;
        }
	
		$coupon_is_useable = $this->_checkUserCouponUseable($user_info['uid'], $params->user_coupon_id);
		if (!$coupon_is_useable) {
			return false;
		}
		$verified_params['lottery_id'] = $scheme_info['lottery_id'];
		$verified_params['scheme_bought_amount'] = $params->pay_total_amount;
		$verified_params['scheme_bought_unit'] = $params->total_unit;
		$verified_params['scheme_identity'] = $params->order_identity;
		$verified_params['user_coupon_id'] = $params->user_coupon_id;
		return $verified_params;
	}
	
	public function addJcOrder($uid, $scheme_sn, $params){
		$verifyObj = Factory::createVerifyJcObj($params->lottery_id);
		$lottery_id = $params->lottery_id;
		if(!isset($params->coupon_id)){
			$params->coupon_id = 0;
		}
		if (isJcMix($lottery_id)) {
			$formated_schedule_orders = $this->formatRequestScheduleOrders($params->schedule_orders);
			$tickets_from_combination = $verifyObj->convertScheduleOrderToTickets($formated_schedule_orders, $params->series, $params->lottery_id);
			return $this->_addJcMixtureOrder($uid, $scheme_sn, $params, $tickets_from_combination);
		} else {
			return $this->_addJcNoMixtureOrder($uid, $scheme_sn, $params);
		}
	}

	private function _addJcMixtureOrder($uid, $orderSku, $params, $tickets_from_combination){
		$jcTicketInfo = $this->_buildJcMixtureTicketInfo($uid, $params->schedule_orders, $tickets_from_combination, $params->stake_count, $params->total_amount, $params->multiple, $params->lottery_id);
		if (empty($jcTicketInfo)) {
			$this->_throwExcepiton(C('ERROR_CODE.DATABASE_ERROR'));
		}
		
		$orderTicket = $this->_addJzOrderSchedule($params->lottery_id, $uid, $params->total_amount, $orderSku, $params->multiple, $params->coupon_id, $params->schedule_orders, $jcTicketInfo, $params->order_identity, $params);
		
		return $orderTicket;
	}

	private function _buildJcMixtureTicketInfo($uid, array $scheduleOrders, array $tickets_from_combination, $stakeCount, $totalAmount, $multiple, $lotteryId){
		foreach($scheduleOrders as $schedule_info){
			if(is_object($schedule_info)){
				$schedule_info = (array)$schedule_info;
			}
			$schedule_ids_in_order[] = $schedule_info['schedule_id'];
				
		}
// 		$schedule_ids_in_order = array_column($scheduleOrders, 'schedule_id');
		ApiLog('ids:' . print_r($schedule_ids_in_order, true), 'opay');
		$schedule_infos_in_order = D('JcSchedule')->getScheduleIssueNo($schedule_ids_in_order);
		$schedule_ids_of_all_lottery = array();
		foreach ($schedule_infos_in_order as $schedule_id => $scheduleInfo) {
			$schedule_end_time_unix_timestamp = strtotime($scheduleInfo['schedule_end_time']);
			$scheduleInfo['schedule_end_time_unix_timestamp'] = $schedule_end_time_unix_timestamp;
			$mix_schedule_id = $scheduleInfo['schedule_id'];
			$day = $scheduleInfo['schedule_day'];
			$week = $scheduleInfo['schedule_week'];
			$round_no = $scheduleInfo['schedule_round_no'];
			
			$schedule_ids_of_all_lottery[$mix_schedule_id] = D('JcSchedule')->queryAllScheduleIdsFromScheduleNo($day, $week, $round_no);
			$new_schedule_infos_in_order[$schedule_id] = $scheduleInfo;
		}
		ApiLog('new in order:' . print_r($new_schedule_infos_in_order, true), 'opay');
		
		$stageTicket = $this->_saveJcMixtureTicket($uid, $tickets_from_combination, $new_schedule_infos_in_order, $lotteryId, $schedule_ids_of_all_lottery, $multiple);
		
		$schedule_range_info = $this->checkScheduleTimeRangeInfo($new_schedule_infos_in_order);
		$last_schedule_info_in_order = $schedule_range_info['last_schedule_info'];
		$first_schedule_info_in_order = $schedule_range_info['first_schedule_info'];
		if ($stageTicket) {
			return array(
					'lastSchedule' => $last_schedule_info_in_order,
					'firstSchedule' => $first_schedule_info_in_order,
					'stageTicket' => $stageTicket 
			);
		} else {
			return false;
		}
	}

	private function _saveJcMixtureTicket($uid, array $tickets_from_combination, array $scheduleInfos, $lotteryId, $schedule_ids_of_all_lottery, $multiple){
		$orderStakeCount = 0;
		$ticketSeq = 0;
		$ticketData = $ticketList = array();
		$verifyObj = Factory::createVerifyJcObj($lotteryId);
		ApiLog('tickets:' . print_r($tickets_from_combination, true), 'opay');
		foreach ($tickets_from_combination as $ticket) {
			$competitionInfo = $this->_buildJcMixCompetitionInfoForPrintOut($ticket, $scheduleInfos, $lotteryId, $schedule_ids_of_all_lottery);
			if (!$competitionInfo) {
				$this->_throwExcepiton(C('ERROR_CODE.SCHEDULE_NO_ERROR'));
			}
			$competition = $competitionInfo['competition'];
			
			$betType = $ticket['bet_type'];
			$ticket_schedule_list = $this->buildBetScheduleListInTicket($ticket);
			
			$stakeCount = $verifyObj->getStakeCount($ticket_schedule_list, $betType);
			
			ApiLog('aaaa:' . $betType . '===' . $stakeCount . '===' . print_r($ticket_schedule_list, true), 'opay');
			
			$devide_result = $this->devideOverMultipleTicket($uid, $lotteryId, JC_PLAY_TYPE_MULTI_STAGE, $ticketSeq, $stakeCount, $multiple, $betType, $competitionInfo);
			$orderStakeCount += $stakeCount;
			
			$ticketSeq = $devide_result['ticket_seq'];
			$ticketList = array_merge($ticketList, $devide_result['printout_ticket_list']);
			$ticketData = array_merge($ticketData, $devide_result['ticket_data']);
		}
		
		return array(
				'stakeCount' => $orderStakeCount,
				'ticketList' => $ticketList,
				'ticketData' => $ticketData 
		);
	}

	private function _devideOverMultipleTicket($uid, $lotteryId, $playType, $ticketSeq, $stakeCount, $multiple, $betType, $competitionInfo){
		$first_schedule_issue_id_in_ticket = $competitionInfo['first_schedule_issue_id'];
		$first_schedule_issue_no_in_ticket = $competitionInfo['first_schedule_issue_no'];
		$first_schedule_end_time_in_ticket = $competitionInfo['first_schedule_end_time'];
		$last_schedule_issue_id_in_ticket = $competitionInfo['last_schedule_issue_id'];
		$last_schedule_issue_no_in_ticket = $competitionInfo['last_schedule_issue_no'];
		$last_schedule_end_time_in_ticket = $competitionInfo['last_schedule_end_time'];
		$competition = $competitionInfo['competition'];
		
		$once_ticket_amount = $stakeCount * LOTTERY_PRICE;
		if ($once_ticket_amount > BET_TICKET_AMOUNT_LIMIT) {
			$this->_throwExcepiton(C('ERROR_CODE.OVER_TICKET_LIMIT'));
		}
		
		$max_multiple = getMaxMultipleByLotteryId($competitionInfo['ticket_lottery_id']);
		
		if ($multiple > $max_multiple) {
			$limit_multiple = $max_multiple;
		} else {
			$limit_multiple = $multiple;
		}
		
		$limit_ticket_amount = $once_ticket_amount * $limit_multiple;
		if ($limit_ticket_amount > BET_TICKET_AMOUNT_LIMIT) {
			$max_once_ticket_multiple = floor(BET_TICKET_AMOUNT_LIMIT / $once_ticket_amount);
			$once_ticket_multiple = $max_once_ticket_multiple;
		} else {
			$once_ticket_multiple = $limit_multiple;
		}
		$devide_ticket_num = ceil($multiple / $once_ticket_multiple);
		
		for($i = 0; $i < $devide_ticket_num; $i++) {
			if ($i == $devide_ticket_num - 1) {
				$ticket_multiple = $multiple - ($devide_ticket_num - 1) * $once_ticket_multiple;
			} else {
				$ticket_multiple = $once_ticket_multiple;
			}
			$ticket_amount = $once_ticket_amount * $ticket_multiple;
			$ticketSeq++;
			$ticket_lottery_id = $competitionInfo['ticket_lottery_id'];
			$ticketList[] = $this->buildCompetitionTicketItemForPrintout($ticketSeq, $playType, $betType, $stakeCount, $ticket_amount, $competition, $last_schedule_end_time_in_ticket, $ticket_lottery_id, $ticket_multiple, $first_schedule_end_time_in_ticket, $first_schedule_issue_no_in_ticket, $last_schedule_issue_no_in_ticket);
			
			// add 'v' before option
			$formated_competition_infos = $this->formatBetOptionAddV($competition);
			$jsonCompetition = json_encode($formated_competition_infos);
			
			if ($playType == JC_PLAY_TYPE_MULTI_STAGE) {
				$issueNos = $competitionInfo['ticket_issue_nos'];
			} else {
				$issueNos = $competitionInfo['issue_no'];
			}
			if (!$issueNos) {
				return false;
			}
			
			$ticketData[] = D('JcTicket')->buildTicketData($uid, $ticketSeq, $playType, $stakeCount, $betType, $jsonCompetition, $last_schedule_issue_id_in_ticket, $issueNos, $ticket_amount, $ticket_multiple, $first_schedule_issue_id_in_ticket, $competitionInfo['ticket_lottery_id']);
		}
		
		return array(
				'ticket_seq' => $ticketSeq,
				'ticket_data' => $ticketData,
				'printout_ticket_list' => $ticketList 
		);
	}

	private function _buildJcMixCompetitionInfoForPrintOut(array $ticket_item, array $scheduleInfos, $lotteryId, $schedule_ids_of_all_lottery){
		$competitions = array();
		$ticket_content = $ticket_item;
		$betType = $ticket_content['bet_type'];
		unset($ticket_content['bet_type']);
		$ticket_lottery_id = $this->getLotteryIdByCompetition($ticket_content, $lotteryId);
		$is_jc_mix_lottery_id = isJcMix($ticket_lottery_id);
		
		$last_schedule_game_start_time = 0;
		$first_schedule_end_time = 0;
		foreach ($ticket_content as $bet_schedule) {
			if ($is_jc_mix_lottery_id) {
				$scheduleId = $bet_schedule['schedule_id'];
				$issueNo = $scheduleInfos[$scheduleId]['schedule_issue_no'];
				$ticket_schedule_info = $scheduleInfos[$scheduleId];
				$competition_lottery_id = $bet_schedule['lottery_id'];
				if (!isset($schedule_ids_of_all_lottery[$scheduleId][$competition_lottery_id])) {
					ApiLog('no exist:' . $competition_lottery_id . '====' . $scheduleId . '====' . print_r($scheduleInfos, true), 'csq');
				}
			} else {
				$orig_schedule_id = $bet_schedule['schedule_id'];
				$orig_schedule_info = $scheduleInfos[$orig_schedule_id];
				$play_type = $orig_schedule_info['play_type'];
				// ApiLog('orig_schedule_info:'.print_r($orig_schedule_info,true), 'com');
				$ticket_schedule_info = $schedule_ids_of_all_lottery[$orig_schedule_id][$ticket_lottery_id][$play_type];
				// ApiLog('covert ticket schedule info:'.print_r($ticket_schedule_info,true), 'com');
				$scheduleId = $ticket_schedule_info['schedule_id'];
				$issueNo = $ticket_schedule_info['schedule_issue_no'];
				$competition_lottery_id = $ticket_lottery_id;
			}
			
			if (empty($issueNo)) {
				// 查不到issueno 报警
				ApiLog('$scheduleInfos[$orig_schedule_id]:' . print_r($scheduleInfos[$orig_schedule_id], true), 'opay');
				ApiLog('$$$ticket_schedule_info:' . $orig_schedule_id . '==' . $ticket_lottery_id . '==' . $play_type . '==' . print_r($ticket_schedule_info, true), 'opay');
				ApiLog('$$$schedule_ids_of_all_lottery:' . $issueNo . '=====' . print_r($schedule_ids_of_all_lottery, true), 'opay');
				ApiLog('$$all_ticket_issue_no:' . $issueNo . '=====' . print_r($schedule_ids_of_all_lottery[$orig_schedule_id], true), 'opay');
				return false;
			}
			
			$competition['lottery_id'] = $competition_lottery_id;
			$competition['bet_options'] = $bet_schedule['bet_options'];
			$competition['issue_no'] = $issueNo;
			
			$competitions[] = $competition;
			
			$all_ticket_issue_no[] = $issueNo;
			
			$schedule_end_time_stamp = $ticket_schedule_info['schedule_end_time_unix_timestamp'];
			$schedule_game_start_time_stamp = strtotime($ticket_schedule_info['schedule_game_start_time']);
			;
			if ($schedule_game_start_time_stamp >= $last_schedule_game_start_time) {
				$last_schedule_game_start_time = $schedule_game_start_time_stamp;
				$last_schedule_in_ticket = $ticket_schedule_info;
			}
			
			if ($first_schedule_end_time == 0) {
				$first_schedule_end_time = $schedule_end_time_stamp;
			}
			if ($schedule_end_time_stamp <= $first_schedule_end_time) {
				ApiLog('build ====mix :' . $first_schedule_end_time . '=-===' . print_r($ticket_schedule_info, true), 'opay');
				$first_schedule_end_time = $schedule_end_time_stamp;
				$first_schedule_in_ticket = $ticket_schedule_info;
			}
		}
		ApiLog('build mix :' . $first_schedule_end_time . '===' . print_r($first_schedule_in_ticket, true), 'opay');
		asort($all_ticket_issue_no);
		$ticket_issue_nos = implode(',', $all_ticket_issue_no);
		
		return array(
				'ticket_lottery_id' => $ticket_lottery_id,
				'bet_type' => $betType,
				'ticket_issue_nos' => $ticket_issue_nos,
				'last_schedule_issue_id' => $last_schedule_in_ticket['schedule_id'],
				'last_schedule_issue_no' => $last_schedule_in_ticket['schedule_issue_no'],
				'last_schedule_end_time' => $last_schedule_in_ticket['schedule_end_time'],
				'first_schedule_issue_id' => $first_schedule_in_ticket['schedule_id'],
				'first_schedule_issue_no' => $first_schedule_in_ticket['schedule_issue_no'],
				'first_schedule_end_time' => $first_schedule_in_ticket['schedule_end_time'],
				'competition' => $competitions 
		);
	}

	private function _addJcNoMixtureOrder($uid, $orderSku, $params){
		$playType = C('MAPPINT_JC_PLAY_TYPE.' . $params->play_type);
		$series = explode(',', $params->series);
		
		$jcTicketInfo = $this->_buildJzTicketInfo($params->schedule_orders, $uid, $playType, $series, $params->lottery_id, $params->stake_count, $params->total_amount, $params->multiple);
		\AppException::ifNoExistThrowException($jcTicketInfo, C('ERROR_CODE.DATABASE_ERROR'));
		
		$orderTicket = $this->_addJzOrderSchedule($params->lottery_id, $uid, $params->total_amount, $orderSku, $params->multiple, $params->coupon_id, $params->schedule_orders, $jcTicketInfo, $params->order_identity, $params);
		return $orderTicket;
	}

	private function getTicktModel($lotteryId){
		if (in_array($lotteryId, C('JCZQ'))) {
			return D('CobetJczqTicket');
		} else if (in_array($lotteryId, C('JCLQ'))) {
			return D('CobetJclqTicket');
		} else {
			$lotteryType = C("LOTTERY_TYPE.$lotteryId");
			return D('Cobet' . ucfirst($lotteryType) . 'Ticket');
		}
	}

	private function _addJzOrderSchedule($lotteryId, $uid, $totalAmount, $orderSku, $multiple, $couponId, array $scheduleOrders, array $jcTicketInfo, $identity, $request_params){
		$orderTotalAmount = $totalAmount;
		$lastSchedule = $jcTicketInfo['lastSchedule'];
		$firstSchedule = $jcTicketInfo['firstSchedule'];
		$stageTicket = $jcTicketInfo['stageTicket'];
		$order_params['play_type'] = $request_params->play_type;
		$order_params['series'] = $request_params->series;
		$order_params['order_type'] = intval($request_params->order_type);
		$order_params['content'] = json_encode($request_params->schedule_orders);
		
		M()->startTrans();
		$orderId = D('CobetOrder')->addOrder($uid, $orderTotalAmount, $lastSchedule['schedule_id'], $multiple, $couponId, $lotteryId, $orderSku, $firstSchedule['schedule_id'], $identity, 0, 0, '', $order_params);
		$model = $this->getTicktModel($lotteryId);
		if (!$orderId || !$model) {
			M()->rollback();
			return false;
		}
		$ticketDatas = $model->appendOrderId($stageTicket['ticketData'], $orderId);
		
		$addTickets_result = $model->insertAll($ticketDatas);
		if (!$addTickets_result) {
			ApiLog('$addTickets:' . $addTickets_result, 'opay');
			M()->rollback();
			return false;
		}
		$orderDetails = $this->_getJcOrderDetail($scheduleOrders, $orderId);
		$addDetail_result = D('CobetJcOrderDetail')->insertAll($orderDetails);
		if (!$addDetail_result) {
			ApiLog('after $$addDetail_result:' . $addDetail_result, 'opay');
			M()->rollback();
			return false;
		}
		M()->commit();
		
		return array(
				'orderId' => $orderId,
				'issueNo' => $lastSchedule['schedule_issue_no'],
				'firstIssueNo' => $firstSchedule['schedule_issue_no'],
				'ticketList' => $stageTicket['ticketList'] 
		);
	}

	private function _getJcOrderDetail($scheduleOrders, $orderId){
		$orderDetails = array();
		foreach ($scheduleOrders as $scheduleOrder) {
			if(is_object($scheduleOrder)){
				$scheduleOrder = (array)$scheduleOrder;
			}
			$betNumbers = $this->parseBetNumber($scheduleOrder['bet_number']);
			$betNumbers = betOptionsAddV($betNumbers);
			$betContent = json_encode($betNumbers);
			$orderDetails[] = D('CobetJcOrderDetail')->buildDetailData($orderId, $scheduleOrder['schedule_id'], $betContent, $scheduleOrder['is_sure']);
		}
		return $orderDetails;
	}

	private function _buildJzTicketInfo(array $scheduleOrders, $uid, $playType, array $series, $lotteryId, $stakeCount, $totalAmount, $multiple){
		
		foreach($scheduleOrders as $schedule_info){
			if(is_object($schedule_info)){
				$schedule_info = (array)$schedule_info;
			}
			$schedule_ids_in_order[] = $schedule_info['schedule_id'];
		}
// 		$schedule_ids_in_order = array_column($scheduleOrders, 'schedule_id');
		ApiLog('schedule ids:' . print_r($scheduleOrders, true) . '===' . print_r($schedule_ids_in_order, true), 'opay');
		$scheduleInfos = D('JcSchedule')->getScheduleIssueNo($schedule_ids_in_order);
		ApiLog('schedule info:' . print_r($scheduleInfos, true), 'opay');
		if (!$scheduleInfos) {
			$this->_throwExcepiton(C('ERROR_CODE.SCHEDULE_NO_ERROR'));
		}
		
		$lottery_info = D('Lottery')->getLotteryInfo($lotteryId);
		$this->checkScheduleOutOfTime($scheduleInfos, $lottery_info);
		
		$schedule_range_info = $this->checkScheduleTimeRangeInfo($scheduleInfos);
		$lastSchedule = $schedule_range_info['last_schedule_info'];
		$firstSchedule = $schedule_range_info['first_schedule_info'];
		
		if ($playType == C('JC_PLAY_TYPE.ONE_STAGE')) { // 如果是单关
			$stageTicket = $this->_saveOneStageTicket($uid, $scheduleOrders, $scheduleInfos, $playType, $series, $lotteryId, $multiple);
		} elseif ($playType == C('JC_PLAY_TYPE.MULTI_STAGE')) {
			$stageTicket = $this->_saveMultiStageTicket($uid, $scheduleOrders, $scheduleInfos, $playType, $series, $lotteryId, $multiple);
		}
		if ($stageTicket) {
			ApiLog('sss:' . print_r($lastSchedule, true) . '===' . print_r($stageTicket, true), 'opay');
			return array(
					'lastSchedule' => $lastSchedule,
					'firstSchedule' => $firstSchedule,
					'stageTicket' => $stageTicket 
			);
		} else {
			return false;
		}
	}

	private function _saveOneStageTicket($uid, array $scheduleOrders, array $scheduleInfos, $playType, array $series, $lotteryId, $multiple){
		$betType = $series[0];
		$ticketSeq = 0;
		$ticketList = array();
		$ticketData = array();
		$orderStakeCount = 0;
		foreach ($scheduleOrders as $scheduleOrder) {
		    if(is_object($scheduleOrder)){
                $scheduleOrder = (array)$scheduleOrder;
            }
			$competitionInfo = $this->_buildJcNoMixCompetitionInfoForPrintOut(array(
					$scheduleOrder 
			), $scheduleInfos, $lotteryId);
			$bet = $this->parseBetNumber($scheduleOrder['bet_number']);
			ApiLog('parseBetNumber:' . print_r($bet, true), 'opay');
			
			$bet = array_pop($bet);
			ApiLog('array pop :' . print_r($bet, true), 'opay');
			
			$stakeCount = count($bet);
			
			$devide_result = $this->devideOverMultipleTicket($uid, $lotteryId, $playType, $ticketSeq, $stakeCount, $multiple, $betType, $competitionInfo);
			if (empty($devide_result)) {
				return false;
			}
			$orderStakeCount += $stakeCount;
			
			$ticketSeq = $devide_result['ticket_seq'];
			$ticketList = array_merge($ticketList, $devide_result['printout_ticket_list']);
			$ticketData = array_merge($ticketData, $devide_result['ticket_data']);
		}
		
		ApiLog('stakCount:' . $orderStakeCount . '------' . print_r($ticketList, true), 'opay');
		
		return array(
				'stakeCount' => $orderStakeCount,
				'ticketList' => $ticketList,
				'ticketData' => $ticketData 
		);
	}

	private function _buildJcNoMixCompetitionInfoForPrintOut(array $scheduleOrders, array $scheduleInfos, $ticket_lottery_id){
		$ticketContent = array();
		$competition = array();
		foreach ($scheduleOrders as $scheduleOrder) {
			if(is_object($scheduleOrder)){
				$scheduleOrder = (array)$scheduleOrder;
			}
			$scheduleId = $scheduleOrder['schedule_id'];
			$endTime = $scheduleInfos[$scheduleId]['schedule_end_time'];
			$betNumber = $this->parseBetNumber($scheduleOrder['bet_number']);
			$scheduleIssueNo = $scheduleInfos[$scheduleId]['schedule_issue_no'];
			
			$all_ticket_issue_no[] = $scheduleIssueNo;
			
			foreach ($betNumber as $lotteryId => $bet) {
				sort($bet);
				$competitions[] = array(
						'lottery_id' => $lotteryId,
						'issue_no' => $scheduleIssueNo,
						'bet_options' => $bet 
				);
			}
			$ticketContent[] = array(
					'schedule_issue_no' => $scheduleIssueNo,
					'bet' => $betNumber,
					'schedule_end_time' => $endTime,
					'schedule_game_start_time' => $scheduleInfos[$scheduleId]['schedule_game_start_time'],
					'schedule_id' => $scheduleId 
			);
		}
		
		asort($all_ticket_issue_no);
		$ticket_issue_nos = implode(',', $all_ticket_issue_no);
		
		$schedule_range_info = $this->checkScheduleTimeRangeInfo($ticketContent);
		$last_schedule_in_ticket = $schedule_range_info['last_schedule_info'];
		$first_schedule_in_ticket = $schedule_range_info['first_schedule_info'];
		ApiLog('$last_schedule_info:' . print_r($last_schedule_in_ticket, true), 'opay');
		ApiLog('$$first_schedule_info:' . print_r($first_schedule_in_ticket, true), 'opay');
		
		return array(
				'ticket_lottery_id' => $ticket_lottery_id,
				// 'bet_type' => $betType,
				'ticket_issue_nos' => $ticket_issue_nos,
				'issue_no' => $last_schedule_in_ticket['schedule_issue_no'],
				'last_schedule_issue_id' => $last_schedule_in_ticket['schedule_id'],
				'last_schedule_issue_no' => $last_schedule_in_ticket['schedule_issue_no'],
				'last_schedule_end_time' => $last_schedule_in_ticket['schedule_end_time'],
				'first_schedule_issue_id' => $first_schedule_in_ticket['schedule_id'],
				'first_schedule_issue_no' => $first_schedule_in_ticket['schedule_issue_no'],
				'first_schedule_end_time' => $first_schedule_in_ticket['schedule_end_time'],
				'competition' => $competitions 
		);
	}

	private function _saveMultiStageTicket($uid, array $scheduleOrders, array $scheduleInfos, $playType, array $series, $lotteryId, $multiple){
		$verifyObj = Factory::createVerifyJcObj($lotteryId);
		$ticketSeq = 0;
		$ticketList = array();
		$ticketData = array();
		$orderStakeCount = 0;
		
		foreach ($series as $betType) {
			$maxSelectCount = $verifyObj->getMaxSeriesCount($scheduleOrders, $betType, $lotteryId);
			if (!$maxSelectCount) {
				$this->_throwExcepiton(C('ERROR_CODE.TICKET_ERROR'));
			}
			
			$scheduleCombinatorics = $verifyObj->getScheduleCombinatorics($scheduleOrders, $maxSelectCount);
			foreach ($scheduleCombinatorics as $scheduleCom) {
				$competitionInfo = $this->_buildJcNoMixCompetitionInfoForPrintOut($scheduleCom, $scheduleInfos, $lotteryId);
				ApiLog('$competitionInfo :' . print_r($competitionInfo, true), 'opay');
				ApiLog('$scheduleCom :' . print_r($scheduleCom, true), 'opay');
				
				$stakeCount = $verifyObj->getStakeCount($scheduleCom, $betType);
				ApiLog('$stakeCount :' . print_r($stakeCount, true), 'opay');
				
				$devide_result = $this->devideOverMultipleTicket($uid, $lotteryId, $playType, $ticketSeq, $stakeCount, $multiple, $betType, $competitionInfo);
				if (empty($devide_result)) {
					return false;
				}
				$orderStakeCount += $stakeCount;
				
				$ticketSeq = $devide_result['ticket_seq'];
				$ticketList = array_merge($ticketList, $devide_result['printout_ticket_list']);
				$ticketData = array_merge($ticketData, $devide_result['ticket_data']);
			}
		}
		
		ApiLog('stakCount:' . $orderStakeCount . '------' . print_r($ticketList, true), 'opay');
		ApiLog('stakCount:' . $orderStakeCount . '------' . print_r($ticketData, true), 'opay');
		
		return array(
				'stakeCount' => $orderStakeCount,
				'ticketList' => $ticketList,
				'ticketData' => $ticketData 
		);
	}

	private function _pay($uid, $scheme_id, $user_coupon_id, $scheme_data, $guarantee_amount = 0, $self_buy_amount = 0){
		$frozen_money = $guarantee_amount + $self_buy_amount;
		if ($user_coupon_id) {
			$user_coupon_balance = D('UserCoupon')->getCouponBalance($user_coupon_id);
			$is_bigger = ApiBcComp($user_coupon_balance, $frozen_money);
			if ($is_bigger) {
				$coupon_consume_amount = $frozen_money;
				$account_consume_amount = 0;
			} else {
				$coupon_consume_amount = $user_coupon_balance;
				$account_consume_amount = bcsub($frozen_money, $user_coupon_balance, 2);
			}
		} else {
			$account_consume_amount = $frozen_money;
		}
		
		$total_pay_amount = bcadd($coupon_consume_amount, $account_consume_amount);
		$amount_is_correct = bccomp($total_pay_amount, $frozen_money, 2) == 0; // 扣款数要等于订单价格，注意浮点数比较
		if (!$amount_is_correct) {
			ApiLog('$deductResult:' . $amount_is_correct, 'chase');
			$this->_throwExcepiton(C('ERROR_CODE.DEDUCT_MONEY_ERROR'));
		}
		
		if (!$guarantee_amount) {
			$buy_record_data['type'] = COBET_TYPE_OF_BOUGHT;
			$buy_record_data['record_bought_unit'] = $scheme_data['scheme_bought_unit'];
			$buy_record_data['record_bought_amount'] = $self_buy_amount;
			if ($coupon_consume_amount) {
				$buy_record_data['user_coupon_id'] = $user_coupon_id;
				$buy_record_data['record_user_coupon_consume_amount'] = $coupon_consume_amount;
				$buy_record_data['record_user_cash_amount'] = bcsub($self_buy_amount, $coupon_consume_amount, 2);
			} else {
				$buy_record_data['user_coupon_id'] = 0;
				$buy_record_data['record_user_coupon_consume_amount'] = 0;
				$buy_record_data['record_user_cash_amount'] = $self_buy_amount;
			}
		} elseif (!$self_buy_amount) {
			$guarantee_record_data['type'] = COBET_TYPE_OF_GUARANTEE_FROZEN;
			$guarantee_record_data['record_bought_unit'] = $scheme_data['scheme_guarantee_unit'];
			$guarantee_record_data['record_bought_amount'] = $guarantee_amount;
			if ($coupon_consume_amount) {
				$guarantee_record_data['user_coupon_id'] = $user_coupon_id;
				$guarantee_record_data['record_user_coupon_consume_amount'] = $coupon_consume_amount;
				$guarantee_record_data['record_user_cash_amount'] = bcsub($guarantee_amount, $coupon_consume_amount, 2);
			} else {
				$guarantee_record_data['user_coupon_id'] = 0;
				$guarantee_record_data['record_user_coupon_consume_amount'] = 0;
				$guarantee_record_data['record_user_cash_amount'] = $guarantee_amount;
			}
		} else {
			$buy_record_data['type'] = COBET_TYPE_OF_BOUGHT;
			$buy_record_data['record_bought_unit'] = $scheme_data['scheme_bought_unit'];
			$buy_record_data['record_bought_amount'] = $self_buy_amount;
			$guarantee_record_data['type'] = COBET_TYPE_OF_GUARANTEE_FROZEN;
			$guarantee_record_data['record_bought_unit'] = $scheme_data['scheme_guarantee_unit'];
			$guarantee_record_data['record_bought_amount'] = $guarantee_amount;
			
			$guarantee_record_data['record_bought_unit'] = $scheme_data['scheme_guarantee_unit'];
			$guarantee_record_data['record_bought_amount'] = $guarantee_amount;
			$buy_record_data['record_bought_unit'] = $scheme_data['scheme_bought_unit'];
			$buy_record_data['record_bought_amount'] = $self_buy_amount;
			if ($coupon_consume_amount) {
				$guarantee_record_data['user_coupon_id'] = $user_coupon_id;
				if (ApiBcComp($coupon_consume_amount, $guarantee_amount)) {
					$guarantee_record_data['record_user_coupon_consume_amount'] = $guarantee_amount;
					$guarantee_record_data['record_user_cash_amount'] = 0;
					$buy_record_data['record_user_coupon_consume_amount'] = bcsub($coupon_consume_amount, $guarantee_amount, 2);
					$buy_record_data['record_user_cash_amount'] = $account_consume_amount;
				} else {
					$guarantee_record_data['record_user_coupon_consume_amount'] = $coupon_consume_amount;
					$guarantee_record_data['record_user_cash_amount'] = bcsub($guarantee_amount, $coupon_consume_amount, 2);
					$buy_record_data['record_user_coupon_consume_amount'] = 0;
					$buy_record_data['record_user_cash_amount'] = bcsub($account_consume_amount, $guarantee_record_data['record_user_cash_amount'], 2);
				}
			} else {
				$guarantee_record_data['record_user_coupon_consume_amount'] = 0;
				$guarantee_record_data['record_user_cash_amount'] = $guarantee_amount;
				$buy_record_data['record_user_coupon_consume_amount'] = 0;
				$buy_record_data['record_user_cash_amount'] = $self_buy_amount;
			}
		}
		$remark = '';
		if ($guarantee_record_data) {
			if ($guarantee_record_data['record_user_coupon_consume_amount']) {
				$coupon_deduct_result = D('UserCoupon')->deductCouponBalance($uid, $user_coupon_id, $guarantee_record_data['record_user_coupon_consume_amount'], C('USER_COUPON_LOG_TYPE.COBET_GUARANTEE_FROZEN'), $remark);
				if ($coupon_deduct_result === false) {
					$this->_throwExcepiton(C('ERROR_CODE.USER_COUPON_ERROR'));
				}
			}
			if ($guarantee_record_data['record_user_cash_amount']) {
				$money_deduct_result = D('UserAccount')->deductMoney($uid, $guarantee_record_data['record_user_cash_amount'], $scheme_id, C('USER_ACCOUNT_LOG_TYPE.COBET_GUARANTEE_FROZEN'));
				ApiLog('deduct:' . $money_deduct_result . '====' . $account_consume_amount, 'chase');
				if ($money_deduct_result === false) {
					ApiLog('deduct false:' . $money_deduct_result . '====' . bcsub($frozen_money, $coupon_consume_amount, 2), 'chase');
					$this->_throwExcepiton(C('ERROR_CODE.INSUFFICIENT_FUND'));
				}
			}
			
			$add_guarantee_result = D('CobetRecord')->add($this->_buildCobetRecord($uid, $scheme_id, $guarantee_record_data));
			if (!$add_guarantee_result) {
				$this->_throwExcepiton(C('ERROR_CODE.DATABASE_ERROR'));
			}
		}
		if ($buy_record_data) {
			if ($buy_record_data['record_user_coupon_consume_amount']) {
				$coupon_deduct_result = D('UserCoupon')->deductCouponBalance($uid, $user_coupon_id, $buy_record_data['record_user_coupon_consume_amount'], C('USER_COUPON_LOG_TYPE.COBET_BOUGHT_FROZEN'), $remark);
				if ($coupon_deduct_result === false) {
					$this->_throwExcepiton(C('ERROR_CODE.USER_COUPON_ERROR'));
				}
			}
			if ($buy_record_data['record_user_cash_amount']) {
				$money_deduct_result = D('UserAccount')->deductMoney($uid, $buy_record_data['record_user_cash_amount'], $scheme_id, C('USER_ACCOUNT_LOG_TYPE.COBET_BOUGHT_FROZEN'));
				ApiLog('deduct:' . $money_deduct_result . '====' . $account_consume_amount, 'chase');
				if ($money_deduct_result === false) {
					ApiLog('deduct false:' . $money_deduct_result . '====' . bcsub($frozen_money, $coupon_consume_amount, 2), 'chase');
					$this->_throwExcepiton(C('ERROR_CODE.INSUFFICIENT_FUND'));
				}
			}
			$add_buy_result = D('CobetRecord')->add($this->_buildCobetRecord($uid, $scheme_id, $buy_record_data));
			if (!$add_buy_result) {
				$this->_throwExcepiton(C('ERROR_CODE.DATABASE_ERROR'));
			}
		}
		
		if ($buy_record_data || $guarantee_record_data) {
			return true;
		}
	}

	protected function buildBetScheduleListInTicket(array $ticket_schedule_list) {
		unset($ticket_schedule_list['bet_type']);
		$schedule_list = array();
		foreach ($ticket_schedule_list as $schedule_item) {
			$bet_number =  $schedule_item['lottery_id'].':'.implode(',', $schedule_item['bet_options']);
			$schedule_list[] = array(
					'schedule_id' => $schedule_item['schedule_id'],
					'bet_number' => $bet_number,
					'bet_contents' => array(
							$schedule_item['lottery_id'] => array(
									'bet_number_string' => $bet_number,
									'bet_options' => $schedule_item['bet_options']
							)
					)
			);
		}
		ApiLog('orders:'.print_r($schedule_list,true),'cobet');
		return $schedule_list;
	}
	
	protected function devideOverMultipleTicket($uid, $lotteryId, $playType, $ticketSeq, $ticket_stake_count, $order_multiple, $betType, $competitionInfo, $is_optimize_order=0){
		$first_schedule_issue_id_in_ticket = $competitionInfo['first_schedule_issue_id'];
		$first_schedule_issue_no_in_ticket = $competitionInfo['first_schedule_issue_no'];
		$first_schedule_end_time_in_ticket = $competitionInfo['first_schedule_end_time'];
		$last_schedule_issue_id_in_ticket = $competitionInfo['last_schedule_issue_id'];
		$last_schedule_issue_no_in_ticket = $competitionInfo['last_schedule_issue_no'];
		$last_schedule_end_time_in_ticket = $competitionInfo['last_schedule_end_time'];
		$competition = $competitionInfo['competition'];
	
		$once_ticket_amount = $ticket_stake_count * LOTTERY_PRICE;
		if ($once_ticket_amount > BET_TICKET_AMOUNT_LIMIT) {
			$this->_throwExcepiton(C('ERROR_CODE.OVER_TICKET_LIMIT'));
		}
	
		$max_multiple = getMaxMultipleByLotteryId($competitionInfo['ticket_lottery_id']);
		if($is_optimize_order){
			$total_ticket_multiple = $order_multiple * $ticket_stake_count;
		}else{
			$total_ticket_multiple = $order_multiple;
		}
	
		if ($total_ticket_multiple > $max_multiple) {
			$limit_multiple = $max_multiple;
		} else {
			$limit_multiple = $total_ticket_multiple;
		}
	
		$limit_ticket_amount = $once_ticket_amount * $limit_multiple;
		if ($limit_ticket_amount > BET_TICKET_AMOUNT_LIMIT) {
			$max_once_ticket_multiple = floor(BET_TICKET_AMOUNT_LIMIT / $once_ticket_amount);
			$once_ticket_multiple = $max_once_ticket_multiple;
		} else {
			$once_ticket_multiple = $limit_multiple;
		}
		$devide_ticket_num = ceil($total_ticket_multiple / $once_ticket_multiple);
	
		for($i = 0; $i < $devide_ticket_num; $i++) {
			if ($i == $devide_ticket_num - 1) {
				$ticket_multiple = $total_ticket_multiple - ($devide_ticket_num - 1) * $once_ticket_multiple;
			} else {
				$ticket_multiple = $once_ticket_multiple;
			}
			$ticket_amount = $once_ticket_amount * $ticket_multiple;
			$ticketSeq++;
			$ticket_lottery_id = $competitionInfo['ticket_lottery_id'];
			$ticketList[] = $this->buildCompetitionTicketItemForPrintout($ticketSeq, $playType, $betType, $ticket_stake_count, $ticket_amount, $competition, $last_schedule_end_time_in_ticket, $ticket_lottery_id, $ticket_multiple, $first_schedule_end_time_in_ticket,$first_schedule_issue_no_in_ticket,$last_schedule_issue_no_in_ticket);
	
			// add 'v' before option
			$formated_competition_infos = $this->formatBetOptionAddV($competition);
			$jsonCompetition = json_encode($formated_competition_infos);
	
			if($playType==JC_PLAY_TYPE_MULTI_STAGE){
				$issueNos = $competitionInfo['ticket_issue_nos'];
			}else{
				$issueNos = $competitionInfo['issue_no'];
			}
			if (!$issueNos) { return false; }
	
	
			$ticketData[] = D('JcTicket')->buildTicketData($uid, $ticketSeq, $playType, $ticket_stake_count, $betType, $jsonCompetition, $last_schedule_issue_id_in_ticket, $issueNos, $ticket_amount, $ticket_multiple, $first_schedule_issue_id_in_ticket, $competitionInfo['ticket_lottery_id']);
		}
	
		return array(
				'ticket_seq' => $ticketSeq,
				'ticket_data' => $ticketData,
				'printout_ticket_list' => $ticketList
		);
	}
	
	protected function buildCompetitionTicketItemForPrintout($ticketSeq, $playType, $betType, $stakeCount, $totalAmount, $competition, $last_schedule_end_time, $lotteryId, $ticket_multiple, $first_schedule_end_time,$first_issue_no,$last_issue_no){
		$printout_ticket_info = array(
				"ticket_seq" => $ticketSeq,
				"play_type" => intval($playType), // 玩法（标准、追加）
				"bet_type" => intval($betType), // 选号方式（单关、几串几）
				"stake_count" => intval($stakeCount),
				'ticket_multiple' => $ticket_multiple,
				"amount" => intval($totalAmount),
				'lottery_id' => $lotteryId,
				'last_game_time' => $last_schedule_end_time,
				'last_issue_no' => $last_issue_no,
				'first_issue_no' => $first_issue_no,
				'first_game_end_time' => $first_schedule_end_time
		);
	
		$printout_ticket_info['bet_content'] = array(
				'competition' => $competition
		);
		return $printout_ticket_info;
	}
	
	
	protected function formatBetOptionAddV(array $competitions) {
		$new_competitions = array();
		foreach ($competitions as $competition) {
			$competition['bet_options'] = betOptionAddV($competition['bet_options']);
			$new_competitions[] = $competition;
		}
		return $new_competitions;
	}
	
	protected function parseBetNumber($betNumber) {
		$bet_item_list	= array();
		$bet_content_list = explode('|', $betNumber);
		foreach ($bet_content_list as $bet_content) {
			$bet_content_info 	= explode(':', $bet_content);
			$betOptions = explode(',', $bet_content_info[1]);
			asort($betOptions);
			$bet_item_list[$bet_content_info[0]] = $betOptions;
		}
		return $bet_item_list;
	}
	

	protected function getLotteryIdByCompetition(array $competitions, $lotteryId) {
		$lotteryIds = array_column($competitions, 'lottery_id');
		$lotteryIds = array_unique($lotteryIds);
		// 是否为非混合 ? 非混合lotteryId : 混合lotteryId ;
		$id = (count($lotteryIds) == 1) ? $lotteryIds[0] : $lotteryId ;
		return intval($id);
	}
	
	public function completeCobetScheme($id){
		$scheme_info = D('CobetScheme')->getInfo($id);
		if (!in_array($scheme_info['scheme_status'], array(
				COBET_SCHEME_STATUS_OF_NO_BEGIN_BOUGHT,
				COBET_SCHEME_STATUS_OF_ONGOING
		))) {
			A('CobetOrder')->notifyWarningMsg('状态异常:' . $scheme_info['scheme_id']);
			ApiLog('状态异常:' . print_r($scheme_info, true), 'completeCobetScheme');
			return false;
		}
	
		if (!$this->_isEnoughForSchemeMoney($scheme_info)) {
			A('CobetOrder')->notifyWarningMsg('金额非法:' . $scheme_info['scheme_id']);
			ApiLog('金额非法:' . print_r($scheme_info, true), 'completeCobetScheme');
			return false;
		}
	
		$refund_code = $this->_refundGuaranteeAmount($scheme_info);
		if (!$refund_code) {
			A('CobetOrder')->notifyWarningMsg('退款失败:' . $scheme_info['scheme_id']);
			ApiLog('退款失败:' . print_r($scheme_info, true), 'completeCobetScheme');
			return false;
		}
	
		return true;
	}
	
	private function _isEnoughForSchemeMoney($scheme_info){
		$bought_amount = bcmul($scheme_info['scheme_bought_unit'], $scheme_info['scheme_amount_per_unit']);
		if (bccomp($bought_amount, $scheme_info['scheme_total_amount']) != 0) {
			return false;
		}
	
		$bought_total_amount = D('Crontab/CobetRecord')->getBoughtTotalAmount($scheme_info['scheme_id'], array(
				C('COBET_TYPE.BOUGHT')
		));
		$bought_total_unit = D('Crontab/CobetRecord')->getBoughtTotalUnit($scheme_info['scheme_id'], array(
				C('COBET_TYPE.BOUGHT')
		));
		if (bccomp($bought_total_amount, $scheme_info['scheme_total_amount']) != 0) {
			return false;
		}
	
		if ($bought_total_unit != $scheme_info['scheme_total_unit']) {
			return false;
		}
	
		return true;
	}
	
	private function _refundGuaranteeAmount($scheme_info){
		$guarantee_frozen_info = D('Crontab/CobetRecord')->getGuaranteeFrozenInfoBySchemeId($scheme_info['scheme_id']);
		if(!empty($guarantee_frozen_info)){
            $refund_cash_amount = $guarantee_frozen_info['record_user_cash_amount'];
            $refund_coupon_amount = $guarantee_frozen_info['record_user_coupon_consume_amount'];
            $user_coupon_id = $guarantee_frozen_info['user_coupon_id'];

            // 退回保底的剩余金额
            $refund_guarantee_amount = bcmul($scheme_info['scheme_guarantee_unit'], $scheme_info['scheme_amount_per_unit']);
            if (bccomp($refund_guarantee_amount, ($refund_coupon_amount + $refund_cash_amount)) != 0) {
                A('CobetOrder')->notifyWarningMsg('退回保底金额算错$scheme_info:' . $scheme_info['scheme_id']);
                ApiLog('退回保底金额算错$scheme_info:' . print_r($scheme_info, true), 'completeCobetScheme');
                return false;
            }

            M()->startTrans();

            $user_account_log_type = C('USER_ACCOUNT_LOG_TYPE.COBET_GUARANTEE_REFUND');
            $refund_code = A('CobetOrder')->refundAmount($scheme_info['uid'], $user_coupon_id, $refund_coupon_amount, $refund_cash_amount, $user_account_log_type);
            if (!$refund_code) {
                A('CobetOrder')->notifyWarningMsg('uid:' . $scheme_info['uid'] . '当前退保底错误$record:' . $refund_guarantee_amount['record_id']);
                ApiLog('uid:' . $scheme_info['uid'] . '当前退保底错误$record:' . print_r($scheme_info, true), 'completeCobetScheme');
                M()->rollback();
                return false;
            }

            $save_status = D('Crontab/CobetRecord')->saveRefundStatus($guarantee_frozen_info['record_id'], $guarantee_frozen_info['record_bought_amount'], $guarantee_frozen_info['record_bought_unit'], COBET_STATUS_OF_REFUND);
            if (!$save_status) {
                A('CobetOrder')->notifyWarningMsg('保存记录失败$scheme_info:' . print_r($scheme_info, true));
                ApiLog('sql:' . D('Crontab/CobetRecord')->getLastSql(), 'completeCobetScheme');
                ApiLog('保存记录失败$scheme_info:' . print_r($scheme_info, true), 'completeCobetScheme');
                M()->rollback();
                return false;
            }
        }

		$change_code = D('Crontab/CobetScheme')->changeSchemeStatusById($scheme_info['scheme_id'], C('COBET_SCHEME_STATUS.SCHEME_COMPLETE'));
		if (!$change_code) {
			ApiLog('$change_code:' . $change_code . '$scheme_info:' . print_r($scheme_info, true), 'completeCobetScheme');
			M()->rollback();
		}
	
		M()->commit();
	
		return true;
	}
	
}