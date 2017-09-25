<?php 
namespace Home\Model;
use Think\Model;

class ChannelCouponModel extends Model {
    
    public function queryInfoByCode($coupon_code) {
        $map['cc_code'] = $coupon_code;
        return $this->where($map)->find();
    }
    
    
    public function getCouponExchangeInfo($id) {
        $condition = array('ce_id'=>$id);
        return $this->where($condition)
                    ->find();
    }
    
    
    public function saveExchangeStatus($id, $uid) {
        $condition = array('ce_id'=>$id);
        $data = array(  'ce_status' => C('COUPON_EXCHANGE_STATUS.RECEIVE'),
                        'uid' => $uid,
                        'ce_exchange_time' => getCurrentTime() );
        return $this->where($condition)
                    ->save($data);
    }
    
}

?>