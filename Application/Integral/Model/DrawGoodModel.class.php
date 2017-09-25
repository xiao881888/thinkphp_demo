<?php 
namespace Integral\Model;
use Think\Model;

class DrawGoodModel extends Model {

    const ENABLE_STATUS = 1;

    public function getDrawGoodInfo($id){
        $where['dg_id'] = $id;
        return $this->where($where)->find();
    }

    public function getAllDrawGood(){
        $where['dg_status'] = self::ENABLE_STATUS;
        return $this->where($where)->select();
    }


}



