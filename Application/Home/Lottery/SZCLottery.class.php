<?php

namespace Home\Lottery;
use Home\Util\Factory;

class SZCLottery extends LotteryBase{

	public function verifyParams($params, $user_info){
		$verified_params = array();
		$issue_id = $params->issue_id;
		$issue_info	= D('Issue')->getIssueInfo($issue_id);
		if(empty($issue_info)){
			$this->_throwExcepiton(C('ERROR_CODE.ISSUE_NO_EXIST'));
		}
		
		$lottery_info = D('Lottery')->getLotteryInfo($issue_info['lottery_id']);
		if(empty($lottery_info) || !$lottery_info['lottery_status']){
			$this->_throwExcepiton(C('ERROR_CODE.ISSUE_NO_START'));
		}
		$uid = $user_info['uid'];
		$out_of_time = ( strtotime($issue_info['issue_end_time']) - time() <= $lottery_info['lottery_ahead_endtime'] );
		if($out_of_time){
			$this->_throwExcepiton(C('ERROR_CODE.OUT_OF_ISSUE_TIME'));
		}

		if(strtotime($issue_info['issue_start_time'])>time()){
			$this->_throwExcepiton(C('ERROR_CODE.ISSUE_NO_START'));
		}
		
		$checkTicketsCode = $this->checkNumberTickets($params->tickets, $issue_info['lottery_id']);
		if($checkTicketsCode!=C('ERROR_CODE.SUCCESS')){
			$this->_throwExcepiton($checkTicketsCode);
		}

		$total_amount = $this->calcSzcOrderTotalAmountForOneTime($params->tickets,$params->multiple);
		if(bccomp($total_amount,$params->total_amount) != 0){
            $this->_throwExcepiton(C('ERROR_CODE.TOTAL_AMOUNT_NO_EQUAL'));
        }
		
		$verified_params['lottery_id'] = $params->lottery_id;
		$verified_params['issue_no'] = $params->issue_no;
		$verified_params['issue_id'] = $issue_info['issue_id'];
		$verified_params['follow_times'] = $params->follow_times;
		$verified_params['user_coupon_id'] = $params->coupon_id;
		$verified_params['tickets'] = $params->tickets;
		$verified_params['order_identity'] = $params->order_identity;
		$verified_params['order_multiple'] = $params->multiple;
		return $verified_params;
	}

    protected function calcSzcOrderTotalAmountForOneTime(array $tickets, $multiple){
        $totalAmount = 0;
        foreach ($tickets as $ticket) {
            $totalAmount += $ticket['total_amount'];
        }
        return $totalAmount * $multiple;
    }

	protected function checkNumberTickets(array $tickets, $lotteryId) {
		$verifyNumber = Factory::createVerifyObj($lotteryId);
		foreach ( $tickets as $ticket ) {
			if(is_object($ticket)){
				$ticket = (array)$ticket;
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
}