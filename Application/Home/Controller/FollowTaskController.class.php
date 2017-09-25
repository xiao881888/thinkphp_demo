<?php
namespace Home\Controller;
use Home\Controller\BettingBaseController;
use Home\Util\Factory;

class FollowTaskController extends BettingBaseController {
    const RETRY_TIMES_LIMIT = 5;
    private $_redis_instance = null;
    private $_follow_task_set = '';
    private $_failed_follow_task_set = '';
    private $_notice_failed_follow_task_set = '';

    public function __construct(){
        $this->_redis_instance = Factory::createRedisObj();
    }

    public function initFollowTaskSetKeys($lottery_id, $issue_id){
        $this->_follow_task_set = $this->_genKeyNameForFollowTaskSet($lottery_id, $issue_id);
        $this->_failed_follow_task_set = $this->_genKeyNameForFailedFollowTaskSet($lottery_id, $issue_id);
        $this->_notice_failed_follow_task_set = $this->_genKeyNameForNoticeFailedFollowTaskSet($lottery_id, $issue_id);
    }

    private function _genKeyNameForFollowTaskSet($lotteryId, $issueId) {
        return 'follow:'.$lotteryId.':'.$issueId;
    }

    private function _genKeyNameForNoticeFailedFollowTaskSet($lotteryId, $issueId) {
        return 'notice:failed_follow:'.$lotteryId.':'.$issueId;
    }

    private function _genKeyNameForFailedFollowTaskSet($lotteryId, $issueId){
        return 'failed_follow:' . $lotteryId . ':' . $issueId;
    }

    private function _genKeyNameForFollowIdFailedTimes($follow_task_id){
        return 'follow_id:fail_times:' . $follow_task_id;
    }

    public function addToTaskSet($task_set_key, $task_ids){
        if(!is_array($task_ids)){
            $task_ids = array($task_ids);
        }
        foreach ($task_ids as $task_id) {
            if($task_id){
                $this->_redis_instance->sadd($task_set_key, $task_id);
                ApiLog('task_id foreach:'.$task_set_key.'==='.$task_id,'tr');

            }
        }
    }

    public function trigger() {
        $lottery_id = intval($_REQUEST['lotteryId']);
        if(empty($lottery_id)){
            $this->ajaxReturn(array('code'=>1));
        }

        $is_limit = $this->isLimitLottery($lottery_id);
        if($is_limit){
            ApiLog(' trigger:'.$lottery_id, 'tr_limit');
            $this->ajaxReturn(array('code'=>0));
        }

        ApiLog(' trigger:'.$lottery_id, 'tr');
        $current_issue_info = D('Issue')->getCurrentIssueInfo($lottery_id);
        $current_issue_id   = $current_issue_info['issue_id'];
        ApiLog(' curr info:'.print_r($current_issue_info,true), 'tr');

        $this->initFollowTaskSetKeys($lottery_id, $current_issue_id);

        $follow_task_ids = D('FollowBet')->queryFollowTaskIds($lottery_id);
        ApiLog('task_ids:'.print_r($follow_task_ids,true),'tr');
        if(!empty($follow_task_ids)){
            $this->addToTaskSet($this->_follow_task_set, $follow_task_ids);
            $this->executeFollowTask($current_issue_info);
            $this->retryFailedFollowTask($current_issue_info);

            $this->noticeFailedFollowTask($lottery_id, $current_issue_info);
        }

        $this->_clearEmptyFollowTaskSet();
        $this->ajaxReturn(array('code'=>C('ERROR_CODE.SUCCESS')));
    }


    private function _clearEmptyFollowTaskSet() {
        $this->_delelteEmptySet($this->_follow_task_set);
        $this->_delelteEmptySet($this->_failed_follow_task_set);
        $this->_delelteEmptySet($this->_notice_failed_follow_task_set);
    }

    private function _delelteEmptySet($task_set){
        $set_card = $this->_redis_instance->scard($task_set);
        if(empty($set_card)){
            $this->_redis_instance->del($task_set);
        }
    }

