<?php

namespace Home\Controller;

class CobetSchemeController extends CobetBaseController{

	private function _genResponseDataForRequestPayUrl($uid, $params, $raw_json_params){
		$encrypt_key = queryClientDesEncryptKeyBySessionCode($params->session);
		if (empty($encrypt_key)) {
			\AppException::throwException(C('ERROR_CODE.SESSION_ERROR'));
		}
		$result['pay_url'] = $this->buildEncryptPayUrl($uid, $params, $raw_json_params);
		return $result;
	}

	private function _buildSchemeIdentity($user_info){
		$randomStr = strtoupper(random_string(4));
		return $user_info['user_telephone'] . date('ymdhis') . $randomStr . $user_info['uid'];
	}

	public function genPayUrlForSubmitScheme($params){
		$user_info = $this->getAvailableUser($params->session);
		$verified_params = $this->verifyCobetParams($params, $user_info);
		if (!$verified_params) {
			$this->_throwExcepiton(C('ERROR_CODE.PARAM_ERROR'));
		}
		$params->order_identity = $this->_buildSchemeIdentity($user_info);
		$lottery_id = $verified_params['lottery_id'];
		$money_pay_for_scheme = bcmul($verified_params['unit_amount'], intval($verified_params['subscribe'] + $verified_params['ensure']), 2);
        ApiLog('$money_pay_for_scheme:' .$money_pay_for_scheme, 'cobet');
		$uid = $user_info['uid'];
		// check balance
		$money_to_be_paid = $this->getRemainMoneyForWebPay($uid,$lottery_id, $money_pay_for_scheme, $money_pay_for_scheme);
		if ($money_to_be_paid > 0) {
			return $this->buildResponseForPayScheme(0, '', abs($money_to_be_paid), C('ERROR_CODE.INSUFFICIENT_FUND'));
		}
		$response_data = $this->_genResponseDataForRequestPayUrl($uid, $params, json_encode($params));
		return $this->_buildResponse($response_data);
	}

	private function _buildResponse($response_data){
        $code = C('ERROR_CODE.SUCCESS');
		if ($response_data['money'] > 0) {
			$code = C('ERROR_CODE.INSUFFICIENT_FUND');
		}
		if ($response_data['pay_url']) {
			$code = C('ERROR_CODE.SUCCESS');
		}
		return array(
				'result' => $response_data,
				'code' => $code 
		);
	}

	public function genPayUrlForJoinScheme($params){
		$user_info = $this->getAvailableUser($params->session);
		$uid = $user_info['uid'];
		$map['scheme_id'] = $params->project_id;
		$scheme_info = D('CobetScheme')->where($map)->find();
		$verified_params = $this->verifyParamsForJoin($params, $user_info, $scheme_info);
		if (!$verified_params) {
			$this->_throwExcepiton(C('ERROR_CODE.PARAM_ERROR'));
		}
        $params->issue_id = $scheme_info['scheme_issue_id'];
        $params->lottery_id = $scheme_info['lottery_id'];
		
		$money_pay_for_join = $verified_params['scheme_bought_amount'];
		$money_to_be_paid = $this->getRemainMoneyForWebPay($uid,  $verified_params['lottery_id'], $money_pay_for_join, $money_pay_for_join);
		if ($money_to_be_paid > 0) {
			return $this->buildResponseForPayScheme(0, '', abs($money_to_be_paid), C('ERROR_CODE.INSUFFICIENT_FUND'));
		}
		$response_data = $this->_genResponseDataForRequestPayUrl($uid, $params, json_encode($params));
		return $this->_buildResponse($response_data);
	}

