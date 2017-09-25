<?php
namespace H5\Controller;


class OrderController extends BaseController
{
    private $_order_info = array();
    private $_offset = 0;
    private $_limit = 20;

    public function index()
    {
        $lottery_id = I('get.lottery_id',0,intval);
        $order_type = I('get.order_type',0,intval);
        $offset = I('get.offset',0,intval);
        $limit = I('get.limit',20,intval);

        $orders = self::getModelInstance('Order')->getOrderInfos($this->uid,$lottery_id,$order_type,$offset,$limit);

        $lottery_info = self::getModelInstance('Lottery')->getLotteryMap();

        $result = array();
        foreach ($orders as $order_info) {
            $order = array();
            $order = $order_info;
            $data = array();
            $data['id'] = (int)$order['order_id'];
            $data['lottery_id'] = (int)$order['lottery_id'];
            $data['lottery_name'] = (string)$lottery_info[$order['lottery_id']]['lottery_name'];
            $data['lottery_image'] = (string)$lottery_info[$order['lottery_id']]['lottery_image'];
            $data['type'] = ($order['follow_bet_id'] ? 1 : 0);
            $data['status_desc'] = $this->_getOrderStatusDesc($order['order_status']);
            $data['status'] = self::getModelInstance('Order')->getStatus($order['order_status'], $order['order_winnings_status'], $order['order_distribute_status']);
            $data['winnings_bonus'] = (float)$order['order_winnings_bonus'];
            $data['plus_award_amount'] = (float)$order['order_plus_award_amount'];
            $data['total_amount'] = (float)$order['order_total_amount'];
            $data['buying_time'] = strtotime($order['order_create_time']);
            $result[] = $data;
        }

        $this->response($result);

    }

    public function detail()
    {
        $order_id = I('get.order_id',0,intval);

        if (!$order_id){
            $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
        }

        $lottery_id = self::getModelInstance('Order')->getLotteryId($order_id);
        $order_view_model = $this->_getOrderViewModel($lottery_id);
        $orderInfo = $order_view_model->getOrderInfoByOrderId($order_id);

        if (empty($orderInfo) or $orderInfo['uid'] != $this->uid){
            $this->responseError(RESPONSE_ERROR_WITHOUT_BILL);
        }
        $order_status = $orderInfo['order_status'];
        $orderInfo['plus_award_amount'] = $orderInfo['order_plus_award_amount'];
        $orderInfo['status_desc'] = $this->_getOrderStatusDesc($orderInfo['order_status']);
        $orderInfo['status'] = self::getModelInstance('Order')->getStatus($orderInfo['order_status'], $orderInfo['order_winnings_status'], $orderInfo['order_distribute_status']);
        if ($orderInfo['status'] == C('ORDER_STATUS.PRINTOUTED') && !isZcsfc($orderInfo['lottery_id'])) {  // 未开奖的不要返回开奖号码
            unset($orderInfo['prize_num']);
        }

        $issue_id = self::getModelInstance('Order')->getIssueId($order_id);
        if(isJc($lottery_id)){
            $orderInfo['series'] 	= $this->_getBetType($lottery_id, $order_id);
            $orderInfo['jc_info'] 	= $this->_getJcInfo($lottery_id, $order_id ,$order_status);
            $endTime = self::getModelInstance('JcSchedule')->getEndTime($issue_id);
        }else{
            $ticketList = $this->_getTickets($lottery_id, $order_id, $this->uid,$orderInfo['order_status']);

            if (isZcsfc($lottery_id)) {
                // bettype字段
                $orderInfo['series'] = $this->_getBetType($lottery_id, $order_id);
                $orderInfo['jc_info'] = $this->getZcsfcInfo($order_id);
            } else {
                $orderInfo['tickets'] = $ticketList['ticket_list'];
            }

            if($orderInfo['order_status'] == C('ORDER_STATUS.PRINTOUT_ERROR_REFUND')){
                $orderInfo['failure_amount'] = $orderInfo['total_amount'];
            }else{
                $orderInfo['failure_amount'] = $ticketList['failure_amount'];
            }
            $endTime = self::getModelInstance('Issue')->getEndTime($issue_id);
        }

        $orderInfo['end_time']	= strtotime($endTime);

        $followBetInfoDetails = D('FollowBetInfoView')->getFollowBetDetailByOrderId($order_id);
        if(empty($followBetInfoDetails)){
            $followInfo = D('FollowBet')->getFollowBetInfo($orderInfo['follow_bet_id']);
            if($followInfo) {
                $orderInfo['follow_bet_id'] = $orderInfo['follow_bet_id'];
                $orderInfo['follow_times']  = $followInfo['follow_times'] + 1;   // 业务展示，追号从 1 开始
                $orderInfo['follow_status'] = $followInfo['follow_status'];
                $orderInfo['follow_finish_times'] = $followInfo['follow_times'] - $followInfo['follow_remain_times'] + 1;
                $orderInfo['current_follow_times'] = $orderInfo['current_follow_times'];
                $orderInfo['follow_refund'] = $followInfo['follow_remain_times'] * $orderInfo['total_amount'];
            }
        } else{
            $orderInfo['follow_bet_id'] = $followBetInfoDetails['fbi_id'];
            $orderInfo['follow_times']  = $followBetInfoDetails['follow_times'];
            $orderInfo['follow_status'] = $this->_getFollowStatus($followBetInfoDetails['fbi_status']);
            $orderInfo['follow_finish_times'] = D('FollowBetInfoView')->getFinishTimes($followBetInfoDetails['fbi_id']);
            $orderInfo['current_follow_times'] = ($followBetInfoDetails['fbd_index'] - 1);
            $orderInfo['follow_refund'] = $this->_getFollowRefund($followBetInfoDetails,$orderInfo);
        }

        $orderInfo['buying_time'] = strtotime($orderInfo['buying_time']);
        $orderInfo['prize_time']  = strtotime($orderInfo['prize_time']);
        $orderInfo['official_prize_time']  = empty($orderInfo['official_prize_time']) ? 0 : strtotime($orderInfo['official_prize_time']);

        if($orderInfo['follow_bet_id'] || $orderInfo['type']==ORDER_TYPE_OF_FOLLOW){
            $orderInfo['type'] = ORDER_TYPE_OF_FOLLOW;
        }

        $orderInfo['lottery_id'] = $this->_convertLotteryId($orderInfo['lottery_id']);

        $this->_order_info = $orderInfo;
        $this->_order_info['order_id'] = $orderInfo['id'];

        if(isJc($lottery_id)){
            $ticket_detail_list = $this->_buildTicketDetailList($lottery_id, $this->uid,0);
            $orderInfo['failure_amount'] = $ticket_detail_list['failure_amount'];
        }

        $this->response($orderInfo);
    }

