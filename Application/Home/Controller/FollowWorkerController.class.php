<?php
namespace Home\Controller;
use Home\Util\Factory;

class FollowWorkerController extends BettingBaseController {

    const FOLLOW_BET_ISSUE_START = 1;
    const FOLLOW_BET_AWARD = 2;

    private $_redis;
    public function __construct(){
        $this->_redis = Factory::createAliRedisObj();
        $this->_redis->select(0);
    }

    public function test(){
        $lottery_id = I('lottery_id');
        $type = I('type');
        $this->trigger($lottery_id,$type);
    }

    public function trigger($lottery_id,$type,$issue_id = 0){
        if(empty($lottery_id)){
            $this->ajaxReturn(array('code'=>1));
        }

        $is_limit = $this->isLimitLottery($lottery_id);
        if($is_limit){
            A('StopFollowBet')->limitBetStopFollowBet($lottery_id);
            ApiLog(' trigger:'.$lottery_id, 'FollowWorker');
            $this->ajaxReturn(array('code'=>0));
        }

        ApiLog(' trigger:'.$lottery_id.';$type'.$type, 'FollowWorker');
        $current_issue_info = D('Issue')->getCurrentIssueInfo($lottery_id);
        $current_issue_id   = $current_issue_info['issue_id'];
        ApiLog(' curr info:'.print_r($current_issue_info,true), 'FollowWorker');

        $lock_status = $this->_lock($lottery_id,$current_issue_id,$type);
        if(!$lock_status){
            ApiLog(' trigger:'.$lottery_id,'$lock_status:'.$lock_status, 'FollowWorker');
            $this->ajaxReturn(array('code'=>1));
        }

       /* if($this->_isFollowed($lottery_id,$current_issue_id,$type)){
            ApiLog(' $lottery_id:'.$lottery_id.'$issue_id:'.$current_issue_id.'已追过', 'FollowWorker');
            $this->ajaxReturn(array('code'=>1));
        }*/

        $follow_bet_detail_ids = $this->_createFollowTaskQueue($lottery_id,$type);
        ApiLog(' $follow_bet_detail_ids info:'.print_r($follow_bet_detail_ids,true), 'FollowWorker');
        $this->_executeFollowTask($current_issue_info,$follow_bet_detail_ids);
        $this->_addFollowed($lottery_id,$current_issue_id,$type);

        $this->_addFollowMonitor($lottery_id,$current_issue_id,$type);
        if(!empty($issue_id)){
            $this->_delFollowMonitor($lottery_id,$issue_id,$type);
        }
        $this->_unlock($lottery_id,$current_issue_id,$type);
    }


    private function _addFollowMonitor($lottery_id,$issue_id,$type){
        if(in_array($lottery_id,C('MONITOR_FOLLOW_LOTTERY_IDS')) && $type==self::FOLLOW_BET_ISSUE_START ){
            ApiLog('_add$lottery_id:'.$lottery_id,'FollowMonitor');
            $issue_info = D('Issue')->getIssueInfo($issue_id);
            $expire_time = date('Y-m-d H:i:s',strtotime($issue_info['issue_end_time']) + 4*60);
            $this->_redis->hSet('FollowMonitor',$lottery_id.'-'.$issue_id.'-expire_time',$expire_time);
        }
    }

    private function _delFollowMonitor($lottery_id,$issue_id,$type){
        if(in_array($lottery_id,C('MONITOR_FOLLOW_LOTTERY_IDS')) && $type==self::FOLLOW_BET_AWARD ){
            ApiLog('_del$lottery_id:'.$lottery_id,'FollowMonitor');
            $this->_redis->hDel('FollowMonitor',$lottery_id.'-'.$issue_id.'-expire_time');
        }
    }

    private function _isFollowed($lottery_id,$issue_id,$type){
        $followed_key = 'Followed:'.$lottery_id.':'.date('Y-m-d',time());
        if($this->_redis->sContains($followed_key,$issue_id.'-'.$type)){
            return true;
        }
        return false;
    }

    private function _addFollowed($lottery_id,$issue_id,$type){
        $followed_key = 'Followed:'.$lottery_id.':'.date('Y-m-d',time());
        $this->_redis->sAdd($followed_key,$issue_id.'-'.$type);
    }

    private function _isFollowedOfPersonal($issue_id,$fbi_id){
        M()->startTrans();
        $follow_detail =  D('FollowBetDetail')->lock(true)->where(array('issue_id'=>$issue_id,'fbi_id'=>$fbi_id))->find();
        M()->commit();
        return $follow_detail;
    }

