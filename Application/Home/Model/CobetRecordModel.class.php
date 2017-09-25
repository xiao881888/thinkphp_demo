<?php

namespace Home\Model;

use Think\Model;

class CobetRecordModel extends Model{

	public function getCobetRecordList($scheme_id){
		$map['scheme_id'] = $scheme_id;
		return $this->where($map)->find();
	}

	public function addRecord($uid, $scheme_id, $type, $bought_unit, $bought_amount){
		$record_data['scheme_id'] = $scheme_id;
		$record_data['uid'] = $uid;
		$record_data['type'] = $type;
		$record_data['cobet_record_bought_unit'] = $bought_unit;
		$record_data['cobet_record_bought_amount'] = $bought_amount;
		$record_data['cobet_record_createtime'] = getCurrentTime();
		return $this->add($record_data);
	}

    public function getCobetAmountBySchemeId($scheme_id){
        $where['scheme_id'] = $scheme_id;
        $where['type'] = array('IN',array(C('COBET_TYPE.BOUGHT'),C('COBET_TYPE.GUARANTEE')));
        return $this->where($where)->sum('record_bought_amount');
    }

    public function getBoughtCount($scheme_id){
        $where['scheme_id'] = $scheme_id;
        $where['type'] = C('COBET_TYPE.BOUGHT');
        $list = $this->where($where)->group('uid')->select();
        return count($list);
    }

    public function isBought($scheme_id,$uid){
        $where['scheme_id'] = $scheme_id;
        $where['uid'] = $uid;
        $where['type'] = C('COBET_TYPE.BOUGHT');
        return $this->where($where)->find();
    }

    public function getCobetUnitCountBySchemeId($scheme_id,$uid = 0){
        $where['scheme_id'] = $scheme_id;
        if($uid){
            $where['uid'] = $uid;
        }
        $where['type'] = array('IN',array(C('COBET_TYPE.BOUGHT'),C('COBET_TYPE.GUARANTEE')));
        return $this->where($where)->sum('record_bought_unit');
    }

    public function getBoughtListBySchemeId($scheme_id,$type = 0,$offset=0,$limit=10){
        if($type){
            $where['type'] = array('IN',$type);
        }else{
            $where['type'] = array('IN',array(C('COBET_TYPE.BOUGHT'),C('COBET_TYPE.GUARANTEE')));
        }
        $where['scheme_id'] = $scheme_id;
        return $this->where($where)->limit($offset,$limit)->select();
    }


    public function getRecordListBySchemeId($scheme_id){
        $where['scheme_id'] = $scheme_id;
        return $this->where($where)->select();
    }

    public function getSchemeIdsByUid($uid){
        $where['uid'] = $uid;
        return $this->where($where)->group('scheme_id')->getField('scheme_id',true);
    }

    public function getUserWinningBonus($uid,$scheme_id){
        $where['scheme_id'] = $scheme_id;
        $where['uid'] = $uid;
        return $this->where($where)->sum('record_winning_bonus');
    }

    public function getEnsureUsed($scheme_id){
        $where['scheme_id'] = $scheme_id;
        $where['type'] = C('COBET_TYPE.GUARANTEE');
        return $this->where($where)->getField('record_bought_unit');
    }

    public function getUserFailureAmount($uid,$scheme_id){
        $where['scheme_id'] = $scheme_id;
        $where['uid'] = $uid;
        $where['type'] = array('IN',array(C('COBET_TYPE.BOUGHT'),C('COBET_TYPE.GUARANTEE')));
        return $this->where($where)->sum('record_refund_amount');
    }

}