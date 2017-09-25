<?php
namespace Home\Controller;
// use Home\Controller\GlobalController;
use Home\Controller\BettingBaseController;
use Home\Util\Factory;

class BetController extends BettingBaseController {
	
	public function payUnpaidOrder($api) {
        $userInfo 	= $this->getAvailableUser($api->session);
        $uid 		= $userInfo['uid'];
        
        $orderInfo 	= D('Order')->getOrderInfo($api->order_id);
        if(!$orderInfo){
        	$this->_throwExcepiton(C('ERROR_CODE.ORDER_NO_EXIST'));
        }

        $is_limit = $this->isLimitLottery($orderInfo['lottery_id']);
        if($is_limit){
            $this->_throwExcepiton(C('ERROR_CODE.ISSUE_NO_EXIST'));
        }
        
        $is_owner = ($orderInfo['uid'] == $uid);
        if(!$is_owner){
        	$this->_throwExcepiton(C('ERROR_CODE.ORDER_OWEN_ERROR'));
        }
        
        if(isJCLottery($orderInfo['lottery_id'])){
        	$totalPayMoney  = $orderInfo['order_total_amount'];
        }else{
            $fbi_info = D('FollowBetInfo')->getFollowInfoByOrderId($api->order_id);
            if($fbi_info) {
                $total_pay_money = $fbi_info['follow_total_amount'];
                ApiLog('$total_pay_money:'.$total_pay_money,'chase');
            }
            $fbiId = $fbi_info['fbi_id'];

            /*
        	$followInfo = D('FollowBet')->getFollowBetInfo($orderInfo['follow_bet_id']);
        	$followTimes = ( $followInfo['follow_remain_times'] ? ($followInfo['follow_remain_times']+1) : 1 );
        	$totalPayMoney  = $orderInfo['order_total_amount'] * $followTimes;
        	ApiLog('$total_pay_money:'.$totalPayMoney,'chase');*/
        	 
        }
        $remain = $this->getRemainMoney($uid, $api->coupon_id,$orderInfo['lottery_id'],$totalPayMoney,$orderInfo['order_total_amount']);
		if($remain<0) {
			return $this->buildResponseForPayOrder($api->order_id, $orderInfo['order_sku'], abs($remain), C('ERROR_CODE.INSUFFICIENT_FUND'));
		}
        
        $passwordFree = $this->checkPasswordFree($uid, $orderInfo['order_total_amount'],
            $userInfo['user_pre_order_limit'], $userInfo['user_pre_day_limit'], $userInfo['user_password_free'], $userInfo['user_payment_password']);
        
        if(!$passwordFree && !$api->recharge_order_id) {
            $checkPayPassword = D('User')->checkPayPassword($uid, $api->pay_passwd, $userInfo['user_payment_password'], $userInfo['user_payment_salt']);
            if(!$checkPayPassword){
            	$this->_throwExcepiton(C('ERROR_CODE.PAY_PASSWORD_ERROR'));
            }
        }
        
        $this->_verifyRecharge($uid, $api->recharge_order_id);
        $unpaidStatus = array(C('ORDER_STATUS.UNPAID'), C('ORDER_STATUS.BET_ERROR'));
        $unpaid = in_array($orderInfo['order_status'], $unpaidStatus);
        if(!$unpaid){
        	$this->_throwExcepiton(C('ERROR_CODE.ORDER_STATUS_ERROR'));
        }
        
        $ticketList 	= $this->getTicketListForPrintoutByOrderId($orderInfo['lottery_id'], $orderInfo['issue_id'], $api->order_id, $uid);
        if(!$ticketList){
        	$this->_throwExcepiton(C('ERROR_CODE.TICKET_ERROR'));
        }
        
        $issueNo 		= $this->queryIssueNoByIssueId($orderInfo['lottery_id'], $orderInfo['issue_id']);
        $first_issue_no = $this->queryIssueNoByIssueId($orderInfo['lottery_id'], $orderInfo['first_issue_id']);
        
        $printOutResult = $this->printOutTicket($userInfo, $issueNo, $api->order_id, $orderInfo['lottery_id'], $ticketList, $orderInfo['order_multiple'], $first_issue_no);
        ApiLog('$$printOutResult:'.$printOutResult,'opay');
        if(!$printOutResult){
        	$this->_throwExcepiton(C('ERROR_CODE.BET_ERROR'));
        }
        
        $this->payOrderWithTransaction($uid, $api->order_id, $api->coupon_id, $orderInfo['order_total_amount'], $fbiId);
        return array(   'result' => '',
                        'code'   => C('ERROR_CODE.SUCCESS'));
	}

