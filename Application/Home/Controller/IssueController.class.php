<?php
namespace Home\Controller;
use Think\Controller;
/**
 * @date 2014-11-21
 * @author tww <merry2014@vip.qq.com>
 */
class IssueController extends Controller{
    const GAOPING_DELAY_TIME = 3;
	protected static $basedata_url = 'http://basedata.tigercai.com/Api';
	
    public function getCurrentIssue($api) {
        $issueId    = D('Issue')->getCurrentIssueId($api->lottery_id);
        $issueInfo  = D('IssueView')->getIssueInfo($issueId);
        $nextIssues = D('Issue')->getNextIssueInfo($api->lottery_id);
        if(!$issueInfo || !$nextIssues ) {
            return $this->_issueNoExistInfo();
        }
        $lotteryInfo = D('Lottery')->getLotteryInfo($api->lottery_id);
        $issueInfo['play_type_list'] = explode(',',$lotteryInfo['support_play_types']);
        
        $issueInfo['id'] = $issueInfo['issue_id'];
        $issueInfo['no'] = $issueInfo['issue_no'];
        $issueInfo['next_issue_start_time'] = strtotime($nextIssues['issue_start_time']) + self::GAOPING_DELAY_TIME;
        $issueInfo['issue_count'] = $lotteryInfo['lottery_issue_count'];

        return array(   'result' => array('issue'=>$issueInfo, 'server_time'=>time()),
            'code'   => C('ERROR_CODE.SUCCESS'));
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

    public function lotteryList($api) {
        $data = array();
        $lotterys      = D('Lottery')->getLottery();
        $lotterys      = reindexArr($lotterys, 'lottery_id');
        //不同操作系统不同版本支持的彩种不同，需要做分版本显示
        $iphone_lottery_show_config = array(
            '1' => array(6, 601, 602, 603, 604, 605, 606),
            '2' => array(6, 2, 4, 8, 1, 3, 7),
            '3' => array(6, 2, 4, 7, 8, 3, 1),
            '4' => array(6, 7, 2, 4, 8, 1, 3, 18),
            '5' => array(6, 7, 2, 4, 8, 5, 1, 3, 18, 19),
            '6' => array(6, 7, 2, 4, 8, 5, 1, 3, 18, 19),
            '7' => array(6, 7, 2, 4, 8, 5, 1, 3, 18, 19),
            '8' => array(6, 7, 20, 21, 2, 4, 8, 5, 1, 3, 18, 19),
            '9' => array(6, 7, 20, 21, 2, 4, 8, 5, 1, 3, 18, 19, 22),
        );
        $android_lottery_show_config = array(
            '1' => array(601, 602, 603, 604, 605, 606, 1, 3),
            '2' => array(6, 2, 4, 8, 1, 3),
            '3' => array(6, 2, 4, 7, 8, 3),
            '4' => array(6, 7, 2, 4, 8, 1, 3, 18),
            '5' => array(6, 7, 2, 4, 8, 5, 1, 3, 18, 19),
            '6' => array(6, 7, 2, 4, 8, 5, 1, 3, 18, 19),
            '7' => array(6, 7, 2, 4, 8, 5, 1, 3, 18, 19),
            '8' => array(6, 7, 20, 21, 2, 4, 8, 5, 1, 3, 18, 19),
            '9' => array(6, 7, 20, 21, 2, 4, 8, 5, 1, 3, 18, 19, 22),
        );
		$audit_version = D('IssueSwitch')->getSwitchOffList();

        if ($api->os == 1 && array_key_exists($api->channel_id, $audit_version) && $api->app_version === $audit_version[$api->channel_id] ) {
            $is_in_audit = true;
        } else {
            $is_in_audit = false;
        }
        if(get_client_ip(0,true)!='124.72.226.65'){
            // $is_in_audit = true;
        }
        // $is_in_audit = false;
        $client_lottery_show_config = $api->os == 1 ? $android_lottery_show_config : $iphone_lottery_show_config;
        if (!empty($client_lottery_show_config) && array_key_exists($api->sdk_version, $client_lottery_show_config)) {
            $client_support_lottery_id = $client_lottery_show_config[$api->sdk_version];
            foreach ($lotterys as $id => $lottery) {
                if (!in_array($id, $client_support_lottery_id)) {
                    unset($lotterys[$id]);
                }
            }
        }
        $lotteryIds    = array_keys($lotterys);
        $currentIssues = D('Issue')->getCurrentIssueInfo($lotteryIds);
        $nextIssues    = D('Issue')->getNextIssueInfos($lotteryIds);
        $plus_award_lottery_list = $this->_queryPlusAwardLotteryList();
        foreach ($lotterys as $lottery) {
            $lotteryId    = $lottery['lottery_id'];
            $issue        = $currentIssues[$lotteryId];
            $nextIssue    = $nextIssues[$lotteryId];
            $currentIssue = $currentIssues[$lotteryId];

            //if(get_client_ip(0, true)!='110.83.28.97' && $api->sdk_version!=4){
            if($api->sdk_version < 4){
                if($lottery['lottery_id']==1 || $lottery['lottery_id']==3 ){
                    continue;
                }
            }

            if(get_client_ip(0, true) != '110.83.28.97'){
                // continue;
            }
            

            $data[] = array(
                'lottery_id' 	=> $lotteryId,
                'lottery_name' 	=> $lottery['lottery_name'],
                'lottery_image' => $lottery['lottery_image'],
                'play_type_list' => explode(',',$lottery['support_play_types']),
                'id' 			=> (int)$issue['issue_id'],
                'issue_no' 		=> emptyToStr($issue['issue_no']),
                'winnings_pool' => emptyToStr($issue['issue_winnings_pool']),
                'end_time' 		=> strtotime($issue['issue_end_time']) - $lottery['lottery_ahead_endtime'],
                'slogon' 		=> emptyToStr($lottery['lottery_slogon']),   // TODO 彩种描述综合
                'short_slogon'  => emptyToStr($lottery['lottery_short_slogon']),
                'priority'      => intval($lottery['lottery_short_slogon_priority']),
                'start_time'    => $currentIssue['issue_start_time'] ? strtotime($currentIssue['issue_start_time']) : 0,
                'next_issue_start_time' => $nextIssue ? strtotime($nextIssue) + self::GAOPING_DELAY_TIME : 0,
                'status'        => $is_in_audit ? 0 : $lottery['lottery_status'] ,
                'is_plus_award' => intval($plus_award_lottery_list[$lotteryId]),
				'issue_count' => $lottery['lottery_issue_count'],
            );
        }

        return array( 
            'result' => array(
                'list' => $data,
                'server_time' => time(),
                ),
            'code' => C('ERROR_CODE.SUCCESS')
            );
    }


    public function getPrizeIssueInfo($api) {
        if ($api->lottery_id) {
            $model = ( in_array($api->lottery_id, C('JC')) ? D('JcScheduleView') : D('IssueView') );
            if($api->lottery_id==1 || $api->lottery_id==3 || $api->lottery_id==20 || $api->lottery_id==21){
                $model = D('CollectIssueView');
            }
            $issueInfos = $model->getPrizeIssueInfo($api->lottery_id, $api->offset, $api->limit);
        } else {
            $issueIds 	 = D('LatestIssuePrize')->getIssuePrizeIds();
            $issue 		 = D('IssueView')->getPrizeIssueInfo($issueIds,0,11);
            $issue_results = $this->_convertIssueLotteryId($issue,$api);

            $scheduleInfos = D('LatestSchedulePrize')->getSchedulePrizeIds();
            $scheduleData = array();
            foreach ($scheduleInfos as $scheduleInfo) {
                $latestSchedule = D('JcScheduleView')->getLatestSchedule($scheduleInfo['schedule_day'],
                    $scheduleInfo['schedule_week'], $scheduleInfo['schedule_round_no'], $scheduleInfo['lottery_id']);
                if (!$latestSchedule) {
                    continue;
                }
                if($api->sdk_version<3 && (isJclq($scheduleInfo['lottery_id']) || $scheduleInfo['lottery_id']==7)){
                    continue;
                }
                $scheduleData[] = $latestSchedule;
            }
            ApiLog('schedules:'.$api->sdk_version.'==='.print_r($scheduleData,true), 'prize');
            $schedule_results  = $this->_convertIssueLotteryId($scheduleData,$api);
            $issueInfos = array_merge($schedule_results, $issue_results);
            ApiLog('_convertIssueLotteryId :'.print_r($issueInfos,true), 'prize');
//     		$extra_prize_list = $this->_fetchCollectIssuePrizeList();
//     		if($extra_prize_list){
// 				$issueInfos = array_merge($issueInfos, $extra_prize_list);
//     		}

        }
        $result = $this->_convertIssueInfo($issueInfos,$api);
        
		/*$audit_version = D('IssueSwitch')->getSwitchOffList();
        
        if ($api->os == 1 && array_key_exists($api->channel_id, $audit_version) && $api->app_version === $audit_version[$api->channel_id] ) {
        	$is_in_audit = true;
        } else {
        	$is_in_audit = false;
        }
        // $is_in_audit = false;

        if($is_in_audit){
        	$result = array();
        }*/
        return array(   'result' => array('list'=>$result),
            'code'   => C('ERROR_CODE.SUCCESS'));
    }

    private function _fetchCollectIssuePrizeList(){
        $ssq_issue_info = D('CollectIssueView')->queryLatestPrizeInfoByLotteryId(1);
        $dlt_issue_info = D('CollectIssueView')->queryLatestPrizeInfoByLotteryId(3);
        if(!empty($ssq_issue_info)){
            $prize_list[] = $ssq_issue_info;
        }
        if(!empty($dlt_issue_info)){
            $prize_list[] = $dlt_issue_info;
        }
        return $prize_list;
    }

    public function getTmpPrizeIssueInfo($api){
        if ($api->lottery_id) {
            $issueInfos = D('IssueTmpView')->getPrizeIssueInfo($api->lottery_id, $api->offset, $api->limit);
        } else {
            $szcIds = array(1,2,3,4,5);
            $latestIssueIds = array();
            foreach ($szcIds as $lotteryId){
                $where = array();
                $where['lottery_id'] = $lotteryId;
                $result = M('IssueTmp')->where($where)->order('issue_no DESC')->find();
                $latestIssueIds[] = $result['issue_id'];
            }
            $latestIssueIds = array_filter($latestIssueIds);
            $latestIssueIds = array_unique($latestIssueIds);
            $issueInfos = D('IssueTmpView')->getPrizeIssueInfo($latestIssueIds);
        }
        return array(   'result' => array('list'=>$issueInfos),
            'code'   => C('ERROR_CODE.SUCCESS'));
    }


    public function getTmpWinningsList($api){
        $where = array();
        $where['issue_id'] = $api->issue_id;
        $issueInfo	 = M('IssueTmp')->where($where)->find();
        $lotteryInfo = D('Lottery')->getLotteryInfo($issueInfo['lottery_id']);
        $winningsRanking = $this->_getTmpWinningsRanking($api->issue_id);
        $result = array (
            'issue_id' 		=> $api->issue_id,
            'issue_no' 		=> $issueInfo['issue_no'],
            'lottery_id' 	=> $issueInfo['lottery_id'],
            'prize_time' 	=> strtotime($issueInfo['issue_prize_time']),
            'lottery_name' 	=> $lotteryInfo['lottery_name'],
            'lottery_image' => $lotteryInfo['lottery_image'],
            'start_time' 	=> strtotime($issueInfo['issue_start_time']),
            'end_time' 		=> strtotime($issueInfo['issue_end_time']) - $lotteryInfo['lottery_ahead_endtime'],
            'prize_num' 	=> $issueInfo['issue_prize_number'],
            'sell_amount' 	=> $issueInfo['issue_sell_amount'],
            'winnings_amount' 	=> $issueInfo['issue_winnings_pool'],
            'winnings_ranking' 	=> $winningsRanking,
        );
        $result = array_map('emptyToStr', $result);
        return array(  'result' => $result,
            'code'   => C('ERROR_CODE.SUCCESS'));
    }

    private function _getTmpWinningsRanking($issueId) {
        $winningsList = D('WinningsSchemeTmp')->getWinningsList($issueId);
        $winnings_ranking = array();
        foreach ($winningsList as $winning) {
            $winnings_ranking[] = array(
                'winnings_category' 	=> $winning['ws_bonus_name'],
                'winnings_stake_count' 	=> $winning['ws_winning_num'],
                'per_winnings_bonus' 	=> $winning['ws_bonus_money'],
            );
        }
        return $winnings_ranking;
    }


    public function getWinningsList($api) {
        $issueInfo	 = D('Issue')->getIssueInfo($api->issue_id);
        if($api->lottery_id==3 || $api->lottery_id==1){
            $issueInfo =  D('CollectIssueView')->getIssueInfo($api->issue_id,$api->lottery_id);
            $lotteryInfo = D('Lottery')->getLotteryInfo($issueInfo['lottery_id']);

            $winning_schema = json_decode($issueInfo['issue_winnings_schema'],true);
            $first_prize['winnings_category'] = '一等奖';
            $first_prize['winnings_stake_count'] = $winning_schema['first_prize_count'];
            $first_prize['per_winnings_bonus'] = $winning_schema['first_prize_bonus'];
            $second_prize['winnings_category'] = '二等奖';
            $second_prize['winnings_stake_count'] = $winning_schema['second_prize_count'];
            $second_prize['per_winnings_bonus'] = $winning_schema['second_prize_bonus'];
            $winnings_ranking = array($first_prize,$second_prize);

            $result = array (
                'issue_id' 		=> $api->issue_id,
                'issue_no' 		=> $issueInfo['issue_no'],
                'lottery_id' 	=> $issueInfo['lottery_id'],
                'prize_time' 	=> $issueInfo['prize_time'],
                'lottery_name' 	=> $lotteryInfo['lottery_name'],
                'lottery_image' => $lotteryInfo['lottery_image'],
                'start_time' 	=> $issueInfo['start_time'],
                'end_time' 		=> $issueInfo['end_time'],
                'prize_num' 	=> $issueInfo['prize_num'],
                'sell_amount' 	=> $issueInfo['issue_sell_amount'],
                'winnings_amount' 	=> $issueInfo['winnings_pool'], //奖池
                'winnings_ranking' 	=> $winnings_ranking,
            );
        }elseif(isZcsfc($api->lottery_id)){

            $fourteen_issue_info =  D('CollectIssueView')->getIssueInfo($api->issue_id,$api->lottery_id);
            $lotteryInfo = D('Lottery')->getLotteryInfo($api->lottery_id);
            $fourteen_ranking = $this->_getWinningsSchema($fourteen_issue_info['issue_winnings_schema']);

            $nine_issue_id = D('CollectIssue')->where(array('issue_no'=>$fourteen_issue_info['issue_no'],'lottery_id'=>TIGER_LOTTERY_ID_OF_SFC_9))->getField('issue_id');
            $nine_issueInfo =  D('CollectIssueView')->getIssueInfo($nine_issue_id,TIGER_LOTTERY_ID_OF_SFC_9);
            $nine_ranking = $this->_getWinningsSchema($nine_issueInfo['issue_winnings_schema']);
            foreach($nine_ranking as $key => $ranking_info){
                $nine_ranking[$key]['winnings_category'] = '任选九';
            }

            $result = array (
                'issue_id' 		=> $api->issue_id,
                'issue_no' 		=> $fourteen_issue_info['issue_no'],
                'lottery_id' 	=> $fourteen_issue_info['lottery_id'],
                'prize_time' 	=> $fourteen_issue_info['prize_time'],
                'lottery_name' 	=> $lotteryInfo['lottery_name'],
                'lottery_image' => $lotteryInfo['lottery_image'],
                'start_time' 	=> strtotime($issueInfo['issue_start_time']),
                'end_time' 		=> strtotime($issueInfo['issue_end_time']) - $lotteryInfo['lottery_ahead_endtime'],
                'prize_num' 	=> $fourteen_issue_info['prize_num'],
                'sell_amount' 	=> $fourteen_issue_info['issue_sell_amount'],
                'winnings_amount' 	=> $fourteen_issue_info['winnings_pool'],
                'fourteen_ranking' 	=> $fourteen_ranking,
                'nine_ranking' 	=> $nine_ranking,
                'nine_sales'  => emptyToStr($nine_issueInfo['issue_sell_amount']),
                'fourteen_sales'  => emptyToStr($fourteen_issue_info['issue_sell_amount']),
            );
        }else{
            $lotteryInfo = D('Lottery')->getLotteryInfo($issueInfo['lottery_id']);
            $winningsRanking = $this->_getWinningsRanking($api->issue_id);
            $result = array (
                'issue_id' 		=> $api->issue_id,
                'issue_no' 		=> $issueInfo['issue_no'],
                'lottery_id' 	=> $issueInfo['lottery_id'],
                'prize_time' 	=> strtotime($issueInfo['issue_prize_time']),
                'lottery_name' 	=> $lotteryInfo['lottery_name'],
                'lottery_image' => $lotteryInfo['lottery_image'],
                'start_time' 	=> strtotime($issueInfo['issue_start_time']),
                'end_time' 		=> strtotime($issueInfo['issue_end_time']) - $lotteryInfo['lottery_ahead_endtime'],
                'prize_num' 	=> $issueInfo['issue_prize_number'],
                'sell_amount' 	=> $issueInfo['issue_sell_amount'],
                'winnings_amount' 	=> $issueInfo['issue_winnings_pool'],
                'winnings_ranking' 	=> $winningsRanking,
            );
        }

        $result = array_map('emptyToStr', $result);
        return array(  'result' => $result,
            'code'   => C('ERROR_CODE.SUCCESS'));
    }

    private function _getZcsfcSellData($issue_id){
        $data = array();
        $issue_no = D('Issue')->getIssueNoById($issue_id);
        $where['issue_no'] = $issue_no;
        $where['lottery_id'] = array('IN',array(TIGER_LOTTERY_ID_OF_SFC_14,TIGER_LOTTERY_ID_OF_SFC_9));
        $issue_list = M('Issue')->where($where)->select();
        foreach($issue_list as $issue_info){
            if($issue_info['lottery_id'] == TIGER_LOTTERY_ID_OF_SFC_14){
                $data['fourteen_sales'] = $issue_info['issue_sell_amount'];
            }elseif($issue_info['lottery_id'] == TIGER_LOTTERY_ID_OF_SFC_9){
                $data['nine_sales'] = $issue_info['issue_sell_amount'];
            }
        }
        return $data;
    }


    public function getJcWinningsList($api) {
        $date       = $this->_getRequestDate($api->date);
//         $lotteryIds = ( $api->lottery_id==C('JC.JCZQ') ? C('JCZQ.CONCEDE') : C('JCLQ.DXF') );
        $lotteryIds = $api->lottery_id;
        $schedules  = D('JcSchedule')->getSchedulesByDate($lotteryIds, $date['begin'], $date['end']);
        $data       = $this->_groupingSchedules($schedules);
        return array(	'result' => $data,
            'code'   => C('ERROR_CODE.SUCCESS'));
    }


    private function _getRequestDate($time) {
        if ($time) {
            $dateBegin	= date('Y-m-d 00:00:00', $time);
            $dateEnd 	= date('Y-m-d 23:59:59', $time);
        } else {
            $twoDayAgo	= strtotime('-2 days');
            $dateBegin  = date('Y-m-d 00:00:00', $twoDayAgo);
            $dateEnd	= date('Y-m-d 23:59:59');
        }
        return array('begin'=>$dateBegin, 'end'=>$dateEnd);
    }


    private function _groupingSchedules($schedules) {
        $idx = 0;
        $data['groups'] = array();
        foreach ($schedules as $schedule) {
            $idx++;
            $groupDate = formatDate($schedule['schedule_prize_time']);
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


    private function _convertIssueLotteryId($datas,$api) {
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
                if($api->sdk_version<8){
                    continue;
                }
            } elseif($data['lottery_id'] == TIGER_LOTTERY_ID_OF_JS_K3){
	    	 if($api->sdk_version<8){
                    continue;
                }
	    }
            $issueInfos[] = $data;
        }
        return $issueInfos;
    }


    private function _convertIssueInfo($issueInfos,$api) {
        $lottery_id = $api->lottery_id;
        $result = array();
        $end_key = count($issueInfos) - 1;
        foreach ( $issueInfos as $key => $issueInfo ) {
            if (!$issueInfo['test_num']) {
                unset($issueInfo['test_num']);
            }
            if($issueInfo['lottery_id'] == TIGER_LOTTERY_ID_OF_SFC_14 || $issueInfo['lottery_id'] == TIGER_LOTTERY_ID_OF_SFC_9){
                $issueInfo['lottery_id'] = TIGER_LOTTERY_ID_OF_SFC_14;
            }

            if ($key == $end_key and in_array($issueInfo['lottery_id'],C('LOTTERY_CATEGORY.SZC')) and $lottery_id){
                $base_response = $this->_getIssueInfoFromBaseData(1002,$issueInfo['lottery_id'],$issueInfo['issue_no']);
                $issueInfo['miss_count'] = (string)$base_response['data']['history_prize_number'];
            }else{
                $issueInfo['miss_count'] = '';
            }

            if ($issueInfo['lottery_id'] == TIGER_LOTTERY_ID_OF_JS_K3 && $api->sdk_version < 9){
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


    private function _issueNoExistInfo() {
        return array(
            'result' => array( 'retry_time' => 90, ),
            'code' 	 => C('ERROR_CODE.ISSUE_NO_EXIST'),
        );
    }


    private function _getWinningsRanking($issueId) {
        $winningsList = D('WinningsScheme')->getWinningsList($issueId);
        $winnings_ranking = array();
        foreach ($winningsList as $winning) {
            $winnings_ranking[] = array(
                'winnings_category' 	=> $winning['ws_bonus_name'],
                'winnings_stake_count' 	=> $winning['ws_winning_num'],
                'per_winnings_bonus' 	=> $winning['ws_bonus_money'],
            );
        }
        return $winnings_ranking;
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

    public function getLatestPrizeIssueInfo($api){
        $lottery_id = $api->lottery_id;
        $lottery_info = D('Lottery')->getLotteryInfo($lottery_id);
        if(empty($lottery_info)){
            \AppException::throwException(C('ERROR_CODE.LOTTERY_NO_EXIST'));
        }
        $latest_issue_info = D('Issue')->getLatestPrizeIssue($lottery_id);

        return array(
            'result' => array(
                    'issue_id'=>emptyToStr($latest_issue_info['issue_id']),
                    'issue_no'=>emptyToStr($latest_issue_info['issue_no']),
                    'prize_num'=>emptyToStr($latest_issue_info['issue_prize_number']),
            ),
            'code'   => C('ERROR_CODE.SUCCESS')
        );

    }

}