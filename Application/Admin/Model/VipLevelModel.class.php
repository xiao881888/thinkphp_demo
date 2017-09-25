<?php 
namespace Admin\Model;
use Think\Model;

class VipLevelModel extends IntegralBaseModel {

    protected $_auto = array(
        array('vip_level_createtime', 'getCurrentTime', self::MODEL_INSERT, 'function'),
        array('vip_level_modifytime', 'getCurrentTime', self::MODEL_UPDATE, 'function')
    );

    public function getStatusFieldName(){
        return 'vip_level_status';
    }

    public function getVipLevelNameById($vip_level_id){
        return $this->where(array('vip_level_id'=>$vip_level_id))->getField('vip_level_name');
    }

    public function getVipLevelList(){
        return $this->field('vip_level_id,vip_level_name')->select();
    }

}