	private function _verifyRecharge($uid, $rechargeId) {
		if (!$rechargeId) {
			return true;
		}
		$rechargeInfo = D('Recharge')->getRechargeInfo($rechargeId);
		if(!$rechargeInfo){
			$this->_throwExcepiton(C('ERROR_CODE.RECHARGE_NO_EXIST'));
		}
		$rechargeSuccess = ($rechargeInfo['recharge_status'] == C('RECHARGE_STATUS.PAID'));
		if(!$rechargeSuccess){
			$this->_throwExcepiton(C('ERROR_CODE.RECHARGE_NO_RECEIVE'));
		}
		$is_owner = ($rechargeInfo['uid'] == $uid);
		if(!$is_owner){
			$this->_throwExcepiton(C('ERROR_CODE.RECHARGE_OWEN_ERROR'));
		}
	}
	
	
	public function submitOrder($params){
		$user_info = $this->getAvailableUser($params->session);
		$lottery_info = D('Lottery')->getLotteryInfo($params->lottery_id);
		if(empty($lottery_info)){
			$this->_throwExcepiton(C('ERROR_CODE.LOTTERY_NO_EXIST'));
		}

        $is_limit = $this->isLimitLottery($params->lottery_id);
        if($is_limit){
            $this->_throwExcepiton(C('ERROR_CODE.ISSUE_NO_EXIST'));
        }

		$lottery_obj_instance = $this->_getLotteryInstance($params->lottery_id);
		$verified_params = $lottery_obj_instance->verifyParams($params, $user_info);
		if(!$verified_params){
			$this->_throwExcepiton(C('ERROR_CODE.PARAM_ERROR'));
		}
		$lottery_id = $verified_params['lottery_id'];
		$order_total_amount = $verified_params['total_amount'];
		$uid = $user_info['uid'];
		//check balance
		$money_to_be_paid = $this->getRemainMoney($uid, $verified_params['user_coupon_id'], $lottery_id ,$order_total_amount,$order_total_amount);
		if($money_to_be_paid<0) {
			return $this->buildResponseForPayOrder(0, '', abs($money_to_be_paid), C('ERROR_CODE.INSUFFICIENT_FUND'));
		}
		$passwordFree = $this->checkPasswordFree($uid, $order_total_amount, $user_info['user_pre_order_limit'], $user_info['user_pre_day_limit'], $user_info['user_password_free'], $user_info['user_payment_password']);
		if(!$passwordFree){
			return $this->buildResponseForPayOrder(0, '', 0, C('ERROR_CODE.NEED_PAY_PASSWORD'));
		}
		
		$exist_order_info = D('Order')->getOrderIdByIdentity($verified_params['order_identity']);
		if ($exist_order_info) {
			if ($exist_order_info['order_status'] > C('ORDER_STATUS.UNPAID')) {
				return $this->buildResponseForPayOrder($exist_order_info['order_id'], $exist_order_info['order_sku'], 0, C('ERROR_CODE.SUCCESS'));
			} elseif ($exist_order_info['order_status'] == C('ORDER_STATUS.UNPAID')) {
				$order_total_amount = $exist_order_info['order_total_amount'];
				$money_to_be_paid = $this->getRemainMoney($uid, $verified_params['user_coupon_id'],$exist_order_info['lottery_id'], $order_total_amount,$order_total_amount);
				ApiLog('add remain:'.$money_to_be_paid, 'opay');
				if ($money_to_be_paid < 0) {
					return $this->buildResponseForPayOrder($exist_order_info['order_id'], $exist_order_info['order_sku'], abs($money_to_be_paid), C('ERROR_CODE.INSUFFICIENT_FUND'));
				}
				$order_id = intval($exist_order_info['order_id']);
				$order_sku = $exist_order_info['order_sku'];
				ApiLog('get ticket list:'.$exist_order_info['lottery_id'].'==='.print_r($exist_order_info,true), 'opay');
				$printout_ticket_list = $this->getTicketListForPrintoutByOrderId($exist_order_info['lottery_id'], $exist_order_info['issue_id'], $exist_order_info['order_id'], $uid);
				if(!$printout_ticket_list){
					$this->_throwExcepiton(C('ERROR_CODE.TICKET_ERROR'));
				}
			}
		}else{
			$order_total_amount = $verified_params['order_total_amount'];
			
			$money_to_be_paid = $this->getRemainMoney($uid, $verified_params['user_coupon_id'], $lottery_id ,$order_total_amount,$order_total_amount);
			if($money_to_be_paid<0) {
				return $this->buildResponseForPayOrder(0, '', abs($money_to_be_paid), C('ERROR_CODE.INSUFFICIENT_FUND'));
			}
			$order_sku = buildOrderSku($uid);
			$add_result = $lottery_obj_instance->addOrderAndTicketData($uid, $order_sku, $verified_params);
			if(!$add_result){
				$this->_throwExcepiton(C('ERROR_CODE.DATABASE_ERROR'));
			}
			$order_id = $add_result['order_id'];
			$printout_ticket_list = $add_result['printout_ticket_list'];
			$issue_no = $add_result['issue_no'];
			$money_to_be_paid = $this->getRemainMoney($uid, $verified_params['user_coupon_id'], $lottery_id ,$order_total_amount,$order_total_amount);
			if($money_to_be_paid<0) {
				return $this->buildResponseForPayOrder($order_id, $order_sku, abs($money_to_be_paid), C('ERROR_CODE.INSUFFICIENT_FUND'));
			}
		}
		if($passwordFree) {
			$printout_result = $this->printOutTicket($user_info, $verified_params['issue_no'], $order_id, $verified_params['lottery_id'], $printout_ticket_list, $verified_params['order_multiple']);
			ApiLog('print_out:'.print_r($printout_result,true), 'opay');
			if($printout_result){
				ApiLog('commit:'.print_r($printout_result,true), 'opay');
			}else{
				ApiLog('rollback:'.print_r($printout_result,true), 'opay');
				$this->_throwExcepiton(C('ERROR_CODE.BET_ERROR'));
			}
			$pay_result = $this->payOrderWithTransaction($user_info['uid'], $order_id, $verified_params['user_coupon_id'], $order_total_amount, 0);
			if($pay_result){
				$code =  C('ERROR_CODE.SUCCESS');
			}
		}
		return $this->buildResponseForPayOrder($order_id, $order_sku, 0, $code);
	}
	
	private function _getLotteryInstance($lottery_id){
		ApiLog('$lottery_id:'.$lottery_id, 'sfc');
		$lottery_prefix = 'SFC';
		if($lottery_id){
			return A($lottery_prefix,'Lottery');
		}
	}

	private function _getSzcFollowInfo($api){
        if(empty($api->follow_detail)){
            if(empty($api->suite_id)){
                //普通下单
                $issueId = $api->issue_id;
                $multiple = $api->multiple;
                $follow_times = $api->follow_times;
            }else{
                //追号套餐
                $packagesInfo = D('LotteryPackage')->getPackagesInfoById($api->suite_id);
                if(empty($packagesInfo)){
                    $this->_throwExcepiton(C('ERROR_CODE.PACKAGES_NO_EXIST'));
                }
                $issueId = $api->issue_id;
                $multiple = $packagesInfo['lp_multiple'];
                $follow_times = $packagesInfo['lp_issue_num'];
                if($packagesInfo['lottery_id']!=$api->lottery_id){
                    $this->_throwExcepiton(C('ERROR_CODE.ISSUE_NO_EXIST'));
                }
                $checkPackagesCode = $this->checkPackagesTicket($api->tickets,$packagesInfo);
                if($checkPackagesCode!=C('ERROR_CODE.SUCCESS')){
                    $this->_throwExcepiton($checkPackagesCode);
                }
            }
            $orderTotalAmount = $this->calcSzcOrderTotalAmountForOneTime($api->tickets, $multiple);
            $follow_detail = $this->buildFollowDetails($api->follow_detail,$follow_times,$orderTotalAmount,$multiple);
            $win_stop_amount = 0;
        }else{
            //智能追号
            $follow_detail = $api->follow_detail;
            $multiple = $follow_detail[0]['multiple'];
            $issueId = $follow_detail[0]['issue_id'];
            $follow_times = $api->follow_times;
            $win_stop_amount = $api->win_stop_amount;
            if(count($follow_detail) != $follow_times){
                $this->_throwExcepiton(C('ERROR_CODE.TICKET_ERROR'));
            }

            $check_code = $this->checkFollowDetail($api->tickets,$api->follow_detail);
            if($check_code != C('ERROR_CODE.SUCCESS')){
                $this->_throwExcepiton($check_code);
            }

        }
        return array(
            'follow_detail' => $follow_detail,
            'multiple' => $multiple,
            'issueId' => $issueId,
            'follow_times' => $follow_times,
            'win_stop_amount' => $win_stop_amount,
            'is_independent' => empty($api->is_independent) ? 0 : $api->is_independent,
        );
    }
	
