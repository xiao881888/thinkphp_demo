<?php
namespace H5\Controller;

use Think\Exception;

class LotteryController extends BaseController{

    const GAOPING_DELAY_TIME = 3;

    protected static $basedata_url = 'http://basedata.tigercai.com/Api';

    public function getLotteryList()
    {
        $data = array();
        $lotterys      = self::getModelInstance('Lottery')->getLottery();
        $lotterys      = reindexArr($lotterys, 'lottery_id');

        foreach ($lotterys as $id => $lottery) {
            if ($id > 100) {
                unset($lotterys[$id]);
            }
        }

        $lottery_ids = array_keys($lotterys);
        $current_issues = self::getModelInstance('Issue')->getCurrentIssueInfo($lottery_ids);
        $next_issues    = self::getModelInstance('Issue')->getNextIssueInfos($lottery_ids);

        $plus_award_lottery_list = $this->_queryPlusAwardLotteryList();
        foreach ($lotterys as $lottery) {
            $lottery_id = $lottery['lottery_id'];
            $issue = $current_issues[$lottery_id];
            $next_issue = $next_issues[$lottery_id];
            $current_issue = $current_issues[$lottery_id];

            $data[] = array(
                'lottery_id' 	=> $lottery_id,
                'play_type_list' => explode(',',$lottery['support_play_types']),
                'lottery_name' 	=> $lottery['lottery_name'],
                'lottery_image' => $lottery['lottery_image'],
                'id' 			=> (int)$issue['issue_id'],
                'issue_no' 		=> emptyToStr($issue['issue_no']),
                'winnings_pool' => emptyToStr($issue['issue_winnings_pool']),
                'end_time' 		=> strtotime($issue['issue_end_time']) - $lottery['lottery_ahead_endtime'],
                'slogon' 		=> emptyToStr($lottery['lottery_slogon']),   // TODO 彩种描述综合
                'short_slogon'  => emptyToStr($lottery['lottery_short_slogon']),
                'priority'      => intval($lottery['lottery_short_slogon_priority']),
                'start_time'    => $current_issue['issue_start_time'] ? strtotime($current_issue['issue_start_time']) : 0,
                'next_issue_start_time' => $next_issue ? strtotime($next_issue) + self::GAOPING_DELAY_TIME : 0,
                'status' => (int)$lottery['lottery_status'],
                'is_plus_award' => intval($plus_award_lottery_list[$lottery_id])
            );

        }

        $this->response(array(
            'list' => $data,
            'server_time' => time(),
        ));
    }

    public function getCurrentIssue()
    {
        $lottery_id = I('get.lottery_id',false);
        if (!$lottery_id){
            $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
        }
        $issue_id = self::getModelInstance('Issue')->getCurrentIssueId($lottery_id);
        $issue_info  = self::getModelInstance('IssueView')->getIssueInfo($issue_id);
        $next_issues = self::getModelInstance('Issue')->getNextIssueInfo($lottery_id);
        if (!$issue_info || !$next_issues){
            $this->responseError(RESPONSE_ERROR_ISSUE_NOT_EXIST);
        }
        $issue_info['id'] = $issue_info['issue_id'];
        $issue_info['no'] = $issue_info['issue_no'];
        $issue_info['next_issue_start_time'] = strtotime($next_issues['issue_start_time']) + self::GAOPING_DELAY_TIME;
        $lottery = self::getModelInstance('Lottery')->where(['lottery_id' => $lottery_id])->find();
        $issue_info['play_type_list'] = explode(',',$lottery['support_play_types']);
        $this->response(array(
            'issue' => $issue_info,
            'server_time' => time(),
        ));
    }

