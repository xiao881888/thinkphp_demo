<?php

namespace H5\Service;

use Home\Controller\BettingBaseController;
use Home\Util\Factory;
use Think\Exception;

class WebPayService extends BettingBaseController
{
    const JUMP_TYPE_FOR_APP = 0;
    const JUMP_TYPE_FOR_ORDER = 1;
    const JUMP_TYPE_FOR_BET = 2;
    const JUMP_TYPE_FOR_RECHARGE = 3;
    private $_request_params = null;
    private $_user_info = null;
    private $_uid = null;
    private $_msg_map = null;
    private $_jc_validator_obj = null;

    public $bet_error_msg = array(
        1050102 => '选择号码错误',
        1050103 => '彩票错误（彩期、玩法，选号方式）',
        1050104 => '彩期已截止',
        1050105 => '注数错误',
        1050106 => '价格不一致',
        1050107 => '扣款失败',
        1050108 => '需要验证支付密码',
        1050109 => '红包错误',
        1050110 => '出票失败',
        1050111 => '下单失败',
        1050112 => '超出单票最大投注金额',
    );

    public function __construct($request_data)
    {
        parent::__construct();
        $this->_msg_map = C('WEB_PAY_MESSAGE');
        $this->_request_params = $request_data;
        $this->_user_info = $this->queryUserInfoByUid($request_data['uid']);
        $this->_uid = $request_data['uid'];
    }

    public function payOrder()
    {
        $data = array();
        try {
            if ($this->_request_params['act'] == C('BET_ACT.BET_JC')) {
                if (!empty($this->_request_params['optimize_ticket_list'])){
                    $order_id = $this->addOptimizeOrder();
                }else{
                    $order_id = $this->addJcOrder();
                }
            } elseif ($this->_request_params['act'] == C('BET_ACT.BET_SZC')) {
                $order_id = $this->addSzcOrder();
            }elseif ($this->_request_params['act'] == C('BET_ACT.BET_LZC')){
                $order_id = $this->addLzcOrder();
            }else{
                die();
                $this->payForNoPaymentOrder();
            }
            $data['code'] = 0;
            $data['order_id'] = $order_id;
        } catch (Exception $e) {
            H5Log('文件:' . $e->getFile() . ';行数:' . $e->getLine() . ';出错信息:' . $e->getMessage(), 'H5PayOrderException');
            $data['code'] = $e->getCode();
            $data['msg'] = $e->getMessage();
            $data['line'] = $e->getLine();
            $data['file'] = $e->getFile();
        }
        return $data;
    }


    protected function queryUserInfoByUid($uid)
    {
        $user_info = D('Home/User')->getUserInfo($uid);
        $user_enable = $user_info['user_status'] == C('USER_STATUS.ENABLE');
        if (!$user_enable) {
            $this->_redirectToFailPageAndExit();
        }
        return $user_info;
    }

    private function _redirectToFailPageAndExit()
    {
        $this->_redirectToFailPage($this->_msg_map['NETWORK_ERROR'], self::JUMP_TYPE_FOR_APP);
        exit();
    }

    public function addJcOrder()
    {
        $existOrder = D('Home/Order')->getOrderIdByIdentity($this->_request_params['order_identity']);
        if ($existOrder) {
            if ($existOrder['order_status'] > C('ORDER_STATUS.UNPAID')) {
                if ($existOrder['order_status'] > C('ORDER_STATUS.PRINTOUTED')) {
                    throw new Exception($this->_msg_map['ALREADY_FAILED'], self::JUMP_TYPE_FOR_BET);
                } else {
                    throw new Exception($this->_msg_map['IS_PAID'], self::JUMP_TYPE_FOR_ORDER);
                }
            } elseif ($existOrder['order_status'] == C('ORDER_STATUS.UNPAID')) {
                $remain = $this->getRemainMoney($this->_uid, $this->_request_params['user_coupon_id'], $existOrder['lottery_id'], $existOrder['order_total_amount'], $existOrder['order_total_amount']);
                if ($remain < 0) {
                    throw new Exception($this->_msg_map['LACK_OF_MONEY'], self::JUMP_TYPE_FOR_RECHARGE);
                }
                $orderId = intval($existOrder['order_id']);
                $orderSku = $existOrder['order_sku'];
                H5Log('addJcOrder exist order info:' . $existOrder['lottery_id'] . '===' . print_r($existOrder, true), 'wpay');
                $ticketList = $this->getTicketListForPrintoutByOrderId($existOrder['lottery_id'], $existOrder['issue_id'], $existOrder['order_id'], $this->_uid);
                if (empty($ticketList)) {
                    $this->_throwExcepiton(self::JUMP_TYPE_FOR_BET, $this->_msg_map['TICKET_LIST_EMPTY']);
                }
                $issueNo = $this->queryIssueNoByIssueId($existOrder['lottery_id'], $existOrder['issue_id']);
                $first_issue_no = $this->queryIssueNoByIssueId($existOrder['lottery_id'], $existOrder['first_issue_id']);
            }
        } else {
            $orderSku = buildOrderSku($this->_uid);
            $orderTicket = $this->_addJCOrderAndTicket($this->_uid, $orderSku, $this->_request_params);
            if (empty($orderTicket)) {
                throw new Exception($this->_msg_map['TICKET_LIST_EMPTY'], self::JUMP_TYPE_FOR_BET);
            }
            $orderId = $orderTicket['orderId'];
            $ticketList = $orderTicket['ticketList'];
            $issueNo = $orderTicket['issueNo'];
            $first_issue_no = $orderTicket['firstIssueNo'];

            $remain = $this->getRemainMoney($this->_uid, $this->_request_params['user_coupon_id'], $this->_request_params['lottery_id'], $this->_request_params['total_amount'], $this->_request_params['total_amount']);
            if ($remain < 0) {
                throw new Exception($this->_msg_map['LACK_OF_MONEY'], self::LACK_OF_MONEY);
            }
        }

        $passwordFree = $this->checkPasswordFree($this->_uid, $this->_request_params['total_amount'], $this->_user_info['user_pre_order_limit'], $this->_user_info['user_pre_day_limit'], $this->_user_info['user_password_free'], $this->_user_info['user_payment_password']);

        if ($passwordFree) {
            $printOutResult = $this->printOutTicket($this->_user_info, $issueNo, $orderId, $this->_request_params['lottery_id'], $ticketList, $this->_request_params['multiple'], $first_issue_no);
            H5Log('jc print_out :' . ($printOutResult), 'wpay');
            if (!$printOutResult) {
                throw new Exception($this->_msg_map['PAY_FAILED'], self::JUMP_TYPE_FOR_ORDER);
            }
            $this->payOrderWithTransaction($this->_uid, $orderId, $this->_request_params['user_coupon_id'], $this->_request_params['total_amount']);
        } else {
            throw new Exception($this->_msg_map['PAY_FAILED'], self::JUMP_TYPE_FOR_ORDER);
        }
        return $orderId;
    }

    public function addOptimizeOrder(){
        $jc_validator_obj = "\\Home\\Validator\\JcValidator";
        $this->_jc_validator_obj = new $jc_validator_obj();
        //$this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, '竞彩官方系统故障，暂不支持投注');
        $orderSku = buildOrderSku($this->_uid);
        $order_total_amount = $this->_request_params['total_amount'];
        // validate param
        $valid = $this->validateParamsForOptimize($this->_uid, $this->_request_params);

        $existOrder = D('Home/Order')->getOrderIdByIdentity($this->_request_params['order_identity']);

        $lottery_id = empty($this->_request_params['lottery_id']) ? $existOrder['lottery_id'] : $this->_request_params['lottery_id'];
        $is_limit = $this->isLimitLottery($lottery_id);
        if($is_limit){
            $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['SZC_LOTTERY_NO_EXISTS']);
        }

