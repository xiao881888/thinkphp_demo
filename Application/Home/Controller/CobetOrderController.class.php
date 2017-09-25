<?php
namespace Home\Controller;
use Home\Util\Factory;
use Think\Exception;

class CobetOrderController extends BettingBaseController {

    public $_redis;
    public function __construct(){
        import('@.Util.AppException');
        parent::__construct();
        $this->_redis = Factory::createAliRedisObj();
        $this->_redis->select(0);
    }

    public function trigger(){
        $complete_scheme_list = D('CobetScheme')->getCompleteSchemeList();
        foreach($complete_scheme_list as $scheme_info){
            $lock_status = $this->_lock($scheme_info['scheme_id']);
            if(!$lock_status){
                ApiLog(' trigger:'.$scheme_info['scheme_id'],'$lock_status:'.$lock_status, 'addOrder');
                continue;
            }
            $this->addOrder($scheme_info);
            $this->_unlock($scheme_info['scheme_id']);
        }
    }

    private function _lock($scheme_id,$expire_time = 60){
        $redis_key = $this->_getLockKey($scheme_id);
        $is_lock = $this->_redis->setnx($redis_key,time()+$expire_time);
        if(!$is_lock){
            $lock_time = $this->_redis->get($redis_key);
            if(time()>$lock_time){
                $this->_unlock($scheme_id);
                $is_lock = $this->_redis->setnx($redis_key,time()+$expire_time);
            }
        }
        return $is_lock?true:false;
    }

    private function _getLockKey($scheme_id){
        return 'cobet_scheme:add_order:'.$scheme_id;
    }

    private function _unlock($scheme_id){
        return $this->_redis->del($this->_getLockKey($scheme_id));
    }

    public function addOrder($scheme_info){
        ApiLog('开始下单$scheme_info:'.print_r($scheme_info,true),'addOrder');
        if($scheme_info['order_id'] || $scheme_info['scheme_status'] == COBET_SCHEME_STATUS_OF_PRINTOUT){
            $this->notifyWarningMsg('已经下过订单id:'.$scheme_info['scheme_id']);
            ApiLog('已经下过订单id:'.print_r($scheme_info,true),'addOrder');
            return false;
        }
        $order_info = json_decode($scheme_info['scheme_bet_content'],true);
        $uid = $scheme_info['uid'];
        $scheme_id = $scheme_info['scheme_id'];

        try{
            if(isJc($scheme_info['lottery_id'])){
                $add_status = $this->addJcOrder($order_info,$uid,$scheme_id);
            }elseif(isZcsfc($scheme_info['lottery_id'])){
                $add_status = $this->addZcsfcOrder($order_info,$uid,$scheme_id);
            }else{
                $add_status = $this->addSzcOrder($order_info,$uid,$scheme_id);
            }
            if(!$add_status){
                ApiLog('下单失败:'.print_r($order_info,true),'addOrder');
                $this->notifyWarningMsg('下单失败id:'.$scheme_info['scheme_id']);
                $this->refundAmountForSchemeFailed($scheme_info);
                return '';
            }
        }catch(Exception $e){
            ApiLog('id:'.$scheme_info['scheme_id'].' file:'.$e->getFile().' line:'.$e->getLine().' code:'.$e->getCode(),'addOrderException');
            $this->notifyWarningMsg('下单失败id:'.$scheme_info['scheme_id']);
            $this->refundAmountForSchemeFailed($scheme_info);
            return '';
        }
        ApiLog('下单success$scheme_info:'.print_r($scheme_info,true),'addOrder');
    }

    public function addSzcOrder($order_info,$uid,$scheme_id) {
        $issue_id = $order_info['issue_id'];
        $multiple = $order_info['multiple'];
        $issueInfo 	= D('Issue')->getIssueInfo($issue_id);
        if(empty($issueInfo)){
            ApiLog('ISSUE_NO_EXIST'.print_r($order_info,true),'addSzcOrder');
            return false;
        }

        $user_info = D('User')->getUserInfo($uid);
        if(empty($user_info)){
            ApiLog('USER_INFO IS NULL'.print_r($order_info,true),'addSzcOrder');
            return false;
        }

        $lotteryInfo = D('Lottery')->getLotteryInfo($issueInfo['lottery_id']);
        if(empty($lotteryInfo)){
            ApiLog('LOTTERY_NO_EXIST $order_info:'.print_r($order_info,true),'addSzcOrder');
            return false;
        }

        $is_limit = $this->isLimitLottery($issueInfo['lottery_id']);
        if($is_limit){
            ApiLog('彩期被禁用$order_info:'.print_r($order_info,true),'addSzcOrder');
            return false;
        }

        $beforeDeadline = ( strtotime($issueInfo['issue_end_time']) - time() > $lotteryInfo['lottery_ahead_endtime'] );
        if(!$beforeDeadline){
            ApiLog('OUT_OF_ISSUE_TIME $order_info:'.print_r($order_info,true),'addSzcOrder');
            return false;
        }

        $limitBetCode = $this->limitBetNum($order_info['issue_id'],$issueInfo['lottery_id'],$order_info['tickets']);
        if($limitBetCode!=C('ERROR_CODE.SUCCESS')){
            ApiLog('彩期被限号$order_info:'.print_r($order_info,true),'addSzcOrder');
            return false;
        }
        $checkTicketsCode = $this->checkNumberTickets($order_info['tickets'], $issueInfo['lottery_id']);
        if($checkTicketsCode!=C('ERROR_CODE.SUCCESS')){
            ApiLog('tickets格式错误$order_info:'.print_r($order_info,true),'addSzcOrder');
            return false;
        }

        $orderTotalAmount = $this->calcSzcOrderTotalAmountForOneTime($order_info['tickets'], $multiple);

        $check_code = $this->_checkSchemeAmount($scheme_id,$orderTotalAmount);
        if(!$check_code){
            ApiLog('金额非法:'.print_r($order_info,true),'addSzcOrder');
            return false;
        }

        $orderSku = buildOrderSku($uid);
        $orderTicket = $this->addSzcOrderAndTicketsForNewFollow($issueInfo['lottery_id'], $uid, $orderTotalAmount,
            $orderSku, $issue_id, $multiple, 0, $order_info['tickets'], 1, $order_info['order_identity'],array(),0,0,0,0,ORDER_TYPE_OF_COBET);
        if (!$orderTicket) {
            ApiLog('自动下单失败$order_info:'.print_r($order_info,true),'addSzcOrder');
            return false;
        }
        $orderId 	 = $orderTicket['orderId'];
        $ticketList  = $orderTicket['ticketList'];
        $printOutResult = $this->printOutTicket($user_info, $issueInfo['issue_no'], $orderId, $issueInfo['lottery_id'], $ticketList, $multiple);
        ApiLog('print_out:'.print_r($printOutResult,true), 'addSzcOrder');
        if($printOutResult){
            ApiLog('commit:'.print_r($printOutResult,true), 'addSzcOrder');
        }else{
            ApiLog('rollback:'.print_r($printOutResult,true), 'addSzcOrder');
            ApiLog('出票失败$order_info:'.print_r($order_info,true),'addSzcOrder');
            return false;
        }

        $save_status = $this->_saveOrderAndScheme($order_info,$orderId,$scheme_id);
        if(!$save_status){
            ApiLog('saveOrderAndScheme失败$order_info:'.print_r($order_info,true),'addSzcOrder');
            return false;
        }

        return true;
    }

