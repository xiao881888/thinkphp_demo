<?php
namespace Home\Model;
use Think\Model\ViewModel;
/**
 * @date 2015-1-12
 * @author tww <merry2014@vip.qq.com>
 */
class CobetJcOrderDetailViewModel extends ViewModel{
	public $viewFields = array(
		'CobetJcOrderDetail' => array(
					'bet_content',
					'is_sure' 
			),
		'JcSchedule'	=> array(
									 'schedule_id',
									 'schedule_week',
									 'schedule_day',
									 'schedule_round_no',
									 'schedule_odds',
									 'schedule_issue_no',
									 'play_type', 
									 'schedule_home_team' 		=> 'home', 
									 'schedule_guest_team' 		=> 'guest',
									 'schedule_league_matches' 	=> 'league',
									 'schedule_start_time' 		=> 'zq_start_date',
									 'schedule_round_no' 		=> 'round_no',
									 'lottery_id' 				=> 'lottery_id',
									 'schedule_status',
									 'schedule_final_score',
									 'schedule_half_score',
									 '_on' 						=> 'CobetJcOrderDetail.schedule_id = JcSchedule.schedule_id')
	);
	
	
	public function getInfos($order_id){
		$where = array();
		$where['order_id'] = $order_id;
		$result = $this->where($where)->select();
	
		foreach ($result as $k=>$v){
			$result[$k]['score']['half'] 	= $v['schedule_half_score'];
			$result[$k]['score']['final'] 	= $v['schedule_final_score'];
			if($v['play_type'] == C('MAPPINT_JC_PLAY_TYPE.1')){
				$result[$k]['play_type'] = 1;
			}else if($v['play_type'] == C('MAPPINT_JC_PLAY_TYPE.2')){
				$result[$k]['play_type'] = 2;
			}
		}
		return $result;
	}
	
	public function getScheduleInfoByIssueNo($order_id){
		$map['order_id'] = $order_id;
		$schedule_issue_list = $this->where($map)->select();
		$return_schedule_list = array();
		foreach ($schedule_issue_list as $schedule_issue_info){
			$issue_no = $schedule_issue_info['schedule_day'].'_'.$schedule_issue_info['schedule_round_no'];
			$schedule_map_list[] = $issue_no;
		}
		
		foreach($schedule_map_list as $schedule_issue_no){
			$schedule_map_info = explode('_', $schedule_issue_no);
			$schedule_map['schedule_day'] = $schedule_map_info[0];
			$schedule_map['schedule_round_no'] = $schedule_map_info[1];
			$schedule_list = D('JcSchedule')->where($schedule_map)->select();
			foreach($schedule_list as $schedule_info){
				$schedule_info['score']['half'] 	= empty($schedule_info['schedule_half_score'])?'':$schedule_info['schedule_half_score'];
				$schedule_info['score']['final'] 	= empty($schedule_info['schedule_final_score'])?'':$schedule_info['schedule_final_score'];
				$return_schedule_list[$schedule_info['schedule_issue_no']] 	= $schedule_info;
			}
		}			
		return $return_schedule_list;
	}
}