<?php
namespace Admin\Model;
use Think\Model;

class FullReducedCouponConfigModel extends Model{
    public function getStatusFieldName(){
        return 'frcc_status';
    }

    public function getInfoById($id){
        $where['frcc_id'] = $id;
        return $this->where($where)->find();
    }
}