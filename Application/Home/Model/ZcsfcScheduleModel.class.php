<?php
namespace Home\Model;
use Think\Model;

class ZcsfcScheduleModel extends Model {

	public function queryScheduleListByIssueNo($issue_no){
		if (empty($issue_no)) {
			return false;
		}
		$map['sfc_schedule_issue_no'] = $issue_no;
		$order_by = 'sfc_schedule_seq ASC';
		return $this->where($map)->order($order_by)->select();
	}

    public function queryScheduleListByIssueNoAndSeq($issue_no,$schedule_seq_list){
        $map['sfc_schedule_issue_no'] = $issue_no;
        $map['sfc_schedule_seq'] = array('in',$schedule_seq_list);
        return $this->where($map)->select();
    }

    public function getScheduleListOfScore($issue_no){
        $schedule_score_list = array();
        $map['sfc_schedule_issue_no'] = $issue_no;
        $schedule_list = $this->where($map)->select();
        foreach($schedule_list as $schedule_info){
            $schedule_score_list[$schedule_info['sfc_schedule_seq']] = $schedule_info;
        }
        return $schedule_score_list;
    }
    
}