    public function program()
    {
        $order_id = I('get.order_id',0,intval);
        if (!$order_id){
            $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
        }
        $lottery_id = self::getModelInstance('Order')->getLotteryId($order_id);
        $order_view_model = $this->_getOrderViewModel($lottery_id);
        $orderInfo = $order_view_model->getOrderInfoByOrderId($order_id);

        $uid = $this->uid;
        $this->_order_info = $orderInfo;
        $this->_order_info['order_id'] = $orderInfo['id'];

        if (empty($orderInfo) or $orderInfo['uid'] != $this->uid){
            $this->responseError(RESPONSE_ERROR_WITHOUT_BILL);
        }
        $response_data = $this->_buildTicketDetailList($this->_order_info['lottery_id'], $uid);
        $this->response($response_data);
    }

    private function _getOrderStatusDesc($order_status){
        $desc = '';
        if($order_status == C('ORDER_STATUS.PRINTOUTED_PART_REFUND')){
            $desc = '部分出票失败';
        }
        return $desc;
    }

    private function _getOrderViewModel($lottery_id){
        $model_name = ( isJc($lottery_id) ? 'JcOrderView' : 'OrderView' );
        return self::getModelInstance($model_name);
    }

    private function _getBetType($lotteryId, $orderId){
        $model = getTicktModel($lotteryId);
        $betTypes = $model->getBetTypesByOrderId($orderId);
        $betTypes = array_unique($betTypes);
        return implode(',', $betTypes);
    }

