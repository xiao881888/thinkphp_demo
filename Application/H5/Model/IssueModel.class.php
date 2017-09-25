<?php
namespace H5\Model;
use Think\Model;
/**
 * @date 2014-11-20
 * @author tww <merry2014@vip.qq.com>
 */
class IssueModel extends Model{
    
	public function getIssueNumById($id){
		$where = array();
		$where['issue_id'] = $id;
		return $this->where($where)->getField('issue');
	}
	
	public function queryIssueInfoByIssueNo($lottery_id, $issue_no){
		$map['issue_no'] = $issue_no;
		$map['lottery_id'] = $lottery_id;
		return $this->where($map)->find();
	}
	
	
	public function getIssueInfo($issueId) {
	    $condition = array('issue_id'=>$issueId);
	    return $this->where($condition)->find();
	}
	
	
	public function getCurrentIssueInfo($lotteryIds){
		$condition = array( 'issue_is_current' => C('ISSUE_IS_CURRENT.YES') );
		$fields = 'lottery_id, issue_id, issue_no, issue_winnings_pool, issue_end_time, issue_slogon, issue_start_time';
		if (is_array($lotteryIds)) {
			$condition['lottery_id'] = array('in', $lotteryIds);
			return $this->where($condition)
						->getField($fields);
		} else {
			$condition['lottery_id'] = $lotteryIds;
			return $this->field($fields)
						->where($condition)
						->find();
		}
	}
	
	
	public function getNextIssueInfos($lotteryIds){
	    $condition = array(
	        'issue_is_current' => C('ISSUE_IS_CURRENT.NEXT'),
	        'issue_start_time' => array('gt', getCurrentTime()),
	        'lottery_id' => array('in', $lotteryIds),
	    );
	
	    return $this->where($condition)
	                ->getField('lottery_id, issue_start_time');
	}
	
	
	public function getCurrentIssueId($lotteryId) {
	    $condition = array('issue_is_current' => C('ISSUE_IS_CURRENT.YES'),
	                       'lottery_id' => $lotteryId,
	    );
	    return $this   ->where($condition)
	                   ->getField('issue_id');
	}
	
	
	public function getNextIssueInfo($lotteryId) {
	    $condition = array('issue_is_current' => C('ISSUE_IS_CURRENT.NEXT'),
	                       'issue_start_time' => array('gt', getCurrentTime()),
	                       'lottery_id' => $lotteryId,
	    );
	    return $this   ->where($condition)
	                   ->find();
	}
	
	public function getEndTime($issue_id){
		$where = array();
		$where['issue_id']= $issue_id;
		return $this->where($where)->getField('issue_end_time');
	}

	public function queryIssueListByLotteryId($lottery_id, $lottery_ahead_endtime, $limit = 6){
        $current_end_time = date('Y-m-d H:i:s',time()+$lottery_ahead_endtime);
        $map['lottery_id'] = $lottery_id;
		$map['issue_end_time'] = array('EGT',$current_end_time);
		return $this->where($map)->limit($limit)->select();
	}

    public function getIssueNoById($id){
        $where = array();
        $where['issue_id'] = $id;
        return $this->where($where)->getField('issue_no');
    }

    public function getLatestPrizeIssue($lottery_id){
        $where['lottery_id'] = $lottery_id;
        $where['issue_is_current'] = 3;
        return $this->field('issue_id,issue_no,issue_prize_number')->where($where)->order('issue_id DESC')->find();

    }

    public function getIssueNoList($lottery_id,$issue_id,$issue_limit){
        $where['lottery_id'] = $lottery_id;
        $where['issue_id'] = array('gt',$issue_id);
        return $this->where($where)->limit($issue_limit)->getField('issue_no',true);
    }
	
}