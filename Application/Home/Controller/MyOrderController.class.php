<?php
namespace Home\Controller;

class MyOrderController extends GlobalController {
	
	private function _getLotteryIds($lottery_id){
		if($lottery_id==TIGER_LOTTERY_ID_OF_JL){
			return array_values(C('JCLQ'));
		}
		if($lottery_id==TIGER_LOTTERY_ID_OF_JZ){
			return array_values(C('JCZQ'));
		}
		if($lottery_id == TIGER_LOTTERY_ID_OF_SFC_14 || $lottery_id == TIGER_LOTTERY_ID_OF_SFC_9){
		    return array(TIGER_LOTTERY_ID_OF_SFC_14,TIGER_LOTTERY_ID_OF_SFC_9);
        }

	}
	
	public function queryScheduleListInOrder($api) {
		$userInfo = $this->getAvailableUser($api->session);
		$lottery_ids = $this->_getLotteryIds($api->lottery_id);
		$uid = $userInfo['uid'];
		$order_list = D('Order')->queryMyOrderList($uid, $lottery_ids, $api->offset, $api->limit);
		$lottery_list = D('Lottery')->getLotteryMap();
		
		$response_order_list = array();
		foreach ($order_list as $order_info){
			$date = date('Y-m-d', strtotime($order_info['order_create_time']));
			$format_order_info = $this->_formatOrderInfo($order_info, $lottery_list);
			$response_order_list_by_date[$date][] = $format_order_info;
		}
		
		foreach($response_order_list_by_date as $date=>$order_list){
			$response_order_info['date'] = $date;
			$response_order_info['date_timestamp'] = strtotime($date);
			$response_order_info['list'] = $order_list;
			$response_order_list[] = $response_order_info;
		}
		 
		return array(   'result' => array('groups'=>$response_order_list),
						'code'   => C('ERROR_CODE.SUCCESS'));
	}
	
	private function _formatOrderInfo($order_info,$lottery_list){
		$format_order_info['id']			= $order_info['order_id'];
		$format_order_info['lottery_id'] 	= $order_info['lottery_id'];
        $format_order_info['issue_no'] 	= D('Issue')->getIssueNoById($order_info['issue_id']);
		$format_order_info['lottery_name'] 	= $lottery_list[$order_info['lottery_id']]['lottery_name'];
		$format_order_info['lottery_image'] 	= $lottery_list[$order_info['lottery_id']]['lottery_image'];
		$format_order_info['type'] 			= $order_info['order_type'];
		$format_order_info['status'] 		= D('Order')->getStatus($order_info['order_status'], $order_info['order_winnings_status'], $order_info['order_distribute_status']);
		$format_order_info['winnings_bonus'] = $order_info['order_winnings_bonus'];
		$format_order_info['plus_award_amount'] = $order_info['order_plus_award_amount'];
		$format_order_info['total_amount'] 	= $order_info['order_total_amount'];
		$format_order_info['multiple'] 	= $order_info['order_multiple'];
		$format_order_info['series'] 	= $this->_getBetType($order_info['lottery_id'], $order_info['order_id']);
        if(isZcsfc($order_info['lottery_id'])){
            $issue_info = D('Issue')->getIssueInfo($order_info['issue_id']);
            $format_order_info['prize_num'] 	= emptyToStr($issue_info['issue_prize_number']);
            $format_order_info['jc_info'] 	= A('Order')->getZcsfcInfo($order_info['order_id']);
            $format_order_info['schedule_list'] 	= $this->_requestZcsfcGameDataInOrder($order_info);
        }else{
            $format_order_info['jc_info'] 	= $this->_queryJcScheduleListInOrder($order_info['lottery_id'], $order_info['order_id'] ,$order_info['order_status']);
            $format_order_info['schedule_list'] 	= $this->_requestJcGameDataInOrder($format_order_info['jc_info'],$order_info['lottery_id']);
        }
		return $format_order_info;
	}

    private function _requestZcsfcGameDataInOrder($order_info){
        $issue_id = $order_info['issue_id'];
        $issue_info = D('Issue')->getIssueInfo($issue_id);
        $issue_no = $issue_info['issue_no'];
        $data = A('ZcsfcGameData')->fetchScheduleListByIssueNo($issue_no);
        return $data;
    }
	
	private function _requestJcGameDataInOrder($jc_schedule_list,$lottery_id){
		$schedule_nos = array();
		foreach($jc_schedule_list as $jc_schedule_info){
			$schedule_nos[] = $jc_schedule_info['id']; 
		}
		if(isJczq($lottery_id)){
			$data = A('LiveScore')->fetchScheduleListByScheduleNos($schedule_nos);
			return $data;
		}elseif(isJclq($lottery_id)){
			$data = A('BasketballGameData')->fetchScheduleListByScheduleNos($schedule_nos);
			return $data;
		}
	}
	
	private function _queryJcScheduleListInOrder($lotteryId, $orderId, $order_status=0){
		$jc_schedule_list = D('JcOrderDetailView')->getInfos($orderId);
		$model = getTicktModel($lotteryId);
		$odds_list = $model->getFormatPrintoutOdds($orderId);
		foreach ($jc_schedule_list as $k=>$schedule_info){
			$bet_content = $schedule_info['bet_content'];
			$bet_content_array = json_decode($bet_content, true);
			$schedule_issue_no = $schedule_info['schedule_issue_no'];
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
					$jc_schedule_list[$k]['score'] = array('half'=>'','final'=>'');
				}
				
				$betting_order = $jc_schedule_list[$k]['betting_order'] ? $jc_schedule_list[$k]['betting_order'] : array();
				$betting_order = array_merge($betting_order, $format_odds);
				
				if(sizeof($betting_order)>0){
					$jc_schedule_list[$k]['betting_order'] = array_merge($betting_order, $format_odds);
				}
			}
			$jc_schedule_list[$k]['round_no'] = getWeekName($schedule_info['schedule_week']).$schedule_info['round_no'];
			
			if(isJczq($lotteryId)){
				$let_point = array_search_value('letPoint', json_decode($schedule_info['schedule_odds'], true));
				$base_point = array_search_value('basePoint', json_decode($schedule_info['schedule_odds'], true));
			}else{
				$let_point = array_search_value('letPoint', json_decode($schedule_info['schedule_odds'], true));
				$base_point = array_search_value('basePoint', json_decode($schedule_info['schedule_odds'], true));
			}
			if(isJcMix($lotteryId)){
				$format_result_odds = json_decode($schedule_info['schedule_odds'], true);
			}else{
				$format_result_odds[$lotteryId] = json_decode($schedule_info['schedule_odds'], true);
			}
			$jc_schedule_list[$k]['let_point'] = $let_point ? $let_point : '';
			$jc_schedule_list[$k]['base_point'] = $base_point ? $base_point : '';
			$jc_schedule_list[$k]['result_odds'] = empty($format_result_odds) ? array():$format_result_odds;
			$jc_schedule_list[$k]['id'] = $schedule_info['schedule_day'].$schedule_info['schedule_round_no'];
			unset($jc_schedule_list[$k]['schedule_odds']);
		}
		return $jc_schedule_list;
	}
	
	private function _getBetType($lotteryId, $orderId){
		$model = getTicktModel($lotteryId);
		$betTypes = $model->getBetTypesByOrderId($orderId);
		$betTypes = array_unique($betTypes);
		return implode(',', $betTypes);
	}
	
}