	public function submitScheme($params){
		ApiLog('params:' . print_r($params, true), 'cobet');
		
		$user_info = $this->getAvailableUser($params->session);
		$verified_params = $this->verifyCobetParams($params, $user_info);
		if (!$verified_params) {
			$this->_throwExcepiton(C('ERROR_CODE.PARAM_ERROR'));
		}
		ApiLog('$verified_params:' . print_r($verified_params, true), 'cobet');
		$lottery_id = $verified_params['lottery_id'];
		$scheme_total_amount = $verified_params['total_amount'];
		$money_pay_for_scheme = bcmul($verified_params['unit_amount'], intval($verified_params['subscribe'] + $verified_params['ensure']), 2);
		$uid = $user_info['uid'];
		ApiLog('$money_pay_for_scheme:' . $scheme_total_amount . '===' . $money_pay_for_scheme, 'cobet');
		
		$money_to_be_paid = $this->getRemainMoney($uid, $verified_params['user_coupon_id'], $lottery_id, $money_pay_for_scheme, $money_pay_for_scheme);
		if ($money_to_be_paid < 0) {
			return $this->buildResponseForPayScheme(0, '', abs($money_to_be_paid), C('ERROR_CODE.INSUFFICIENT_FUND'));
		}
		
		$exist_scheme_info = D('CobetScheme')->getInfoByIdentity($verified_params['scheme_identity']);
		if ($exist_scheme_info) {
			if ($exist_scheme_info['scheme_status'] > COBET_SCHEME_STATUS_OF_NO_BEGIN) {
				return $this->buildResponseForPayScheme($exist_scheme_info['scheme_id'], $exist_scheme_info['scheme_sku'], 0, C('ERROR_CODE.SUCCESS'));
			}
		}
		
		$scheme_sn = buildSchemeSN($uid);
		try {
			M()->startTrans();
			$scheme_id = D('CobetScheme')->addScheme($uid, $scheme_sn, $verified_params);
			ApiLog('schemeid:' . $scheme_id, 'cobet');
			if (isJCLottery($lottery_id)) {
				$add_jc_result = $this->addJcOrder($uid, $scheme_sn, $params);
			}
			
			$guarantee_amount = bcmul($verified_params['scheme_amount_per_unit'], $verified_params['scheme_guarantee_unit'], 2);
			$self_buy_amount = bcmul($verified_params['scheme_amount_per_unit'], $verified_params['scheme_bought_unit'], 2);
			ApiLog('$guarantee_amount:' . $guarantee_amount . '===' . $self_buy_amount . '===' . print_r($verified_params, true), 'cobet');
			$pay_result = true;
			if ($guarantee_amount || $self_buy_amount) {
				$pay_result = $this->payAndRecord($uid, $scheme_id, $verified_params['user_coupon_id'], $verified_params, $guarantee_amount, $self_buy_amount);
			}
			$update_result = $this->updateStatusForBegin($scheme_id, $verified_params);
			if ($pay_result && $update_result) {
				M()->commit();
				if (isJCLottery($lottery_id)) {
					$cobet_order_id = $add_jc_result['orderId'];
					$order_map['order_id'] = $cobet_order_id;
					$cobet_order_info = D('CobetOrder')->where($order_map)->find();
					$update_data['scheme_issue_id'] = $cobet_order_info['first_issue_id'];
					$update_data['cobet_order_id'] = $cobet_order_id;
					$scheme_map['scheme_id'] = $scheme_id;
					D('CobetScheme')->where($scheme_map)->save($update_data);
				}
				if ($verified_params['scheme_bought_unit'] == $verified_params['scheme_total_unit']) {
					$this->completeCobetScheme($scheme_id);
				}
			} else {
				M()->rollback();
				$this->_throwExcepiton(C('ERROR_CODE.DATABASE_ERROR'));
			}
		} catch (\Think\Exception $e) {
			M()->rollback();
			throw new \Think\Exception($e->getMessage(), $e->getCode());
		}
		return $this->buildResponseForPayScheme($scheme_id, $scheme_sn, 0, C('ERROR_CODE.SUCCESS'));
	}

	public function joinScheme($params){
		$user_info = $this->getAvailableUser($params->session);
		$uid = $user_info['uid'];
		$scheme_id = $params->project_id;
		$map['scheme_id'] = $params->project_id;
		$scheme_info = D('CobetScheme')->where($map)->find();
		$verified_params = $this->verifyParamsForJoin($params, $user_info, $scheme_info);
		if (!$verified_params) {
			$this->_throwExcepiton(C('ERROR_CODE.PARAM_ERROR'));
		}
		
		$money_pay_for_join = $verified_params['scheme_bought_amount'];
		$money_to_be_paid = $this->getRemainMoney($uid, $verified_params['user_coupon_id'], $verified_params['lottery_id'], $money_pay_for_join, $money_pay_for_join);
		if ($money_to_be_paid < 0) {
			return $this->buildResponseForPayScheme(0, '', abs($money_to_be_paid), C('ERROR_CODE.INSUFFICIENT_FUND'));
		}
		
		try {
			M()->startTrans();
			$guarantee_amount = 0;
			$self_buy_amount = $verified_params['scheme_bought_amount'];
			ApiLog('aaa:' . print_r($verified_params, true) . '===' . $scheme_id, 'cobet_join');
			$pay_result = true;
			if ($self_buy_amount) {
				$pay_result = $this->payAndRecord($uid, $scheme_id, $verified_params['user_coupon_id'], $verified_params, 0, $self_buy_amount);
			}
			$scheme_data['scheme_bought_unit'] = $scheme_info['scheme_bought_unit'] + $verified_params['scheme_bought_unit'];
			$scheme_data['scheme_bought_rate'] = $scheme_data['scheme_bought_unit'] / $scheme_info['scheme_total_unit'];
			$update_result = true;
			if ($scheme_info['scheme_status'] == C('COBET_SCHEME_STATUS.NO_BEGIN_BOUGHT')) {
				$scheme_data['scheme_status'] = COBET_SCHEME_STATUS_OF_ONGOING;
			}
			// if ($scheme_data['scheme_bought_unit'] == $scheme_info['scheme_total_unit']) {
			// $scheme_data['scheme_status'] = COBET_SCHEME_STATUS_OF_SCHEME_COMPLETE;
			// }
			ApiLog('aaabvbb:' . print_r($scheme_data, true) . '===' . $scheme_id, 'cobet_join');
			if ($scheme_data) {
				$update_result = D('CobetScheme')->where($map)->save($scheme_data);
			}
			if ($pay_result && $update_result) {
				M()->commit();
				if ($scheme_data['scheme_bought_unit'] == $scheme_info['scheme_total_unit']) {
					$this->completeCobetScheme($scheme_id);
				}
			} else {
				M()->rollback();
			}
		} catch (\Think\Exception $e) {
			M()->rollback();
			throw new \Think\Exception($e->getMessage(), $e->getCode());
		}
		return array(
				'result' => '',
				'code' => C('ERROR_CODE.SUCCESS') 
		);
	}