	public function addZcsfcOrder($order_info,$uid,$scheme_id){
		$lottery_info = D('Lottery')->getLotteryInfo($order_info['lottery_id']);
		if(empty($lottery_info)){
            ApiLog('LOTTERY_NO_EXIST $order_info:'.print_r($order_info,true),'addZcsfcOrder');
            return false;
		}

        $user_info = D('User')->getUserInfo($uid);
        if(empty($user_info)){
            ApiLog('USER_INFO IS NULL $order_info：'.print_r($order_info,true),'addZcsfcOrder');
            return false;
        }

        $is_limit = $this->isLimitLottery($order_info['lottery_id']);
        if($is_limit){
            ApiLog('彩期被禁用$order_info:'.print_r($order_info,true),'addZcsfcOrder');
            return false;
        }

		$lottery_obj_instance = $this->_getLotteryInstance($order_info['lottery_id']);
		$verified_params = $lottery_obj_instance->verifyParamsForCobet($order_info, $user_info);
		if(!$verified_params){
            ApiLog('PARAM_ERROR:'.print_r($order_info,true),'addZcsfcOrder');
            return false;
		}

        $order_total_amount = $verified_params['order_total_amount'];
        $check_code = $this->_checkSchemeAmount($scheme_id,$order_total_amount);
        if(!$check_code){
            ApiLog('金额非法:'.print_r($order_info,true),'addZcsfcOrder');
            return false;
        }

        $order_sku = buildOrderSku($uid);
        $add_result = $lottery_obj_instance->addOrderAndTicketData($uid, $order_sku, $verified_params,ORDER_TYPE_OF_COBET);
        if(!$add_result){
            ApiLog('DATABASE_ERROR:'.print_r($order_info,true),'addZcsfcOrder');
            return false;
        }
        $order_id = $add_result['order_id'];
        $printout_ticket_list = $add_result['printout_ticket_list'];

        $printout_result = $this->printOutTicket($user_info, $verified_params['issue_no'], $order_id, $verified_params['lottery_id'], $printout_ticket_list, $verified_params['order_multiple']);
        ApiLog('print_out:'.print_r($printout_result,true), 'addZcsfcOrder');
        if($printout_result){
            ApiLog('commit:'.print_r($printout_result,true), 'addZcsfcOrder');
        }else{
            ApiLog('rollback:'.print_r($printout_result,true), 'addZcsfcOrder');
            ApiLog('出票失败$order_info:'.print_r($order_info,true),'addZcsfcOrder');
            return false;
        }

        $save_status = $this->_saveOrderAndScheme($order_info,$order_id,$scheme_id);
        if(!$save_status){
            ApiLog('saveOrderAndScheme失败$order_info:'.print_r($order_info,true),'addZcsfcOrder');
            return false;
        }

        return true;
	}

    private function _getLotteryInstance($lottery_id){
        $lottery_prefix = 'SFC';
        if($lottery_id){
            return A($lottery_prefix,'Lottery');
        }
    }

	
	public function addJcOrder($order_info,$uid,$scheme_id){
		$lottery_id = $order_info['lottery_id'];
        $lottery_info = D('Lottery')->getLotteryInfo($order_info['lottery_id']);
        if(empty($lottery_info)){
            ApiLog('LOTTERY_NO_EXIST $order_info:'.print_r($order_info,true),'addJcOrder');
            return false;
        }

        $is_limit = $this->isLimitLottery($lottery_id);
        if($is_limit){
            ApiLog('ISSUE_NO_EXIST $order_info:'.print_r($order_info,true),'addJcOrder');
            return false;
        }

        $user_info = D('User')->getUserInfo($uid);
        if(empty($user_info)){
            ApiLog('USER_INFO IS NULL $order_info：'.print_r($order_info,true),'addJcOrder');
            return false;
        }

        $check_code = $this->_checkSchemeAmount($scheme_id,$order_info['total_amount']);
        if(!$check_code){
            ApiLog('金额非法:'.print_r($order_info,true),'addJcOrder');
            return false;
        }

        $orderSku = buildOrderSku($uid);
        $orderTicket = $this->_addJCOrderAndTicket($uid, $orderSku, $order_info);
        $order_id = $orderTicket['orderId'];
        $ticketList = $orderTicket['ticketList'];
        $issueNo = $orderTicket['issueNo'];
        $first_issue_no = $orderTicket['firstIssueNo'];

        if (!$orderTicket) {
            ApiLog('TICKET_ERROR:'.print_r($order_info,true),'addJcOrder');
            return false;
        }
        ApiLog('$orderTickets:' . count($orderTicket), 'addJcOrder');

        $printOutResult = $this->printOutTicket($user_info, $issueNo, $order_id, $order_info['lottery_id'], $ticketList, $order_info['multiple'],$first_issue_no);
        ApiLog('jc print_out :' . ($printOutResult), 'addJcOrder');
        if(!$printOutResult){
            ApiLog('出票失败$order_info:'.print_r($order_info,true),'addJcOrder');
            return false;
        }
        ApiLog('jc begin payOrderWithTransaction', 'addJcOrder');

        $save_status = $this->_saveOrderAndScheme($order_info,$order_id,$scheme_id);
        if(!$save_status){
            ApiLog('saveOrderAndScheme失败$order_info:'.print_r($order_info,true),'addJcOrder');
            return false;
        }

        return true;

	}

