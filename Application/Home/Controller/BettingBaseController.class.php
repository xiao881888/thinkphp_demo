<?php

namespace Home\Controller;

use Home\Controller\GlobalController;
use Home\Util\Factory;

class BettingBaseController extends GlobalController{
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
	
	protected function addSzcOrderAndTickets($lotteryId, $uid, $orderTotalAmount, $orderSku, $issueId, $multiple, $couponId, $tickets, $userFollowTimes, $identity){
		M()->startTrans();
		
		$extra_params['content'] = json_encode($tickets);
		$orderId = D('Order')->addOrder($uid, $orderTotalAmount, $issueId, $multiple, $couponId, $lotteryId, $orderSku, $issueId, $identity,0,0,'',$extra_params);

		if (!$orderId) {
			M()->rollback();
			return false;
		}
	
		$followBetId = $this->_saveFollow($lotteryId, $issueId, $orderId, $userFollowTimes);
		if ($followBetId === false) {
			M()->rollback();
			return false;
		}
	
		$ticketList = $this->_saveTicket($lotteryId, $uid, $orderId, $issueId, $tickets, $multiple);
		if (!$ticketList) {
			M()->rollback();
			return false;
		}
	
		M()->commit();
	
		return array(
				'orderId' => $orderId,
				'ticketList' => $ticketList,
				'followBetId' => $followBetId
		);
	}
	
	private function _saveFollow($lotteryId, $issueId, $orderId, $userFollowTimes){
		$followTimes = $userFollowTimes - 1;
		if ($followTimes > 0) {
			$followBetId = $this->_addFollowBet($lotteryId, $issueId, $followTimes, $orderId);
		} else {
			$followBetId = 0;
		}
		return $followBetId;
	}
	
	private function _addFollowBet($lotteryId, $issueId, $followTimes, $orderId){
		$followBetId = D('FollowBet')->addFollowBet($lotteryId, $issueId, $followTimes, $orderId);
		if (empty($followBetId)) {
			return false;
		}
	
		$saveFollow = D('Order')->saveFollowBetId($orderId, $followBetId);
		if ($saveFollow === false) {
			return false;
		}
		return $followBetId;
	}
	
	private function _saveTicket($lotteryId, $uid, $orderId, $issueId, array $tickets, $multiple){
		$ticketSeq = 0;
		$ticketList = array();
		$verifyNumber = Factory::createVerifyObj($lotteryId);
		$ticketsData = array();
		$ticketModel = getTicktModel($lotteryId);
		$max_multiple = getMaxMultipleByLotteryId($lotteryId);
	
		foreach ($tickets as $ticket) {
			$once_ticket_amount = $ticket['total_amount']; // $stakeCount * LOTTERY_PRICE;
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
				// 11选5 任选八 复式
				if ($ticket['play_type'] == SYXW_PLAY_TYPE_OF_RENXUAN8 && $ticket['bet_type'] == BET_TYPE_OF_FUXUAN) {
					$number_arr = explode(',', $ticket['bet_number']);
					$select_number = C('SYXW_SELECT_COUNT.' . $ticket['play_type']);
					import('@.Util.Combinatorics');
					$mathCombinatorics = new \Math_Combinatorics();
					$renxuan8_ticket_list = $mathCombinatorics->combinations($number_arr, $select_number);
					foreach ($renxuan8_ticket_list as $renxuan8_ticket_info) {
						$bet_number = implode(',', $renxuan8_ticket_info);
						$stake_count = 1;
						$ticket_amount = LOTTERY_PRICE * $stake_count * $ticket_multiple;
						$bet_type = BET_TYPE_OF_DASHI;
						$ticketSeq++;
						$sortBetNumber = $verifyNumber->formatBetNumber($bet_number, $ticket['play_type']);
						$ticketsData[] = $ticketModel->buildTicketData($uid, $issueId, $sortBetNumber, $ticket['play_type'], $stake_count, $ticket_amount, $orderId, $ticketSeq, $bet_type, $ticket_multiple, $issueId, $lotteryId);
						$ticketList[] = $this->buildNumberTicketItemForPrintout($ticketSeq, $ticket['play_type'], $bet_type, $sortBetNumber, 1, $ticket_amount, $ticket_multiple);
					}
				} else {
					$ticket_amount = $once_ticket_amount * $ticket_multiple;
					$ticketSeq++;
					$sortBetNumber = $verifyNumber->formatBetNumber($ticket['bet_number'], $ticket['play_type']);
					$ticketsData[] = $ticketModel->buildTicketData($uid, $issueId, $sortBetNumber, $ticket['play_type'], $ticket['stake_count'], $ticket_amount, $orderId, $ticketSeq, $ticket['bet_type'], $ticket_multiple, $issueId, $lotteryId);
					$ticketList[] = $this->buildNumberTicketItemForPrintout($ticketSeq, $ticket['play_type'], $ticket['bet_type'], $sortBetNumber, $ticket['stake_count'], $ticket_amount, $ticket_multiple);
				}
			}
		}
	