	public function cancelScheme($params){
		$user_info = $this->getAvailableUser($params->session);
		$uid = $user_info['uid'];
		
		$map['scheme_id'] = $params->project_id;
		$scheme_info = D('CobetScheme')->where($map)->find();
		if ($scheme_info['uid'] != $uid) {
			$this->_throwExcepiton(C('ERROR_CODE.PARAM_ERROR'));
		}
		if ($scheme_info['scheme_status'] > C('COBET_SCHEME_STATUS.NO_BEGIN_BOUGHT')) {
			$this->_throwExcepiton(C('ERROR_CODE.SCHEME_IS_BOUGHT_BY_OTHERS'));
		}
		$cancel_result = $this->cancelCobetScheme($params->project_id);
		if ($cancel_result) {
			return array(
					'result' => '',
					'code' => C('ERROR_CODE.SUCCESS') 
			);
		} else {
			$this->_throwExcepiton(C('ERROR_CODE.DATABASE_ERROR'));
		}
	}

	public function querySchemeList($api){
		$scheme_uid = $api->user_id;
		$uid = D('Session')->getUid($api->session);
		$user_info = D('User')->getUserInfo($uid);
		
		$scheme_list = $this->_getSchemeList($api, $scheme_uid, $uid);
		
		$format_scheme_list = $this->_formatSchemeList($scheme_list, $user_info);
		return array(
				'result' => array(
						'list' => $format_scheme_list 
				),
				'code' => C('ERROR_CODE.SUCCESS') 
		);
	}

	private function _getSchemeList($api, $scheme_uid, $uid){
		$status_list = $this->_getStatusListByFilter($api->filter, $api->session);
		$is_index = $api->filter == 0 ? 1 : 0;
		if ($api->filter != 2) {
			$scheme_list = D('CobetScheme')->getSchemeListByUid($status_list, $scheme_uid, $api->sort, $api->offset, $api->limit, $api->lottery_id, $api->sub_sort, $api->filter, $uid,$is_index);
		} else {
			$scheme_list = D('CobetSchemeView')->getSchemeListByUid($status_list, $scheme_uid, $api->sort, $api->offset, $api->limit, $api->lottery_id, $api->sub_sort, $api->filter, $uid);
		}
		return $scheme_list;
	}

	private function _getStatusListByFilter($filter, $session){
		if ($filter == 0) {
			return array(
					C('COBET_SCHEME_STATUS.NO_BEGIN_BOUGHT'),
					C('COBET_SCHEME_STATUS.ONGOING') 
			);
		} else {
			$this->getAvailableUser($session);
			return array(
					C('COBET_SCHEME_STATUS.NO_BEGIN_BOUGHT'),
					C('COBET_SCHEME_STATUS.ONGOING'),
					C('COBET_SCHEME_STATUS.SCHEME_COMPLETE'),
					C('COBET_SCHEME_STATUS.PRINTOUT') 
			);
		}
	}

	private function _formatSchemeList($scheme_list, $user_info = array()){
		$data = array();
		foreach ($scheme_list as $scheme_info) {
			$scheme_user_info = D('User')->getUserInfo($scheme_info['uid']);
			$bought_count = D('CobetRecord')->getBoughtCount($scheme_info['scheme_id']);
			$data[] = array(
					'id' => $scheme_info['scheme_id'],
					'lottery_id' => $scheme_info['lottery_id'],
					'lottery_name' => D('Lottery')->getLotteryNameById($scheme_info['lottery_id']),
					'nick_name' => emptyToStr($scheme_user_info['user_nick_name']),
					'user_name' => emptyToStr($scheme_user_info['user_name']),
					'history_gain' => $this->_getHistoryGain($scheme_info['uid']),
					'total_amount' => $scheme_info['scheme_total_amount'],
					'member' => empty($bought_count) ? 0 : $bought_count,
					'total_unit' => $scheme_info['scheme_total_unit'],
					'subscribe' => $scheme_info['scheme_bought_unit'],
					'ensure' => $scheme_info['scheme_guarantee_unit'],
					'is_subscribe' => empty(D('CobetRecord')->isBought($scheme_info['scheme_id'], $user_info['uid'])) ? 0 : 1 
			);
		}
		return $data;
	}

	private function _getHistoryGain($uid){
		$history_data = D('CobetScheme')->getHistoryDataById($uid);
		$history_data = json_decode($history_data, true);
		$history_gain = empty($history_data['history_gain']) ? 0 : $history_data['history_gain'];
		return $history_gain . '%';
	}

