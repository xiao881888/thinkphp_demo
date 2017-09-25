<?php
namespace Home\Model;
use Think\Model\ViewModel;

class JcScheduleViewModel extends ViewModel {
	
	protected $viewFields = array(
			'JcSchedule' => array(
					'schedule_id'=>'issue_id',
					'schedule_issue_no'=>'issue_no',
					'lottery_id',
					'UNIX_TIMESTAMP(schedule_prize_time)' => 'prize_time',
					'UNIX_TIMESTAMP(schedule_start_time)' => 'start_time',
					'UNIX_TIMESTAMP(schedule_end_time) - lottery_ahead_endtime'   => 'end_time',
					'schedule_final_score' 	=> 'prize_num',
					'schedule_round_no' 	=> 'round_no',
					'schedule_home_team'	=> 'home',
					'schedule_guest_team' 	=> 'guest',
					'schedule_league_matches' => 'league',
					'schedule_odds',
			),
			'Lottery' => array(
					'lottery_name',
					'lottery_slogon' => 'slogon',
					'lottery_image',
					'lottery_ahead_endtime',
					'_on' => 'JcSchedule.lottery_id = Lottery.lottery_id',
			),
	);
	
	
	public function getPrizeIssueInfo($ids=0, $offset=0, $limit=10) {
		$condition = array( 'schedule_status'=>C('SCHEDULE_STATUS.PRIZE') );
		if ($ids && is_numeric($ids)) {
			$condition['lottery_id'] = array('IN', $this->_getJcLotteryIds($ids));
		} elseif (is_array($ids)) {
			$condition['schedule_id'] = array('IN', $ids);
		}
		$offset = ( $offset ? $offset : 0 );
		$limit  = ( $limit  ? $limit  : 10 );
		$condition['lottery_is_show'] = C('LOTTERY_DISPLAY.YES');
		return $this->where($condition)
					->order('schedule_prize_time DESC')
					->limit($offset, $limit)
					->select();
	}
	
	

	public function getLatestSchedule($scheduleDay, $shceduleWeek, $scheduleRoundNo, $lotteryId) {
	    $condition = array(
	        'schedule_day' => $scheduleDay,
	        'schedule_week' => $shceduleWeek,
	        'schedule_round_no' => $scheduleRoundNo,
	        'schedule_final_score' => array('NEQ', ''),
	        'lottery_status' => C('LOTTERY_STATUS.ONSALE'),
// 	        'lottery_is_show' => C('LOTTERY_DISPLAY.YES'),
	    );
	    $condition['lottery_id'] = $this->_getLotteryPoint($lotteryId);
	    $data = $this  ->where($condition)
            	       ->find();
	    if ($data) {
	        return $data;
	    } else {
	        $condition['lottery_id'] = array('in', $this->_getJcLotteryIds($lotteryId) );
	        return $this   ->where($condition)
            	           ->find();
	    }
	}
	
	
	private function _getLotteryPoint($lotteryId) {
	    if ($lotteryId==C('JC.JCZQ')) {
	        return C('JCZQ.CONCEDE');
	    } elseif ($lotteryId==C('JC.JCLQ')) {
// 	        return C('JCLQ.DXF');
	        return C('JCLQ.MIX');
	    } else {
	        return $lotteryId;
	    }
	}
	

	private function _getJcLotteryIds($lotteryId) {
	    if ($lotteryId==C('JC.JCZQ')) {
	        return C('JCZQ');
	    } elseif ($lotteryId==C('JC.JCLQ')) {
	        return C('JCLQ');
	    } else {
	        return $lotteryId;
	    }
	}
	
}

?>