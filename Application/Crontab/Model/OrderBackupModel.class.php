<?php

namespace Crontab\Model;

use Think\Model;

class OrderBackupModel extends Model
{
    public function getOrderTotalAmountByUid($uid,$order_type){
        $where['uid'] = $uid;
        if($order_type){
            $where['order_type'] = $order_type;
        }
        $where['order_winnings_status'] = array('IN',array(-1,1,2));
        if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
            return $this->db(1,C('READ_DB'),true)->where($where)->sum('order_total_amount - order_refund_amount');
        } else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
            return $this->where($where)->sum('order_total_amount - order_refund_amount');
        } else {
            return $this->where($where)->sum('order_total_amount - order_refund_amount');
        }

    }

    public function getOrderWinningAmountByUid($uid,$order_type){
        $where['uid'] = $uid;
        if($order_type){
            $where['order_type'] = $order_type;
        }
        $where['order_winnings_status'] = array('IN',array(-1,1,2));
        if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
            return $this->db(1,C('READ_DB'),true)->where($where)->sum('order_winnings_bonus');
        } else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
            return $this->where($where)->sum('order_winnings_bonus');
        } else {
            return $this->where($where)->sum('order_winnings_bonus');
        }
    }
}