<?php
namespace Home\Controller;
use Home\Controller\GlobalController;

class JczqController extends GlobalController {
    
    public function getJczqList($api) {
    	$lottery_id = $api->lottery_id;
    	$play_type  = $api->play_type;
    	
    	$lottery_info = D('Lottery')->getLotteryInfo($lottery_id);

        $schedules = D('JcSchedule')->getScheduleList($lottery_id, $play_type);
        $data['groups'] = array();
        $i = 1;
        $vs_data_list_by_date = array();
        foreach ($schedules as $schedule) {
        	$schedule_date = $schedule['schedule_day'];
        	if(empty($vs_data_list_by_date[$schedule_date])){
        		$vs_data_list_by_date[$schedule_date] = D('VsData')->queryVsDataListByDate($schedule_date);
        	}
        	$round_no = $schedule['schedule_round_no'];
        	$vs_data_info = $average_info = array();
        	if($vs_data_list_by_date[$schedule_date]){
        		$vs_data_info = $vs_data_list_by_date[$schedule_date][$round_no];
        		$average_info = json_decode($vs_data_info['vs_average_rate'],true);
        	}
        	$i ++;
//         	$group_time = substr($schedule['schedule_game_start_time'], 0, 10);
			$bet_end_time = empty($schedule['schedule_end_time']) ? 0 : strtotime($schedule['schedule_end_time'])-intval($lottery_info['lottery_ahead_endtime']);
        	$group_time = date("Y-m-d",strtotime($schedule['schedule_day']));
        	$betDate = ( strtotime($schedule['schedule_start_time']) >0 ? strtotime($schedule['schedule_start_time']) : 0 );
        		
        	$match_id = substr($schedule['schedule_day'],3).$schedule['schedule_round_no'];
        	
        	$data['groups'][$group_time]['id'] 			= $i;
        	$data['groups'][$group_time]['name'] 		= $group_time;
            $data['groups'][$group_time]['schedules'][] = array(
                'id' 					=> $schedule['schedule_id'],
            	'round_no' 				=> $schedule['schedule_round_no'],
                'home' 					=> $schedule['schedule_home_team'],
                'guest' 				=> $schedule['schedule_guest_team'],
                'league' 				=> $schedule['schedule_league_matches'],
                'betting_date' 			=> $betDate,
                'match_id' 				=> $match_id,
            	'end_time' 				=> $bet_end_time,
            	'betting_score_odds' 	=> getFormatOdds($lottery_id, $schedule['schedule_odds']),
            	//下面字段暂时无数据	
            	'third_party_schedule_id' => empty($vs_data_info['third_party_schedule_id'])?0:$vs_data_info['third_party_schedule_id'],
            	'history_fight' 		=> empty($vs_data_info['vs_history_data'])?$this->_buildEmptyHistory():json_decode($vs_data_info['vs_history_data'],true),
            	'latest_record' 		=> empty($vs_data_info['vs_latest_data'])?$this->_buildEmptyRecord():json_decode($vs_data_info['vs_latest_data'],true),
            	'home_rank' 			=> ($api->sdk_version>=8)?(empty($vs_data_info['schedule_home_rank'])? 0:$vs_data_info['schedule_home_rank']):(empty($vs_data_info['schedule_home_rank'])? 0:intval($vs_data_info['schedule_home_rank'])),
            	'guest_rank' 			=> ($api->sdk_version>=8)?(empty($vs_data_info['schedule_guest_rank'])? 0:$vs_data_info['schedule_guest_rank']):(empty($vs_data_info['schedule_guest_rank'])? 0:intval($vs_data_info['schedule_guest_rank'])),
            	'betting_win_percent'	=> '',
            	'betting_equal_percent'	=> '',
            	'betting_lose_percent'	=> '',
            	// 'average_equal_odds'	=> empty($average_info['v1'])?0:doubleval($average_info['v1']),
            	// 'average_win_odds'	 	=> empty($average_info['v3'])?0:doubleval($average_info['v3']),
            	// 'average_lose_odds'		=> empty($average_info['v0'])?0:doubleval($average_info['v0']),
                'average_equal_odds'    => empty($average_info['v1'])?0:(string)$average_info['v1'],
                'average_win_odds'      => empty($average_info['v3'])?0:(string)$average_info['v3'],
                'average_lose_odds'     => empty($average_info['v0'])?0:(string)$average_info['v0'],
            	'detail_url'		=> empty($vs_data_info['vs_detail_url'])?'':$this->_buildDetailUrl($vs_data_info['vs_detail_url']),
            );
        }
        $data['groups'] = array_values($data['groups']);
        $data['lottery_id'] = $api->lottery_id;
        return array(   'result' => $data,
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }
    private function _buildEmptyHistory(){
    	return array('win'=>0,'equal'=>0,'lose'=>0,'games_count'=>0);
    }
    
    private function _buildEmptyRecord(){
    	return array('home'=>$this->_buildEmptyHistory(),'guest'=>$this->_buildEmptyHistory());
    }
    
    private function _buildDetailUrl($url){
    	return $url.'/client/1';
    }
}