	private function _saveOrderAndScheme($order_info,$order_id,$scheme_id){
        M()->startTrans();
        $saveOrder = D('Order')->savePaidOrder($order_id, C('ORDER_STATUS.PAYMENT_SUCCESS'), 0, 0, 0);
        if(!$saveOrder){
            ApiLog('ORDER_STATUS_ERROR:'.print_r($order_info,true), 'addOrder');
            M()->rollback();
            return false;
        }

        $save_status = D('CobetScheme')->updateSchemeForSuccess($scheme_id,$order_id);
        if(!$save_status){
            ApiLog('SAVE_STATUS_ERROR:'.print_r($order_info,true), 'addOrder');
            return false;
        }
        M()->commit();
        return true;
    }

	
	private function _addJCOrderAndTicket($uid, $orderSku, $order_info) {
		$verifyObj	= Factory::createVerifyJcObj($order_info['lottery_id']);
		$isVaildBetNumber = $verifyObj->checkCompetitionTickets($order_info['schedule_orders'], $order_info['series']);

		if(!$isVaildBetNumber){
            ApiLog('isvalid:'.$isVaildBetNumber.'$order_info:'.print_r($order_info,true), 'addJcOrder');
            return false;
		}
		if (isJcMix($order_info['lottery_id'])) {
			$formated_schedule_orders = $this->formatRequestScheduleOrders($order_info['schedule_orders']);
			$tickets_from_combination = $verifyObj->convertScheduleOrderToTickets($formated_schedule_orders, $order_info['series'], $order_info['lottery_id']);
			if (empty($tickets_from_combination)) {
				return false;
			} elseif (count($tickets_from_combination) > 2000) {
				return false;
			}
			return $this->_addJcMixtureOrder($uid, $orderSku, $order_info, $tickets_from_combination);
		} else {
			return $this->_addJcNoMixtureOrder($uid, $orderSku, $order_info);
		}
	}
	
    private function _addJcMixtureOrder($uid, $orderSku, $order_info, $tickets_from_combination) {
    	$series 	= explode(',', $order_info['series']);
    	$jcTicketInfo = $this->_buildJcMixtureTicketInfo($uid, $order_info['schedule_orders'], $tickets_from_combination,
            $order_info['stake_count'], $order_info['total_amount'], $order_info['multiple'], $order_info['lottery_id']);
    	if(empty($jcTicketInfo)){
    	    ApiLog('$jcTicketInfo:'.print_r($jcTicketInfo,true),'addJcOrder');
            return false;
    	}
    	
    	$orderTicket = $this->_addJzOrderSchedule($order_info['lottery_id'], $uid, $order_info['total_amount'],
    			$orderSku, $order_info['multiple'], $order_info['coupon_id'], $order_info['schedule_orders'], $jcTicketInfo, $order_info['order_identity'], $order_info);
    	
    	return $orderTicket;
    }
    
    private function _buildJcMixtureTicketInfo($uid, array $scheduleOrders, array $tickets_from_combination, $stakeCount, $totalAmount, $multiple, $lotteryId) {
    	$schedule_ids_in_order 	= array_column($scheduleOrders, 'schedule_id');
    	ApiLog('ids:'.print_r($schedule_ids_in_order,true) ,'cobet_pay');
    	$schedule_infos_in_order = D('JcSchedule')->getScheduleIssueNo($schedule_ids_in_order);
    	if(!$schedule_infos_in_order){
    	    throw new Exception('',C('ERROR_CODE.SCHEDULE_NO_ERROR'));
    	}
    	
    	$lottery_info = D('Lottery')->getLotteryInfo($lotteryId);
    	$this->checkScheduleOutOfTime($schedule_infos_in_order, $lottery_info);
    	
    	$schedule_ids_of_all_lottery = array();
    	foreach($schedule_infos_in_order as $schedule_id=>$scheduleInfo){
    		$schedule_end_time_unix_timestamp = strtotime($scheduleInfo['schedule_end_time']);
    		$scheduleInfo['schedule_end_time_unix_timestamp'] = $schedule_end_time_unix_timestamp;
    		$mix_schedule_id = $scheduleInfo['schedule_id'];
    		$day = $scheduleInfo['schedule_day'];
    		$week = $scheduleInfo['schedule_week'];
    		$round_no = $scheduleInfo['schedule_round_no'];
    		
    		$schedule_ids_of_all_lottery[$mix_schedule_id] = D('JcSchedule')->queryAllScheduleIdsFromScheduleNo($day,$week,$round_no);
    		$new_schedule_infos_in_order[$schedule_id] = $scheduleInfo;
    	}
    	ApiLog('new in order:'.print_r($new_schedule_infos_in_order,true), 'cobet_pay');
    	
		$stageTicket = $this->_saveJcMixtureTicket($uid, $tickets_from_combination, $new_schedule_infos_in_order, $lotteryId, $schedule_ids_of_all_lottery, $multiple);
    	$this->verifyStakeCountAndTotalAmountByOrder($stageTicket, $stakeCount, $multiple, $totalAmount);

    	$schedule_range_info = $this->checkScheduleTimeRangeInfo($new_schedule_infos_in_order);
    	$last_schedule_info_in_order = $schedule_range_info['last_schedule_info'];
    	$first_schedule_info_in_order = $schedule_range_info['first_schedule_info'];
    	
    	ApiLog('$schedule_range_info:'.print_r($schedule_range_info,true), 'cobet_pay');
    	 
    	if ($stageTicket) {
    		return array(
    			'lastSchedule' => $last_schedule_info_in_order,
    			'firstSchedule' => $first_schedule_info_in_order,
    			'stageTicket'  => $stageTicket,
    		);
    	} else {
    		return false;
    	}
    }
    
