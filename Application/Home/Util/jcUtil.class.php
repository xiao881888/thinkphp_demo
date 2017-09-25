<?php
namespace Home\Util;

class jcUtil {

    public function getIssueNos(array $competition) {
    	$issueNos = array_column($competition, 'issue_no');

    	if(count($issueNos)!=count(array_unique($issueNos))){
    		return false;
    	}
    	
    	asort($issueNos);
    	return implode(',', $issueNos);
    }
    
    public function getStakeCount(array $scheduleOrders, $series) {    # 足彩注数计算
    	import('@.Util.Combinatorics');
    	$mathCombinatorics = new \Math_Combinatorics();
    	$playList = $this->_getPlayList($scheduleOrders);
    	$mergeCount  = C("MERGE_COUNT.$series");
    	$selectCount = $mergeCount['count'];
    	$mergeSeries = $mergeCount['series'];
    	$combinatorics = $mathCombinatorics->combinations($playList, $selectCount);
    	$sum = 0;
    	foreach ($combinatorics as $combinatoric) {
    		$cartesians = $mathCombinatorics->array_cartesian($combinatoric);
    		foreach ($cartesians as $cartesian) {
    			$sum += $this->_getSeriesStakeCount($cartesian, $mergeSeries);
    		}
    	}
    	return $sum;
    }

    
    public function getScheduleCombinatorics(array $scheduleOrders, $maxSelectCount) {
    	if($this->_hasSure($scheduleOrders)) {
    		return $this->_getSureScheduleCombinatorics($scheduleOrders, $maxSelectCount);
    	} else {
    		return $this->_getScheduleCombinatorics($scheduleOrders, $maxSelectCount);
    	}
    }

	// TODO 调用处，方法提前判断
    public function checkCompetitionTickets(array $scheduleOrders, $series) {
    	$betTypes = explode(',', $series);
    	foreach ($scheduleOrders as $scheduleOrder) {
    		if (!preg_match('/^(\d+:(\d+,?)+\|?)+$/', $scheduleOrder['bet_number'])) {
    			return false;
    		}
    	}
    	return $this->_checkSeries($betTypes) && $this->_checkSerieSureCount($betTypes, $scheduleOrders);
    }

    private function _checkSeries(array $series) {	// @TODO 改为正常算法
    	$seriesInclude = array();
    	$multiStageCount = 0;

    	foreach ($series as $betType) {
    		$mergeCount = C("MERGE_COUNT.$betType");
    		$seriesCount = count($mergeCount['series']);
    		if (!$mergeCount || !$seriesCount) {
    			return false;
    		}
    		$seriesInclude[] = $seriesCount;
    		if ($seriesCount > 1) {
    			$multiStageCount++;
    		}
    	}
    	$seriesInclude = array_unique($seriesInclude);

    	// 自由过关和多串过关不允许同时存在  并且  不允许有多个多串过关
    	return count($seriesInclude) == 1 && $multiStageCount <= 1;
    }
    
    private function _checkSerieSureCount(array $series, $scheduleOrders) {
    	foreach ($series as $serie) {
    		$verifyMerge = $this->_verifySureCount($scheduleOrders, $serie);
    		if(!$verifyMerge) {
    			return false;
    		}
    	}
    	return true;
    }
    
    private function _verifySureCount(array $scheduleOrders, $series) {
    	$data = $this->_getSureSchedule($scheduleOrders);
    	$sureCount 	= count($data['sure']);
    	$mergeCount = C("MERGE_COUNT.$series");
    	$limit 		= min($mergeCount['series']);
    	return $sureCount < $limit;
    }
    
    
    public function getMaxSeriesCount(array $scheduleOrders, $betType, $lotteryId) {
    	if (isJcMix($lotteryId)) {
    		$maxMergeSchedule = $this->_getJcMixMergeCount($scheduleOrders);
    	} else {
    		$maxMergeSchedule = C("MAX_MERGE_SCHEDULE.$lotteryId");
    	}
    	$mergeCount = C("MERGE_COUNT.$betType");
    	$maxScheduleCount = $mergeCount['count'];
    	if ($maxScheduleCount <= $maxMergeSchedule) {
    		return min($maxScheduleCount, $maxMergeSchedule);
    	} else {
    		return false;
    	}
    }
    

