<?php
namespace Home\Validator;
class JcValidator  {	
	private $_optimize_series_type = array();
	public function __construct(){		
		$this->_optimize_series_type = C('OPTIMIZE_SERIES_TYPE');
	}
	
	public function validateTicketSeriesType($series_type){
		if(!in_array($series_type,$this->_optimize_series_type)){
			return false;
		}
		return true;
	}
	
	public function validateTicketSchedules($ticket_schedules){
		$schedule_ids = array();
		$schedule_lottery_ids = array();
		foreach ($ticket_schedules as $ticket_schedule) {
// 			if(!$this->validateBetContentFormat($ticket_schedule['bet_number'])){
// 				return false;
// 			}
			if(!in_array($ticket_schedule['schedule_id'],$schedule_ids)){
				$schedule_ids[] = $ticket_schedule['schedule_id'];				
			}
			
			if(!in_array($ticket_schedule['schedule_lottery_id'],$schedule_lottery_ids)){
				$schedule_lottery_ids[] = $ticket_schedule['schedule_lottery_id'];
			}
		}
		
		return true;
	}
	
	public function getTicketLotteryId($schedule_lottery_ids) {
		ApiLog('schedule_ids:'.print_r($schedule_lottery_ids,true), 'tick');
		
		$schedule_lottery_ids = array_unique($schedule_lottery_ids);
		ApiLog('array_unique schedule_ids:'.print_r($schedule_lottery_ids,true).'==='.count($schedule_lottery_ids), 'tick');
		if(count($schedule_lottery_ids) == 1){
			$lottery_id = $schedule_lottery_ids[0];
		}else{
			$lottery_char = C('LOTTERY_TYPE.'.$schedule_lottery_ids[0]);			
			$lottery_id = C(strtoupper($lottery_char).'.MIX');
		}
		ApiLog('$lottery_id:'.$lottery_id, 'tick');
		
		return $lottery_id;
	}
	
	public function validateBetOption($bet_option){
		if (!preg_match('/^(\d+)$/', $bet_option)) {
			return false;
		}
		return true;
	}
	
	public function validateSeriesType($ticket_schedule_num, $ticket_series_type) {
		$series_count_config = C("MERGE_COUNT.$ticket_series_type");
		$series_param_number = $series_count_config['count'];
		if($ticket_schedule_num!=$series_param_number){
			return false;
		}else{
			return true;
		}
	}
	
	public function validateSeriesNumberOverMaxLimit($ticket_lottery_ids, $ticket_series_type) {
		ApiLog('ticket_ids:'.$ticket_series_type.'==='.print_r($ticket_lottery_ids,true), 'tick');
		$ticket_lottery_id = $this->getTicketLotteryId($ticket_lottery_ids);
		
		if (isJcMix($ticket_lottery_id)) {
			$max_series_number = $this->_getJcMixSeriesNumber($ticket_lottery_ids);
			ApiLog('isJcMix $series_number:'.$max_series_number, 'tick');
				
		} else {
			$max_series_number = C("MAX_MERGE_SCHEDULE.$ticket_lottery_id");
			ApiLog('$series_number:'.$max_series_number, 'tick');				
		}
		
		$series_count_config = C("MERGE_COUNT.$ticket_series_type");
		$series_param_number = $series_count_config['count'];
		ApiLog('$$series_param_number:'.$series_param_number.'==='.print_r($series_count_config,true), 'tick');
		
		if($max_series_number>=$series_param_number){
			return true;
		}else{
			return false;
		}
	}
	
	private function _getJcMixSeriesNumber($ticket_lottery_ids) {
		$series_number_collection = array();
		foreach ($ticket_lottery_ids as $ticket_lottery_id) {
			$series_number_collection[] = C("MAX_MERGE_SCHEDULE.$ticket_lottery_id");
		}
		return min($series_number_collection);
	}
}

?>