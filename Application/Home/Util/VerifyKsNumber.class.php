<?php
namespace Home\Util;
use Home\Util\VerifyNumber;
/**
 * @date 2014-12-30
 * @author tww <merry2014@vip.qq.com>
 */
class  VerifyKsNumber extends VerifyNumber{
	const THREE_SELECT_COUNT = 3;
	const TWO_SELECT_COUNT = 2 ;
	const THREE_TTX = 'A,A,A';
	const THREE_LTX = 'A,B,C';
	
	public function verify($ball, $playType, $betType){
		switch ($playType){
			case C('KS_PLAY_TYPE.SUM'): 
				return $this->_checkSumRange($ball) && $this->checkNoRepeat($ball) && $this->_checkLength($ball, 1);
			case C('KS_PLAY_TYPE.THREE_SAME_NUM_ALL'): 
				return $ball == self::THREE_TTX;
			case C('KS_PLAY_TYPE.THREE_SAME_NUM_SINGLE'):
				return $this->_checkThreeDx($ball) && $this->_checkLength($ball, 3);
			case C('KS_PLAY_TYPE.THREE_DIFF_NUM'):
				return $this->_isSort($ball) && $this->checkNoRepeat($ball) && $this->_checkNumRange($ball) && $this->_checkLength($ball, 3);
			case C('KS_PLAY_TYPE.THREE_SEQUENCE_ALL'):
				return $ball == self::THREE_LTX;
			case C('KS_PLAY_TYPE.TWO_SAME_NUM_ALL'):
				return $this->_checkSameTwoAll($ball) && $this->_checkLength($ball, 3);
			case C('KS_PLAY_TYPE.TWO_SAME_NUM_SINGLE'):
				return $this->_checkSameTwoSingle($ball) && $this->_checkLength($ball, 3);
			case C('KS_PLAY_TYPE.TWO_DIFF_NUM'):
				return $this->_isSort($ball) && $this->checkNoRepeat($ball) && $this->_checkNumRange($ball) && $this->_checkLength($ball, 2);
		}
	}
	
	private function _isSort($bet_number){
		$bet_numbers = explode(',', $bet_number);
		sort($bet_numbers);
		$new_num = implode(',', $bet_numbers);
		return $new_num == $bet_number;		
	}
	
	private function _checkLength($bet_number, $length){
		$bet_numbers = explode(',', $bet_number);
		return count($bet_numbers) == $length;
	}
	
	
	private function _checkSameTwoSingle($bet_number){
		$bet_numbers = explode(',', $bet_number);
		if($bet_numbers[0] != $bet_numbers[1]){
			return false;
		}
		$range = range(1, 6);
		foreach ($bet_numbers as $bet_num){
			if(!in_array($bet_num, $range)){
				return false;
			}
		}
		return count(array_unique($bet_numbers)) == 2;
	}
		
	
	private function _checkSameTwoAll($bet_number){
		$two_all_range = array('1,1,*', '2,2,*', '3,3,*', '4,4,*', '5,5,*', '6,6,*');
		return in_array($bet_number, $two_all_range);
	}
	
	
	private function _checkThreeDx($bet_number){
		$three_dx_range = array('1,1,1', '2,2,2', '3,3,3', '4,4,4', '5,5,5', '6,6,6');
		return in_array($bet_number, $three_dx_range);
	}
	

	private function _checkSumRange($bet_number){
		$sum_range = range(4, 17);
		$bet_number = explode(',', $bet_number);
		foreach ($bet_number as $num){
			if(!in_array($num, $sum_range)){
				return false;
			}
		}	
		return true;
	}
	
	
	private function _checkNumRange($bet_number){
		$balls = range(1, 6);
		$bet_number = str_replace('@', ',', $bet_number);
		$bet_number_list = explode(',', $bet_number);
		foreach ($bet_number_list as $num){
			if(!in_array($num, $balls)){
				return false;
			}
		}
		return true;
	}
	
	
	public function getTicketQuantity($betNumber, $playType, $betType){
		if($playType == C('KS_PLAY_TYPE.SUM')){
			return count(explode(',', $betNumber));
		}else if($playType == C('KS_PLAY_TYPE.TWO_DIFF_NUM')){			
			return $this->_getCombination($betNumber, self::TWO_SELECT_COUNT);
		}else{
			return $this->_getCombination($betNumber, self::THREE_SELECT_COUNT);
		}	
	}

}