    private function _executeFollowTask($issue_info,$follow_bet_detail_ids){
        set_time_limit(0);
        foreach($follow_bet_detail_ids as $follow_bet_detail_id){
            $follow_bet_detail = D('FollowBetInfoView')->getFollowBetDetailIdsByFbdId($follow_bet_detail_id);
            if(!$this->_checkFollowStatus($follow_bet_detail)){
                ApiLog('$follow_bet_detail_id:'.$follow_bet_detail_id.'状态异常','FollowWorker');
                continue;
            }
            //测试注释
            if($this->_isFollowedOfPersonal($issue_info['issue_id'],$follow_bet_detail['fbi_id'])){
                ApiLog('$follow_bet_detail_id:'.$follow_bet_detail_id.'已经追过','FollowWorker');
                continue;
            }

            if($this->_isOverTime($issue_info)){
                ApiLog('$follow_bet_detail_id:'.$follow_bet_detail_id.'状态超时','FollowWorker');
                continue;
            }
            $user_info 	= D('User')->getUserInfo($follow_bet_detail['uid']);

            if ($user_info['user_status'] != C('USER_STATUS.ENABLE')) {
                $this->_notifyWarningMsg('$follow_bet_detail_id:'.$follow_bet_detail_id.'用户被禁用');
                ApiLog('$follow_bet_detail_id:'.$follow_bet_detail_id.'用户被禁用','FollowWorker');
                continue;
            }

            $order_info 	= D('Order')->getOrderInfo($follow_bet_detail['first_order_id']);
            if(empty($order_info)){
                $order_info 	= D('OrderBackup')->getOrderInfo($follow_bet_detail['first_order_id']);
                if(empty($order_info)){
                    $this->_notifyWarningMsg('$follow_bet_detail_id:'.$follow_bet_detail_id.'初始订单不存在');
                    ApiLog('$follow_bet_detail_id:'.$follow_bet_detail_id.'初始订单不存在','FollowWorker');
                    continue;
                }
            }

            $frozenBalance = D('UserAccount')->getFrozenBalance($order_info['uid']);
            $order_reduced_amount = $this->_getOrderReduceAmount($follow_bet_detail['fbi_id']);
            $order_reduce_remain_amount = bcsub($order_info['order_reduce_consumption'],$order_reduced_amount);
            if (bcadd($frozenBalance,$order_reduce_remain_amount) < $follow_bet_detail['order_total_amount']) {
                $this->_notifyWarningMsg('$follow_bet_detail_id:'.$follow_bet_detail_id.'限制金额不够');
                ApiLog('$follow_bet_detail_id:'.$follow_bet_detail_id.'限制金额不够','FollowWorker');
                continue;
            }

            $follow_status = $this->_followBetOrder($follow_bet_detail,$order_info,$issue_info,$user_info);
            if(!$follow_status){
                ApiLog('$follow_bet_detail_id:'.$follow_bet_detail_id.'追号失败','FollowWorker');
                $this->_notifyWarningMsg('$follow_bet_detail_id:'.$follow_bet_detail_id.'追号失败');
                continue;
            }
        }
    }

    private function _lock($lottery_id,$issue_id,$type,$expire_time = 60){
        $redis_key = $this->_getLockKey($lottery_id,$issue_id,$type);
        $is_lock = $this->_redis->setnx($redis_key,time()+$expire_time);
        if(!$is_lock){
            $lock_time = $this->_redis->get($redis_key);
            if(time()>$lock_time){
                $this->_unlock($lottery_id,$issue_id,$type);
                $is_lock = $this->_redis->setnx($redis_key,time()+$expire_time);
            }
        }
        return $is_lock?true:false;
    }

    private function _getLockKey($lottery_id,$issue_id,$type){
        return 'follow_bet:'.$lottery_id.':lock:'.$issue_id.':'.$type;
    }

    private function _unlock($lottery_id,$issue_id,$type){
        return $this->_redis->del($this->_getLockKey($lottery_id,$issue_id,$type));
    }

