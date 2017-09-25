<?php
namespace Home\Controller;
use Home\Controller\GlobalController;

class OrderController extends GlobalController {
	private $_uid = 0;
	private $_user_info = null;
	private $_order_info = null;
	private $_offset = 0;
	private $_limit = 20;
	
	public function queryTicketListInOrder($api){
		$this->_checkOrderViewPermission($api);
		$uid = $this->_uid;
		if($this->_uid == C('TEST_USER_ID')){
			$uid = $this->_order_info['uid'];
		}
		$response_data = $this->buildTicketDetailList($this->_order_info['lottery_id'], $uid,$api);
		return array(	'result' => $response_data,
				'code'   => C('ERROR_CODE.SUCCESS'));
	}

	public function buildTicketDetailList($lottery_id, $uid, $api,$is_ticket_detail=1){
		$ticket_detail_list = array();
		$ticketModel = getTicktModel($this->_order_info['lottery_id']);
		$ticket_list = $ticketModel->getTicketsByOrderId($this->_order_info['order_id'],$uid);
		if($is_ticket_detail && isJCLottery($this->_order_info['lottery_id'])){
			$schedule_list = D('JcOrderDetailView')->getScheduleInfoByIssueNo($this->_order_info['order_id']);
		}else{
		    if(isZcsfc($this->_order_info['lottery_id'])){
                $issue_id = D('Order')->getIssueId($this->_order_info['order_id']);
                $issue_info = D('Issue')->getIssueInfo($issue_id);
                $issue_no = $issue_info['issue_no'];
                $schedule_list = D('ZcsfcSchedule')->getScheduleListOfScore($issue_no);
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
		if($api->sdk_version >= 8){
            $ticket_detail_list = $this->_filterTicketList($ticket_detail_list);
        }
		$response['success_amount'] = $success_amount;
		$response['failure_amount'] = $failure_amount;	
		$response['winnings_bonus'] = $winnings_bonus;	
		$response['tickets'] = $ticket_detail_list;
		return $response;
	}

    public function buildTicketDetailListForCobetScheme($lottery_id, $uid, $order_id,$is_ticket_detail=1){
        $ticket_detail_list = array();
        $ticketModel = getTicktModel($lottery_id);
        $ticket_list = $ticketModel->getTicketsByOrderId($order_id,$uid);
        if($is_ticket_detail && isJCLottery($lottery_id)){
            $schedule_list = D('JcOrderDetailView')->getScheduleInfoByIssueNo($order_id);
        }else{
            if(isZcsfc($lottery_id)){
                $issue_id = D('Order')->getIssueId($order_id);
                $issue_info = D('Issue')->getIssueInfo($issue_id);
                $issue_no = $issue_info['issue_no'];
                $schedule_list = D('ZcsfcSchedule')->getScheduleListOfScore($issue_no);
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
			$jc_info_list = array();
			foreach($printout_odds_list as $schedule_bet_info){
				$jc_info['let_point'] = $schedule_bet_info['odds']['letPoint'];
				$jc_info['base_point'] = $schedule_bet_info['odds']['basePoint'];
				$jc_info['round_no'] = $this->_parseRoundNo($schedule_bet_info['issue_no'], $schedule_list);
				$jc_info['betting_order'] = getFormatOdds($schedule_bet_info['lottery_id'], json_encode($schedule_bet_info['odds']));
				$jc_info['score'] = $schedule_list[$schedule_bet_info['issue_no']]['score'];
				$jc_info_list[] = $jc_info;
			}
		}
		return $jc_info_list;
	}

	private function _parseRoundNo($issue_no, $schedule_list){
		$round_no = getWeekName($schedule_list[$issue_no]['schedule_week']).$schedule_list[$issue_no]['schedule_round_no'];
		return $round_no;
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
	
	private function _checkOrderViewPermission($api){
		$this->_user_info = $this->getAvailableUser($api->session);
		$this->_uid = $this->_user_info['uid'];
		$this->_offset = isset($api->offset) ? $api->offset : $this->_offset;
        $this->_limit = isset($api->limit) ? $api->limit : $this->_limit;
		
		$this->_order_info = D('Order')->getOrderInfo($api->order_id);
		\AppException::ifNoExistThrowException($this->_order_info, C('ERROR_CODE.ORDER_NO_EXIST'));
			
		$is_paid = ($this->_order_info['order_status'] > C('ORDER_STATUS.UNPAID'));
		if(!$is_paid){
			\AppException::throwException(C('ERROR_CODE.ORDER_STATUS_ERROR'));
		}

		if($this->_order_info['order_type'] != ORDER_TYPE_OF_COBET){
            $userOwen = (($this->_order_info['uid'] == $this->_user_info['uid']) || ($this->_user_info['uid'] == C('TEST_USER_ID')));
            \AppException::ifNoExistThrowException($userOwen, C('ERROR_CODE.ORDER_OWEN_ERROR'));
        }

	}
	
	public function deleteOrder($api) {
		$userInfo = $this->getAvailableUser($api->session);
		$uid = $userInfo['uid'];
	
		$orderInfo = D('Order')->getOrderInfo($api->order_id);
		\AppException::ifNoExistThrowException($orderInfo, C('ERROR_CODE.ORDER_NO_EXIST'));
		 
		$unpaid = ($orderInfo['order_status'] == C('ORDER_STATUS.UNPAID'));
		\AppException::ifNoExistThrowException($unpaid, C('ERROR_CODE.ORDER_STATUS_ERROR'));
		 
		$userOwen = ($orderInfo['uid'] == $uid);
		\AppException::ifNoExistThrowException($userOwen, C('ERROR_CODE.ORDER_OWEN_ERROR'));
		 
		$this->_deleteOrder($api->order_id, $orderInfo['lottery_id']);
		return array(	'result' => '',
						'code'   => C('ERROR_CODE.SUCCESS'));
	}
	
	public function getOrderStatusDesc($order_status, $order_winnings_status, $order_distribute_status,$is_order_list = false){
        $status_desc = '';
	    if($order_status == C('ORDER_STATUS.UNPAID')){
            $status_desc = '未支付';
        }else if($order_status == C('ORDER_STATUS.PRINTOUT_ERROR')){
            $status_desc = '出票失败';
        }else if ($order_status == C('ORDER_STATUS.PAYMENT_SUCCESS') || $order_status == C('ORDER_STATUS.PRINTOUTING')){
            $status_desc = '出票中';
        }else if($order_status == C('ORDER_STATUS.PRINTOUT_ERROR_REFUND')){
            $status_desc = '出票失败';
        }else if($order_status == C('ORDER_STATUS.BET_ERROR')){
            $status_desc = '出票失败';
        }else if($order_status == C('ORDER_STATUS.PRINTOUTING_PART_REFUND')){
            $status_desc = '出票中';
     	}else if($order_status == C('ORDER_STATUS.PRINTOUTED_PART_REFUND')){
            $status_desc = '部分出票失败';
        }else if($order_winnings_status == C('ORDER_WINNINGS_STATUS.WAITING')){
            $status_desc = '待开奖';//未开奖
        }else if($order_winnings_status == C('ORDER_WINNINGS_STATUS.NO')){
            $status_desc = '未中奖';//未中奖
        }else if($order_winnings_status == C('ORDER_WINNINGS_STATUS.YES')){
            if(!$is_order_list){
                $status_desc = '已中奖';
            }else{
                $status_desc = '';
            }
        }else if($order_winnings_status == C('ORDER_WINNINGS_STATUS.PART')){
            $status_desc = '部分派奖';
        }

        if($order_distribute_status == C('ORDER_DISTRIBUTE_STATUS.YES')){
            $status_desc = '等待派奖';//派奖中
        }
        return $status_desc;
	}

	public function detail($api) {
	    $userInfo   = $this->getAvailableUser($api->session);
	    $this->_user_info = $userInfo;
	    $this->_uid = $this->_user_info['uid'];
	     
		$lottery_id = D('Order')->getLotteryId($api->order_id);
		$order_view_model = $this->_getOrderViewModel($lottery_id);
		$orderInfo = $order_view_model->getOrderInfoByOrderId($api->order_id);
		\AppException::ifNoExistThrowException($orderInfo, C('ERROR_CODE.ORDER_NO_EXIST'));
		$order_status = $orderInfo['order_status'];
		$orderInfo['plus_award_amount'] = $orderInfo['order_plus_award_amount'];
		$orderInfo['status_desc'] = $this->getOrderStatusDesc($orderInfo['order_status'],$orderInfo['order_winnings_status'], $orderInfo['order_distribute_status']);
		$orderInfo['status'] = D('Order')->getStatus($orderInfo['order_status'], $orderInfo['order_winnings_status'], $orderInfo['order_distribute_status']);
		if ($orderInfo['status'] == 3 && !isZcsfc($orderInfo['lottery_id'])) {  // 未开奖的不要返回开奖号码
		    unset($orderInfo['prize_num']);
		}
		
		$owenUser = (($orderInfo['uid'] == $userInfo['uid']) || ($userInfo['uid'] == C('TEST_USER_ID')));
		\AppException::ifNoExistThrowException($owenUser, C('ERROR_CODE.ORDER_OWEN_ERROR'));
		
		$issue_id = D('Order')->getIssueId($api->order_id);
		if(isJc($lottery_id)){
			$orderInfo['series'] 	= $this->getBetType($lottery_id, $api->order_id);
			$orderInfo['jc_info'] 	= $this->getJcInfo($lottery_id, $api->order_id ,$order_status);
			$endTime = D('JcSchedule')->getEndTime($issue_id);
		}else{

			$uid = $this->_uid;
			if ($uid == C('TEST_USER_ID')) {
				$uid = $orderInfo['uid'];
			}
			$ticketList = $this->getTickets($lottery_id, $api->order_id, $uid,$orderInfo['order_status']);
			\AppException::ifNoExistThrowException($ticketList, C('ERROR_CODE.ORDER_DETAIL_NO_EXIST'));

            if (isZcsfc($lottery_id)) {
				// bettype字段
				$orderInfo['series'] = $this->getBetType($lottery_id, $api->order_id);
				$orderInfo['jc_info'] = $this->getZcsfcInfo($api->order_id);
			} else {
				$orderInfo['tickets'] = $ticketList['ticket_list'];
			}

			if($orderInfo['order_status']==5){
				$orderInfo['failure_amount'] = $orderInfo['total_amount'];
			}else{
				$orderInfo['failure_amount'] = $ticketList['failure_amount'];
			}
			$endTime = D('Issue')->getEndTime($issue_id);
		}
        $orderInfo['end_time']	= strtotime($endTime);

        $followBetInfoDetails = D('FollowBetInfoView')->getFollowBetDetailByOrderId($api->order_id);
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
		$uid = $this->_uid;
		if($this->_uid == C('TEST_USER_ID')){
			$uid = $this->_order_info['uid'];
		}

		if(isJc($lottery_id)){
            $ticket_detail_list = $this->buildTicketDetailList($lottery_id, $uid,$api,0);
			$orderInfo['failure_amount'] = $ticket_detail_list['failure_amount'];
		}
		
		return array(   'result' => $orderInfo,
						'code'   => C('ERROR_CODE.SUCCESS'));
	}

	private function _getFollowRefund($followBetInfoDetails,$orderInfo){
	    if(($followBetInfoDetails['follow_total_amount'] - $followBetInfoDetails['followed_amount']) > $orderInfo['order_reduce_consumption']){
	        return $followBetInfoDetails['follow_total_amount'] - $followBetInfoDetails['followed_amount'] - $orderInfo['order_reduce_consumption'];
        }else{
	        return 0;
        }

    }

    private function _getFollowStatus($fbi_status){
        return $fbi_status != C('FOLLOW_BET_INFO_STATUS.ON_GOING') ? 0 : 1;
    }
	
	public function cancelFollowOrder($api) {
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
            return $this->cancelFollowOrderOld($api);
        }

        $used_coupon_amount = A('FollowWorker')->getOrderUsedCoupon($followBetInfoDetails['fbi_id']);
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
        $cancel_result = A('StopFollowBet')->cancelFollowBetInfo($uid, $followBetInfoDetails['fbi_id'], $refund_money, $refund_coupon_amount, $orderInfo['user_coupon_id'],C('FOLLOW_BET_INFO_STATUS.CANCEL'));
        if(!$cancel_result){
            throw new \Think\Exception('', C('ERROR_CODE.DATABASE_ERROR'));
        }
        return array(   'result' => '',
            'code'   => C('ERROR_CODE.SUCCESS'));
	}

    public function cancelFollowOrderOld($api) {
        $userInfo = $this->getAvailableUser($api->session);
        $uid = $userInfo['uid'];

        $orderInfo = D('Order')->getOrderInfo($api->order_id);
        $userOwen = ($orderInfo['uid'] == $uid);
        \AppException::ifNoExistThrowException($userOwen, C('ERROR_CODE.ORDER_OWEN_ERROR'));

        $followInfo = D('FollowBet')->getFollowBetInfo($orderInfo['follow_bet_id']);

        // 要返还的总额
        $refund_amount = $followInfo['follow_remain_times'] * $orderInfo['order_total_amount'];

        $allowFollowStatus = $refund_amount>0 && $followInfo['follow_status'] == C('FOLLOW_STATUS.NORMAL');
        \AppException::ifNoExistThrowException($allowFollowStatus, C('ERROR_CODE.FOLLOW_BET_CAN_NOT_CANCEL'));

        $userAccount = D('UserAccount')->getUserAccount($uid);
        $frozenBalanceEnough = ($userAccount['user_account_frozen_balance'] >= $refund_amount);
        \AppException::ifNoExistThrowException($frozenBalanceEnough, C('ERROR_CODE.FROZEN_BALANCE_NO_ENOUGH'));

        // 现金支付总额
// 		$refund_cash = $orderInfo['order_total_amount'] + ($followInfo['follow_times'] * $orderInfo['order_total_amount']) - $orderInfo['order_coupon_consumption'];

        $first_order_info = D('Order')->getOrderInfo($followInfo['order_id']);

        $refund_cash = $first_order_info['order_total_amount'] + ($followInfo['follow_times'] * $first_order_info['order_total_amount']) - $first_order_info['order_coupon_consumption'];


        M()->startTrans();
        $saveStatus = D('FollowBet')->saveFollowBetStatus($orderInfo['follow_bet_id'], C('FOLLOW_STATUS.CANCEL'));
        if(empty($saveStatus)){
            M()->rollback();
            \AppException::ifNoExistThrowException($saveStatus, C('ERROR_CODE.ORDER_OWEN_ERROR'));
        }

        if($refund_amount > $refund_cash){
            $refund_coupon_amount = $refund_amount - $refund_cash;
            $refund_coupon_result = D('UserCoupon')->increaseCoupon($orderInfo['user_coupon_id'], $refund_coupon_amount);

            if(empty($refund_coupon_result)){
                M()->rollback();
                \AppException::ifNoExistThrowException($refund_coupon_result, C('ERROR_CODE.DATABASE_ERROR'));
            }
        }else{
            $refund_coupon_amount = 0;
            $refund_cash = $refund_amount;
        }

        $refund_result = D('UserAccount')->refundFollowMoney($uid, $refund_amount,$refund_cash,$refund_coupon_amount, $orderInfo['follow_bet_id'], C('USER_ACCOUNT_LOG_TYPE.CANCEL_FOLLOW'));
        if(empty($refund_result)){
            M()->rollback();
            \AppException::ifNoExistThrowException($refund_result, C('ERROR_CODE.DATABASE_ERROR'));
        }
        M()->commit();

        return array(   'result' => '',
            'code'   => C('ERROR_CODE.SUCCESS'));
    }
	
	private function _convertLotteryId($lotteryId) {
	    return $lotteryId;
	    
// 	    if (isJczq($lotteryId)) {
// 	        return  C('JC.JCZQ');
// 	    } elseif (isJclq($lotteryId)) {
// 	        return  C('JC.JCLQ');
// 	    } else {
// 	        return $lotteryId;
// 	    }
	}
	
	public function orders($api) {
		$userInfo = $this->getAvailableUser($api->session);
		$uid = $userInfo['uid'];

		$category = empty($api->category) ? 0 : $api->category;
        if($category == 0){
            if ($uid == C('TEST_USER_ID')) {
                $orders = D('Order')->getBigOrders($api->lottery_id, $api->order_type, $api->offset, $api->limit,$category);
            } else {
                $orders = D('Order')->getOrderInfos($uid, $api->lottery_id, $api->order_type, $api->offset, $api->limit,$category);
            }

            $lottery_info = D('Lottery')->getLotteryMap();
            $result = array();
            foreach ($orders as $order_info){
                $order = array();
                $order = $order_info;
                $data = array();
                $data['id']				= $order['order_id'];
                $data['lottery_id'] 	= $order['lottery_id'];
                $data['lottery_name'] 	= $lottery_info[$order['lottery_id']]['lottery_name'];
                $data['lottery_image'] 	= $lottery_info[$order['lottery_id']]['lottery_image'];
                $data['type'] 			= ($order['follow_bet_id'] ? ORDER_TYPE_OF_FOLLOW : ORDER_TYPE_OF_NORMAL);
                $data['status_desc'] = $this->getOrderStatusDesc($order['order_status'],$order['order_winnings_status'], $order['order_distribute_status'],true);
                $data['status'] 		= D('Order')->getStatus($order['order_status'], $order['order_winnings_status'], $order['order_distribute_status']);
                $data['winnings_bonus'] = $order['order_winnings_bonus'];
                $data['plus_award_amount'] = $order['order_plus_award_amount'];
                $data['total_amount'] 	= $order['order_total_amount'];
                $data['buying_time'] 	= strtotime($order['order_create_time']);
                $result[] = $data;
            }

        }elseif($category == 1){
            $follow_bet_info_list = D('FollowBetInfo')->getFollowInfoListByType($uid,$api->order_type,$api->offset, $api->limit);
            $lottery_info = D('Lottery')->getLotteryMap();
            $result = array();
            foreach ($follow_bet_info_list as $follow_bet_info){
                $follow_bet_detail = D('FollowBetInfoView')->getFollowBetDetailByOrderId($follow_bet_info['order_id']);
                $order_ids = D('FollowBetDetail')->getOrderIdsByFbiId($follow_bet_info['fbi_id']);
                $follow_bet_info_status_desc = $this->getFollowBetInfoStatusDesc($follow_bet_detail,$order_ids);
                $data = array();
                $data['id']				= $follow_bet_info['fbi_id'];
                $data['lottery_id'] 	= $follow_bet_info['lottery_id'];
                $data['lottery_name'] 	= $lottery_info[$follow_bet_info['lottery_id']]['lottery_name'];
                $data['lottery_image'] 	= $lottery_info[$follow_bet_info['lottery_id']]['lottery_image'];
                $data['type'] 			= ORDER_TYPE_OF_FOLLOW;
                $data['status_desc'] = $follow_bet_info_status_desc['status_desc'];
                $data['status'] 		= $follow_bet_info_status_desc['status'];
                $data['total_amount'] 	= $follow_bet_info['follow_total_amount'];
                $data['buying_time'] 	= strtotime($follow_bet_info['fbi_createtime']);
                $current_follow_detail_info = D('FollowBetInfoView')->getFollowBetDetailCurrentInfo($follow_bet_info['fbi_id']);
                if(empty($current_follow_detail_info)){
                    $fbd_id = D('FollowBetDetail')->getLastFollowDetailByFbiId($follow_bet_info['fbi_id']);
                    $current_follow_detail_info = D('FollowBetInfoView')->getFollowBetDetailIdsByFbdId($fbd_id);
                }

                $data['follow_times'] = $current_follow_detail_info['follow_times'];
                $data['current_follow_times'] = ($current_follow_detail_info['fbd_index'] - 1);
                $result[] = $data;
            }

        }elseif($category == 2){
            $lottery_info = D('Lottery')->getLotteryMap();
            if($api->filter == 1){
                $scheme_ids = D('CobetScheme')->getSchemeIdsByUid($uid);
            }else{
                $scheme_ids = D('CobetSchemeView')->getSchemeIdsByUid($uid);
            }

            $scheme_list = D('CobetOrderView')->getSchemeListBySchemeIds($scheme_ids,$api->offset, $api->limit);
            foreach($scheme_list as $scheme_info){
                if(empty($scheme_info['order_id'])){
                    $total_amount = $scheme_info['scheme_total_amount'] - $scheme_info['scheme_refund_amount'];
                }else{
                    $total_amount = $scheme_info['order_total_amount'] - $scheme_info['order_refund_amount'];
                }
                $status_list = $this->getCobetOrderStatusDesc($scheme_info);
                $result[] = array(
                    'id' => $scheme_info['scheme_id'],
                    'lottery_id' => $scheme_info['lottery_id'],
                    'lottery_name' => $lottery_info[$scheme_info['lottery_id']]['lottery_name'],
                    'lottery_image' =>  $lottery_info[$scheme_info['lottery_id']]['lottery_image'],
                    'type' => ORDER_TYPE_OF_COBET,
                    'status_desc' => emptyToStr($status_list['status_desc']),
                    'status' => $status_list['status'],
                    'total_amount' =>  $total_amount,
                    'buying_time' => strtotime($scheme_info['scheme_createtime']),
                    'winnings_bonus' => empty($scheme_info['scheme_winning_bonus']) ? 0 : $scheme_info['scheme_winning_bonus'],
                );
            }
        }
		 
		$result = ( $result ? $result : array() );
		return array(   'result' => array('list'=>$result),
						'code'   => C('ERROR_CODE.SUCCESS'));
	}

    public function getFollowBetInfoStatusDesc($follow_bet_detail,$order_ids){
        $data = array();
        switch ($follow_bet_detail['fbi_status']){
            case C('FOLLOW_BET_INFO_STATUS.ON_GOING') :
                $data['status'] = C('FOLLOW_BET_INFO_API_STATUS.ON_GOING');
                $data['status_desc'] = C('FOLLOW_BET_INFO_API_STATUS_DESC.ON_GOING');
                break;
            case C('FOLLOW_BET_INFO_STATUS.PRIZE_STOP') :
                $order_winning_status_list = D('Home/Order')->getOrderWinningStatusByIds($order_ids);
                if($this->_getOrderIdsIsPrize($order_winning_status_list)){
                    $data['status'] = C('FOLLOW_BET_INFO_API_STATUS.CANCEL_PRIZE');
                    $data['status_desc'] = C('FOLLOW_BET_INFO_API_STATUS_DESC.CANCEL_PRIZE');
                }
                break;
            case C('FOLLOW_BET_INFO_STATUS.CANCEL') :
                $order_status_list = D('Home/Order')->getOrderWinningStatusByIds($order_ids);
                if($this->_getOrderIdsIsPrize($order_status_list)){
                    $data['status'] = C('FOLLOW_BET_INFO_API_STATUS.CANCEL_PRIZE');
                    $data['status_desc'] =C('FOLLOW_BET_INFO_API_STATUS_DESC.CANCEL_PRIZE');
                }else{
                    $data['status'] = C('FOLLOW_BET_INFO_API_STATUS.CANCEL_NO_PRIZE');
                    $data['status_desc'] = C('FOLLOW_BET_INFO_API_STATUS_DESC.CANCEL_NO_PRIZE');
                }
                break;
            case C('FOLLOW_BET_INFO_STATUS.ENDING') :
                $order_status_list = D('Home/Order')->getOrderWinningStatusByIds($order_ids);
                if($this->_getOrderIdsIsPrize($order_status_list)){
                    $data['status'] = C('FOLLOW_BET_INFO_API_STATUS.ENDING_PRIZE');
                    $data['status_desc'] = C('FOLLOW_BET_INFO_API_STATUS_DESC.ENDING_PRIZE');
                }else{
                    $data['status'] = C('FOLLOW_BET_INFO_API_STATUS.ENDING_NO_PRIZE');
                    $data['status_desc'] = C('FOLLOW_BET_INFO_API_STATUS_DESC.ENDING_NO_PRIZE');
                }
                break;
        }
        return $data;
    }


    private function _getOrderIdsIsPrize($order_winning_status_list){
        return in_array(1,$order_winning_status_list) || in_array(2,$order_winning_status_list);
    }

	
	
	
	private function _findJclqLetPoint($order_id){
		$ticketInfos = D('JclqTicket')->getTicketInfos($order_id);
		$result = array();
		foreach ($ticketInfos as $v){
			$printout_odds = json_decode($v['printout_odds'], true);
			$let_point = array_search_value('let_point', $printout_odds);
			if($let_point){
				$result[$v['winnings_status']] = $let_point;
			}
		}
		//多个ticket不同以中奖的为准1中奖、0未中奖
		$let_point = $result[1] ? $result[1] : $result[0];
		return $let_point;
	}
	
	private function _findJclqPrintoutContent($order_id, $field){
		$ticketInfos = D('JclqTicket')->getTicketInfos($order_id);
		$result = array();
		foreach ($ticketInfos as $v){
			$printout_odds = json_decode($v['printout_odds'], true);
			$let_point = array_search_value($field, $printout_odds);
			if($let_point){
				$result[$v['winnings_status']] = $let_point;
			}
		}
		//多个ticket不同以中奖的为准1中奖、0未中奖
		$value = $result[1] ? $result[1] : $result[0];
		return $value;
	}
	
	public function getJcInfo($lotteryId, $orderId, $order_status=0){
		$jcInfo = D('JcOrderDetailView')->getInfos($orderId);
		
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
// 				$let_point = $this->_findJclqPrintoutContent($orderId, 'letPoint');
// 				$base_point = $this->_findJclqPrintoutContent($orderId, 'basePoint');
				$let_point = array_search_value('letPoint', json_decode($v['schedule_odds'], true));
				$base_point = array_search_value('basePoint', json_decode($v['schedule_odds'], true));
			}
			if(isJcMix($lotteryId)){
				$format_result_odds = json_decode($v['schedule_odds'], true);
			}else{
				$format_result_odds[$lotteryId] = json_decode($v['schedule_odds'], true);
			}
			$jcInfo[$k]['let_point'] = $let_point ? $let_point : '';
			$jcInfo[$k]['base_point'] = $base_point ? $base_point : '';
			$jcInfo[$k]['result_odds'] = empty($format_result_odds) ? array():$format_result_odds;
				
			unset($jcInfo[$k]['schedule_odds']);
		}
		return $jcInfo;
	}
	
	public function getBetType($lotteryId, $orderId){
		$model = getTicktModel($lotteryId);
		$betTypes = $model->getBetTypesByOrderId($orderId);
		$betTypes = array_unique($betTypes);
		return implode(',', $betTypes);
	}
	
	
	private function _getOrderViewModel($lottery_id){
		$model_name = ( isJc($lottery_id) ? 'JcOrderView' : 'OrderView' );
		return D($model_name);
	}
	
	
	public function getTickets($lottery_id, $order_id, $uid, $order_status ){
		$ticketModel = getTicktModel($lottery_id);
		$tickets = $ticketModel->getTicketsByOrderId($order_id,$uid);
		$ticketList = array();
		$failure_amount = 0;
		foreach ($tickets as $ticket) {
			$ticket_info = array(
				'bet_number'    => $ticket['bet_number'],
				'play_type'     => $ticket['play_type'],
				'bet_type'      => $ticket['bet_type'],
				'stake_count'   => $ticket['stake_count'],
				'winnings_status' => $ticket['winnings_status'],
				'ticket_status' => ($order_status==5)? 2: $ticket['ticket_status'],
				'ticket_multiple' => $ticket['ticket_multiple'],
			);
			if($ticket_info['ticket_status']==C('TICKET_STATUS.PRINTOUT_FAIL')){
				$failure_amount += $ticket['total_amount'];
			}

			$ticketList[] = $ticket_info;
		}
		if(!$ticketList){
			return false;
		}
		$res['ticket_list'] = $ticketList;
		$res['failure_amount'] = $failure_amount;
		return $res;
	}

	
	private function _deleteOrder($orderId, $lotteryId) {
		try {
			M()->startTrans();
			$deleted = D('Order')->deleteUnpaidOrder($orderId);
			\AppException::ifExistThrowException($deleted===false, C('ERROR_CODE.DATABASE_ERROR'));
	
			$ticketModel = getTicktModel($lotteryId);
			$deleted = $ticketModel->deleteTicketByOrderId($orderId);
			\AppException::ifExistThrowException($deleted===false, C('ERROR_CODE.DATABASE_ERROR'));
			 
			$followBetId = D('Order')->getFollowBetId($orderId);
			$deleted = D('FollowBet')->deleteFollowBet($followBetId);
			\AppException::ifExistThrowException($deleted===false, C('ERROR_CODE.DATABASE_ERROR'));
			M()->commit();
		} catch (\Think\Exception $e) {
			M()->rollback();
			throw new \Think\Exception($e->getMessage(), $e->getCode());
		}
	}

    public function getZcsfcInfo($orderId){
        $zcsfc_list = array();
        $order_info = D('Order')->getOrderInfo($orderId);
        $order_content = json_decode($order_info['order_content'],true);
        $issue_id = $order_info['issue_id'];
        $issue_info = D('Issue')->getIssueInfo($issue_id);
        $issue_no = $issue_info['issue_no'];
        $schedule_seq_list = array_keys($order_content);
        $sfc_schedule_list = D('ZcsfcSchedule')->queryScheduleListByIssueNoAndSeq($issue_no,$schedule_seq_list);
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

    public function getCobetOrderStatusDesc($scheme_info){
        switch ($scheme_info['scheme_status']) {
            case C('COBET_SCHEME_STATUS.CANCEL') :
                $data['status'] = C('API_COBET_SCHEME_STATUS.CANCEL');
                $data['status_desc'] = C('API_COBET_SCHEME_STATUS_DESC.CANCEL');
                break;
            case C('COBET_SCHEME_STATUS.CANCEL_REFUND') :
                $data['status'] = C('API_COBET_SCHEME_STATUS.CANCEL_REFUND');
                $data['status_desc'] = C('API_COBET_SCHEME_STATUS_DESC.CANCEL_REFUND');
                break;
            case C('COBET_SCHEME_STATUS.FAILED') :
                $data['status'] = C('API_COBET_SCHEME_STATUS.FAILED');
                $data['status_desc'] = C('API_COBET_SCHEME_STATUS_DESC.FAILED');
                break;
            case C('COBET_SCHEME_STATUS.FAILED_REFUND') :
                $data['status'] = C('API_COBET_SCHEME_STATUS.FAILED_REFUND');
                $data['status_desc'] = C('API_COBET_SCHEME_STATUS_DESC.FAILED_REFUND');
                break;
            default :
                $data['status'] = C('API_COBET_SCHEME_STATUS.ONGOING');
                $data['status_desc'] = C('API_COBET_SCHEME_STATUS_DESC.ONGOING');
                break;
        }
        if(!empty($scheme_info['order_id'])){
            $order_info = D('Order')->getOrderInfo($scheme_info['order_id']);
            $data['status_desc'] = $this->getOrderStatusDesc($order_info['order_status'],$order_info['order_winnings_status'], $order_info['order_distribute_status']);
            $data['status'] 		= D('Order')->getStatus($order_info['order_status'], $order_info['order_winnings_status'], $order_info['order_distribute_status']);
        }
        return $data;
    }
	
}