	public function fetchSchemeDetail($api){
		$scheme_id = $api->project_id;
		$scheme_info = D('CobetOrderView')->getInfo($scheme_id);
		\AppException::ifNoExistThrowException($scheme_info, C('ERROR_CODE.ORDER_NO_EXIST'));
		
		$scheme_user_info = D('User')->getUserInfo($scheme_info['uid']);

        $uid = D('Session')->getUid($api->session);
        $user_info = D('User')->getUserInfo($uid);
		
		$order_id = $scheme_info['order_id'];
		if ($order_id) {
			$lottery_id = D('Order')->getLotteryId($order_id);
			$order_view_model = $this->_getOrderViewModel($lottery_id);
			$order_info = $order_view_model->getOrderInfoByOrderId($order_id);
			\AppException::ifNoExistThrowException($order_info, C('ERROR_CODE.ORDER_NO_EXIST'));
			
			$issue_id = D('Order')->getIssueId($order_id);
			
			if (isJc($lottery_id)) {
				$order_info['series'] = A('Order')->getBetType($lottery_id, $order_id);
				$order_info['jc_info'] = A('Order')->getJcInfo($lottery_id, $order_id, $order_info['order_status']);
				$end_time = D('JcSchedule')->getEndTime($issue_id);
			} else {
				$ticketList = A('Order')->getTickets($lottery_id, $order_id, $order_info['uid'], $order_info['order_status']);
				\AppException::ifNoExistThrowException($ticketList, C('ERROR_CODE.ORDER_DETAIL_NO_EXIST'));
				if (isZcsfc($lottery_id)) {
					// bettype字段
					$order_info['series'] = A('Order')->getBetType($lottery_id, $order_id);
					$order_info['jc_info'] = A('Order')->getZcsfcInfo($order_id);
				} else {
					$order_info['tickets'] = $ticketList['ticket_list'];
				}
				
				if ($order_info['order_status'] == 5) {
					$order_info['failure_amount'] = $order_info['total_amount'];
				} else {
					$order_info['failure_amount'] = $ticketList['failure_amount'];
				}
				$end_time = D('Issue')->getEndTime($issue_id);
			}

			if (isJc($lottery_id)) {
				$ticket_detail_list = A('Order')->buildTicketDetailListForCobetScheme($lottery_id, $scheme_info['uid'], $order_id);
				$order_info['failure_amount'] = $ticket_detail_list['failure_amount'];
			}
			
			$issue_no = $order_info['issue_no'];
			$sku = $order_info['sku'];
			$total_amount = $order_info['total_amount'];
		} else {
			$lottery_id = $scheme_info['lottery_id'];
			$issue_id = $scheme_info['scheme_issue_id'];
			if (isJc($lottery_id)) {
				$order_id = $scheme_info['cobet_order_id'];
				$order_view_model = $this->_getCobetOrderViewModel($lottery_id);
				$order_info = $order_view_model->getOrderInfoByOrderId($order_id);
				\AppException::ifNoExistThrowException($order_info, C('ERROR_CODE.ORDER_NO_EXIST'));
				$order_info['series'] = $this->getBetType($lottery_id, $order_info['id']);
				$order_info['jc_info'] = $this->getJcInfo($lottery_id, $order_info['id']);
				$end_time = D('JcSchedule')->getEndTime($issue_id);
				$sku = $order_info['sku'];
			} else {
				$bet_order_info = json_decode($scheme_info['scheme_bet_content'], true);
				if (isZcsfc($lottery_id)) {
                    $order_info['multiple'] = $bet_order_info['multiple'];
					// bettype字段
					$order_info['series'] = '';
					$order_info['jc_info'] = $this->getZcsfcInfo($bet_order_info);
				} else {
                    $order_info['multiple'] = $bet_order_info['multiple'];
					$ticketList = $this->getTickets($bet_order_info);
					\AppException::ifNoExistThrowException($ticketList, C('ERROR_CODE.ORDER_DETAIL_NO_EXIST'));
					$order_info['tickets'] = $ticketList['ticket_list'];
				}
				$end_time = D('Issue')->getEndTime($issue_id);
				$sku = $bet_order_info['order_identity'];
			}
			$orderInfo['type'] = ORDER_TYPE_OF_COBET;
			$order_info['failure_amount'] = 0;
			$total_amount = $scheme_info['scheme_total_amount'];
			
			$issue_no = D('Issue')->getIssueNoById($scheme_info['scheme_issue_id']);
		}
		
		$status_list = A('order')->getCobetOrderStatusDesc($scheme_info);
		
		$subscribe = D('CobetRecord')->getCobetUnitCountBySchemeId($scheme_id, $user_info['uid']);
		$author_subscribe = D('CobetRecord')->getCobetUnitCountBySchemeId($scheme_id, $scheme_user_info['uid']);
		$total_subscribe = D('CobetRecord')->getCobetUnitCountBySchemeId($scheme_id);
		
		$lottery = D('Lottery')->getLotteryInfo($lottery_id);
        $end_timestamp = strtotime($end_time) - $lottery['lottery_ahead_endtime'];
        $cobet_end_timestamp = strtotime($end_time) - $lottery['lottery_ahead_endtime'] - C('COBET_SCHEME_AHEAD_END_TIME');


		return array(
				'result' => array(
						'sku' => emptyToStr($sku),
						'lottery_id' => $scheme_info['lottery_id'],
						'lottery_name' => $lottery['lottery_name'],
						'lottery_image' => $lottery['lottery_image'],
						'issue_no' => emptyToStr($issue_no),
						'prize_time' => empty($order_info['prize_time']) ? 0 : strtotime($order_info['prize_time']),
						'winnings_bonus' => empty($order_info['winnings_bonus']) ? 0 : $order_info['winnings_bonus'],
						'total_amount' => empty($total_amount) ? 0 : $total_amount,
						'failure_amount' => empty($order_info['failure_amount']) ? 0 : $order_info['failure_amount'],
						'addition_amount' => empty($order_info['order_plus_award_amount']) ? 0 : $order_info['order_plus_award_amount'],
						'status' => emptyToStr($status_list['status']),
						'status_desc' => emptyToStr($status_list['status_desc']),
						'nick_name' => emptyToStr($scheme_user_info['user_nick_name']),
						'user_name' => emptyToStr($scheme_user_info['user_telephone']),
						'history_gain' => $this->_getHistoryGain($scheme_info['uid']),
						'user_id' => $scheme_info['uid'],
						'bet_time' => strtotime($scheme_info['scheme_createtime']),
						'total_unit' => $scheme_info['scheme_total_unit'],
						'total_subscribe' => empty($total_subscribe) ? 0 : $total_subscribe,
						'subscribe' => empty($subscribe) ? 0 : $subscribe,
						'ensure' => $scheme_info['scheme_guarantee_unit'],
						'is_subscribe' => empty(D('CobetRecord')->isBought($scheme_id, $user_info['uid'])) ? 0 : 1,
						'author_subscribe' => empty($author_subscribe) ? 0 : $author_subscribe,
						'commission' => $scheme_info['scheme_commission_rate'],
						'history_record' => $this->_getHistoryRecord($scheme_info['uid']),
						'type' => $scheme_info['scheme_show_status'],
						'multiple' => empty($order_info['multiple']) ? 0 : $order_info['multiple'],
						'series' => empty($order_info['series']) ? '' : $order_info['series'],
						'issue_id' => $issue_id,
						'prize_num' => emptyToStr($order_info['prize_num']),
						'official_prize_time' => empty($order_info['official_prize_time']) ? 0 : strtotime($order_info['official_prize_time']),
						'end_time' => empty($end_timestamp) ? 0 : $end_timestamp,
                        'copurchase_end_time' => empty($cobet_end_timestamp) ? 0 : $cobet_end_timestamp,
						'jc_info' => empty($order_info['jc_info']) ? array() : $order_info['jc_info'],
						'tickets' => empty($order_info['tickets']) ? array() : $order_info['tickets'] ,
                        'commission_amount' => $scheme_info['scheme_commission_amount'],
                        'unit_amount' => $this->_getSchemeUnitWinningBonus($scheme_info),
                        'user_amount' => $this->_getUserWinningBonus($user_info['uid'],$scheme_info['scheme_id']),
                        'order_id' => empty($scheme_info['order_id']) ? 0 : $scheme_info['order_id'],
                        'user_failure_amount' => $this->_getUserFailureAmount($user_info['uid'],$scheme_info['scheme_id']),
                        'ensure_used' => $this->_getEnsureUsed($scheme_info['scheme_id']),
				),
				'code' => C('ERROR_CODE.SUCCESS') 
		);
	}

