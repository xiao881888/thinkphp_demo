<?php
namespace Home\Controller;

class BaseZcGameDataController extends GlobalController
{
    protected $schedule_odd_list = array();
    const SCHEDULE_CHANGE_UP = 0;
    const SCHEDULE_CHANGE_DOWN = 1;

    const SCHEDULE_HALF_TIME = 45;
    const SCHEDULE_END_TIME = 90;

    public function getScheduleEvent($api)
    {
        $schedule_id = $api->schedule_id;
        $lottery_id = $api->lottery_id;
        $third_party_schedule_id = $api->third_party_schedule_id;
        $schedule_id = $this->addFirstToScheduleId($schedule_id);
        $request_data['id'] = $schedule_id;
        if(!empty($third_party_schedule_id)){
            $request_data['schedule_id'] = $third_party_schedule_id;
        }
        $request_method = 'matchEvent';
        $match_info_list = $this->requestJcDataFromBaseDataService($request_method,$request_data);
        $request_method = 'match';
        $schedule_info = $this->requestJcDataFromBaseDataService($request_method,$request_data);
        $format_data = $this->formatScheduleEvent($match_info_list, $schedule_info,$api);
        return array('result' => array('list'=>$format_data),
            'code' => C('ERROR_CODE.SUCCESS'));
    }

    public function getRecentRecord($api)
    {
        $team_id = $api->team_id;
        $third_party_schedule_id = $api->third_party_schedule_id;
        $lottery_id = $api->lottery_id;
        $schedule_id = $api->schedule_id;
        $request_data['team_id'] = $team_id;
        $request_data['type'] = C('API_RECENT_RECORD');
        $request_method = 'record';
        $recent_record_list = $this->requestJcDataFromBaseDataService($request_method,$request_data);
        $result = $this->formatRecentRecord($recent_record_list, $team_id);
        return array('result' => $result,
            'code' => C('ERROR_CODE.SUCCESS'));
    }

    public function getFutureRecord($api)
    {
        $team_id = $api->team_id;
        $third_party_schedule_id = $api->third_party_schedule_id;
        $lottery_id = $api->lottery_id;
        $schedule_id = $api->schedule_id;
        $request_data['team_id'] = $team_id;
        $request_data['type'] = C('API_FUTURE_RECORD');
        $request_method = 'record';
        $future_record_list = $this->requestJcDataFromBaseDataService($request_method,$request_data);
        $result = $this->formatFutureRecord($future_record_list, $team_id);
        return array('result' => $result,
            'code' => C('ERROR_CODE.SUCCESS'));
    }

    public function getHistoryRecord($api)
    {
        $schedule_id = $api->schedule_id;
        $third_party_schedule_id = $api->third_party_schedule_id;
        $lottery_id = $api->lottery_id;
        $schedule_id = $this->addFirstToScheduleId($schedule_id);
        $request_data['id'] = $schedule_id;
        if(!empty($third_party_schedule_id)){
            $request_data['schedule_id'] = $third_party_schedule_id;
        }
        $request_data['type'] = C('API_HISTORY_RECORD');
        $request_method = 'record';
        $history_record_list = $this->requestJcDataFromBaseDataService($request_method,$request_data);
        $result = $this->formatHistoryRecord($history_record_list);
        return array('result' => $result,
            'code' => C('ERROR_CODE.SUCCESS'));
    }

    public function getScheduleIntergral($api)
    {
        $schedule_id = $api->schedule_id;
        $schedule_id = $this->addFirstToScheduleId($schedule_id);
        $type = $api->type;
        $third_party_schedule_id = $api->third_party_schedule_id;
        $request_data['id'] = $schedule_id;
        if(!empty($third_party_schedule_id)){
            $request_data['schedule_id'] = $third_party_schedule_id;
        }
        $request_method = 'intergral';
        $scheduleIntergralInfo = $this->requestJcDataFromBaseDataService($request_method,$request_data);
        $result = $this->formatScheduleIntergral($scheduleIntergralInfo, $type);
        return array('result' => $result,
            'code' => C('ERROR_CODE.SUCCESS'));

    }

    public function getLasterOdds($api)
    {
        $schedule_id = $api->schedule_id;
        $third_party_schedule_id = $api->third_party_schedule_id;
        $lottery_id = $api->lottery_id;
        $schedule_id = $this->addFirstToScheduleId($schedule_id);
        $type = $api->type;
        $request_data['id'] = $schedule_id;
        if(!empty($third_party_schedule_id)){
            $request_data['schedule_id'] = $third_party_schedule_id;
        }
        $request_data['type'] = $type;

        if ($type == C('API_ASIA_ODDS')) {
            $request_method = 'asia';
            $letgoal_list = $this->requestJcDataFromBaseDataService($request_method,$request_data);
            $result = $this->formatAsiaOdds($letgoal_list);
        } elseif ($type == C('API_EUROPE_ODDS')) {
            $request_method = 'europe';
            $standard_list = $this->requestJcDataFromBaseDataService($request_method,$request_data);
            $result = $this->formatEuropeOdds($standard_list);
        }

        return array('result' => $result,
            'code' => C('ERROR_CODE.SUCCESS'));

    }

