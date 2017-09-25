<?php
namespace Home\Controller;
use Home\Util\Factory;

class StopFollowBetController extends BettingBaseController {

    const FOLLOW_BET_ISSUE_START = 1;
    const FOLLOW_BET_AWARD = 2;
    const FOLLOW_BET_PRIZED = 3;

    public function trigger($lottery_id,$type,$issue_id){
        $issue_info = D('Issue')->getIssueInfo($issue_id);
        if($issue_info < 9){
            ApiLog('该彩期未算奖$issue_id:'.$issue_id,'StopFollowBet');
            return false;
        }

        if(!in_array($type,array(self::FOLLOW_BET_AWARD,self::FOLLOW_BET_PRIZED))){
            ApiLog('$type:'.$type.'类型通知错误错误','StopFollowBet');
            return false;
        }


        $winning_order_list = D('CalculateResult')->where(array('lottery_id'=>$lottery_id,'issue_id'=>$issue_id))
                                            ->getField('order_id',true);
        ApiLog('$lottery_id:'.$lottery_id,'StopFollowBet');
        ApiLog('$winning_order_list:'.print_r($winning_order_list,true),'StopFollowBet');

        $fbi_ids = D('FollowBetInfo')->getFollowInfoListOfPrizeStop($lottery_id);
        ApiLog('$fbi_ids:'.print_r($fbi_ids,true),'StopFollowBet');
        foreach($fbi_ids as $fbi_id){
            $order_id = D('FollowBetInfoView')->getFollowBetDetailLastOrderId($fbi_id);
            $is_sure_stop = $this->_checkSureStop($fbi_id,$order_id,$type);
            if(!$is_sure_stop){
                ApiLog('$order_id:'.$order_id.'不满足停追的条件','StopFollowBet');
                continue;
            }
            ApiLog('$order_id:'.$order_id,'StopFollowBet');
            if(in_array($order_id,$winning_order_list)){
                $this->cancelFollowBet($order_id,C('FOLLOW_BET_INFO_STATUS.PRIZE_STOP'));
            }
        }

        A('FollowWorker')->trigger($lottery_id,$type,$issue_id);
        return true;

    }

    private function _checkSureStop($fbi_id,$order_id,$type){
        if($type == self::FOLLOW_BET_AWARD){
            $fbi_info = D('FollowBetInfo')->getFollowInfoById($fbi_id);//
            if($fbi_info['fbi_type'] == C('FOLLOW_BET_INFO_TYPE.PRIZE_STOP')){
                return true;
            }
            ApiLog('$order_id:'.$order_id.'  010','StopFollowBet');
            return false;
        }

        $order_info = D('Order')->getOrderInfo($order_id);
        if(!in_array($order_info['order_winnings_status'],array(C('ORDER_WINNINGS_STATUS.YES'),C('ORDER_WINNINGS_STATUS.PART')))){
            ApiLog('$order_id:'.$order_id.'  000','StopFollowBet');
            return false;
        }

        $fbi_info = D('FollowBetInfo')->getFollowInfoById($fbi_id);//
        if($fbi_info['fbi_type'] == C('FOLLOW_BET_INFO_TYPE.WIN_STOP_AMOUNT')){
            if(bccomp($order_info['order_winnings_bonus'],$fbi_info['fbi_win_stop_amount'],2) > -1){
                return true;
            }
        }
        ApiLog('$order_id:'.$order_id.'  111','StopFollowBet');
        return false;
    }

    public function limitBetStopFollowBet($lottery_id){
        $fbi_ids = D('FollowBetInfo')->getFollowInfoListOfOngoing($lottery_id);
        ApiLog('$fbi_ids'.print_r($fbi_ids,true),'stopFollowBet');
        foreach($fbi_ids as $fbi_id){
            $order_id = D('FollowBetInfoView')->getFollowBetDetailLastOrderId($fbi_id);
            ApiLog('$order_id:'.$order_id.'limitBetStopFollowBet','stopFollowBet');
            $this->cancelFollowBet($order_id,C('FOLLOW_BET_INFO_STATUS.CANCEL'));
        }
    }

    public function prizeStopFollowBet($order_info){
        $order_id = $order_info['order_id'];
        ApiLog('$order_id:'.$order_id.'prizeStopFollowBet','stopFollowBet');
        $follow_bet_detail = D('FollowBetInfoView')->getFollowBetDetailByOrderId($order_id,C('FOLLOW_BET_INFO_TYPE.PRIZE_STOP'));
        if(!empty($follow_bet_detail)){
            $this->cancelFollowBet($order_id,C('FOLLOW_BET_INFO_STATUS.PRIZE_STOP'));
        }
    }