	# act 10501 , 数字彩下注接口
	public function addOrder($api) {
		$userInfo 	= $this->getAvailableUser($api->session);
        $szcFollowInfo = $this->_getSzcFollowInfo($api);
        $issueId = $szcFollowInfo['issueId'];
        $follow_times = $szcFollowInfo['follow_times'];
        $multiple = $szcFollowInfo['multiple'];
        $follow_detail = $szcFollowInfo['follow_detail'];
        $win_stop_amount = $szcFollowInfo['win_stop_amount'];
        $is_independent = $szcFollowInfo['is_independent'];
		$issueInfo 	= D('Issue')->getIssueInfo($issueId);
		if(empty($issueInfo)){
			$this->_throwExcepiton(C('ERROR_CODE.ISSUE_NO_EXIST'));
		}
		$lotteryInfo = D('Lottery')->getLotteryInfo($issueInfo['lottery_id']);
		if(empty($lotteryInfo)){
			$this->_throwExcepiton(C('ERROR_CODE.LOTTERY_NO_EXIST'));
		}

        $is_limit = $this->isLimitLottery($issueInfo['lottery_id']);
        if($is_limit){
            $this->_throwExcepiton(C('ERROR_CODE.ISSUE_NO_EXIST'));
        }

		$uid = $userInfo['uid'];
	
		$beforeDeadline = ( strtotime($issueInfo['issue_end_time']) - time() > $lotteryInfo['lottery_ahead_endtime'] );
		if(!$beforeDeadline){
			$this->_throwExcepiton(C('ERROR_CODE.OUT_OF_ISSUE_TIME'));
		}

		$limitBetCode = $this->limitBetNum($api->issue_id,$issueInfo['lottery_id'],$api->tickets);
		if($limitBetCode!=C('ERROR_CODE.SUCCESS')){
            $this->_throwExcepiton($limitBetCode);
        }
		$checkTicketsCode = $this->checkNumberTickets($api->tickets, $issueInfo['lottery_id']);
		if($checkTicketsCode!=C('ERROR_CODE.SUCCESS')){
			$this->_throwExcepiton($checkTicketsCode);
		}
		
		$existOrder = D('Order')->getOrderIdByIdentity($api->order_identity);
		if ($existOrder) {
			if ($existOrder['order_status'] > C('ORDER_STATUS.UNPAID')) {
				return $this->rebuildResponseForPayOrder($uid, $existOrder, $api->coupon_id, $follow_times,$existOrder['lottery_id']);
			} elseif ($existOrder['order_status'] == C('ORDER_STATUS.UNPAID')) {
				ApiLog('$$existOrder:'.print_r($existOrder,true),'chase');
				
				$total_pay_money = $existOrder['order_total_amount'];
                $fbi_info = D('FollowBetInfo')->getFollowInfoByOrderId($existOrder['order_id']);
                if($fbi_info) {
                    $total_pay_money = $fbi_info['follow_total_amount'];
                    ApiLog('$total_pay_money:'.$total_pay_money,'chase');
                }

                $fbiId = $fbi_info['fbi_id'];
				
				$remain = $this->getRemainMoney($uid, $api->coupon_id,$issueInfo['lottery_id'], $total_pay_money,$existOrder['order_total_amount'],$api->suite_id);
				if ($remain < 0) {
					return $this->buildResponseForPayOrder($existOrder['order_id'], $existOrder['order_sku'], abs($remain), C('ERROR_CODE.INSUFFICIENT_FUND'));
				}
				$orderId = intval($existOrder['order_id']);
				$orderSku = $existOrder['order_sku'];
				ApiLog('addJcOrder exist order info:' . $existOrder['lottery_id'] . '===' . print_r($existOrder, true), 'opay');
				$ticketList = $this->getTicketListForPrintoutByOrderId($existOrder['lottery_id'], $existOrder['issue_id'], $existOrder['order_id'], $uid);
				if(!$ticketList){
					$this->_throwExcepiton(C('ERROR_CODE.TICKET_ERROR'));
				}
				
				$issueNo = $this->queryIssueNoByIssueId($existOrder['lottery_id'], $existOrder['issue_id']);
			}
		}else{
			$orderTotalAmount = $this->calcSzcOrderTotalAmountForOneTime($api->tickets, $multiple);
            //检查金额是否足够
            $totalPayMoney = $this->calcTotalPayAmount($api->tickets,$follow_detail,$api->suite_id);
            if(empty($totalPayMoney)){
                $this->_throwExcepiton(C('ERROR_CODE.DATABASE_ERROR'));
            }
			$remain = $this->getRemainMoney($uid, $api->coupon_id, $issueInfo['lottery_id'] ,$totalPayMoney,$orderTotalAmount,$api->suite_id);
			if($remain<0) {
				return $this->buildResponseForPayOrder(0, '', abs($remain), C('ERROR_CODE.INSUFFICIENT_FUND'));
			}	
			
			$orderSku = buildOrderSku($uid);
			
			$orderTicket = $this->addSzcOrderAndTicketsForNewFollow($issueInfo['lottery_id'], $uid, $orderTotalAmount,
					$orderSku, $issueId, $multiple, $api->coupon_id, $api->tickets, $follow_times, $api->order_identity,$follow_detail,$api->is_win_stop,$api->suite_id,$is_independent,$win_stop_amount);
			if (!$orderTicket) {
				$this->_throwExcepiton(C('ERROR_CODE.DATABASE_ERROR'));
			}
			$orderId 	 = $orderTicket['orderId'];
			$ticketList  = $orderTicket['ticketList'];
			$fbiId = $orderTicket['fbiId'];
			$totalPayMoney  = $this->calcTotalPayAmount($api->tickets,$follow_detail);
			$remain = $this->getRemainMoney($uid, $api->coupon_id, $issueInfo['lottery_id'], $totalPayMoney,$orderTotalAmount,$api->suite_id);
			
			if($remain<0) {
				return $this->buildResponseForPayOrder($orderId, $orderSku, abs($remain), C('ERROR_CODE.INSUFFICIENT_FUND'));
			}
		}
		
		$passwordFree = $this->checkPasswordFree($uid, $totalPayMoney, $userInfo['user_pre_order_limit'],
				$userInfo['user_pre_day_limit'], $userInfo['user_password_free'], $userInfo['user_payment_password']);
	
		if($passwordFree) {
			$printOutResult = $this->printOutTicket($userInfo, $issueInfo['issue_no'], $orderId, $issueInfo['lottery_id'], $ticketList, $multiple);
			ApiLog('print_out:'.print_r($printOutResult,true), 'opay');
			if($printOutResult){
				ApiLog('commit:'.print_r($printOutResult,true), 'opay');
			}else{
				ApiLog('rollback:'.print_r($printOutResult,true), 'opay');
				$this->_throwExcepiton(C('ERROR_CODE.BET_ERROR'));
			}
			ApiLog('begin payOrderWithTransaction:', 'opay');
			$this->payOrderWithTransaction($uid, $orderId, $api->coupon_id, $orderTotalAmount, $fbiId);
		}
		$code = ( $passwordFree ? C('ERROR_CODE.SUCCESS') : C('ERROR_CODE.NEED_PAY_PASSWORD') );
		return $this->buildResponseForPayOrder($orderId, $orderSku, 0, $code,$fbiId);
	}
	
