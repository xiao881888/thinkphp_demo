<?php
namespace Home\Controller;
use Home\Controller\GlobalController;

class SFCIssueController extends GlobalController {
    
    public function fetchIssueList($params) {
    	$lottery_id = $params->lottery_id;
    	$lottery_info = D('Lottery')->getLotteryInfo($lottery_id);
		$sfc_issue_list = D('Issue')->queryIssueListByLotteryId($lottery_id, $lottery_info['lottery_ahead_endtime']);
		$response['issues'] = array();

		$vs_data_list_by_issue_no = array();
		foreach($sfc_issue_list as $sfc_issue_info){
			$end_time = strtotime($sfc_issue_info['issue_end_time']) - $lottery_info['lottery_ahead_endtime'];
			$issue_no = $sfc_issue_info['issue_no'];
			if(empty($vs_data_list_by_issue_no[$issue_no])){
				$vs_data_list_by_issue_no[$issue_no] = D('VsData')->queryVsDataListByDate($issue_no);
			}
			$response_sfc_issue_item['no'] = $sfc_issue_info['issue_no'];
			$response_sfc_issue_item['start_time'] = strtotime($sfc_issue_info['issue_start_time']);
			$response_sfc_issue_item['end_time'] = $end_time;
			$response_sfc_issue_item['sfc_issue_status'] = $this->_getSfcIssueStatus($sfc_issue_info,$lottery_info['lottery_ahead_endtime']);
			$response_sfc_issue_item['schedules'] = $this->_queryScheduleListByIssueNo($sfc_issue_info['issue_no'], $vs_data_list_by_issue_no[$issue_no]);
			$response['issues'][] = $response_sfc_issue_item;
		}
        return array(   'result' => $response,
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }

	private function _getSfcIssueStatus($sfc_issue_info, $ahead_time){
		$end_time = strtotime($sfc_issue_info['issue_end_time']) - $ahead_time;
		if($end_time < time()){
			$issue_status = 2; //已截止			
		}elseif(time()<=$end_time && time()>=strtotime($sfc_issue_info['issue_start_time'])){
			$issue_status = 1;
		}else{
			$issue_status = 0;
		}
		return $issue_status;
    }
    
    private function _queryScheduleListByIssueNo($issue_no,$vs_data_list){
    	$formated_schedule_list = array();
    	$schedule_list = D('ZcsfcSchedule')->queryScheduleListByIssueNo($issue_no);
    	ApiLog('$schedule_list:'.print_r($schedule_list,true), 'sfc');
    	 
    	foreach($schedule_list as $schedule_info){
    		$formated_schedule_list[] = $this->_formatScheduleInfo($schedule_info,$vs_data_list[$schedule_info['sfc_schedule_seq']]);
    	}
    	return $formated_schedule_list;
    }
    
    private function _formatScheduleInfo($schedule_info,$vs_data_info){
    	$formated_schedule_info['round_id'] = $schedule_info['sfc_schedule_seq'];
    	$formated_schedule_info['third_party_schedule_id'] =  intval($vs_data_info['third_party_schedule_id']);
    	$formated_schedule_info['home'] = $schedule_info['sfc_schedule_home_team'];
    	$formated_schedule_info['home_rank'] = emptyToStr($vs_data_info['schedule_home_rank']);
    	$formated_schedule_info['guest'] =$schedule_info['sfc_schedule_guest_team'];
    	$formated_schedule_info['guest_rank'] = emptyToStr($vs_data_info['schedule_guest_rank']);
    	$formated_schedule_info['league'] = $schedule_info['sfc_schedule_league'];
    	$formated_schedule_info['start_time'] = strtotime($schedule_info['sfc_schedule_game_start_time']);
//     	$formated_schedule_info['score'] =  emptyToStr($schedule_info['sfc_schedule_prize_result']);
    	$formated_schedule_info['history_fight'] = empty($vs_data_info['vs_history_data'])?$this->_buildEmptyHistory():json_decode($vs_data_info['vs_history_data'],true);
    	$formated_schedule_info['latest_record'] = empty($vs_data_info['vs_latest_data'])?$this->_buildEmptyRecord():json_decode($vs_data_info['vs_latest_data'],true);
    	$formated_schedule_info['betting_win_percent'] = '';
    	$formated_schedule_info['betting_equal_percent'] = '';
    	$formated_schedule_info['betting_lose_percent'] = '';
    	$average_info = json_decode($vs_data_info['vs_average_rate'],true);
    	$formated_schedule_info['average_win_odds'] = empty($average_info['v3'])?0:(string)$average_info['v3'];
    	$formated_schedule_info['average_equal_odds'] = empty($average_info['v1'])?0:(string)$average_info['v1'];
    	$formated_schedule_info['average_lose_odds'] = empty($average_info['v0'])?0:(string)$average_info['v0'];
    	return $formated_schedule_info;
    }
    
    private function _buildEmptyHistory(){
    	return array('win'=>0,'equal'=>0,'lose'=>0,'games_count'=>0);
    }
    
    private function _buildEmptyRecord(){
    	return array('home'=>$this->_buildEmptyHistory(),'guest'=>$this->_buildEmptyHistory());
    }
}