    public function cancelFollowBet($order_id,$type){
        $orderInfo = D('Order')->getOrderInfo($order_id);
        ApiLog('$order_id:'.$order_id.'订单取消追号','stopFollowBet');
        if(empty($orderInfo)){
            $this->_notifyWarningMsg('$order_id:'.$order_id.'订单为空');
            ApiLog('$order_id:'.$order_id.'订单为空','stopFollowBet');
            return false;
        }
        $order_reduce_consumption = empty($orderInfo['order_reduce_consumption']) ? 0 : $orderInfo['order_reduce_consumption'];

        $followBetInfoDetails = D('FollowBetInfoView')->getFollowBetDetailByOrderId($orderInfo['order_id']);
        if(empty($followBetInfoDetails)){
            $this->_notifyWarningMsg('$order_id:'.$order_id.'追号信息不存在');
            ApiLog('$order_id:'.$order_id.'追号信息不存在','stopFollowBet');
            return false;
        }

        if($followBetInfoDetails['fbi_status'] != C('FOLLOW_BET_INFO_STATUS.ON_GOING')){
            ApiLog('$order_id:'.$order_id.'该状态不能允许取消','stopFollowBet');
            return false;
        }

        $used_coupon_amount = A('FollowWorker')->getOrderUsedCoupon($followBetInfoDetails['fbi_id']);
        $refund_coupon_amount = bcsub($orderInfo['order_coupon_consumption'],$used_coupon_amount);

        //获取未追号的金额
        $no_follow_amount = bcsub($followBetInfoDetails['follow_total_amount'] ,$followBetInfoDetails['followed_amount']);   // 要返还的钱
        if($order_reduce_consumption <= 0){
            //没有订单优惠金额
            $refund_amount = $no_follow_amount;
            $refund_money = $refund_amount - $refund_coupon_amount;
        }else{
            if($no_follow_amount > $order_reduce_consumption){
                //退的金额=未追金额-订单优惠金额
                $refund_amount = bcsub($no_follow_amount,$order_reduce_consumption);
                $refund_money = $refund_amount - $refund_coupon_amount;
            }else{
                $refund_amount = 0;
                $refund_coupon_amount = 0;
                $refund_money = 0;
            }
        }

        $allowFollowStatus = $refund_amount>=0 && $followBetInfoDetails['fbi_status'] == C('FOLLOW_BET_INFO_STATUS.ON_GOING');
        if(empty($allowFollowStatus)){
            $this->_notifyWarningMsg('$order_id:'.$order_id.'$allowFollowStatus非法');
            ApiLog('$order_id:'.$order_id.'$allowFollowStatus非法','stopFollowBet');
            return false;
        }

        $userAccount = D('UserAccount')->getUserAccount($orderInfo['uid']);

        $frozenBalanceEnough = ($userAccount['user_account_frozen_balance'] >= $refund_amount);
        if(empty($frozenBalanceEnough)){
            $this->_notifyWarningMsg('$order_id:'.$order_id.'限制金额不够');
            ApiLog('$order_id:'.$order_id.'限制金额不够','stopFollowBet');
            return false;
        }


        $cancel_result = $this->cancelFollowBetInfo($orderInfo['uid'], $followBetInfoDetails['fbi_id'], $refund_money, $refund_coupon_amount, $orderInfo['user_coupon_id'],$type);
        if(!$cancel_result){
            $this->_notifyWarningMsg('$order_id:'.$order_id.'取消失败');
            ApiLog('$order_id:'.$order_id.'取消失败','stopFollowBet');
            return false;
        }
        return true;

    }

    public function cancelFollowBetInfo($uid, $fbi_id, $refund_money, $refund_coupon_amount, $userCouponId,$type) {
        M()->startTrans();
        $saveStatus = D('FollowBetInfo')->changeFollowBetInfoStatus($fbi_id, $type);
        if(empty($saveStatus)){
            $this->_notifyWarningMsg('cancelFollowBetInfo:$fbi_id:'.$fbi_id.'保存状态失败');
            M()->rollback();
            return false;
        }

        if($refund_coupon_amount){
            $result = D('UserCoupon')->increaseCoupon($userCouponId, $refund_coupon_amount);
            if(empty($result)){
                $this->_notifyWarningMsg('increaseCoupon:$fbi_id:'.$fbi_id.'退红包失败');
                M()->rollback();
                return false;
            }
        }

        $refund_total_amount = bcadd($refund_money,$refund_coupon_amount);
        if($refund_total_amount > 0){
            $refund_result = D('UserAccount')->refundFollowMoney($uid, bcadd($refund_money,$refund_coupon_amount),$refund_money,$refund_coupon_amount, $fbi_id, C('USER_ACCOUNT_LOG_TYPE.CANCEL_FOLLOW'));
            if(empty($refund_result)){
                ApiLog('sql1:'.D('UserAccountLog')->getLastSql(),'stopFollowBet');
                ApiLog('sql2:'.D('UserAccount')->getLastSql(),'stopFollowBet');
                $this->_notifyWarningMsg('refundFollowMoney:$fbi_id:'.$fbi_id.'退钱失败');
                M()->rollback();
                return false;
            }
        }
        M()->commit();
        return true;
    }

    private function _notifyWarningMsg($msg=''){
        $data = array(
            'telephone_list' => array('18705085505'),
            'send_data' => array($msg),
            'template_id' => '82542',
        );
        sendTelephoneMsgNew($data);
    }

    public function test(){
        $uid = 853;
        $refund_money = 0;
        $refund_coupon_amount = 8;
        $fbi_id = 149;
        $refund_result = D('UserAccount')->refundFollowMoney($uid, bcadd($refund_money,$refund_coupon_amount),$refund_money,$refund_coupon_amount, $fbi_id, C('USER_ACCOUNT_LOG_TYPE.CANCEL_FOLLOW'));

    }



}