	public function addJcOrder($api){
		$userInfo = $this->getAvailableUser($api->session);
		$uid = $userInfo['uid'];
		$existOrder = D('Order')->getOrderIdByIdentity($api->order_identity);

		$lottery_id = empty($api->lottery_id) ? $existOrder['lottery_id'] : $api->lottery_id;
        $is_limit = $this->isLimitLottery($lottery_id);
        if($is_limit){
            $this->_throwExcepiton(C('ERROR_CODE.ISSUE_NO_EXIST'));
        }

		if ($existOrder) {
			if ($existOrder['order_status'] > C('ORDER_STATUS.UNPAID')) {
				return $this->buildResponseForPayOrder($existOrder['order_id'], $existOrder['order_sku'], 0, C('ERROR_CODE.SUCCESS'));
			} elseif ($existOrder['order_status'] == C('ORDER_STATUS.UNPAID')) {
				$orderTotalAmount = $existOrder['order_total_amount'];
				$remain = $this->getRemainMoney($uid, $api->coupon_id,$existOrder['lottery_id'], $orderTotalAmount,$orderTotalAmount);
				ApiLog('add remain:'.$remain, 'opay');
				if ($remain < 0) {
					return $this->buildResponseForPayOrder($existOrder['order_id'], $existOrder['order_sku'], abs($remain), C('ERROR_CODE.INSUFFICIENT_FUND'));
				}
				$orderId = intval($existOrder['order_id']);
				$orderSku = $existOrder['order_sku'];
				ApiLog('get ticket list:'.$existOrder['lottery_id'].'==='.print_r($existOrder,true), 'opay');
				$ticketList = $this->getTicketListForPrintoutByOrderId($existOrder['lottery_id'], $existOrder['issue_id'], $existOrder['order_id'], $uid);
				if(!$ticketList){
					$this->_throwExcepiton(C('ERROR_CODE.TICKET_ERROR'));
				}
				
				$issueNo = $this->queryIssueNoByIssueId($existOrder['lottery_id'], $existOrder['issue_id']);
				$first_issue_no = $this->queryIssueNoByIssueId($existOrder['lottery_id'], $existOrder['first_issue_id']);
			}
		} else {
			//检查金额是否足够
			$orderTotalAmount = $api->total_amount;
			$remain = $this->getRemainMoney($uid, $api->coupon_id,$api->lottery_id, $orderTotalAmount,$orderTotalAmount);
			if($remain<0) {
				return $this->buildResponseForPayOrder(0, '', abs($remain), C('ERROR_CODE.INSUFFICIENT_FUND'));
			}
			
			$orderSku = buildOrderSku($uid);
			$orderTicket = $this->_addJCOrderAndTicket($uid, $orderSku, $api);
			$orderId = $orderTicket['orderId'];
			$ticketList = $orderTicket['ticketList'];
			$issueNo = $orderTicket['issueNo'];
			$first_issue_no = $orderTicket['firstIssueNo'];
			
			if (!$orderTicket) {
				ApiLog('$orderTic1111kets:', 'opay');
				$this->_throwExcepiton(C('ERROR_CODE.TICKET_ERROR'));
			}
			ApiLog('$orderTickets:' . count($orderTicket), 'opay');
			
			$orderTotalAmount = $api->total_amount;
			$remain = $this->getRemainMoney($uid, $api->coupon_id,$api->lottery_id, $orderTotalAmount,$orderTotalAmount);
			if ($remain < 0) {
				return $this->buildResponseForPayOrder($orderId, $orderSku, abs($remain), C('ERROR_CODE.INSUFFICIENT_FUND'));
			}
		}
		
		$passwordFree = $this->checkPasswordFree($uid, $orderTotalAmount, $userInfo['user_pre_order_limit'], $userInfo['user_pre_day_limit'], $userInfo['user_password_free'], $userInfo['user_payment_password']);
		
		if ($passwordFree) {
			// TODO 失败重试机制
			$printOutResult = $this->printOutTicket($userInfo, $issueNo, $orderId, $api->lottery_id, $ticketList, $api->multiple,$first_issue_no);
			ApiLog('jc print_out :' . ($printOutResult), 'opay');
			if(!$printOutResult){
				$this->_throwExcepiton(C('ERROR_CODE.BET_ERROR'));
			}
			ApiLog('jc begin payOrderWithTransaction', 'opay');
			$this->payOrderWithTransaction($uid, $orderId, $api->coupon_id, $orderTotalAmount);
		}
		$code = ($passwordFree ? C('ERROR_CODE.SUCCESS') : C('ERROR_CODE.NEED_PAY_PASSWORD'));
		
		return $this->buildResponseForPayOrder($orderId, $orderSku, 0, $code);
	}
	
