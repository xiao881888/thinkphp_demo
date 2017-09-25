<?php 
namespace Home\Model;
use Think\Model;

class UserAccountModel extends Model {
    
    public function getUserAccount($uid) {
        $condtion = array('uid'=>$uid);
        return $this->field('user_account_balance, user_account_frozen_balance, user_account_recharge_amount, user_account_consume_amount,
                             user_account_coupon_amount, user_account_coupon_consumption')
                    ->where($condtion)
                    ->find();
    }
    
    
    public function deductMoney($uid, $money, $orderId, $logType, $consume=true) {
        if ($money <= 0) { return 0; }
        $deduct = $this->_deductMoney($uid, $money);
        
        if($deduct===false) {return false;}
        $consumeResult = ( $consume ? $this->updateAmountStatics($uid, $deduct) : true );
        $userAccount = $this->getUserAccount($uid);
        $logResult = D('UserAccountLog')->addLog($uid, $logType, -$deduct, $userAccount['user_account_balance'],
            0, buildUserAccountLogDesc($logType,array('id'=>$orderId)), 0, $userAccount['user_account_frozen_balance']);
        return ( $consumeResult && $logResult ? $deduct : false );
    }
    
    
    public function increaseMoney($uid, $money, $id, $logType, $consume=true) {
        $result = $this->_increaseMoney($uid, $money, $consume);
        $consumeResult = ( $consume ? $this->updateRechargeStatics($uid, $money) : true );
        $userAccount = $this->getUserAccount($uid);
        $logResult = D('UserAccountLog')->addLog($uid, $logType, $money, $userAccount['user_account_balance'],
            0, buildUserAccountLogDesc($logType,array('id'=>$id)), 0, $userAccount['user_account_frozen_balance']);
        return ( $result && $logResult && $consumeResult ? $money : false );
    }
    

    public function increaseFrozenBalance($uid, $money, $id, $logType) {
        $result = $this->_increaseFrozenBalance($uid, $money);
        $userAccount = $this->getUserAccount($uid);
        $logResult = D('UserAccountLog')->addLog($uid, $logType, 0, $userAccount['user_account_balance'],
            0, buildUserAccountLogDesc($logType,array('id'=>$id)), $money, $userAccount['user_account_frozen_balance']);
        return ( $result!==false && $logResult ? $money : false );
    }
    
    
    public function deductFrozenBalance($uid, $money, $id, $logType) {
        $result = $this->_deductFrozenBalance($uid, $money);
        $userAccount = $this->getUserAccount($uid);
        $logResult = D('UserAccountLog')->addLog($uid, $logType, 0, $userAccount['user_account_balance'],
            0, buildUserAccountLogDesc($logType,array('id'=>$id)), -$money, $userAccount['user_account_frozen_balance']);
        return ( $result && $logResult ? $money : false );
    }
    
    
    
    public function getUserBalance($uid) {
        $condition = array('uid'=>$uid);
        return $this->where($condition)
                    ->getField('user_account_balance');
    }
    
    
    public function getFrozenBalance($uid) {
    	$condition = array('uid'=>$uid);
    	return $this->where($condition)
    				->getField('user_account_frozen_balance');
    }
    
    
    public function updateAmountStatics($uid, $money) {
        $condition = array('uid'=>$uid);
        return $this->where($condition)
                    ->setInc('user_account_consume_amount', $money);
    }
    

    public function updateConsumptionCouponStatics($uid, $couponAmount) {
        $condition = array('uid'=>$uid);
        return $this->where($condition)
                    ->setInc('user_account_coupon_consumption', $couponAmount);
    }
    
    
    public function increaseUserAccountCouponAmount($uid, $couponAmount){
    	return $this->updateBuyCouponStatics($uid, $couponAmount);
    }
    
    public function updateBuyCouponStatics($uid, $couponAmount) {
        $condition = array('uid'=>$uid);
        return $this->where($condition)
                    ->setInc('user_account_coupon_amount', $couponAmount);
    }
    
    
    public function updateRechargeStatics($uid, $rechargeMoney) {
        $condition = array('uid'=>$uid);
        return $this->where($condition)
                    ->setInc('user_account_recharge_amount', $rechargeMoney);
    }
    
    
    public function addUserAccount($uid) {
        $data = array(
            'uid' => $uid,
            'user_account_balance' => 0,
            'user_account_frozen_balance' => 0,
        );
        return $this->add($data);
    }
    
    
    public function withdraw($uid, $money, $id) {
        $deductResult = $this->_deductMoney($uid, $money);
        $increaseResult = $this->_increaseFrozenBalance($uid, $money);
        $userAccount = $this->getUserAccount($uid);
        $logResult = D('UserAccountLog')->addLog($uid, C('USER_ACCOUNT_LOG_TYPE.APPLY_WITHDRAW'), -$money, $userAccount['user_account_balance'],
            0, buildUserAccountLogDesc(C('USER_ACCOUNT_LOG_TYPE.APPLY_WITHDRAW'),array('id'=>$id)), $money, $userAccount['user_account_frozen_balance']);
        return ( $deductResult && $increaseResult && $logResult ? $money : false );
    }

    public function refuseWithdraw($withdraw_data){
        $return = false;

        M()->startTrans();
        $unfreeze_money = $this->_deductFrozenBalance($withdraw_data['uid'], $withdraw_data['withdraw_amount']); 
        if ($unfreeze_money) {
            $add_account_balance = $this->_increaseMoney($withdraw_data['uid'], $withdraw_data['withdraw_amount']);
            if ($add_account_balance) {
                $user_account = $this->getUserAccount($withdraw_data['uid']);

                $add_log = D('UserAccountLog')->addLog($withdraw_data['uid'], C('USER_ACCOUNT_LOG_TYPE.REFUSE_DRAW'), $withdraw_data['withdraw_amount'], $user_account['user_account_balance'], 0, buildUserAccountLogDesc(C('USER_ACCOUNT_LOG_TYPE.REFUSE_DRAW'), array('id'=>$withdraw_data['withdraw_id'])), -$withdraw_data['withdraw_amount'], $user_account['user_account_frozen_balance']);

                if ($add_log) {
                    $new_withdraw_data = array(
                        'withdraw_id' => $withdraw_data['withdraw_id'],
                        'withdraw_status' => C('WITHDRAW_STATUS.WITHDRAW_STATUS_REFUSE'), 
                        'withdraw_remark' => C('AUTO_REFUSE_WITHDRAW_REMARK'),
                        );
                    $update_withdraw_status = D('Withdraw')->save($new_withdraw_data);

                    if ($update_withdraw_status) {
                        $return = true;
                    }
                }
            }
        }

        if ($return) {
            M()->commit();
        } else {
            M()->rollback();
        }

        return $return;
    }
    
    
    public function cancelFollow($uid, $money, $id, $consume) {
        $deductResult = $this->_deductFrozenBalance($uid, $money);
        $increaseResult = $this->_increaseMoney($uid, $money, $consume);
        $userAccount = $this->getUserAccount($uid);
        $logResult = D('UserAccountLog')->addLog($uid, C('USER_ACCOUNT_LOG_TYPE.CANCEL_FOLLOW'), $money, $userAccount['user_account_balance'],
            0, buildUserAccountLogDesc(C('USER_ACCOUNT_LOG_TYPE.CANCEL_FOLLOW'),array('id'=>$id)), -$money, $userAccount['user_account_frozen_balance']);
        return ( $deductResult && $increaseResult && $logResult ? $money : false );
    }

    public function refundFollowMoney($uid, $total_amount,$refund_money,$coupon_amount, $id,$type='') {
    	if(empty($type)){
    		$type = C('USER_ACCOUNT_LOG_TYPE.FOLLOW_FAILED_REFUND');
    	}
    	
    	$account_data['user_account_frozen_balance'] = array('exp','user_account_frozen_balance-'.$total_amount);
    	$account_data['user_account_balance'] = array('exp','user_account_balance+'.$refund_money);
    	$account_data['user_account_consume_amount'] = array('exp','user_account_consume_amount-'.$total_amount);
    	$account_data['user_account_coupon_amount'] = array('exp','user_account_coupon_amount+'.$coupon_amount);
    	$account_data['user_account_coupon_consumption'] = array('exp','user_account_coupon_consumption-'.$coupon_amount);
    	 
    	$map['uid'] = $uid;
    	$map['user_account_frozen_balance'] = array('egt',$total_amount);
    	$map['user_account_consume_amount'] = array('egt',$refund_money);
    	$map['user_account_coupon_consumption'] = array('egt',$coupon_amount);
    	$result = $this->where($map)->save($account_data);

    	$remark = $id.'=='.$total_amount.'=='.$refund_money.'=='.$coupon_amount;
    	$userAccount = $this->getUserAccount($uid);
    	$logResult = D('UserAccountLog')->addLog($uid, $type, $total_amount, $userAccount['user_account_balance'],
    			0, $remark, -$total_amount, $userAccount['user_account_frozen_balance']);
    	return ( $result && $logResult ? $total_amount : false );
    }

    private function _deductFrozenBalance($uid, $money) {
        if($money <= 0) { return 0; }
        $condition = array('uid'=>$uid,
        					'user_account_frozen_balance'=>array('egt',$money));
        $result = $this ->where($condition)
                        ->setDec('user_account_frozen_balance', $money);
        return $result;
    }
    

    private function _increaseMoney($uid, $money, $consume=0) {
        $condition = array('uid'=>$uid);
        $result = $this ->where($condition)
                        ->setInc('user_account_balance', $money);
        return $result;
    }
    

    private function _deductMoney($uid, $money) {
        $userBalance = $this->getUserBalance($uid);
        if($userBalance>=$money) {
            $deduct = $money;
        } else {
            return false;
        }
        $condition = array('uid'=>$uid);
        $condition['user_account_balance'] = array('egt',$money);
        $result = $this ->where($condition)
                        ->setDec('user_account_balance', $deduct);
        return ( $result ? $deduct : false );
    }
    
    
    private function _increaseFrozenBalance($uid, $money) {
        $condition = array('uid'=>$uid);
        $result = $this ->where($condition)
                        ->setInc('user_account_frozen_balance', $money);
        return ( $result ? $money : false );
    }


    public function refundCobetMoney($uid, $total_amount,$refund_money,$coupon_amount, $id,$type) {
        $account_data['user_account_balance'] = array('exp','user_account_balance+'.$refund_money);
        $account_data['user_account_consume_amount'] = array('exp','user_account_consume_amount-'.$total_amount);
        $account_data['user_account_coupon_amount'] = array('exp','user_account_coupon_amount+'.$coupon_amount);
        $account_data['user_account_coupon_consumption'] = array('exp','user_account_coupon_consumption-'.$coupon_amount);

        $map['uid'] = $uid;
        $map['user_account_consume_amount'] = array('egt',$refund_money);
        $map['user_account_coupon_consumption'] = array('egt',$coupon_amount);
        $result = $this->where($map)->save($account_data);
        $remark = '合买退款:'.$id.'=='.$total_amount.'=='.$refund_money.'=='.$coupon_amount;
        $userAccount = $this->getUserAccount($uid);
        $logResult = D('Home/UserAccountLog')->addLog($uid, $type, $total_amount, $userAccount['user_account_balance'],
            0, $remark, 0, $userAccount['user_account_frozen_balance']);
        return ( $result && $logResult ? $total_amount : false );
    }
    

}