    public function convertScheduleOrderToTickets(array $scheduleOrders, $betTypes, $lotteryId) {
    	$betTypesArray = explode(',', $betTypes);
    	$tickets = array();
    	foreach ($betTypesArray as $betType) {
    		$ticketList = $this->_convertScheduleOrderToTickets($scheduleOrders, $betType, $lotteryId);
    		if (!$ticketList) { return false; }
    		$tickets = array_merge($tickets, $ticketList);
    	}
    	return $tickets;
    }


    private function _convertScheduleOrderToTickets(array $scheduleOrders, $betType, $lotteryId) {
    	$maxSelectCount = $this->getMaxSeriesCount($scheduleOrders, $betType, $lotteryId);
		if (!$maxSelectCount) {
			return false;
		}
    	$schedules = $this->getScheduleCombinatorics($scheduleOrders, $maxSelectCount);
//     	ApiLog('$schedules:'.print_r($schedules,true), 'schedule');
    	
    	$jcMatrix = $this->_getJcMatrix($lotteryId);
    	$lottery_id_map = array_flip($jcMatrix);
    	
    	$data = array();
    	foreach ($schedules as $schedule) {
    		$scheduleOrder = $this->_getScheduleOrder($schedule, $betType, $lotteryId,$jcMatrix ,$lottery_id_map);
    		$data = array_merge($data, $scheduleOrder);
    	}
    	return $data;
    }

    private function _getScheduleOrder(array $scheduleList, $betType, $lotteryId, $jcMatrix ,$lottery_id_map) {
    	$scheduleMatrix = $this->_getScheduleMatrix($scheduleList, $lotteryId, $jcMatrix);
//     	ApiLog('$scheduleMatrix:'.print_r($scheduleMatrix,true), 'schedule');
    	$result = array();
    	foreach ($scheduleMatrix as $singleMatrix) {
    		$singlePlayList = array();
    		$newBetSchedule = $this->_buildBetScheduleListFromMatrix($singleMatrix, $scheduleList, $lotteryId,$lottery_id_map);
//     		ApiLog('$$newBetSchedule:'.print_r($newBetSchedule,true), 'schedule');
    		$singlePlayList['bet_type'] = $betType;
    		foreach ($newBetSchedule as $item) {
    			$dict = array();
    			$dict['schedule_id'] = $item['schedule_id'];
    			$dict['lottery_id']  = $item['bet_lottery_id'];
    			$dict['bet_options'] = $item['bet_options'];
    			$singlePlayList[] 	 = $dict;
    		}
    		
//     		foreach ($newBetSchedule as $item) {
//     			$dict = array();
//     			$dict['schedule_id'] = $item['schedule_id'];
//     			$data = explode(':', $item['bet_number']);
//     			$dict['lottery_id']  = $data[0];
//     			$data2 = explode(',', $data[1]);
//     			$dict['bet_options'] = $data2;
//     			$singlePlayList[] 	 = $dict;
//     		}
    		$result[] = $singlePlayList;
    	}
    	return $result;
    }


    private function _getScheduleMatrix(array $scheduleList, $lotteryId, $jcMatrix) {
    	import('@.Util.Combinatorics');
//     	$jcMatrix = $this->_getJcMatrix($lotteryId);
    	$mathCombinatorics = new \Math_Combinatorics();
    	$schedulePlayList = array();
    	foreach($scheduleList as $schedule) {
    		$bet_contents = $schedule['bet_contents'];
    		$singleSchedulePlayList = array();
    		foreach ($bet_contents as $bet_lottery_id=>$bet_content) {
    			//701 702
    			$play_type = $bet_lottery_id;
    			$playTypeBox = $this->_initPlayTypeBox($lotteryId,$jcMatrix);
    			$playTypeBox[ $jcMatrix[$play_type] - 1 ] = $jcMatrix[$play_type];
    			$singleSchedulePlayList[] = $playTypeBox;
    		}
    		$schedulePlayList[] = $singleSchedulePlayList;
    		
//     		bet_number 701:0|702:0|704:1|703:11,13,01
//     		$betNumber = $schedule['bet_number'];
//     		$playTypeList = explode('|', $betNumber);
//     		$singleSchedulePlayList = array();
//     		foreach ($playTypeList as $playTypeInfo) {
//     			$data = explode(':', $playTypeInfo);
//     			//701 702
//     			$play_type = intval($data[0]);
    			 
//     			$playTypeBox = $this->_initPlayTypeBox($lotteryId,$jcMatrix);
//     			$playTypeBox[ $jcMatrix[$play_type] - 1 ] = $jcMatrix[$play_type];
//     			$singleSchedulePlayList[] = $playTypeBox;
//     		}
//     		$schedulePlayList[] = $singleSchedulePlayList;
    	}
    	return $mathCombinatorics->array_cartesian($schedulePlayList);
    }

