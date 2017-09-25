<?php

namespace Home\Lottery;

class SFCLottery extends LotteryBase{
	const JUMP_TYPE_FOR_APP = 0;
	const JUMP_TYPE_FOR_ORDER = 1;
	const JUMP_TYPE_FOR_BET = 2;
	const JUMP_TYPE_FOR_RECHARGE = 3;

	public function verifyParamsForWebPay($params, $user_info){
		$verified_params = array();
		// verify issue
		$issue_info = D('Issue')->queryIssueInfoByIssueNo($params['lottery_id'], $params['issue_no']);
		if (empty($issue_info)) {
			$this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['SZC_ISSUE_NO_EXISTS']);
		}
		$lottery_info = D('Lottery')->getLotteryInfo($issue_info['lottery_id']);

		if(strtotime($issue_info['issue_start_time'])>time()){
			$this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['ISSUE_NO_START']);
		}
		
		$end_time = strtotime($issue_info['issue_end_time']) - $lottery_info['lottery_ahead_endtime'];
		if ($end_time<time()) {
			$this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['SZC_OUT_OF_ISSUE_TIME']);
		}
		$lottery_id = $issue_info['lottery_id'];
		// verify bet format
		$formated_schedule_list = $this->_validateBetScheduleList($params['schedule_orders'], $params['lottery_id']);
		if (empty($formated_schedule_list)) {
			ApiLog('$formated_schedule_list:' . 'empty', 'sfc_ver');
			$this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['PAY_FAILED']);
		}
		// verify sure number
		$schedule_collection = $this->_validateSureScheduleNumber($formated_schedule_list, $params['lottery_id']);
		if (empty($schedule_collection)) {
			ApiLog('$$schedule_collection:' . 'sure no correct', 'sfc_ver');
			$this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['PAY_FAILED']);
		}
		$schedule_combinatorics = $this->combineBetScheduleList($formated_schedule_list, $params['lottery_id'], $schedule_collection);
		// TODO 需要检查拆票算法
		// verify stake
		$order_stake_count = $this->calculateOrderStakeCount($schedule_combinatorics, $params['lottery_id']);
		ApiLog('$order_stake:' . $order_stake_count . '====' . $order_stake_count * 2, 'sfc_ver');
		if(!$order_stake_count){
			$this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['PAY_FAILED']);
		}
		// verify amount
		$amount_is_correct = $this->verifyOrderTotalAmount($params['total_amount'], $order_stake_count, $params['multiple']);
		if (!$amount_is_correct) {
			ApiLog('$$amount_is_correct:' . $amount_is_correct, 'play');
			$this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['PAY_FAILED']);
		}
		ApiLog('$$amount_is_correct:' . $amount_is_correct, 'play');
		
		$verified_params['lottery_id'] = $params['lottery_id'];
		$verified_params['issue_no'] = $params['issue_no'];
		$verified_params['issue_id'] = $issue_info['issue_id'];
		$verified_params['formated_schedule_list'] = $formated_schedule_list;
		$verified_params['sure_schedule_collection'] = $schedule_collection;
		$verified_params['ticket_schedule_combinatorics'] = $schedule_combinatorics;
		$verified_params['order_stake'] = $order_stake_count;
		$verified_params['order_total_amount'] = $params['total_amount'];
		$verified_params['user_coupon_id'] = intval($params['user_coupon_id']);
		$verified_params['play_type'] = $params['play_type'];
		$verified_params['bet_type'] = $params['bet_type'];
		$verified_params['order_multiple'] = $params['multiple'];
		if (!$params->order_identity) {
			$order_identify = $this->buildOrderIdentity($user_info);
		} else {
			$order_identify = $params['order_identity'];
		}
		$verified_params['order_identity'] = $order_identify;
		return $verified_params;
	}

	public function verifyParams($params, $user_info){
		$verified_params = array();
		// verify issue
		$issue_info = D('Issue')->queryIssueInfoByIssueNo($params->lottery_id, $params->issue_no);
		if (empty($issue_info)) {
			$this->_throwExcepiton(C('ERROR_CODE.ISSUE_NO_EXIST'));
		}
		$lottery_info = D('Lottery')->getLotteryInfo($issue_info['lottery_id']);
		$end_time = strtotime($issue_info['issue_end_time']) - $lottery_info['lottery_ahead_endtime'];
		if ($end_time<time()) {
			$this->_throwExcepiton(C('ERROR_CODE.OUT_OF_ISSUE_TIME'));
		}
		
		if(strtotime($issue_info['issue_start_time'])>time()){
			$this->_throwExcepiton(C('ERROR_CODE.ISSUE_NO_START'));
		}
		
		$lottery_id = $issue_info['lottery_id'];
		// verify bet format
		$formated_schedule_list = $this->_validateBetScheduleList($params->schedule_orders, $params->lottery_id);
		if (empty($formated_schedule_list)) {
			ApiLog('$formated_schedule_list:' . 'empty', 'sfc_ver');
			$this->_throwExcepiton(C('ERROR_CODE.PARAM_ERROR'));
		}
		// verify sure number
		$schedule_collection = $this->_validateSureScheduleNumber($formated_schedule_list, $params->lottery_id);
		if (empty($schedule_collection)) {
			ApiLog('$$schedule_collection:' . 'sure no correct', 'sfc_ver');
			$this->_throwExcepiton(C('ERROR_CODE.PARAM_ERROR'));
		}
		$schedule_combinatorics = $this->combineBetScheduleList($formated_schedule_list, $params->lottery_id, $schedule_collection);
		// TODO 需要检查拆票算法
		// verify stake
		$order_stake_count = $this->calculateOrderStakeCount($schedule_combinatorics, $params->lottery_id);
		ApiLog('$order_stake:' . $order_stake_count . '====' . $order_stake_count * 2, 'sfc_ver');
		if(!$order_stake_count){
			$this->_throwExcepiton(C('ERROR_CODE.PARAM_ERROR'));
		}
		// verify amount
		$amount_is_correct = $this->verifyOrderTotalAmount($params->total_amount, $order_stake_count, $params->multiple);
		if (!$amount_is_correct) {
			ApiLog('$$amount_is_correct:' . $amount_is_correct, 'play');
			$this->_throwExcepiton(C('ERROR_CODE.TOTAL_AMOUNT_NO_EQUAL'));
		}
		ApiLog('$$amount_is_correct:' . $amount_is_correct, 'play');
		
		$verified_params['lottery_id'] = $params->lottery_id;
		$verified_params['issue_no'] = $params->issue_no;
		$verified_params['issue_id'] = $issue_info['issue_id'];
		$verified_params['formated_schedule_list'] = $formated_schedule_list;
		$verified_params['sure_schedule_collection'] = $schedule_collection;
		$verified_params['ticket_schedule_combinatorics'] = $schedule_combinatorics;
		$verified_params['order_stake'] = $order_stake_count;
		$verified_params['order_total_amount'] = $params->total_amount;
		$verified_params['user_coupon_id'] = intval($params->coupon_id);
		$verified_params['play_type'] = $params->play_type;
		$verified_params['bet_type'] = $params->bet_type;
		$verified_params['order_multiple'] = $params->multiple;
		if (!$params->order_identity) {
			$order_identify = $this->buildOrderIdentity($user_info);
		} else {
			$order_identify = $params->order_identity;
		}
		$verified_params['order_identity'] = $order_identify;
		ApiLog('$$verified_params:' . print_r($verified_params,true), 'cobet');
		
		return $verified_params;
	}

	private function _getSeriesNumber($lottery_id){
		if ($lottery_id == TIGER_LOTTERY_ID_OF_SFC_9) {
			$series_number = 9;
		} elseif ($lottery_id == TIGER_LOTTERY_ID_OF_SFC_14) {
			$series_number = 14;
		}
		return $series_number;
	}

	public function calculateOrderStakeCount($schedule_combinatorics, $lottery_id){
		$order_stake_count = 0;
		foreach ($schedule_combinatorics as $schedule_combinatoric) {
			$ticket_stake_count = $schedule_combinatoric['ticket_stake_count'];
			$order_stake_count += $ticket_stake_count;
		}
		return $order_stake_count;
	}

	public function getStakeCount($schedule_combinatoric, $series_number){ // 足彩注数计算
		import('@.Util.Combinatorics');
		$mathCombinatorics = new \Math_Combinatorics();
		$playList = $this->_getPlayList($schedule_combinatoric);
		ApiLog('play list:' . print_r($schedule_combinatoric, true) . '====' . var_export($playList, true), 'play');
		$selectCount = $series_number;
		$combinatorics = $mathCombinatorics->combinations($playList, $selectCount);
		$sum = 0;
		foreach ($combinatorics as $combinatoric) {
			$cartesians = $mathCombinatorics->array_cartesian($combinatoric);
			foreach ($cartesians as $cartesian) {
				$sum += $this->_getSeriesStakeCount($cartesian, array(
						$selectCount 
				));
			}
		}
		ApiLog('$sum:' . $sum, 'play');
		return $sum;
	}

	private function _getSeriesStakeCount(array $cartesian, array $series){
		$mathCombinatorics = new \Math_Combinatorics();
		$sum = 0;
		foreach ($series as $serie) {
			$combinatorics = $mathCombinatorics->combinations($cartesian, $serie);
			foreach ($combinatorics as $combinatoric) {
				$sum += array_product($combinatoric);
			}
		}
		ApiLog('_getSeriesStakeCount $sum:' . $sum, 'play');
		return $sum;
	}

	private function _getPlayList(array $scheduleOrders){
		$data = array();
		foreach ($scheduleOrders as $scheduleOrder) {
			ApiLog('$scheduleOrder $sum:' . print_r($scheduleOrder, true), 'play');
			
			$data[] = $this->_getPlayArray($scheduleOrder['bet_number']);
		}
		return $data;
	}

	private function _getPlayArray($bet_contents){
		$stakes = explode(',', $bet_contents);
		ApiLog('$stakes:' . print_r($stakes, true), 'play');
		
		$data[] = count($stakes);
		return $data;
	}

	public function combineBetScheduleList($formated_schedule_list, $lottery_id, $schedule_collection = array()){
		$max_series_number = $this->_getSeriesNumber($lottery_id);
		$schedule_combinatorics = $this->getScheduleCombinatorics($formated_schedule_list, $max_series_number, $schedule_collection, $lottery_id);
		
		ApiLog('$schedules:' . print_r($schedule_combinatorics, true), 'sfc');
		return $schedule_combinatorics;
	}

	public function getScheduleCombinatorics($formated_schedule_list, $max_series_number, $schedule_collection, $lottery_id){
		if (count($schedule_collection['sure'])) {
			return $this->_getScheduleCombinatoricsIncludeSure($formated_schedule_list, $max_series_number, $schedule_collection, $lottery_id);
		} else {
			return $this->_getScheduleCombinatorics($formated_schedule_list, $max_series_number, $lottery_id);
		}
	}

	private function _getScheduleCombinatoricsIncludeSure($formated_schedule_list, $max_series_number, $schedule_collection, $lottery_id){
		import('@.Util.Combinatorics');
		$sure_schedule_number = count($schedule_collection['sure']);
		$mathCombinatorics = new \Math_Combinatorics();
		$no_sure_combinatorics = $mathCombinatorics->combinations($schedule_collection['no_sure'], $max_series_number - $sure_schedule_number);
		$schedule_combinatorics = array();
		$series_number = $this->_getSeriesNumber($lottery_id);
		foreach ($no_sure_combinatorics as $no_sure_combinatoric) {
			$schedule_combinatoric = array_merge($no_sure_combinatoric, $schedule_collection['sure']);
			$ticket_stake_count = $this->getStakeCount($schedule_combinatoric, $max_series_number);
			ApiLog('$schedule_combinatoric:' . print_r($schedule_combinatoric, true), 'sfc_com');
				
			if($ticket_stake_count > 10000){
				$rebuild_schedule_combinatoric = $this->_reCombineScheduleList($schedule_combinatoric, $series_number);
				$schedule_combinatorics = array_merge($schedule_combinatorics, $rebuild_schedule_combinatoric );
			}else{
				$schedule_combinatoric['ticket_stake_count'] = $ticket_stake_count;
				$schedule_combinatorics[] = $schedule_combinatoric;
			}
		}
		return $schedule_combinatorics;
	}
	
	private function _reCombineScheduleList($schedule_combinatoric, $series_number){
		$new_schedule_combinatic_list = array() ;
		$before_split_schedule_list = array() ;
		$schedule_combinatorics = array();
		foreach($schedule_combinatoric as $idx=>$schedule_item){
			$bet_number = $schedule_item['bet_number'];
			$bet_option = explode(',', $bet_number);
			if(count($bet_option)>1){
				$before_split_schedule_list[$idx] = $schedule_item;
			}
		}
		
		ApiLog('before recombine :' .$this->_seq++.'==='. print_r($schedule_combinatoric, true), 'sfc_com');
		
		foreach($before_split_schedule_list as $idx=>$before_split_schedule_item){
			$split_schedule_bet_options = explode(',', $before_split_schedule_item['bet_number']);
			foreach($split_schedule_bet_options as $bet_option){
				$new_schedule_combinatic_list = $schedule_combinatoric;
				$split_schedule_item = $this->_rebuildScheduleItem($before_split_schedule_item, $bet_option);
				$new_schedule_combinatic_list[$idx] = $split_schedule_item;
				$ticket_stake_count = $this->getStakeCount($new_schedule_combinatic_list, $series_number);
				ApiLog('recombine $new_schedule_combinatic_list:' .$ticket_stake_count.'==='. print_r($new_schedule_combinatic_list, true), 'sfc_com');
				
				if($ticket_stake_count > 10000){
					ApiLog('recombine > 10000 $new_schedule_combinatic_list:' .$ticket_stake_count.'==='. print_r($new_schedule_combinatic_list, true), 'sfc_com');
					$rebuild_schedule_combinatoric = $this->_reCombineScheduleList($new_schedule_combinatic_list, $series_number);
					$schedule_combinatorics = array_merge($schedule_combinatorics, $rebuild_schedule_combinatoric );
				}else{
					ApiLog('recombine <<< 10000 $new_schedule_combinatic_list:' .$ticket_stake_count.'==='. print_r($new_schedule_combinatic_list, true), 'sfc_com');
					$new_schedule_combinatic_list['ticket_stake_count'] = $ticket_stake_count;
					$schedule_combinatorics[] = $new_schedule_combinatic_list;
				}
			}
			break;
		}
		return $schedule_combinatorics;
	}
	
	private function _rebuildScheduleItem($more_option_schedule_item,$bet_option){
		$new_schedule_item = $more_option_schedule_item;
		$new_schedule_item['bet_number'] = $bet_option;
		return $new_schedule_item;
	}

	private function _getScheduleCombinatorics($formated_schedule_list, $max_series_number){
		import('@.Util.Combinatorics');
		$mathCombinatorics = new \Math_Combinatorics();
		if (count($formated_schedule_list) == $max_series_number) {
			$schedule_combinatorics = array(
					$formated_schedule_list 
			);
		} else {
			$schedule_combinatorics = $mathCombinatorics->combinations($formated_schedule_list, $max_series_number);
		}
		$new_schedule_combinatorics = array();
		foreach ($schedule_combinatorics as $schedule_combinatoric) {
			$ticket_stake_count = $this->getStakeCount($schedule_combinatoric, $max_series_number);
			ApiLog('first schedule_combinatoric:' . $ticket_stake_count.'==='.print_r($schedule_combinatoric, true), 'sfc_com');
				
			if($ticket_stake_count > 10000){
				$rebuild_schedule_combinatoric = $this->_reCombineScheduleList($schedule_combinatoric, $max_series_number);
				$new_schedule_combinatorics = array_merge($new_schedule_combinatorics, $rebuild_schedule_combinatoric );
				ApiLog('first >10000:' . $ticket_stake_count.'==='.print_r($rebuild_schedule_combinatoric, true), 'sfc_com');
				
			}else{
				$schedule_combinatoric['ticket_stake_count'] = $ticket_stake_count;
				$new_schedule_combinatorics[] = $schedule_combinatoric;
			}
		}
		ApiLog('first new_schedule_combinatic_list:' . print_r($new_schedule_combinatorics, true), 'sfc_com');
		
		return $new_schedule_combinatorics;
	}

	private function _validateBetScheduleList($bet_schedule_list, $lottery_id){
		ApiLog('$$bet_schedule_list:' . print_r($bet_schedule_list, true), 'sfc');
		
		if (empty($bet_schedule_list)) {
			return false;
		}
		
		// 3,1,0
		$formated_schedule_list = array();
		foreach ($bet_schedule_list as $bet_schedule_item) {
			if(is_object($bet_schedule_item)){
				$bet_schedule_item = (array)$bet_schedule_item;
			}
			if (!preg_match('/^((\d+,?)+?)+$/', $bet_schedule_item['bet_number'])) {
				return false;
			}
			$schedule_seq = $bet_schedule_item['round_id'];
			$formated_schedule_list[$schedule_seq] = $bet_schedule_item;
		}
		
		ApiLog('$$formated_schedule_list:' . print_r($formated_schedule_list, true) . '===' . count($formated_schedule_list), 'sfc');
		
		// check count before count relist
		$schedule_number = count($formated_schedule_list);
		if ($schedule_number < 14 && $lottery_id == TIGER_LOTTERY_ID_OF_SFC_14) {
			return false;
		} elseif ($schedule_number < 9 && $lottery_id == TIGER_LOTTERY_ID_OF_SFC_9) {
			return false;
		}
		ksort($formated_schedule_list);
		return $formated_schedule_list;
	}

	private function _formatScheduleList($bet_schedule_list){
	}

	private function _validateSureScheduleNumber($formated_schedule_list, $lottery_id){
		$schedule_collection = array();
		foreach ($formated_schedule_list as $schedule_info) {
			if ($schedule_info['is_sure']) {
				$schedule_collection['sure'][] = $schedule_info;
			} else {
				$schedule_collection['no_sure'][] = $schedule_info;
			}
		}
		$sure_count = count($schedule_collection['sure']);
		if ($lottery_id == TIGER_LOTTERY_ID_OF_SFC_9) {
			if ($sure_count >= 9) {
				return false;
			}
		}
		return $schedule_collection;
	}

	public function addOrderAndTicketData($uid, $order_sku, $params,$order_type = 0){
		M()->startTrans();
		
		$extra_params['content'] = json_encode($params['formated_schedule_list']);
		$order_id = D('Order')->addOrder($uid, $params['order_total_amount'], $params['issue_id'], $params['order_multiple'], $params['user_coupon_id'], $params['lottery_id'], $order_sku, $params['issue_id'], $params['order_identity'], 0, 0, '', $extra_params,0,$order_type);
		
		if (!$order_id) {
			M()->rollback();
			return false;
		}
		
		$ticket_model = getTicktModel($params['lottery_id']);
		if (!$ticket_model) {
			M()->rollback();
			return false;
		}
		
		$devide_ticket_result = $this->_devideOverMultipleTicket($params['ticket_schedule_combinatorics'], $uid, $params);
		
		$printout_ticket_list = $devide_ticket_result['printout_ticket_list'];
		$ticket_data_list = $devide_ticket_result['ticket_data_list'];
		
		$devide_ticket_data_list = $ticket_model->appendOrderId($ticket_data_list, $order_id);
		
		$add_ticket_list_result = $ticket_model->insertAll($devide_ticket_data_list);
		if (!$add_ticket_list_result) {
			ApiLog('$addTickets:' . $add_ticket_list_result, 'opay');
			M()->rollback();
			return false;
		}
		
		M()->commit();
		return array(
				'order_id' => $order_id,
				'ticket_data_list' => $devide_ticket_data_list,
				'printout_ticket_list' => $printout_ticket_list,
				'issue_no' => $params['issue_info'] 
		);
	}

	private function _devideOverMultipleTicket($ticket_schedule_combinatorics, $uid, $params){
		$order_multiple = $params['order_multiple'];
		$lottery_id = $params['lottery_id'];
		
		// 倍数检查
		$max_multiple = getMaxMultipleByLotteryId($lottery_id);
		if ($order_multiple > $max_multiple) {
			$limit_multiple = $max_multiple;
		} else {
			$limit_multiple = $order_multiple;
		}
		
		// 单票金额检查
		$ticket_seq = 0;
		foreach ($ticket_schedule_combinatorics as $ticket_schedule_combinatoric) {
			$once_ticket_amount = $ticket_schedule_combinatoric['ticket_stake_count'] * LOTTERY_PRICE;
			$ticket_stake_count = $ticket_schedule_combinatoric['ticket_stake_count'];
			if ($ticket_schedule_combinatoric['ticket_stake_count'] * LOTTERY_PRICE > $ticket_schedule_combinatoric['ticket_stake_count']) {
			
			}
			
			$limit_ticket_amount = $once_ticket_amount * $limit_multiple;
			if ($limit_ticket_amount > BET_TICKET_AMOUNT_LIMIT) {
				$max_once_ticket_multiple = floor(BET_TICKET_AMOUNT_LIMIT / $once_ticket_amount);
				$once_ticket_multiple = $max_once_ticket_multiple;
			} else {
				$once_ticket_multiple = $limit_multiple;
			}
			$devide_ticket_num = ceil($order_multiple / $once_ticket_multiple);
			
			$ticket_bet_content = $this->_buildTicketBetContent($ticket_schedule_combinatoric);
			
			for($i = 0; $i < $devide_ticket_num; $i++) {
				if ($i == $devide_ticket_num - 1) {
					$ticket_multiple = $order_multiple - ($devide_ticket_num - 1) * $once_ticket_multiple;
				} else {
					$ticket_multiple = $once_ticket_multiple;
				}
				$ticket_amount = $once_ticket_amount * $ticket_multiple;
				$ticket_seq++;
				$ticket_lottery_id = $lottery_id;
				
				$ticket_data['uid'] = $uid;
				$ticket_data['issue_id'] = $params['issue_id'];
				$ticket_data['lottery_id'] = $ticket_lottery_id;
				$ticket_data['ticket_seq'] = $ticket_seq;
				$ticket_data['bet_number'] = $ticket_bet_content;
				$ticket_data['play_type'] = $params['play_type'];
				$bet_type = ($once_ticket_amount > LOTTERY_PRICE) ? C('BET_TYPE.MULTIPLE') : C('BET_TYPE.SINGLE');
				$ticket_data['bet_type'] = $bet_type;
				$ticket_data['stake_count'] = $ticket_stake_count;
				$ticket_data['total_amount'] = $ticket_amount;
				$ticket_data['ticket_status'] = C('ORDER_STATUS.UNPAID');
				$ticket_data['issue_no'] = $params['issue_no'];
				$ticket_data['last_issue_id'] = $params['issue_id'];
				$ticket_data['first_issue_id'] = $params['issue_id'];
				$ticket_data['ticket_multiple'] = $ticket_multiple;
				
				$printout_ticket_list[] = $this->buildNumberTicketItemForPrintout($ticket_seq, $params['play_type'], $bet_type, $ticket_bet_content, $ticket_stake_count, $ticket_amount, $ticket_multiple);
				$ticket_data_list[] = D('ZcsfcTicket')->buildTicketData($ticket_data);
			}
		}
		$devide_ticket_result['printout_ticket_list'] = $printout_ticket_list;
		$devide_ticket_result['ticket_data_list'] = $ticket_data_list;
		return $devide_ticket_result;
	}

	private function _buildTicketBetContent($ticket_schedule_combinatoric){
		$seperator = ',';
		$empty_bet_item = '#';
		$bet_item_list = array();
		foreach ($ticket_schedule_combinatoric as $bet_schedule_item) {
			$bet_item_list[$bet_schedule_item['round_id']] = $bet_schedule_item['bet_number'];
		}
		ksort($bet_item_list);
		$bet_content_list = '';
		for($i = 1; $i < 15; $i++) {
			if (isset($bet_item_list[$i])) {
				$bet_item_string = $this->_formatBetOption($bet_item_list[$i]);
			} else {
				$bet_item_string = $empty_bet_item;
			}
			$bet_content_list[$i] = $bet_item_string;
		}
		return implode(',', $bet_content_list);
	}

	private function _formatBetOption($bet_option_string){
		return str_replace(',', '|', $bet_option_string);
	}

    public function verifyParamsForCobet($params, $user_info){
        $verified_params = array();
        // verify issue
        $issue_info = D('Issue')->queryIssueInfoByIssueNo($params['lottery_id'], $params['issue_no']);
        if (empty($issue_info)) {
            ApiLog('ISSUE_NO_EXIST$params:'.print_r($params,true),'verifyParamsForCobet');
            return false;
        }
        $lottery_info = D('Lottery')->getLotteryInfo($issue_info['lottery_id']);
        $end_time = strtotime($issue_info['issue_end_time']) - $lottery_info['lottery_ahead_endtime'];
        if ($end_time<time()) {
            ApiLog('OUT_OF_ISSUE_TIME$params:'.print_r($params,true),'verifyParamsForCobet');
            return false;
        }

        if(strtotime($issue_info['issue_start_time'])>time()){
            ApiLog('ISSUE_NO_START$params:'.print_r($params,true),'verifyParamsForCobet');
            return false;
        }

        $lottery_id = $issue_info['lottery_id'];
        // verify bet format
        $formated_schedule_list = $this->_validateBetScheduleList($params['schedule_orders'], $params['lottery_id']);
        if (empty($formated_schedule_list)) {
            ApiLog('PARAM_ERROR$params:'.print_r($params,true),'verifyParamsForCobet');
            return false;
        }
        // verify sure number
        $schedule_collection = $this->_validateSureScheduleNumber($formated_schedule_list, $params['lottery_id']);
        if (empty($schedule_collection)) {
            ApiLog('$$schedule_collection:' . 'sure no correct'.print_r($params,true),'verifyParamsForCobet');
            return false;
        }
        $schedule_combinatorics = $this->combineBetScheduleList($formated_schedule_list, $params['lottery_id'], $schedule_collection);
        // TODO 需要检查拆票算法
        // verify stake
        $order_stake_count = $this->calculateOrderStakeCount($schedule_combinatorics, $params['lottery_id']);
        ApiLog('$order_stake:' . $order_stake_count . '====' . $order_stake_count * 2, 'verifyParamsForCobet');
        if(!$order_stake_count){
            ApiLog('PARAM_ERROR2:'.print_r($params,true),'verifyParamsForCobet');
            return false;
        }
        // verify amount
        $amount_is_correct = $this->verifyOrderTotalAmount($params['total_amount'], $order_stake_count, $params['multiple']);
        if (!$amount_is_correct) {
            ApiLog('TOTAL_AMOUNT_NO_EQUAL:'.print_r($params,true),'verifyParamsForCobet');
            return false;
        }
        ApiLog('$$amount_is_correct:' . $amount_is_correct, 'verifyParamsForCobet');

        $verified_params['lottery_id'] = $params['lottery_id'];
        $verified_params['issue_no'] = $params['issue_no'];
        $verified_params['issue_id'] = $issue_info['issue_id'];
        $verified_params['formated_schedule_list'] = $formated_schedule_list;
        $verified_params['sure_schedule_collection'] = $schedule_collection;
        $verified_params['ticket_schedule_combinatorics'] = $schedule_combinatorics;
        $verified_params['order_stake'] = $order_stake_count;
        $verified_params['order_total_amount'] = $params['total_amount'];
        $verified_params['user_coupon_id'] = intval($params['coupon_id']);
        $verified_params['play_type'] = $this->_getPlayType($params['play_type']);
        $verified_params['bet_type'] = $params['bet_type'];
        $verified_params['order_multiple'] = $params['multiple'];
        if (!$params['order_identity']) {
            $order_identify = $this->buildOrderIdentity($user_info);
        } else {
            $order_identify = $params['order_identity'];
        }
        $verified_params['order_identity'] = $order_identify;
        return $verified_params;
    }

    private function _getPlayType($play_type){
        if($play_type != 101 || $play_type != '101'){
            $play_type = '101';
        }
        return $play_type;

    }
}