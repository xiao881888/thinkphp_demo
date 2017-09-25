<?php
namespace Home\Model;
use Think\Model;

class VsDataModel extends Model {
    //注：胜负彩data传入issue_no
	public function queryVsDataListByDate($schedule_date) {
		$map['schedule_date'] = $schedule_date;
		$list = $this->where($map)->getField('schedule_round_no,schedule_home_rank,schedule_guest_rank,vs_history_data,vs_latest_data,vs_average_rate,vs_detail_url,third_party_schedule_id');
		return $list;
	} 
}