    private function _getJcInfo($lotteryId, $orderId, $order_status=0){
        $jcInfo = self::getModelInstance('JcOrderDetailView')->getInfos($orderId);

        $model = getTicktModel($lotteryId);
        $odds_list = $model->getFormatPrintoutOdds($orderId);
        foreach ($jcInfo as $k=>$v){
            $bet_content = $v['bet_content'];
            $bet_content_array = json_decode($bet_content, true);
            $schedule_issue_no = $v['schedule_issue_no'];
            $schedule_issue_no = substr($schedule_issue_no, 3);
            foreach ($bet_content_array as $k_lottery_id=>$content){
                $odds_key = $schedule_issue_no.'_'.$k_lottery_id;
                $odds = $odds_list[$odds_key];

                //如果找不到赔率，显示投注时候的内容
                if(empty($odds)){
                    $odds = array();
                    foreach ($content as $op_v){
                        $odds[$op_v] = '';
                    }

                }
                $format_odds = array();
                $format_odds = getFormatOdds($k_lottery_id, json_encode($odds), $order_status);
                if($order_status==5){

                    $jcInfo[$k]['score'] = array('half'=>'','final'=>'');
                }

                $betting_order = $jcInfo[$k]['betting_order'] ? $jcInfo[$k]['betting_order'] : array();
                $betting_order = array_merge($betting_order, $format_odds);

                if(sizeof($betting_order)>0){
                    $jcInfo[$k]['betting_order'] = array_merge($betting_order, $format_odds);
                }
            }
            $jcInfo[$k]['round_no'] = getWeekName($v['schedule_week']).$v['round_no'];

            if(isJczq($lotteryId)){
                $let_point = array_search_value('letPoint', json_decode($v['schedule_odds'], true));
                $base_point = array_search_value('basePoint', json_decode($v['schedule_odds'], true));
            }else{
                $let_point = array_search_value('letPoint', json_decode($v['schedule_odds'], true));
                $base_point = array_search_value('basePoint', json_decode($v['schedule_odds'], true));
            }
            if(isJcMix($lotteryId)){
                $format_result_odds = json_decode($v['schedule_odds'], true);
            }else{
                $format_result_odds[$lotteryId] = json_decode($v['schedule_odds'], true);
            }

            $jcInfo[$k]['let_point'] = $let_point ? (float)$let_point : '';
            $jcInfo[$k]['base_point'] = $base_point ? (float)$base_point : '';
            $jcInfo[$k]['result_odds'] = empty($format_result_odds) ? array():$format_result_odds;

            unset($jcInfo[$k]['schedule_odds']);
        }

        return $jcInfo;
    }

    private function _getTickets($lottery_id, $order_id, $uid, $order_status ){
        $ticketModel = getTicktModel($lottery_id);
        $tickets = $ticketModel->getTicketsByOrderId($order_id,$uid);

        $ticketList = array();
        $failure_amount = 0;
        foreach ($tickets as $ticket) {
            $ticket_info = array(
                'bet_number' => $ticket['bet_number'],
                'play_type' => $ticket['play_type'],
                'bet_type' => $ticket['bet_type'],
                'stake_count' => $ticket['stake_count'],
                'winnings_status' => $ticket['winnings_status'],
                'ticket_status' => ($order_status == 5) ? 2 : $ticket['ticket_status'],
                'ticket_multiple' => $ticket['ticket_multiple'],
            );

            if ($ticket_info['ticket_status'] == C('TICKET_STATUS.PRINTOUT_FAIL')) {
                $failure_amount += $ticket['total_amount'];
            }

            $ticketList[] = $ticket_info;
        }
        if (!$ticketList) {
            return false;
        }
        $res['ticket_list'] = $ticketList;
        $res['failure_amount'] = $failure_amount;
        return $res;
    }

    private function _convertLotteryId($lotteryId) {
        return $lotteryId;
    }

    private function _buildTicketDetailList($lottery_id, $uid, $is_ticket_detail=1){
        $ticket_detail_list = array();
        $ticketModel = getTicktModel($this->_order_info['lottery_id']);
        $ticket_list = $ticketModel->getTicketsByOrderId($this->_order_info['order_id'],$uid);

        if($is_ticket_detail && isJCLottery($this->_order_info['lottery_id'])){
            $schedule_list = self::getModelInstance('JcOrderDetailView')->getScheduleInfoByIssueNo($this->_order_info['order_id']);
        }else{
            if(isZcsfc($this->_order_info['lottery_id'])){
                $issue_id = self::getModelInstance('Order')->getIssueId($this->_order_info['order_id']);
                $issue_info = self::getModelInstance('Issue')->getIssueInfo($issue_id);
                $issue_no = $issue_info['issue_no'];
                $schedule_list = self::getModelInstance('ZcsfcSchedule')->getScheduleListOfScore($issue_no);
            }else{
                $schedule_list = array();
            }

        }
        $success_amount = 0;
        $failure_amount = 0;
        $winnings_bonus = 0;
        foreach($ticket_list as $ticket_info){
            $ticket_detail_info = $this->_buildTicketDetailInfo($ticket_info,$schedule_list,$is_ticket_detail);
            if($ticket_detail_info['ticket_status']==C('TICKET_STATUS.PRINTOUT')){
                $success_amount += $ticket_detail_info['ticket_amount'];
                $winnings_bonus += $ticket_detail_info['ticket_winnings'];
            }elseif($ticket_detail_info['ticket_status']==C('TICKET_STATUS.PRINTOUT_FAIL')){
                $failure_amount += $ticket_detail_info['ticket_amount'];
            }
            $ticket_detail_list[] = $ticket_detail_info;
        }

        $ticket_detail_list = $this->_filterTicketList($ticket_detail_list);

        $response['success_amount'] = $success_amount;
        $response['failure_amount'] = $failure_amount;
        $response['winnings_bonus'] = $winnings_bonus;
        $response['tickets'] = $ticket_detail_list;
        return $response;
    }