    private function _followBetOrder($follow_bet_detail,$order_info,$issue_info,$user_info){
        $order_data = $this->_buildOrderData($follow_bet_detail,$order_info,$issue_info);
        M()->startTrans();
        $new_order_id = D('Order')->add($order_data);
        $tickets_list = $this->_addTickets($issue_info['lottery_id'], $follow_bet_detail['first_order_id'], $order_info['uid'], $issue_info['issue_id'], $new_order_id ,$follow_bet_detail['order_multiple'],$follow_bet_detail['fbi_is_independent'],$follow_bet_detail['fbd_bet_number_list']);
        if (empty($new_order_id) || empty($tickets_list)) {
            $this->_notifyWarningMsg('$follow_bet_detail:'.json_encode($follow_bet_detail).'empty($new_order_id)164');
            M()->rollback();
            return false;
        }

        $limitBetCode = $this->limitBetNum($issue_info['issue_id'],$issue_info['lottery_id'],$tickets_list);
        if($limitBetCode!=C('ERROR_CODE.SUCCESS')){
            ApiLog('$follow_bet_detail:'.json_encode($follow_bet_detail).'限号停追','FollowWorker');
            $this->_notifyWarningMsg('$follow_bet_detail:'.json_encode($follow_bet_detail).'限号停追');
            M()->rollback();
            $order_id = D('FollowBetInfoView')->getFollowBetDetailLastOrderId($follow_bet_detail['fbi_id']);
            A('StopFollowBet')->cancelFollowBet($order_id,C('FOLLOW_BET_INFO_STATUS.CANCEL'));
            return false;
        }




        //已追号金额    是否最后一期  更新是否当前追号期次  订单号   彩期ID
        $update_next_status = true;
        $update_ending_status = true;
        $update_status = D('FollowBetDetail')->updateCurrentData($follow_bet_detail['fbd_id'],$issue_info['issue_id'],$new_order_id);
        $is_last_issue = D('FollowBetDetail')->isLastIssue($follow_bet_detail['fbi_id'],$follow_bet_detail['fbd_index']);
        if(!$is_last_issue){
            $update_next_status = D('FollowBetDetail')->updateNextData($follow_bet_detail['fbi_id'],$follow_bet_detail['fbd_index']);
        }

        $add_follow_amount = D('FollowBetInfo')->addFollowAmount($follow_bet_detail['fbi_id'],$follow_bet_detail['order_total_amount']);
        if($is_last_issue){
            $update_ending_status = D('FollowBetInfo')->changeFollowBetInfoStatus($follow_bet_detail['fbi_id'],C('FOLLOW_BET_INFO_STATUS.ENDING'));
        }
        if(!($update_status && $add_follow_amount && $update_next_status && $update_ending_status)){
            $this->_notifyWarningMsg('$follow_bet_detail:'.json_encode($follow_bet_detail).'$update_status:false164');
            M()->rollback();
            return false;
        }
        M()->commit();

        $printTickets = array();
        foreach ($tickets_list as $ticket) {
            $printTickets[] = $this->buildNumberTicketItemForPrintout($ticket['ticket_seq'], $ticket['play_type'],
                $ticket['bet_type'], $ticket['bet_number'], $ticket['stake_count'], $ticket['total_amount'], $ticket['ticket_multiple']);
        }

        $print_out_result = $this->printOutTicket($user_info, $issue_info['issue_no'], $new_order_id, $issue_info['lottery_id'], $printTickets, $order_data['order_multiple']);
        if(!$print_out_result){
            for($i=0;$i<3;$i++){
                $print_out_result = $this->printOutTicket($user_info, $issue_info['issue_no'], $new_order_id,  $issue_info['lottery_id'], $printTickets, $order_data['order_multiple']);
                if($print_out_result){
                    break;
                }
            }
        }
        if($print_out_result){
            $order_pay_money = bcsub($follow_bet_detail['order_total_amount'],$order_data['order_reduce_amount']);
            if($order_pay_money > 0){
                $deduct_result = D('UserAccount')->deductFrozenBalance($order_info['uid'],$order_pay_money, $new_order_id, C('USER_ACCOUNT_LOG_TYPE.BET'));
            }else{
                $deduct_result = true;
            }
        }
        if (!$print_out_result || !$deduct_result) {
            $this->_notifyWarningMsg('$follow_bet_detail:'.json_encode($follow_bet_detail).'189!$print_out_result');
            return false;
        }
        $change_status = D('FollowBetDetail')->changeFollowBetDetailStatus($follow_bet_detail['fbd_id'],C('FOLLOW_BET_DETAIL_STATUS.FOLLOWED'));
        if ($change_status===false) {
            $this->_notifyWarningMsg('$follow_bet_detail:'.json_encode($follow_bet_detail).'194!$change_status');
            return false;
        }

        $save_status = D('Order')->saveOrderStatus($new_order_id, C('ORDER_STATUS.PAYMENT_SUCCESS'));
        if ($save_status===false) {
            $this->_notifyWarningMsg('$follow_bet_detail:'.json_encode($follow_bet_detail).'200!$save_status');
            return false;
        }

        return true;
    }