		$addResult = $ticketModel->addAll($ticketsData);
		return ($addResult ? $ticketList : false);
	}
	
	
	protected function rebuildResponseForPayOrder($uid, $existOrder, $couponId, $followTimes,$lottery_id) {
        $fbi_info = D('FollowBetInfo')->getFollowInfoByOrderId($existOrder['order_id']);
        if($fbi_info) {
            $totalPayMoney = $fbi_info['follow_total_amount'];
        }else{
            $totalPayMoney  = $existOrder['order_total_amount'] * $followTimes;
        }
		$remain = $this->getRemainMoney($uid, $couponId,$lottery_id,$totalPayMoney,$existOrder['order_total_amount']);
		ApiLog('remain:'.$remain, 'opay');
		$remain = ( $remain < 0 ? abs($remain) : 0 );
		$code = $remain ? C('ERROR_CODE.INSUFFICIENT_FUND') : C('ERROR_CODE.SUCCESS') ;
		return $this->buildResponseForPayOrder($existOrder['order_id'], $existOrder['order_sku'], 0, $code);
	}

    protected function limitBetNum($issue_id,$lottery_id,array $tickets){
        /*if(!$this->_limitFC3D($lottery_id,$tickets)){
            return C('ERROR_CODE.BET_NUMBER_ERROR');
        }*/
	    $issue_limit_no_list = D('IssueLimitNo')->getIssueLimitNoListByLotteryId($lottery_id);
        foreach ( $tickets as $ticket ) {
            $is_limit_bet_no = $this->_isLimitBetNo($issue_limit_no_list,$issue_id,$ticket['play_type'],$ticket['bet_number']);
            if($is_limit_bet_no){
                return C('ERROR_CODE.BET_NUMBER_ERROR');
            }
        }
        return C('ERROR_CODE.SUCCESS');
    }

    private function  _limitFC3D($lottery_id,array $tickets){
        if($lottery_id == 2){
            foreach ( $tickets as $ticket ) {
                if($ticket['play_type'] == '11'){
                    $ticket_arr = explode('#',$ticket['bet_number']);
                    $ticket_arr[0] = explode(',',$ticket_arr[0]);
                    $ticket_arr[1] = explode(',',$ticket_arr[1]);
                    $ticket_arr[2] = explode(',',$ticket_arr[2]);
                    if(in_array(5,$ticket_arr[0])||in_array(6,$ticket_arr[0])||in_array(7,$ticket_arr[0])){
                        if(in_array(5,$ticket_arr[1])||in_array(6,$ticket_arr[1])||in_array(7,$ticket_arr[1])){
                            if(in_array(5,$ticket_arr[2])||in_array(6,$ticket_arr[2])||in_array(7,$ticket_arr[2])){
                                return false;
                            }
                        }
                    }
                }else{
                    $ticket_arr = explode(',',$ticket['bet_number']);
                    if(in_array(5,$ticket_arr)&&in_array(6,$ticket_arr)&&in_array(7,$ticket_arr)){
                        return false;
                    }
                }
            }
        }
        return true;

    }

    private function _isLimitBetNo($issue_limit_no_list,$issue_id,$play_type,$bet_number){
        foreach($issue_limit_no_list as $issue_limit_no_info){
            if(empty($issue_limit_no_info['issue_id']) && empty($issue_limit_no_info['play_type']) && empty($issue_limit_no_info['bet_number'])){
                continue;
            }
            $limit_issue_id = empty($issue_limit_no_info['issue_id']) ? $issue_id : $issue_limit_no_info['issue_id'];
            $limit_play_type = empty($issue_limit_no_info['play_type']) ? $play_type : $issue_limit_no_info['play_type'];
            $limit_bet_num_list = empty($issue_limit_no_info['bet_number']) ? array($bet_number) : explode(',',$issue_limit_no_info['bet_number']);
            if($issue_id == $limit_issue_id && $play_type == $limit_play_type){
                foreach ($limit_bet_num_list as $limit_bet_num){
                    if(strpos($bet_number,$limit_bet_num) === false){
                        return false;
                    }
                }
                return true;
            }
        }
        return false;
    }
	
	protected function checkNumberTickets(array $tickets, $lotteryId) {
		$verifyNumber = Factory::createVerifyObj($lotteryId);
		foreach ( $tickets as $ticket ) {
            if($ticket['total_amount'] <= 0){
                return C('ERROR_CODE.TICKET_ERROR');
            }

			$isFormatValid = $verifyNumber->verify($ticket['bet_number'], $ticket['play_type'], $ticket['bet_type']);
	
			if(!$isFormatValid) {
				return C('ERROR_CODE.BET_NUMBER_ERROR');
			}
	
			$quantity = $verifyNumber->getTicketQuantity($ticket['bet_number'], $ticket['play_type'], $ticket['bet_type']);
			if(!$quantity) {
				return C('ERROR_CODE.TICKET_ERROR');
			}
			$vailBetType = $verifyNumber->checkBetType($ticket['bet_number'], $quantity, $ticket['bet_type'], $ticket['play_type']);
			if(!$vailBetType) {
				return C('ERROR_CODE.TICKET_ERROR');
			}
	
			$isStakeCountConsistent = ( $quantity == $ticket['stake_count'] );
			if(!$isStakeCountConsistent) {
				return C('ERROR_CODE.STAKE_COUNT_NO_EQUAL');
			}
	
			$lotteryPrice = ( $ticket['play_type']==2 ? C('LOTTERY_ADD_PRICE') : C('LOTTERY_PRICE') );
			$amountAccordance = (bccomp($quantity*$lotteryPrice, $ticket['total_amount'], 2) == 0);
			if(!$amountAccordance) {
				return C('ERROR_CODE.TOTAL_AMOUNT_NO_EQUAL');
			}
		}
		return C('ERROR_CODE.SUCCESS');
	}

	protected function buildResponseForPayOrder($orderId, $orderSku, $amount, $code,$fbi_id = 0){
        if($fbi_id){
            $result['follow_order_id'] = $fbi_id;
        }
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
		ApiLog('get reamin:'.$couponBalance.'===='.$userBalance, 'opay');
		return ($userBalance + $couponBalance - $totalPayMoney);
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

	private function _getSelectedCouponBalanceBak($uid, $couponId) {
		if($couponId) {
			$is_owner = D('UserCoupon')->owenedByUser($uid, $couponId);
			if(!$is_owner){
				$this->_throwExcepiton(C('ERROR_CODE.USER_COUPON_ERROR'));
			}
			return D('UserCoupon')->getCouponBalance($couponId);
		} else {
			return 0;
		}
	}
	
	protected function getTicketListForPrintoutByOrderId($lotteryId, $issueId, $orderId, $uid){
		ApiLog('lottery:' . $lotteryId . '===' . $issueId . '==' . $orderId . '====' . $uid, 'opay');
		$ticketModel = getTicktModel($lotteryId);
		$tickets = $ticketModel->getTicketsByOrderId($orderId, $uid);
		ApiLog('$tickets:' . print_r($tickets,true), 'opay');
		
		$printout_ticket_list = array();
		if (isJCLottery($lotteryId)) {
			$first_issue_ids = array_unique(array_column($tickets, 'first_issue_id'));
			$last_issue_ids = array_unique(array_column($tickets, 'last_issue_id'));
				
			ApiLog('$$first_issue_ids:' . print_r($first_issue_ids,true), 'opay');
				
			$unique_issue_ids = array_unique(array_merge($first_issue_ids,$last_issue_ids));
			$scheduleInfos = D('JcSchedule')->getScheduleIssueNo($unique_issue_ids);
			ApiLog('schedule:' . M()->_sql(), 'opay');
			ApiLog('schedule:' . print_r($scheduleInfos, true), 'opay');
			if(empty($scheduleInfos)){
				$this->_throwExcepiton(C('ERROR_CODE.SCHEDULE_NO_ERROR'));
				return false;
			}
			
			//如果超过投注时间也要退出
			$lottery_info = D('Lottery')->getLotteryInfo($lotteryId);
			$this->checkScheduleOutOfTime($scheduleInfos, $lottery_info);
			ApiLog('schedule info:' . print_r($scheduleInfos, true), 'opay');
			
			foreach ($tickets as $ticket) {
				$competition = $this->_formatPrintOutTicketCompetitions($ticket['ticket_content']);
				ApiLog('$competition string:' . $competition, 'opay');
				ApiLog('$ticket:' . print_r($ticket,true).'==='.print_r($scheduleInfos,true), 'opay');
				
				$lastScheduleId = $ticket['last_issue_id'];
				$lastGameTime = $scheduleInfos[$lastScheduleId]['schedule_game_start_time'];
				$scheduleLotteryId = $scheduleInfos[$lastScheduleId]['lottery_id'];
				$printout_ticket_list[] = $this->buildCompetitionTicketItemForPrintout($ticket['ticket_seq'], $ticket['play_type'], $ticket['bet_type'], $ticket['stake_count'], $ticket['total_amount'], $competition, $lastGameTime, $scheduleLotteryId, $ticket['ticket_multiple'],$scheduleInfos[$ticket['first_issue_id']]['schedule_end_time'],$scheduleInfos[$ticket['first_issue_id']]['schedule_issue_no'],$scheduleInfos[$ticket['last_issue_id']]['schedule_issue_no']);
			}
		} else {
			foreach ($tickets as $ticket) {
				$printout_ticket_list[] = $this->buildNumberTicketItemForPrintout($ticket['ticket_seq'], $ticket['play_type'], $ticket['bet_type'], $ticket['bet_number'], $ticket['stake_count'], $ticket['total_amount'], $ticket['ticket_multiple']);
			}
		}
		return $printout_ticket_list;
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
	
	protected function queryIssueNoByIssueId($lotteryId, $issueId) {
		if (isNumberGame($lotteryId)) {
			$issueInfo = D('Issue')->getIssueInfo($issueId);
			return $issueInfo['issue_no'];
		} else {
			$scheduleInfo = D('JcSchedule')->getIssueInfo($issueId);
			return $scheduleInfo['schedule_issue_no'];
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

// 	protected function queryScheduleIdsOfAllLotteryBySelectedScheduleList($select_schedule_info_list,$lottery_info){
// 		$schedule_ids_of_all_lottery = array();
// 		foreach($select_schedule_info_list as $schedule_id=>$scheduleInfo){
// 			$schedule_end_time_unix_timestamp = strtotime($scheduleInfo['schedule_end_time']);
// 			$out_of_time = $schedule_end_time_unix_timestamp < (time() + intval($lottery_info['lottery_ahead_endtime']));
// 			if($out_of_time){
// 				$this->_throwExcepiton(C('ERROR_CODE.OUT_OF_ISSUE_TIME'));
// 			}

// 			$scheduleInfo['schedule_end_time_unix_timestamp'] = $schedule_end_time_unix_timestamp;
// 			$mix_schedule_id = $scheduleInfo['schedule_id'];
// 			$day = $scheduleInfo['schedule_day'];
// 			$week = $scheduleInfo['schedule_week'];
// 			$round_no = $scheduleInfo['schedule_round_no'];

// 			$schedule_ids_of_all_lottery[$mix_schedule_id] = D('JcSchedule')->queryAllScheduleIdsFromScheduleNo($day,$week,$round_no);
// 		}
// 		return $schedule_ids_of_all_lottery;

// 		$schedule_ids_of_all_lottery = array();
// 		foreach($select_schedule_info_list as $schedule_id=>$scheduleInfo){
// 			$schedule_end_time_unix_timestamp = strtotime($scheduleInfo['schedule_end_time']);
// 			$scheduleInfo['schedule_end_time_unix_timestamp'] = $schedule_end_time_unix_timestamp;
// 			$mix_schedule_id = $scheduleInfo['schedule_id'];
// 			$day = $scheduleInfo['schedule_day'];
// 			$week = $scheduleInfo['schedule_week'];
// 			$round_no = $scheduleInfo['schedule_round_no'];

// 			$schedule_ids_of_all_lottery[$mix_schedule_id] = D('JcSchedule')->queryAllScheduleIdsFromScheduleNo($day,$week,$round_no);
// 			$new_schedule_infos_in_order[$schedule_id] = $scheduleInfo;
// 		}
// 	}

	protected function formatRequestScheduleOrders($schedule_orders){
		$formated_schedule_orders = array();
		foreach ($schedule_orders as $schedule){
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

	protected function verifyStakeCountAndTotalAmountByOrder(array $stageTicket, $stakeCount, $multiple, $totalAmount) {
		$stake_count_is_correct	= ($stageTicket['stakeCount'] == $stakeCount);
		$order_amount_calc_by_stake_count 	= $stageTicket['stakeCount'] * C('LOTTERY_PRICE') * $multiple;
		$total_amount_is_correct 	 	= bccomp($order_amount_calc_by_stake_count, $totalAmount, 2) === 0;
		$is_correct = ( $stake_count_is_correct && $total_amount_is_correct );
		ApiLog('jc stake:'.$stake_count_is_correct.'==='.$stageTicket['stakeCount'].'==='.$stakeCount, 'opay');
		ApiLog('jc stake $allowAmount:'.$total_amount_is_correct.'==='.$totalAmount.'==='.bccomp($order_amount_calc_by_stake_count, $totalAmount, 2), 'opay');
		if(!$is_correct){
			$this->_throwExcepiton(C('ERROR_CODE.STAKE_COUNT_NO_EQUAL'));
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
		ApiLog('orders:'.print_r($schedule_list,true),'opay');
		return $schedule_list;
	}
	
	
	private function _formatPrintOutTicketCompetitions($ticket_competitions_string) {
		ApiLog('ticket string:'.$ticket_competitions_string, 'opay');
		$competitions = json_decode($ticket_competitions_string,true);
		ApiLog('$competitions:'.print_r($competitions,true), 'opay');
	
		$data = array();
		foreach ($competitions as $competition) {
			ApiLog('befor bet_options:'.print_r($competition['bet_options'],true), 'opay');
	
			$competition['bet_options'] = formatbetOption($competition['bet_options']);
			ApiLog('bet_options:'.print_r($competition,true), 'opay');
	
			$data[] = $competition;
		}
		return $data;
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

	protected function buildNumberTicketItemForPrintout($ticketSeq, $playType, $betType, $bet_number, $stakeCount, $totalAmount, $ticket_multiple){
		return array(
				"ticket_seq" => $ticketSeq,
				"play_type" => intval($playType), // 玩法（标准、追加）
				"bet_type" => intval($betType), // 选号方式（单式、复式、胆拖）
				"stake_count" => intval($stakeCount),
				"bet_content" => array(
						'number' => $bet_number 
				),
				'ticket_multiple' => $ticket_multiple,
				"amount" => intval($totalAmount) 
		);
	}
	
	protected function payOrderAndPrintoutTicketInTransaction($user_info, $orderId, $couponId, $amount, $followBetId=0, $printout_ticket_list, $params) {
		try {
			$uid = $user_info['uid'];
			M()->startTrans();
			$this->_payOrder($uid, $orderId, $couponId, $amount, $followBetId);
			
			$printout_result = $this->printOutTicket($user_info, $params['issue_no'], $orderId, $params['lottery_id'], $printout_ticket_list, $params['order_multiple']);
			ApiLog('print_out:'.print_r($printout_result,true), 'opay');
			if($printout_result){
				ApiLog('commit:'.print_r($printout_result,true), 'opay');
			}else{
				M()->rollback();
				ApiLog('rollback:'.print_r($printout_result,true), 'opay');
				$this->_throwExcepiton(C('ERROR_CODE.BET_ERROR'));
			}
			ApiLog('begin payOrderWithTransaction:', 'opay');
			M()->commit();
			return true;
		} catch (\Think\Exception $e) {
			M()->rollback();
			throw new \Think\Exception($e->getMessage(), $e->getCode());
		}
	}
	
	protected function payOrderWithTransaction($uid, $orderId, $couponId, $amount, $followBetId=0) {
		try {
			M()->startTrans();
			$this->_payOrder($uid, $orderId, $couponId, $amount, $followBetId);
			M()->commit();
		} catch (\Think\Exception $e) {
			M()->rollback();
			throw new \Think\Exception($e->getMessage(), $e->getCode());
		}
	}
	
	private function _payOrder($uid, $orderId, $couponId, $orderTotalAmount, $followBetId) {
		ApiLog('$_payOrder:'.$uid.'===='.$orderId.'==='.$couponId.'==='.$orderTotalAmount.'==='.$followBetId, 'chase');
		
		$frozenMoney = $this->_getFrozenMoney($followBetId);
		$payMoney 	 = $orderTotalAmount + $frozenMoney;
		ApiLog('$payMoney:'.$frozenMoney.'===='.$payMoney, 'chase');
		
		if($couponId) {
			$coupon_is_useable = $this->_checkUserCouponUseable($uid, $couponId);
			if(!$coupon_is_useable){
				$this->_throwExcepiton(C('ERROR_CODE.USER_COUPON_ERROR'));
			}
			$deductCoupon = D('UserCoupon')->deductCoupon($uid, $couponId, $payMoney);
			if($deductCoupon===false){
				$this->_throwExcepiton(C('ERROR_CODE.USER_COUPON_ERROR'));
			}
		} else {
			$deductCoupon = 0;
		}
	
		if($deductCoupon){
			if($deductCoupon>=$orderTotalAmount){
				$order_coupon_amount = $orderTotalAmount;
			}else{
				$order_coupon_amount = $deductCoupon;
			}
			$order_coupon_id = $couponId;
		}else{
			$order_coupon_amount = 0;
			$order_coupon_id = 0 ;
		}
	
		ApiLog('$$order_coupon_id:'.$deductCoupon.'===='.$order_coupon_amount.'==='.$order_coupon_id, 'chase');
		
		$deductMoney = D('UserAccount')->deductMoney($uid, bcsub($payMoney, $deductCoupon,2), $orderId, C('USER_ACCOUNT_LOG_TYPE.BET'));
		ApiLog('deduct:'.$deductMoney.'===='.bcsub($payMoney, $deductCoupon,2), 'chase');

		if($deductMoney===false){
			ApiLog('deduct false:'.$deductMoney.'===='.bcsub($payMoney, $deductCoupon,2), 'chase');
			$this->_throwExcepiton(C('ERROR_CODE.INSUFFICIENT_FUND'));
		}

		$deductAmount = bcadd($deductCoupon, $deductMoney);
		$deductResult = bccomp($deductAmount, $payMoney, 2) == 0;     // 扣款数要等于订单价格，注意浮点数比较
// 		\AppException::ifNoExistThrowException($deductResult, C('ERROR_CODE.DEDUCT_MONEY_ERROR'));
		if(!$deductResult){
			ApiLog('$deductResult:'.$deductResult, 'chase');
			$this->_throwExcepiton(C('ERROR_CODE.DEDUCT_MONEY_ERROR'));
		}

		$saveOrder = D('Order')->savePaidOrder($orderId, C('ORDER_STATUS.PAYMENT_SUCCESS'), $deductCoupon, $order_coupon_amount, $order_coupon_id);
// 		\AppException::ifNoExistThrowException($saveOrder, C('ERROR_CODE.ORDER_STATUS_ERROR'));
		if(!$saveOrder){
			$this->_throwExcepiton(C('ERROR_CODE.ORDER_STATUS_ERROR'));
		}

		if($followBetId) {
            $saveFollow = D('FollowBetInfo')->changeFollowBetInfoStatus($followBetId,C('FOLLOW_BET_INFO_STATUS.ON_GOING'));
			//$saveFollow = D('FollowBet')->saveFollowBetStatus($followBetId, C('FOLLOW_STATUS.NORMAL'));
			if($saveFollow===false){
				$this->_throwExcepiton(C('ERROR_CODE.DATABASE_ERROR'));
			}
			
			$saveResult = D('UserAccount')->increaseFrozenBalance($uid, $frozenMoney, $followBetId, C('USER_ACCOUNT_LOG_TYPE.FOLLOW_FOR_FROZEN'));
// 			\AppException::ifExistThrowException($saveResult===false, C('ERROR_CODE.DATABASE_ERROR'));
			if($saveResult===false){
				$this->_throwExcepiton(C('ERROR_CODE.DATABASE_ERROR'));
			}
		}
	
	}

	/*private function _getOrderPayInfo($uid, $order_id, $coupon_d, $order_total_amount,$pay_money,$fbi_id, $suite_id){
        if($coupon_d) {
            $coupon_is_useable = $this->_checkUserCouponUseable($uid, $coupon_d);
            if(!$coupon_is_useable){
                $this->_throwExcepiton(C('ERROR_CODE.USER_COUPON_ERROR'));
            }
            $coupon_amount = D('UserCoupon')->deductCoupon($uid, $coupon_d, $pay_money);
            if($coupon_amount===false){
                $this->_throwExcepiton(C('ERROR_CODE.USER_COUPON_ERROR'));
            }
        } else {
            $coupon_amount = 0;
        }
        $pay_info = array(
            'coupon_amount' => 0,
            'reduce_amount' => 0,
            'pay_reduce' => 0,
            'pay_coupon' => 0,
            'pay_account' => 0,
        );
        $pay_info['coupon_amount'] = $coupon_amount;
	    if(!$fbi_id){
            $pay_account = bcsub($pay_money, $coupon_amount);
            $pay_info['pay_account'] = $pay_account;
            $pay_info['pay_coupon'] = $coupon_amount;
        }else{
            if($suite_id){
                $reduce_amount = D('LotteryPackage')->getReduceAmount($suite_id);
            }else{
                $reduce_amount = 0;
            }
            $pay_info['reduce_amount'] = $reduce_amount;
            $pay_account = bcsub($pay_money, bcadd($coupon_amount,$reduce_amount));
            if($pay_account >= $order_total_amount){
                $pay_info['pay_account'] = $order_total_amount;
            }else{
                if($pay_account+$coupon_amount){

                }
            }

        }






        $pay_info['pay_account'] = $pay_account;
        if($pay_account >= $order_total_amount){
            $pay_info['pay_coupon'] = 0;
        }else{

        }


    }*/
	
	private function _getFrozenMoney($followBetId) {
		if($followBetId) {
		    $fbiInfo = D('FollowBetInfo')->getFollowInfoById($followBetId);
		    $is_suite = !empty($fbiInfo['extra_id']);
		    if($is_suite){
                $lottery_package_price = D('LotteryPackage')->getPackagesPriceById($fbiInfo['extra_id']);
                return bcsub($lottery_package_price,$fbiInfo['followed_amount']);//bccomp
            }else{
                return bcsub($fbiInfo['follow_total_amount'],$fbiInfo['followed_amount']);//bccomp
            }
			/*$followInfo = D('FollowBet')->getFollowBetInfo($followBetId);
			return $followInfo['follow_remain_times'] * $orderTotalAmount;*/
		} else {
			return 0;
		}
	}
	
	private function _checkUserCouponUseable($uid, $user_coupon_id){
		$user_coupon_info = D('UserCoupon')->getUserCouponInfo($user_coupon_id);
		if(strtotime($user_coupon_info['user_coupon_end_time'])<time()){
			return false;
		}
		if(strtotime($user_coupon_info['user_coupon_start_time'])>time()){
			return false;
		}
		if($user_coupon_info['uid']!=$uid){
			return false;

		}
		if($user_coupon_info['user_coupon_status']!=C('USER_COUPON_STATUS.AVAILABLE')){
			return false;
		}
		return true;
	}
	
	protected function requestBeeByCurl($orderMsg){
		ApiLog('req url:' . $_SERVER['HTTP_HOST'].'=='.C('PRINT_OUT_TICKET_URL').'===='.print_r($orderMsg, true), 'wpay_printout_curl');
		ApiLog('req :' . json_encode($orderMsg), 'wpay_printout_curl');
		$response = requestByCurl(C('PRINT_OUT_TICKET_URL'), $orderMsg);
		return json_decode($response, true);
	}
	
	protected function calcSzcOrderTotalAmount($request_params){
		$order_total_amount_for_one_time = $this->calcSzcOrderTotalAmountForOneTime($request_params['tickets'],$request_params['multiple']);
		$order_total_amount  = $order_total_amount_for_one_time * $request_params['follow_times'];
		return $order_total_amount;
	}

    protected function getOrderTotalAmountOneTime($request_params){
        return $this->calcSzcOrderTotalAmountForOneTime($request_params['tickets'],$request_params['multiple']);
    }
	
	protected function calcSzcOrderTotalAmountForOneTime(array $tickets, $multiple){
		$totalAmount = 0;
		foreach ($tickets as $ticket) {
			$totalAmount += $ticket['total_amount'];
		}
		return $totalAmount * $multiple;
	}

    protected function calcIntelligentFollowTotalAmount(array $tickets){
        $totalAmount = 0;
        foreach ($tickets as $ticket) {
            $totalAmount += $ticket['total_amount'];
        }
        return $totalAmount;
    }

    protected function calcTotalPayAmount(array $tickets,$follow_detail,$suite_id = 0){
        if($suite_id){
            $package_info = D('LotteryPackage')->getPackagesInfoById($suite_id);
            $totalPayMoney = $package_info['lp_price'];
        }else{
            $totalAmount = 0;
            foreach ($tickets as $ticket) {
                $totalAmount += $ticket['total_amount'];
            }

            $totalPayMoney = 0;
            foreach($follow_detail as $follow_info){
                if($follow_info['total_amount'] != $totalAmount*$follow_info['multiple']){
                    return false;
                }
                $totalPayMoney += $follow_info['total_amount'];
            }
        }
        return $totalPayMoney;
    }

    protected function calcTotalOrderAmount(array $tickets,$follow_detail){
        $totalAmount = 0;
        foreach ($tickets as $ticket) {
            $totalAmount += $ticket['total_amount'];
        }

        $totalPayMoney = 0;
        foreach($follow_detail as $follow_info){
            if($follow_info['total_amount'] != $totalAmount*$follow_info['multiple']){
                return false;
            }
            $totalPayMoney += $follow_info['total_amount'];
        }
        return $totalPayMoney;
    }


	protected function buildUserInfoForPrintOut(array $userInfo){
		if (empty($userInfo['user_real_name']) || empty($userInfo['user_identity_card'])) {
			$userInfo = array(
					'user_name' => '陈翔',
					'user_real_name' => '陈翔',
					'user_telephone' => '15107529417',
					'user_email' => '',
					'card_type' => 1,
					'user_identity_card' => '350983199008068734',
			);
		}
		return array(
				'user_name' => $userInfo['user_name'],
				'real_name' => $userInfo['user_real_name'],
				'phone' => $userInfo['user_telephone'],
				'email' => $userInfo['user_email'],
				'card_type' => 1,
				'card_no' => $userInfo['user_identity_card'],
		);
	}
	
	protected function printOutTicket(array $userInfo, $issueNo, $orderId, $lotteryId, array $ticketList, $multiple, $first_issue_no = '') {
		$userInfo = $this->buildUserInfoForPrintOut($userInfo);
		$first_end_time = '';
		if(isJCLottery($lotteryId) && $first_issue_no){
			$first_end_time = D('JcSchedule')->queryEndTimeByScheduleNo($first_issue_no);
		}

		$order_info = D('Order')->getOrderInfo($orderId);
		
		$orderMsg = array(
				"data" => array(
						"order_info" => array(
								"user_info" => $userInfo,
								"lottery_id" => $lotteryId,
								"multiple" => $multiple,
								"issue_no" => $issueNo,
								"first_issue_no" => $first_issue_no,
								"first_end_time" => $first_end_time,
								"order_id" => $orderId,
								"order_amount" => floatval($order_info['order_total_amount']),
								"order_play_type" => $order_info['play_type'],
								"order_bet_type" => $order_info['bet_type'],
								"ticket_list" => $ticketList 
						) 
				) 
		);
		$printout_data = array(
				"sign_key" => "",
				'order_info'    => json_encode($orderMsg),
				"lottery_id"    => $lotteryId, );
		ApiLog('print out order info:' . print_r($printout_data, true), 'printout');
		//请求异常
		$result = $this->requestBeeByCurl($printout_data);
		ApiLog('print out $result:' . print_r($result, true), 'printout');
		
		if (!$result || $result['code'] !== C('ERROR_CODE.PRINTOUT_SUCCESS')) {
			//FIXME 报警
			D('Order')->saveOrderStatus($orderId, C('ORDER_STATUS.BET_ERROR'));
			return false;
		} else {
			return $result;
		}
	}
	
	protected function checkPasswordFree($uid, $amount, $orderLimit, $dayLimit, $userPasswordFree, $paymentPassword) {
		//暂不支持小额免密
		return true;
		 
		$passwordFree = false;
		if($userPasswordFree){
			$passwordFree = true;
			$todayAmount = D('Order')->getTodayOrderAmount($uid);
			ApiLog('passfree:'.$userPasswordFree.'==='.$todayAmount,'opay');
			$withinLimitPerOrder = ($orderLimit == 0) 	|| ($amount < $orderLimit);
			$withinLimitPerDay 	 = ($dayLimit == 0) 	|| ($todayAmount + $amount < $dayLimit);
			ApiLog('$orderLimit ss:'.$amount.'==='.$orderLimit,'opay');
			ApiLog('$$todayAmount ss:'.($todayAmount + $amount).'==='.$orderLimit,'opay');
			ApiLog('passfree ss:'.$withinLimitPerOrder.'==='.$withinLimitPerDay,'opay');
			 
			if($withinLimitPerOrder && $withinLimitPerDay) {
				$passwordFree = true;
			}else{
				$passwordFree = false;
			}
		}
		if(!$paymentPassword) {
			$passwordFree = true;
		}
		return $passwordFree;
	}

	protected function isLimitLottery($lottery_id){
        $lottery_id = $this->_getLotteryId($lottery_id);
        $lottery_info = D('Lottery')->getLotteryInfo($lottery_id);
        $lottery_status = $lottery_info['lottery_status'];
        if($lottery_status === '0' || $lottery_status === 0){
               return true;
        }
        return false;
    }

    private function _getLotteryId($lottery_id){
        if(!isJc($lottery_id)){
            return $lottery_id;
        }
        if(isJclq($lottery_id)){
            return TIGER_LOTTERY_ID_OF_JL;
        }
        if(isJczq($lottery_id)){
            return TIGER_LOTTERY_ID_OF_JZ;
        }
    }

    protected function addSzcOrderAndTicketsForNewFollow($lotteryId, $uid, $orderTotalAmount, $orderSku, $issueId, $multiple,
                                                         $couponId, $tickets, $userFollowTimes, $identity,$followDetails = array(),$is_win_stop = 0,$suite_id = 0,$is_independent = 0,$win_stop_amount = 0,$order_type = 0){
        M()->startTrans();

        $extra_params['content'] = json_encode($tickets);
        $orderId = D('Order')->addOrder($uid, $orderTotalAmount, $issueId, $multiple, $couponId, $lotteryId, $orderSku, $issueId, $identity,0,0,'',$extra_params,$suite_id,$order_type);

        if (!$orderId) {
            M()->rollback();
            return false;
        }

        if($suite_id){
            $reduce_amount = D('LotteryPackage')->getReduceAmount($suite_id);
            if(!empty($reduce_amount)){
                $save_status = D('Order')->where(array('order_id'=>$orderId))->save(array('order_reduce_consumption'=>$reduce_amount));
                if(!$save_status){
                    M()->rollback();
                    return false;
                }
            }
        }

        if($userFollowTimes > 1){
            $fbiId = $this->_saveFollowInfo($uid,$lotteryId,$issueId,$orderId,$userFollowTimes,$tickets,$followDetails,$suite_id,$is_win_stop,$is_independent,$win_stop_amount);
            if ($fbiId === false) {
                M()->rollback();
                return false;
            }
            $fbdInsertStatus = $this->_saveFollowDetail($uid,$issueId,$lotteryId,$orderId,$followDetails,$fbiId,$tickets,$is_independent);
            if ($fbdInsertStatus === false) {
                M()->rollback();
                return false;
            }
        }else{
            $fbiId = 0;
        }


        $ticketList = $this->_saveTicket($lotteryId, $uid, $orderId, $issueId, $tickets, $multiple);
        if (!$ticketList) {
            M()->rollback();
            return false;
        }

        M()->commit();

        return array(
            'orderId' => $orderId,
            'ticketList' => $ticketList,
            'fbiId' => $fbiId
        );
    }

    protected function buildFollowDetails($followDetails,$userFollowTimes=1,$orderTotalAmount=0,$multiple=1){
        if(empty($followDetails)){
            for($i=0;$i<$userFollowTimes;$i++){
                $followDetails[$i]['multiple'] = $multiple;
                $followDetails[$i]['total_amount'] = $orderTotalAmount;
            }
        }
        return $followDetails;
    }

    private function _saveFollowInfo($uid,$lottery_id,$issue_id,$order_id,$user_follow_times,$tickets,$follow_details,$suite_id = 0,$is_win_stop=0,$is_independent = 0,$win_stop_amount = 0){
        $total_order_money = $this->calcTotalOrderAmount($tickets,$follow_details);
        $follow_info = array(
            'uid' => $uid,
            'lottery_id' => $lottery_id,
            'issue_id' => $issue_id,
            'follow_times' => $user_follow_times,
            'follow_total_amount' => $total_order_money,
            'followed_amount' => $follow_details[0]['total_amount'],
            'fbi_type' => $this->_getFbiType($is_win_stop,$win_stop_amount),
            'fbi_createtime' => getCurrentTime(),
            'order_id' => $order_id,
            'extra_id' => empty($suite_id) ? 0 : $suite_id,
            'fbi_win_stop_amount' => empty($win_stop_amount) ? 0 : $win_stop_amount,
            'fbi_is_independent' => empty($is_independent) ? 0 : $is_independent,
            'fbi_status' => C('FOLLOW_BET_INFO_STATUS.NO_PAY'),
        );
        return D('FollowBetInfo')->add($follow_info);
    }

    private function _getFbiType($is_win_stop,$win_stop_amount){
        if(empty($is_win_stop) && empty($win_stop_amount)){
            return 0;
        }elseif(!empty($is_win_stop) && empty($win_stop_amount)){
            return 1;
        }elseif(!empty($is_win_stop) && !empty($win_stop_amount)){
            return 2;
        }

    }

    private function _saveFollowDetail($uid,$issueId,$lottery_id,$order_id,$follow_details,$fbi_id,$tickets,$is_independent){
        foreach($follow_details as $key => $detail){
            $bet_number_list = '';
            if($is_independent && $key >= 1){
                $check_random_tickets_code = $this->checkRandomTickets($detail['tickets'],$lottery_id);
                if($check_random_tickets_code!=C('ERROR_CODE.SUCCESS')){
                    $this->_throwExcepiton($check_random_tickets_code);
                }
                $bet_number_list = json_encode($detail['tickets']);
            }elseif($key >= 1){
                $bet_number_list = json_encode($tickets);
            }

            $follow_order_id = ($key == 0) ? $order_id : 0;
            $is_current = ($key == 1) ? C('FOLLOW_BET_DETAIL_STATUS.IS_CURRENT') : C('FOLLOW_BET_DETAIL_STATUS.NO_CURRENT');
            $follow_status = ($key == 0) ? C('FOLLOW_BET_DETAIL_STATUS.FOLLOWED') : C('FOLLOW_BET_DETAIL_STATUS.NO_FOLLOW');
            $issue_id = ($key == 0) ? $issueId : 0;
            $follow_detail[] = array(
                'fbi_id' => $fbi_id,
                'uid' => $uid,
                'issue_id' => $issue_id,
                'lottery_id' => $lottery_id,
                'order_id' => $follow_order_id,
                'order_multiple' => $detail['multiple'],
                'order_total_amount' => $detail['total_amount'],
                'fbd_createtime' => getCurrentTime(),
                'fbd_is_current' => $is_current,
                'fbd_index' => $key+1,
                'fbd_status' => $follow_status,
                'fbd_bet_number_list' => $bet_number_list,
            );
        }
        return D('FollowBetDetail')->addAll($follow_detail);
    }

    private function _getFollowDetailId($fbi_id,$index = 1){
        $follow_detail_info =  D('FollowBetDetail')->getFollowDetailByFbiId($fbi_id,$index);
        return $follow_detail_info['fbd_id'];
    }

    protected function checkPackagesTicket($tickets,$package_info){
        $lp_stake_count = $package_info['lp_stake_count'];
        if($lp_stake_count != count($tickets)){
            return C('ERROR_CODE.TICKET_ERROR');
        }
        foreach ( $tickets as $ticket ) {
            if( $ticket['bet_type'] != BET_TYPE_OF_DASHI){
                return C('ERROR_CODE.TICKET_ERROR');
            }
        }
        return C('ERROR_CODE.SUCCESS');

    }


    protected function checkRandomTickets($random_tickets,$lottery_id){
        if(!in_array($lottery_id,array(TIGER_LOTTERY_ID_OF_SSQ,TIGER_LOTTERY_ID_OF_DLT))){
            ApiLog('不允许随机$lottery_id:'.$lottery_id,'randomTickets');
            return C('ERROR_CODE.TICKET_ERROR');
        }
        $checkTicketsCode = $this->checkNumberTickets($random_tickets, $lottery_id);
        return $checkTicketsCode;
    }


    protected function checkFollowDetail($tickets,$follow_detail){

        $ticket_total_amount = $this->calcIntelligentFollowTotalAmount($tickets);
        foreach($follow_detail as $follow_info){
            if($follow_info['multiple'] <= 0 || $follow_info['total_amount'] <= 0){
                return C('ERROR_CODE.TICKET_ERROR');
            }

            $follow_total_amount = bcmul($follow_info['multiple'],$ticket_total_amount);
            if(bccomp($follow_info['total_amount'],$follow_total_amount) != 0){
                return C('ERROR_CODE.TICKET_ERROR');
            }
        }
        return C('ERROR_CODE.SUCCESS');

    }


}