<?php 
namespace Home\Model;
use Think\Model;

class ChannelCouponPlanModel extends Model {
    
    public function queryInfoById($plan_id) {
        $map['plan_id'] = $plan_id;
        return $this->where($map)->find();
    }
    
}