    public function getScheduleDetail($api)
    {
        $schedule_id = $api->schedule_id;
        $third_party_schedule_id = $api->third_party_schedule_id;
        $lottery_id = $api->lottery_id;
        $schedule_id = $this->addFirstToScheduleId($schedule_id);
        $request_data['id'] = $schedule_id;
        if(!empty($third_party_schedule_id)){
            $request_data['schedule_id'] = $third_party_schedule_id;
        }
        $request_method = 'match';
        $schedule_info = $this->requestJcDataFromBaseDataService($request_method,$request_data);
        $result = $this->formatScheduleDetail($schedule_info);
        return array('result' => $result,
            'code' => C('ERROR_CODE.SUCCESS'));
    }

    public function requestGameTechStats($api){
        $schedule_id = $api->schedule_id;
        $third_party_schedule_id = $api->third_party_schedule_id;
        $schedule_id = $this->addFirstToScheduleId($schedule_id);
        $request_data['id'] = $schedule_id;
        if(!empty($third_party_schedule_id)){
            $request_data['schedule_id'] = $third_party_schedule_id;
        }
        $request_method = 'technic';
        $game_tech_info = $this->requestJcDataFromBaseDataService($request_method,$request_data);
        $response_data = $this->formatGameTechStatsList($game_tech_info);
        return array(
            'result' => array('list'=>$response_data),
            'code' => C('ERROR_CODE.SUCCESS')
        );
    }

    public function requestOddChangeListByCompany($api){
        $odd_type_map = array(
            C('API_ASIA_ODDS')=>'letscore',
            C('API_EUROPE_ODDS')=>'europe',
            C('API_BASEPOINT_ODDS')=>'total',
        );

        $schedule_id = $api->schedule_id;
        $schedule_id = $this->addFirstToScheduleId($schedule_id);
        $request_data['id'] = $schedule_id;
        $third_party_schedule_id = $api->third_party_schedule_id;
        if(!empty($third_party_schedule_id)){
            $request_data['schedule_id'] = $third_party_schedule_id;
        }
        $request_data['company_id'] = $api->company_id;
        $request_data['type'] = $odd_type_map[$api->type];
        $type = $api->type;
        if ($type == C('API_ASIA_ODDS')) {
            $request_method = 'asiaDetail';
            $odds_list = $this->requestJcDataFromBaseDataService($request_method,$request_data);
            $format_data = $this->formatAsiaOddChangeList($odds_list);
        } elseif ($type == C('API_EUROPE_ODDS')) {
            $request_method = 'europeDetail';
            $odds_list = $this->requestJcDataFromBaseDataService($request_method,$request_data);
            $format_data = $this->formatEuropeOddChangeList($odds_list);
        }
        return array(
            'result' => array('list'=>$format_data),
            'code' => C('ERROR_CODE.SUCCESS')
        );
    }

    protected function formatScheduleEvent($match_info_list, $schedule_info,$api)
    {
        // 比赛状态 0:未开,1:上半场,2:中场,3:下半场,4,加时，-11:待定,-12:腰斩,-13:中断,-14:推迟,-1:完场，-10取消
        $is_normal_schedule = $this->isNormalSchedule($schedule_info);
        return $this->_getScheduleEvent($match_info_list, $schedule_info,$api,$is_normal_schedule);
    }