    private function _getUserFailureAmount($uid,$scheme_id){
        $user_failure_amount = D('CobetRecord')->getUserFailureAmount($uid,$scheme_id);
        return empty($user_failure_amount) ? 0 : $user_failure_amount;
    }

    private function _getEnsureUsed($scheme_id){
        $ensure_used = D('CobetRecord')->getEnsureUsed($scheme_id);
        return empty($ensure_used) ? 0 : $ensure_used;
    }

	private function _getSchemeUnitWinningBonus($scheme_info){
	    $total_amount = bcsub($scheme_info['scheme_winning_bonus'],$scheme_info['scheme_commission_amount'],2);
	    $total_unit = $scheme_info['scheme_bought_unit'] - $scheme_info['scheme_refund_unit'];
        return bcdiv($total_amount,$total_unit,2);
    }

    private function _getUserWinningBonus($uid,$scheme_id){
        $user_winning_bonus = D('CobetRecord')->getUserWinningBonus($uid,$scheme_id);
        return empty($user_winning_bonus) ? 0 : $user_winning_bonus;
    }

	public function getZcsfcInfo($bet_order_info){
		$zcsfc_list = array();
		$schedule_orders = $bet_order_info['schedule_orders'];
		foreach ($schedule_orders as $schedule_order) {
			$schedule_seq_list[] = $schedule_order['round_id'];
			$bet_number_list[$schedule_order['round_id']] = $schedule_order['bet_number'];
			$sure_list[$schedule_order['round_id']] = $schedule_order['is_sure'];
		}
		$issue_no = $bet_order_info['issue_no'];
		$sfc_schedule_list = D('ZcsfcSchedule')->queryScheduleListByIssueNoAndSeq($issue_no, $schedule_seq_list);
		foreach ($sfc_schedule_list as $schedule_info) {
			$zcsfc_list[] = array(
					'home' => $schedule_info['sfc_schedule_home_team'],
					'guest' => $schedule_info['sfc_schedule_guest_team'],
					'league' => $schedule_info['sfc_schedule_league'],
					'zq_start_date' => strtotime($schedule_info['sfc_schedule_game_start_time']),
					'round_no' => $schedule_info['sfc_schedule_seq'],
					'issue_no' => $schedule_info['sfc_schedule_issue_no'],
					'lottery_id' => $bet_order_info['lottery_id'],
					'play_type' => $bet_order_info['play_type'],
					'betting_order' => array(
							'betting_num' => $bet_number_list[$schedule_info['sfc_schedule_seq']]
					),
					'result_odds' => array(
							'prize_num' => emptyToStr($schedule_info['sfc_schedule_prize_result']) 
					),
					'score' => array(
							'final' => emptyToStr($schedule_info['sfc_schedule_final_score']) 
					),
					'is_sure' => empty($sure_list[$schedule_info['sfc_schedule_seq']]) ? 0 : $sure_list[$schedule_info['sfc_schedule_seq']]
			);
		}
		return $zcsfc_list;
	}

