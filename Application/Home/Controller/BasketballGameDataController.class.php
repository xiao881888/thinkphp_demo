<?php

namespace Home\Controller;

use Home\Controller\GlobalController;

class BasketballGameDataController extends GlobalController{
	private $_schedule_odd_list = array();
	private $_basket_interface_baseurl = '';
	private $_service_url_map = array();
	
	public function __construct(){
		$this->_basket_interface_baseurl = C('BASEDATA_INTERFACE_URL');
		$this->_service_url_map = C('BASEDATA_SERVICE_URL_MAP');
		parent::__construct();
	}

	private function _requestDataFromBaseDataService($type, $params){
		$interface_url = $this->_basket_interface_baseurl . $this->_service_url_map[$type];
		
		$response_content = getByCurl($interface_url, $params);
		$response = json_decode($response_content, true);
		return $response['data'];
	}
	
	private function _queryScheduleOddsList($date_list){
		return D('JcSchedule')->queryScheduleOddsListByDate($date_list, TIGER_LOTTERY_ID_OF_JL_HHTZ);
	}
	
	public function getScheduleList($api){
		$request_code = 'SCHEDULE_LIST';
		$lottery_id = $api->lottery_id;
		$type = $api->type ? $api->type : C('API_SCHEDULE_STATUS_OF_PLAYING');
		
		$schedule_list = array();
		$request_params['status'] = $this->_convertListTypeToScheduleStatus($type);
		
		$yesterday = date("Ymd", strtotime('-1 day'));
		$today = date("Ymd");
		$tomorrow = date("Ymd", strtotime('+1 day'));
		
		$this->_schedule_odd_list = $this->_queryScheduleOddsList(array($yesterday,$today,$tomorrow));
		
		$request_date_list_map = array(
			C('API_SCHEDULE_STATUS_OF_OVER') => array($yesterday,$today,$tomorrow),
			C('API_SCHEDULE_STATUS_OF_NOBEGIN') => array($yesterday,$today,$tomorrow),
			C('API_SCHEDULE_STATUS_OF_PLAYING') => array($yesterday,$today,$tomorrow),
		);
		
		$response_schedule_list = array();
		$request_date_list = $request_date_list_map[$type];
		
		foreach ($request_date_list as $date){
			$request_params['schedule_date'] = $date;
			$schedule_list = $this->_requestDataFromBaseDataService($request_code, $request_params);
			if(empty($schedule_list)){
				continue;
			}
			$response_schedule_list[] = $this->_formatScheduleList($schedule_list, $date, $type);
		}
		$response['groups'] = $response_schedule_list;
		
		return array(
				'result' => $response,
				'code' => C('ERROR_CODE.SUCCESS') 
		);
	}

	private function _convertListTypeToScheduleStatus($type){
		$status_type_map = C('BASKETBALL_SCHEDULE_STATUS_TYPE_MAP');
		return implode('_', $status_type_map[$type]);
	}
	
	private function _convertScheduleStatusToListType($status){
		foreach(C('BASKETBALL_SCHEDULE_STATUS_TYPE_MAP') as $type=>$status_list){
			if(in_array($status,$status_list)){
				return $type;
			}
		}
	}

	private function _formatScheduleList($schedule_list, $date, $type = ''){
		if(empty($schedule_list)){
			return false;
		}
		$name = date('Y-m-d',strtotime($date)) . ' ' . count($schedule_list) . '场';
		$formated_schedule_list['name'] = $name; 
		$formated_schedule_list['date'] = $date;
		$formated_schedule_list['date_timestamp'] = strtotime($date);
		
		$schedule_info_list = array();
		foreach ($schedule_list as $schedule_info) {
			$formated_schedule_info = $this->_formatScheduleInfo($schedule_info);
			$schedule_info_list[] = $formated_schedule_info;
			$schedule_id_list[] = $formated_schedule_info['id'];
		}
		if ($type == C('API_SCHEDULE_STATUS_OF_OVER')) {
			$schedule_info_list = $this->_reSortScheduleListByRoundNo($schedule_info_list, $schedule_id_list);
		}
		$formated_schedule_list['schedules'] = $schedule_info_list;
		return $formated_schedule_list;
	}

	private function _reSortScheduleListByRoundNo($schedule_info_list, $schedule_id_list){
		array_multisort($schedule_id_list, SORT_DESC, $schedule_info_list);
		return $schedule_info_list;
	}

