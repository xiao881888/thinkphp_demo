<?php
namespace Home\Controller;
use Home\Controller\GlobalController;

class FollowBetController extends GlobalController {
    
    public function cancelFollowBet($api) {
        $userInfo = $this->getAvailableUser($api->session);
        $uid = $userInfo['uid'];
        
        $userAccount = D('UserAccount')->getUserAccount($uid);
        
        $orderInfo = D('Order')->getOrderInfo($api->order_id);
        $userOwen = ($orderInfo['uid'] == $uid);
        \AppException::ifNoExistThrowException($userOwen, C('ERROR_CODE.ORDER_OWEN_ERROR'));

        $order_reduce_consumption = empty($orderInfo['order_reduce_consumption']) ? 0 : $orderInfo['order_reduce_consumption'];
        
        $followBetInfoDetails = D('FollowBetInfoView')->getFollowBetDetailByOrderId($api->order_id);
        if(empty($followBetInfoDetails)){
            $followInfo = D('FollowBet')->getFollowBetInfo($orderInfo['follow_bet_id']);
            if(empty($followInfo)){
                \AppException::ifNoExistThrowException($followBetInfoDetails, C('ERROR_CODE.FOLLOW_BET_CAN_NOT_CANCEL'));
            }
            return $this->cancelFollowBetOld($api);
        }

        $follow_worker_obj = new FollowWorkerController();
        $used_coupon_amount = $follow_worker_obj->getOrderUsedCoupon($followBetInfoDetails['fbi_id']);
        $refund_coupon_amount = bcsub($orderInfo['order_coupon_consumption'],$used_coupon_amount);
        
        $no_follow_amount = bcsub($followBetInfoDetails['follow_total_amount'] ,$followBetInfoDetails['followed_amount']);   // 要返还的钱
        if($order_reduce_consumption <= 0){
            $refund_amount = $no_follow_amount;
            $refund_money = $refund_amount - $refund_coupon_amount;
        }else{
            if($no_follow_amount > $order_reduce_consumption){
                $refund_amount = bcsub($no_follow_amount,$order_reduce_consumption);
                $refund_money = $refund_amount - $refund_coupon_amount;
            }else{
                $refund_amount = 0;
                $refund_coupon_amount = 0;
                $refund_money = 0;
            }
        }
        
        $allowFollowStatus = $refund_amount>=0 && $followBetInfoDetails['fbi_status'] == C('FOLLOW_BET_INFO_STATUS.ON_GOING');
        \AppException::ifNoExistThrowException($allowFollowStatus, C('ERROR_CODE.FOLLOW_BET_CAN_NOT_CANCEL'));
        
        $frozenBalanceEnough = ($userAccount['user_account_frozen_balance'] >= $refund_amount);
        \AppException::ifNoExistThrowException($frozenBalanceEnough, C('ERROR_CODE.FROZEN_BALANCE_NO_ENOUGH'));
        
        $cancel_result = $follow_worker_obj->cancelFollowBetInfo($uid, $followBetInfoDetails['fbi_id'], $refund_money, $refund_coupon_amount, $orderInfo['user_coupon_id'],C('FOLLOW_BET_INFO_STATUS.CANCEL'));
        if(!$cancel_result){
        	throw new \Think\Exception('', C('ERROR_CODE.DATABASE_ERROR'));
        }
        return array(   'result' => '',
                        'code'   => C('ERROR_CODE.SUCCESS'));
    }

