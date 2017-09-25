<?php 
namespace Home\Model;
use Think\Model;

class WithdrawModel extends Model {

    public function addWithdraw($uid, $amount, $bankCardNo, $bankCardAddress, $accountName, $bankCardType, $pay_fee = 0) {
        $data = array(
            'uid' => $uid,
            'withdraw_amount' => $amount,
            'withdraw_request_time' => getCurrentTime(),
            'withdraw_status' => C('WITHDRAW_STATUS.NO_AUDIT'),
            'withdraw_modify_time' => getCurrentTime(),
            'user_bank_card_number' => $bankCardNo,
            'user_bank_card_address' => $bankCardAddress,
            'user_bank_card_account_name' => $accountName,
            'user_bank_card_type' => $bankCardType,
            'withdraw_fee' => $pay_fee,
            'withdraw_actual_money' => ($amount - $pay_fee)
        );
        $withDrawId = $this->add($data);
        $logResult = D('UserAccount')->withdraw($uid, $amount, $withDrawId);
        return ( $withDrawId && $logResult ? $withDrawId : false );
    }

    public function getUserWithdrawAmount($uid){
        $map = array();
        $map['uid'] = $uid;
        $map['withdraw_status'] = WITHDRAW_STATUS_PAID;

        return $this->where($map)->sum('withdraw_amount');
    }
}



?>