	private function _formatScheduleInfo($schedule_info){
		$formated_schedule_info['id'] = $schedule_info['schedule_date'] . $schedule_info['schedule_no'];
		$formated_schedule_info['home'] = emptyToStr($schedule_info['home_team']);
		if($schedule_info['has_data'] == 1){
            $formated_schedule_info['third_party_schedule_id'] = (int)$schedule_info['schedule_qt_id'];
        }else{
            $formated_schedule_info['third_party_schedule_id'] = '';
        }
		$formated_schedule_info['guest'] = emptyToStr($schedule_info['guest_team']);
		$formated_schedule_info['league'] = emptyToStr($schedule_info['league_short']);
		$formated_schedule_info['begin_date'] = strtotime($schedule_info['schedule_date']) ? strtotime($schedule_info['schedule_date']) : 0;
		$formated_schedule_info['first_half_begin_time'] = strtotime($schedule_info['schedule_match_time']);
		$formated_schedule_info['round_no'] = emptyToStr($schedule_info['schedule_no']);
		$formated_schedule_info['current_score'] = emptyToStr($this->_buildScoreStringForCurrentScore($schedule_info));
		$formated_schedule_info['home_info']['score'] = $this->_buildScoreListForHomeScore($schedule_info);
		$formated_schedule_info['guest_info']['score'] = $this->_buildScoreListForGuestScore($schedule_info);
		$formated_schedule_info['match_status'] = $this->_convertScheduleStatusToListType($schedule_info['schedule_match_status']);
		$formated_schedule_info['basketball_game_time_rule'] = intval($schedule_info['basketball_game_time_rule']);
		$formated_schedule_info['basketball_status'] = (int)$schedule_info['schedule_match_status'];
		$formated_schedule_info['match_status_description'] = emptyToStr($this->_buildScheduleStatusDesc($schedule_info['schedule_match_status'],$schedule_info['basketball_game_time_rule']));
		$formated_schedule_info['match_duration'] = emptyToStr($schedule_info['schedule_remain_time']);
		$key = $schedule_info['schedule_date'] . $schedule_info['schedule_no'];
		$formated_schedule_info['result_odds'] = json_decode($this->_schedule_odd_list[$key],true);
		return $formated_schedule_info;
	}

	private function _buildScoreStringForCurrentScore($schedule_info){
		$match_status = $schedule_info['schedule_match_status'];
		$score_string = '';
		if($match_status==C('BASKETBALL_SCHEDULE_STATUS.NOBEGIN')){
			$score_string = '';
		}else{
			$score_string = $schedule_info['schedule_home_score'].':'.$schedule_info['schedule_guest_score'];
		}
		return $score_string;
	}
	
	private function _buildScoreListForHomeScore($schedule_info){
		$home_score_field_map = array(
				1 => 'schedule_home_score_q1',
				2 => 'schedule_home_score_q2',
				3 => 'schedule_home_score_q3',
				4 => 'schedule_home_score_q4',
				5 => 'schedule_home_ot_score' 
		);
		return $this->_buildScoreList($schedule_info, $home_score_field_map);
	}

	private function _buildScoreListForGuestScore($schedule_info){
		$guest_score_field_map = array(
				1 => 'schedule_guest_score_q1',
				2 => 'schedule_guest_score_q2',
				3 => 'schedule_guest_score_q3',
				4 => 'schedule_guest_score_q4',
				5 => 'schedule_guest_ot_score' 
		);
		return $this->_buildScoreList($schedule_info, $guest_score_field_map);
	}
	
	private function _buildScoreList($schedule_info, $score_field_map){
		$status_quarter_map = array(
				C('BASKETBALL_SCHEDULE_STATUS.HALFTIME')=>array(1,2),
				C('BASKETBALL_SCHEDULE_STATUS.OVER')=>array(1,2,3,4,5),
				C('BASKETBALL_SCHEDULE_STATUS.NOSURE')=>array(1,2,3,4),
				C('BASKETBALL_SCHEDULE_STATUS.INTERRUPT')=>array(1,2,3,4),
				C('BASKETBALL_SCHEDULE_STATUS.CANCEL')=>array(1,2,3,4),
				C('BASKETBALL_SCHEDULE_STATUS.DELAY')=>array(1,2,3,4),
				C('BASKETBALL_SCHEDULE_STATUS.IS_QUARTER1')=>array(1),
				C('BASKETBALL_SCHEDULE_STATUS.IS_QUARTER2')=>array(1,2),
				C('BASKETBALL_SCHEDULE_STATUS.IS_QUARTER3')=>array(1,2,3),
				C('BASKETBALL_SCHEDULE_STATUS.IS_QUARTER4')=>array(1,2,3,4),
				C('BASKETBALL_SCHEDULE_STATUS.IS_OVERTIME1')=>array(1,2,3,4,5),
				C('BASKETBALL_SCHEDULE_STATUS.IS_OVERTIME2')=>array(1,2,3,4,5),
				C('BASKETBALL_SCHEDULE_STATUS.IS_OVERTIME3')=>array(1,2,3,4,5),
				C('BASKETBALL_SCHEDULE_STATUS.IS_OVERTIME4')=>array(1,2,3,4,5),
				C('BASKETBALL_SCHEDULE_STATUS.IS_OVERTIME5')=>array(1,2,3,4,5),
		);
		
		$match_status = $schedule_info['schedule_match_status'];
		$quarters = $status_quarter_map[$match_status];
		

		$score_list = array();
		foreach($quarters as $quarter_no){
			$score_info['quarter'] = $quarter_no;
			$score_info['score'] = $schedule_info[$score_field_map[$quarter_no]];
			if(!$score_info['score']){
				continue;
			}
			$score_list[] = $score_info;
		}
	
		return $score_list;
	}
	
	public function getRecentRecord($api){
		$request_code = 'TEAM_RECENT_RECORD';
		
		$request_params['team_id'] = $api->team_id;
// 		$request_params['schedule_id'] = $api->third_party_schedule_id;
		$recent_record_list = $this->_requestDataFromBaseDataService($request_code, $request_params);
		
		$formated_record_list = $this->_formatTeamRecentRecordList($recent_record_list, $api->team_id);
		$response['team_id'] = $api->team_id;
		$response['list'] = $formated_record_list;
		
		return array(
				'result' => $response,
				'code' => C('ERROR_CODE.SUCCESS') 
		);
	}