    private function _getScheduleEvent($match_info_list, $schedule_info,$api,$is_normal_schedule){
        $data = array();
        if (!empty($schedule_info) && $is_normal_schedule) {
            $data[] = array(
                'event_type' => C('API_EVENT_TYPE_OF_SCHEDULE_BEGIN'),
                'content' => C('SCHEDULE_START_STR'),
            );

            $is_first_half = true;
            foreach ($match_info_list as $match_info) {
                if ($match_info['mdr_happen_time'] > self::SCHEDULE_HALF_TIME && $is_first_half) {
                    $is_first_half = false;
                    $data[] = array(
                        'event_type' => C('API_EVENT_TYPE_OF_MIDFIED'),
                        'content' => C('SCHEDULE_MIDFIED_STR'),
                    );
                }

                if ($match_info['mdr_kind'] == C('JC_EVENT_TYPE_OF_CHANGE')) {

                    //换上
                    $event_type = C('API_EVENT_TYPE_OF_CHANGE_UP');
                    $content = emptyToStr($this->formatePlayerName($match_info['player_name'], self::SCHEDULE_CHANGE_UP));
                    $data[] = $this->rebuildMatchEvent($match_info,$event_type,$content);

                    //换下
                    $event_type = C('API_EVENT_TYPE_OF_CHANGE_DOWN');
                    $content = emptyToStr($this->formatePlayerName($match_info['player_name'], self::SCHEDULE_CHANGE_DOWN));
                    $data[] = $this->rebuildMatchEvent($match_info,$event_type,$content);

                }elseif($match_info['mdr_kind'] == C('JC_EVENT_TYPE_OF_PENALTY') || $match_info['mdr_kind'] == C('JC_EVENT_TYPE_OF_OWN_GOAL') || $match_info['mdr_kind'] == C('JC_EVENT_TYPE_OF_YELLOW_CARD') || $match_info['mdr_kind'] == C('JC_EVENT_TYPE_OF_RED_CARD') || $match_info['mdr_kind'] == C('JC_EVENT_TYPE_OF_TWO_YELLOW_BROWN')){

                    //其他事件
                    $event_type = $this->getAppEvenId($match_info['mdr_kind']);
                    $content = $match_info['player_name'];
                    $data[] = $this->rebuildMatchEvent($match_info,$event_type,$content);

                }elseif($match_info['mdr_kind'] == C('JC_EVENT_TYPE_OF_GOAL')){

                    //进球
                    $event_type = $this->getAppEvenId($match_info['mdr_kind']);
                    $content = $this->getGoalContent($match_info['player_name']);
                    $data[] = $this->rebuildMatchEvent($match_info,$event_type,$content);

                    $sdk_version = $api->sdk_version;
                    if($sdk_version >= 7){
                        //助攻
                        $assist_content = $this->getAssistContent($match_info['player_name']);
                        if(!empty($assist_content)){
                            $event_type = C('API_EVENT_TYPE_OF_ASSIST');
                            $content = $assist_content;
                            $data[] = $this->rebuildMatchEvent($match_info,$event_type,$content);
                        }
                    }
                }
            }
            if ($schedule_info['schedule_match_state'] == C('SCHEDULE_STATUS_OF_END')) {
                $data[] = array(
                    'event_type' => C('API_EVENT_TYPE_OF_SCHEDULE_END'),
                    'content' => C('SCHEDULE_END_STR'),
                );
            }
        }
        return $data;
    }

    protected function getGoalContent($play_name){
        $play_name_arr = explode('(',$play_name);
        return $play_name_arr[0];
    }

    protected function getAssistContent($play_name){
        $assist_play_name = '';
        if(strpos($play_name,"助攻")){
            $play_name_arr = explode(':',$play_name);
            $assist_play_name = substr($play_name_arr[1],0,strlen($play_name_arr[1]) - 1);
        }
        return $assist_play_name;
    }

    protected function rebuildMatchEvent($match_info,$event_type,$content){
        $data = array(
            'id' => emptyToStr($match_info['mdr_id']),
            'team' => emptyToStr($match_info['team_id']),
            'event_type' => emptyToStr($event_type),
            'time' => emptyToStr($match_info['mdr_happen_time']),
            'content' => emptyToStr($content),
        );
        return $data;

    }

    protected function isNormalSchedule($schedule_info){
        return in_array($schedule_info['schedule_match_state'], array(C('SCHEDULE_STATUS_OF_FIRST_HALF'), C('SCHEDULE_STATUS_OF_MIDFIED'), C('SCHEDULE_STATUS_OF_SECOND_HALF'), C('SCHEDULE_STATUS_OF_OVER_TIME'), C('SCHEDULE_STATUS_OF_END')));
    }

    protected function formatePlayerName($player_name, $type)
    {
        $player_name_arr = explode("↑", $player_name);
        $player_name_arr[1] = str_replace("↓", "", $player_name_arr[1]);
        return $player_name_arr[$type];
    }


    protected function getAppEvenId($mdr_king)
    {
        $event_type_list = C('API_EVENT_TYPE_LIST');
        return $event_type_list[$mdr_king];
    }

