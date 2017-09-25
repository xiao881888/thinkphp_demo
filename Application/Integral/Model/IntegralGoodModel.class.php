<?php 
namespace Integral\Model;
use Think\Model;

class IntegralGoodModel extends Model {
    const ENABLE_STATUS = 1;
    public function getIntegralGoodsList(){
        $where['ig_status'] = self::ENABLE_STATUS;
        $where['ig_good_num'] = array('egt',0);
        return $this->where($where)->select();
    }

    public function getIntegralGoodsInfo($id){
        $where['ig_id'] = $id;
        return $this->where($where)->find();
    }

    public function reduceIntegralGoodNum($id){
        $where['ig_id'] = $id;
        return $this->where($where)->setDec('ig_good_num',1);
    }

}



