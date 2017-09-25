<?php

namespace Home\Lottery;
use Home\Util\Factory;

class JCLottery extends LotteryBase{

	private function _validateBetContent($bet_schedule_orders){
		ApiLog('$bet_schedule_orders:'.print_r($bet_schedule_orders,true),'cobet');
		foreach ($bet_schedule_orders as $bet_schedule_order_item) {
			if(is_object($bet_schedule_order_item)){
				$bet_schedule_order_item = (array)$bet_schedule_order_item;
			}
			ApiLog('$$bet_schedule_order_item:'.print_r($bet_schedule_order_item,true),'cobet');
			if (!preg_match('/^(\d+:(\d+,?)+\|?)+$/', $bet_schedule_order_item['bet_number'])) {
				return false;
			}
		}
		return true;
	}
	
	private function _validateSeries(array $series) {	// @TODO 改为正常算法
		$seriesInclude = array();
		$multiStageCount = 0;
		ApiLog('_checkSeries:'.print_r($series,true), 'cobet');
	
		foreach ($series as $betType) {
			$mergeCount = C("MERGE_COUNT.$betType");
			$seriesCount = count($mergeCount['series']);
			ApiLog('$seriesCount:'.$betType.'==='.$mergeCount.'===='.$seriesCount, 'cobet');
			if (!$mergeCount || !$seriesCount) {
				return false;
			}
			$seriesInclude[] = $seriesCount;
			if ($seriesCount > 1) {
				$multiStageCount++;
			}
		}
		$seriesInclude = array_unique($seriesInclude);
		ApiLog('$seriesInclude:'.print_r($seriesInclude,true), 'cobet');
		ApiLog('$check:'.count($seriesInclude).'==='.$multiStageCount, 'cobet');
	
		// 自由过关和多串过关不允许同时存在  并且  不允许有多个多串过关
		return count($seriesInclude) == 1 && $multiStageCount <= 1;
	}
	
	private function _checkSerieSureCount(array $series, $scheduleOrders) {
		foreach ($series as $serie) {
			$verifyMerge = $this->_verifySureCount($scheduleOrders, $serie);
			if(!$verifyMerge) {
				return false;
			}
		}
		return true;
	}
	
	private function _verifySureCount(array $scheduleOrders, $series) {
		$data = $this->_getSureSchedule($scheduleOrders);
		$sureCount 	= count($data['sure']);
		$mergeCount = C("MERGE_COUNT.$series");
		$limit 		= min($mergeCount['series']);
		return $sureCount < $limit;
	}
	
	private function _getSureSchedule(array $scheduleOrders) {
		$data = array();
		foreach ($scheduleOrders as $scheduleOrder) {
			if(is_object($scheduleOrder)){
				$scheduleOrder = (array)$scheduleOrder;
			}
			if($scheduleOrder['is_sure']) {
				$data['sure'][] = $scheduleOrder;
			} else {
				$data['no_sure'][] = $scheduleOrder;
			}
		}
		return $data;
	}
	
