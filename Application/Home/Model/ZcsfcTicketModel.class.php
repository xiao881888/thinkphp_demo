<?php
namespace Home\Model;
use Think\Model;
class ZcsfcTicketModel extends JcModel{
	
	public function getBetTypesByOrderId($order_id){
		$where = array();
		$where['order_id'] = $order_id;
		return $this->where($where)->getField('bet_type', true);
	}
	
	public function getTicketInfos($order_id){
		$where = array();
		$where['order_id'] = $order_id;
		return $this->where($where)->select();
	}
	
	public function getFormatPrintoutOdds($order_id){
		$where = array();
		$where['order_id'] = $order_id;
		$result = $this->where($where)->getField('printout_odds', true);
		$odds_result = array();
		foreach ($result as $v){
			$odds_infos = json_decode($v, true);
			if(is_array($odds_infos[0])){
				foreach ($odds_infos as $odds_info){
					$this->_analyOdds($odds_info, $odds_result);
				}
			}else{
				$this->_analyOdds($odds_infos, $odds_result);
			}
		}
		return $odds_result;
	}


	public function getTicketsByOrderId($order_id,$uid){
		$where['order_id'] = $order_id;
        //$where['uid'] = $uid;
        return $this->field('bet_number, play_type, bet_type, stake_count, winnings_status, total_amount, ticket_seq, ticket_multiple, first_issue_id, last_issue_id, lottery_id,ticket_status,printout_time ,winnings_bonus')
            ->where($where)
            ->select();
	}
	
	public function buildTicketData($ticket_data){
		$ticket_data['create_time'] = getCurrentTime();
		return $ticket_data;
	}
	
	public function appendOrderId($ticket_data_list, $orderId) {
		$formated_ticket_data_list = array();
		foreach ($ticket_data_list as $ticket_data_item) {
			$ticket_data_item['order_id'] = $orderId;
			$formated_ticket_data_list[] = $ticket_data_item;
		}
		return $formated_ticket_data_list;
	}
}