    private function _buildOrderData($follow_bet_detail,$order_info,$issue_info){
        $orderSku	= buildOrderSku($follow_bet_detail['uid']);
        $order_coupon_total_amount = $order_info['order_coupon_consumption'];
        $order_reduce_total_amount = $order_info['order_reduce_consumption'];

        $followed_amount = $follow_bet_detail['followed_amount'];
        if($order_coupon_total_amount){
            if(bcsub($order_coupon_total_amount,$followed_amount)>=$follow_bet_detail['order_total_amount']){
                $order_coupon_amount = $follow_bet_detail['order_total_amount'];
            }elseif(bcsub($order_coupon_total_amount,$followed_amount)<$follow_bet_detail['order_total_amount'] && bcsub($order_coupon_total_amount,$followed_amount) > 0){
                $order_coupon_amount = bcsub($order_coupon_total_amount,$followed_amount);
            }else{
                $order_coupon_amount = 0;
            }
            $order_coupon_id = $order_info['user_coupon_id'] ;
        }else{
            $order_coupon_amount = 0;
            $order_coupon_id = 0 ;
        }

        if($order_reduce_total_amount){
            $no_follow_amount = bcsub($follow_bet_detail['follow_total_amount'],$follow_bet_detail['followed_amount']);
            if(bcadd($follow_bet_detail['order_total_amount'],$order_reduce_total_amount) > $no_follow_amount){
                //开始使用套餐优惠金额
                $order_reduced_amount = $this->_getOrderReduceAmount($follow_bet_detail['fbi_id']);
                $order_reduce_remain_amount = bcsub($order_reduce_total_amount,$order_reduced_amount);
                if(bccomp($follow_bet_detail['order_total_amount'],$order_reduce_remain_amount,2) < 0){
                    if(bcmod($order_reduce_remain_amount,$follow_bet_detail['order_total_amount']) > 0){
                        $order_reduce_amount = bcmod($order_reduce_remain_amount,$follow_bet_detail['order_total_amount']);
                    }else{
                        $order_reduce_amount = $follow_bet_detail['order_total_amount'];
                    }
                }else{
                    $order_reduce_amount = $order_reduce_remain_amount;
                }
            }else{
                $order_reduce_amount = 0;
            }
        }else{
            $order_reduce_amount = 0;
        }

        $order_identity = 'FOLLOW'.date("YmdHis").$order_info['order_id'].$order_info['uid'].$follow_bet_detail['index'];
        return array(
            'order_sku' => $orderSku,
            'uid' => $order_info['uid'],
            'order_create_time' => getCurrentTime(),
            'order_modify_time' => getCurrentTime(),
            'order_total_amount'=> $follow_bet_detail['order_total_amount'],
            'order_multiple' => $follow_bet_detail['order_multiple'],
            'issue_id' => $issue_info['issue_id'],
            'first_issue_id' => $issue_info['issue_id'],
            'order_status' => C('ORDER_STATUS.UNPAID'),
            'user_coupon_id' => $order_coupon_id,
            'user_coupon_amount' => $order_coupon_amount,
            'order_coupon_consumption' => $order_info['order_coupon_consumption'],
            'lottery_id' => $issue_info['lottery_id'],
            'current_follow_times' => $follow_bet_detail['fbd_index'],
            'order_identity' => $order_identity,
            'follow_bet_id' => $follow_bet_detail['fbd_id'],
            'play_type' => $order_info['play_type'],
            'bet_type' => $order_info['bet_type'],
            'order_type' => ORDER_TYPE_OF_FOLLOW,
            'order_reduce_amount' => $order_reduce_amount,
            'order_reduce_consumption' => $order_info['order_reduce_consumption'],
            'lp_id' => $order_info['lp_id'],
        );
    }

    private function _getOrderReduceAmount($fbi_id){
        $order_ids = D('FollowBetDetail')->getFollowBetOrderListByFbiId($fbi_id);
        $order_reduce_amount = D('Order')->getTotalReduceAmountByIds($order_ids);
        return $order_reduce_amount;
    }

    public function getOrderUsedCoupon($fbi_id){
        $order_ids = D('FollowBetDetail')->getFollowBetOrderListByFbiId($fbi_id);
        $order_used_coupon = D('Order')->getTotalUsedCouponByIds($order_ids);
        return $order_used_coupon;
    }

