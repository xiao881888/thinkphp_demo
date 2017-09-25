<?php
namespace Home\Model;
use Think\Model;

class JcScheduleModel extends Model {
    const STATUS_OF_YES = 1;
    const STATUS_OF_NO = 0;
    public function getScheduleList($lottery_id = 0, $play_type = 0) {
    	$lotteryInfo = D('Lottery')->getLotteryInfo($lottery_id);
    	
        $condition = array( 'schedule_status'   => C('SCHEDULE_STATUS.ON_SALE'),
                            'schedule_end_time' => array('gt', date('Y-m-d H:i:s',(time()+intval($lotteryInfo['lottery_ahead_endtime']))))
        );
        $condition['schedule_stop_sell_status'] = self::STATUS_OF_NO;
        if($lottery_id){
        	$condition['lottery_id'] = $lottery_id;
        }
        if($play_type){
        	$play_type = C("MAPPINT_JC_PLAY_TYPE.$play_type");
        	$condition['play_type'] = $play_type;
        }
        return $this->where($condition)
                    ->select();
    }
    
    
    public function getIssueInfo($issueId) {
    	$condition = array('schedule_id'=>$issueId);
    	return $this->where($condition)
					->find();
    }
    
    public function queryScheduleInfoList($schedule_ids){
    	return $this->getScheduleIssueNo($schedule_ids);
    }
    
    public function getScheduleIssueNo(array $scheduleIds){
		$fields = 'schedule_id, schedule_day, schedule_week,schedule_round_no,schedule_issue_no, play_type, schedule_game_start_time, schedule_end_time, lottery_id';
		$condition = array();
		$condition['schedule_id'] = array(
				'in',
				$scheduleIds 
		);
		$condition['schedule_end_time'] = array(
				'gt',
				getCurrentTime() 
		);
		$condition['schedule_status'] = C('SCHEDULE_STATUS.ON_SALE');
		$scheduleInfos = $this->where($condition)->getField($fields);
		return count($scheduleInfos) == count($scheduleIds) ? $scheduleInfos : false;
	}

	public function getScheduleInfoByScheduleNo($schedule_info){
    	$condition['schedule_day'] = $schedule_info['schedule_day'];
    	$condition['schedule_week'] = $schedule_info['schedule_week'];
	    $condition['schedule_round_no'] = $schedule_info['schedule_round_no'];
	    $condition['lottery_id'] = $schedule_info['lottery_id'];
	    $condition['play_type'] = $schedule_info['play_type'];
	     
	    $condition['schedule_end_time'] = array('gt', getCurrentTime());
	    $condition['schedule_status']   = C('SCHEDULE_STATUS.ON_SALE');
	    
		$scheduleInfo = $this->where($condition)->find();
		return $scheduleInfo;
	}
    
    public function getSchedulesByDate($lotteryId, $beginDate, $endDate) {
    	$condition  = array();
    	$condition['lottery_id'] = $lotteryId;
    	$condition['schedule_status'] = C('SCHEDULE_STATUS.PRIZE');
    	$condition['play_type'] = C('MAPPINT_JC_PLAY_TYPE.2');
    	$condition['schedule_prize_time'] = array('between', array($beginDate, $endDate));
    	$order = 'schedule_id desc';
    	return $this->field(array(
    					'schedule_id',
    					'lottery_id',
    					'schedule_home_team',
    					'schedule_guest_team',
    					'schedule_league_matches',
    					'schedule_start_time',
    					'schedule_end_time',
    					'schedule_prize_time',
    					'schedule_round_no',
    					'schedule_odds',
    					'schedule_half_score',
    					'schedule_final_score',
    				))
    				->where($condition)->order($order)
    				->select();
    }
    
    public function getEndTime($schedule_id){
    	$where = array();
    	$where['schedule_id'] = $schedule_id;
    	return $this->where($where)->getField('schedule_end_time');
    }
    

    public function queryAllScheduleIdsFromScheduleNo($day,$week,$round_no) {
    	$query_fields = 'schedule_id,schedule_issue_no,schedule_day,schedule_week,schedule_round_no,schedule_game_start_time,schedule_end_time,lottery_id, play_type';
    	$condition['schedule_day']       = $day;
    	$condition['schedule_week']       = $week;
    	$condition['schedule_round_no']       = $round_no;
    	$schedule_list = $this->field($query_fields)->where($condition)->select();
    	foreach($schedule_list as $schedule_info){
    		$lottery_id = $schedule_info['lottery_id'];
    		$play_type = $schedule_info['play_type'];
    		$schedule_info['schedule_end_time_unix_timestamp'] = strtotime($schedule_info['schedule_end_time']);
    		$schedule_ids[$lottery_id][$play_type] = $schedule_info;
    	}
    	return $schedule_ids;
    } 
    
    public function queryEndTimeByScheduleNo($schedule_issue_no){
    	$where['schedule_issue_no'] = $schedule_issue_no;
    	return $this->where($where)->getField('schedule_end_time');
    }

	public function queryScheduleOddsListByDate($date_list, $lottery_id){
    	$map['schedule_day'] = array('IN' , $date_list);
    	$map['lottery_id'] = $lottery_id;
    	$map['play_type'] = JC_PLAY_TYPE_MULTI_STAGE;
    	$fields = 'schedule_day,schedule_round_no,schedule_odds';
    	$schedule_list = $this->field($fields)->where($map)->select();
    	$schedule_odd_list = array();
    	foreach($schedule_list as $schedule_info){
    		$key = $schedule_info['schedule_day'].$schedule_info['schedule_round_no'];
    		$schedule_odd_list[$key] = $schedule_info['schedule_odds'];
    	}
    	return $schedule_odd_list;
	}

	public function queryScheduleIdsFromDateAndNo($day, $round_no){
		$schedule_ids = array();
		$query_fields = 'schedule_id';
		$map['schedule_day']  = $day;
		$map['schedule_round_no']  = $round_no;
		$schedule_list = $this->field($query_fields)->where($map)->select();
		foreach($schedule_list as $schedule_info){
			$schedule_ids[] = $schedule_info['schedule_id'];
		}
		return $schedule_ids;
	}
    
}