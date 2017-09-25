<?php

namespace Home\Controller;

use Home\Controller\BettingBaseController;
use Home\Util\Factory;

class JcBetController extends BettingBaseController{
	private $_jc_validator_obj = null;

	public function __construct(){
		parent::__construct();
		$jc_validator_obj = "\Home\Validator\JcValidator";
		$this->_jc_validator_obj = new $jc_validator_obj();
	}
	
	public function validateParamsForOptimize($uid, $params){
		if ($params->coupon_id) {
			$coupon_owen_by_user = D('UserCoupon')->owenedByUser($uid, $params->coupon_id);
			if (!$coupon_owen_by_user) {
				$this->_throwExcepiton(C('ERROR_CODE.USER_COUPON_ERROR'));
// 				\AppException::throwException(C('ERROR_CODE.USER_COUPON_ERROR'));
			}
		}
		
		$order_stake = 0;
		foreach ($params->optimize_ticket_list as $ticket) {
			$ticket_stake = $ticket["ticket_multiple"];
			$order_stake += $ticket_stake;
			
			$ticket_lottery_ids = array();
			foreach ($ticket['ticket_schedules'] as $ticket_schedule) {
				$bet_option_error = $this->_jc_validator_obj->validateBetOption($ticket_schedule['bet_options']);
				if (!$bet_option_error) {
					$this->_throwExcepiton(C('ERROR_CODE.BET_NUMBER_ERROR'));
// 					\AppException::throwException(C('ERROR_CODE.BET_NUMBER_ERROR'));
				}
				$ticket_lottery_ids[] = $ticket_schedule['schedule_lottery_id'];
				$ticket_schedule_id_list[] =  $ticket_schedule['id'];
			}
			
// 			if(count($ticket['ticket_schedules']!=count($params->select_schedule_ids))){
// 				$this->_throwExcepiton(C('ERROR_CODE.BET_NUMBER_ERROR'));
// 			}
			
			$series_number_error = $this->_jc_validator_obj->validateSeriesType(count($ticket['ticket_schedules']), $ticket['series_type']);
			if (!$series_number_error) {
				$this->_throwExcepiton(C('ERROR_CODE.JC_SERIES_TYPE_ERROR'));
// 				\AppException::throwException(C('ERROR_CODE.JC_SERIES_TYPE_ERROR'));
			}
			
			$series_number_error = $this->_jc_validator_obj->validateSeriesNumberOverMaxLimit($ticket_lottery_ids, $ticket['series_type']);
			if (!$series_number_error) {
				$this->_throwExcepiton(C('ERROR_CODE.JC_SERIES_TYPE_ERROR'));
// 				\AppException::throwException(C('ERROR_CODE.JC_SERIES_TYPE_ERROR'));
			}
		}
		if ($order_stake != $params->stake_count) {
			$this->_throwExcepiton(C('ERROR_CODE.STAKE_COUNT_NO_EQUAL'));
// 			\AppException::throwException(C('ERROR_CODE.STAKE_COUNT_NO_EQUAL'));
		}
		ApiLog('stake:' . $params->total_amount . '====' . $order_stake . '===' . $params->order_multiple . '====' . $order_stake * $params->order_multiple * LOTTERY_PRICE, 'tick');
		if ($params->total_amount != $order_stake * $params->order_multiple * LOTTERY_PRICE) {
			$this->_throwExcepiton(C('ERROR_CODE.TOTAL_AMOUNT_NO_EQUAL'));
// 			\AppException::throwException(C('ERROR_CODE.TOTAL_AMOUNT_NO_EQUAL'));
		}
		
		$select_schedule_info_list = $this->queryScheduleInfoListForOptimize($params->select_schedule_ids);
		if ($select_schedule_info_list == false) {
			$this->_throwExcepiton(C('ERROR_CODE.OUT_OF_ISSUE_TIME'));
// 			\AppException::throwException(C('ERROR_CODE.OUT_OF_ISSUE_TIME'));
		}
	}