    public function addFailFollowOrderTickets($follow_task_id,$lotteryId,$issueInfo){
        //增加失败状态 order ticket
        $followInfo = D('FollowBet')->getFollowBetInfo($follow_task_id);
        if (empty($followInfo)) {
            return false;
        }

        $orderInfo 	= D('Order')->getOrderInfo($followInfo['order_id']);
        if(empty($orderInfo)){
            $orderInfo 	= D('OrderBackup')->getOrderInfo($followInfo['order_id']);
        }

        $orderSku	= buildOrderSku($orderInfo['uid']);
        $currentFollowTimes = ($followInfo['follow_times'] - $followInfo['follow_remain_times']) + 1;

        $order_coupon_total_amount = $orderInfo['order_coupon_consumption'];

        $used_amount = $currentFollowTimes*$orderInfo['order_total_amount'];

        if($order_coupon_total_amount){
            if(($order_coupon_total_amount-$used_amount)>=$orderInfo['order_total_amount']){
                $order_coupon_amount = $orderInfo['order_total_amount'];
            }else{
                $order_coupon_amount = $order_coupon_amount-$used_amount;
            }
            $order_coupon_id = $orderInfo['user_coupon_id'] ;
        }else{
            $order_coupon_amount = 0;
            $order_coupon_id = 0 ;
        }

        $fail_order_data = array(
            'order_sku' => $orderSku,
            'uid' => $orderInfo['uid'],
            'order_create_time' => getCurrentTime(),
            'order_modify_time' => getCurrentTime(),
            'order_total_amount'=> $orderInfo['order_total_amount'],
            'order_multiple' => $orderInfo['order_multiple'],
            'issue_id' => $issueInfo['issue_id'],
            'first_issue_id' => $issueInfo['issue_id'],
            'order_status' => C('ORDER_STATUS.BET_ERROR'),
            'user_coupon_id' => $order_coupon_id,
            'user_coupon_amount' => $order_coupon_amount,
            'order_coupon_consumption' => $order_coupon_amount,
            'lottery_id' => $lotteryId,
            'current_follow_times' => $currentFollowTimes,
            'order_identity' => '',
            'follow_bet_id' => $followInfo['follow_bet_id'],
        );

        M()->startTrans();
        $newOrderId = D('Order')->add($fail_order_data);
        $ticketsList = $this->_addTickets($lotteryId, $followInfo['order_id'], $orderInfo['uid'], $issueInfo['issue_id'], $newOrderId);
        if (empty($newOrderId) || empty($ticketsList)) {
            M()->rollback();
            return false;
        }
        $remainTimes 	= $followInfo['follow_remain_times'] - 1;
        $followStatus 	= ( $remainTimes ? C('FOLLOW_STATUS.NORMAL') : C('FOLLOW_STATUS.FINISH') );
        $saveFollow 	= D('FollowBet')->saveFollowRemainTimes($followInfo['follow_bet_id'], $remainTimes, $followStatus);
        if (!$saveFollow) {
            M()->rollback();
            return false;
        }

        //退款
        if($fail_order_data['user_coupon_id']){
            // 有红包
            if($fail_order_data['user_coupon_amount']>=$fail_order_data['order_total_amount']){
                $refund_money = 0;
                $coupon_amount = $fail_order_data['order_total_amount'];
            }else{
                $refund_money = $fail_order_data['order_total_amount'] - $fail_order_data['user_coupon_amount'];
                $coupon_amount = $fail_order_data['user_coupon_amount'];
            }
        }else{
            $refund_money = $fail_order_data['order_total_amount'];
            $coupon_amount = 0;
        }

        if($coupon_amount){
            $coupon_result = D('UserCoupon')->refundCoupon($orderInfo['user_coupon_id'],$coupon_amount);
            if(empty($coupon_result)){
                M()->rollback();
                return false;
            }
        }

        $refund_result = D('UserAccount')->refundFollowMoney($orderInfo['uid'], $orderInfo['order_total_amount'],$refund_money,$coupon_amount, $follow_task_id);
        if(empty($refund_result)){
            M()->rollback();
            return false;
        }
        M()->commit();
        return $newOrderId;
    }



    public function retryFailedFollowTask($issue_info){
        $retry_to_follow = true;
        $this->doFollowTasks($issue_info, $retry_to_follow);
    }