    protected function formatRecentRecord($recent_record_list, $team_id)
    {
        $data = array();
        $data['latest_record']['win'] = emptyToStr($recent_record_list['win']);
        $data['latest_record']['lose'] = emptyToStr($recent_record_list['fail']);
        $data['latest_record']['equal'] = emptyToStr($recent_record_list['flat']);
        $data['latest_record']['games_count'] = emptyToStr($recent_record_list['count']);

        $data['list'] = array();
        $data['team_id'] = emptyToStr($team_id);

        foreach ($recent_record_list['list'] as $key => $recent_record) {
            $data['list'][$key] = array(
                'id' => emptyToStr($recent_record['schedule_id']),
                'league' => emptyToStr($recent_record['league']),
                'date' => strtotime($recent_record['schedule_match_time']),
                'home_team' => emptyToStr($recent_record['schedule_home_team']),
                'guest_team' => emptyToStr($recent_record['schedule_guest_team']),
                'home_team_id' => emptyToStr($recent_record['schedule_home_team_id']),
                'guest_team_id' => emptyToStr($recent_record['schedule_guest_team_id']),
                'score' => emptyToStr($recent_record['schedule_home_score'] . ":" . $recent_record['schedule_guest_score']),
                'result' => emptyToStr($this->getScheduleResult($recent_record['schedule_home_score'], $recent_record['schedule_guest_score'])),
                'let_point' => emptyToStr($recent_record['jc_letscore']),
                'base_point' => emptyToStr($recent_record['jc_total_score']),
            );
        }
        return $data;
    }

    protected function getScheduleResult($schedule_home_score, $schedule_guest_score)
    {
        if ($schedule_home_score > $schedule_guest_score) {
            return C('HOME_WIN_STR');
        } elseif ($schedule_home_score == $schedule_guest_score) {
            return C('HOME_EQUAL_STR');
        } else {
            return C('HOME_FAIL_STR');
        }

    }

    protected function formatFutureRecord($future_record_list, $team_id = '')
    {
        $data = array();
        $data['list'] = array();
        $data['team_id'] = emptyToStr($team_id);
        foreach ($future_record_list as $future_record) {
            $data['list'][] = array(
                'id' => emptyToStr($future_record['schedule_id']),
                'league' => emptyToStr($future_record['league']),
                'date' => strtotime($future_record['schedule_match_time']),
                'home_team' => emptyToStr($future_record['schedule_home_team']),
                'guest_team' => emptyToStr($future_record['schedule_guest_team']),
                'date_interval' => emptyToStr($this->getFutureDay($future_record['schedule_match_time'])),
            );
        }
        return $data;
    }

    protected function getFutureDay($time)
    {
        $day = 0;
        $time = strtotime($time);
        $current_time = time();
        if ($time > $current_time) {
            $day = round(($time - $current_time) / (24 * 60 * 60));
        }
        return $day;
    }

    protected function formatHistoryRecord($history_record_list)
    {
        $data = array();
        $data['history_fight']['win'] = emptyToStr($history_record_list['win']);
        $data['history_fight']['lose'] = emptyToStr($history_record_list['fail']);
        $data['history_fight']['equal'] = emptyToStr($history_record_list['flat']);
        $data['history_fight']['games_count'] = emptyToStr($history_record_list['count']);
        $data['list'] = array();
        foreach ($history_record_list['list'] as $history_record) {
            $data['list'][] = array(
                'id' => emptyToStr($history_record['schedule_id']),
                'league' => emptyToStr($history_record['league']),
                'date' => strtotime($history_record['schedule_match_time']),
                'home_team' => emptyToStr($history_record['schedule_home_team']),
                'guest_team' => emptyToStr($history_record['schedule_guest_team']),
                'home_team_id' => emptyToStr($history_record['schedule_home_team_id']),
                'guest_team_id' => emptyToStr($history_record['schedule_guest_team_id']),
                'score' => emptyToStr($history_record['schedule_home_score'] . ":" . $history_record['schedule_guest_score']),
                'let_point' => emptyToStr($history_record['jc_letscore']),
                'base_point' => emptyToStr($history_record['jc_total_score']),
            );
        }
        return $data;
    }

    protected function formatScheduleIntergral($scheduleIntergralInfo, $type)
    {
        $data = array();
        $scheduleIntergralList = $this->getScheduleIntergralListForType($scheduleIntergralInfo,$type);
        foreach ($scheduleIntergralList as $key => $info) {
            $data['league'] = '';
            $data['list'][] = array(
                'id' => emptyToStr($info['score_id']),
                'team_id' => emptyToStr($info['team_qt_id']),
                'team_name' => emptyToStr($info['team_name']),
                'rank' => emptyToStr($info['score_order']),
                'game_count' => emptyToStr($info['score_curr_round']),
                'win' => emptyToStr($info['score_win']),
                'equal' => emptyToStr($info['score_flat']),
                'lose' => emptyToStr($info['score_fail']),
                'goal' => emptyToStr($info['score_total_homescore']),
                'fumble' => emptyToStr($info['score_total_guestscore']),
                'point' => emptyToStr($info['score_total']),
            );
        }
        return $data;
    }

