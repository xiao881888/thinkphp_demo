<?php 
namespace Home\Model;
use Think\Model;

class CouponExchangeModel extends Model {
    
    public function checkExchangeValid($id) {
        $couponInfo = $this->getCouponExchangeInfo($id);
        $statusLegal = ( $couponInfo['ce_status'] == C('COUPON_EXCHANGE_STATUS.NO_RECEIVE') );
        $inExpireTime = ( strtotime($couponInfo['ce_end_time']) > time() );
        return ($statusLegal && $inExpireTime);
    }
    
    
    public function getCouponExchangeId($couponCode) {
        $condition = array('ce_exchange_code'=>$couponCode);
        return $this->where($condition)
                    ->getField('ce_id');
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

    public function getExchangeCouponInfo($id) {
        $coupon_exchange_info = $this->getCouponExchangeInfo($id);
        $coupon_id = $coupon_exchange_info['coupon_id'];
        return  D('Coupon')->getCouponInfo($coupon_id);
    }
    
}

?>