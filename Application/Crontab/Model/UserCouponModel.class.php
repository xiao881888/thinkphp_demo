<?php

namespace Crontab\Model;

use Think\Model;

class UserCouponModel extends Model
{
    const NOTIFY_AHEAD_TIME = 24*60*60;
    public function getExpireCouponList()
    {
        //$where['user_coupon_end_time'] = array('EGT',date('Y-m-d H:i:s',time()+60*60));
        $where['user_coupon_end_time'] = array('ELT',date('Y-m-d H:i:s',time()+self::NOTIFY_AHEAD_TIME));
        $where['user_coupon_status'] = 3;
        $where['user_coupon_balance'] = array('GT',0);
        return $this->db(1,C('READ_DB'),true)->field('user_coupon_id,uid')->where($where)->select();
    }
}