    private function _filterTicketList($ticket_detail_list){
        $ticket_list = array();
        if (I('limit',false)){
            $this->_limit = I('limit');
        }
        if (I('offset',false)){
            $this->_offset = I('offset');
        }
        $start_seq = $this->_offset+1;
        $end_seq = $this->_offset+1+$this->_limit;
        foreach($ticket_detail_list as $key => $ticket_info){
            if($ticket_info['seq'] >= $start_seq && $ticket_info['seq'] <= $end_seq){
                $ticket_list[$key] = $ticket_info;
            }
            if(count($ticket_list) >= $this->_limit){
                break;
            }
        }
        return array_values($ticket_list);
    }

    private function _buildTicketDetailInfo($ticket_info,$schedule_list, $is_ticket_detail=1){
        $detail_info['seq'] = $ticket_info['ticket_seq'];
        $detail_info['ticket_winnings_status'] = $ticket_info['winnings_status'];
        $detail_info['ticket_winnings'] = $ticket_info['winnings_bonus'];
        $detail_info['multiple'] = $ticket_info['ticket_multiple'];
        $detail_info['series'] = $ticket_info['bet_type'];
        $detail_info['ticket_amount'] = $ticket_info['total_amount'];
        $detail_info['lottery_id'] = $ticket_info['lottery_id'];
        $detail_info['printout_time'] = ($ticket_info['printout_time']=='0000-00-00 00:00:00')?0:strtotime($ticket_info['printout_time']);
        $detail_info['ticket_status'] = $this->_buildTicketStatus($ticket_info['ticket_status'], $this->_order_info['order_status']);
        if($is_ticket_detail && isJCLottery($ticket_info['lottery_id'])){
            $detail_info['jc_info'] = $this->_buildJcInfoByTicketInfo($ticket_info, $schedule_list);
        }else{
            if(isZcsfc($ticket_info['lottery_id'])){
                $detail_info['jc_info'] = $this->_buildZcsfcJcInfoByTicketInfo($ticket_info,$schedule_list);
            }
            $detail_info['bet_number'] = $ticket_info['bet_number'];
            $detail_info['play_type'] = $ticket_info['play_type'];
            $detail_info['bet_type'] = $ticket_info['bet_type'];
        }
        return $detail_info;
    }

    private function _buildTicketStatus($ticket_status,$order_status){
        $fail_status = array(
            C('ORDER_STATUS.PRINTOUT_ERROR'),
            C('ORDER_STATUS.PRINTOUT_ERROR_REFUND'),
            C('ORDER_STATUS.BET_ERROR'),
        );
        if(in_array($order_status,$fail_status)){
            return C('TICKET_STATUS.PRINTOUT_FAIL');
        }
        return $ticket_status;
    }

    private function _buildJcInfoByTicketInfo($ticket_info,$schedule_list){
        if(empty($ticket_info['printout_odds'])){
            $ticket_content_list = json_decode($ticket_info['ticket_content'],true);

            $jc_info_list = array();
            foreach($ticket_content_list as $ticket_content_info){

                $virtual_odds = array();
                foreach($ticket_content_info['bet_options'] as $option){
                    $virtual_odds[$option] = '';
                }
// 				$jc_info['let_point'] = '';
// 				$jc_info['base_point'] = '';
                $jc_info['round_no'] = $this->_parseRoundNo($ticket_content_info['issue_no'], $schedule_list);
                $jc_info['betting_order'] = getFormatOdds($ticket_content_info['lottery_id'], json_encode($virtual_odds));
// 				$jc_info['betting_order'] = array();
// 				$jc_info['score'] = '';
                $jc_info_list[] = $jc_info;
            }
        }else{
            $printout_odds_list = json_decode($ticket_info['printout_odds'],true);
            ApiLog('$printout_odds_list:'.print_r($printout_odds_list,true), 'h5_detail');
            $jc_info_list = array();
            foreach($printout_odds_list as $schedule_bet_info){
                $jc_info['let_point'] = (string)$schedule_bet_info['odds']['letPoint'];
                $jc_info['base_point'] = (string)$schedule_bet_info['odds']['basePoint'];
                $jc_info['round_no'] = $this->_parseRoundNo($schedule_bet_info['issue_no'], $schedule_list);
                $jc_info['betting_order'] = getFormatOdds($schedule_bet_info['lottery_id'], json_encode($schedule_bet_info['odds']));
                $jc_info['score'] = $schedule_list[$schedule_bet_info['issue_no']]['score'];
                $jc_info_list[] = $jc_info;
            }
        }
        return $jc_info_list;
    }