	private function _addJCOrderAndTicket($uid, $orderSku, $params) {
		$verifyObj	= Factory::createVerifyJcObj($params->lottery_id);
		$isVaildBetNumber = $verifyObj->checkCompetitionTickets($params->schedule_orders, $params->series);
		ApiLog('isvalid:'.$isVaildBetNumber, 'opay');
		if(!$isVaildBetNumber){
			$this->_throwExcepiton(C('ERROR_CODE.BET_NUMBER_ERROR'));
		}
		if (isJcMix($params->lottery_id)) {
			$formated_schedule_orders = $this->formatRequestScheduleOrders($params->schedule_orders);
			$tickets_from_combination = $verifyObj->convertScheduleOrderToTickets($formated_schedule_orders, $params->series, $params->lottery_id);
// 			ApiLog('$tickets_from_combination:'.empty($tickets_from_combination).'----'.count($tickets_from_combination), 'opay');
			if (empty($tickets_from_combination)) {
				return false;
			} elseif (count($tickets_from_combination) > 2000) {
				return false;
			}
			return $this->_addJcMixtureOrder($uid, $orderSku, $params, $tickets_from_combination);
		} else {
			return $this->_addJcNoMixtureOrder($uid, $orderSku, $params);
		}
	}
	
    private function _addJcMixtureOrder($uid, $orderSku, $params, $tickets_from_combination) {
    	$series 	= explode(',', $params->series);
    	$jcTicketInfo = $this->_buildJcMixtureTicketInfo($uid, $params->schedule_orders, $tickets_from_combination,
    			$params->stake_count, $params->total_amount, $params->multiple, $params->lottery_id);
    	if(empty($jcTicketInfo)){
    		$this->_throwExcepiton(C('ERROR_CODE.DATABASE_ERROR'));
    	}
    	
    	$orderTicket = $this->_addJzOrderSchedule($params->lottery_id, $uid, $params->total_amount,
    			$orderSku, $params->multiple, $params->coupon_id, $params->schedule_orders, $jcTicketInfo, $params->order_identity, $params);
    	
    	return $orderTicket;
    }
    
    private function _buildJcMixtureTicketInfo($uid, array $scheduleOrders, array $tickets_from_combination, $stakeCount, $totalAmount, $multiple, $lotteryId) {
    	$schedule_ids_in_order 	= array_column($scheduleOrders, 'schedule_id');
    	ApiLog('ids:'.print_r($schedule_ids_in_order,true) ,'opay');
    	$schedule_infos_in_order = D('JcSchedule')->getScheduleIssueNo($schedule_ids_in_order);
//     	ApiLog('mix schedule_ids:'.print_r($schedule_infos_in_order,true), 'bet_jc');
    	if(!$schedule_infos_in_order){
    		$this->_throwExcepiton(C('ERROR_CODE.SCHEDULE_NO_ERROR'));
    	}
    	
    	$lottery_info = D('Lottery')->getLotteryInfo($lotteryId);
    	$this->checkScheduleOutOfTime($schedule_infos_in_order, $lottery_info);
    	
    	$schedule_ids_of_all_lottery = array();
    	foreach($schedule_infos_in_order as $schedule_id=>$scheduleInfo){
    		$schedule_end_time_unix_timestamp = strtotime($scheduleInfo['schedule_end_time']);
    		$scheduleInfo['schedule_end_time_unix_timestamp'] = $schedule_end_time_unix_timestamp;
    		$mix_schedule_id = $scheduleInfo['schedule_id'];
    		$day = $scheduleInfo['schedule_day'];
    		$week = $scheduleInfo['schedule_week'];
    		$round_no = $scheduleInfo['schedule_round_no'];
    		
    		$schedule_ids_of_all_lottery[$mix_schedule_id] = D('JcSchedule')->queryAllScheduleIdsFromScheduleNo($day,$week,$round_no);
    		$new_schedule_infos_in_order[$schedule_id] = $scheduleInfo;
    	}
    	ApiLog('new in order:'.print_r($new_schedule_infos_in_order,true), 'opay');
    	
		$stageTicket = $this->_saveJcMixtureTicket($uid, $tickets_from_combination, $new_schedule_infos_in_order, $lotteryId, $schedule_ids_of_all_lottery, $multiple);
    	$this->verifyStakeCountAndTotalAmountByOrder($stageTicket, $stakeCount, $multiple, $totalAmount);

    	$schedule_range_info = $this->checkScheduleTimeRangeInfo($new_schedule_infos_in_order);
    	$last_schedule_info_in_order = $schedule_range_info['last_schedule_info'];
    	$first_schedule_info_in_order = $schedule_range_info['first_schedule_info'];
    	
    	ApiLog('$schedule_range_info:'.print_r($schedule_range_info,true), 'opay');
    	 
    	if ($stageTicket) {
    		return array(
    			'lastSchedule' => $last_schedule_info_in_order,
    			'firstSchedule' => $first_schedule_info_in_order,
    			'stageTicket'  => $stageTicket,
    		);
    	} else {
    		return false;
    	}
    }
    
    private function _saveJcMixtureTicket($uid, array $tickets_from_combination, array $scheduleInfos, $lotteryId, $schedule_ids_of_all_lottery, $multiple) {
    	$orderStakeCount = 0;
    	$ticketSeq 		 = 0;
    	$ticketData = $ticketList = array();
    	$verifyObj 		 = Factory::createVerifyJcObj($lotteryId);
    	ApiLog('tickets:'.print_r($tickets_from_combination,true),'opay');
    	foreach ($tickets_from_combination as $ticket) {
    		$competitionInfo = $this->_buildJcMixCompetitionInfoForPrintOut($ticket, $scheduleInfos, $lotteryId, $schedule_ids_of_all_lottery);
    		if(!$competitionInfo){
    			$this->_throwExcepiton(C('ERROR_CODE.SCHEDULE_NO_ERROR'));
    		}
    		$competition	 = $competitionInfo['competition'];
    		
    		$betType = $ticket['bet_type'];
			$ticket_schedule_list = $this->buildBetScheduleListInTicket($ticket);
    		
			$stakeCount = $verifyObj->getStakeCount($ticket_schedule_list, $betType);
    		
    		ApiLog('aaaa:'.$betType.'==='.$stakeCount.'==='.print_r($ticket_schedule_list,true),'opay');
    		
    		$devide_result = $this->devideOverMultipleTicket($uid, $lotteryId, JC_PLAY_TYPE_MULTI_STAGE, $ticketSeq, $stakeCount, $multiple, $betType, $competitionInfo);
    		$orderStakeCount += $stakeCount;
    		
    		$ticketSeq = $devide_result['ticket_seq'];
    		$ticketList = array_merge($ticketList,$devide_result['printout_ticket_list']);
    		$ticketData = array_merge($ticketData,$devide_result['ticket_data']);
    	}
    	 
    	return array(
    			'stakeCount' => $orderStakeCount,
    			'ticketList' => $ticketList,
    			'ticketData' => $ticketData,
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
			$ticketList[] = $this->buildCompetitionTicketItemForPrintout($ticketSeq, $playType, $betType, $stakeCount, $ticket_amount, $competition, $last_schedule_end_time_in_ticket, $ticket_lottery_id, $ticket_multiple, $first_schedule_end_time_in_ticket,$first_schedule_issue_no_in_ticket,$last_schedule_issue_no_in_ticket);
			
			// add 'v' before option
			$formated_competition_infos = $this->formatBetOptionAddV($competition);
			$jsonCompetition = json_encode($formated_competition_infos);
			
			if($playType==JC_PLAY_TYPE_MULTI_STAGE){
				$issueNos = $competitionInfo['ticket_issue_nos'];
			}else{
				$issueNos = $competitionInfo['issue_no'];
			}
			if (!$issueNos) { return false; }
			
			
			$ticketData[] = D('JcTicket')->buildTicketData($uid, $ticketSeq, $playType, $stakeCount, $betType, $jsonCompetition, $last_schedule_issue_id_in_ticket, $issueNos, $ticket_amount, $ticket_multiple, $first_schedule_issue_id_in_ticket, $competitionInfo['ticket_lottery_id']);
		}
		
		return array(
				'ticket_seq' => $ticketSeq,
				'ticket_data' => $ticketData,
				'printout_ticket_list' => $ticketList 
		);
	}
    