    public function noticeFailedFollowTask($lottery_id, $issue_info){
        $task_set = $this->_notice_failed_follow_task_set;
        $set_card = $this->_redis_instance->scard($task_set);
        if($set_card){
            while($follow_task_id = $this->_redis_instance->spop($task_set)){
                $followInfo = D('FollowBet')->getFollowBetInfo($follow_task_id);
                if (empty($followInfo)) {
                    //报警 汇报$follow_task_id
                    sendMail(C('NOTICE_EMAILS'),'查不到追号信息：'.$follow_task_id,'');
                    continue;
                }
                $fail_order_id = $this->addFailFollowOrderTickets($follow_task_id, $lottery_id, $issue_info);
                if($fail_order_id){
                    for($i=0;$i<5;$i++){
                        $push_result = A('Push')->pushFollowFailedMessage($followInfo['order_id'], $issue_info, $fail_order_id);
                        if($push_result){
                            break;
                        }
                    }
                }

            }
        }
    }

    public function executeFollowTask($issue_info) {
        set_time_limit(0);
        $this->doFollowTasks($issue_info);
    }

    public function doFollowTasks($issue_info, $retry_to_follow = false){
        $task_set = $retry_to_follow ? $this->_failed_follow_task_set : $this->_follow_task_set;
        $set_card = $this->_redis_instance->scard($task_set);
        if($set_card){
            while($follow_task_id = $this->_redis_instance->spop($task_set)){
                if($retry_to_follow){
                    $follow_task_failed_times_key = $this->_genKeyNameForFollowIdFailedTimes($follow_task_id);
                    $follow_times = $this->_redis_instance->get($follow_task_failed_times_key);
                    if($follow_times>=self::RETRY_TIMES_LIMIT){
                        $this->_redis_instance->sadd($this->_notice_failed_follow_task_set,$follow_task_id);
                        continue;
                    }
                }

                $existFollowOrderId = D('Order')->queryOrderIdByFollowIdAndIssueId( $follow_task_id, $issue_info['issue_id']);
                ApiLog('$existFollowOrderId:'.$existFollowOrderId.'---'.$follow_task_id.'---'.$issue_info['issue_id'], 'tr');
                if(!$existFollowOrderId){
                    $existFollowOrderId 	= D('OrderBackup')->queryOrderIdByFollowIdAndIssueId( $follow_task_id, $issue_info['issue_id']);
                }

                if ($existFollowOrderId) {
                    //已追过 通知 并log
                    sendMail(C('NOTICE_EMAILS'),$follow_task_id.'已存在追号订单：'.$existFollowOrderId,$existFollowOrderId);
                    continue;
                }

                $lottery_info = D('Lottery')->getLotteryInfo($issue_info['lottery_id']);
                $beforeDeadline = (strtotime($issue_info['issue_end_time']) - time() > $lottery_info['lottery_ahead_endtime']);
                if (!$beforeDeadline) {
                    sendMail(C('NOTICE_EMAILS'), $follow_task_id . '时间来不及追号的', $issue_info['issue_end_time'] . '====' . date('Y-m-d H:i:s'));
                    $this->_redis_instance->sadd($this->_notice_failed_follow_task_set, $follow_task_id);
                    continue;
                }

                $this->doFollowTaskById($follow_task_id, $issue_info);
            }
        }
    }

    private function doFollowTaskById($follow_task_id, $issue_info) {
        $do_follow_result = $this->_betFollowOrder($follow_task_id, $issue_info);
        if (empty($do_follow_result)) {
            $this->_redis_instance->sadd($this->_failed_follow_task_set, $follow_task_id);
            $follow_task_failed_times_key = $this->_genKeyNameForFollowIdFailedTimes($follow_task_id);
            $this->_redis_instance->incr($follow_task_failed_times_key);
        }
    }