	private function _formatTeamRecentRecordList($recent_record_list, $team_id){
		$formated_record_list = array();
		foreach($recent_record_list as $record_info){
			$formated_record_list[] = $this->_formatTeamRecentRecordInfo($record_info);
		}
		return $formated_record_list;
	}

	private function _formatTeamRecentRecordInfo($record_info){
		$formated_record_info['id'] = $record_info['schedule_qt_id'];
		$formated_record_info['league'] = $record_info['league_short'];
		$formated_record_info['date'] = strtotime($record_info['schedule_match_time']);
		$formated_record_info['home_team'] = $record_info['home_team'];
		$formated_record_info['guest_team'] = $record_info['guest_team'];
		$formated_record_info['home_team_id'] = $record_info['schedule_home_team_id'];
		$formated_record_info['guest_team_id'] = $record_info['schedule_guest_team_id'];
		$formated_record_info['score'] = $this->_buildScoreStringForCurrentScore($record_info);
		$formated_record_info['let_point'] = empty($record_info['jc_letscore'])?'':$record_info['jc_letscore'];
		$formated_record_info['base_point'] = empty($record_info['jc_total_score'])?'':$record_info['jc_total_score'];
		return $formated_record_info;
	}
	
	public function getFutureRecord($api){
		$request_code = 'TEAM_FUTURE_GAME_SCHEDULE';
		$request_params['team_id'] = $api->team_id;
		$future_schedule_list = $this->_requestDataFromBaseDataService($request_code, $request_params);
		$formated_future_schedule_list = $this->_formatTeamFutureScheduleList($future_schedule_list);
		$response['list'] = $formated_future_schedule_list;
		
		return array(
				'result' => $response,
				'code' => C('ERROR_CODE.SUCCESS') 
		);
	}
	
	private function _formatTeamFutureScheduleList($future_schedule_list){
		$formated_future_schedule_list = array();
		foreach ($future_schedule_list as $future_schedule_info) {
			$formated_future_schedule_list[] = $this->_formatTeamFutureScheduleInfo($future_schedule_info);
		}
		return $formated_future_schedule_list;
	}
	
	private function _formatTeamFutureScheduleInfo($future_schedule_info){
		$formated_future_schedule_info['id'] = $future_schedule_info['schedule_qt_id'];
		$formated_future_schedule_info['league'] = $future_schedule_info['league_short'];
		$formated_future_schedule_info['date'] = strtotime($future_schedule_info['schedule_match_time']);
		$formated_future_schedule_info['home_team'] = $future_schedule_info['home_team'];
		$formated_future_schedule_info['guest_team'] = $future_schedule_info['guest_team'];
		$formated_future_schedule_info['date_interval'] = $this->_getIntervalDays($future_schedule_info['schedule_match_time']);
		return $formated_future_schedule_info;
	}

	private function _getIntervalDays($time){
		$day = 0;
		$time = strtotime($time);
		$current_time = time();
		if ($time > $current_time) {
			$day = round(($time - $current_time) / (24 * 60 * 60));
		}
		return $day;
	}

	public function getHistoryRecord($api){
		$request_code = 'TEAM_HISTORY_RECORD';
		$request_params['schedule_id'] = $api->third_party_schedule_id;
		$history_record_list = $this->_requestDataFromBaseDataService($request_code, $request_params);
		$formated_history_record_list = $this->_formatHistoryRecordList($history_record_list);
		$response['list'] = $formated_history_record_list;
		
		return array(
				'result' => $response,
				'code' => C('ERROR_CODE.SUCCESS')
		);
	}

	private function _formatHistoryRecordList($history_record_list){
		$formated_history_record_list = array();
		foreach ($history_record_list as $history_record_info) {
			$formated_history_record_list[] = $this->_formatHistoryRecordInfo($history_record_info);
		}
		return $formated_history_record_list;
	}
	
	private function _formatHistoryRecordInfo($history_record_info){
		$formated_record_info['id'] = $history_record_info['schedule_qt_id'];
		$formated_record_info['league'] = $history_record_info['league_short'];
		$formated_record_info['date'] = strtotime($history_record_info['schedule_match_time']);
		$formated_record_info['home_team'] = $history_record_info['home_team'];
		$formated_record_info['guest_team'] = $history_record_info['guest_team'];
		$formated_record_info['home_team_id'] = $history_record_info['schedule_home_team_id'];
		$formated_record_info['guest_team_id'] = $history_record_info['schedule_guest_team_id'];
		$formated_record_info['let_point'] = empty($history_record_info['jc_letscore'])?'':$history_record_info['jc_letscore'];
		$formated_record_info['base_point'] = empty($history_record_info['jc_total_score'])?'':$history_record_info['jc_total_score'];
		$formated_record_info['score'] = $this->_buildScoreStringForCurrentScore($history_record_info);
		return $formated_record_info;
	}