    private function _buildZcsfcJcInfoByTicketInfo($ticket_info,$schedule_list){
        $bet_num_list = $ticket_info['bet_number'];
        $bet_num_list = explode(',',$bet_num_list);
        foreach($bet_num_list as $key => $bet_num){
            if($bet_num == '#'){
                continue;
            }
            $round_no = $key+1;
            $jc_info[] = array(
                'round_no' => $round_no,
                'betting_order' => array('betting_num'=>$bet_num),
                'score' => array('final'=>emptyToStr($schedule_list[$round_no]['sfc_schedule_final_score'])),
            );
        }
        return $jc_info;
    }

    private function _buildZcsfcInfoByTicketInfo($ticket_info){
        $zcsfc_info = array();
        $bet_number_list = $ticket_info['bet_number'];
        $bet_number_list = explode(',',$bet_number_list);
        foreach($bet_number_list as $key => $bet_number){
            $zcsfc_info[] = array(
                'round_no' => $key+1,
                'betting_order' => array('betting_num'=>$bet_number),
            );
        }
        return $zcsfc_info;
    }

    private function _parseRoundNo($issue_no, $schedule_list){
        $round_no = getWeekName($schedule_list[$issue_no]['schedule_week']).$schedule_list[$issue_no]['schedule_round_no'];
        return $round_no;
    }

    private function getZcsfcInfo($orderId){
        $zcsfc_list = array();
        $order_info = self::getModelInstance('Order')->getOrderInfo($orderId);
        $order_content = json_decode($order_info['order_content'],true);
        $issue_id = $order_info['issue_id'];
        $issue_info = self::getModelInstance('Issue')->getIssueInfo($issue_id);
        $issue_no = $issue_info['issue_no'];
        $schedule_seq_list = array_keys($order_content);
        $sfc_schedule_list = self::getModelInstance('ZcsfcSchedule')->queryScheduleListByIssueNoAndSeq($issue_no,$schedule_seq_list);

        foreach($sfc_schedule_list as $schedule_info){
            $zcsfc_list[] = array(
                'home' => $schedule_info['sfc_schedule_home_team'],
                'guest' => $schedule_info['sfc_schedule_guest_team'],
                'league' => $schedule_info['sfc_schedule_league'],
                'zq_start_date' => strtotime($schedule_info['sfc_schedule_game_start_time']),
                'round_no' => $schedule_info['sfc_schedule_seq'],
                'issue_no' => $schedule_info['sfc_schedule_issue_no'],
                'lottery_id' => $order_info['lottery_id'],
                'play_type' => $order_info['play_type'],
                'betting_order' => array('betting_num'=>$order_content[$schedule_info['sfc_schedule_seq']]['bet_number']),
                'result_odds' => array('prize_num'=>emptyToStr($schedule_info['sfc_schedule_prize_result'])),
                'score' => array('final'=>emptyToStr($schedule_info['sfc_schedule_final_score'])),
                'is_sure' => $order_content[$schedule_info['sfc_schedule_seq']]['is_sure'],
            );
        }

        return $zcsfc_list;
    }

    private function _getFollowStatus($fbi_status){
        return $fbi_status != C('FOLLOW_BET_INFO_STATUS.ON_GOING') ? 0 : 1;
    }

    private function _getFollowRefund($followBetInfoDetails,$orderInfo){
        if(($followBetInfoDetails['follow_total_amount'] - $followBetInfoDetails['followed_amount']) > $orderInfo['order_reduce_consumption']){
            return $followBetInfoDetails['follow_total_amount'] - $followBetInfoDetails['followed_amount'] - $orderInfo['order_reduce_consumption'];
        }else{
            return 0;
        }

    }

}