	public function verifyParams($params, $user_info){
		$verified_params = array();
		$bet_number_is_correct = $this->_validateBetContent($params->schedule_orders);
		if(!$bet_number_is_correct){
			$this->_throwExcepiton(C('ERROR_CODE.BET_NUMBER_ERROR'));
		}
		$series_list = explode(',', $params->series);
		$series_is_correct = $this->_validateSeries($series_list);
		if(!$series_is_correct){
			$this->_throwExcepiton(C('ERROR_CODE.BET_NUMBER_ERROR'));
		}
		$sure_count_is_correct = $this->_checkSerieSureCount($series_list, $params->schedule_orders);
		if(!$sure_count_is_correct){
			$this->_throwExcepiton(C('ERROR_CODE.BET_NUMBER_ERROR'));
		}
		foreach($params->schedule_orders as $schedule_info){
			if(is_object($schedule_info)){
				$schedule_info = (array)$schedule_info;
			}
			$schedule_ids_in_order[] = $schedule_info['schedule_id'];
		}
		ApiLog('ids:'.print_r($schedule_ids_in_order,true) ,'cobet');
		$schedule_infos_in_order = D('JcSchedule')->getScheduleIssueNo($schedule_ids_in_order);
		if(!$schedule_infos_in_order){
			$this->_throwExcepiton(C('ERROR_CODE.SCHEDULE_NO_ERROR'));
		}
		
		$lottery_info = D('Lottery')->getLotteryInfo($params->lottery_id);
		$this->checkScheduleOutOfTime($schedule_infos_in_order, $lottery_info);
		
		$order_stake_count = 0;
		$verifyObj	= Factory::createVerifyJcObj($params->lottery_id);
		if (isJcMix($params->lottery_id)) {
			$formated_schedule_orders = $this->formatRequestScheduleOrders($params->schedule_orders);
			$tickets_from_combination = $verifyObj->convertScheduleOrderToTickets($formated_schedule_orders, $params->series, $params->lottery_id);
			ApiLog('$tickets_from_combination:'.empty($tickets_from_combination).'----'.count($tickets_from_combination), 'cobet');
			if (empty($tickets_from_combination)) {
				$this->_throwExcepiton(C('ERROR_CODE.BET_NUMBER_ERROR'));
			} elseif (count($tickets_from_combination) > 2000) {
				$this->_throwExcepiton(C('ERROR_CODE.OVER_TICKET_COMBINATION_LIMIT'));
			}
			
			foreach ($tickets_from_combination as $ticket) {
				$series = $ticket['bet_type'];
				$ticket_schedule_list = $this->buildBetScheduleListInTicket($ticket);
				$stake_count_for_one_ticket = $verifyObj->getStakeCount($ticket_schedule_list, $series);
				$order_stake_count += $stake_count_for_one_ticket;
			}
			ApiLog('$$$order_stake_count:'.$order_stake_count, 'cobet');
		} else {
			$tiger_play_type = C('MAPPINT_JC_PLAY_TYPE.'.$params->play_type);
			if ($tiger_play_type == C('JC_PLAY_TYPE.ONE_STAGE')) {	// 如果是单关
				$order_stake_count = $this->caculateOrderStakeCountForOneStage($params->schedule_orders);
			} elseif ($tiger_play_type == C('JC_PLAY_TYPE.MULTI_STAGE')) {
				$order_stake_count = $this->caculateOrderStakeCountForMultiStage($params->schedule_orders, $tiger_play_type, $series_list, $params->lottery_id, $params->multiple);
				ApiLog('$$zzz:'.$order_stake_count.'==='.$params->play_type.'==='.print_r($params->schedule_orders,true), 'cobet');
			}
		}
		$stake_count_is_correct = $this->verifyStakeCountAndTotalAmountByOrder($order_stake_count, $params->stake_count, $params->multiple, $params->total_amount);
		if(!$stake_count_is_correct){
			$this->_throwExcepiton(C('ERROR_CODE.STAKE_COUNT_NO_EQUAL'));
		}
		
		$verified_params['lottery_id'] = $params->lottery_id;
		$verified_params['schedule_orders'] = $params->schedule_orders;
		$verified_params['total_amount'] = $params->total_amount;
		$verified_params['stake_count'] = $params->stake_count;
		$verified_params['user_coupon_id'] = $params->coupon_id;
		$verified_params['series'] = $params->series;
		$verified_params['play_type'] = $params->play_type;
		$verified_params['order_multiple'] = $params->multiple;
		$verified_params['order_identity'] = $params->order_identity;
		return $verified_params;
	}
	
	protected function verifyStakeCountAndTotalAmountByOrder($verify_order_stake_count, $order_stake_count_param, $multiple, $order_total_amount) {
		$stake_count_is_correct	= ($verify_order_stake_count == $order_stake_count_param);
		$order_amount_calc_by_stake_count 	= $verify_order_stake_count * C('LOTTERY_PRICE') * $multiple;
		$total_amount_is_correct 	 	= bccomp($order_amount_calc_by_stake_count, $order_total_amount, 2) === 0;
		$is_correct = ( $stake_count_is_correct && $total_amount_is_correct );
		ApiLog('jc stake:'.$stake_count_is_correct.'==='.$verify_order_stake_count.'==='.$order_stake_count_param, 'opay');
		ApiLog('jc stake $allowAmount:'.$total_amount_is_correct.'==='.$order_total_amount.'==='.bccomp($order_amount_calc_by_stake_count, $order_total_amount, 2), 'opay');
		return $is_correct;
	}
	