	public function getLasterOdds($api){
		$odd_type_map = array(
				C('API_ASIA_ODDS')=>'letscore',
				C('API_EUROPE_ODDS')=>'europe',
				C('API_BASEPOINT_ODDS')=>'total',
		);
		
		$request_code = 'MATCH_REAL_TIME_ODDS';
		$request_params['schedule_id'] = $api->third_party_schedule_id;
		$request_params['type'] = $odd_type_map[$api->type];
		
		
		$odds_list = $this->_requestDataFromBaseDataService($request_code, $request_params);
		$type = $api->type;
		if ($type == C('API_ASIA_ODDS')) {
			$result = $this->_formatAsiaOdds($odds_list);
		} elseif ($type == C('API_EUROPE_ODDS')) {
			$result = $this->_formatEuropeOdds($odds_list);
		}else{
			$result = $this->_formatBasePointOdds($odds_list);
		}
		$response['list'] = $result;
		return array(
				'result' => $response,
				'code' => C('ERROR_CODE.SUCCESS') 
		);
	}

	private function _formatAsiaOdds($asia_odds_list){
		$formated_asia_odds_list = array();
		foreach($asia_odds_list as $odd_info){
			$formated_asia_odds_list[] = $this->_formatAsiaOddCompanyInfo($odd_info);
		}
		return $formated_asia_odds_list;
	}
	
	private function _formatAsiaOddCompanyInfo($odd_info){
		$formated_odd_info['id'] = $odd_info['ls_id'];
		$formated_odd_info['company_id'] = $odd_info['company_qt_id'];
		$formated_odd_info['company_name'] = emptyToStr($odd_info['company_name']);
		$formated_odd_info['origin_odds']['win'] = $odd_info['ls_home_odds_f'];
		$formated_odd_info['origin_odds']['lose'] = $odd_info['ls_guest_odds_f'];
		$formated_odd_info['origin_odds']['condition'] = $odd_info['ls_letgoal_f'];
		$formated_odd_info['current_odds']['win'] = $odd_info['ls_home_odds'];
		$formated_odd_info['current_odds']['lose'] = $odd_info['ls_guest_odds'];
		$formated_odd_info['current_odds']['condition'] = $odd_info['ls_letgoal'];
		return $formated_odd_info;
	}

	private function _formatEuropeOdds($standard_odds_list){
		$formated_europe_odds_list = array();
		foreach($standard_odds_list as $odd_info){
			$formated_europe_odds_list[] = $this->_formatEuropeOddCompanyInfo($odd_info);
		}
		return $formated_europe_odds_list;
	}
	
	private function _formatEuropeOddCompanyInfo($odd_info){
		$formated_odd_info['id'] = $odd_info['eo_id'];
		$formated_odd_info['company_id'] = $odd_info['company_qt_id'];
		$formated_odd_info['company_name'] = emptyToStr($odd_info['company_name']);
		$formated_odd_info['origin_odds']['win'] = $odd_info['first_home_win'];
		$formated_odd_info['origin_odds']['lose'] = $odd_info['first_guest_win'];
		$formated_odd_info['current_odds']['win'] = $odd_info['home_win'];
		$formated_odd_info['current_odds']['lose'] = $odd_info['guest_win'];
		return $formated_odd_info;
	}
	
	private function _formatBasePointOdds($basepoint_odds_list){
		$formated_basepoint_odds_list = array();
		foreach($basepoint_odds_list as $odd_info){
			$formated_basepoint_odds_list[] = $this->_formatBasePointOddCompanyInfo($odd_info);
		}
		return $formated_basepoint_odds_list;
	}
	
	private function _formatBasePointOddCompanyInfo($odd_info){
		$formated_odd_info['id'] = $odd_info['total_id'];
		$formated_odd_info['company_id'] = $odd_info['company_qt_id'];
		$formated_odd_info['company_name'] = emptyToStr($odd_info['company_name']);
		$formated_odd_info['origin_odds']['low'] = $odd_info['total_low_odds_f'];
		$formated_odd_info['origin_odds']['high'] = $odd_info['total_high_odds_f'];
		$formated_odd_info['origin_odds']['condition'] = $odd_info['total_score_f'];
		$formated_odd_info['current_odds']['low'] = $odd_info['total_low_odds'];
		$formated_odd_info['current_odds']['high'] = $odd_info['total_high_odds'];
		$formated_odd_info['current_odds']['condition'] = $odd_info['total_score'];
		return $formated_odd_info;
	}

	public function getScheduleDetail($api){
		$request_code = 'MATCH_INFO';
		$request_params['schedule_id'] = $api->third_party_schedule_id;
		$request_params['lottery_id'] = $api->lottery_id;
		
		$schedule_info = $this->_requestDataFromBaseDataService($request_code, $request_params);
		
		$response = $this->_formatScheduleDetailInfo($schedule_info);
		return array(
				'result' => $response,
				'code' => C('ERROR_CODE.SUCCESS') 
		);
	}

