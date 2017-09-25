<?php 
namespace Home\Model;
use Think\Model;

class FollowBetModel extends Model {
    
	// 追号状态，只有 1、正常   3、取消   4、结束
	
    public function addFollowBet($lotteryId, $issueId, $followTimes, $orderId) {
        $data = array(
            'lottery_id' => $lotteryId,
            'follow_start_issue' => $issueId,
            'follow_times' => $followTimes,
            'follow_remain_times' => $followTimes,
            'order_id' => $orderId,
        	'follow_status' => C('FOLLOW_STATUS.UNPAID'),
        );
        
        return $this->add($data);
    }
    
    public function queryFollowTaskIds($lotteryId){
    	$condition = array(
    		'follow_status' => C('FOLLOW_STATUS.NORMAL'),
    		'lottery_id' 	=> $lotteryId,
    		'follow_remain_times' => array('GT', 0),
    	);
    	return $this->where($condition)
    				->getField('follow_bet_id', true);
    }
    
    public function deleteFollowBet($followBetId) {
        $condition = array('follow_bet_id'=>$followBetId);
        $data = array('follow_status' => C('ORDER_STATUS.DELETE'));
        return $this->where($condition)
                    ->save($data);
    }
    
    
    public function saveFollowRemainTimes($followId, $remainTimes, $status) {
    	$condition = array('follow_bet_id'=>$followId);
    	$data = array(
    		'follow_remain_times' => $remainTimes,
    		'follow_status' => $status,
    	);
    	return $this->where($condition)
    				->save($data);
    }
    
    
    public function getFollowBetInfo($followBetId) {
        return $this->queryFollowInfoById($followBetId);
    }

	public function queryFollowInfoById($follow_bet_id){
		$condition['follow_bet_id'] = $follow_bet_id; 
		return $this->where($condition)->find();
	}
    
    public function saveFollowBetStatus($followId, $status) {
        $condition = array('follow_bet_id'=>$followId);
        $data = array('follow_status'=>$status);
        return $this->where($condition)
                    ->save($data);
    }
    
}

?>