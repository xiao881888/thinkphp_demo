<?php 
namespace H5\Model;
use Think\Model;
use H5\Util\RandomStringGenerator;

class H5BetLogModel extends Model {


    public function addLog($pre_bet_id,$uid,$order_id,$oder_info,$bet_info,$product_name = 'YQDS')
    {
        return $this->add(array(
            'pre_bet_id' => $pre_bet_id,
            'uid' => $uid,
            'order_id' => $order_id,
            'lottery_id' => $oder_info['lottery_id'],
            'log_order_info' => json_encode($oder_info),
            'log_bet_info' => json_encode($bet_info),
            'product_name' => $product_name,
            'bet_money' => $oder_info['total_amount'],
            'log_createtime' => date('Y-m-d H:i:s'),
        ));
    }

}