    public function cancelFollowBetOld($api) {
        $userInfo = $this->getAvailableUser($api->session);
        $uid = $userInfo['uid'];

        $userAccount = D('UserAccount')->getUserAccount($uid);

        $orderInfo = D('Order')->getOrderInfo($api->order_id);
        $userOwen = ($orderInfo['uid'] == $uid);
        \AppException::ifNoExistThrowException($userOwen, C('ERROR_CODE.ORDER_OWEN_ERROR'));

        $followInfo = D('FollowBet')->getFollowBetInfo($orderInfo['follow_bet_id']);

        $refund_amount = $followInfo['follow_remain_times'] * $orderInfo['order_total_amount'];   // 要返还的钱

        $allowFollowStatus = $refund_amount>0 && $followInfo['follow_status'] == C('FOLLOW_STATUS.NORMAL');
        \AppException::ifNoExistThrowException($allowFollowStatus, C('ERROR_CODE.FOLLOW_BET_CAN_NOT_CANCEL'));

        $frozenBalanceEnough = ($userAccount['user_account_frozen_balance'] >= $refund_amount);
        \AppException::ifNoExistThrowException($frozenBalanceEnough, C('ERROR_CODE.FROZEN_BALANCE_NO_ENOUGH'));

        $refund_money = $orderInfo['order_total_amount'] + ($followInfo['follow_times'] * $orderInfo['order_total_amount']) - $orderInfo['order_coupon_consumption'];

        $cancel_result = $this->_cancelFollowBet($uid, $orderInfo['follow_bet_id'], $refund_amount, $refund_money, $orderInfo['user_coupon_id']);
        if($cancel_result!=C('ERROR_CODE.SUCCESS')){
            throw new \Think\Exception('', $cancel_result);
        }
        return array(   'result' => '',
            'code'   => C('ERROR_CODE.SUCCESS'));
    }


    private function _cancelFollowBet($uid, $followBetId, $refund_amount, $refund_money, $userCouponId) {
        M()->startTrans();
        $saveStatus = D('FollowBet')->saveFollowBetStatus($followBetId, C('FOLLOW_STATUS.CANCEL'));
        if(empty($saveStatus)){
            M()->rollback();
            return C('ERROR_CODE.ORDER_OWEN_ERROR');
        }

        $refund_coupon_amount = $refund_amount - $refund_money;
        if($refund_coupon_amount){
            $result = D('UserCoupon')->increaseCoupon($userCouponId, $refund_coupon_amount);
            if(empty($result)){
                M()->rollback();
                return C('ERROR_CODE.DATABASE_ERROR');
            }
        }

        $refund_result = D('UserAccount')->refundFollowMoney($uid, $refund_amount,$refund_money,$refund_coupon_amount, $followBetId, C('USER_ACCOUNT_LOG_TYPE.CANCEL_FOLLOW'));
        if(empty($refund_result)){
            M()->rollback();
            return C('ERROR_CODE.DATABASE_ERROR');
        }
        M()->commit();
        return C('ERROR_CODE.SUCCESS');
    }