    protected function getScheduleIntergralListForType($scheduleIntergralInfo,$type){
        $data = array();
        if ($type == C('API_TOTAL_INTERGRAL')) {
            $data = $scheduleIntergralInfo['total'];
        } elseif ($type == C('API_HOME_INTERGRAL')) {
            $data = $scheduleIntergralInfo['home'];
        } elseif ($type == C('API_GUEST_INTERGRAL')) {
            $data = $scheduleIntergralInfo['guest'];
        }
        return $data;
    }


    protected function formatAsiaOdds($letgoal_list)
    {
        $data = array();
        $data['average_origin_odds']['win'] = emptyToStr($letgoal_list['letgoal_first_up_odds_avg']);
        $data['average_origin_odds']['lose'] = emptyToStr($letgoal_list['letgoal_first_down_odds_avg']);
        $data['average_current_odds']['win'] = emptyToStr($letgoal_list['letgoal_up_odds_avg']);
        $data['average_current_odds']['lose'] = emptyToStr($letgoal_list['letgoal_down_odds_avg']);
        $data['list'] = array();
        foreach ($letgoal_list['list'] as $key => $info) {
            $data['league'] = '';
            $data['list'][] = array(
                'id' => $info['letgoal_id'],
                'company_id' => emptyToStr($info['company_qt_id']),
                'company_name' => emptyToStr($info['company_name']),
                //亚赔
                'origin_odds' => array(
                    'win' => emptyToStr($info['letgoal_first_up_odds']),
                    'lose' => emptyToStr($info['letgoal_first_down_odds']),
                    'condition' => emptyToStr($info['letgoal_name_first']),
                ),
                'current_odds' => array(
                    'win' => emptyToStr($info['letgoal_up_odds']),
                    'lose' => emptyToStr($info['letgoal_down_odds']),
                    'condition' => emptyToStr($info['letgoal_name']),
                ),
            );
        }
        return $data;
    }

    protected function formatEuropeOdds($standard_list)
    {
        $data = array();
        $data['average_origin_odds']['win'] = emptyToStr($standard_list['standard_first_home_win_avg']);
        $data['average_origin_odds']['equal'] = emptyToStr($standard_list['standard_first_standoff_avg']);
        $data['average_origin_odds']['lose'] = emptyToStr($standard_list['standard_first_guest_win_avg']);

        $data['average_current_odds']['win'] = emptyToStr($standard_list['standard_home_win_avg']);
        $data['average_current_odds']['equal'] = emptyToStr($standard_list['standard_standoff_avg']);
        $data['average_current_odds']['lose'] = emptyToStr($standard_list['standard_guest_avg']);
        $data['list'] = array();
        foreach ($standard_list['list'] as $key => $info) {
            $data['league'] = '';
            $data['list'][] = array(
                'id' => emptyToStr($info['standard_id']),
                'company_id' => emptyToStr($info['company_qt_id']),
                'company_name' => emptyToStr($info['company_name']),
                //欧赔
                'origin_odds' => array(
                    'win' => emptyToStr($info['standard_first_home_win']),
                    'equal' => emptyToStr($info['standard_first_standoff']),
                    'lose' => emptyToStr($info['standard_first_guest_win']),
                ),
                'current_odds' => array(
                    'win' => emptyToStr($info['standard_home_win']),
                    'equal' => emptyToStr($info['standard_standoff']),
                    'lose' => emptyToStr($info['standard_guest_win']),
                ),
            );
        }
        return $data;
    }

    protected function formatScheduleDetail($schedule_info)
    {
        $data = array();
        $data['home'] = emptyToStr($schedule_info['schedule_home_team']);
        $data['home_id'] = emptyToStr($schedule_info['schedule_home_team_id']);
        $data['home_rank'] = emptyToStr($schedule_info['schedule_home_order']);
        $data['home_logo'] = emptyToStr($schedule_info['schedule_home_team_flag']);
        $data['guest'] = emptyToStr($schedule_info['schedule_guest_team']);
        $data['guest_id'] = emptyToStr($schedule_info['schedule_guest_team_id']);
        $data['guest_rank'] = emptyToStr($schedule_info['schedule_guest_order']);
        $data['guest_logo'] = emptyToStr($schedule_info['schedule_guest_team_flag']);
        $data['schedule_progress'] = emptyToStr($this->calcMinute($schedule_info['schedule_match_state'], $schedule_info['schedule_match_time2']));
        $data['first_half_begin_time'] = strtotime($schedule_info['schedule_match_time']);
        $data['second_half_begin_time'] = strtotime($schedule_info['schedule_match_time2']);
        $data['match_status_description'] = emptyToStr($this->buildScheduleStatusDesc($schedule_info['schedule_match_state']));

        $score_string = $schedule_info['schedule_home_score'] . ':' . $schedule_info['schedule_guest_score'];
        $score_half_string = $schedule_info['schedule_home_half_score'] . ':' . $schedule_info['schedule_guest_half_score'];

        $data['half_score'] = emptyToStr($this->buildHalfScoreString($schedule_info['schedule_match_state'], $score_half_string));
        $data['match_status'] = emptyToStr($this->buildScheduleStatus($schedule_info['schedule_match_state']));
        $data['current_score'] = emptyToStr($this->buildScoreString($schedule_info['schedule_match_state'], $score_string));
        $data['match_duration'] = emptyToStr($this->calcMinute($schedule_info['schedule_match_state'], $schedule_info['schedule_match_time2']));
        $data['league'] = emptyToStr($schedule_info['matches_short_simple_name']);
        $data['third_party_schedule_id'] = empty($schedule_info['schedule_qt_id']) ? 0 : $schedule_info['schedule_qt_id'];
        return $data;
    }

