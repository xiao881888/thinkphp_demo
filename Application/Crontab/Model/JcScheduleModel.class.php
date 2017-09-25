<?php

namespace Crontab\Model;

use Think\Model;

class JcScheduleModel extends Model{

	public function queryScheduleNoList(){
		$field = 'lottery_id,schedule_day,schedule_round_no';
		$condition = array(
				'schedule_status' => C('SCHEDULE_STATUS.ON_SALE'),
				'schedule_end_time' => array(
						'gt',
						date('Y-m-d H:i:s') 
				) 
		);
		return $this->distinct(true)->where($condition)->field($field)->select();
	}
	
	public function queryScheduleDateList(){
		$field = 'schedule_day';
		$condition = array(
				'schedule_status' => C('SCHEDULE_STATUS.ON_SALE'),
				'schedule_end_time' => array(
						'gt',
						date('Y-m-d H:i:s')
				)
		);
		return $this->distinct(true)->where($condition)->getField($field,true);
	}

	public function getScheduleList($schedule_day,$lottery_ids = ''){
		$condition = array(
			'schedule_day' => $schedule_day
		);
		if($lottery_ids){
			$condition['lottery_id'] = array('in',$lottery_ids);
		}
		$field = 'schedule_id,schedule_day,schedule_round_no,schedule_end_time';

		return $this->where($condition)->field($field)->select();
	}
}