	public function getTickets($order_info){
		$tickets = $order_info['tickets'];
		$ticketList = array();
		$failure_amount = 0;
		foreach ($tickets as $ticket) {
			$ticketList[] = array(
					'bet_number' => $ticket['bet_number'],
					'play_type' => $ticket['play_type'],
					'bet_type' => $ticket['bet_type'],
					'stake_count' => $ticket['stake_count'],
					'winnings_status' => 0,
					'ticket_status' => 0,
					'ticket_multiple' => $order_info['order_multiple'] 
			);
		}
		if (!$ticketList) {
			return false;
		}
		$res['ticket_list'] = $ticketList;
		$res['failure_amount'] = $failure_amount;
		return $res;
	}

	public function getBetType($lotteryId, $orderId){
		$model = getCobetTicktModel($lotteryId);
		$betTypes = $model->getBetTypesByOrderId($orderId);
		$betTypes = array_unique($betTypes);
		return implode(',', $betTypes);
	}

	public function getJcInfo($lotteryId, $orderId, $order_status = 0){
		$jcInfo = D('CobetJcOrderDetailView')->getInfos($orderId);
		
		$model = getCobetTicktModel($lotteryId);
		$odds_list = $model->getFormatPrintoutOdds($orderId);
		foreach ($jcInfo as $k => $v) {
			$bet_content = $v['bet_content'];
			$bet_content_array = json_decode($bet_content, true);
			$schedule_issue_no = $v['schedule_issue_no'];
			$schedule_issue_no = substr($schedule_issue_no, 3);
			foreach ($bet_content_array as $k_lottery_id => $content) {
				$odds_key = $schedule_issue_no . '_' . $k_lottery_id;
				$odds = $odds_list[$odds_key];
				
				// 如果找不到赔率，显示投注时候的内容
				if (empty($odds)) {
					$odds = array();
					foreach ($content as $op_v) {
						$odds[$op_v] = '';
					}
				}
				$format_odds = array();
				$format_odds = getFormatOdds($k_lottery_id, json_encode($odds), $order_status);
				if ($order_status == 5) {
					
					$jcInfo[$k]['score'] = array(
							'half' => '',
							'final' => '' 
					);
				}
				
				$betting_order = $jcInfo[$k]['betting_order'] ? $jcInfo[$k]['betting_order'] : array();
				$betting_order = array_merge($betting_order, $format_odds);
				
				if (sizeof($betting_order) > 0) {
					$jcInfo[$k]['betting_order'] = array_merge($betting_order, $format_odds);
				}
			}
			$jcInfo[$k]['round_no'] = getWeekName($v['schedule_week']) . $v['round_no'];
			
			if (isJczq($lotteryId)) {
				$let_point = array_search_value('letPoint', json_decode($v['schedule_odds'], true));
				$base_point = array_search_value('basePoint', json_decode($v['schedule_odds'], true));
			} else {
				$let_point = array_search_value('letPoint', json_decode($v['schedule_odds'], true));
				$base_point = array_search_value('basePoint', json_decode($v['schedule_odds'], true));
			}
			if (isJcMix($lotteryId)) {
				$format_result_odds = json_decode($v['schedule_odds'], true);
			} else {
				$format_result_odds[$lotteryId] = json_decode($v['schedule_odds'], true);
			}
			$jcInfo[$k]['let_point'] = $let_point ? $let_point : '';
			$jcInfo[$k]['base_point'] = $base_point ? $base_point : '';
			$jcInfo[$k]['result_odds'] = empty($format_result_odds) ? array() : $format_result_odds;
			
			unset($jcInfo[$k]['schedule_odds']);
		}
		return $jcInfo;
	}

	private function _getHistoryRecord($uid){
		$history_data = D('CobetScheme')->getHistoryDataById($uid);
		$history_data = json_decode($history_data, true);
		$history_record_list = explode('/',$history_data['history_record_desc']) ;
        $history_record_list[1] = empty($history_record_list[1]) ? 0 : $history_record_list[1];
        $history_record_list[0] = empty($history_record_list[0]) ? 0 : $history_record_list[0];
        return sprintf(C('COBET_HISTORY_RECORD_DESC'),$history_record_list[1],$history_record_list[0]);
	}

