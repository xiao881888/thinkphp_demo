<?php 
namespace Home\Model;
use Think\Model;

abstract class TicketModel extends Model {

	public function buildTicketData($uid, $issueId, $betNumber, $playType, $stakeCount, $totalAmount, $orderId, $ticketSeq, $betType, $ticket_multiple, $first_issue_id = 0, $lottery_id = 0){
		return array(
				'uid' => $uid,
				'issue_id' => $issueId,
				'bet_number' => $betNumber,
				'ticket_seq' => $ticketSeq,
				'play_type' => $playType,
				'stake_count' => $stakeCount,
				'total_amount' => $totalAmount,
				'order_id' => $orderId,
				'bet_type' => $betType,
				'create_time' => getCurrentTime(),
				'modify_time' => getCurrentTime(),
				'ticket_multiple' => $ticket_multiple,
				'lottery_id' => $lottery_id,
				'last_issue_id' => $issueId,
				'first_issue_id' => $first_issue_id 
		);
	}
    
    public function detail($orderId){
    	$where = array();
    	$where['order_id'] = $orderId;
    	return $this->where($where)->select();
    }
    
    
    public function deleteTicketByOrderId($orderId) {
        $condition = array('order_id'=>$orderId, 'ticket_status'=>C('TICKET_STATUS.UN_PRINTOUT'));
        $data = array('ticket_status'=>C('TICKET_STATUS.DELETE'));
        return $this->where($condition)
                    ->save($data);
    }
    
    public function getTicketsByOrderId($order_id,$uid){
    	if(empty($uid)){
    		return false;
    	}
    	$where['order_id'] = $order_id;
    	//$where['uid'] = $uid;
		return $this->field('bet_number, play_type, bet_type, stake_count, winnings_status, total_amount, ticket_seq, first_issue_id, last_issue_id, lottery_id,printout_time ,winnings_bonus ,ticket_multiple,ticket_status')
                	->where($where)
                	->select();
    }
}