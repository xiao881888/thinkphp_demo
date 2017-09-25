<?php 
namespace Integral\Model;
use Think\Model;

class CouponModel extends TigerBaseModel {

    public function getCouponById($coupon_id) {
        $condition = array('coupon_id'=>$coupon_id);
        return $this->where($condition)
            ->find();
    }

}



