<?php
namespace Home\Util;
use Home\Util\VerifyNumber;
class VerifySyxwNumber extends VerifyNumber {
    const Q_TWO_ZHIX 	= '30';
    const Q_TWO_ZUX 	= '31';
    const Q_THREE_ZHIX 	= '32';
    const Q_THREE_ZUX 	= '33';
    const Q_LEXUAN2 	= '34';
    const Q_LEXUAN3 	= '35';
    private $_new_play_types = array(
    		TIGER_PLAY_ID_OF_LEXUAN2,
    		TIGER_PLAY_ID_OF_LEXUAN3,
    		TIGER_PLAY_ID_OF_LEXUAN4,
    		TIGER_PLAY_ID_OF_LEXUAN5
    );
    private $_new_play_types_stake = array(
    		TIGER_PLAY_ID_OF_LEXUAN2 => 3,
    		TIGER_PLAY_ID_OF_LEXUAN3 => 3,
    		TIGER_PLAY_ID_OF_LEXUAN4 => 5,
    		TIGER_PLAY_ID_OF_LEXUAN5 => 7
    );
    public function checkRange($betNumber) {   
        $balls = explode('#', $betNumber);
        $allowBall = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11');
        foreach ($balls as $ball) {
            if(!$this->_checkRange($ball, $allowBall)) {
                return false;
            }
        }
        return true;
    }
    
    public function getTicketQuantity($betNumber, $playType, $betType) {
		if (in_array($playType, $this->_new_play_types)) {
    		return $this->_new_play_types_stake[$playType];
    	}else{
    		$selectCount = getSelectCount($playType);
    		if(strpos($betNumber, '#')) {
    			$balls = explode('#', $betNumber);
    			$quantity = 1;
    			foreach ($balls as $ball) {
    				$quantity = $quantity * count(explode(',', $ball));
    			}
    			return $quantity;
    		} else {
    			return $this->_getCombination($betNumber, $selectCount);
    		}
    	}
        
    }
    
    
    public function verify($ball, $playType, $betType) {
        $vaildFormat     = $this->_checkFormat($ball, $playType);
        $norepeat        = $this->checkNoRepeat($ball);
        $vaildBallNumber = $this->checkRange($ball);
        return $vaildFormat && $vaildBallNumber && $norepeat;
    }
    
    
    private function _checkFormat($ball, $playType) {
        if (($playType>=22 && $playType<=28) || $playType==self::Q_TWO_ZUX || $playType==self::Q_THREE_ZUX 
        || $playType==TIGER_PLAY_ID_OF_LEXUAN4 || $playType==TIGER_PLAY_ID_OF_LEXUAN5) {
            $selectCount = C('SYXW_SELECT_COUNT.'.$playType);
        }
        if (strpos($ball, '@') !== false) {
            $vaildSureBall = $this->_checkSureBallQuantity($ball, $selectCount);
            $vaildFormat = preg_match('/^([01]\d,?){1,11}@([01]\d,?){1,11}$/', $ball);
            return $vaildSureBall && $vaildFormat;
        } elseif ($selectCount) {
            return preg_match('/^([01]\d,){'.($selectCount-1).',10}([01]\d)$/', $ball);
        } elseif ($playType==self::Q_TWO_ZHIX || $playType==TIGER_PLAY_ID_OF_LEXUAN2) {
            return preg_match('/^([01]\d,?){1,11}#([01]\d,?){1,11}$/', $ball);
        } elseif ($playType==self::Q_THREE_ZHIX || $playType==TIGER_PLAY_ID_OF_LEXUAN3) {
            return preg_match('/^(([01]\d,?){1,11}#){2}([01]\d,?){1,11}$/', $ball);
        } else {
            return false;
        }
    }
    
    
    private function _checkSureBallQuantity($ball, $selectCount) {
        $balls = explode('@', $ball);
        $sureBallCount = count(explode(',', $balls[0]));
        return ( $sureBallCount < $selectCount ) ;
    }

	public function checkBetType($ball, $quantity, $betType, $play_type=''){      // @TODO 11选5，有序
    	if($play_type && in_array($play_type, $this->_new_play_types)){
    		return ( $betType == C('BET_TYPE.SINGLE') );
    	}
    	if($quantity == 1) {
    		if (strpos($ball, '@')!==false) {
    			return false;
    		}
    		return ( $betType == C('BET_TYPE.SINGLE') );
    	} else {
    		if(strpos($ball, '@') !== false) {
    			return ( $betType == C('BET_TYPE.SURE_OR_NOT') );
    		} else {
    			return ( $betType == C('BET_TYPE.MULTIPLE') || $betType == C('BET_TYPE.POSITION_MULTIPLE') );
    		}
    	}
    }

}


?>