    private function _addTickets($lottery_id, $order_id, $uid, $issue_id, $new_order_id,$order_multiple,$is_independent = 0,$bet_number_list_string = '') {
        if (!$new_order_id) {
            return false;
        }
        $ticketModel = getTicktModel($lottery_id);
        if(empty($bet_number_list_string)){
            $is_old = true;
            $tickets = $ticketModel->getTicketsByOrderId($order_id,$uid);
            if (!$tickets) {
                return false;
            }
            foreach ($tickets as $key => $ticket) {
                $tickets[$key]['once_ticket_amount'] = $ticket['total_amount']/$ticket['ticket_multiple'];
                if(isset($bet_number_list[$ticket['bet_number'].'-'.$ticket['play_type']])){
                    unset($tickets[$key]);
                    continue;
                }
                $bet_number_list[$ticket['bet_number'].'-'.$ticket['play_type']] =  $ticket['bet_number'];
            }
        }else{
            $is_old = false;
            $tickets = json_decode($bet_number_list_string,true);
        }
        $tickets_list = $this->_buildNewTicketData($tickets, $uid, $issue_id, $new_order_id, $lottery_id,$order_multiple,$is_old);
        $add_ticket_list_result = $ticketModel->addAll($tickets_list);
        return  ( $add_ticket_list_result ? $tickets_list : false );
    }


    private function _buildNewTicketData(array $tickets, $uid, $issue_id, $order_id, $lottery_id,$order_multiple,$is_old) {
        $ticket_data_list = array();
        $verifyNumber = Factory::createVerifyObj($lottery_id);
        $ticketModel = getTicktModel($lottery_id);
        $max_multiple = getMaxMultipleByLotteryId($lottery_id);
        $ticket_seq = 1;
        foreach ($tickets as $key => $ticket) {
            if($is_old){
                $once_ticket_amount = $ticket['total_amount']/$ticket['ticket_multiple'];
            }else{
                $once_ticket_amount = $ticket['total_amount'];
            }
            if ($order_multiple > $max_multiple) {
                $limit_multiple = $max_multiple;
            } else {
                $limit_multiple = $order_multiple;
            }

            $limit_ticket_amount = $once_ticket_amount * $limit_multiple;
            if ($limit_ticket_amount > BET_TICKET_AMOUNT_LIMIT) {
                $max_once_ticket_multiple = floor(BET_TICKET_AMOUNT_LIMIT / $once_ticket_amount);
                $once_ticket_multiple = $max_once_ticket_multiple;
            } else {
                $once_ticket_multiple = $limit_multiple;
            }

            $devide_ticket_num = ceil($order_multiple / $once_ticket_multiple);
            for($i = 0; $i < $devide_ticket_num; $i++) {
                if ($i == $devide_ticket_num - 1) {
                    $ticket_multiple = $order_multiple - ($devide_ticket_num - 1) * $once_ticket_multiple;
                } else {
                    $ticket_multiple = $once_ticket_multiple;
                }
                if($is_old){
                    $total_amount = $once_ticket_amount * $ticket_multiple;
                }else{
                    $total_amount = $once_ticket_amount * $ticket_multiple;
                }

                $sortBetNumber = $verifyNumber->formatBetNumber($ticket['bet_number'], $ticket['play_type']);
                $ticket_data_list[] = $ticketModel->buildTicketData($uid, $issue_id, $sortBetNumber,
                    $ticket['play_type'], $ticket['stake_count'], $total_amount, $order_id, $ticket_seq, $ticket['bet_type'], $ticket_multiple, $issue_id, $lottery_id);
                $ticket_seq++;
            }
        }
        return $ticket_data_list;
    }

    private function _isOverTime($issue_info){
        $lottery_info = D('Lottery')->getLotteryInfo($issue_info['lottery_id']);
        $beforeDeadline = (strtotime($issue_info['issue_end_time']) - time() > $lottery_info['lottery_ahead_endtime']);
        if (!$beforeDeadline) {
            return true;
        }
        return false;
    }

    private function _checkFollowStatus($follow_bet_detail){
        if(!empty($follow_bet_detail['order_id'])){
            return false;
        }
        if($follow_bet_detail['fbd_status'] == C('FOLLOW_BET_DETAIL_STATUS.FOLLOWED')){
            return false;
        }
        return true;
    }

    private function _createFollowTaskQueue($lottery_id,$type){
        return  D('FollowBetInfoView')->getFollowBetDetailIds($lottery_id,$type);
    }

    private function _notifyWarningMsg($msg=''){
        $data = array(
            'telephone_list' => array('18705085505'),
            'send_data' => array(get_cfg_var('PROJECT_RUN_MODE').$msg),
            'template_id' => '82542',
        );
        sendTelephoneMsgNew($data);
    }



}