<?php 
namespace Home\Util;
abstract class VerifyNumber {
	
	const SINGLE_PLAY = 1;
	const MULTIPLE_PLAY = 2;
    
    public function __clone() {
        return false;
    }
    
    abstract public function verify($ball, $playType, $betType);
    abstract public function getTicketQuantity($betNumber, $playType, $betType);
    
    /**
     * 验证红区和蓝区中，同一个号码是不是只出现过一次
     */
    public function checkNoRepeat($betNumber) {
        $ball = str_replace('@', ',', $betNumber);
        $balls = explode('#', $ball);
        foreach ($balls as $ballNumber) {
            if(!$this->_checkBallCount($ballNumber)) {
                return false;
            }
        }
        return true;
    }
    

    public function checkBetType($ball, $quantity, $betType, $play_type='') {      // @TODO 11选5，有序
        if($quantity == 1) {
            if (strpos($ball, '@')!==false) {
                return false;
            }
            return ( $betType == C('BET_TYPE.SINGLE') );
        } else {
            if(strpos($ball, '@') !== false) {
                return ( $betType == C('BET_TYPE.SURE_OR_NOT') );
            } else {
                return ( $betType == C('BET_TYPE.MULTIPLE') );
            }
        }
    }
    
    
    public function formatBetNumber($ball, $playType) {
        if(in_array($playType, C('SORT_PLAY_TYPE'))) {
            return $this->_sortBetNumber($ball);
        } else {
            return $ball;
        }
    }
    
    
    protected function _sortAreaBall($balls) {
        $balls = explode('@', $balls);
        $sureBalls = explode(',', $balls[0]);
        asort($sureBalls);
        $sortSureBall = implode(',', $sureBalls);
        $notSureBalls = explode(',', $balls[1]);
        asort($notSureBalls);
        $sortNotSureBall = implode(',', $notSureBalls);
        $sortBall = "$sortSureBall@$sortNotSureBall";
        return trim($sortBall, '@');
    }
    

    protected function _checkBallCount($ball) {
        $balls = explode(',', $ball);
        $ballsCount = array_count_values($balls);
        foreach ($ballsCount as $count ) {
            if($count != 1) {
                return false;
            }
        }
        return true;
    }
    

    protected function _getCombination($ball, $selectCount) {
        if(strpos($ball, '@') !== false) {
            return $this->_getSureOrNotCombination($ball, $selectCount);
        } else {
            return $this->_getMultipleCombination($ball, $selectCount);
        }
    }


    private function _sortBetNumber($ball) {
        $balls = explode('#', $ball);
        $str = '';
        foreach ($balls as $ball) {
            $str .= '#'.$this->_sortAreaBall($ball);
        }
        return trim($str, '#');
    }
    
    
    private function _getMultipleCombination($ball, $selectCount) {
        $countBall = substr_count($ball, ',') + 1;
        return combination($countBall, $selectCount);
    }
    
    
    protected function _checkRange($betNumber, $allowBall) {
        $betNumbers = str_replace(array('@', '#'), ',', $betNumber);
        $balls = explode(',', $betNumbers);
        foreach ($balls as $ball) {
        	if (!in_array($ball, $allowBall)) {
        		return false;
        	}
        }
        return true;
    }
    
    
    private function _getSureOrNotCombination($ball, $selectCount) {
        $balls = explode('@', $ball);
        $countSureBall = count(explode(',', $balls[0]));
        $countNoSureBall = count(explode(',', $balls[1]));
        return combination($countNoSureBall, ($countSureBall+$countNoSureBall-$selectCount));
    }
    
}


?>