    protected function buildScheduleStatusDesc($match_status)
    {
        $schedule_status = C('JC_SCHEDULE_STATUS_LIST');
        return $schedule_status[$match_status];
    }

    protected function addFirstToScheduleIds($schedule_ids){
        foreach($schedule_ids as $k => $schedule_id){
            $schedule_ids[$k] = $this->addFirstToScheduleId($schedule_id);
        }
        return $schedule_ids;
    }

    protected function addFirstToScheduleId($schedule_id){
        $schedule_id = '201'.$schedule_id;
        return $schedule_id;
    }

    protected function reduceFirstToScheduleId($schedule_id){
        $schedule_id = substr($schedule_id,3);
        return $schedule_id;
    }


    protected function formatGameTechStatsList($game_tech_info){
        if(!$game_tech_info){
            return array();
        }

        $possession['name'] = '控球率';
        $possession['home_count'] = $game_tech_info['home_possession']? $game_tech_info['home_possession']:'0%';
        $possession['guest_count'] = $game_tech_info['guest_possession']? $game_tech_info['guest_possession']:'0%';
        $possession['home_rate'] = $game_tech_info['home_possession'] ? ((float)$game_tech_info['home_possession'])/100 :0;
        $possession['guest_rate'] = $game_tech_info['guest_possession'] ? ((float)$game_tech_info['guest_possession'])/100:0;

        $shots['name'] = '射门次数';
        $total_shots = $game_tech_info['home_shots'] + $game_tech_info['guest_shots'];
        $shots['home_count'] = $game_tech_info['home_shots'] ? $game_tech_info['home_shots']:0;
        $shots['guest_count'] = $game_tech_info['guest_shots']? $game_tech_info['guest_shots']:0;
        $shots['home_rate'] = $game_tech_info['home_shots']? $this->formatFloatNumber($game_tech_info['home_shots']/$total_shots):0;
        $shots['guest_rate'] = $game_tech_info['guest_shots']? $this->formatFloatNumber($game_tech_info['guest_shots']/$total_shots):0;

        $shots_on_goal['name'] = '射正次数';
        $total_shots_on_goal = $game_tech_info['home_shots_on_goal'] + $game_tech_info['guest_shots_on_goal'];
        $shots_on_goal['home_count'] = $game_tech_info['home_shots_on_goal']? $game_tech_info['home_shots_on_goal']:0;
        $shots_on_goal['guest_count'] = $game_tech_info['guest_shots_on_goal']? $game_tech_info['guest_shots_on_goal']:0;
        $shots_on_goal['home_rate'] = $game_tech_info['home_shots_on_goal']? $this->formatFloatNumber($game_tech_info['home_shots_on_goal']/$total_shots_on_goal):0;
        $shots_on_goal['guest_rate'] = $game_tech_info['guest_shots_on_goal']? $this->formatFloatNumber($game_tech_info['guest_shots_on_goal']/$total_shots_on_goal):0;

        $red_cards['name'] = '红牌';
        $total_red_cards = $game_tech_info['home_red_cards'] + $game_tech_info['guest_red_cards'];
        $red_cards['home_count'] = $game_tech_info['home_red_cards']? $game_tech_info['home_red_cards']:0;
        $red_cards['guest_count'] = $game_tech_info['guest_red_cards']? $game_tech_info['guest_red_cards']:0;
        $red_cards['home_rate'] = $game_tech_info['home_red_cards']? $this->formatFloatNumber($game_tech_info['home_red_cards']/$total_red_cards):0;
        $red_cards['guest_rate'] = $game_tech_info['guest_red_cards']? $this->formatFloatNumber($game_tech_info['guest_red_cards']/$total_red_cards):0;

        $yellow_cards['name'] = '黄牌';
        $total_yellow_cards = $game_tech_info['home_yellow_cards'] + $game_tech_info['guest_yellow_cards'];
        $yellow_cards['home_count'] = $game_tech_info['home_yellow_cards']? $game_tech_info['home_yellow_cards']:0;
        $yellow_cards['guest_count'] = $game_tech_info['guest_yellow_cards']? $game_tech_info['guest_yellow_cards']:0;
        $yellow_cards['home_rate'] = $game_tech_info['home_yellow_cards']? $this->formatFloatNumber($game_tech_info['home_yellow_cards']/$total_yellow_cards):0;
        $yellow_cards['guest_rate'] = $game_tech_info['guest_yellow_cards']? $this->formatFloatNumber($game_tech_info['guest_yellow_cards']/$total_yellow_cards):0;

        $offsides['name'] = '越位';
        $total_offsides = $game_tech_info['home_offsides'] + $game_tech_info['guest_offsides'];
        $offsides['home_count'] = $game_tech_info['home_offsides']? $game_tech_info['home_offsides']:0;
        $offsides['guest_count'] = $game_tech_info['guest_offsides']? $game_tech_info['guest_offsides']:0;
        $offsides['home_rate'] = $game_tech_info['home_offsides']? $this->formatFloatNumber($game_tech_info['home_offsides']/$total_offsides):0;
        $offsides['guest_rate'] = $game_tech_info['guest_offsides']? $this->formatFloatNumber($game_tech_info['guest_offsides']/$total_offsides):0;

        $corner_kicks['name'] = '角球';
        $total_corner_kicks = $game_tech_info['home_corner_kicks'] + $game_tech_info['guest_corner_kicks'];
        $corner_kicks['home_count'] = $game_tech_info['home_corner_kicks']? $game_tech_info['home_corner_kicks']:0;
        $corner_kicks['guest_count'] = $game_tech_info['guest_corner_kicks']? $game_tech_info['guest_corner_kicks']:0;
        $corner_kicks['home_rate'] = $game_tech_info['home_corner_kicks']? $this->formatFloatNumber($game_tech_info['home_corner_kicks']/$total_corner_kicks):0;
        $corner_kicks['guest_rate'] = $game_tech_info['guest_corner_kicks']? $this->formatFloatNumber($game_tech_info['guest_corner_kicks']/$total_corner_kicks):0;

        return array(
            $possession,$shots,$shots_on_goal,$red_cards,$yellow_cards,$offsides,$corner_kicks
        );
    }

