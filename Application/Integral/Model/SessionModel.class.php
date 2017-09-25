<?php 
namespace Integral\Model;
use Think\Model;

class SessionModel extends TigerBaseModel {

    public function getTigerUid($session) {
        $condition = array('session_code'=>$session);
        return $this->where($condition)
            ->getField('uid');
    }

}