	private function _formatScheduleDetailInfo($schedule_info){
		if(empty($schedule_info)){
			return array();
		}
		$formated_schedule_info['id'] = $schedule_info['schedule_date'] . $schedule_info['schedule_no'];
		$formated_schedule_info['third_party_schedule_id'] = (int)$schedule_info['schedule_qt_id'];
		$formated_schedule_info['home'] = emptyToStr($schedule_info['home_team']);
		$formated_schedule_info['home_id'] = emptyToStr($schedule_info['schedule_home_team_id']);
		$formated_schedule_info['home_rank'] = emptyToStr($schedule_info['schedule_home_rank']);
		$formated_schedule_info['home_logo'] = emptyToStr($schedule_info['home_logo']);
		$formated_schedule_info['guest'] = emptyToStr($schedule_info['guest_team']);
		$formated_schedule_info['guest_id'] = emptyToStr($schedule_info['schedule_guest_team_id']);
		$formated_schedule_info['guest_rank'] = emptyToStr($schedule_info['schedule_guest_rank']);
		$formated_schedule_info['guest_logo'] = emptyToStr($schedule_info['guest_logo']);
		$formated_schedule_info['basketball_game_time_rule'] = intval($schedule_info['basketball_game_time_rule']);
		$formated_schedule_info['current_score'] = emptyToStr($this->_buildScoreStringForCurrentScore($schedule_info));
		$formated_schedule_info['match_status'] = (int)$this->_convertScheduleStatusToListType($schedule_info['schedule_match_status']);
		$formated_schedule_info['basketball_status'] = (int)$schedule_info['schedule_match_status'];
		$formated_schedule_info['first_half_begin_time'] = strtotime($schedule_info['schedule_match_time']);
		$formated_schedule_info['match_status_description'] = emptyToStr($this->_buildScheduleStatusDesc($schedule_info['schedule_match_status'],$schedule_info['basketball_game_time_rule']));
		$formated_schedule_info['match_duration'] = emptyToStr($schedule_info['schedule_remain_time']);
		$formated_schedule_info['league'] = emptyToStr($schedule_info['league_short']);
		return $formated_schedule_info;
	}

	private function _buildScheduleStatusDesc($match_status,$basketball_game_time_rule = 0){
	    if($basketball_game_time_rule == 1){
            $schedule_status_desc_map = C('SPECIAL_BASKETBALL_SCHEDULE_STATUS_DESC');
            return $schedule_status_desc_map[$match_status];
        }else{
            $schedule_status_desc_map = C('BASKETBALL_SCHEDULE_STATUS_DESC');
            return $schedule_status_desc_map[$match_status];
        }
	}
	
	public function requestGameTechStats($api){
		$request_code = 'MATCH_TECH_STATS';
		$request_params['schedule_id'] = $api->third_party_schedule_id;
	
		//team
		$request_params['type'] = 1;
		
		$game_tech_stats = $this->_requestDataFromBaseDataService($request_code, $request_params);
	
		$response['list'] = $this->_formatGameTechStatsList($game_tech_stats);
		return array(
				'result' => $response,
				'code' => C('ERROR_CODE.SUCCESS')
		);
	}
	
	private function _formatGameTechStatsList($game_tech_stats){
		if(!$game_tech_stats){
			return array();
		}
		$home_tech_stats = $game_tech_stats['home'];
		$guest_tech_stats = $game_tech_stats['guest'];
		$type_list = array(
// 				'shoot' => '出手次数',
// 				'field_goals' => '出手命中次数',
// 				'three_point' => '三分出手次数',
// 				'three_point_field_goals' => '三分命中个数',
// 				'free_throw' => '罚球次数',
// 				'free_throw_field_goals' => '罚球命中个数',
				'rebounds' => '篮板',
				'assists' => '助攻',
				'steals' => '抢断',
				'blocks' => '盖帽',
				'turnovers' => '失误',
				'fouls' => '犯规',
				'fast_break_points' => '快攻得分',
				'points_in_paint' => '内线得分',
				'exceed' => '最大领先' 
		);
		$formated_game_tech_stats_list = array();
		
		$shoot['name'] = '投篮命中';
		$shoot['home_count'] = $home_tech_stats['shoot']? $this->_formatFloatNumber($home_tech_stats['field_goals']/$home_tech_stats['shoot']*100).'%':0;
		$shoot['guest_count'] = $guest_tech_stats['shoot']? $this->_formatFloatNumber($guest_tech_stats['field_goals']/$guest_tech_stats['shoot']*100).'%':0;
		$shoot['home_rate'] = $home_tech_stats['shoot']? $this->_formatFloatNumber($home_tech_stats['field_goals']/$home_tech_stats['shoot'],2):0;
		$shoot['guest_rate'] = $guest_tech_stats['shoot']? $this->_formatFloatNumber($guest_tech_stats['field_goals']/$guest_tech_stats['shoot'],2):0;
		
		$three_point['name'] = '3分命中';
		$three_point['home_count'] = $home_tech_stats['three_point']? $this->_formatFloatNumber($home_tech_stats['three_point_field_goals']/$home_tech_stats['three_point']*100).'%':0;
		$three_point['guest_count'] = $guest_tech_stats['three_point']? $this->_formatFloatNumber($guest_tech_stats['three_point_field_goals']/$guest_tech_stats['three_point']*100).'%':0;
		$three_point['home_rate'] = $home_tech_stats['three_point']? $this->_formatFloatNumber($home_tech_stats['three_point_field_goals']/$home_tech_stats['three_point'],2):0;
		$three_point['guest_rate'] = $guest_tech_stats['three_point']? $this->_formatFloatNumber($guest_tech_stats['three_point_field_goals']/$guest_tech_stats['three_point'],2):0;
		
		$free_throw['name'] = '罚球命中';
		$free_throw['home_count'] = $home_tech_stats['free_throw']? $this->_formatFloatNumber($home_tech_stats['free_throw_field_goals']/$home_tech_stats['free_throw']*100).'%':0;
		$free_throw['guest_count'] = $guest_tech_stats['free_throw']? $this->_formatFloatNumber($guest_tech_stats['free_throw_field_goals']/$guest_tech_stats['free_throw']*100).'%':0;
		$free_throw['home_rate'] = $home_tech_stats['free_throw']? $this->_formatFloatNumber($home_tech_stats['free_throw_field_goals']/$home_tech_stats['free_throw'],2):0;
		$free_throw['guest_rate'] = $guest_tech_stats['free_throw']? $this->_formatFloatNumber($guest_tech_stats['free_throw_field_goals']/$guest_tech_stats['free_throw'],2):0;
		
		$formated_game_tech_stats_list[] = $shoot;
		$formated_game_tech_stats_list[] = $three_point;
		$formated_game_tech_stats_list[] = $free_throw;
		foreach($type_list as $type=>$type_desc){
			$formated_game_tech_stat_item['home_count'] = $home_count = $home_tech_stats[$type];
			$formated_game_tech_stat_item['guest_count'] = $guest_count = $guest_tech_stats[$type];
			$total = $home_count + $guest_count;
			if($type=='fast_break_points' || $type=='points_in_paint' || $type=='exceed'){
				if(!$total){
					continue;
				}
			}
			$formated_game_tech_stat_item['home_rate'] = $total ? $this->_formatFloatNumber($home_count / $total,2) : 0;
			$formated_game_tech_stat_item['guest_rate'] = $total ? $this->_formatFloatNumber($guest_count / $total,2) : 0;
			$formated_game_tech_stat_item['name'] = $type_desc;
			$formated_game_tech_stats_list[] = $formated_game_tech_stat_item;
		}
		
		return $formated_game_tech_stats_list;
	}
	