    private function _buildJcMixCompetitionInfoForPrintOut(array $ticket_item, array $scheduleInfos, $lotteryId,$schedule_ids_of_all_lottery) {
    	$competitions = array();
    	$ticket_content = $ticket_item;
    	$betType 	  = $ticket_content['bet_type'];
    	unset($ticket_content['bet_type']);
    	$ticket_lottery_id = $this->getLotteryIdByCompetition($ticket_content, $lotteryId);
    	$is_jc_mix_lottery_id = isJcMix($ticket_lottery_id);
    	
    	$last_schedule_game_start_time = 0;
    	$first_schedule_end_time = 0;
    	foreach ($ticket_content as $bet_schedule) {
    		if($is_jc_mix_lottery_id){
    			$scheduleId = $bet_schedule['schedule_id'];
    			$issueNo 	= $scheduleInfos[$scheduleId]['schedule_issue_no'];
    			$ticket_schedule_info =  $scheduleInfos[$scheduleId];
    			$competition_lottery_id = $bet_schedule['lottery_id'];
    			if(!isset($schedule_ids_of_all_lottery[$scheduleId][$competition_lottery_id])){
    				ApiLog('no exist:'.$competition_lottery_id.'===='.$scheduleId.'===='.print_r($scheduleInfos,true), 'csq');
    			}
    		}else{
    			$orig_schedule_id = $bet_schedule['schedule_id'];
    			$orig_schedule_info = $scheduleInfos[$orig_schedule_id];
    			$play_type = $orig_schedule_info['play_type']; 
//     			ApiLog('orig_schedule_info:'.print_r($orig_schedule_info,true), 'com');
    			$ticket_schedule_info = $schedule_ids_of_all_lottery[$orig_schedule_id][$ticket_lottery_id][$play_type];
//     			ApiLog('covert ticket schedule info:'.print_r($ticket_schedule_info,true), 'com');
    			$scheduleId = $ticket_schedule_info['schedule_id'];
    			$issueNo 	= $ticket_schedule_info['schedule_issue_no'];
    			$competition_lottery_id = $ticket_lottery_id;
    		}
    		
    		if(empty($issueNo)){
    			//查不到issueno 报警
    			ApiLog('$scheduleInfos[$orig_schedule_id]:'.print_r($scheduleInfos[$orig_schedule_id],true), 'opay');
    			ApiLog('$$$ticket_schedule_info:'.$orig_schedule_id.'=='.$ticket_lottery_id.'=='.$play_type.'=='.print_r($ticket_schedule_info,true), 'opay');
    			ApiLog('$$$schedule_ids_of_all_lottery:'.$issueNo.'====='.print_r($schedule_ids_of_all_lottery,true), 'opay');
    			ApiLog('$$all_ticket_issue_no:'.$issueNo.'====='.print_r($schedule_ids_of_all_lottery[$orig_schedule_id],true), 'opay');
    			return false;
    		}
    		
//     		$competition['schedule_id'] = $scheduleId;
    		$competition['lottery_id'] = $competition_lottery_id;
    		$competition['bet_options'] = $bet_schedule['bet_options'];
    		$competition['issue_no'] = $issueNo;
    		
//     		ApiLog('competion:'.print_r($competition,true), 'com');
    		
    		$competitions[] = $competition;
    		
    		$all_ticket_issue_no[] = $issueNo;
    		
    		$schedule_end_time_stamp = $ticket_schedule_info['schedule_end_time_unix_timestamp'];
    		$schedule_game_start_time_stamp = strtotime($ticket_schedule_info['schedule_game_start_time']);;
    		if ($schedule_game_start_time_stamp >= $last_schedule_game_start_time) {
    			$last_schedule_game_start_time = $schedule_game_start_time_stamp;
    			$last_schedule_in_ticket = $ticket_schedule_info;
    		}
    		
    		if($first_schedule_end_time==0){
    			$first_schedule_end_time = $schedule_end_time_stamp;
    		}
    		if ($schedule_end_time_stamp <= $first_schedule_end_time) {
    			ApiLog('build ====mix :'.$first_schedule_end_time.'=-==='.print_r($ticket_schedule_info,true),'opay');
    			$first_schedule_end_time = $schedule_end_time_stamp;
    			$first_schedule_in_ticket = $ticket_schedule_info;
    		}
    	}
    	ApiLog('build mix :'.$first_schedule_end_time.'==='.print_r($first_schedule_in_ticket,true), 'opay');
    	asort($all_ticket_issue_no);
    	$ticket_issue_nos = implode(',', $all_ticket_issue_no);
    	
		return array(
				'ticket_lottery_id'=>$ticket_lottery_id,
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
    
    private function _addJcNoMixtureOrder($uid, $orderSku, $params) {
    	$playType 	= C('MAPPINT_JC_PLAY_TYPE.'.$params->play_type);
    	$series 	= explode(',', $params->series);
    	
    	$jcTicketInfo = $this->_buildJzTicketInfo($params->schedule_orders, $uid, $playType,
    			$series, $params->lottery_id, $params->stake_count, $params->total_amount, $params->multiple);
    	\AppException::ifNoExistThrowException($jcTicketInfo, C('ERROR_CODE.DATABASE_ERROR'));
    	
    	$orderTicket = $this->_addJzOrderSchedule($params->lottery_id, $uid, $params->total_amount,
    			$orderSku, $params->multiple, $params->coupon_id, $params->schedule_orders, $jcTicketInfo, $params->order_identity,$params);
    	return $orderTicket;
    }

    private function _addJzOrderSchedule($lotteryId, $uid, $totalAmount, $orderSku, $multiple, $couponId, array $scheduleOrders, array $jcTicketInfo, $identity, $request_params) {
    	$orderTotalAmount = $totalAmount ;
    	$lastSchedule = $jcTicketInfo['lastSchedule'];
    	$firstSchedule = $jcTicketInfo['firstSchedule'];
    	$stageTicket  = $jcTicketInfo['stageTicket'];
    	$order_params['play_type'] = $request_params->play_type; 
    	$order_params['series'] = $request_params->series; 
    	$order_params['order_type'] = intval($request_params->order_type); 
    	$order_params['content'] = json_encode($request_params->schedule_orders);
    	 
    	M()->startTrans();
    	$orderId = D('Order')->addOrder($uid, $orderTotalAmount, $lastSchedule['schedule_id'], $multiple, $couponId, $lotteryId, $orderSku, $firstSchedule['schedule_id'], $identity,0,0,'',$order_params);
    	$model	 = getTicktModel($lotteryId);
    	if(!$orderId || !$model) {
    		M()->rollback();
    		return false;
    	}
    	$ticketDatas = $model->appendOrderId($stageTicket['ticketData'], $orderId);
    	
    	$addTickets_result  = $model->insertAll($ticketDatas);
    	if(!$addTickets_result) {
    		ApiLog('$addTickets:'.$addTickets_result, 'opay');
    		M()->rollback();
    		return false;
    	}
    	$orderDetails = $this->_getJcOrderDetail($scheduleOrders, $orderId);
    	$addDetail_result = D('JcOrderDetail')->insertAll($orderDetails);
    	if(!$addDetail_result) {
    		ApiLog('after $$addDetail_result:'.$addDetail_result, 'opay');
    		M()->rollback();
    		return false;
    	}
    	M()->commit();
    	 
    	return array(
    			'orderId' 	 => $orderId,
    			'issueNo' 	 => $lastSchedule['schedule_issue_no'],
    			'firstIssueNo' 	 => $firstSchedule['schedule_issue_no'],
    			'ticketList' => $stageTicket['ticketList'],
    	);
    }
    
    
    private function _getJcOrderDetail($scheduleOrders, $orderId) {
    	$orderDetails = array();
    	foreach ($scheduleOrders as $scheduleOrder) {
    		$betNumbers = $this->parseBetNumber($scheduleOrder['bet_number']);
    		$betNumbers = betOptionsAddV($betNumbers);
    		$betContent = json_encode($betNumbers);
    		$orderDetails[] = D('JcOrderDetail')->buildDetailData($orderId, $scheduleOrder['schedule_id'], $betContent, $scheduleOrder['is_sure']);
    	}
    	return $orderDetails;
    }
    
    private function _buildJzTicketInfo(array $scheduleOrders, $uid, $playType, array $series, $lotteryId, $stakeCount, $totalAmount, $multiple) {
//     	$scheduleIds 	= array_column($scheduleOrders, 'schedule_id');
    	foreach($scheduleOrders as $scheduleOrder){
    		$scheduleIds[] = $scheduleOrder['schedule_id'];
    	}
    	ApiLog('schedule ids:'.print_r($scheduleOrders,true).'==='.print_r($scheduleIds,true), 'opay');
    	$scheduleInfos 	= D('JcSchedule')->getScheduleIssueNo($scheduleIds);
    	ApiLog('schedule info:'.print_r($scheduleInfos,true), 'opay');
    	if(!$scheduleInfos){
    		$this->_throwExcepiton(C('ERROR_CODE.SCHEDULE_NO_ERROR'));
    	}
    	
		$lottery_info = D('Lottery')->getLotteryInfo($lotteryId);
    	$this->checkScheduleOutOfTime($scheduleInfos, $lottery_info);
    	
    	$schedule_range_info = $this->checkScheduleTimeRangeInfo($scheduleInfos);
    	$lastSchedule = $schedule_range_info['last_schedule_info'];
    	$firstSchedule = $schedule_range_info['first_schedule_info'];
    	
//     	$lastSchedule 	= $this->_getLatestSchedule($scheduleInfos);
//     	$firstSchedule = $this->_getFirstSchedule($scheduleInfos);
    	ApiLog('$firstSchedule info:'.print_r($firstSchedule,true), 'opay');
    	ApiLog('$$scheduleInfos info:'.print_r($scheduleInfos,true), 'opay');
    	 
    	
    	if ($playType == C('JC_PLAY_TYPE.ONE_STAGE')) {	// 如果是单关
    		$stageTicket = $this->_saveOneStageTicket($uid, $scheduleOrders, $scheduleInfos, $playType, $series, $lotteryId, $multiple);
    	} elseif ($playType == C('JC_PLAY_TYPE.MULTI_STAGE')) {
    		$stageTicket = $this->_saveMultiStageTicket($uid, $scheduleOrders, $scheduleInfos, $playType, $series, $lotteryId, $multiple);
    	}
    	$this->verifyStakeCountAndTotalAmountByOrder($stageTicket, $stakeCount, $multiple, $totalAmount);
    	
    	if ($stageTicket) {
    		ApiLog('sss:'.print_r($lastSchedule,true).'==='.print_r($stageTicket,true), 'opay');
    		return array(
    				'lastSchedule' => $lastSchedule,
    				'firstSchedule' => $firstSchedule,
    				'stageTicket'  => $stageTicket,
    		);
    	} else {
    		return false;
    	}
    }
    
    
    private function _saveOneStageTicket($uid, array $scheduleOrders, array $scheduleInfos, $playType, array $series, $lotteryId, $multiple) {
    	$betType 	= $series[0];
    	$ticketSeq 	= 0;
    	$ticketList = array();
    	$ticketData = array();
    	$orderStakeCount = 0;
    	foreach ($scheduleOrders as $scheduleOrder) {
    		$competitionInfo = $this->_buildJcNoMixCompetitionInfoForPrintOut(array($scheduleOrder), $scheduleInfos, $lotteryId);
    		$bet = $this->parseBetNumber($scheduleOrder['bet_number']);
    		ApiLog('parseBetNumber:'.print_r($bet,true), 'opay');
    		
    		$bet = array_pop($bet);
    		ApiLog('array pop :'.print_r($bet,true), 'opay');
    		
    		$stakeCount = count($bet);
    		
    		$devide_result = $this->devideOverMultipleTicket($uid, $lotteryId, $playType, $ticketSeq, $stakeCount, $multiple, $betType, $competitionInfo);
    		if(empty($devide_result)){
    			return false;
    		}
    		$orderStakeCount += $stakeCount;
    		
    		$ticketSeq = $devide_result['ticket_seq'];
    		$ticketList = array_merge($ticketList,$devide_result['printout_ticket_list']);
    		$ticketData = array_merge($ticketData,$devide_result['ticket_data']);
    	}
    	
    	ApiLog('stakCount:'.$orderStakeCount.'------'.print_r($ticketList,true), 'opay');
    	
    	return array(
    		'stakeCount' => $orderStakeCount,
    		'ticketList' => $ticketList,
    		'ticketData' => $ticketData,
    	);
    }
    
    private function _buildJcNoMixCompetitionInfoForPrintOut(array $scheduleOrders, array $scheduleInfos, $ticket_lottery_id) {
    	$ticketContent 	= array();
    	$competition 	= array();
    	foreach ($scheduleOrders as $scheduleOrder) {
    		$scheduleId = $scheduleOrder['schedule_id'];
    		$endTime 	= $scheduleInfos[$scheduleId]['schedule_end_time'];
    		$betNumber 	= $this->parseBetNumber($scheduleOrder['bet_number']);
    		$scheduleIssueNo = $scheduleInfos[$scheduleId]['schedule_issue_no'];

    		$all_ticket_issue_no[] = $scheduleIssueNo;

    		foreach ($betNumber as $lotteryId=>$bet) {
    			sort($bet);
    			$competitions[] = array(
    					'lottery_id' 	=> $lotteryId,
    					'issue_no' 		=> $scheduleIssueNo,
    					'bet_options' 	=> $bet,
    			);
    		}
    		$ticketContent[] = array(	'schedule_issue_no'	=> $scheduleIssueNo,
    				'bet' 				=> $betNumber,
    				'schedule_end_time'	=> $endTime,
    				'schedule_game_start_time'	=> $scheduleInfos[$scheduleId]['schedule_game_start_time'],
    				'schedule_id'		=> $scheduleId, );
    	}

    	asort($all_ticket_issue_no);
    	$ticket_issue_nos = implode(',', $all_ticket_issue_no);

    	$schedule_range_info = $this->checkScheduleTimeRangeInfo($ticketContent);
    	$last_schedule_in_ticket = $schedule_range_info['last_schedule_info'];
    	$first_schedule_in_ticket = $schedule_range_info['first_schedule_info'];
    	ApiLog('$last_schedule_info:'.print_r($last_schedule_in_ticket,true), 'opay');
    	ApiLog('$$first_schedule_info:'.print_r($first_schedule_in_ticket,true), 'opay');
    
    	return array(
    			'ticket_lottery_id'=>$ticket_lottery_id,
//     			'bet_type' => $betType,
    			'ticket_issue_nos' => $ticket_issue_nos,
    			'issue_no'		 => $last_schedule_in_ticket['schedule_issue_no'],
    			'last_schedule_issue_id' => $last_schedule_in_ticket['schedule_id'],
    			'last_schedule_issue_no' => $last_schedule_in_ticket['schedule_issue_no'],
    			'last_schedule_end_time' => $last_schedule_in_ticket['schedule_end_time'],
    			'first_schedule_issue_id' => $first_schedule_in_ticket['schedule_id'],
    			'first_schedule_issue_no' => $first_schedule_in_ticket['schedule_issue_no'],
    			'first_schedule_end_time' => $first_schedule_in_ticket['schedule_end_time'],
    			'competition'	 => $competitions,
    	);
    	
    }
    
    private function _saveMultiStageTicket($uid, array $scheduleOrders, array $scheduleInfos, $playType, array $series, $lotteryId, $multiple) {
    	$verifyObj 	= Factory::createVerifyJcObj($lotteryId);
    	$ticketSeq 	= 0;
    	$ticketList = array();
    	$ticketData = array();
    	$orderStakeCount = 0;
    	
    	foreach ($series as $betType) {
    		$maxSelectCount = $verifyObj->getMaxSeriesCount($scheduleOrders, $betType, $lotteryId);
    		if(!$maxSelectCount){
    			$this->_throwExcepiton(C('ERROR_CODE.TICKET_ERROR'));
    		}
    		
    		$scheduleCombinatorics = $verifyObj->getScheduleCombinatorics($scheduleOrders, $maxSelectCount);
    		foreach ($scheduleCombinatorics as $scheduleCom) {
    			$competitionInfo = $this->_buildJcNoMixCompetitionInfoForPrintOut($scheduleCom, $scheduleInfos, $lotteryId);
    			ApiLog('$competitionInfo :'.print_r($competitionInfo,true), 'opay');
    			ApiLog('$scheduleCom :'.print_r($scheduleCom,true), 'opay');
    			 
    			$stakeCount = $verifyObj->getStakeCount($scheduleCom, $betType);
    			ApiLog('$stakeCount :'.print_r($stakeCount,true), 'opay');

    			$devide_result = $this->devideOverMultipleTicket($uid, $lotteryId, $playType, $ticketSeq, $stakeCount, $multiple, $betType, $competitionInfo);
    			if(empty($devide_result)){
	    			return false;
	    		}
	    		$orderStakeCount += $stakeCount;
	    		
	    		$ticketSeq = $devide_result['ticket_seq'];
	    		$ticketList = array_merge($ticketList,$devide_result['printout_ticket_list']);
	    		$ticketData = array_merge($ticketData,$devide_result['ticket_data']);
    		}
    	}
    	
    	ApiLog('stakCount:'.$orderStakeCount.'------'.print_r($ticketList,true), 'opay');
    	ApiLog('stakCount:'.$orderStakeCount.'------'.print_r($ticketData,true), 'opay');
    	 
    	return array(
    		'stakeCount' => $orderStakeCount,
    		'ticketList' => $ticketList,
    		'ticketData' => $ticketData,
    	);
    }

}