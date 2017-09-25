<?php 
namespace Home\Model;
use Think\Model;

class RechargeModel extends Model {

    public function addRecharge($uid, $platformId, $money, $remark, $sku, $source) {
        $data = array(
            'uid' => $uid,
            'recharge_channel_id' => $platformId,
            'recharge_create_time' => getCurrentTime(),
            'recharge_status' => C('RECHARGE_STATUS.UNPAID'),
            'recharge_amount' => $money,
            'recharge_remark' => $remark,
            'recharge_sku' => $sku,
            'recharge_source' => $source,
        );
        return $this->add($data);
    }
    
    
    public function getRechargeInfo($rechargeId) {
        $condition = array('recharge_id'=>$rechargeId);
        return $this->where($condition)
                    ->find();
    }
    
    
    public function getRechargeInfoBySku($rechargeSku) {
        $condition = array('recharge_sku'=>$rechargeSku);
        return $this->where($condition)
                    ->find();
    }
    
    
    public function saveRechargeInfo($rechargeId, $status, $rechargeNo='') {
        $condition = array('recharge_id'=>$rechargeId);
        $data = array(  'recharge_status' => $status,
                        'recharge_receive_time' => getCurrentTime(),
                        'recharge_no' => $rechargeNo);
        return $this ->where($condition)
                            ->save($data);

    }

    public function getRechargeAmountByUidAndTime($uid,$start_time,$end_time){
        $condition['uid'] = $uid;
        $condition['recharge_receive_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $condition['recharge_status'] = 1;
        return $this->where($condition)->sum('recharge_amount');
    }

    public function getRechargeInfoBySkuOfLock($rechargeSku) {
        $condition = array('recharge_sku'=>$rechargeSku);
        return $this->lock(true)->where($condition)
            ->find();
    }

    public function getRechargeIdBySku($rechargeSku) {
        $condition = array('recharge_sku'=>$rechargeSku);
        return $this->where($condition)
            ->getField('recharge_id');
    }

    public function getRechargeInfoOfLock($rechargeId) {
        $condition = array('recharge_id'=>$rechargeId);
        return $this->lock(true)->where($condition)
            ->find();
    }
    
    
}



?>