    private function _buildBetScheduleListFromMatrix(array $scheduleMatrix, array $scheduleList, $lotteryId,$lottery_id_map) {
    	$newBetSchedule = array();
    	$idx = 0;
    	foreach ($scheduleList as $index=>$schedule_item) {
//     		bet_number 701:0|702:0|704:1|703:11,13,01
//     		$betNumber = $schedule_item['bet_number'];
//     		$bet_content_per_lottery = explode('|', $betNumber);
//     		foreach ($bet_content_per_lottery as $bet_content_item) {
//     			$bet_content = explode(':',$bet_content_item);
//     			$bet_number_lottery_id = intval($bet_content[0]);
//     			$bet_number_per_lottery[$bet_number_lottery_id] = $bet_content_item;
//     		}
    		
    		$betScheduleDict = array();
    		$bet_contents = $schedule_item['bet_contents'];
    		$scheduleId = $schedule_item['schedule_id'];
    		$scheduleRow = $scheduleMatrix[$idx];
    
    		foreach ($scheduleRow as $playType) {
    			if ($playType>0) {
    				$scheduleLotteryId = $lottery_id_map[$playType];
    				$singleBetNumber = $bet_contents[$scheduleLotteryId]['bet_number_string'];
    				if ($singleBetNumber) {
    					$betScheduleDict['bet_lottery_id'] = $scheduleLotteryId;
    					$betScheduleDict['bet_options'] = $bet_contents[$scheduleLotteryId]['bet_options'];
    					$betScheduleDict['bet_number'] = $singleBetNumber;
    					$betScheduleDict['schedule_id'] = $scheduleId;
    					$newBetSchedule[] = $betScheduleDict;
    				}
    			}
    		}
    		$idx++;
    	}
    	return $newBetSchedule;
    }

    private function _getBetSchedule(array $scheduleMatrix, array $scheduleList, $lotteryId,$lottery_id_map) {
//     	$jcMatrix = $this->_getJcMatrix($lotteryId);
    	$newBetSchedule = array();
    	$idx = 0;
    	foreach ($scheduleList as $index=>$item) {
    		$betScheduleDict = array();
    		//bet_number 701:0|702:0|704:1|703:11,13,01
    		$betNumber = $item['bet_number'];
    		$bet_content_per_lottery = explode('|', $betNumber);
    		foreach ($bet_content_per_lottery as $bet_content_item) {
    			$bet_content = explode(':',$bet_content_item);
    			$bet_number_lottery_id = intval($bet_content[0]);
    			$bet_number_per_lottery[$bet_number_lottery_id] = $bet_content_item;
    		}
    		
    		$scheduleId = $item['schedule_id'];
    		$scheduleRow = $scheduleMatrix[$idx];
    
    		foreach ($scheduleRow as $playType) {
    			if ($playType>0) {
//     				$scheduleLotteryId = $this->_getLotteryId($playType, $jcMatrix);
//     				$singleBetNumber = $this->_getSingleBetNumber($scheduleLotteryId, $betNumber);

    				$scheduleLotteryId = $lottery_id_map[$playType];
    				$singleBetNumber = $bet_number_per_lottery[$scheduleLotteryId];
    				if ($singleBetNumber) {
    					$betScheduleDict['bet_number'] = $singleBetNumber;
    					$betScheduleDict['schedule_id'] = $scheduleId;
    					$newBetSchedule[] = $betScheduleDict;
    				}
    			}
    		}
    		$idx++;
    	}
    	return $newBetSchedule;
    }
    
