<?php
namespace Home\Model;
use Think\Model;
/**
 * @date 2015-1-27
 * @author tww <merry2014@vip.qq.com>
 */
class JcTicketModel extends JcModel{
	
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
		return $this->field('ticket_content, play_type, bet_type, stake_count, winnings_status, total_amount, ticket_seq, ticket_multiple, first_issue_id, last_issue_id, lottery_id,ticket_status,printout_time ,winnings_bonus,printout_odds')
					->where($where)
					->select();
	}
	
	
	public function buildTicketData($uid, $ticketSeq, $playType, $stakeCount, $betType, $ticketContent, $lastIssueId, $issueNos, $ticket_total_amount, $ticket_multiple, $first_issue_id=0, $lottery_id=0){
		return array(
				'uid' => $uid,
				'ticket_seq' => $ticketSeq,
				'play_type' => $playType,
				'stake_count' => $stakeCount,
				'total_amount' => $ticket_total_amount,
				'ticket_status' => TICKET_STATUS_OF_UN_PRINTOUT,
				'create_time' => getCurrentTime(),
				'bet_type' => $betType,
				'issue_nos' => $issueNos,
				'ticket_content' => $ticketContent,
				'last_issue_id' => $lastIssueId,
				'first_issue_id' => $first_issue_id,
				'lottery_id' => $lottery_id,
				'ticket_multiple' => $ticket_multiple 
		);
	}
	
	public function buildTicketDataForEmergency($uid, $ticketSeq, $playType, $stakeCount, $betType, $ticketContent, $lastIssueId, $issueNos,$ticket_total_amount,$ticket_multiple, $ticketOdds) {
		return array(
				'uid' => $uid,
				'ticket_seq' 	 => $ticketSeq,
				'play_type' 	 => $playType,
				'stake_count' 	 => $stakeCount,
				'total_amount' 	 => $ticket_total_amount,
				'ticket_status'  => TICKET_STATUS_OF_PRINTOUTED,
				'verify_status'  => TICKET_STATUS_OF_PRINTOUTED, //1 完成
				'create_time' 	 => getCurrentTime(),
				'bet_type' 		 => $betType,
				'issue_nos' 	 => $issueNos,
				'ticket_content' => $ticketContent,
				'printout_odds' => $ticketOdds,
				'last_issue_id'  => $lastIssueId,
				'ticket_multiple'  => $ticket_multiple,
		);
	}
	
	public function appendOrderId($ticketDatas, $orderId) {
		$data = array();
		foreach ($ticketDatas as $ticketData) {
			$ticketData['order_id'] = $orderId;
			$data[] = $ticketData;
		}
		return $data;
	}
	
	
	private function _analyOdds($odds_info, &$odds_result){
		$lottery_id = $odds_info['lottery_id'];
		$issue_no 	= $odds_info['issue_no'];
		$issue_no 	= substr($issue_no, 3);
		$odds_list 	= $odds_info['odds'];
		$key 		= $issue_no.'_'.$lottery_id;
	
		foreach ($odds_list as $bet_key=>$odds){
			$last_odds = $odds_result[$key][$bet_key];
			if($last_odds){
				$min = $odds<$last_odds ? $odds : $last_odds;
				$odds_result[$key][$bet_key] = $min;
			}else{
				$odds_result[$key][$bet_key] = $odds;
			}
		}
	}
	
	public function deleteTicketByOrderId($orderId) {
		$condition = array('order_id'=>$orderId, 'ticket_status'=>C('TICKET_STATUS.UN_PRINTOUT'));
		$data = array('ticket_status'=>C('TICKET_STATUS.DELETE'));
		return $this->where($condition)->save($data);
	}

    public function getIndexFormatPrintoutOdds($content){
        $odds_infos = json_decode($content, true);
        if(is_array($odds_infos[0])){
            foreach ($odds_infos as $odds_info){
                $this->_analyOdds($odds_info, $odds_result);
            }
        }else{
            $this->_analyOdds($odds_infos, $odds_result);
        }
        return $odds_result;
    }
	
}