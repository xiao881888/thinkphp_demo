<?php 
namespace Integral\Model;
use Think\Model;

class VipLevelModel extends Model {

    const ENABLE_STATUS = 1;

    public function getVipLevelId($exp){
        $level = 1;
        $vip_level_list = $this->where(array('vip_level_status'=>self::ENABLE_STATUS))->order('vip_level_grade')->select();
        foreach($vip_level_list as $vip_level_info){
            if($exp < $vip_level_info['vip_level_min_exp']){
                $level = $vip_level_info['vip_level_grade'];
                break;
            }
        }
        return $level;
    }

    public function getVipLevelInfo($id){
        $where['vip_level_id'] = $id;
        return $this->where($where)->find();
    }

    public function getNextVipLevelInfo($id){
        $vip_level_info = $this->getVipLevelInfo($id);
        if($vip_level_info['vip_level_grade'] == C('MAX_USER_LEVEL_GRADE')){
            return $vip_level_info;
        }
        $where['vip_level_grade'] = array('eq',($vip_level_info['vip_level_grade']+1));
        $where['vip_level_status'] = self::ENABLE_STATUS;
        return $this->where($where)->find();
    }


    public function getPreVipLevelInfo($id){
        $vip_level_info = $this->getVipLevelInfo($id);
        $where['vip_level_grade'] = array('eq',($vip_level_info['vip_level_grade']-1));
        $where['vip_level_status'] = self::ENABLE_STATUS;
        return $this->where($where)->find();
    }

    public function getIntegralOrderPrecent($id){
        $where['vip_level_id'] = $id;
        return $this->where($where)->getField('vip_level_integral_order_precent');
    }

}