	public function queryScheduleInfoListForOptimize($schedule_ids){
		$schedulesInfo = D('JcSchedule')->getScheduleIssueNo($schedule_ids);
		return $schedulesInfo;
	}

	private function _buildTicketCompetitionContent($ticket_schedules, $ticket_lottery_id, $play_type, $select_schedule_info_list,$schedule_ids_of_all_lottery){
		$competitions = array();
		foreach ($ticket_schedules as $ticket_schedule) {
			$ticket_schedule_id = $ticket_schedule['id'];
// 			$schedule_issue_no = $select_schedule_info_list[$ticket_schedule_id]['schedule_issue_no'];
			$bet_options = is_array($ticket_schedule['bet_options']) ? $ticket_schedule['bet_options'] : array(
					$ticket_schedule['bet_options'] 
			);
			
			$schedule_issue_no = $schedule_ids_of_all_lottery[$ticket_schedule_id][$ticket_lottery_id][$play_type]['schedule_issue_no'];
			$competitions[] = array(
					'lottery_id' => $ticket_schedule['schedule_lottery_id'],
					// 'schedule_id' => $ticket_schedule_id,
					'bet_options' => $bet_options,
					'issue_no' => $schedule_issue_no 
			);
			
			$is_jc_mix_lottery_id = isJcMix($ticket_lottery_id);
			if($is_jc_mix_lottery_id){
				$ticket_schedule_time_list[] = array(
					'schedule_issue_no' => $schedule_issue_no,
					'schedule_end_time' => $schedule_ids_of_all_lottery[$ticket_schedule_id][$ticket_lottery_id][$play_type]['schedule_end_time'],
					'schedule_game_start_time' => $schedule_ids_of_all_lottery[$ticket_schedule_id][$ticket_lottery_id][$play_type]['schedule_game_start_time'],
					'schedule_id' => $ticket_schedule_id 
				);
			}else{
				$ticket_schedule_time_list[] = array(
					'schedule_issue_no' => $schedule_issue_no,
					'schedule_end_time' => $schedule_ids_of_all_lottery[$ticket_schedule_id][$ticket_lottery_id][$play_type]['schedule_end_time'],
					'schedule_game_start_time' => $schedule_ids_of_all_lottery[$ticket_schedule_id][$ticket_lottery_id][$play_type]['schedule_game_start_time'],
					'schedule_id' => $schedule_ids_of_all_lottery[$ticket_schedule_id][$ticket_lottery_id][$play_type]['schedule_id']
				);				
			}		
			$all_ticket_issue_no[] = $schedule_issue_no;
		}
		
		asort($all_ticket_issue_no);
		$ticket_issue_nos = implode(',', $all_ticket_issue_no);
		
		$schedule_range_info = $this->checkScheduleTimeRangeInfo($ticket_schedule_time_list);
		$last_schedule_in_ticket = $schedule_range_info['last_schedule_info'];
		$first_schedule_in_ticket = $schedule_range_info['first_schedule_info'];
		
		return array(
				'ticket_lottery_id'=>$ticket_lottery_id,
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

	private function _devideOverMultipleTicketForOptimize($uid, $ticket_lottery_id, $order_play_type, $ticket_seq, $ticket_stake_count, $order_multiple, $ticket_series_type, $ticket_schedules_info){
		$last_schedule_issue_id_in_ticket = $ticket_schedules_info['last_schedule_issue_id'];
		$last_schedule_end_time_in_ticket = $ticket_schedules_info['last_schedule_end_time'];
		$first_schedule_issue_id_in_ticket = $ticket_schedules_info['first_schedule_issue_id'];
		$first_schedule_end_time_in_ticket = $ticket_schedules_info['first_schedule_end_time'];
		$first_schedule_issue_no_in_ticket = $ticket_schedules_info['first_schedule_issue_no'];
		$last_schedule_issue_no_in_ticket = $ticket_schedules_info['last_schedule_issue_no'];
		
		
		
		$competitions = $ticket_schedules_info['competition'];
		
		$once_ticket_amount = LOTTERY_PRICE;
		$total_ticket_multiple = $order_multiple * $ticket_stake_count;
		
		if ($total_ticket_multiple > BET_JC_TICKET_TIME_LIMIT) {
			$limit_multiple = BET_JC_TICKET_TIME_LIMIT;
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
			$ticket_seq++;
// 			$printout_ticket_item = $this->_buildPrintOutTicketInfo($ticket_seq, $ticket_lottery_id, $order_play_type, $ticket_series_type, 1, $ticket_multiple, $ticket_amount, $last_schedule_end_time_in_ticket, $first_schedule_end_time_in_ticket, $competitions);
			$printout_ticket_item = $this->buildCompetitionTicketItemForPrintout($ticket_seq, $order_play_type, $ticket_series_type, 1, $ticket_amount, $competitions, $last_schedule_end_time_in_ticket, $ticket_lottery_id, $ticket_multiple, $first_schedule_end_time_in_ticket,$first_schedule_issue_no_in_ticket,$last_schedule_issue_no_in_ticket);
				
			$printout_ticket_list[] = $printout_ticket_item;
			
			$ticket_data = $this->_buildTicketData($uid, $ticket_seq, $ticket_lottery_id, $order_play_type, $ticket_series_type, 1, $ticket_multiple, $ticket_amount, $ticket_schedules_info, $last_schedule_issue_id_in_ticket);
			
			$ticket_data_list[] = $ticket_data;
		}
		ApiLog('$printticket_data_list:' . print_r($printout_ticket_list, true), 'opti');
		ApiLog('$ticket_data_list:' . print_r($ticket_data_list, true), 'opti');
		
		return array(
				'ticket_seq' => $ticket_seq,
				'ticket_data_list' => $ticket_data_list,
				'printout_ticket_list' => $printout_ticket_list 
		);
	}

	private function _buildTicketData($uid, $ticket_seq, $ticket_lottery_id, $order_play_type, $ticket_series_type, $ticket_stake_count, $ticket_multiple, $ticket_amount, $ticket_schedules_info, $last_schedule_issue_id_in_ticket){
		$competitions = $ticket_schedules_info['competition'];
		$formated_competitions = $this->formatBetOptionAddV($competitions);
		$competitions_json_string = json_encode($formated_competitions);
		ApiLog('ticket:' . $ticket_lottery_id, 'opti');
		
		$verifyObj = Factory::createVerifyJcObj($ticket_lottery_id);
		ApiLog($order_play_type, 'opti');
		if ($order_play_type == JC_PLAY_TYPE_MULTI_STAGE) {
			$ticket_issue_nos = $verifyObj->getIssueNos($competitions);
			ApiLog($ticket_issue_nos, 'opti');
		} else {
			$ticket_issue_nos = $ticket_schedules_info['issue_no'];
			ApiLog($ticket_issue_nos, 'opti');
		}
		ApiLog($ticket_issue_nos, 'opti');
		
		if (!$ticket_issue_nos) {
			return false;
		}
		
		return D('JcTicket')->buildTicketData($uid, $ticket_seq, $order_play_type, $ticket_stake_count, $ticket_series_type, $competitions_json_string, $last_schedule_issue_id_in_ticket, $ticket_issue_nos, $ticket_amount, $ticket_multiple, $ticket_schedules_info['first_schedule_issue_id'], $ticket_schedules_info['ticket_lottery_id']);
	}

	private function _getTicketLotteryId($ticket_lottery_ids){
		return $this->_jc_validator_obj->getTicketLotteryId($ticket_lottery_ids);
	}

	private function _parseOrderContent($uid, $optimize_ticket_list, $select_schedule_info_list, $order_play_type, $order_multiple,$schedule_ids_of_all_lottery){
		$ticket_seq = 0;
		$ticket_data_list = array();
		$printout_ticket_list = array();
		foreach ($optimize_ticket_list as $ticket) {
			$ticket_series_type = $ticket['series_type'];
			$ticket_stake_count = $ticket['ticket_multiple'];
			$ticket_lottery_ids = array();
			foreach ($ticket['ticket_schedules'] as $ticket_schedule) {
				$ticket_lottery_ids[] = $ticket_schedule['schedule_lottery_id'];
			}
			$ticket_lottery_id = $this->_getTicketLotteryId($ticket_lottery_ids);
			ApiLog('_parseOrderContent :'.$ticket_lottery_id, 'wpay');
			$ticket_schedules_info = $this->_buildTicketCompetitionContent($ticket['ticket_schedules'], $ticket_lottery_id, $order_play_type, $select_schedule_info_list,$schedule_ids_of_all_lottery);
			ApiLog(print_r($ticket_schedules_info, true), 'opti');
			
			$devide_result = $this->_devideOverMultipleTicketForOptimize($uid, $ticket_lottery_id, $order_play_type, $ticket_seq, $ticket_stake_count, $order_multiple, $ticket_series_type, $ticket_schedules_info);
			ApiLog('devie:' . print_r($devide_result, true), 'opti');
			
			if (empty($devide_result)) {
				return false;
			}
			
			$ticket_seq = $devide_result['ticket_seq'];
			$ticket_data_list = array_merge($ticket_data_list, $devide_result['ticket_data_list']);
			$printout_ticket_list = array_merge($printout_ticket_list, $devide_result['printout_ticket_list']);
		}
		
		return array(
				'ticket_data_list' => $ticket_data_list,
				'printout_ticket_list' => $printout_ticket_list 
		);
	}
	
	private function _getJcOrderDetailForOptimize($optimize_ticket_list, $order_id) {
		$schedule_bet_content = array();
		foreach ($optimize_ticket_list as $ticket) {
			$ticket_lottery_ids = array();
			foreach ($ticket['ticket_schedules'] as $ticket_schedule) {
				$schedule_id = $ticket_schedule['id'];
				$ticket_lottery_id = $ticket_schedule['schedule_lottery_id'];
				$schedule_bet_option = $ticket_schedule['bet_options'];
				if(!in_array($schedule_bet_option,$schedule_bet_content[$schedule_id][$ticket_lottery_id])){
					$schedule_bet_content[$schedule_id][$ticket_lottery_id][] = $schedule_bet_option;
				}
			}
		}
		ApiLog('bet:'.print_r($schedule_bet_content,true),'j');
		$order_detail_list = array();
		foreach($schedule_bet_content as $schedule_id=>$bet_content_info){
			$bet_content = json_encode(betOptionsAddV($bet_content_info));
			$order_detail_list[] = D('JcOrderDetail')->buildDetailData($order_id, $schedule_id, $bet_content, 0);
		}
		return $order_detail_list;
	}
	
	private function _queryScheduleIdsOfAllLotteryForOptimize($select_schedule_info_list,$lottery_info){
		$schedule_ids_of_all_lottery = array();
		foreach($select_schedule_info_list as $schedule_id=>$scheduleInfo){
			$schedule_end_time_unix_timestamp = strtotime($scheduleInfo['schedule_end_time']);
			$out_of_time = $schedule_end_time_unix_timestamp < (time() + intval($lottery_info['lottery_ahead_endtime']));
			if($out_of_time){
				$this->_throwExcepiton(C('ERROR_CODE.OUT_OF_ISSUE_TIME'));
			}
					
			$mix_schedule_id = $scheduleInfo['schedule_id'];
			$day = $scheduleInfo['schedule_day'];
			$week = $scheduleInfo['schedule_week'];
			$round_no = $scheduleInfo['schedule_round_no'];
		
			$schedule_ids_of_all_lottery[$mix_schedule_id] = D('JcSchedule')->queryAllScheduleIdsFromScheduleNo($day,$week,$round_no);
		}
		return $schedule_ids_of_all_lottery;
	}
	
	private function _getOrderSeries($optimize_ticket_list){
		$series_list = array();
		foreach($optimize_ticket_list as $optimize_ticket_item){
			$series_list[] = $optimize_ticket_item['series_type'];
		}
		$series_list = array_unique($series_list);
		return implode(',',$series_list);
	}
	
	public function addOptimizeOrder($params){
	    $is_limit = $this->isLimitLottery($params->lottery_id);
	    if($is_limit){
            $this->_throwExcepiton(C('ERROR_CODE.ISSUE_NO_EXIST'));
        }
		$user_info = $this->getAvailableUser($params->session);
		$uid = $user_info['uid'];
		$orderSku = buildOrderSku($uid);
		$order_total_amount = $params->total_amount;
		// validate param
		$this->validateParamsForOptimize($uid, $params);
		$lack_money = $this->getRemainMoney($uid, $params->coupon_id,$params->lottery_id, $order_total_amount,$order_total_amount);
		if ($lack_money < 0) {
			return $this->buildResponseForPayOrder(0, '', abs($lack_money), C('ERROR_CODE.INSUFFICIENT_FUND'));
		}
			
		$existOrder = D('Order')->getOrderIdByIdentity($params->order_identity);
		if ($existOrder) {
			if ($existOrder['order_status'] > C('ORDER_STATUS.UNPAID')) {
				return $this->buildResponseForPayOrder($existOrder['order_id'], $existOrder['order_sku'], 0, C('ERROR_CODE.SUCCESS'));
			} elseif ($existOrder['order_status'] == C('ORDER_STATUS.UNPAID')) {
				$order_total_amount = $existOrder['order_total_amount'];
				$lack_money = $this->getRemainMoney($uid, $params->coupon_id,$existOrder['lottery_id'], $order_total_amount,$order_total_amount);
				if ($lack_money < 0) {
					return $this->buildResponseForPayOrder($existOrder['order_id'], $existOrder['order_sku'], abs($lack_money), C('ERROR_CODE.INSUFFICIENT_FUND'));
				}
				
				$order_id = intval($existOrder['order_id']);
				$orderSku = $existOrder['order_sku'];
				ApiLog('get ticket list:'.$existOrder['lottery_id'].'==='.print_r($existOrder,true), 'pack');
				$printout_ticket_list = $this->getTicketListForPrintoutByOrderId($existOrder['lottery_id'], $existOrder['issue_id'], $existOrder['order_id'], $uid);
				if(!$printout_ticket_list){
					$this->_throwExcepiton(C('ERROR_CODE.TICKET_ERROR'));
				}
		
				$last_issue_no = $this->queryIssueNoByIssueId($existOrder['lottery_id'], $existOrder['issue_id']);
				$first_issue_no = $this->queryIssueNoByIssueId($existOrder['lottery_id'], $existOrder['first_issue_id']);
			}
		}else{
			$select_schedule_info_list = $this->queryScheduleInfoListForOptimize($params->select_schedule_ids);
			$schedule_range_info = $this->checkScheduleTimeRangeInfo($select_schedule_info_list);
			$last_schedule_in_order = $schedule_range_info['last_schedule_info'];
			$first_schedule_in_order = $schedule_range_info['first_schedule_info'];
			
			$order_last_schedule = $last_schedule_in_order;
			$lottery_info = D('Lottery')->getLotteryInfo($params->lottery_id);
			$schedule_ids_of_all_lottery  = $this->_queryScheduleIdsOfAllLotteryForOptimize($select_schedule_info_list,$lottery_info);
				
			ApiLog('bet $select_schedule_info_list:' . print_r($select_schedule_info_list, true), 'opti');
			ApiLog('bet $schedule_ids_of_all_lottery:' . print_r($schedule_ids_of_all_lottery, true), 'opti');
			ApiLog('bet $order_last_schedule:' . print_r($order_last_schedule, true), 'opti');
			ApiLog('bet optimize_ticket_list:' . print_r($params->optimize_ticket_list, true), 'opti');
			$order_play_type = C('MAPPINT_JC_PLAY_TYPE.' . $params->play_type);
				
			$last_issue_no = $order_last_schedule['schedule_issue_no'];
				
			$bet_info = $this->_parseOrderContent($uid, $params->optimize_ticket_list, $select_schedule_info_list, $order_play_type, $params->order_multiple,$schedule_ids_of_all_lottery);
			ApiLog('bet:' . print_r($bet_info, true), 'opti');
			
			$first_schedule_info_in_order = $first_schedule_in_order;
			$first_issue_no = $first_schedule_info_in_order['schedule_issue_no'];
			
			$ticket_data_list = $bet_info['ticket_data_list'];
			$printout_ticket_list = $bet_info['printout_ticket_list'];
			
			$order_request_params['series'] = $this->_getOrderSeries($params->optimize_ticket_list);
			$order_request_params['play_type'] = $params->play_type;
			$order_request_params['order_type'] = ORDER_TYPE_OF_OPTIMIZE;
			M()->startTrans();
			$order_id = D('Order')->addOrder($uid, $params->total_amount, $order_last_schedule['schedule_id'], $params->order_multiple, $params->coupon_id, $params->lottery_id, $orderSku, $first_schedule_info_in_order['schedule_id'], $params->order_identity, 0, 0, '', $order_request_params);
			$model = getTicktModel($params->lottery_id);
			ApiLog($order_id, 'opti');
			ApiLog($params->lottery_id, 'opti');
			if (!$order_id || !$model) {
				ApiLog('here', 'opti');
				M()->rollback();
				$this->_throwExcepiton(C('ERROR_CODE.BET_ERROR'));
			}
			$ticket_data_list_with_order_id = $model->appendOrderId($ticket_data_list, $order_id);
			ApiLog(print_r($ticket_data_list, true), 'opti');
			
			$add_ticket_result = $model->insertAll($ticket_data_list_with_order_id);
			if (!$add_ticket_result) {
				ApiLog('here11', 'opti');
				ApiLog($add_ticket_result, 'opti');
				M()->rollback();
				$this->_throwExcepiton(C('ERROR_CODE.BET_ERROR'));
			}
			
			$order_detail_list = $this->_getJcOrderDetailForOptimize($params->optimize_ticket_list, $order_id);
			$add_detail_result = D('JcOrderDetail')->insertAll($order_detail_list);
			if(!$add_detail_result) {
				ApiLog('after $$addDetail_result:'.$add_detail_result, 'opti');
				M()->rollback();
				\AppException::throwException(C('ERROR_CODE.BET_ERROR'));
			}
			
			M()->commit();
			
			$lack_money = $this->getRemainMoney($uid, $params->coupon_id,$params->lottery_id,$order_total_amount,$order_total_amount);
			if ($lack_money < 0) {
				return $this->buildResponseForPayOrder($order_id, $orderSku, abs($lack_money), C('ERROR_CODE.INSUFFICIENT_FUND'));
			}
		}
		
		$passwordFree = $this->checkPasswordFree($uid, $order_total_amount, $user_info['user_pre_order_limit'], $user_info['user_pre_day_limit'], $user_info['user_password_free'], $user_info['user_payment_password']);
		if ($passwordFree) {
			// TODO 失败重试机制
			$printout_result = $this->printOutTicket($user_info, $last_issue_no, $order_id, $params->lottery_id, $printout_ticket_list, $params->order_multiple, $first_issue_no);
			ApiLog('jc print_out :' . ($printout_result), 'opti');
			if (!$printout_result) {
				$this->_throwExcepiton(C('ERROR_CODE.PRINT_OUT_TICKET_ERROR'));
			}
			
			ApiLog('jc begin payOrderWithTransaction', 'opti');
			$this->payOrderWithTransaction($uid, $order_id, $params->coupon_id, $order_total_amount);
		}
		$code = ($passwordFree ? C('ERROR_CODE.SUCCESS') : C('ERROR_CODE.NEED_PAY_PASSWORD'));
		return $this->buildResponseForPayOrder($order_id, $orderSku, 0, $code);
	}
}