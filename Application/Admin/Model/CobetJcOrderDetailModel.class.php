<?php
namespace Home\Model;
use Think\Model;

class CobetJcOrderDetailModel extends Model {

    public function getOrderIdsByScheduleIds($schedule_ids){
        $where['schedule_id'] = array('IN',$schedule_ids);
        return $this->where($where)->getField('order_id',true);
    }

}