	public function requestOddChangeListByCompany($api){
		$odd_type_map = array(
				C('API_ASIA_ODDS')=>'letscore',
				C('API_EUROPE_ODDS')=>'europe',
				C('API_BASEPOINT_ODDS')=>'total',
		);
		
		$request_code = 'MATCH_ODD_CHANGES';
		$request_params['schedule_id'] = $api->third_party_schedule_id;
		$request_params['company_id'] = $api->company_id;
		
		$request_params['type'] = $odd_type_map[$api->type];
		
		$odds_list = $this->_requestDataFromBaseDataService($request_code, $request_params);
		$type = $api->type;
		if ($type == C('API_ASIA_ODDS')) {
			$result = $this->_formatAsiaOddChangeList($odds_list);
		} elseif ($type == C('API_EUROPE_ODDS')) {
			$result = $this->_formatEuropeOddChangeList($odds_list);
		}else{
			$result = $this->_formatBasePointOddChangeList($odds_list);
		}
		$response['list'] = $result;
		return array(
				'result' => $response,
				'code' => C('ERROR_CODE.SUCCESS')
		);
	}
	
	private function _formatAsiaOddChangeList($asia_odds_list){
		$formated_asia_odds_list = array();
		foreach($asia_odds_list as $odd_info){
			$formated_asia_odds_list[] = $this->_formatAsiaOddChangeCompanyInfo($odd_info);
		}
		return $formated_asia_odds_list;
	}
	
	private function _formatAsiaOddChangeCompanyInfo($odd_info){
		$formated_odd_info['time'] = strtotime($odd_info['lgd_createtime']);
		$formated_odd_info['current_odds']['win'] = $odd_info['lgd_home_odds'];
		$formated_odd_info['current_odds']['lose'] = $odd_info['lgd_guest_odds'];
		$formated_odd_info['current_odds']['condition'] = $odd_info['lgd_letgoal'];
		return $formated_odd_info;
	}
	
	private function _formatEuropeOddChangeList($standard_odds_list){
		$formated_europe_odds_list = array();
		foreach($standard_odds_list as $odd_info){
			$formated_europe_odds_list[] = $this->_formatEuropeOddChangeCompanyInfo($odd_info);
		}
		return $formated_europe_odds_list;
	}
	
	private function _formatEuropeOddChangeCompanyInfo($odd_info){
		$formated_odd_info['time'] = strtotime($odd_info['eod_createtime']);
		$formated_odd_info['current_odds']['win'] = $odd_info['eod_home_win'];
		$formated_odd_info['current_odds']['lose'] = $odd_info['eod_guest_win'];
		return $formated_odd_info;
	}
	
	private function _formatBasePointOddChangeList($basepoint_odds_list){
		$formated_basepoint_odds_list = array();
		foreach($basepoint_odds_list as $odd_info){
			$formated_basepoint_odds_list[] = $this->_formatBasePointOddChangeCompanyInfo($odd_info);
		}
		return $formated_basepoint_odds_list;
	}
	