    public function getFollowBetDetail($api){
        $fbi_id = $api->follow_bet_id;
        $fbi_info = D('FollowBetInfo')->getFollowInfoById($fbi_id);
        \AppException::ifNoExistThrowException($fbi_info,C('ERROR_CODE.ORDER_NO_EXIST'));
        $follow_bet_detail_current = D('FollowBetInfoView')->getFollowBetDetailCurrentInfo($fbi_id);
        if(empty($follow_bet_detail_current)){
            $fbd_id = D('FollowBetDetail')->getLastFollowDetailByFbiId($fbi_id);
            $follow_bet_detail_current = D('FollowBetInfoView')->getFollowBetDetailIdsByFbdId($fbd_id);
        }
        \AppException::ifNoExistThrowException($follow_bet_detail_current,C('ERROR_CODE.ORDER_NO_EXIST'));
        $is_last_issue = D('FollowBetDetail')->isLastIssue($follow_bet_detail_current['fbi_id'],$follow_bet_detail_current['fbd_index']);
        if(!$is_last_issue){
            $follow_bet_detail_pre = D('FollowBetInfoView')->getFollowBetDetailPreInfo($fbi_id);
        }else{
            $follow_bet_detail_pre = $follow_bet_detail_current;

        }
        \AppException::ifNoExistThrowException($follow_bet_detail_pre,C('ERROR_CODE.ORDER_NO_EXIST'));
        $lottery_name = D('Lottery')->getLotteryNameById($fbi_info['lottery_id']);
        $lottery_info = D('Lottery')->getLotteryInfo($fbi_info['lottery_id']);
        $order_ids = D('FollowBetDetail')->getOrderIdsByFbiId($fbi_id);
        $winning_amount = empty(D('Order')->getOrderTotalWinningAmountByIds($order_ids)) ? 0 : D('Order')->getOrderTotalWinningAmountByIds($order_ids);
        $total_amount = $fbi_info['follow_total_amount'];
        $follow_bet_info_status_desc = A('Order')->getFollowBetInfoStatusDesc($follow_bet_detail_current,$order_ids);
        $order_info = D('Order')->getOrderInfo($fbi_info['order_id']);
        $refund_amount = $this->_cancelFollowBetRefundAmount($follow_bet_detail_current,$order_info['order_reduce_consumption']);
        $follow_detail = $this->_getFollowDetail($follow_bet_detail_current);
        $current_issue_no = D('Issue')->getIssueNoById($follow_bet_detail_pre['issue_id']);

        return array(
            'result' => array(
                'lottery_id' => $fbi_info['lottery_id'],
                'lottery_name' => $lottery_info['lottery_name'],
                'lottery_image' => $lottery_info['lottery_image'],
                'winnings_bonus' => $winning_amount,
                'total_amount' => $total_amount,
                'status' => $follow_bet_info_status_desc['status'],
                'status_desc' => $follow_bet_info_status_desc['status_desc'],
                'status_desc_detail' => $this->_getFollowBetInfoStatusDesc($follow_bet_detail_current),
                'follow_refund' => $refund_amount,
                'follow_times' => $fbi_info['follow_times'],
                'current_follow_times' => ($follow_bet_detail_pre['fbd_index']),
                'is_win_stop' => empty($fbi_info['fbi_type']) ? 0 : 1,
                'bet_time' => strtotime($fbi_info['fbi_createtime']),
                'follow_detail' => $follow_detail,
                'current_issue_no'=>emptyToStr($current_issue_no),
            ),
            'code'   => C('ERROR_CODE.SUCCESS')
        );
    }

    private function _getFollowDetail($follow_bet_detail_current){
        $data = array();
        $follow_detail_list = D('FollowBetDetail')->getFollowBetDetailListByFbiId($follow_bet_detail_current['fbi_id']);
        foreach ($follow_detail_list as $follow_detail){
            $follow_bet_detail_status_desc = $this->_getFollowBetDetailStatusDesc($follow_bet_detail_current,$follow_detail['order_id'],$follow_detail);
            if(!empty($follow_detail['issue_id'])){
                $issue_no = D('Issue')->getIssueNoById($follow_detail['issue_id']);
            }else{
                $last_issue_id = D('FollowBetInfoView')->getFollowBetDetailLastIssueId($follow_detail['fbi_id']);
                $current_index = $follow_bet_detail_current['fbd_index'];
                $issue_limit = $follow_detail['fbd_index'] - $current_index + 1;
                $issue_no_list = D('Issue')->getIssueNoList($follow_detail['lottery_id'],$last_issue_id,$issue_limit);
                $issue_no = array_pop($issue_no_list);
            }
            $order_winnings_bonus = D('Order')->getOrderWinningAmountById($follow_detail['order_id']);
            $data[] = array(
                'order_id' => $follow_detail['order_id'],
                'amount' => $follow_detail['order_total_amount'],
                'winnings_bonus' => empty($order_winnings_bonus) ? 0 : $order_winnings_bonus,
                'issue_no' => emptyToStr($issue_no),
                'status' => $follow_bet_detail_status_desc['status'],
                'status_desc' => $follow_bet_detail_status_desc['status_desc'],
            );
        }
        return $data;
    }


    private function _cancelFollowBetRefundAmount($follow_bet_detail,$order_reduce_consumption){
        //获取未追号的金额
        $no_follow_amount = bcsub($follow_bet_detail['follow_total_amount'] ,$follow_bet_detail['followed_amount']);   // 要返还的钱
        if($no_follow_amount > $order_reduce_consumption){
            //退的金额=未追金额-订单优惠金额
            $no_follow_amount = bcsub($no_follow_amount,$order_reduce_consumption);;
        }
        return $no_follow_amount;

    }