    public function getPrizeIssueInfo()
    {
        $lottery_id = I('get.lottery_id',false);
        $offset = I('get.offset',0);
        $limit = I('get.limit',10);
        $begin_date = I('get.begin',false);
        $end_date = I('get.end',false);

        if ($lottery_id){
            if (isJc($lottery_id)){
                if (!$begin_date or !$end_date){
                    $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
                }
                $schedules  = self::getModelInstance('JcSchedule')->getSchedulesByDate($lottery_id, $begin_date, $end_date);
                $lottery_info = D('Lottery')->where(['lottery_id' => $lottery_id])->find();
                $data['result'] = $this->_groupingSchedules($lottery_info,$schedules);
                $this->response($data);
            }else{
                $model = isJc($lottery_id) ? self::getModelInstance('JcScheduleView') : self::getModelInstance('IssueView');
                if($lottery_id==1 || $lottery_id==3 || $lottery_id==20 || $lottery_id==21){
                    $model = self::getModelInstance('CollectIssueView');
                }
                $issueInfos = $model->getPrizeIssueInfo($lottery_id, $offset, $limit);
            }

        }else{
            $issueIds 	 = self::getModelInstance('LatestIssuePrize')->getIssuePrizeIds();
            $issue 		 = self::getModelInstance('IssueView')->getPrizeIssueInfo($issueIds);
            $issue_results = $this->_convertIssueLotteryId($issue);

            $scheduleInfos = self::getModelInstance('LatestSchedulePrize')->getSchedulePrizeIds();
            $scheduleData = array();
            foreach ($scheduleInfos as $scheduleInfo) {
                $latestSchedule = self::getModelInstance('JcScheduleView')->getLatestSchedule($scheduleInfo['schedule_day'],
                    $scheduleInfo['schedule_week'], $scheduleInfo['schedule_round_no'], $scheduleInfo['lottery_id']);
                if (!$latestSchedule) {
                    continue;
                }
                $scheduleData[] = $latestSchedule;
            }

            $schedule_results  = $this->_convertIssueLotteryId($scheduleData,$api);
            $issueInfos = array_merge($schedule_results, $issue_results);
        }

        $result = $this->_convertIssueInfo($issueInfos,$lottery_id);
        $this->response(['list' => $result]);
    }

    public function getJcList()
    {
        $lottery_id = I('get.lottery_id',0,intval);
        $play_type  = I('get.play_type',0,intval);

        if (!$lottery_id or !$play_type){
            $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
        }

        $lottery_info = D('Lottery')->getLotteryInfo($lottery_id);
        $schedules = self::getModelInstance('JcSchedule')->getScheduleList($lottery_id, $play_type);

        $data['groups'] = array();
        $i = 1;
        $vs_data_list_by_date = array();
        foreach ($schedules as $schedule) {
            $schedule_date = $schedule['schedule_day'];
            if(empty($vs_data_list_by_date[$schedule_date])){
                $vs_data_list_by_date[$schedule_date] = self::getModelInstance('VsData')->queryVsDataListByDate($schedule_date);
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
                'home_rank' 			=> empty($vs_data_info['schedule_home_rank'])? 0:$vs_data_info['schedule_home_rank'],
                'guest_rank' 			=> empty($vs_data_info['schedule_guest_rank'])? 0:$vs_data_info['schedule_guest_rank'],
                'betting_win_percent'	=> '',
                'betting_equal_percent'	=> '',
                'betting_lose_percent'	=> '',
                // 'average_equal_odds'	=> empty($average_info['v1'])?0:doubleval($average_info['v1']),
                // 'average_win_odds'	 	=> empty($average_info['v3'])?0:doubleval($average_info['v3']),
                // 'average_lose_odds'		=> empty($average_info['v0'])?0:doubleval($average_info['v0']),
                'average_equal_odds'    => empty($average_info['v1'])?0:(string)$average_info['v1'],
                'average_win_odds'      => empty($average_info['v3'])?0:(string)$average_info['v3'],
                'average_lose_odds'     => empty($average_info['v0'])?0:(string)$average_info['v0'],
                //'detail_url'		=> empty($vs_data_info['vs_detail_url'])?'':$this->_buildDetailUrl($vs_data_info['vs_detail_url']),
            );
        }
        $data['groups'] = array_values($data['groups']);
        $data['lottery_id'] = $lottery_id;
        $this->response($data);
    }