        if ($existOrder) {
            if ($existOrder['order_status'] > C('ORDER_STATUS.UNPAID')) {
                if ($existOrder['order_status'] > C('ORDER_STATUS.PRINTOUTED')) {
                    $this->_throwExcepiton(self::JUMP_TYPE_FOR_BET, $this->_msg_map['ALREADY_FAILED']);
                } else {
                    $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['IS_PAID']);
                }
            } elseif ($existOrder['order_status'] == C('ORDER_STATUS.UNPAID')) {
                $remain = $this->getRemainMoney($this->_uid, $this->_request_params['user_coupon_id'],$existOrder['lottery_id'], $existOrder['order_total_amount'],$existOrder['order_total_amount']);
                if ($remain < 0) {
                    $this->_throwExcepiton(self::JUMP_TYPE_FOR_RECHARGE, $this->_msg_map['LACK_OF_MONEY']);
                }

                $orderId = intval($existOrder['order_id']);
                $orderSku = $existOrder['order_sku'];
                H5Log('get ticket list:' . $existOrder['lottery_id'] . '===' . print_r($existOrder, true), 'wpay');
                $ticketList = $this->getTicketListForPrintoutByOrderId($existOrder['lottery_id'], $existOrder['issue_id'], $existOrder['order_id'], $uid);
                if (empty($ticketList)) {
                    \AppException::throwException(self::JUMP_TYPE_FOR_BET, $this->_msg_map['TICKET_LIST_EMPTY']);
                }
                $last_issue_no = $this->queryIssueNoByIssueId($existOrder['lottery_id'], $existOrder['issue_id']);
                $first_issue_no = $this->queryIssueNoByIssueId($existOrder['lottery_id'], $existOrder['first_issue_id']);
            }
        } else {
            $select_schedule_info_list = $this->queryScheduleInfoListForOptimize($this->_request_params['select_schedule_ids']);
            $schedule_range_info = $this->checkScheduleTimeRangeInfo($select_schedule_info_list);
            $last_schedule_in_order = $schedule_range_info['last_schedule_info'];
            $first_schedule_in_order = $schedule_range_info['first_schedule_info'];
            $order_last_schedule = $last_schedule_in_order;

            $lottery_info = D('Lottery')->getLotteryInfo($this->_request_params['lottery_id']);
            $schedule_ids_of_all_lottery = $this->_queryScheduleIdsOfAllLotteryForOptimize($select_schedule_info_list, $lottery_info);

            H5Log('bet $select_schedule_info_list:' . print_r($select_schedule_info_list, true), 'wpay');
            H5Log('bet $schedule_ids_of_all_lottery:' . print_r($schedule_ids_of_all_lottery, true), 'wpay');
            H5Log('bet $order_last_schedule:' . print_r($order_last_schedule, true), 'wpay');
            $order_play_type = C('MAPPINT_JC_PLAY_TYPE.' . $this->_request_params['play_type']);

            $last_issue_no = $order_last_schedule['schedule_issue_no'];

            $bet_info = $this->_parseOrderContent($this->_uid, $this->_request_params['optimize_ticket_list'], $select_schedule_info_list, $order_play_type, $this->_request_params['order_multiple'], $schedule_ids_of_all_lottery);
            H5Log('bet:' . print_r($bet_info, true), 'wpay');
            $first_schedule_info_in_order = $first_schedule_in_order;
            $first_issue_no = $first_schedule_info_in_order['schedule_issue_no'];

            $ticket_data_list = $bet_info['ticket_data_list'];
            $printout_ticket_list = $bet_info['printout_ticket_list'];

            $order_request_params['series'] = $this->_getOrderSeries($this->_request_params['optimize_ticket_list']);
            $order_request_params['play_type'] = $this->_request_params['play_type'];
            $order_request_params['order_type'] = ORDER_TYPE_OF_OPTIMIZE;

            M()->startTrans();
            $order_id = D('Order')->addOrder($this->_uid, $this->_request_params['total_amount'], $order_last_schedule['schedule_id'], $this->_request_params['order_multiple'], $this->_request_params['user_coupon_id'], $this->_request_params['lottery_id'], $orderSku, $first_schedule_info_in_order['schedule_id'], $this->_request_params['order_identity'], 0, 0, '', $order_request_params);

            $model = getTicktModel($this->_request_params['lottery_id']);

            H5Log($order_id, 'wpay');
            H5Log($this->_request_params['lottery_id'], 'wpay');
            if (!$order_id || !$model) {
                M()->rollback();
                $this->_throwExcepiton(self::JUMP_TYPE_FOR_BET, $this->_msg_map['TICKET_LIST_EMPTY']);
            }
            $ticket_data_list_with_order_id = $model->appendOrderId($ticket_data_list, $order_id);
            H5Log(print_r($ticket_data_list, true), 'wpay');

            $add_ticket_result = $model->insertAll($ticket_data_list_with_order_id);
            if (!$add_ticket_result) {
                H5Log($add_ticket_result, 'wpay');
                M()->rollback();
                $this->_throwExcepiton(self::JUMP_TYPE_FOR_BET, $this->_msg_map['TICKET_LIST_EMPTY']);
            }

            $order_detail_list = $this->_getJcOrderDetailForOptimize($this->_request_params['optimize_ticket_list'], $order_id);
            $add_detail_result = D('Home/JcOrderDetail')->insertAll($order_detail_list);

            if (!$add_detail_result) {
                H5Log('after $$addDetail_result:' . $add_detail_result, 'wpay');
                M()->rollback();
                $this->_throwExcepiton(self::JUMP_TYPE_FOR_BET, $this->_msg_map['TICKET_LIST_EMPTY']);
            }

            M()->commit();

            $remain = $this->getRemainMoney($this->_uid, $this->_request_params['user_coupon_id'],$this->_request_params['lottery_id'], $this->_request_params['total_amount'],$this->_request_params['total_amount']);
            if ($remain < 0) {
                $this->_throwExcepiton(self::JUMP_TYPE_FOR_RECHARGE, $this->_msg_map['LACK_OF_MONEY']);
            }
        }

        $passwordFree = $this->checkPasswordFree($this->_uid, $order_total_amount, $this->_user_info['user_pre_order_limit'], $this->_user_info['user_pre_day_limit'], $this->_user_info['user_password_free'], $this->_user_info['user_payment_password']);

        if ($passwordFree) {
            // TODO 失败重试机制
            $printOutResult = $this->printOutTicket($this->_user_info, $last_issue_no, $order_id, $this->_request_params['lottery_id'], $printout_ticket_list, $this->_request_params['order_multiple'], $first_issue_no);
            H5Log('jc print_out :' . print_r($printOutResult, true), 'wpay');
            if (!$printOutResult) {
                $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['PAY_FAILED']);
            }
            $this->payOrderWithTransaction($this->_uid, $order_id, $this->_request_params['user_coupon_id'], $order_total_amount);
        }
        return $order_id;
    }

    // FIXME数字彩支付待修改
    public function addSzcOrder(){
        $szcFollowInfo = $this->_getSzcFollowInfo();
        $issueId = $szcFollowInfo['issueId'];
        $multiple = $szcFollowInfo['multiple'];
        $follow_times = $szcFollowInfo['follow_times'];
        $follow_detail = $szcFollowInfo['follow_detail'];

        $issueInfo = D('Home/Issue')->getIssueInfo($this->_request_params['issue_id']);
        if (empty($issueInfo)) {
            $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['SZC_ISSUE_NO_EXISTS']);
        }
        $lotteryInfo = D('Lottery')->getLotteryInfo($issueInfo['lottery_id']);
        if (empty($lotteryInfo)) {
            $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['SZC_LOTTERY_NO_EXISTS']);
        }
        $is_limit = $this->isLimitLottery($issueInfo['lottery_id']);

        if($is_limit){
            $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['SZC_LOTTERY_NO_EXISTS']);
        }

        $beforeDeadline = (strtotime($issueInfo['issue_end_time']) - time() > $lotteryInfo['lottery_ahead_endtime']);

        if (!$beforeDeadline) {
            $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['SZC_OUT_OF_ISSUE_TIME']);
        }

        $limitBetCode = $this->limitBetNum($this->_request_params['issue_id'],$issueInfo['lottery_id'],$this->_request_params['tickets']);
        if($limitBetCode!=C('ERROR_CODE.SUCCESS')){
            $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['LIMIT_NUMBER']);
        }

        $checkTicketsCode = $this->checkNumberTickets($this->_request_params['tickets'], $issueInfo['lottery_id']);
        
        if ($checkTicketsCode != C('ERROR_CODE.SUCCESS')) {
            $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['ALREADY_FAILED']);
        }
        $this->_request_params['lottery_id'] = $issueInfo['lottery_id'];
        $existOrder = D('Order')->getOrderIdByIdentity($this->_request_params['order_identity']);

        if ($existOrder) {
            if ($existOrder['order_status'] > C('ORDER_STATUS.UNPAID')) {
                if ($existOrder['order_status'] > C('ORDER_STATUS.PRINTOUTED')) {
                    $this->_throwExcepiton(self::JUMP_TYPE_FOR_BET, $this->_msg_map['ALREADY_FAILED']);
                } else {
                    $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['IS_PAID']);
                }
            } elseif ($existOrder['order_status'] == C('ORDER_STATUS.UNPAID')) {
                $total_pay_money = $existOrder['order_total_amount'];
                if ($existOrder['follow_bet_id']) {
                    $followInfo = D('FollowBet')->getFollowBetInfo($existOrder['follow_bet_id']);
                    $total_pay_money = ($followInfo['follow_remain_times']+1) * $existOrder['order_total_amount'];
                }

                $remain = $this->getRemainMoney($this->_uid, $this->_request_params['user_coupon_id'],$existOrder['lottery_id'], $total_pay_money,$existOrder['order_total_amount']);
                if ($remain < 0) {
                    $this->_throwExcepiton(self::JUMP_TYPE_FOR_RECHARGE, $this->_msg_map['LACK_OF_MONEY']);
                }
                $orderId = intval($existOrder['order_id']);
                $orderSku = $existOrder['order_sku'];
                H5Log('addJcOrder exist order info:' . $existOrder['lottery_id'] . '===' . print_r($existOrder, true), 'wpay');
                $ticketList = $this->getTicketListForPrintoutByOrderId($existOrder['lottery_id'], $existOrder['issue_id'], $existOrder['order_id'], $this->_uid);
                if (empty($ticketList)) {
                    $this->_throwExcepiton(self::JUMP_TYPE_FOR_BET, $this->_msg_map['TICKET_LIST_EMPTY']);
                }
                $issueNo = $this->queryIssueNoByIssueId($existOrder['lottery_id'], $existOrder['issue_id']);
            }
        } else {
            $uid = $this->_uid;
            $orderTotalAmount = $this->calcSzcOrderTotalAmountForOneTime($this->_request_params['tickets'], $this->_request_params['multiple']);
            $orderSku = buildOrderSku($uid);
            //$orderTicket = $this->addSzcOrderAndTickets($issueInfo['lottery_id'], $uid, $orderTotalAmount, $orderSku, $this->_request_params['issue_id'], $this->_request_params['multiple'], $this->_request_params['user_coupon_id'], $this->_request_params['tickets'], $this->_request_params['follow_times'], $this->_request_params['order_identity']);
            if ($this->_request_params['follow_times'] > 1){
                $order_type = 1;
            }else {
                $order_type = 0;
            }
            $orderTicket = $this->addSzcOrderAndTicketsForNewFollow($issueInfo['lottery_id'], $uid, $orderTotalAmount,
                $orderSku, $issueId, $multiple, $this->_request_params['user_coupon_id'], $this->_request_params['tickets'],
                $follow_times, $this->_request_params['order_identity'],$follow_detail,$this->_request_params['is_win_stop'],
                $this->_request_params['suite_id'],0,0,$order_type);
            if (!$orderTicket) {
                $this->_throwExcepiton(self::JUMP_TYPE_FOR_BET, $this->_msg_map['TICKET_LIST_EMPTY']);
            }
            $orderId = $orderTicket['orderId'];
            $ticketList = $orderTicket['ticketList'];
            $fbiId = $orderTicket['fbiId'];
            $totalPayMoney = $this->calcTotalPayAmount($this->_request_params['tickets'],$follow_detail,$this->_request_params['suite_id']);
            $remain = $this->getRemainMoney($this->_uid, $this->_request_params['user_coupon_id'], $this->_request_params['lottery_id'] ,$totalPayMoney,$orderTotalAmount);

            if ($remain < 0) {
                $this->_throwExcepiton(self::JUMP_TYPE_FOR_RECHARGE, $this->_msg_map['LACK_OF_MONEY']);
            }
            $passwordFree = $this->checkPasswordFree($this->_uid, $totalPayMoney, $this->_user_info['user_pre_order_limit'], $this->_user_info['user_pre_day_limit'], $this->_user_info['user_password_free'], $this->_user_info['user_payment_password']);

            if ($passwordFree) {
                $printOutResult = $this->printOutTicket($this->_user_info, $issueInfo['issue_no'], $orderId, $issueInfo['lottery_id'], $ticketList, $multiple);
                H5Log('szc print_out:' . print_r($printOutResult, true), 'wpay');
                if (!$printOutResult) {
                    H5Log('rollback:' . print_r($printOutResult, true), 'wpay');
                    $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['PAY_FAILED']);
                }
                H5Log('begin payOrderWithTransaction:', 'wpay');
                $this->payOrderWithTransaction($this->_uid, $orderId, $this->_request_params['user_coupon_id'], $orderTotalAmount, $fbiId);
            } else {
                $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['PAY_FAILED']);
            }
        }
        return $orderId;
    }

    public function addLzcOrder()
    {
        $lottery_info = D('Lottery')->getLotteryInfo($this->_request_params['lottery_id']);
        if(empty($lottery_info)){
            $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['SZC_LOTTERY_NO_EXISTS']);
        }