    private function _saveJcMixtureTicket($uid, array $tickets_from_combination, array $scheduleInfos, $lotteryId, $schedule_ids_of_all_lottery, $multiple) {
    	$orderStakeCount = 0;
    	$ticketSeq 		 = 0;
    	$ticketData = $ticketList = array();
    	$verifyObj 		 = Factory::createVerifyJcObj($lotteryId);
    	ApiLog('tickets:'.print_r($tickets_from_combination,true),'cobet_pay');
    	foreach ($tickets_from_combination as $ticket) {
    		$competitionInfo = $this->_buildJcMixCompetitionInfoForPrintOut($ticket, $scheduleInfos, $lotteryId, $schedule_ids_of_all_lottery);
    		if(!$competitionInfo){
                throw new Exception('',C('ERROR_CODE.SCHEDULE_NO_ERROR'));
    		}
    		$competition	 = $competitionInfo['competition'];
    		
    		$betType = $ticket['bet_type'];
			$ticket_schedule_list = $this->buildBetScheduleListInTicket($ticket);
    		
			$stakeCount = $verifyObj->getStakeCount($ticket_schedule_list, $betType);
    		
    		ApiLog('aaaa:'.$betType.'==='.$stakeCount.'==='.print_r($ticket_schedule_list,true),'cobet_pay');
    		
    		$devide_result = $this->devideOverMultipleTicket($uid, $lotteryId, JC_PLAY_TYPE_MULTI_STAGE, $ticketSeq, $stakeCount, $multiple, $betType, $competitionInfo);
    		$orderStakeCount += $stakeCount;
    		
    		$ticketSeq = $devide_result['ticket_seq'];
    		$ticketList = array_merge($ticketList,$devide_result['printout_ticket_list']);
    		$ticketData = array_merge($ticketData,$devide_result['ticket_data']);
    	}
    	 
    	return array(
    			'stakeCount' => $orderStakeCount,
    			'ticketList' => $ticketList,
    			'ticketData' => $ticketData,
    	);
    }

	private function _devideOverMultipleTicket($uid, $lotteryId, $playType, $ticketSeq, $stakeCount, $multiple, $betType, $competitionInfo){
		$first_schedule_issue_id_in_ticket = $competitionInfo['first_schedule_issue_id'];
		$first_schedule_issue_no_in_ticket = $competitionInfo['first_schedule_issue_no'];
		$first_schedule_end_time_in_ticket = $competitionInfo['first_schedule_end_time'];
		$last_schedule_issue_id_in_ticket = $competitionInfo['last_schedule_issue_id'];
		$last_schedule_issue_no_in_ticket = $competitionInfo['last_schedule_issue_no'];
		$last_schedule_end_time_in_ticket = $competitionInfo['last_schedule_end_time'];
		$competition = $competitionInfo['competition'];
		
		$once_ticket_amount = $stakeCount * LOTTERY_PRICE;
		if ($once_ticket_amount > BET_TICKET_AMOUNT_LIMIT) {
            throw new Exception('',C('ERROR_CODE.OVER_TICKET_LIMIT'));
		}
		
		$max_multiple = getMaxMultipleByLotteryId($competitionInfo['ticket_lottery_id']);

		if ($multiple > $max_multiple) {
			$limit_multiple = $max_multiple;
		} else {
			$limit_multiple = $multiple;
		}
		
		$limit_ticket_amount = $once_ticket_amount * $limit_multiple;
		if ($limit_ticket_amount > BET_TICKET_AMOUNT_LIMIT) {
			$max_once_ticket_multiple = floor(BET_TICKET_AMOUNT_LIMIT / $once_ticket_amount);
			$once_ticket_multiple = $max_once_ticket_multiple;
		} else {
			$once_ticket_multiple = $limit_multiple;
		}
		$devide_ticket_num = ceil($multiple / $once_ticket_multiple);
		
		for($i = 0; $i < $devide_ticket_num; $i++) {
			if ($i == $devide_ticket_num - 1) {
				$ticket_multiple = $multiple - ($devide_ticket_num - 1) * $once_ticket_multiple;
			} else {
				$ticket_multiple = $once_ticket_multiple;
			}
			$ticket_amount = $once_ticket_amount * $ticket_multiple;
			$ticketSeq++;
			$ticket_lottery_id = $competitionInfo['ticket_lottery_id'];
			$ticketList[] = $this->buildCompetitionTicketItemForPrintout($ticketSeq, $playType, $betType, $stakeCount, $ticket_amount, $competition, $last_schedule_end_time_in_ticket, $ticket_lottery_id, $ticket_multiple, $first_schedule_end_time_in_ticket,$first_schedule_issue_no_in_ticket,$last_schedule_issue_no_in_ticket);
			
			// add 'v' before option
			$formated_competition_infos = $this->formatBetOptionAddV($competition);
			$jsonCompetition = json_encode($formated_competition_infos);
			
			if($playType==JC_PLAY_TYPE_MULTI_STAGE){
				$issueNos = $competitionInfo['ticket_issue_nos'];
			}else{
				$issueNos = $competitionInfo['issue_no'];
			}
			if (!$issueNos) { return false; }
			
			
			$ticketData[] = D('JcTicket')->buildTicketData($uid, $ticketSeq, $playType, $stakeCount, $betType, $jsonCompetition, $last_schedule_issue_id_in_ticket, $issueNos, $ticket_amount, $ticket_multiple, $first_schedule_issue_id_in_ticket, $competitionInfo['ticket_lottery_id']);
		}
		
		return array(
				'ticket_seq' => $ticketSeq,
				'ticket_data' => $ticketData,
				'printout_ticket_list' => $ticketList 
		);
	}
    