    public function getCtzqList()
    {
        $lottery_id = I('get.lottery_id',false,intval);

        if (!$lottery_id){
            $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
        }

        $lottery_info = D('Lottery')->getLotteryInfo($lottery_id);
        $sfc_issue_list = self::getModelInstance('Issue')->queryIssueListByLotteryId($lottery_id, $lottery_info['lottery_ahead_endtime']);
        $response['issues'] = array();

        $vs_data_list_by_issue_no = array();
        foreach($sfc_issue_list as $sfc_issue_info){
            $end_time = strtotime($sfc_issue_info['issue_end_time']) - $lottery_info['lottery_ahead_endtime'];
            $issue_no = $sfc_issue_info['issue_no'];
            if(empty($vs_data_list_by_issue_no[$issue_no])){
                $vs_data_list_by_issue_no[$issue_no] = self::getModelInstance('VsData')->queryVsDataListByDate($issue_no);
            }
            $response_sfc_issue_item['no'] = $sfc_issue_info['issue_no'];
            $response_sfc_issue_item['start_time'] = strtotime($sfc_issue_info['issue_start_time']);
            $response_sfc_issue_item['end_time'] = $end_time;
            $response_sfc_issue_item['sfc_issue_status'] = $this->_getSfcIssueStatus($sfc_issue_info,$lottery_info['lottery_ahead_endtime']);
            $response_sfc_issue_item['schedules'] = $this->_queryScheduleListByIssueNo($sfc_issue_info['issue_no'], $vs_data_list_by_issue_no[$issue_no]);
            $response['issues'][] = $response_sfc_issue_item;
        }
        $this->response(['result' => $response]);

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
        $schedule_list = self::getModelInstance('ZcsfcSchedule')->queryScheduleListByIssueNo($issue_no);
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

    private function _queryPlusAwardLotteryList(){
        $time = date("Y-m-d H:i:s");
        $map['pac_start_time'] = array('elt',$time);
        $map['pac_end_time'] = array('egt',$time);
        $plus_award_config_list = D('PlusAwardConfig')->where($map)->select();
        foreach($plus_award_config_list as $plus_award_config_info){
            $plus_award_lottery_list[$plus_award_config_info['lottery_id']] = 1;
            if(isJCLottery($plus_award_config_info['lottery_id'])){
                $jc_lottery_id = substr($plus_award_config_info['lottery_id'],0,1);
                $plus_award_lottery_list[$jc_lottery_id] = 1;
                $jc_lottery_id = 0;
            }
        }
        return $plus_award_lottery_list;
    }

    private function _convertIssueInfo($issueInfos,$lottery_id = 0) {
        $result = array();
        $end_key = count($issueInfos) - 1;

        foreach ( $issueInfos as $key => $issueInfo ) {
            if (!$issueInfo['test_num']) {
                unset($issueInfo['test_num']);
            }
            if($issueInfo['lottery_id'] == TIGER_LOTTERY_ID_OF_SFC_14 || $issueInfo['lottery_id'] == TIGER_LOTTERY_ID_OF_SFC_9){
                $issueInfo['lottery_id'] = TIGER_LOTTERY_ID_OF_SFC_14;
                $fourteen_ranking = $this->_getWinningsSchema($issueInfo['issue_winnings_schema']);
                $nine_issueInfo =  self::getModelInstance('CollectIssueView')->where([
                    'issue_no' => $issueInfo['issue_no'],
                    'lottery_id' => TIGER_LOTTERY_ID_OF_SFC_9
                ])->find();
                $nine_ranking = $this->_getWinningsSchema($nine_issueInfo['issue_winnings_schema']);
                foreach($nine_ranking as $key => $ranking_info){
                    $nine_ranking[$key]['winnings_category'] = '任选九';
                }
                $issueInfo['fourteen_ranking'] = $fourteen_ranking;
                $issueInfo['nine_ranking'] = $nine_ranking;
                $issueInfo['nine_sales'] = emptyToStr($nine_issueInfo['issue_sell_amount']);
                $issueInfo['fourteen_sales'] = emptyToStr($issueInfo['issue_sell_amount']);
            }

            if ($key == $end_key and in_array($issueInfo['lottery_id'],C('LOTTERY_CATEGORY.SZC')) and $lottery_id){
//                $base_response = $this->_getIssueInfoFromBaseData(1002,$issueInfo['lottery_id'],$issueInfo['issue_no']);
//                $issueInfo['miss_count'] = (string)$base_response['data']['history_prize_number'];
            }else{
                $issueInfo['miss_count'] = '';
            }

            if ($issueInfo['lottery_id'] == TIGER_LOTTERY_ID_OF_JS_K3){
                continue;
            }

            $result[] = $this->_fillScheduleInfo($issueInfo['schedule_odds'], $issueInfo);

        }

        return $result;
    }

    private function _fillScheduleInfo($scheduleOdds, $schedule, $lottery_id = 0){
        if ($scheduleOdds) {
            $scheduleOdds = json_decode($scheduleOdds,true);
            if(isJclq($lottery_id)){
                $schedule['base_point'] = (float)$scheduleOdds['704']['basePoint'];
                $schedule['let_point'] = (float)$scheduleOdds['702']['letPoint'];
            }else{
                if ($scheduleOdds['basePoint']) {
                    $schedule['base_point'] = (float)$scheduleOdds['basePoint'];
                } elseif ($scheduleOdds['letPoint']) {
                    $schedule['let_point']  = $scheduleOdds['letPoint'];
                }
            }

        }
        return $schedule;
    }

    private function _getIssueInfoFromBaseData($act,$lottery_id,$issue_no)
    {
        if ($lottery_id == TIGER_LOTTERY_ID_OF_SSQ){
            $issue_no = '20'.$issue_no ;
        }

        $request = array(
            'act' => 1002,
            'data' => array(
                'lottery_id' => $lottery_id,
                'issue_no' => $issue_no,
            ),
        );
        $response = postByCurl(self::$basedata_url,json_encode($request,true));
        return json_decode($response,true);
    }

    private function _convertIssueLotteryId($datas) {
        $issueInfos = array();
        foreach ($datas as $data) {
            if (in_array($data['lottery_id'], C('JCZQ'))) {
//     			$data['lottery_id'] = C('JC.JCZQ');
                $data['lottery_id'] = $data['lottery_id'];
                $data['lottery_name'] = '竞彩足球';
            } elseif (in_array($data['lottery_id'], C('JCLQ'))) {
//     			$data['lottery_id'] = C('JC.JCLQ');
                $data['lottery_id'] = $data['lottery_id'];
                $data['lottery_name'] = '竞彩篮球';
            } elseif($data['lottery_id'] == TIGER_LOTTERY_ID_OF_SFC_9){
                continue;
            } elseif($data['lottery_id'] == TIGER_LOTTERY_ID_OF_SFC_14){
                $data['lottery_name'] = '胜负彩（任选九）';
            }
            $issueInfos[] = $data;
        }
        return $issueInfos;
    }

    private function _buildEmptyHistory(){
        return array('win'=>0,'equal'=>0,'lose'=>0,'games_count'=>0);
    }

    private function _buildEmptyRecord(){
        return array('home'=>$this->_buildEmptyHistory(),'guest'=>$this->_buildEmptyHistory());
    }

    private function _groupingSchedules($lottery_info,$schedules) {
        $idx = 0;
        $data['groups'] = array();
        $data['status'] = (int)$lottery_info['lottery_status'];

        foreach ($schedules as $schedule) {
            $idx++;
            $groupDate = date('Y-m-d', strtotime($schedule['schedule_prize_time']));
            $data['groups'][$groupDate]['id']	= $idx;
            $betDate = ( strtotime($schedule['schedule_start_time']) >0 ? strtotime($schedule['schedule_start_time']) : 0 );
            $data['groups'][$groupDate]['name']	= $groupDate;
            $scheduleDetail = array(
                'id'		=> $schedule['schedule_id'],
                'round_no'	=> $schedule['schedule_round_no'],
                'home'		=> $schedule['schedule_home_team'],
                'guest'		=> $schedule['schedule_guest_team'],
                'league'	=> $schedule['schedule_league_matches'],
                'end_time'	=> strtotime($schedule['schedule_end_time']),
                'betting_date'	=> $betDate,
                'score' => array(	'half'	=> $schedule['schedule_half_score'],
                    'final'	=> $schedule['schedule_final_score']), );
            $data['groups'][$groupDate]['schedules'][] = $this->_fillScheduleInfo($schedule['schedule_odds'], $scheduleDetail,$schedule['lottery_id']);
        }
        $data['groups'] = array_values($data['groups']);
        return $data;
    }

    private function _getWinningsSchema($winnings_schema) {
        $winnings_schema = json_decode($winnings_schema,true);
        $winnings_ranking = array();
        foreach ($winnings_schema as $info) {
            $winnings_ranking[] = array(
                'winnings_category' 	=> $info['name'],
                'winnings_stake_count' 	=> $info['count'],
                'per_winnings_bonus' 	=> $info['money'],
            );
        }
        return $winnings_ranking;
    }
}