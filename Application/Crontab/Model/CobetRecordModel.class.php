<?php
namespace Crontab\Model;
use Think\Model;
class CobetRecordModel extends Model {

    public function getRecordListBySchemeId($scheme_id){
        $where['scheme_id'] = $scheme_id;
        $where['type'] = array('IN',array(C('COBET_TYPE.BOUGHT'),C('COBET_TYPE.GUARANTEE_FROZEN')));
        return $this->where($where)->select();
    }

    public function getSchemeStatusById($id){
        $where['scheme_id'] = $id;
        return $this->where($where)->getField('scheme_status');
    }

    public function changeSchemeStatusById($id,$status){
        $where['scheme_id'] = $id;
        return $this->where($where)->save(array('scheme_status' => $status));
    }

    public function getBoughtTotalAmount($id,$type = 'all'){
        $where['scheme_id'] = $id;
        if($type != 'all'){
            $where['type'] = array('IN',$type);
        }
        return $this->where($where)->sum('record_bought_amount');
    }

    public function getBoughtTotalUnit($id,$type = 'all'){
        $where['scheme_id'] = $id;
        if($type != 'all'){
            $where['type'] = array('IN',$type);
        }
        return $this->where($where)->sum('record_bought_unit');
    }

    public function addRecord($uid, $scheme_id, $type, $user_coupon_id,$user_coupon_consume_amount,$record_user_cash_amount,$bought_unit, $bought_amount,$record_status = 1){
        $record_data['scheme_id'] = $scheme_id;
        $record_data['uid'] = $uid;
        $record_data['type'] = $type;
        $record_data['user_coupon_id'] = $user_coupon_id;
        $record_data['record_user_coupon_consume_amount'] = $user_coupon_consume_amount;
        $record_data['record_user_cash_amount'] = $record_user_cash_amount;
        $record_data['record_bought_unit'] = $bought_unit;
        $record_data['record_bought_amount'] = $bought_amount;
        $record_data['record_createtime'] = getCurrentTime();
        $record_data['record_status'] = $record_status;
        return $this->add($record_data);
    }

    public function getGuaranteeFrozenInfoBySchemeId($scheme_id){
        $where['scheme_id'] = $scheme_id;
        $where['type'] = C('COBET_TYPE.GUARANTEE_FROZEN');
        return $this->where($where)->find();
    }

    public function saveRefundStatus($record_id,$refund_amount,$refund_unit,$refund_status){
        $where['record_id'] = $record_id;
        $data['record_refund_unit'] = $refund_unit;
        $data['record_status'] = $refund_status;
        $data['record_refund_amount'] = $refund_amount;
        return $this->where($where)->save($data);
    }
    
}