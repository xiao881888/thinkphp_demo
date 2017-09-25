<?php 
namespace Admin\Model;
use Think\Model;

class IntegralUserModel extends IntegralBaseModel {

    protected $tableName        =   'user';

    public function getUserListByVipLevelId($vip_level_id){
        $where['vip_level_id'] = $vip_level_id;
        return $this->where($where)->getField('uid',true);
    }

}



