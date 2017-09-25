<?php
namespace Admin\Model;
use Think\Model;
use Admin\Controller\PublicController;
/**
 * @date 2014-12-11
 * @author tww <merry2014@vip.qq.com>
 */
class IssueModel extends Model{
	public function getWaitPrize($lottery_id){
		$where = array();
		$where['lottery_id'] = array('IN', $lottery_id);
		$where['issue_prize_status'] = PRIZE_STATUS_WAITPRIZE;
		$where['issue_prize_number'] = array('NEQ', '');
		
		return $this->where($where)->select();
	}
	
	public function getIssueInfo($issue_id){
		$where = array();
		$where['issue_id'] = $issue_id;
		return $this->where($where)->find();
	}
	
	public function getLotteryId($issue_id){
		$where = array();
		$where['issue_id'] = $issue_id;
		return $this->where($where)->getField('lottery_id');
	}
	
	public function getPrizeStatus($issue_id){
		$where = array();
		$where['issue_id'] = $issue_id;
		return $this->where($where)->getField('issue_prize_status');
	}
	
	public function getWaitDistribute($lottery_id){
		$where = array();
		$where['lottery_id'] = $lottery_id;
		$where['issue_prize_status'] = PRIZE_STATUS_WAITDISTRIBUTION;			
		return $this->where($where)->select();
	}
	
	public function getIssueInfos($issue_ids){
		$where = array();
		$where['issue_id'] = array('IN', $issue_ids);
		return $this->where($where)->select();
	}
	
	public function getPrizeNum($issue_id){
		$where = array();
		$where['issue_id'] = $issue_id;
		return $this->where($where)->getField('issue_prize_number');
	}
	
	public function getIssueNo($issue_id){
		$where = array();
		$where['issue_id'] = $issue_id;
		return $this->where($where)->getField('issue_no');
	}
	
	public function getOrderFields(){
	    return 'issue_id asc';
	}
}