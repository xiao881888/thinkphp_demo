<?php 
namespace H5\Model;
use Think\Model;
class UserCouponLogModel extends Model {
    
    public function addUserCouponLog($uid, $userCouponId, $amount, $couponBalance, $type, $operatorId,$remark = '') {
        $data = array(
            'uid' => $uid,
            'user_coupon_id' => $userCouponId,
            'coupon_amount' => $amount,
            'user_coupon_balance' => $couponBalance,
            'ucl_create_time' => getCurrentTime(),
            'ucl_type' => $type,
            'operator_id' => $operatorId,
            'remark' => $remark,
        );
        return $this->add($data);
    }
    
    
}

