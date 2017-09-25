<?php 

namespace Home\Model;
use Think\Model;

class UserAccountLogModel extends Model {
    
    public function addLog($uid, $type, $amount, $balance, $operatorId, $remark, $frozenAmount, $frozenBalance) {
        $data = array(
            'uid' => $uid,
            'ual_type' => $type,
            'ual_amount' => $amount,
            'ual_balance' => $balance,
            'operator_id' => $operatorId,
            'ual_create_time' => getCurrentTime(),
            'ual_remark' => $remark,
            'ual_frozen_amount' => $frozenAmount,
            'ual_frozen_balance' => $frozenBalance,
        );
        return $this->add($data);
    }
    
}


?>