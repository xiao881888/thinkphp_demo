<?php
namespace Home\Lottery;

class LotteryBase {
	protected $_msg_map;
	public function __construct(){
		$this->_msg_map = C('WEB_PAY_MESSAGE');
	}
	
	// web支付的重写这个方法
	protected function _throwExcepiton($error_code, $error_msg = ''){
		\AppException::throwException($error_code, $error_msg);
	}
	
	protected function verifyOrderTotalAmount($order_total_amount, $order_stake_count, $order_multiple){
		if(!$order_total_amount || !$order_stake_count || !$order_multiple){
			return false;
		}
		ApiLog('$$$order_total_amount:' . $order_total_amount .'=='. $order_stake_count.'==='.bccomp($order_total_amount,$order_multiple * $order_stake_count * LOTTERY_PRICE), 'sfc_ver');
		
		if ($order_total_amount==$order_multiple * $order_stake_count * LOTTERY_PRICE) {
			return true;
		}
		return false;
	}

	protected function buildOrderIdentity($user_info){
		$randomStr = strtoupper(random_string(4));
		return $user_info['user_telephone'] . date('ymdhis') . $randomStr . $user_info['uid'];
	}
	
	protected function buildNumberTicketItemForPrintout($ticket_seq, $play_type, $bet_type, $bet_number, $ticket_stake_count, $ticket_amount, $ticket_multiple){
		return array(
				"ticket_seq" => $ticket_seq,
				"play_type" => intval($play_type), // 玩法（标准、追加）
				"bet_type" => intval($bet_type), // 选号方式（单式、复式、胆拖）
				"stake_count" => intval($ticket_stake_count),
				"bet_content" => array(
						'number' => $bet_number
				),
				'ticket_multiple' => $ticket_multiple,
				"amount" => intval($ticket_amount)
		);
	}
	
}