    protected function formatFloatNumber($number){
        return number_format($number,2);
    }

    protected function formatAsiaOddChangeList($asia_odds_list){
        $format_asia_odds_list = array();
        foreach($asia_odds_list as $odd_info){
            $format_asia_odds_list[] = $this->formatAsiaOddChangeCompanyInfo($odd_info);
        }
        return $format_asia_odds_list;
    }

    protected function formatAsiaOddChangeCompanyInfo($odd_info){
        $format_odd_info['time'] = strtotime($odd_info['ld_createtime']);
        $format_odd_info['current_odds']['win'] = $odd_info['ld_up_odds'];
        $format_odd_info['current_odds']['lose'] = $odd_info['ld_down_odds'];
        $format_odd_info['current_odds']['condition_bak'] = $odd_info['ld_goal'];
        $format_odd_info['current_odds']['condition'] = $odd_info['letgoal_name'];
        return $format_odd_info;
    }

    protected function formatEuropeOddChangeList($standard_odds_list){
        $format_europe_odds_list = array();
        foreach($standard_odds_list as $odd_info){
            $format_europe_odds_list[] = $this->formatEuropeOddChangeCompanyInfo($odd_info);
        }
        return $format_europe_odds_list;
    }

    protected function formatEuropeOddChangeCompanyInfo($odd_info){
        $format_odd_info['time'] = strtotime($odd_info['sd_createtime']);
        $format_odd_info['current_odds']['win'] = $odd_info['sd_home_win'];
        $format_odd_info['current_odds']['equal'] = $odd_info['sd_standoff'];
        $format_odd_info['current_odds']['lose'] = $odd_info['sd_guest_win'];
        return $format_odd_info;
    }

    protected function queryScheduleOddsList($date_list){
        return D('JcSchedule')->queryScheduleOddsListByDate($date_list, TIGER_LOTTERY_ID_OF_JZ_HHTZ);
    }

    protected function getRequestStatusForGetScheduleList($type){
        if ($type == C('API_SCHEDULE_STATUS_OF_NOBEGIN')) {
            return C('JC_SCHEDULE_STATUS_LIST_NOBEGIN');
        } elseif ($type == C('API_SCHEDULE_STATUS_OF_PLAYING')) {
            return C('JC_SCHEDULE_STATUS_LIST_PLAYING');
        } elseif ($type == C('API_SCHEDULE_STATUS_OF_OVER')) {
            return C('JC_SCHEDULE_STATUS_LIST_OVER');
        }
    }

