<?php 
namespace Integral\Model;
use Think\Model;

class VipGiftsModel extends Model {

    public function getVipGiftsInfoByLevelId($vip_level_id){
        $where['vip_level_id'] = $vip_level_id;
        $where['vg_status'] = array('neq',0);
        return $this->where($where)->find();
    }


}



