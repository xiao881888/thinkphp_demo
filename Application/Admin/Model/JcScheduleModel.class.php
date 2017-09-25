<?php
namespace Admin\Model;
use Think\Model;
use Admin\Controller\PublicController;
/**
 * @date
 * @author
 */
class JcScheduleModel extends Model{
	
	public function getScheduleInfo($schedule_id){
		$where = array();
		$where['schedule_id'] = $schedule_id;
		return $this->where($where)->find();
	}
	
	public function getScheduleNo($schedule_id){
		$where = array();
		$where['schedule_id'] = $schedule_id;
		return $this->where($where)->getField('schedule_issue_no');
	}
	
	public function getScheduleInfoByNo($lottery_id, $schedule_issue_no){
		$where = array();
		$where['lottery_id'] = $lottery_id;
		$where['schedule_issue_no'] = $schedule_issue_no;
		return $this->where($where)->find();
	}
	
	public function getScheduleIdsByDayRoundNo($lottery_id, $schedule_day, $schedule_round_no){
	    $where = array();
	    $where['lottery_id'] = is_array($lottery_id) ? array('IN', $lottery_id) : $lottery_id;
	    $where['schedule_day'] = $schedule_day;
	    $where['schedule_round_no'] = is_array($schedule_round_no) ? array('IN', $schedule_round_no) : $schedule_round_no;
	    return $this->where($where)->getField('schedule_id', true);
	}
	
	public function getOrderFields(){
	    return 'schedule_game_start_time asc, schedule_round_no asc';
	}
}