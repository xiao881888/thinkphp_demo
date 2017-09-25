<?php
namespace Crontab\Model;
use Think\Model\ViewModel;
class CollectIssueViewModel extends ViewModel {
    
    protected $viewFields = array(
        'CollectIssue' => array(
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
        	'lottery_status' => 'status',
            '_on' => 'Issue.lottery_id = Lottery.lottery_id',
        ),
     );

	public function queryLatestPrizeInfoByLotteryId($lottery_id){
		$map['lottery_id'] = $lottery_id;
		$order_by = 'issue_no DESC';
		return $this->where($map)->order($order_by)->find();
	}
    
}