//        $is_limit = $this->isLimitLottery($this->_request_params['lottery_id']);
//        if($is_limit){
//            $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['SZC_LOTTERY_NO_EXISTS']);
//        }

        $user_info = $this->_user_info;
        $lottery_obj_instance = $this->_getLotteryInstance($this->_request_params['lottery_id']);
        $verified_params = $lottery_obj_instance->verifyParamsForWebPay($this->_request_params, $user_info);
        if(!$verified_params){
            $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['PAY_FAILED']);
        }

        $lottery_id = $verified_params['lottery_id'];
        $order_total_amount = $verified_params['total_amount'];
        $uid = $user_info['uid'];
        //check balance
        $money_to_be_paid = $this->getRemainMoney($uid, $verified_params['user_coupon_id'], $lottery_id ,$order_total_amount,$order_total_amount);
        if($money_to_be_paid<0) {
            $this->_throwExcepiton(self::JUMP_TYPE_FOR_RECHARGE, $this->_msg_map['LACK_OF_MONEY']);
        }

        $passwordFree = $this->checkPasswordFree($uid, $order_total_amount, $user_info['user_pre_order_limit'], $user_info['user_pre_day_limit'], $user_info['user_password_free'], $user_info['user_payment_password']);
        if(!$passwordFree){
            $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['PAY_FAILED']);
        }

        $exist_order_info = D('Order')->getOrderIdByIdentity($verified_params['order_identity']);
        if ($exist_order_info) {
            if ($exist_order_info['order_status'] > C('ORDER_STATUS.UNPAID')) {
                return $this->buildResponseForPayOrder($exist_order_info['order_id'], $exist_order_info['order_sku'], 0, C('ERROR_CODE.SUCCESS'));
            } elseif ($exist_order_info['order_status'] == C('ORDER_STATUS.UNPAID')) {
                $order_total_amount = $exist_order_info['order_total_amount'];
                $money_to_be_paid = $this->getRemainMoney($uid, $verified_params['user_coupon_id'],$exist_order_info['lottery_id'], $order_total_amount,$order_total_amount);
                H5Log('add remain:'.$money_to_be_paid, 'sfc');
                if ($money_to_be_paid < 0) {
                    $this->_throwExcepiton(self::JUMP_TYPE_FOR_RECHARGE, $this->_msg_map['LACK_OF_MONEY']);
                }
                $order_id = intval($exist_order_info['order_id']);
                $order_sku = $exist_order_info['order_sku'];
                H5Log('get ticket list:'.$exist_order_info['lottery_id'].'==='.print_r($exist_order_info,true), 'sfc');
                $ticketList = $this->getTicketListForPrintoutByOrderId($exist_order_info['lottery_id'], $exist_order_info['issue_id'], $exist_order_info['order_id'], $uid);
                if(!$ticketList){
                    $this->_throwExcepiton(self::JUMP_TYPE_FOR_BET, $this->_msg_map['TICKET_LIST_EMPTY']);
                }

                $issueNo = $this->queryIssueNoByIssueId($exist_order_info['lottery_id'], $exist_order_info['issue_id']);
                $first_issue_no = $this->queryIssueNoByIssueId($exist_order_info['lottery_id'], $exist_order_info['first_issue_id']);
            }
        }else{
            $order_total_amount = $verified_params['order_total_amount'];

            $money_to_be_paid = $this->getRemainMoney($uid, $verified_params['user_coupon_id'], $lottery_id ,$order_total_amount,$order_total_amount);

            if($money_to_be_paid<0) {
                return $this->buildResponseForPayOrder(0, '', abs($money_to_be_paid), C('ERROR_CODE.INSUFFICIENT_FUND'));
            }

            $order_sku = buildOrderSku($uid);
            $add_result = $lottery_obj_instance->addOrderAndTicketData($uid, $order_sku, $verified_params);

            if(!$add_result){
                $this->_throwExcepiton(self::JUMP_TYPE_FOR_BET, $this->_msg_map['TICKET_LIST_EMPTY']);
            }
            $order_id = $add_result['order_id'];
            $printout_ticket_list = $add_result['printout_ticket_list'];
            $issue_no = $add_result['issue_no'];

            $money_to_be_paid = $this->getRemainMoney($uid, $verified_params['user_coupon_id'], $lottery_id ,$order_total_amount,$order_total_amount);
            if($money_to_be_paid<0) {
                $this->_throwExcepiton(self::JUMP_TYPE_FOR_RECHARGE, $this->_msg_map['LACK_OF_MONEY']);
            }
        }

        if($passwordFree) {
            $printout_result = $this->printOutTicket($user_info, $verified_params['issue_no'], $order_id, $verified_params['lottery_id'], $printout_ticket_list, $verified_params['order_multiple']);
            H5Log('print_out:'.print_r($printout_result,true), 'opay');
            if($printout_result){
                H5Log('commit:'.print_r($printout_result,true), 'opay');
            }else{
                H5Log('rollback:'.print_r($printout_result,true), 'opay');
                $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['PAY_FAILED']);
            }
            $pay_result = $this->payOrderWithTransaction($user_info['uid'], $order_id, $verified_params['user_coupon_id'], $order_total_amount, 0);
            if($pay_result){
                $code =  C('ERROR_CODE.SUCCESS');
            }

        }else{
            $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['PAY_FAILED']);
        }
        return $order_id;
    }

    private function _addJCOrderAndTicket($uid, $orderSku, $params)
    {
        $verifyObj = Factory::createVerifyJcObj($this->_request_params['lottery_id']);
        $isVaildBetNumber = $verifyObj->checkCompetitionTickets($this->_request_params['schedule_orders'], $this->_request_params['series']);
        if (!$isVaildBetNumber) {
            throw new Exception($this->_msg_map['TICKET_LIST_EMPTY'], self::JUMP_TYPE_FOR_BET);
        }
        if (isJcMix($this->_request_params['lottery_id'])) {
            $formated_schedule_orders = $this->formatRequestScheduleOrders($this->_request_params['schedule_orders']);
            $tickets_from_combination = $verifyObj->convertScheduleOrderToTickets($formated_schedule_orders, $this->_request_params['series'], $this->_request_params['lottery_id']);
            // H5Log('$tickets_from_combination:'.empty($tickets_from_combination).'----'.count($tickets_from_combination), 'bet_mix');
            if (empty($tickets_from_combination)) {
                throw new Exception($this->_msg_map['TICKET_LIST_EMPTY'], self::JUMP_TYPE_FOR_BET);
            } elseif (count($tickets_from_combination) > 2000) {
                throw new Exception($this->_msg_map['TICKET_SCHEME_ERROR'], self::JUMP_TYPE_FOR_BET);
            } elseif (count($tickets_from_combination) > 2000) {
                throw new Exception($this->_msg_map['TICKET_SCHEME_ERROR'], self::JUMP_TYPE_FOR_BET);
            }
            return $this->_addJcMixtureOrder($uid, $orderSku, $params, $tickets_from_combination);
        } else {
            return $this->_addJcNoMixtureOrder($uid, $orderSku, $params);
        }
    }

    private function _addJcMixtureOrder($uid, $orderSku, $params, $tickets_from_combination)
    {
        $series = explode(',', $this->_request_params['series']);
        $jcTicketInfo = $this->_buildJcMixtureTicketInfo($uid, $this->_request_params['schedule_orders'], $tickets_from_combination, $this->_request_params['stake_count'], $this->_request_params['total_amount'], $this->_request_params['multiple'], $this->_request_params['lottery_id']);
        if (empty($jcTicketInfo)) {
            throw new Exception($this->_msg_map['TICKET_LIST_EMPTY'], self::JUMP_TYPE_FOR_BET);
        }
        $orderTicket = $this->_addJzOrderSchedule($this->_request_params['lottery_id'], $uid, $this->_request_params['total_amount'], $orderSku, $this->_request_params['multiple'], $this->_request_params['user_coupon_id'], $this->_request_params['schedule_orders'], $jcTicketInfo, $this->_request_params['order_identity'], $this->_request_params);
        return $orderTicket;
    }

    private function _buildJcMixtureTicketInfo($uid, array $scheduleOrders, array $tickets_from_combination, $stakeCount, $totalAmount, $multiple, $lotteryId)
    {
        $schedule_ids_in_order = array_column($scheduleOrders, 'schedule_id');
        H5Log('ids:' . print_r($schedule_ids_in_order, true), 'wpay');
        $schedule_infos_in_order = D('Home/JcSchedule')->getScheduleIssueNo($schedule_ids_in_order);
        if (empty($schedule_infos_in_order)) {
            throw new Exception($this->_msg_map['TICKET_LIST_EMPTY'], self::JUMP_TYPE_FOR_BET);
        }

        $lottery_info = D('Home/Lottery')->getLotteryInfo($lotteryId);
        $this->checkScheduleOutOfTime($schedule_infos_in_order, $lottery_info);
        $schedule_ids_of_all_lottery = array();
        foreach ($schedule_infos_in_order as $schedule_id => $scheduleInfo) {
            $schedule_end_time_unix_timestamp = strtotime($scheduleInfo['schedule_end_time']);
            $scheduleInfo['schedule_end_time_unix_timestamp'] = $schedule_end_time_unix_timestamp;
            $mix_schedule_id = $scheduleInfo['schedule_id'];
            $day = $scheduleInfo['schedule_day'];
            $week = $scheduleInfo['schedule_week'];
            $round_no = $scheduleInfo['schedule_round_no'];

            $schedule_ids_of_all_lottery[$mix_schedule_id] = D('Home/JcSchedule')->queryAllScheduleIdsFromScheduleNo($day, $week, $round_no);
            $new_schedule_infos_in_order[$schedule_id] = $scheduleInfo;
        }
        H5Log('new in order:' . print_r($new_schedule_infos_in_order, true), 'wpay');
        $stageTicket = $this->_saveJcMixtureTicket($uid, $tickets_from_combination, $new_schedule_infos_in_order, $lotteryId, $schedule_ids_of_all_lottery, $multiple);
        $this->verifyStakeCountAndTotalAmountByOrder($stageTicket, $stakeCount, $multiple, $totalAmount);

        $schedule_range_info = $this->checkScheduleTimeRangeInfo($new_schedule_infos_in_order);
        $last_schedule_info_in_order = $schedule_range_info['last_schedule_info'];
        $first_schedule_info_in_order = $schedule_range_info['first_schedule_info'];
        H5Log('$schedule_range_info:' . print_r($schedule_range_info, true), 'wpay');

        if ($stageTicket) {
            return array(
                'lastSchedule' => $last_schedule_info_in_order,
                'firstSchedule' => $first_schedule_info_in_order,
                'stageTicket' => $stageTicket
            );
        } else {
            return false;
        }
    }

    private function _saveJcMixtureTicket($uid, array $tickets_from_combination, array $scheduleInfos, $lotteryId, $schedule_ids_of_all_lottery, $multiple)
    {
        $orderStakeCount = 0;
        $ticketSeq = 0;
        $ticketData = $ticketList = array();
        $verifyObj = Factory::createVerifyJcObj($lotteryId);
        H5Log('tickets:' . print_r($tickets_from_combination, true), 'wpay');
        foreach ($tickets_from_combination as $ticket) {
            $competitionInfo = $this->_buildJcMixCompetitionInfoForPrintOut($ticket, $scheduleInfos, $lotteryId, $schedule_ids_of_all_lottery);
            if (empty($competitionInfo)) {
                throw new Exception($this->_msg_map['TICKET_LIST_EMPTY'], self::JUMP_TYPE_FOR_BET);
            }
            $competition = $competitionInfo['competition'];

            $betType = $ticket['bet_type'];
            $ticket_schedule_list = $this->buildBetScheduleListInTicket($ticket);

            $stakeCount = $verifyObj->getStakeCount($ticket_schedule_list, $betType);
            H5Log('aaaa:' . $betType . '===' . $stakeCount . '===' . print_r($ticket_schedule_list, true), 'wpay');
            // $devide_result = $this->devideOverMultipleTicket($uid, $ticketSeq, $stakeCount, $multiple, $betType, $stakeCount, $competitionInfo);
            $devide_result = $this->devideOverMultipleTicket($uid, $lotteryId, JC_PLAY_TYPE_MULTI_STAGE, $ticketSeq, $stakeCount, $multiple, $betType, $competitionInfo);
            $orderStakeCount += $stakeCount;
            $ticketSeq = $devide_result['ticket_seq'];
            $ticketList = array_merge($ticketList, $devide_result['printout_ticket_list']);
            $ticketData = array_merge($ticketData, $devide_result['ticket_data']);
        }

        return array(
            'stakeCount' => $orderStakeCount,
            'ticketList' => $ticketList,
            'ticketData' => $ticketData
        );
    }

    private function _buildJcMixCompetitionInfoForPrintOut(array $ticket_item, array $scheduleInfos, $lotteryId, $schedule_ids_of_all_lottery)
    {
        $competitions = array();
        $ticket_content = $ticket_item;
        $betType = $ticket_content['bet_type'];
        unset($ticket_content['bet_type']);
        $ticket_lottery_id = $this->getLotteryIdByCompetition($ticket_content, $lotteryId);
        $is_jc_mix_lottery_id = isJcMix($ticket_lottery_id);

        $last_schedule_game_start_time = 0;
        $first_schedule_end_time = 0;
        foreach ($ticket_content as $bet_schedule) {
            if ($is_jc_mix_lottery_id) {
                $scheduleId = $bet_schedule['schedule_id'];
                $issueNo = $scheduleInfos[$scheduleId]['schedule_issue_no'];
                $ticket_schedule_info = $scheduleInfos[$scheduleId];
                $competition_lottery_id = $bet_schedule['lottery_id'];
                if (!isset($schedule_ids_of_all_lottery[$scheduleId][$competition_lottery_id])) {
                    H5Log('no exist:' . $competition_lottery_id . '====' . $scheduleId . '====' . print_r($scheduleInfos, true), 'csq');
                }
                // H5Log('$$$$scheduleInfos[$scheduleId]:'.print_r($scheduleInfos[$scheduleId],true), 'com');
            } else {
                $orig_schedule_id = $bet_schedule['schedule_id'];
                $orig_schedule_info = $scheduleInfos[$orig_schedule_id];
                $play_type = $orig_schedule_info['play_type'];
                // H5Log('orig_schedule_info:'.print_r($orig_schedule_info,true), 'com');
                $ticket_schedule_info = $schedule_ids_of_all_lottery[$orig_schedule_id][$ticket_lottery_id][$play_type];
                // H5Log('covert ticket schedule info:'.print_r($ticket_schedule_info,true), 'com');
                $scheduleId = $ticket_schedule_info['schedule_id'];
                $issueNo = $ticket_schedule_info['schedule_issue_no'];
                $competition_lottery_id = $ticket_lottery_id;
            }

            if (empty($issueNo)) {
                // 查不到issueno 报警
                H5Log('$scheduleInfos[$orig_schedule_id]:' . print_r($scheduleInfos[$orig_schedule_id], true), 'opay');
                H5Log('$$$ticket_schedule_info:' . $orig_schedule_id . '==' . $ticket_lottery_id . '==' . $play_type . '==' . print_r($ticket_schedule_info, true), 'opay');
                H5Log('$$$schedule_ids_of_all_lottery:' . $issueNo . '=====' . print_r($schedule_ids_of_all_lottery, true), 'opay');
                H5Log('$$all_ticket_issue_no:' . $issueNo . '=====' . print_r($schedule_ids_of_all_lottery[$orig_schedule_id], true), 'opay');
                return false;
            }

            // $competition['schedule_id'] = $scheduleId;
            $competition['lottery_id'] = $competition_lottery_id;
            $competition['bet_options'] = $bet_schedule['bet_options'];
            $competition['issue_no'] = $issueNo;

            // H5Log('competion:'.print_r($competition,true), 'com');

            $competitions[] = $competition;

            $all_ticket_issue_no[] = $issueNo;

            $schedule_end_time_stamp = $ticket_schedule_info['schedule_end_time_unix_timestamp'];
            $schedule_game_start_time_stamp = strtotime($ticket_schedule_info['schedule_game_start_time']);;
            if ($schedule_game_start_time_stamp >= $last_schedule_game_start_time) {
                $last_schedule_game_start_time = $schedule_game_start_time_stamp;
                $last_schedule_in_ticket = $ticket_schedule_info;
            }

            if ($first_schedule_end_time == 0) {
                $first_schedule_end_time = $schedule_end_time_stamp;
            }
            if ($schedule_end_time_stamp <= $first_schedule_end_time) {
                H5Log('build ====mix :' . $first_schedule_end_time . '=-===' . print_r($ticket_schedule_info, true), 'opay');
                $first_schedule_end_time = $schedule_end_time_stamp;
                $first_schedule_in_ticket = $ticket_schedule_info;
            }
        }
        H5Log('build mix :' . $first_schedule_end_time . '===' . print_r($first_schedule_in_ticket, true), 'opay');
        asort($all_ticket_issue_no);
        $ticket_issue_nos = implode(',', $all_ticket_issue_no);

        return array(
            'ticket_lottery_id' => $ticket_lottery_id,
            'bet_type' => $betType,
            'ticket_issue_nos' => $ticket_issue_nos,
            'last_schedule_issue_id' => $last_schedule_in_ticket['schedule_id'],
            'last_schedule_issue_no' => $last_schedule_in_ticket['schedule_issue_no'],
            'last_schedule_end_time' => $last_schedule_in_ticket['schedule_end_time'],
            'first_schedule_issue_id' => $first_schedule_in_ticket['schedule_id'],
            'first_schedule_issue_no' => $first_schedule_in_ticket['schedule_issue_no'],
            'first_schedule_end_time' => $first_schedule_in_ticket['schedule_end_time'],
            'competition' => $competitions
        );
    }

    private function _addJzOrderSchedule($lotteryId, $uid, $totalAmount, $orderSku, $multiple, $couponId, array $scheduleOrders, array $jcTicketInfo, $identity, $request_params)
    {
        $orderTotalAmount = $totalAmount;
        $lastSchedule = $jcTicketInfo['lastSchedule'];
        $firstSchedule = $jcTicketInfo['firstSchedule'];
        $stageTicket = $jcTicketInfo['stageTicket'];
        $request_params['content'] = json_encode($request_params['schedule_orders']);

        $request_params['content'] = json_encode($request_params['schedule_orders']);

        M()->startTrans();
        $orderId = D('Home/Order')->addOrder($uid, $orderTotalAmount, $lastSchedule['schedule_id'], $multiple, $couponId, $lotteryId, $orderSku, $firstSchedule['schedule_id'], $identity, 0, 0, '', $request_params);
        $model = getTicktModel($lotteryId);
        if (!$orderId || !$model) {
            M()->rollback();
            return false;
        }
        $ticketDatas = $model->appendOrderId($stageTicket['ticketData'], $orderId);

        $addTickets_result = $model->insertAll($ticketDatas);
        if (!$addTickets_result) {
            H5Log('$addTickets:' . $addTickets_result, 'wpay');
            M()->rollback();
            return false;
        }
        $orderDetails = $this->_getJcOrderDetail($scheduleOrders, $orderId);
        $addDetail_result = D('Home/JcOrderDetail')->insertAll($orderDetails);
        if (!$addDetail_result) {
            H5Log('after $$addDetail_result:' . $addDetail_result, 'wpay');
            M()->rollback();
            return false;
        }
        M()->commit();

        return array(
            'orderId' => $orderId,
            'issueNo' => $lastSchedule['schedule_issue_no'],
            'firstIssueNo' => $firstSchedule['schedule_issue_no'],
            'ticketList' => $stageTicket['ticketList']
        );
    }

    private function _getJcOrderDetail($scheduleOrders, $orderId)
    {
        $orderDetails = array();
        foreach ($scheduleOrders as $scheduleOrder) {
            $betNumbers = $this->parseBetNumber($scheduleOrder['bet_number']);
            $betNumbers = betOptionsAddV($betNumbers);
            $betContent = json_encode($betNumbers);
            $orderDetails[] = D('Home/JcOrderDetail')->buildDetailData($orderId, $scheduleOrder['schedule_id'], $betContent, $scheduleOrder['is_sure']);
        }
        return $orderDetails;
    }

    private function _addJcNoMixtureOrder($uid, $orderSku, $params)
    {
        $playType = C('MAPPINT_JC_PLAY_TYPE.' . $this->_request_params['play_type']);
        $series = explode(',', $this->_request_params['series']);

        $jcTicketInfo = $this->_buildJzTicketInfo($this->_request_params['schedule_orders'], $uid, $playType, $series, $this->_request_params['lottery_id'], $this->_request_params['stake_count'], $this->_request_params['total_amount'], $this->_request_params['multiple']);
        if (empty($jcTicketInfo)) {
            throw new Exception($this->_msg_map['TICKET_LIST_EMPTY'], self::JUMP_TYPE_FOR_BET);
        }
        $orderTicket = $this->_addJzOrderSchedule($this->_request_params['lottery_id'], $uid, $this->_request_params['total_amount'], $orderSku, $this->_request_params['multiple'], $this->_request_params['user_coupon_id'], $this->_request_params['schedule_orders'], $jcTicketInfo, $this->_request_params['order_identity'], $this->_request_params);
        return $orderTicket;
    }

    private function _buildJzTicketInfo(array $scheduleOrders, $uid, $playType, array $series, $lotteryId, $stakeCount, $totalAmount, $multiple)
    {
        foreach ($scheduleOrders as $scheduleOrder) {
            $scheduleIds[] = $scheduleOrder['schedule_id'];
        }
        H5Log('schedule ids:' . print_r($scheduleOrders, true) . '===' . print_r($scheduleIds, true), 'wpay');
        $scheduleInfos = D('Home/JcSchedule')->getScheduleIssueNo($scheduleIds);
        H5Log('schedule info:' . print_r($scheduleInfos, true), 'wpay');
        if (empty($scheduleInfos)) {
            throw new Exception($this->_msg_map['TICKET_LIST_EMPTY'], self::JUMP_TYPE_FOR_BET);
        }
        $lottery_info = D('Home/Lottery')->getLotteryInfo($lotteryId);
        $this->checkScheduleOutOfTime($scheduleInfos, $lottery_info);

        $schedule_range_info = $this->checkScheduleTimeRangeInfo($scheduleInfos);
        $lastSchedule = $schedule_range_info['last_schedule_info'];
        $firstSchedule = $schedule_range_info['first_schedule_info'];
        H5Log('$firstSchedule info:' . print_r($firstSchedule, true), 'wpay');
        H5Log('$$scheduleInfos info:' . print_r($scheduleInfos, true), 'wpay');

        if ($playType == C('JC_PLAY_TYPE.ONE_STAGE')) { // 如果是单关
            $stageTicket = $this->_saveOneStageTicket($uid, $scheduleOrders, $scheduleInfos, $playType, $series, $lotteryId, $multiple);
        } elseif ($playType == C('JC_PLAY_TYPE.MULTI_STAGE')) {
            $stageTicket = $this->_saveMultiStageTicket($uid, $scheduleOrders, $scheduleInfos, $playType, $series, $lotteryId, $multiple);
        }
        $this->verifyStakeCountAndTotalAmountByOrder($stageTicket, $stakeCount, $multiple, $totalAmount);

        if ($stageTicket) {
            H5Log('sss:' . print_r($lastSchedule, true) . '===' . print_r($stageTicket, true), 'wpay');
            return array(
                'lastSchedule' => $lastSchedule,
                'firstSchedule' => $firstSchedule,
                'stageTicket' => $stageTicket
            );
        } else {
            return false;
        }
    }

    private function _saveOneStageTicket($uid, array $scheduleOrders, array $scheduleInfos, $playType, array $series, $lotteryId, $multiple)
    {
        $betType = $series[0];
        $ticketSeq = 0;
        $ticketList = array();
        $ticketData = array();
        $orderStakeCount = 0;
        foreach ($scheduleOrders as $scheduleOrder) {
            $competitionInfo = $this->_buildJcNoMixCompetitionInfoForPrintOut(array(
                $scheduleOrder
            ), $scheduleInfos, $lotteryId);
            $bet = $this->parseBetNumber($scheduleOrder['bet_number']);
            H5Log('parseBetNumber:' . print_r($bet, true), 'wpay');

            $bet = array_pop($bet);
            H5Log('array pop :' . print_r($bet, true), 'wpay');

            $stakeCount = count($bet);

            $devide_result = $this->devideOverMultipleTicket($uid, $lotteryId, $playType, $ticketSeq, $stakeCount, $multiple, $betType, $competitionInfo);
            if (empty($devide_result)) {
                return false;
            }
            $orderStakeCount += $stakeCount;

            $ticketSeq = $devide_result['ticket_seq'];
            $ticketList = array_merge($ticketList, $devide_result['printout_ticket_list']);
            $ticketData = array_merge($ticketData, $devide_result['ticket_data']);
        }

        H5Log('stakCount:' . $orderStakeCount . '------' . print_r($ticketList, true), 'wpay');

        return array(
            'stakeCount' => $orderStakeCount,
            'ticketList' => $ticketList,
            'ticketData' => $ticketData
        );
    }

    private function _buildJcNoMixCompetitionInfoForPrintOut(array $scheduleOrders, array $scheduleInfos, $ticket_lottery_id)
    {
        $ticketContent = array();
        $competition = array();
        foreach ($scheduleOrders as $scheduleOrder) {
            $scheduleId = $scheduleOrder['schedule_id'];
            $endTime = $scheduleInfos[$scheduleId]['schedule_end_time'];
            $betNumber = $this->parseBetNumber($scheduleOrder['bet_number']);
            $scheduleIssueNo = $scheduleInfos[$scheduleId]['schedule_issue_no'];

            $all_ticket_issue_no[] = $scheduleIssueNo;

            foreach ($betNumber as $lotteryId => $bet) {
                sort($bet);
                $competitions[] = array(
                    'lottery_id' => $lotteryId,
                    'issue_no' => $scheduleIssueNo,
                    'bet_options' => $bet
                );
            }
            $ticketContent[] = array(
                'schedule_issue_no' => $scheduleIssueNo,
                'bet' => $betNumber,
                'schedule_end_time' => $endTime,
                'schedule_game_start_time' => $scheduleInfos[$scheduleId]['schedule_game_start_time'],
                'schedule_id' => $scheduleId
            );
        }

        asort($all_ticket_issue_no);
        $ticket_issue_nos = implode(',', $all_ticket_issue_no);

        $schedule_range_info = $this->checkScheduleTimeRangeInfo($ticketContent);
        $last_schedule_in_ticket = $schedule_range_info['last_schedule_info'];
        $first_schedule_in_ticket = $schedule_range_info['first_schedule_info'];
        H5Log('$last_schedule_info:' . print_r($last_schedule_in_ticket, true), 'opay');
        H5Log('$$first_schedule_info:' . print_r($first_schedule_in_ticket, true), 'opay');

        return array(
            'ticket_lottery_id' => $ticket_lottery_id,
            // 'bet_type' => $betType,
            'ticket_issue_nos' => $ticket_issue_nos,
            'issue_no' => $last_schedule_in_ticket['schedule_issue_no'],
            'last_schedule_issue_id' => $last_schedule_in_ticket['schedule_id'],
            'last_schedule_issue_no' => $last_schedule_in_ticket['schedule_issue_no'],
            'last_schedule_end_time' => $last_schedule_in_ticket['schedule_end_time'],
            'first_schedule_issue_id' => $first_schedule_in_ticket['schedule_id'],
            'first_schedule_issue_no' => $first_schedule_in_ticket['schedule_issue_no'],
            'first_schedule_end_time' => $first_schedule_in_ticket['schedule_end_time'],
            'competition' => $competitions
        );
    }

    private function _saveMultiStageTicket($uid, array $scheduleOrders, array $scheduleInfos, $playType, array $series, $lotteryId, $multiple)
    {
        $verifyObj = Factory::createVerifyJcObj($lotteryId);
        $ticketSeq = 0;
        $ticketList = array();
        $ticketData = array();
        $orderStakeCount = 0;

        foreach ($series as $betType) {
            $maxSelectCount = $verifyObj->getMaxSeriesCount($scheduleOrders, $betType, $lotteryId);
            if (empty($maxSelectCount)) {
                throw new Exception($this->_msg_map['TICKET_LIST_EMPTY'], self::JUMP_TYPE_FOR_BET);
            }
            $scheduleCombinatorics = $verifyObj->getScheduleCombinatorics($scheduleOrders, $maxSelectCount);
            foreach ($scheduleCombinatorics as $scheduleCom) {
                $competitionInfo = $this->_buildJcNoMixCompetitionInfoForPrintOut($scheduleCom, $scheduleInfos, $lotteryId);
                H5Log('$competitionInfo :' . print_r($competitionInfo, true), 'wpay');
                H5Log('$scheduleCom :' . print_r($scheduleCom, true), 'wpay');

                $stakeCount = $verifyObj->getStakeCount($scheduleCom, $betType);
                H5Log('$stakeCount :' . print_r($stakeCount, true), 'wpay');

                $devide_result = $this->devideOverMultipleTicket($uid, $lotteryId, $playType, $ticketSeq, $stakeCount, $multiple, $betType, $competitionInfo);
                if (empty($devide_result)) {
                    return false;
                }
                $orderStakeCount += $stakeCount;

                $ticketSeq = $devide_result['ticket_seq'];
                $ticketList = array_merge($ticketList, $devide_result['printout_ticket_list']);
                $ticketData = array_merge($ticketData, $devide_result['ticket_data']);
            }
        }

        H5Log('stakCount:' . $orderStakeCount . '------' . print_r($ticketList, true), 'wpay');
        H5Log('stakCount:' . $orderStakeCount . '------' . print_r($ticketData, true), 'wpay');

        return array(
            'stakeCount' => $orderStakeCount,
            'ticketList' => $ticketList,
            'ticketData' => $ticketData
        );
    }

    public function queryScheduleInfoListForOptimize($schedule_ids)
    {
        $schedulesInfo = D('Home/JcSchedule')->getScheduleIssueNo($schedule_ids);
        return $schedulesInfo;
    }

    public function buildTicketOdds($competitionInfo)
    {
        $competitions = $competitionInfo['competition'];
        $odds_list = array();
        $ticket_lottery_id = $competitionInfo['ticket_lottery_id'];

        foreach ($competitions as $competition) {
            $odds_info['lottery_id'] = $competition['lottery_id'];
            $odds_info['issue_no'] = $competition['issue_no'];
            $odds_info['odds'] = $this->getScheduleOdds($ticket_lottery_id, $competition['lottery_id'], $competition['issue_no'], $competition['bet_options']);
            $odds_list[] = $odds_info;
        }
        if (empty($odds_list)) {
            throw new Exception($this->_msg_map['TICKET_LIST_EMPTY'], self::JUMP_TYPE_FOR_BET);
        }
        return json_encode($odds_list);
    }

    public function getScheduleOdds($ticket_lottery_id, $bet_lottery_id, $issue_no, $bet_options)
    {
        $map['schedule_issue_no'] = $issue_no;
        $schedule_info = M('JcSchedule')->where($map)->find();
        $schedule_odds_info = json_decode($schedule_info['schedule_odds'], true);
        H5Log('sche:' . print_r($schedule_odds_info, true) . '====' . print_r($bet_options, true), 'emer');
        $formated_bet_options = betOptionAddV($bet_options);
        if (isJcMix($ticket_lottery_id)) {
            $schedule_odds_info = $schedule_odds_info[$bet_lottery_id];
        }
        if (isset($schedule_odds_info['letPoint'])) {
            $odds_info['letPoint'] = $schedule_odds_info['letPoint'];
        }
        foreach ($formated_bet_options as $bet_option_str) {
            H5Log('$bet_option_str:' . $bet_option_str . '====' . $schedule_odds_info[$bet_option_str], 'emer');
            $odds_info[$bet_option_str] = $schedule_odds_info[$bet_option_str];
        }
        return $odds_info;
    }

    public function payForNoPaymentOrder()
    {
        $orderInfo = D('Home/Order')->getOrderInfo($this->_request_params['order_id']);
        if (empty($orderInfo)) {
            throw new Exception($this->_msg_map['ORDER_INFO_ERROR'], self::JUMP_TYPE_FOR_BET);
        }

        if ($orderInfo['order_status'] > C('ORDER_STATUS.UNPAID')) {
            if ($orderInfo['order_status'] > C('ORDER_STATUS.PRINTOUTED')) {
                throw new Exception($this->_msg_map['ALREADY_FAILED'], self::JUMP_TYPE_FOR_BET);
            } else {
                throw new Exception($this->_msg_map['IS_PAID'], self::JUMP_TYPE_FOR_ORDER);
            }
        }

        $userOwen = ($orderInfo['uid'] == $this->_uid);
        if (!($userOwen)) {
            throw new Exception($this->_msg_map['ORDER_INFO_ERROR'], self::JUMP_TYPE_FOR_BET);
        }

        if (isJCLottery($this->_request_params['lottery_id'])) {
            $totalPayMoney = $orderInfo['order_total_amount'];
        } else {
            $followInfo = D('Home/FollowBet')->getFollowBetInfo($orderInfo['follow_bet_id']);
            $followTimes = ($followInfo['follow_remain_times'] ? ($followInfo['follow_remain_times'] + 1) : 1);
            $totalPayMoney = $orderInfo['order_total_amount'] * $followTimes;
        }

        $remain = $this->getRemainMoney($this->_uid, $this->_request_params['user_coupon_id'], $orderInfo['lottery_id'], $totalPayMoney, $orderInfo['order_total_amount']);
        if ($remain < 0) {
            throw new Exception($this->_msg_map['LACK_OF_MONEY'], self::JUMP_TYPE_FOR_BET);
        }

        $ticketList = $this->getTicketListForPrintoutByOrderId($orderInfo['lottery_id'], $orderInfo['issue_id'], $this->_request_params['order_id'], $this->_uid);
        if (empty($ticketList)) {
            throw new Exception($this->_msg_map['TICKET_LIST_EMPTY'], self::JUMP_TYPE_FOR_BET);
        }

        $issueNo = $this->queryIssueNoByIssueId($orderInfo['lottery_id'], $orderInfo['issue_id']);
        $first_issue_no = $this->queryIssueNoByIssueId($orderInfo['lottery_id'], $orderInfo['first_issue_id']);

        $passwordFree = $this->checkPasswordFree($this->_uid, $orderInfo['order_total_amount'], $this->_user_info['user_pre_order_limit'], $this->_user_info['user_pre_day_limit'], $this->_user_info['user_password_free'], $this->_user_info['user_payment_password']);

        if ($passwordFree) {
            // TODO 失败重试机制
            $printOutResult = $this->printOutTicket($this->_user_info, $issueNo, $this->_request_params['order_id'], $orderInfo['lottery_id'], $ticketList, $this->_request_params['multiple'], $first_issue_no);
            H5Log('payForNoPaymentOrder print_out :' . ($printOutResult), 'wpay');
            if (!$printOutResult) {
                throw new Exception($this->_msg_map['PAY_FAILED'], self::JUMP_TYPE_FOR_ORDER);
            }
            $this->payOrderWithTransaction($this->_uid, $this->_request_params['order_id'], $this->_request_params['user_coupon_id'], $orderInfo['order_total_amount'], $orderInfo['follow_bet_id']);
        } else {
            throw new Exception($this->_msg_map['PAY_FAILED'], self::JUMP_TYPE_FOR_ORDER);
        }
    }

    private function _redirectToFailPage($error_msg, $jump_page = '')
    {
        $this->assign('error_msg', $error_msg);
        $this->assign('jump_page', $jump_page);
        $this->display('error');
    }

    private function _redirectToSuccessPage($success_msg)
    {
        if ($this->_request_params['lottery_id']) {
            $this->assign('lottery_id', intval($this->_request_params['lottery_id']));
        }
        $this->assign('success_msg', $success_msg);
        $this->display('success');
    }

    private function _getSzcFollowInfo(){
        if(empty($this->_request_params['follow_detail'])){
            if(empty($this->_request_params['suite_id'])){
                //普通下单
                $issueId = $this->_request_params['issue_id'];
                $multiple = $this->_request_params['multiple'];
                $follow_times = $this->_request_params['follow_times'];
            }else{
                //追号套餐
                $packagesInfo = D('LotteryPackage')->getPackagesInfoById($this->_request_params['suite_id']);
                if(empty($packagesInfo)){
                    $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['PACKAGES_NO_EXIST']);
                }
                $issueId = $this->_request_params['issue_id'];
                $multiple = $packagesInfo['lp_multiple'];
                $follow_times = $packagesInfo['lp_issue_num'];
                if($packagesInfo['lottery_id']!=$this->_request_params['lottery_id']){
                    $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['SZC_ISSUE_NO_EXISTS']);
                }
                $checkPackagesCode = $this->checkPackagesTicket($this->_request_params['tickets'],$packagesInfo);
                if($checkPackagesCode!=C('ERROR_CODE.SUCCESS')){
                    $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['ALREADY_FAILED']);
                }
            }
            $orderTotalAmount = $this->calcSzcOrderTotalAmountForOneTime($this->_request_params['tickets'], $multiple);
            $follow_detail = $this->buildFollowDetails($this->_request_params['follow_detail'],$follow_times,$orderTotalAmount,$multiple);
        }else{
            //智能追号
            $follow_detail = $this->_request_params['follow_detail'];
            $multiple = $follow_detail[0]['multiple'];
            $issueId = $follow_detail[0]['issue_id'];
            $follow_times = $this->_request_params['follow_times'];
            if(count($follow_detail) != $follow_times){
                $this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['ALREADY_FAILED']);
            }
        }
        return array(
            'follow_detail' => $follow_detail,
            'multiple' => $multiple,
            'issueId' => $issueId,
            'follow_times' => $follow_times,
        );
    }

    public function validateParamsForOptimize($uid, $params){
        if ($params['user_coupon_id']) {
            $coupon_owen_by_user = D('UserCoupon')->owenedByUser($uid, $params['user_coupon_id']);
            if (!$coupon_owen_by_user) {
                $this->_throwExcepiton(self::JUMP_TYPE_FOR_RECHARGE, $this->_msg_map['COUPON_NOT_USEABLE']);
            }
        }
        H5Log('aaa', 'wpay');
        $order_stake = 0;
        foreach ($params['optimize_ticket_list'] as $ticket) {
            $ticket_stake = $ticket["ticket_multiple"];
            $order_stake += $ticket_stake;

            $ticket_lottery_ids = array();
            foreach ($ticket['ticket_schedules'] as $ticket_schedule) {
                $bet_option_error = $this->_jc_validator_obj->validateBetOption($ticket_schedule['bet_options']);
                if (!$bet_option_error) {
                    $this->_throwExcepiton(self::JUMP_TYPE_FOR_BET, $this->_msg_map['TICKET_LIST_EMPTY']);
                }
                $ticket_lottery_ids[] = $ticket_schedule['schedule_lottery_id'];
            }
            $series_type_error = $this->_jc_validator_obj->validateSeriesType(count($ticket['ticket_schedules']), $ticket['series_type']);
            if (!$series_type_error) {
                $this->_throwExcepiton(self::JUMP_TYPE_FOR_BET, $this->_msg_map['TICKET_LIST_EMPTY']);
            }

            $series_number_error = $this->_jc_validator_obj->validateSeriesNumberOverMaxLimit($ticket_lottery_ids, $ticket['series_type']);
            if (!$series_number_error) {
                $this->_throwExcepiton(self::JUMP_TYPE_FOR_BET, $this->_msg_map['TICKET_LIST_EMPTY']);
            }
        }
        H5Log('hahahah', 'wpay');
        if ($order_stake != $params['stake_count']) {
            $this->_throwExcepiton(self::JUMP_TYPE_FOR_BET, $this->_msg_map['TICKET_LIST_EMPTY']);
        }
        if ($params['total_amount'] != $order_stake * $params['order_multiple'] * LOTTERY_PRICE) {
            $this->_throwExcepiton(self::JUMP_TYPE_FOR_BET, $this->_msg_map['TICKET_LIST_EMPTY']);
        }
        $select_schedule_info_list = $this->queryScheduleInfoListForOptimize($params['select_schedule_ids']);
        if ($select_schedule_info_list == false) {
            $this->_throwExcepiton(self::JUMP_TYPE_FOR_BET, $this->_msg_map['OUT_OF_TIME']);
        }
    }

    private function _queryScheduleIdsOfAllLotteryForOptimize($select_schedule_info_list, $lottery_info){
        $schedule_ids_of_all_lottery = array();
        foreach ($select_schedule_info_list as $schedule_id => $scheduleInfo) {
            $schedule_end_time_unix_timestamp = strtotime($scheduleInfo['schedule_end_time']);
            $out_of_time = $schedule_end_time_unix_timestamp < (time() + intval($lottery_info['lottery_ahead_endtime']));
            if ($out_of_time) {
                $this->_throwExcepiton(self::JUMP_TYPE_FOR_BET, $this->_msg_map['OUT_OF_TIME']);
            }
            $mix_schedule_id = $scheduleInfo['schedule_id'];
            $day = $scheduleInfo['schedule_day'];
            $week = $scheduleInfo['schedule_week'];
            $round_no = $scheduleInfo['schedule_round_no'];

            $schedule_ids_of_all_lottery[$mix_schedule_id] = D('JcSchedule')->queryAllScheduleIdsFromScheduleNo($day, $week, $round_no);
        }
        return $schedule_ids_of_all_lottery;
    }

    private function _parseOrderContent($uid, $optimize_ticket_list, $select_schedule_info_list, $order_play_type, $order_multiple, $schedule_ids_of_all_lottery){
        $ticket_seq = 0;
        $ticket_data_list = array();
        $printout_ticket_list = array();

        foreach ($optimize_ticket_list as $ticket) {
            $ticket_series_type = $ticket['series_type'];
            $ticket_stake_count = $ticket['ticket_multiple'];
            $ticket_lottery_ids = array();
            foreach ($ticket['ticket_schedules'] as $ticket_schedule) {
                $ticket_lottery_ids[] = $ticket_schedule['schedule_lottery_id'];
            }
            $ticket_lottery_id = $this->_getTicketLotteryId($ticket_lottery_ids);
            H5Log('_parseOrderContent :' . $ticket_lottery_id, 'wpay');
            $ticket_schedules_info = $this->_buildTicketCompetitionContent($ticket['ticket_schedules'], $ticket_lottery_id, $order_play_type, $select_schedule_info_list, $schedule_ids_of_all_lottery);
            H5Log('buid= $ticket_schedules_info===:' . print_r($ticket['ticket_schedules'], true), 'wpay');
            H5Log('buid====:' . print_r($ticket_schedules_info, true), 'wpay');

            $devide_result = $this->_devideOverMultipleTicketForOptimize($uid, $ticket_lottery_id, $order_play_type, $ticket_seq, $ticket_stake_count, $order_multiple, $ticket_series_type, $ticket_schedules_info);
            H5Log('devie:' . print_r($devide_result, true), 'wpay');

            if (empty($devide_result)) {
                return false;
            }

            $ticket_seq = $devide_result['ticket_seq'];
            $ticket_data_list = array_merge($ticket_data_list, $devide_result['ticket_data_list']);
            $printout_ticket_list = array_merge($printout_ticket_list, $devide_result['printout_ticket_list']);
        }

        return array(
            'ticket_data_list' => $ticket_data_list,
            'printout_ticket_list' => $printout_ticket_list
        );
    }

    private function _getTicketLotteryId($ticket_lottery_ids){
        return $this->_jc_validator_obj->getTicketLotteryId($ticket_lottery_ids);
    }

    private function _buildTicketCompetitionContent($ticket_schedules, $ticket_lottery_id, $play_type, $select_schedule_info_list, $schedule_ids_of_all_lottery){
        $competitions = array();
        foreach ($ticket_schedules as $ticket_schedule) {
            $ticket_schedule_id = $ticket_schedule['id'];
            // $schedule_issue_no = $select_schedule_info_list[$ticket_schedule_id]['schedule_issue_no'];
            $bet_options = is_array($ticket_schedule['bet_options']) ? $ticket_schedule['bet_options'] : array(
                $ticket_schedule['bet_options']
            );

            $schedule_issue_no = $schedule_ids_of_all_lottery[$ticket_schedule_id][$ticket_lottery_id][$play_type]['schedule_issue_no'];
            $competitions[] = array(
                'lottery_id' => $ticket_schedule['schedule_lottery_id'],
                // 'schedule_id' => $ticket_schedule_id,
                'bet_options' => $bet_options,
                'issue_no' => $schedule_issue_no
            );

            $is_jc_mix_lottery_id = isJcMix($ticket_lottery_id);
            if ($is_jc_mix_lottery_id) {
                $ticket_schedule_time_list[] = array(
                    'schedule_issue_no' => $schedule_issue_no,
                    'schedule_end_time' => $schedule_ids_of_all_lottery[$ticket_schedule_id][$ticket_lottery_id][$play_type]['schedule_end_time'],
                    'schedule_game_start_time' => $schedule_ids_of_all_lottery[$ticket_schedule_id][$ticket_lottery_id][$play_type]['schedule_game_start_time'],
                    'schedule_id' => $ticket_schedule_id
                );
            } else {
                $ticket_schedule_time_list[] = array(
                    'schedule_issue_no' => $schedule_issue_no,
                    'schedule_end_time' => $schedule_ids_of_all_lottery[$ticket_schedule_id][$ticket_lottery_id][$play_type]['schedule_end_time'],
                    'schedule_game_start_time' => $schedule_ids_of_all_lottery[$ticket_schedule_id][$ticket_lottery_id][$play_type]['schedule_game_start_time'],
                    'schedule_id' => $schedule_ids_of_all_lottery[$ticket_schedule_id][$ticket_lottery_id][$play_type]['schedule_id']
                );
            }
            $all_ticket_issue_no[] = $schedule_issue_no;
        }

        asort($all_ticket_issue_no);
        $ticket_issue_nos = implode(',', $all_ticket_issue_no);

        $schedule_range_info = $this->checkScheduleTimeRangeInfo($ticket_schedule_time_list);
        $last_schedule_in_ticket = $schedule_range_info['last_schedule_info'];
        $first_schedule_in_ticket = $schedule_range_info['first_schedule_info'];

        return array(
            'ticket_lottery_id' => $ticket_lottery_id,
            'ticket_issue_nos' => $ticket_issue_nos,
            'issue_no' => $last_schedule_in_ticket['schedule_issue_no'],
            'last_schedule_issue_id' => $last_schedule_in_ticket['schedule_id'],
            'last_schedule_issue_no' => $last_schedule_in_ticket['schedule_issue_no'],
            'last_schedule_end_time' => $last_schedule_in_ticket['schedule_end_time'],
            'first_schedule_issue_id' => $first_schedule_in_ticket['schedule_id'],
            'first_schedule_issue_no' => $first_schedule_in_ticket['schedule_issue_no'],
            'first_schedule_end_time' => $first_schedule_in_ticket['schedule_end_time'],
            'competition' => $competitions
        );
    }

    private function _devideOverMultipleTicketForOptimize($uid, $ticket_lottery_id, $order_play_type, $ticket_seq, $ticket_stake_count, $order_multiple, $ticket_series_type, $ticket_schedules_info){
        $last_schedule_issue_id_in_ticket = $ticket_schedules_info['last_schedule_issue_id'];
        $last_schedule_end_time_in_ticket = $ticket_schedules_info['last_schedule_end_time'];
        $first_schedule_issue_id_in_ticket = $ticket_schedules_info['first_schedule_issue_id'];
        $first_schedule_end_time_in_ticket = $ticket_schedules_info['first_schedule_end_time'];
        $first_schedule_issue_no_in_ticket = $ticket_schedules_info['first_schedule_issue_no'];
        $last_schedule_issue_no_in_ticket = $ticket_schedules_info['last_schedule_issue_no'];


        $competitions = $ticket_schedules_info['competition'];

        $once_ticket_amount = LOTTERY_PRICE;
        $total_ticket_multiple = $order_multiple * $ticket_stake_count;

        if ($total_ticket_multiple > BET_JC_TICKET_TIME_LIMIT) {
            $limit_multiple = BET_JC_TICKET_TIME_LIMIT;
        } else {
            $limit_multiple = $total_ticket_multiple;
        }

        $limit_ticket_amount = $once_ticket_amount * $limit_multiple;
        if ($limit_ticket_amount > BET_TICKET_AMOUNT_LIMIT) {
            $max_once_ticket_multiple = floor(BET_TICKET_AMOUNT_LIMIT / $once_ticket_amount);
            $once_ticket_multiple = $max_once_ticket_multiple;
        } else {
            $once_ticket_multiple = $limit_multiple;
        }
        $devide_ticket_num = ceil($total_ticket_multiple / $once_ticket_multiple);

        for($i = 0; $i < $devide_ticket_num; $i++) {
            if ($i == $devide_ticket_num - 1) {
                $ticket_multiple = $total_ticket_multiple - ($devide_ticket_num - 1) * $once_ticket_multiple;
            } else {
                $ticket_multiple = $once_ticket_multiple;
            }
            $ticket_amount = $once_ticket_amount * $ticket_multiple;
            $ticket_seq++;
            // $printout_ticket_item = $this->buildCompetitionTicketItemForPrintout($ticket_seq, $ticket_lottery_id, $order_play_type, $ticket_series_type, 1, $ticket_multiple, $ticket_amount, $last_schedule_end_time_in_ticket, $first_schedule_end_time_in_ticket, $competitions);
            $printout_ticket_item = $this->buildCompetitionTicketItemForPrintout($ticket_seq, $order_play_type, $ticket_series_type, 1, $ticket_amount, $competitions, $last_schedule_end_time_in_ticket, $ticket_lottery_id, $ticket_multiple, $first_schedule_end_time_in_ticket,$first_schedule_issue_no_in_ticket,$last_schedule_issue_no_in_ticket);
            $printout_ticket_list[] = $printout_ticket_item;

            $ticket_data = $this->_buildTicketData($uid, $ticket_seq, $ticket_lottery_id, $order_play_type, $ticket_series_type, 1, $ticket_multiple, $ticket_amount, $ticket_schedules_info, $last_schedule_issue_id_in_ticket);

            $ticket_data_list[] = $ticket_data;
        }
        H5Log('$printticket_data_list:' . print_r($printout_ticket_list, true), 'wpay');
        H5Log('$ticket_data_list:' . print_r($ticket_data_list, true), 'wpay');

        return array(
            'ticket_seq' => $ticket_seq,
            'ticket_data_list' => $ticket_data_list,
            'printout_ticket_list' => $printout_ticket_list
        );
    }

    private function _buildTicketData($uid, $ticket_seq, $ticket_lottery_id, $order_play_type, $ticket_series_type, $ticket_stake_count, $ticket_multiple, $ticket_amount, $ticket_schedules_info, $last_schedule_issue_id_in_ticket){
        $competitions = $ticket_schedules_info['competition'];
        $formated_competitions = $this->formatBetOptionAddV($competitions);
        $competitions_json_string = json_encode($formated_competitions);
        H5Log('ticket:' . $ticket_lottery_id, 'opti');

        $verifyObj = Factory::createVerifyJcObj($ticket_lottery_id);
        H5Log($order_play_type, 'opti');
        if ($order_play_type == JC_PLAY_TYPE_MULTI_STAGE) {
            $ticket_issue_nos = $verifyObj->getIssueNos($competitions);
            H5Log('mult:' . $ticket_issue_nos, 'opti');
        } else {
            $ticket_issue_nos = $ticket_schedules_info['issue_no'];
            H5Log('single:' . $ticket_issue_nos, 'opti');
        }
        H5Log('issue:' . $ticket_issue_nos, 'opti');

        if (!$ticket_issue_nos) {
            return false;
        }
        if ($this->_is_emergency) {
            H5Log('competition:' . print_r($ticket_schedules_info, true), 'emer');
            $ticket_schedules_info['ticket_lottery_id'] = $ticket_lottery_id;
            $ticketOdds = $this->buildTicketOdds($ticket_schedules_info);
            return D('JcTicket')->buildTicketDataForEmergency($uid, $ticket_seq, $order_play_type, $ticket_stake_count, $ticket_series_type, $competitions_json_string, $last_schedule_issue_id_in_ticket, $ticket_issue_nos, $ticket_amount, $ticket_multiple, $ticketOdds);
        } else {
            return D('JcTicket')->buildTicketData($uid, $ticket_seq, $order_play_type, $ticket_stake_count, $ticket_series_type, $competitions_json_string, $last_schedule_issue_id_in_ticket, $ticket_issue_nos, $ticket_amount, $ticket_multiple, $ticket_schedules_info['first_schedule_issue_id'], $ticket_schedules_info['ticket_lottery_id']);
        }
    }

    private function _getOrderSeries($optimize_ticket_list){
        $series_list = array();
        foreach ($optimize_ticket_list as $optimize_ticket_item) {
            $series_list[] = $optimize_ticket_item['series_type'];
        }
        $series_list = array_unique($series_list);
        return implode(',', $series_list);
    }

    private function _getJcOrderDetailForOptimize($optimize_ticket_list, $order_id){
        $schedule_bet_content = array();
        foreach ($optimize_ticket_list as $ticket) {
            $ticket_lottery_ids = array();
            foreach ($ticket['ticket_schedules'] as $ticket_schedule) {
                $schedule_id = $ticket_schedule['id'];
                $ticket_lottery_id = $ticket_schedule['schedule_lottery_id'];
                $schedule_bet_option = $ticket_schedule['bet_options'];
                if (!in_array($schedule_bet_option, $schedule_bet_content[$schedule_id][$ticket_lottery_id])) {
                    $schedule_bet_content[$schedule_id][$ticket_lottery_id][] = $schedule_bet_option;
                }
            }
        }
        H5Log('bet:' . print_r($schedule_bet_content, true), 'wpay');
        $order_detail_list = array();
        foreach ($schedule_bet_content as $schedule_id => $bet_content_info) {
            $bet_content = json_encode(betOptionsAddV($bet_content_info));
            $order_detail_list[] = D('Home/JcOrderDetail')->buildDetailData($order_id, $schedule_id, $bet_content, 0);
        }
        return $order_detail_list;
    }

    private function _getLotteryInstance($lottery_id){
        H5Log('$lottery_id:'.$lottery_id, 'sfc');
        $lottery_prefix = 'SFC';
        if($lottery_id){
            return A('Home/'.$lottery_prefix,'Lottery');
        }
    }
}