    private function _betFollowOrder($follow_task_id, $issue_info) {
        $followInfo = D('FollowBet')->queryFollowInfoById($follow_task_id);
        if (empty($followInfo)) {
            return false;
        }

// 先去掉
// 		if ($this->_isSpecialFollowOrder($followInfo['follow_bet_type'])) {
// 			if ($this->_isFollowOrderFinish($followInfo)) {
// 				D('FollowBet')->saveFollowRemainTimes($followId, 0, C('FOLLOW_STATUS.FINISH'));
// 				return 'error';
// 			}
// 		}

        $orderInfo 	= D('Order')->getOrderInfo($followInfo['order_id']);
        if(empty($orderInfo)){
            $orderInfo 	= D('OrderBackup')->getOrderInfo($followInfo['order_id']);
            if(empty($orderInfo)){
                return false;
            }
        }
        $frozenBalance = D('UserAccount')->getFrozenBalance($orderInfo['uid']);
        if ($frozenBalance < $orderInfo['order_total_amount']) {
            return false;
        }

        $lotteryId 	= $orderInfo['lottery_id'];
        $userInfo 	= D('User')->getUserInfo($orderInfo['uid']);

        if ($userInfo['user_status'] != C('USER_STATUS.ENABLE')) {
            return false;
        }

        return $this->_createFollowBet($orderInfo, $followInfo, $issue_info, $userInfo, $lotteryId);
    }

    private function _createFollowBet($orderInfo, $followInfo, $issueInfo, $userInfo, $lotteryId) {
        $orderSku	= buildOrderSku($orderInfo['uid']);

        //FIXME 当前次数如何确认
        $currentFollowTimes = ($followInfo['follow_times'] - $followInfo['follow_remain_times']) + 1;

        $order_coupon_total_amount = $orderInfo['order_coupon_consumption'];

        $used_amount = $currentFollowTimes * $orderInfo['order_total_amount'];

        //FIXME 当前消费金额如何确认

        if($order_coupon_total_amount){
            if(($order_coupon_total_amount-$used_amount)>=$orderInfo['order_total_amount']){
                $order_coupon_amount = $orderInfo['order_total_amount'];
            }else{
                $order_coupon_amount = $order_coupon_amount-$used_amount;
            }
            $order_coupon_id = $orderInfo['user_coupon_id'] ;
        }else{
            $order_coupon_amount = 0;
            $order_coupon_id = 0 ;
        }

        $order_identity = 'FOLLOW'.date("YmdHis").$orderInfo['order_id'].$orderInfo['uid'].$currentFollowTimes;
        $order_data = array(
            'order_sku' => $orderSku,
            'uid' => $orderInfo['uid'],
            'order_create_time' => getCurrentTime(),
            'order_modify_time' => getCurrentTime(),
            'order_total_amount'=> $orderInfo['order_total_amount'],
            'order_multiple' => $orderInfo['order_multiple'],
            'issue_id' => $issueInfo['issue_id'],
            'first_issue_id' => $issueInfo['issue_id'],
            'order_status' => C('ORDER_STATUS.UNPAID'),
            'user_coupon_id' => $order_coupon_id,
            'user_coupon_amount' => $order_coupon_amount,
            'order_coupon_consumption' => $order_coupon_amount,
            'lottery_id' => $lotteryId,
            'current_follow_times' => $currentFollowTimes,
            'order_identity' => $order_identity,
            'follow_bet_id' => $followInfo['follow_bet_id'],
            'play_type' => $orderInfo['play_type'],
            'bet_type' => $orderInfo['bet_type'],
            'order_type' => ORDER_TYPE_OF_FOLLOW,
        );

        M()->startTrans();
        $newOrderId = D('Order')->add($order_data);
        $ticketsList = $this->_addTickets($lotteryId, $followInfo['order_id'], $orderInfo['uid'], $issueInfo['issue_id'], $newOrderId);
        if (empty($newOrderId) || empty($ticketsList)) {
            M()->rollback();
            return false;
        }

        $remainTimes 	= $followInfo['follow_remain_times'] - 1;
        $followStatus 	= ( $remainTimes ? C('FOLLOW_STATUS.NORMAL') : C('FOLLOW_STATUS.FINISH') );
        $saveFollow 	= D('FollowBet')->saveFollowRemainTimes($followInfo['follow_bet_id'], $remainTimes, $followStatus);
        if (!$saveFollow) {
            M()->rollback();
            return false;
        }
        M()->commit();

        $printTickets = array();
        foreach ($ticketsList as $ticket) {
            $printTickets[] = $this->buildNumberTicketItemForPrintout($ticket['ticket_seq'], $ticket['play_type'],
                $ticket['bet_type'], $ticket['bet_number'], $ticket['stake_count'], $ticket['total_amount'], $ticket['ticket_multiple']);
        }

        $printOutResult = $this->printOutTicket($userInfo, $issueInfo['issue_no'], $newOrderId, $lotteryId, $printTickets, $orderInfo['order_multiple']);
        if(!$printOutResult){
            for($i=0;$i<3;$i++){
                $printOutResult = $this->printOutTicket($userInfo, $issueInfo['issue_no'], $newOrderId, $lotteryId, $printTickets, $orderInfo['order_multiple']);
                if($printOutResult){
                    break;
                }
            }
        }
        if($printOutResult){
            $deductResult = D('UserAccount')->deductFrozenBalance($orderInfo['uid'], $orderInfo['order_total_amount'], $newOrderId, C('USER_ACCOUNT_LOG_TYPE.BET'));
        }
        if (!$printOutResult || !$deductResult) {
            return false;
        }

        $saveStatus = D('Order')->saveOrderStatus($newOrderId, C('ORDER_STATUS.PAYMENT_SUCCESS'));
        if ($saveStatus===false) {
            return false;
        }
        return true;
    }

