<?php 
namespace Admin\Model;
use Think\Model;

class VipGiftsModel extends IntegralBaseModel {

    protected $_auto = array(
        array('vg_createtime', 'getCurrentTime', self::MODEL_INSERT, 'function'),
        array('vg_modifytime', 'getCurrentTime', self::MODEL_UPDATE, 'function')
    );

    public function saveVgContent($vg_id,$vip_content){
        $this->where(array('vg_id'=>$vg_id))->save(array('vg_content'=>$vip_content));
    }

    public function getVgInfoById($vg_id){
        return $this->where(array('vg_id'=>$vg_id))->find();
    }

    public function updateSuccessStatus($vg_id){
        $this->where(array('vg_id'=>$vg_id))->save(array('vg_status'=>2));
    }

    public function updateSendStatus($vg_id){
        $this->where(array('vg_id'=>$vg_id))->save(array('vg_status'=>1));
    }

    public function updatePushTime($vg_id){
        $this->where(array('vg_id'=>$vg_id))->save(array('vg_sendtime'=>getCurrentTime()));
    }


}



