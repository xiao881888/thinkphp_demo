<?php
namespace Home\Util;
use Home\Util\VerifyNumber;
class VerifyFc3dNumber extends VerifyNumber {
    const PLAY_TYPE_DIRECT = '11';
    const PLAY_TYPE_COMBINATION_THREE = '12';
    const PLAY_TYPE_COMBINATION_SIX = '13';
    
    
    public function checkRange($betNumber) {
        $balls = explode('#', $betNumber);
        $allowBall = range(0, 9);
        foreach ($balls as $ball) {
            if(!$this->_checkRange($ball, $allowBall)) {
                return false;
            }
        }
        return true;
    }
    
    
    public function verify($ball, $playType, $betType) {
        $vaildFormat = $this->_checkFormat($ball, $playType);
        $isCombinationThreeSingle = ( $playType == self::PLAY_TYPE_COMBINATION_THREE && $betType==self::SINGLE_PLAY );
        if ($playType != self::PLAY_TYPE_DIRECT && !$isCombinationThreeSingle ) {
            $norepeat   = $this->checkNoRepeat($ball);
        } else {
            $norepeat   = true;
        }
        $vaildBallNumber = $this->checkRange($ball);
        return $vaildFormat && $vaildBallNumber && $norepeat;
    }
    
    
    public function getTicketQuantity($betNumber, $playType, $betType) {
        if ( $playType == self::PLAY_TYPE_DIRECT ) {
            return $this->_getDirectTicketQuantity($betNumber);
        } elseif ( $playType == self::PLAY_TYPE_COMBINATION_THREE ) {
            if ($betType == self::SINGLE_PLAY) {
                return 1;
            } else {
                return $this->_getCombination($betNumber, 2) * 2;
            }
        } elseif( $playType == self::PLAY_TYPE_COMBINATION_SIX ) {
            return $this->_getCombination($betNumber, 3);
        } else {
            return 0;
        }
    }


    private function _getDirectTicketQuantity($betNumber) {
    	$balls = explode('#', $betNumber);
    	$quantity = 1;
    	foreach ($balls as $ball) {
    		$quantity = $quantity * count(explode(',', $ball));
    	}
    	return $quantity;
    }
    

    private function _checkFormat($ball, $playType) {
    	if ( $playType == self::PLAY_TYPE_DIRECT ) {
    		return preg_match('/^(\d,?#?)+$/', $ball);
    	} elseif ( $playType == self::PLAY_TYPE_COMBINATION_THREE ) {
    		return preg_match('/^(\d,?){2,10}$/', $ball);
    	} elseif ( $playType == self::PLAY_TYPE_COMBINATION_SIX ) {
    		return preg_match('/^(\d,?){3,10}$/', $ball);
    	} else {
    		return false;
    	}
    }
    
}

?>