	protected function caculateOrderStakeCountForOneStage($formated_schedule_orders){
		$order_stake_count = 0;
		foreach ($formated_schedule_orders as $schedule_order) {
            if(is_object($schedule_order)){
                $schedule_order = (array)$schedule_order;
            }
			$bet_item_list = $this->parseBetNumber($schedule_order['bet_number']);
			ApiLog('parseBetNumber:'.print_r($bet_item_list,true), 'cobet');
			$stake_count_for_one_schedule = 0;
			foreach($bet_item_list as $lottery_id=>$bet_options){
				$stake_count_for_one_schedule += count($bet_options);
			}
			ApiLog('$stake_count_for_one_schedule :'.$stake_count_for_one_schedule, 'cobet');
			$order_stake_count += $stake_count_for_one_schedule;
		}
		return $order_stake_count;
	}
	
	//bet_number 601:3,1,0|602:3,1
	protected function parseBetNumber($bet_number_string) {
		$bet_item_list	= array();
		$bet_content_list = explode('|', $bet_number_string);
		foreach ($bet_content_list as $bet_content) {
			$bet_content_info 	= explode(':', $bet_content);
			$lottery_id = $bet_content_info[0];
			$bet_options = explode(',', $bet_content_info[1]);
			asort($bet_options);
			$bet_item_list[$lottery_id] = $bet_options;
		}
		return $bet_item_list;
	}
	
	protected function caculateOrderStakeCountForMultiStage($formated_schedule_orders, $playType, $series_list, $lotteryId, $multiple){
		ApiLog('$formated_schedule_orders:'.print_r($formated_schedule_orders,true),'cobet');
		
		$verifyObj 	= Factory::createVerifyJcObj($lotteryId);
    	$order_stake_count = 0;
    	foreach ($series_list as $series) {
    		$max_series_count = $verifyObj->getMaxSeriesCount($formated_schedule_orders, $series, $lotteryId);
    		ApiLog('$$maxSelectCount :'.$max_series_count, 'cobet');
    		if(!$max_series_count){
    			return false;
    		}
    		$all_combinations_by_schedules = $verifyObj->getScheduleCombinatorics($formated_schedule_orders, $max_series_count);
    		foreach ($all_combinations_by_schedules as $combination_item) {
    			$stake_count_for_one_combination = $verifyObj->getStakeCount($combination_item, $series);
    			ApiLog('$$stake_count_for_one_combination :'.print_r($stake_count_for_one_combination,true), 'cobet');
	    		$order_stake_count += $stake_count_for_one_combination;
    		}
    	}
    	return $order_stake_count;
	}
	
	protected function checkScheduleOutOfTime($schedule_info_list, $lottery_info){
		foreach ($schedule_info_list as $schedule_info) {
			$out_of_time = strtotime($schedule_info['schedule_end_time']) < (time() + intval($lottery_info['lottery_ahead_endtime']));
			ApiLog('mix end time :' . $schedule_info['schedule_end_time'] . '======' . date('Y-m-d H:i:s'), 'cobet');
			ApiLog('mix end sss time :' . $out_of_time . '========' . strtotime($schedule_info['schedule_end_time']) . '======' . (time() + intval($lottery_info['lottery_ahead_endtime'])), 'cobet');
			if($out_of_time){
				$this->_throwExcepiton(C('ERROR_CODE.OUT_OF_ISSUE_TIME'));
			}
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

	protected function formatRequestScheduleOrders($schedule_orders){
		ApiLog('formatRequestScheduleOrders:'.print_r($schedule_orders,true),'cobet');
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
		ApiLog('$formated_schedule_orders:'.print_r($formated_schedule_orders,true),'cobet');
		return $formated_schedule_orders;
	}
}