<?php
namespace Home\Model;
use Think\Model;

class FullReducedCouponConfigModel extends Model{

    public function getActivityStatusById($id){
        $where['frcc_id'] = $id;
        return $this->where($where)->getField('frcc_status');
    }

    public function getCouponId($id){
        $where['frcc_id'] = $id;
        return $this->where($where)->getField('coupon_id');
    }

    public function getFullReducedCouponInfoById($id){
        $where['frcc_id'] = $id;
        return $this->where($where)->find();
    }

}