    private function _buildJcMixCompetitionInfoForPrintOut(array $ticket_item, array $scheduleInfos, $lotteryId,$schedule_ids_of_all_lottery) {
    	$competitions = array();
    	$ticket_content = $ticket_item;
    	$betType 	  = $ticket_content['bet_type'];
    	unset($ticket_content['bet_type']);
    	$ticket_lottery_id = $this->getLotteryIdByCompetition($ticket_content, $lotteryId);
    	$is_jc_mix_lottery_id = isJcMix($ticket_lottery_id);
    	
    	$last_schedule_game_start_time = 0;
    	$first_schedule_end_time = 0;
    	foreach ($ticket_content as $bet_schedule) {
    		if($is_jc_mix_lottery_id){
    			$scheduleId = $bet_schedule['schedule_id'];
    			$issueNo 	= $scheduleInfos[$scheduleId]['schedule_issue_no'];
    			$ticket_schedule_info =  $scheduleInfos[$scheduleId];
    			$competition_lottery_id = $bet_schedule['lottery_id'];
    			if(!isset($schedule_ids_of_all_lottery[$scheduleId][$competition_lottery_id])){
    				ApiLog('no exist:'.$competition_lottery_id.'===='.$scheduleId.'===='.print_r($scheduleInfos,true), 'csq');
    			}
    		}else{
    			$orig_schedule_id = $bet_schedule['schedule_id'];
    			$orig_schedule_info = $scheduleInfos[$orig_schedule_id];
    			$play_type = $orig_schedule_info['play_type']; 
//     			ApiLog('orig_schedule_info:'.print_r($orig_schedule_info,true), 'com');
    			$ticket_schedule_info = $schedule_ids_of_all_lottery[$orig_schedule_id][$ticket_lottery_id][$play_type];
//     			ApiLog('covert ticket schedule info:'.print_r($ticket_schedule_info,true), 'com');
    			$scheduleId = $ticket_schedule_info['schedule_id'];
    			$issueNo 	= $ticket_schedule_info['schedule_issue_no'];
    			$competition_lottery_id = $ticket_lottery_id;
    		}
    		
    		if(empty($issueNo)){
    			//查不到issueno 报警
    			ApiLog('$scheduleInfos[$orig_schedule_id]:'.print_r($scheduleInfos[$orig_schedule_id],true), 'cobet_pay');
    			ApiLog('$$$ticket_schedule_info:'.$orig_schedule_id.'=='.$ticket_lottery_id.'=='.$play_type.'=='.print_r($ticket_schedule_info,true), 'cobet_pay');
    			ApiLog('$$$schedule_ids_of_all_lottery:'.$issueNo.'====='.print_r($schedule_ids_of_all_lottery,true), 'cobet_pay');
    			ApiLog('$$all_ticket_issue_no:'.$issueNo.'====='.print_r($schedule_ids_of_all_lottery[$orig_schedule_id],true), 'cobet_pay');
    			return false;
    		}
    		
//     		$competition['schedule_id'] = $scheduleId;
    		$competition['lottery_id'] = $competition_lottery_id;
    		$competition['bet_options'] = $bet_schedule['bet_options'];
    		$competition['issue_no'] = $issueNo;
    		
//     		ApiLog('competion:'.print_r($competition,true), 'com');
    		
    		$competitions[] = $competition;
    		
    		$all_ticket_issue_no[] = $issueNo;
    		
    		$schedule_end_time_stamp = $ticket_schedule_info['schedule_end_time_unix_timestamp'];
    		$schedule_game_start_time_stamp = strtotime($ticket_schedule_info['schedule_game_start_time']);;
    		if ($schedule_game_start_time_stamp >= $last_schedule_game_start_time) {
    			$last_schedule_game_start_time = $schedule_game_start_time_stamp;
    			$last_schedule_in_ticket = $ticket_schedule_info;
    		}
    		
    		if($first_schedule_end_time==0){
    			$first_schedule_end_time = $schedule_end_time_stamp;
    		}
    		if ($schedule_end_time_stamp <= $first_schedule_end_time) {
    			ApiLog('build ====mix :'.$first_schedule_end_time.'=-==='.print_r($ticket_schedule_info,true),'cobet_pay');
    			$first_schedule_end_time = $schedule_end_time_stamp;
    			$first_schedule_in_ticket = $ticket_schedule_info;
    		}
    	}
    	ApiLog('build mix :'.$first_schedule_end_time.'==='.print_r($first_schedule_in_ticket,true), 'cobet_pay');
    	asort($all_ticket_issue_no);
    	$ticket_issue_nos = implode(',', $all_ticket_issue_no);
    	
		return array(
				'ticket_lottery_id'=>$ticket_lottery_id,
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
    
    private function _addJcNoMixtureOrder($uid, $orderSku, $order_info) {
    	$playType 	= C('MAPPINT_JC_PLAY_TYPE.'.$order_info['play_type']);
    	$series 	= explode(',', $order_info['series']);
    	
    	$jcTicketInfo = $this->_buildJzTicketInfo($order_info['schedule_orders'], $uid, $playType,
    			$series, $order_info['lottery_id'], $order_info['stake_count'], $order_info['total_amount'], $order_info['multiple']);
    	if(empty($jcTicketInfo)){
    	    ApiLog('empty($jcTicketInfo)$order_info:'.print_r($order_info,true),'addJcOrder');
    	    return false;
        }
    	
    	$orderTicket = $this->_addJzOrderSchedule($order_info['lottery_id'], $uid, $order_info['total_amount'],
    			$orderSku, $order_info['multiple'], $order_info['coupon_id'], $order_info['schedule_orders'], $jcTicketInfo, $order_info['order_identity'],$order_info);
    	return $orderTicket;
    }

    private function _addJzOrderSchedule($lotteryId, $uid, $totalAmount, $orderSku, $multiple, $couponId, array $scheduleOrders, array $jcTicketInfo, $identity, $order_info) {
    	$orderTotalAmount = $totalAmount ;
    	$lastSchedule = $jcTicketInfo['lastSchedule'];
    	$firstSchedule = $jcTicketInfo['firstSchedule'];
    	$stageTicket  = $jcTicketInfo['stageTicket'];
    	$order_params['play_type'] = $order_info['play_type'];
    	$order_params['series'] = $order_info['series'];
    	$order_params['order_type'] = ORDER_TYPE_OF_COBET;
    	$order_params['content'] = json_encode($order_info['schedule_orders']);
        $couponId = empty($couponId) ? 0 : $couponId;
    	 
    	M()->startTrans();
    	$orderId = D('Order')->addOrder($uid, $orderTotalAmount, $lastSchedule['schedule_id'], $multiple, $couponId, $lotteryId, $orderSku, $firstSchedule['schedule_id'], $identity,0,0,'',$order_params);
    	$model	 = getTicktModel($lotteryId);
    	if(!$orderId || !$model) {
            ApiLog('sql:'.D('Order')->getLastSql(), 'cobet_pay');
    		M()->rollback();
    		return false;
    	}
    	$ticketDatas = $model->appendOrderId($stageTicket['ticketData'], $orderId);
    	
    	$addTickets_result  = $model->insertAll($ticketDatas);
    	if(!$addTickets_result) {
    		ApiLog('$addTickets:'.$addTickets_result, 'cobet_pay');
    		M()->rollback();
    		return false;
    	}
    	$orderDetails = $this->_getJcOrderDetail($scheduleOrders, $orderId);
    	$addDetail_result = D('JcOrderDetail')->insertAll($orderDetails);
    	if(!$addDetail_result) {
    		ApiLog('after $$addDetail_result:'.$addDetail_result, 'cobet_pay');
    		M()->rollback();
    		return false;
    	}
    	M()->commit();
    	 
    	return array(
    			'orderId' 	 => $orderId,
    			'issueNo' 	 => $lastSchedule['schedule_issue_no'],
    			'firstIssueNo' 	 => $firstSchedule['schedule_issue_no'],
    			'ticketList' => $stageTicket['ticketList'],
    	);
    }
    
    
    private function _getJcOrderDetail($scheduleOrders, $orderId) {
    	$orderDetails = array();
    	foreach ($scheduleOrders as $scheduleOrder) {
    		$betNumbers = $this->parseBetNumber($scheduleOrder['bet_number']);
    		$betNumbers = betOptionsAddV($betNumbers);
    		$betContent = json_encode($betNumbers);
    		$orderDetails[] = D('JcOrderDetail')->buildDetailData($orderId, $scheduleOrder['schedule_id'], $betContent, $scheduleOrder['is_sure']);
    	}
    	return $orderDetails;
    }
    
    private function _buildJzTicketInfo(array $scheduleOrders, $uid, $playType, array $series, $lotteryId, $stakeCount, $totalAmount, $multiple) {
//     	$scheduleIds 	= array_column($scheduleOrders, 'schedule_id');
    	foreach($scheduleOrders as $scheduleOrder){
    		$scheduleIds[] = $scheduleOrder['schedule_id'];
    	}
    	ApiLog('schedule ids:'.print_r($scheduleOrders,true).'==='.print_r($scheduleIds,true), 'cobet_pay');
    	$scheduleInfos 	= D('JcSchedule')->getScheduleIssueNo($scheduleIds);
    	ApiLog('schedule info:'.print_r($scheduleInfos,true), 'cobet_pay');
    	if(!$scheduleInfos){
            throw new Exception('',C('ERROR_CODE.SCHEDULE_NO_ERROR'));
    	}
    	
		$lottery_info = D('Lottery')->getLotteryInfo($lotteryId);
    	$this->checkScheduleOutOfTime($scheduleInfos, $lottery_info);
    	
    	$schedule_range_info = $this->checkScheduleTimeRangeInfo($scheduleInfos);
    	$lastSchedule = $schedule_range_info['last_schedule_info'];
    	$firstSchedule = $schedule_range_info['first_schedule_info'];
    	
//     	$lastSchedule 	= $this->_getLatestSchedule($scheduleInfos);
//     	$firstSchedule = $this->_getFirstSchedule($scheduleInfos);
    	ApiLog('$firstSchedule info:'.print_r($firstSchedule,true), 'cobet_pay');
    	ApiLog('$$scheduleInfos info:'.print_r($scheduleInfos,true), 'cobet_pay');
    	 
    	
    	if ($playType == C('JC_PLAY_TYPE.ONE_STAGE')) {	// 如果是单关
    		$stageTicket = $this->_saveOneStageTicket($uid, $scheduleOrders, $scheduleInfos, $playType, $series, $lotteryId, $multiple);
    	} elseif ($playType == C('JC_PLAY_TYPE.MULTI_STAGE')) {
    		$stageTicket = $this->_saveMultiStageTicket($uid, $scheduleOrders, $scheduleInfos, $playType, $series, $lotteryId, $multiple);
    	}
    	$this->verifyStakeCountAndTotalAmountByOrder($stageTicket, $stakeCount, $multiple, $totalAmount);
    	
    	if ($stageTicket) {
    		ApiLog('sss:'.print_r($lastSchedule,true).'==='.print_r($stageTicket,true), 'cobet_pay');
    		return array(
    				'lastSchedule' => $lastSchedule,
    				'firstSchedule' => $firstSchedule,
    				'stageTicket'  => $stageTicket,
    		);
    	} else {
    		return false;
    	}
    }
    
    
    private function _saveOneStageTicket($uid, array $scheduleOrders, array $scheduleInfos, $playType, array $series, $lotteryId, $multiple) {
    	$betType 	= $series[0];
    	$ticketSeq 	= 0;
    	$ticketList = array();
    	$ticketData = array();
    	$orderStakeCount = 0;
    	foreach ($scheduleOrders as $scheduleOrder) {
    		$competitionInfo = $this->_buildJcNoMixCompetitionInfoForPrintOut(array($scheduleOrder), $scheduleInfos, $lotteryId);
    		$bet = $this->parseBetNumber($scheduleOrder['bet_number']);
    		ApiLog('parseBetNumber:'.print_r($bet,true), 'cobet_pay');
    		
    		$bet = array_pop($bet);
    		ApiLog('array pop :'.print_r($bet,true), 'cobet_pay');
    		
    		$stakeCount = count($bet);
    		
    		$devide_result = $this->devideOverMultipleTicket($uid, $lotteryId, $playType, $ticketSeq, $stakeCount, $multiple, $betType, $competitionInfo);
    		if(empty($devide_result)){
    			return false;
    		}
    		$orderStakeCount += $stakeCount;
    		
    		$ticketSeq = $devide_result['ticket_seq'];
    		$ticketList = array_merge($ticketList,$devide_result['printout_ticket_list']);
    		$ticketData = array_merge($ticketData,$devide_result['ticket_data']);
    	}
    	
    	ApiLog('stakCount:'.$orderStakeCount.'------'.print_r($ticketList,true), 'cobet_pay');
    	
    	return array(
    		'stakeCount' => $orderStakeCount,
    		'ticketList' => $ticketList,
    		'ticketData' => $ticketData,
    	);
    }
    
    private function _buildJcNoMixCompetitionInfoForPrintOut(array $scheduleOrders, array $scheduleInfos, $ticket_lottery_id) {
    	$ticketContent 	= array();
    	$competition 	= array();
    	foreach ($scheduleOrders as $scheduleOrder) {
    		$scheduleId = $scheduleOrder['schedule_id'];
    		$endTime 	= $scheduleInfos[$scheduleId]['schedule_end_time'];
    		$betNumber 	= $this->parseBetNumber($scheduleOrder['bet_number']);
    		$scheduleIssueNo = $scheduleInfos[$scheduleId]['schedule_issue_no'];

    		$all_ticket_issue_no[] = $scheduleIssueNo;

    		foreach ($betNumber as $lotteryId=>$bet) {
    			sort($bet);
    			$competitions[] = array(
    					'lottery_id' 	=> $lotteryId,
    					'issue_no' 		=> $scheduleIssueNo,
    					'bet_options' 	=> $bet,
    			);
    		}
    		$ticketContent[] = array(	'schedule_issue_no'	=> $scheduleIssueNo,
    				'bet' 				=> $betNumber,
    				'schedule_end_time'	=> $endTime,
    				'schedule_game_start_time'	=> $scheduleInfos[$scheduleId]['schedule_game_start_time'],
    				'schedule_id'		=> $scheduleId, );
    	}

    	asort($all_ticket_issue_no);
    	$ticket_issue_nos = implode(',', $all_ticket_issue_no);

    	$schedule_range_info = $this->checkScheduleTimeRangeInfo($ticketContent);
    	$last_schedule_in_ticket = $schedule_range_info['last_schedule_info'];
    	$first_schedule_in_ticket = $schedule_range_info['first_schedule_info'];
    	ApiLog('$last_schedule_info:'.print_r($last_schedule_in_ticket,true), 'cobet_pay');
    	ApiLog('$$first_schedule_info:'.print_r($first_schedule_in_ticket,true), 'cobet_pay');
    
    	return array(
    			'ticket_lottery_id'=>$ticket_lottery_id,
//     			'bet_type' => $betType,
    			'ticket_issue_nos' => $ticket_issue_nos,
    			'issue_no'		 => $last_schedule_in_ticket['schedule_issue_no'],
    			'last_schedule_issue_id' => $last_schedule_in_ticket['schedule_id'],
    			'last_schedule_issue_no' => $last_schedule_in_ticket['schedule_issue_no'],
    			'last_schedule_end_time' => $last_schedule_in_ticket['schedule_end_time'],
    			'first_schedule_issue_id' => $first_schedule_in_ticket['schedule_id'],
    			'first_schedule_issue_no' => $first_schedule_in_ticket['schedule_issue_no'],
    			'first_schedule_end_time' => $first_schedule_in_ticket['schedule_end_time'],
    			'competition'	 => $competitions,
    	);
    	
    }
    
    private function _saveMultiStageTicket($uid, array $scheduleOrders, array $scheduleInfos, $playType, array $series, $lotteryId, $multiple) {
    	$verifyObj 	= Factory::createVerifyJcObj($lotteryId);
    	$ticketSeq 	= 0;
    	$ticketList = array();
    	$ticketData = array();
    	$orderStakeCount = 0;
    	
    	foreach ($series as $betType) {
    		$maxSelectCount = $verifyObj->getMaxSeriesCount($scheduleOrders, $betType, $lotteryId);
    		if(!$maxSelectCount){
                throw new Exception('',C('ERROR_CODE.TICKET_ERROR'));
    		}
    		
    		$scheduleCombinatorics = $verifyObj->getScheduleCombinatorics($scheduleOrders, $maxSelectCount);
    		foreach ($scheduleCombinatorics as $scheduleCom) {
    			$competitionInfo = $this->_buildJcNoMixCompetitionInfoForPrintOut($scheduleCom, $scheduleInfos, $lotteryId);
    			ApiLog('$competitionInfo :'.print_r($competitionInfo,true), 'cobet_pay');
    			ApiLog('$scheduleCom :'.print_r($scheduleCom,true), 'cobet_pay');
    			 
    			$stakeCount = $verifyObj->getStakeCount($scheduleCom, $betType);
    			ApiLog('$stakeCount :'.print_r($stakeCount,true), 'cobet_pay');

    			$devide_result = $this->devideOverMultipleTicket($uid, $lotteryId, $playType, $ticketSeq, $stakeCount, $multiple, $betType, $competitionInfo);
    			if(empty($devide_result)){
	    			return false;
	    		}
	    		$orderStakeCount += $stakeCount;
	    		
	    		$ticketSeq = $devide_result['ticket_seq'];
	    		$ticketList = array_merge($ticketList,$devide_result['printout_ticket_list']);
	    		$ticketData = array_merge($ticketData,$devide_result['ticket_data']);
    		}
    	}
    	
    	ApiLog('stakCount:'.$orderStakeCount.'------'.print_r($ticketList,true), 'cobet_pay');
    	ApiLog('stakCount:'.$orderStakeCount.'------'.print_r($ticketData,true), 'cobet_pay');
    	 
    	return array(
    		'stakeCount' => $orderStakeCount,
    		'ticketList' => $ticketList,
    		'ticketData' => $ticketData,
    	);
    }

    private function _checkSchemeAmount($scheme_id,$order_total_amount){
        $cobet_amount = D('CobetRecord')->getCobetAmountBySchemeId($scheme_id);
        if(bccomp($cobet_amount,$order_total_amount) != 0){
            return false;
        }
        return true;
    }


    public function refundAmountForSchemeFailed($scheme_info){
        if(COBET_SCHEME_STATUS_OF_SCHEME_COMPLETE != $scheme_info['scheme_status']){
            ApiLog('当前状态不允许退款$scheme_info:'.print_r($scheme_info,true),'home_refundAmountForSchemeFailed');
            return false;
        }
        M()->startTrans();
        $change_code = D('Crontab/CobetScheme')->changeSchemeStatusById($scheme_info['scheme_id'],C('COBET_SCHEME_STATUS.FAILED'));
        if(!$change_code){
            ApiLog('sql1:'.D('Crontab/CobetScheme')->getLastSql().'$scheme_info:'.print_r($scheme_info,true),'home_refundAmountForSchemeFailed');
            M()->rollback();
            return false;
        }

        $record_list = D('CobetRecord')->getRecordListBySchemeId($scheme_info['scheme_id']);
        foreach($record_list as $record){

            if($record['type'] == COBET_TYPE_OF_GUARANTEE_FROZEN){
                continue;
            }

            if($record['record_status'] != COBET_STATUS_OF_CONSUME){
                ApiLog('uid:'.$record['uid'].'当前退款record_status异常$record:'.print_r($record,true),'home_refundAmountForSchemeFailed');
                M()->rollback();
                return false;
            }

            if($record['type'] == COBET_TYPE_OF_BOUGHT){
                $user_account_log_type = C('USER_ACCOUNT_LOG_TYPE.COBET_BOUGHT_REFUND');
            }elseif($record['type'] == COBET_TYPE_OF_GUARANTEE){
                $user_account_log_type = C('USER_ACCOUNT_LOG_TYPE.COBET_GUARANTEE_REFUND');
            }
            $user_coupon_id = $record['user_coupon_id'];
            $refund_coupon_amount = $record['record_user_coupon_consume_amount'];
            $refund_money = $record['record_user_cash_amount'];
            $refund_code = $this->refundAmount($record['uid'],$user_coupon_id,$refund_coupon_amount,$refund_money,$user_account_log_type);
            if(!$refund_code){
                ApiLog('uid:'.$record['uid'].'当前退款失败$record:'.print_r($record,true),'home_refundAmountForSchemeFailed');
                M()->rollback();
                return false;
            }

            $record_status = COBET_STATUS_OF_REFUND;
            $refund_unit = $record['record_bought_unit'];
            $refund_amount = $record['record_bought_amount'];;
            if($refund_unit < 0 || $refund_amount < 0){
                $this->notifyWarningMsg('2退款数据异常$scheme_info:'.print_r($scheme_info,true));
                ApiLog('2退款数据异常$scheme_info:'.print_r($scheme_info,true),'home_refundAmountForSchemeFailed');
                return false;
            }

            $save_status = D('Crontab/CobetRecord')->saveRefundStatus($record['record_id'],$refund_amount,$refund_unit,$record_status);
            if(!$save_status){
                $this->notifyWarningMsg('保存记录失败$record:'.print_r($record,true));
                ApiLog('sql:'.D('CobetRecord')->getLastSql(),'home_refundAmountForSchemeFailed');
                ApiLog('保存记录失败$record:'.print_r($record,true),'home_refundAmountForSchemeFailed');
                return false;
            }

        }
        $change_code = D('Crontab/CobetScheme')->changeSchemeStatusById($scheme_info['scheme_id'],C('COBET_SCHEME_STATUS.FAILED_REFUND'));
        if(!$change_code){
            ApiLog('sql2:'.D('Crontab/CobetScheme')->getLastSql().'当前退款失败$scheme_info:'.print_r($scheme_info,true),'home_refundAmountForSchemeFailed');
            M()->rollback();
            return false;
        }

        M()->commit();
        return true;
    }

    public function refundAmount($uid,$user_coupon_id,$refund_coupon_amount,$refund_money,$user_account_log_type){
        if($user_coupon_id && $refund_coupon_amount>0){
            $result = D('UserCoupon')->increaseCoupon($user_coupon_id, $refund_coupon_amount);
            $coupon_balance = D('UserCoupon')->getCouponBalance($user_coupon_id);
            D('UserCouponLog')->addUserCouponLog($uid, $user_coupon_id, $refund_coupon_amount, $coupon_balance, $user_account_log_type, $uid,$remark = '合买退回红包退回');
            if(empty($result)){
                ApiLog('sql2:'.D('UserCoupon')->getLastSql(),'home_refundAmountForSchemeFailed');
                return false;
            }
        }

        $refund_total_amount = bcadd($refund_money,$refund_coupon_amount);
        if($refund_total_amount > 0){
            $refund_result = D('UserAccount')->refundCobetMoney($uid, $refund_total_amount,$refund_money,$refund_coupon_amount, 0,$user_account_log_type);
            if(empty($refund_result)){
                ApiLog('sql3:'.D('UserAccount')->getLastSql(),'home_refundAmountForSchemeFailed');
                return false;
            }
        }
        return true;
    }

    public function notifyWarningMsg($msg=''){
        $data = array(
            'telephone_list' => array('18705085505'),
            'send_data' => array(getCurrentTime().':'.get_cfg_var('PROJECT_RUN_MODE').$msg),
            'template_id' => '82542',
        );
        sendTelephoneMsgNew($data);
    }

}