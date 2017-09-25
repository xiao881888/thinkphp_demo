<?php 
namespace Home\Model;
use Think\Model;

class OrderBackupModel extends OrderModel {
    public function getOrderTotalWinningAmountByIds($order_ids){
        $where['order_id'] = array('IN',$order_ids);
        return $this->where($where)->sum('order_winnings_bonus');
    }

    public function getOrderTotalAmountByIds($order_ids){
        $where['order_id'] = array('IN',$order_ids);
        $where['order_status'] = array('IN',array(3,8));
        return $this->where($where)->sum('order_total_amount');
    }
}