	private function _getStatusDesc($scheme_info, $order_winnings_status = 0){
		switch ($scheme_info['scheme_status']) {
			case C('COBET_SCHEME_STATUS.CANCEL') :
				$data['status'] = C('API_COBET_SCHEME_STATUS.CANCEL');
				$data['status_desc'] = C('API_COBET_SCHEME_STATUS_DESC.CANCEL');
				break;
			case C('COBET_SCHEME_STATUS.CANCEL_REFUND') :
				$data['status'] = C('API_COBET_SCHEME_STATUS.CANCEL_REFUND');
				$data['status_desc'] = C('API_COBET_SCHEME_STATUS_DESC.CANCEL_REFUND');
				break;
			case C('COBET_SCHEME_STATUS.FAILED') :
				$data['status'] = C('API_COBET_SCHEME_STATUS.FAILED');
				$data['status_desc'] = C('API_COBET_SCHEME_STATUS_DESC.FAILED');
				break;
			case C('COBET_SCHEME_STATUS.FAILED_REFUND') :
				$data['status'] = C('API_COBET_SCHEME_STATUS.FAILED_REFUND');
				$data['status_desc'] = C('API_COBET_SCHEME_STATUS_DESC.FAILED_REFUND');
				break;
			case C('COBET_SCHEME_STATUS.PRINTOUT') :
				if ($order_winnings_status == C('ORDER_WINNINGS_STATUS.WAITING')) {
					$data['status'] = C('API_COBET_SCHEME_STATUS.WAITING_PRIZE');
					$data['status_desc'] = C('API_COBET_SCHEME_STATUS_DESC.WAITING_PRIZE');
				} else if ($order_winnings_status == C('ORDER_WINNINGS_STATUS.NO')) {
					$data['status'] = C('API_COBET_SCHEME_STATUS.NO_WINNING');
					$data['status_desc'] = C('API_COBET_SCHEME_STATUS_DESC.NO_WINNING');
				} else if ($order_winnings_status == C('ORDER_WINNINGS_STATUS.YES')) {
					$data['status'] = C('API_COBET_SCHEME_STATUS.WINNING');
					$data['status_desc'] = C('API_COBET_SCHEME_STATUS_DESC.WINNING');
				}
				break;
			case C('COBET_SCHEME_STATUS.NO_BEGIN') :
				$data['status'] = C('API_COBET_SCHEME_STATUS.CANCEL');
				$data['status_desc'] = C('API_COBET_SCHEME_STATUS_DESC.CANCEL');
				break;
			default :
				$data['status'] = C('API_COBET_SCHEME_STATUS.ONGOING');
				$data['status_desc'] = C('API_COBET_SCHEME_STATUS_DESC.ONGOING');
				break;
		}
		return $data;
	}

	private function _getOrderViewModel($lottery_id){
		$model_name = (isJc($lottery_id) ? 'JcOrderView' : 'OrderView');
		return D($model_name);
	}

	private function _getCobetOrderViewModel($lottery_id){
		$model_name = (isJc($lottery_id) ? 'CobetJcOrderView' : 'CobetOrderView');
		return D($model_name);
	}

	public function queryHistoryRecordList($api){
		$scheme_uid = $api->user_id;
		$scheme_user_info = D('User')->getUserInfo($scheme_uid);
		\AppException::ifNoExistThrowException($scheme_user_info, C('ERROR_CODE.USER_NOT_EXIST'));
		$user_scheme_list = D('CobetScheme')->getSchemeListByUid(array(
				C('COBET_SCHEME_STATUS.PRINTOUT') 
		), $scheme_uid, 0, $api->offset, $api->limit);
		return array(
				'result' => array(
						'nick_name' => emptyToStr($scheme_user_info['user_nick_name']),
						'avatar' => emptyToStr($scheme_user_info['user_avatar']),
						'user_name' => emptyToStr($scheme_user_info['user_telephone']),
						'history_gain' => $this->_getHistoryGain($scheme_uid),
						'history_record' => $this->_getHistoryRecord($scheme_uid),
						'list' => $this->_formatHistoryRecordList($user_scheme_list) 
				),
				'code' => C('ERROR_CODE.SUCCESS') 
		);
	}

	private function _formatHistoryRecordList($user_scheme_list){
		$data = array();
		foreach ($user_scheme_list as $scheme_info) {
			$status_list = A('order')->getCobetOrderStatusDesc($scheme_info);
			$data[] = array(
					'id' => $scheme_info['scheme_id'],
					'lottery_id' => $scheme_info['lottery_id'],
					'lottery_name' => D('Lottery')->getLotteryNameById($scheme_info['lottery_id']),
					'date' => strtotime($scheme_info['scheme_createtime']),
					'winnings_bonus' => $scheme_info['scheme_winning_bonus'],
					'status' => emptyToStr($status_list['status']),
					'status_desc' => emptyToStr($status_list['status_desc']),
					'total_amount' => bcsub($scheme_info['scheme_total_amount'],$scheme_info['scheme_refund_amount'],2),
					'member' => D('CobetRecord')->getBoughtCount($scheme_info['scheme_id']) 
			);
		}
		return $data;
	}

	public function queryCobetUserList($api){
		$scheme_id = $api->project_id;
		$scheme_info = D('CobetScheme')->getInfo($scheme_id);
		\AppException::ifNoExistThrowException($scheme_info, C('ERROR_CODE.ORDER_NO_EXIST'));
		$record_list = D('CobetRecord')->getBoughtListBySchemeId($scheme_id, 0, $api->offset, $api->limit);
		return array(
				'result' => array(
						'list' => $this->_formatRecordList($record_list) 
				),
				'code' => C('ERROR_CODE.SUCCESS') 
		);
	}