    private function _isSpecialFollowOrder($followType) {
        return in_array($followType, array(C('FOLLOW_BET_TYPE.STOP_WHEN_PRIZE'), C('FOLLOW_BET_TYPE.STOP_UNTIL_AMOUNT')));
    }


    private function _isFollowOrderFinish($followInfo) {
        $followOrders = D('Order')->getOrderWinningsInfo($followInfo['follow_bet_id']);
        if(empty($followOrders)){
            $followOrders = D('OrderBackup')->getOrderWinningsInfo($followInfo['follow_bet_id']);
        }
        if ($followInfo['follow_bet_type'] == C('FOLLOW_BET_TYPE.STOP_WHEN_PRIZE')) {
            foreach ($followOrders as $followOrder) {		// 如果有一个订单中奖，就停止追号
                if ($followOrder['order_winnings_status'] == C('ORDER_WINNINGS_STATUS.YES')) {
                    return true;
                }
            }
        } elseif ($followInfo['follow_bet_type'] == C('FOLLOW_BET_TYPE.STOP_UNTIL_AMOUNT')) {
            $winningsBonus = 0;
            foreach ($followOrders as $followOrder) {
                $winningsBonus += $followOrder['order_winnings_bonus'];
            }
            // 有设置金额限制，并且中奖金额累计超过限制，就停止追号
            if ($followInfo['follow_bet_upper_limit']>0 && $winningsBonus>=$followInfo['follow_bet_upper_limit']) {
                return true;
            }
        }
        return false;
    }

    private function _addTickets($lotteryId, $orderId, $uid, $issueId, $newOrderId) {
        if (!$newOrderId) {
            return false;
        }
        $ticketModel = getTicktModel($lotteryId);
        $tickets = $ticketModel->getTicketsByOrderId($orderId,$uid);
        if (!$tickets) {
            return false;
        }
        $ticketsList = $this->_buildTicketDataForCopy($tickets, $uid, $issueId, $newOrderId, $lotteryId);
        $add_ticketlist_result = $ticketModel->addAll($ticketsList);
        return  ( $add_ticketlist_result ? $ticketsList : false );
    }

    private function _buildTicketDataForCopy(array $tickets, $uid, $issueId, $orderId, $lotteryId) {
        $ticket_data_list = array();
        $ticketModel = getTicktModel($lotteryId);
        foreach ($tickets as $ticket) {
            $ticketData = $ticketModel->buildTicketData($uid, $issueId, $ticket['bet_number'],
                $ticket['play_type'], $ticket['stake_count'], $ticket['total_amount'], $orderId, $ticket['ticket_seq'], $ticket['bet_type'], $ticket['ticket_multiple'], $issueId, $lotteryId);

            $ticket_data_list[] = $ticketData;
        }
        return $ticket_data_list;
    }


}