    private function _getFollowBetDetailStatusDesc($follow_bet_info_view,$order_id,$follow_detail){
        $data = array();
        switch ($follow_detail['fbd_status']){
            case C('FOLLOW_BET_DETAIL_STATUS.NO_FOLLOW') :
                if($follow_bet_info_view['fbi_status'] == C('FOLLOW_BET_INFO_STATUS.ON_GOING')){
                    $data['status'] = 10;
                    $data['status_desc'] = C('FOLLOW_BET_DETAIL_API_STATUS_DESC.NO_BEGIN');
                }else{
                    $data['status'] = 11;
                    $data['status_desc'] = C('FOLLOW_BET_DETAIL_API_STATUS_DESC.CANCEL');
                }
                break;
            case C('FOLLOW_BET_DETAIL_STATUS.FOLLOWED') :
                $order_info = D('Order')->getOrderInfo($order_id);
                $data['status_desc'] = A('Order')->getOrderStatusDesc($order_info['order_status'],$order_info['order_winnings_status'], $order_info['order_distribute_status']);
                $data['status'] = D('Order')->getStatus($order_info['order_status'], $order_info['order_winnings_status'], $order_info['order_distribute_status']);
                /*if($order_info['order_winnings_status'] == C('ORDER_WINNINGS_STATUS.WAITING')){
                    $data['status'] = C('FOLLOW_BET_DETAIL_API_STATUS.WAITING_PRIZE');
                    //$data['status_desc'] = C('FOLLOW_BET_DETAIL_API_STATUS_DESC.WAITING_PRIZE');
                }elseif($order_info['order_winnings_status'] == C('ORDER_WINNINGS_STATUS.NO')){
                    $data['status'] = C('FOLLOW_BET_DETAIL_API_STATUS.NO_PRIZE');
                    //$data['status_desc'] = C('FOLLOW_BET_DETAIL_API_STATUS_DESC.NO_PRIZE');
                }elseif($order_info['order_winnings_status'] == C('ORDER_WINNINGS_STATUS.YES') || $order_info['order_winnings_status'] == C('ORDER_WINNINGS_STATUS.PART')){
                    $data['status'] = C('FOLLOW_BET_DETAIL_API_STATUS.PRIZE');
                    //$data['status_desc'] = C('FOLLOW_BET_DETAIL_API_STATUS_DESC.PRIZE');
                }*/
                break;
        }
        return $data;
    }

    private function _getFollowBetInfoStatusDesc($follow_bet_detail){
        $desc = '';
        switch ($follow_bet_detail['fbi_status']){
            case C('FOLLOW_BET_INFO_STATUS.ON_GOING') :
                if($follow_bet_detail['type'] == C('FOLLOW_BET_INFO_TYPE.FOLLOW_ISSUE')){
                    $desc = '完成后停追';
                }elseif($follow_bet_detail['type'] == C('FOLLOW_BET_INFO_TYPE.PRIZE_STOP')){
                    $desc = '中奖后停追';
                }elseif($follow_bet_detail['type'] == C('FOLLOW_BET_INFO_TYPE.WIN_STOP_AMOUNT')){
                    $desc = '中奖≥'.$follow_bet_detail['fbi_win_stop_amount'].'后停追';
                }
                break;
            case C('FOLLOW_BET_INFO_STATUS.PRIZE_STOP') :
                $desc = '已中奖停追';
                break;
            case C('FOLLOW_BET_INFO_STATUS.CANCEL') :
                $desc = '已手动停追';
                break;
            case C('FOLLOW_BET_INFO_STATUS.ENDING') :
                $desc = '';
                break;
        }
        return $desc;
    }

    private function _getOrderIdsIsPrize($order_winning_status_list){
        return in_array(C('ORDER_WINNINGS_STATUS.YES'),$order_winning_status_list) || in_array(C('ORDER_WINNINGS_STATUS.PART'),$order_winning_status_list);
    }


    
    
}