	private function _formatBasePointOddChangeCompanyInfo($odd_info){
		$formated_odd_info['time'] = strtotime($odd_info['tod_createtime']);
		$formated_odd_info['current_odds']['low'] = $odd_info['tod_low_odds'];
		$formated_odd_info['current_odds']['high'] = $odd_info['tod_high_odds'];
		$formated_odd_info['current_odds']['condition'] = $odd_info['tod_score'];
		return $formated_odd_info;
	}
	
	public function requestScoreDetail($api){
		$request_code = 'BASKETBALL_SCORE_DETAIL';
		$request_params['schedule_id'] = $api->third_party_schedule_id;
	
		$schedule_info = $this->_requestDataFromBaseDataService($request_code, $request_params);
	
		$response = $this->_formatScoreDetailInfo($schedule_info);
		return array(
				'result' => $response,
				'code' => C('ERROR_CODE.SUCCESS')
		);
	}
	
	private function _formatScoreDetailInfo($schedule_info){
		$formated_schedule_info['home_score'] = $this->_buildScoreListForHomeScore($schedule_info);
		$formated_schedule_info['guest_score'] = $this->_buildScoreListForGuestScore($schedule_info);
		return $formated_schedule_info;
	}
	
	public function requestPlayerTechStats($api){
		$request_code = 'MATCH_TECH_STATS';
		$request_params['schedule_id'] = $api->third_party_schedule_id;
		//player
		$request_params['type'] = 2;
		$request_params['team_id'] = $api->team_id;
		$player_tech_stats_list = $this->_requestDataFromBaseDataService($request_code, $request_params);
	
		$response['list'] = $this->_formatPlayerTechStatsList($player_tech_stats_list);
		return array(
				'result' => $response,
				'code' => C('ERROR_CODE.SUCCESS')
		);
	}
	
	private function _formatPlayerTechStatsList($player_tech_stats_list){
		$formated_player_tech_stats_list = array();
		foreach($player_tech_stats_list as $player_tech_stats_item){
			$formated_player_tech_stats_list[] = $this->_formatPlayerTechStatsItem($player_tech_stats_item);
		}
	
		return $formated_player_tech_stats_list;
	}

	private function _formatPlayerTechStatsItem($player_tech_stats_item){
		$formated_player_tech_stats_item['player_id'] = $player_tech_stats_item['player_id'];
		$formated_player_tech_stats_item['player_name'] = $player_tech_stats_item['player_name'];
		$formated_player_tech_stats_item['play_time'] = $player_tech_stats_item['minutes_played'];
		$formated_player_tech_stats_item['score'] = $player_tech_stats_item['points_scored'];
		$formated_player_tech_stats_item['rebound'] = $player_tech_stats_item['rebounds'];
		$formated_player_tech_stats_item['assist'] = $player_tech_stats_item['assists'];
		$formated_player_tech_stats_item['shoot'] = $player_tech_stats_item['field_goals'].'-'.$player_tech_stats_item['shoot'];
		$formated_player_tech_stats_item['three_point'] = $player_tech_stats_item['three_point_field_goals'].'-'.$player_tech_stats_item['three_point'];
		$formated_player_tech_stats_item['on_court'] = $player_tech_stats_item['on_court'];
		$formated_player_tech_stats_item['lineup'] = $player_tech_stats_item['lineup'];
		$formated_player_tech_stats_item['free_throws'] = $player_tech_stats_item['free_throw_field_goals'].'-'.$player_tech_stats_item['free_throw'];
		return $formated_player_tech_stats_item;
	}
	
	public function requestTeamRecordStats($api){
		$request_code = 'LEAGUE_SCORE';
		$request_params['schedule_id'] = $api->third_party_schedule_id;
		$request_params['team_id'] = $api->team_id;
		
		$team_record_stats = $this->_requestDataFromBaseDataService($request_code, $request_params);
		$response['list'] = $this->_formatTeamRecordStatsList($team_record_stats);
		return array(
				'result' => $response,
				'code' => C('ERROR_CODE.SUCCESS')
		);
	}
	