	private function _formatRecordList($record_list){
		$data = array();
		foreach ($record_list as $record) {
			$user_info = D('User')->getUserInfo($record['uid']);
			$data[] = array(
					'nick_name' => $user_info['user_nick_name'],
					'user_name' => $user_info['user_telephone'],
					'unit' => $record['record_bought_unit'],
					'date' => strtotime($record['record_createtime']) 
			);
		}
		return $data;
	}


	public function cancelCobetScheme($id){
		$scheme_info = D('CobetScheme')->getInfo($id);
		if ($scheme_info['scheme_status'] != COBET_SCHEME_STATUS_OF_NO_BEGIN_BOUGHT) {
			A('CobetOrder')->notifyWarningMsg('状态异常:' . $scheme_info['scheme_id']);
			ApiLog('状态异常:' . print_r($scheme_info, true), 'cancelCobetScheme');
			return false;
		}
		
		$refund_code = $this->_refundCancelSchemeAmount($scheme_info);
		if (!$refund_code) {
			A('CobetOrder')->notifyWarningMsg('退款失败:' . $scheme_info['scheme_id']);
			ApiLog('111退款失败:' . print_r($scheme_info, true), 'cancelCobetScheme');
			return false;
		}
		
		return true;
	}

	private function _refundCancelSchemeAmount($scheme_info){
		M()->startTrans();
		$change_code = D('Crontab/CobetScheme')->changeSchemeStatusById($scheme_info['scheme_id'], C('COBET_SCHEME_STATUS.CANCEL'));
		if (!$change_code) {
			ApiLog('sql1:' . D('Crontab/CobetScheme')->getLastSql() . '$scheme_info:' . print_r($scheme_info, true), 'cancelCobetScheme');
			M()->rollback();
			return false;
		}
		
		$record_list = D('CobetRecord')->getRecordListBySchemeId($scheme_info['scheme_id']);
		foreach ($record_list as $record) {
			
			if ($record['uid'] != $scheme_info['uid']) {
				ApiLog('uid:' . $record['uid'] . '当前退款uid异常$record:' . print_r($record, true), 'cancelCobetScheme');
				M()->rollback();
				return false;
			}
			
			if ($record['record_status'] != COBET_STATUS_OF_CONSUME) {
				ApiLog('uid:' . $record['uid'] . '当前退款record_status异常$record:' . print_r($record, true), 'cancelCobetScheme');
				M()->rollback();
				return false;
			}
			
			if ($record['type'] == C('COBET_TYPE.BOUGHT')) {
				$user_account_log_type = C('USER_ACCOUNT_LOG_TYPE.COBET_BOUGHT_REFUND');
			} elseif ($record['type'] == C('COBET_TYPE.GUARANTEE_FROZEN')) {
				$user_account_log_type = C('USER_ACCOUNT_LOG_TYPE.COBET_GUARANTEE_REFUND');
			}
			$user_coupon_id = $record['user_coupon_id'];
			$refund_coupon_amount = $record['record_user_coupon_consume_amount'];
			$refund_money = $record['record_user_cash_amount'];
			$refund_code = A('CobetOrder')->refundAmount($record['uid'], $user_coupon_id, $refund_coupon_amount, $refund_money, $user_account_log_type);
			if (!$refund_code) {
				ApiLog('uid:' . $record['uid'] . '当前退款失败$record:' . print_r($record, true), 'cancelCobetScheme');
				M()->rollback();
				return false;
			}
			
			$record_status = COBET_STATUS_OF_REFUND;
			$refund_unit = $record['record_bought_unit'];
			$refund_amount = $record['record_bought_amount'];
			;
			if ($refund_unit < 0 || $refund_amount < 0) {
				A('CobetOrder')->notifyWarningMsg('2退款数据异常$record_id:' . $record['record_id']);
				ApiLog('2退款数据异常$scheme_info:' . print_r($scheme_info, true), 'cancelCobetScheme');
				return false;
			}
			
			$save_status = D('Crontab/CobetRecord')->saveRefundStatus($record['record_id'], $refund_amount, $refund_unit, $record_status);
			if (!$save_status) {
				A('CobetOrder')->notifyWarningMsg('保存记录失败$record:' . $record['record_id']);
				ApiLog('sql:' . D('CobetRecord')->getLastSql(), 'cancelCobetScheme');
				ApiLog('保存记录失败$record:' . print_r($record, true), 'cancelCobetScheme');
				return false;
			}
		}
		$change_code = D('Crontab/CobetScheme')->changeSchemeStatusById($scheme_info['scheme_id'], C('COBET_SCHEME_STATUS.CANCEL_REFUND'));
		if (!$change_code) {
			ApiLog('sql2:' . D('Crontab/CobetScheme')->getLastSql() . '当前退款失败$scheme_info:' . print_r($scheme_info, true), 'cancelCobetScheme');
			M()->rollback();
			return false;
		}
		
		M()->commit();
		return true;
	}
}