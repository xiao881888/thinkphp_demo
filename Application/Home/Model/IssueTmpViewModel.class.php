<?php
namespace Home\Model;
use Think\Model\ViewModel;
/**
 * @date 2015-5-9
 * @author tww <merry2014@vip.qq.com>
 */
class IssueTmpViewModel extends ViewModel{
	protected $viewFields = array(
			'IssueTmp' => array(
					'issue_id',
					'issue_no',
					'lottery_id',
					'issue_slogon' => 'slogon',
					'issue_winnings_pool' => 'winnings_pool',
					'UNIX_TIMESTAMP(issue_prize_time)' => 'prize_time',
					'UNIX_TIMESTAMP(issue_start_time)' => 'start_time',
					'UNIX_TIMESTAMP(issue_end_time) - lottery_ahead_endtime'   => 'end_time',
					'issue_prize_number' => 'prize_num',
					'issue_test_number'	 => 'test_num',
			),
			'Lottery' => array(
					'lottery_name',
					'lottery_image',
					'lottery_ahead_endtime',
					'_on' => 'IssueTmp.lottery_id = Lottery.lottery_id',
			),
	);
	
	
	public function getIssueInfo($issue_id) {
		if(empty($issue_id)){
			return false;
		}
		$condition = array('issue_id'=>$issue_id);
		return $this->where($condition)
		->find();
	}
	
	
	public function getPrizeIssueInfo($ids=0, $offset=0, $limit=10) {
		if(is_array($ids)) {
			$condition = array( 'issue_id' => array('in', $ids));
		} else {
			$condition = array( 'lottery_id' => $ids,
					'issue_prize_status' => C('ISSUE_PRIZE_STATUS.FINISH') );
		}
		$offset = ( $offset ? $offset : 0 );
		$limit  = ( $limit  ? $limit  : 10 );
		return $this->where($condition)
		->order('lottery_id ASC, issue_prize_time DESC')
		->limit($offset, $limit)
		->select();
	}
}