    private function _getJcMixMergeCount(array $scheduleOrders) {
    	$mergeCount = array();
    	foreach ($scheduleOrders as $scheduleOrder) {
    		$mergeCount[] = $this->_getMinMergeCountByBetNumber($scheduleOrder['bet_number'],$scheduleOrder['bet_contents']);
    	}
    	return min($mergeCount);
    }
    
    
    private function _getMinMergeCountByBetNumber($betNumber,$bet_contents) {
    	foreach($bet_contents as $lottery_id=>$bet_content){
    		$selectCount[] = C("MAX_MERGE_SCHEDULE.$lottery_id");
    	}
    	return min($selectCount);
    	 
//     	$bets = explode('|', $betNumber);
//     	$selectCount = array();
//     	foreach ($bets as $bet) {
//     		$selectNumber = explode(':', $bet);
//     		$lotteryId = $selectNumber[0];
//     		$selectCount[] = C("MAX_MERGE_SCHEDULE.$lotteryId");
//     	}
//     	return min($selectCount);
    }

    
    private function _hasSure(array $scheduleOrders) {
    	foreach ($scheduleOrders as $scheduleOrder) {
    		if(is_object($scheduleOrder)){
    			$scheduleOrder = (array)$scheduleOrder;
    		}
    		if ($scheduleOrder['is_sure']) {
    			return true;
    		}
    	}
    	return false;
    }


    private function _getSureScheduleCombinatorics(array $scheduleOrders, $maxSelectCount) {
    	import('@.Util.Combinatorics');
    	$schedule  	= $this->_getSureSchedule($scheduleOrders);
    	$sureCount 	= count($schedule['sure']);
    	$data 	   	= array();
    	$mathCombinatorics 	 = new \Math_Combinatorics();
    	$noSureCombinatorics = $mathCombinatorics->combinations($schedule['no_sure'], $maxSelectCount-$sureCount);
    	foreach ($noSureCombinatorics as $noSureCombinatoric) {
    		$data[] = array_merge($noSureCombinatoric, $schedule['sure']);
    	}
    	return $data;
    }


    private function _getSureSchedule(array $scheduleOrders) {
    	$data = array();
    	foreach ($scheduleOrders as $scheduleOrder) {
    		if(is_object($scheduleOrder)){
    			$scheduleOrder = (array)$scheduleOrder;
    		}
    		if($scheduleOrder['is_sure']) {
    			$data['sure'][] = $scheduleOrder;
    		} else {
    			$data['no_sure'][] = $scheduleOrder;
    		}
    	}
    	return $data;
    }
    
    
    private function _getJcMatrix($lotteryId) {
    	if ($lotteryId==C('JCZQ.MIX')) {
    		return C('JCZQ_MATRIX');
    	} elseif ($lotteryId==C('JCLQ.MIX')) {
    		return C('JCLQ_MATRIX');
    	} else {
    		return false;
    	}
    }


    private function _getScheduleCombinatorics(array $scheduleOrders, $maxScheduleCount) {
    	import('@.Util.Combinatorics');
    	$mathCombinatorics = new \Math_Combinatorics();
    	if (count($scheduleOrders)==$maxScheduleCount) {
    		return array($scheduleOrders);
    	} else {
    		return $mathCombinatorics->combinations($scheduleOrders, $maxScheduleCount);
    	}
    }
    
    
    private function _getPlayType($playTypeInfo) {
    	$data = explode(':', $playTypeInfo);
    	return intval($data[0]);
    }
    
     
    private function _initPlayTypeBox($lotteryId,$jcMatrix) {
    	$data = array();
    	foreach ($jcMatrix as $playType) {
    		$data[] = 0;
    	}
    	return $data;
    }
    

    private function _getSeriesStakeCount(array $cartesian, array $series) {
    	$mathCombinatorics = new \Math_Combinatorics();
    	$sum = 0;
    	foreach ($series as $serie) {
    		$combinatorics = $mathCombinatorics->combinations($cartesian, $serie);
    		foreach ($combinatorics as $combinatoric) {
    			$sum += array_product($combinatoric);
    		}
    	}
    	return $sum;
    }
    

    private function _getPlayList(array $scheduleOrders) {
    	$data = array();
    	foreach ($scheduleOrders as $scheduleOrder) {
    		if(is_object($scheduleOrder)){
    			$scheduleOrder = (array)$scheduleOrder;
    		}
    		$data[] = $this->_getPlayArray($scheduleOrder['bet_number'],$scheduleOrder['bet_contents']);
    	}
    	return $data;
    }


    private function _getPlayArray($betNumber,$bet_contents) {
    	$data = array();
//     	foreach($bet_contents as $lottery_id=>$bet_content){
//     		$data[] = count($bet_content['bet_options']);
//     	}
    	$stakes = explode('|', $betNumber);
    	foreach ($stakes as $stake) {
    		$tickets 	= explode(':', $stake);
    		$betOptions = explode(',', $tickets[1]);
    		$data[] 	= count($betOptions);
    	}
    	return $data;
    }
    
    

}