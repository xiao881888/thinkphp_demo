<?php
namespace Home\Util;
use Home\Util\VerifyNumber;
class VerifyDltNumber extends VerifyNumber {
    private $_sure_needle = '';
    private $_each_number_needle = '';
    public function __construct(){
    	$this->_each_number_needle = C('BET_NUMBER_FORMAT_SEPERATOR.EACH_NUMBER');
    	$this->_sure_needle = C('BET_NUMBER_FORMAT_SEPERATOR.SURE_OR_NOT');
    }
    
    public function checkRange($betNumber) {
        $balls = explode('#', $betNumber);
        $red = array(   '01', '02', '03', '04', '05', '06', '07', '08', '09', '10',
                        '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
                        '21', '22', '23', '24', '25', '26', '27', '28', '29', '30',
                        '31', '32', '33', '34', '35');
        $blue = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
        foreach ($balls as $key=>$ball) {
            $allowBalls = array($red, $blue);
            if(!$this->_checkRange($ball, $allowBalls[$key])) {
                return false;
            }
        }
        return true;
    }
    
	
	public function getTicketQuantity($betNumber, $playType, $betType) {
	    $balls = explode('#', $betNumber);
	    return $this->_countCombination($balls[0], 'red') * $this->_countCombination($balls[1], 'blue');
	}
	
    
    public function verify($ball, $playType, $betType) {
        $vaildFormat 	 = $this->_checkFormat($ball);
        $vaildBallNumber = $this->checkRange($ball);
        $vaildQuantity 	 = $this->_checkBallQuantity($ball, $betType);
        $norepeat        = $this->checkNoRepeat($ball);
        return $vaildFormat && $vaildBallNumber && $vaildQuantity && $norepeat;
    }

    private function _checkFormat($ball) {
    	if (preg_match('/^([0-3]\d,?){1,35}#([01]\d,?){0,12}$/', $ball)) {    	// 验证单式和复式
    		return true;
    	} elseif (preg_match('/^([0-3]\d,?){1,35}@([0-3]\d,?){1,35}#(([01]\d)?[@,]?){0,12}$/', $ball)) {   // 验证胆拖
    		return true;
    	} else {
    		return false;
    	}
    }

    private function _checkBallQuantity($betNumber, $betType) {
        $balls 	= explode('#', $betNumber);
        $red_balls = $balls[0];
        $blue_balls = $balls[1];
        
        $red_ball_is_valid = false;
        $blue_ball_is_valid = false;
        
        if($betType==C('BET_TYPE.SURE_OR_NOT')){
        	//胆拖检查前区要>=6个号
        	if(strpos($red_balls, $this->_sure_needle)){
        		$red_ball_bet_content = str_replace($this->_sure_needle, $this->_each_number_needle, $red_balls);
        		$red_ball_numbers = count(explode($this->_each_number_needle, $red_ball_bet_content));
        		if($red_ball_numbers>=6){
        			$red_ball_is_valid = true;
        		}
        	}else{
        		$red_ball_numbers = count(explode($this->_each_number_needle, $red_balls));
        		if($red_ball_numbers>=5){
        			$red_ball_is_valid = true;
        		}
        	}
        	//胆拖检查后区>=3个号
        	if(strpos($blue_balls, $this->_sure_needle)){
        		$blue_ball_bet_content = str_replace($this->_sure_needle, $this->_each_number_needle, $blue_balls);
        		$blue_ball_numbers = count(explode($this->_each_number_needle, $blue_ball_bet_content));
        		if($blue_ball_numbers>=3){
        			$blue_ball_is_valid = true;
        		}
        	}else{
        		$blue_ball_numbers = count(explode($this->_each_number_needle, $blue_balls));
        		if($blue_ball_numbers>=2){
        			$blue_ball_is_valid = true;
        		}
        	}
        }else{
        	$red_ball_numbers = count(explode($this->_each_number_needle, $red_balls));
        	if($red_ball_numbers>=5){
        		$red_ball_is_valid = true;
        	}
        	$blue_ball_numbers = count(explode($this->_each_number_needle, $blue_balls));
        	if($blue_ball_numbers>=2){
        		$blue_ball_is_valid = true;
        	}
        }
       
        return $red_ball_is_valid && $blue_ball_is_valid;
    }

    private function _countCombination($ball, $area='red') {
        $selectCount = ($area=='red' ? 5 : 2);
        return $this->_getCombination($ball, $selectCount);
    }
    
}

?>