	private function _formatTeamRecordStatsList($team_record_stats){
		if(empty($team_record_stats)){
			return array();
		}
		$formated_home_team_record_stats['round'] = '主场';
		$home_game_count = intval($team_record_stats['tr_home_win'])+intval($team_record_stats['tr_home_lost']);
		$formated_home_team_record_stats['game_count'] = $home_game_count;
		$formated_home_team_record_stats['win'] = $team_record_stats['tr_home_win'];
		$formated_home_team_record_stats['lose'] = $team_record_stats['tr_home_lost'];
		$home_score_per_game = $home_game_count? $team_record_stats['tr_home_score']/$home_game_count:0;
		$home_loss_score_per_game = $home_game_count? $team_record_stats['tr_home_loss_score']/$home_game_count:0;
		$formated_home_team_record_stats['goal'] = $this->_formatFloatNumber($home_score_per_game);
		$formated_home_team_record_stats['fumble'] = $this->_formatFloatNumber($home_loss_score_per_game);
		$formated_home_team_record_stats['differential'] = $this->_formatFloatNumber($home_score_per_game - $home_loss_score_per_game);
		$home_rate = $home_game_count?$team_record_stats['tr_home_win']/$home_game_count*100:0;
		$formated_home_team_record_stats['win_rate'] = $this->_formatFloatNumber($home_rate,2).'%';
		
		$formated_guest_team_record_stats['round'] = '客场';
		$guest_game_count = intval($team_record_stats['tr_guest_win'])+intval($team_record_stats['tr_guest_lost']);
		$formated_guest_team_record_stats['game_count'] = $guest_game_count;
		$formated_guest_team_record_stats['win'] = $team_record_stats['tr_guest_win'];
		$formated_guest_team_record_stats['lose'] = $team_record_stats['tr_guest_lost'];
		$guest_score_per_game = $guest_game_count? $team_record_stats['tr_guest_score']/$guest_game_count:0;
		$guest_loss_score_per_game = $guest_game_count? $team_record_stats['tr_guest_loss_score']/$guest_game_count:0;
		
		$formated_guest_team_record_stats['goal'] = $this->_formatFloatNumber($guest_score_per_game);
		$formated_guest_team_record_stats['fumble'] = $this->_formatFloatNumber($guest_loss_score_per_game);
		$formated_guest_team_record_stats['differential'] = $this->_formatFloatNumber($guest_score_per_game-$guest_loss_score_per_game);
		$guest_rate = $guest_game_count?$team_record_stats['tr_guest_win']/$guest_game_count*100:0;
		$formated_guest_team_record_stats['win_rate'] = $this->_formatFloatNumber($guest_rate,2).'%';;
		
		$total_game_count = $home_game_count+$guest_game_count;
		$total_win_game_count = $team_record_stats['tr_home_win']+$team_record_stats['tr_guest_win'];
		$formated_total_team_record_stats['round'] = '全部';
		$formated_total_team_record_stats['game_count'] = $total_game_count;
		$formated_total_team_record_stats['win'] = $team_record_stats['tr_home_win']+$team_record_stats['tr_guest_win'];
		$formated_total_team_record_stats['lose'] = $team_record_stats['tr_home_lost']+$team_record_stats['tr_guest_lost'];
		
		$total_score = $team_record_stats['tr_home_score']+$team_record_stats['tr_guest_score'];
		$total_loss_score = $team_record_stats['tr_home_loss_score']+$team_record_stats['tr_guest_loss_score'];
		$total_score_per_game = $total_game_count? $total_score/$total_game_count:0;
		$total_loss_score_per_game = $total_game_count? $total_loss_score/$total_game_count:0;
		
		$formated_total_team_record_stats['goal'] = $this->_formatFloatNumber($total_score_per_game);
		$formated_total_team_record_stats['fumble'] = $this->_formatFloatNumber($total_loss_score_per_game);
		$formated_total_team_record_stats['differential'] = $this->_formatFloatNumber($total_score_per_game-$total_loss_score_per_game);
		$total_rate = $total_game_count?$total_win_game_count/$total_game_count*100:0;
		$formated_total_team_record_stats['win_rate'] = $this->_formatFloatNumber($total_rate,2).'%';;
		
		$recent_10game_count = intval($team_record_stats['tr_near10_win'])+intval($team_record_stats['tr_near10_loss']);
		$formated_recent_10game_record_stats['round'] = '近'.$recent_10game_count.'场';
		$formated_recent_10game_record_stats['game_count'] = $recent_10game_count;
		$formated_recent_10game_record_stats['win'] = $team_record_stats['tr_near10_win'];
		$formated_recent_10game_record_stats['lose'] = $team_record_stats['tr_near10_loss'];
		$recent_10game_score_per_game = $recent_10game_count? $team_record_stats['tr_near10_score']/$recent_10game_count:0;
		$recent_10game_loss_score_per_game = $recent_10game_count? $team_record_stats['tr_near10_loss_score']/$recent_10game_count:0;
		
		$formated_recent_10game_record_stats['goal'] = $this->_formatFloatNumber($recent_10game_score_per_game);
		$formated_recent_10game_record_stats['fumble'] = $this->_formatFloatNumber($recent_10game_loss_score_per_game);
		$formated_recent_10game_record_stats['differential'] = $this->_formatFloatNumber($recent_10game_score_per_game-$recent_10game_loss_score_per_game);
		$recent_10game_rate = $recent_10game_count?$team_record_stats['tr_near10_win']/$recent_10game_count*100:0;
		$formated_recent_10game_record_stats['win_rate'] = $this->_formatFloatNumber($recent_10game_rate,2).'%';;
		
		$formated_team_record_stats = array(
				$formated_total_team_record_stats,
				$formated_home_team_record_stats,
				$formated_guest_team_record_stats,
				$formated_recent_10game_record_stats,
		);
		return $formated_team_record_stats;
	}
	
	private function _formatFloatNumber($number,$decimals=1){
		return number_format($number,$decimals);
	}
	
	public function fetchScheduleListByScheduleNos($schedule_nos){
		$request_code = 'SCHEDULE_LIST';
		$lottery_id = $api->lottery_id;
		
		$schedule_list = array();
		$request_params['schedule_no'] = implode('_', $schedule_nos);
		$schedule_list = $this->_requestDataFromBaseDataService($request_code, $request_params);
		
		$response_schedule_list = array();
		foreach ($schedule_list as $schedule_info) {
			$formated_schedule_info = $this->_formatScheduleInfo($schedule_info);
			$response_schedule_list[] = $formated_schedule_info;
		}
		return $response_schedule_list;
	}
	
}