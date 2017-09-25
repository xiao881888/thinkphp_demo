<?php

namespace Admin\Model;

use Think\Model;

class CobetRecordModel extends Model{

    public function getRecordListBySchemeId($scheme_id){
        $where['scheme_id'] = $scheme_id;
        return $this->where($where)->select();
    }

    public function getSchemeIdsByUid($uid){
        $where['uid'] = $uid;
        return $this->where($where)->getField('scheme_id',true);
    }

}