    protected function requestJcDataFromBaseDataService($request_method,$request_data = array()){
        $requestURL = C('JC_REQUEST_URL') . $request_method;
        $result = requestByCurl($requestURL, $request_data);
        $result = json_decode($result, true);
        return $result['data'];
    }

    protected function formateScheduleByTime($schedule_data)
    {
        $data = array();
        foreach($schedule_data as $k => $v) {
            $data[$k] = $v['first_half_begin_time'];
        }
        array_multisort($data,SORT_DESC,$schedule_data);
        return $schedule_data;
    }



    protected function getImportantData($technic_info,$team_type = 'home'){
        $important_data = array();
        $important_data['red_card'] = emptyToStr($technic_info[$team_type.'_red_cards']);
        $important_data['yellow_card'] = emptyToStr($technic_info[$team_type.'_yellow_cards']);
        $important_data['ball_possession'] = emptyToStr($technic_info[$team_type.'_possession']);
        $important_data['shoot'] = emptyToStr($technic_info[$team_type.'_shots']);
        $important_data['shots_on_goal'] = emptyToStr($technic_info[$team_type.'_shots_on_goal']);
        return $important_data;
    }

    protected function buildScoreString($game_status, $schedule_score)
    {
        $score_string = '';
        if ($game_status === C('SCHEDULE_STATUS_OF_NO_BEGIN')) {
            $score_string = '0:0';
        } else {
            $score_string = $schedule_score;
        }
        return $score_string;
    }

    protected function buildHalfScoreString($game_status, $schedule_score)
    {
        $score_string = '';
        // 比赛状态 0:未开,1:上半场,2:中场,3:下半场,4,加时，-11:待定,-12:腰斩,-13:中断,-14:推迟,-1:完场，-10取消
        if ($game_status == C('SCHEDULE_STATUS_OF_NO_BEGIN') || $game_status == C('SCHEDULE_STATUS_OF_FIRST_HALF')) {
            $score_string = '';
        } else {
            $score_string = $schedule_score;
        }
        return $score_string;
    }

    protected function calcMinute($game_status, $math_time)
    {
        $min = '';
        if ($game_status == C('SCHEDULE_STATUS_OF_FIRST_HALF')) {
            $min = ceil((time() - strtotime($math_time)) / 60);
            //$min .= "'";
            if ($min > self::SCHEDULE_HALF_TIME) {
                $min = '45+';
            }
        } elseif ($game_status == C('SCHEDULE_STATUS_OF_MIDFIED')) {
            $min = "中场";
        } elseif ($game_status == C('SCHEDULE_STATUS_OF_SECOND_HALF')) {
            $min = ceil((time() - strtotime($math_time)) / 60);
            if ($min > self::SCHEDULE_HALF_TIME) {
                $min = '90+';
            } else {
                $min = self::SCHEDULE_HALF_TIME + intval($min);
                //$min .= "'";
            }
        } elseif ($game_status == C('SCHEDULE_STATUS_OF_OVER_TIME')) {
            $min = C('SCHEDULE_OVER_TIME_STR');
        }
        return $min;
    }

    protected function buildScheduleStatus($game_status)
    {
        // 比赛状态 0:未开,1:上半场,2:中场,3:下半场,4,加时，-11:待定,-12:腰斩,-13:中断,-14:推迟,-1:完场，-10取消
        if ($game_status == C('SCHEDULE_STATUS_OF_END')) {
            return C('API_SCHEDULE_STATUS_OF_OVER');
        }

        $nobegin = array(
            C('SCHEDULE_STATUS_OF_UNDETERMINED'),
            C('SCHEDULE_STATUS_OF_SCRAPPED'),
            C('SCHEDULE_STATUS_OF_BREAK'),
            C('SCHEDULE_STATUS_OF_DELAY'),
            C('SCHEDULE_STATUS_OF_NO_BEGIN'),
        );

        if (in_array($game_status,$nobegin)) {
            return C('API_SCHEDULE_STATUS_OF_NOBEGIN');
        }
        $status_map = array(C('SCHEDULE_STATUS_OF_FIRST_HALF'), C('SCHEDULE_STATUS_OF_MIDFIED'), C('SCHEDULE_STATUS_OF_SECOND_HALF'), C('SCHEDULE_STATUS_OF_OVER_TIME'));
        if (in_array($game_status, $status_map)) {
            return C('API_SCHEDULE_STATUS_OF_PLAYING');
        }
        return C('API_SCHEDULE_STATUS_OF_OVER');
    }

}