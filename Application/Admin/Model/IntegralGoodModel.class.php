<?php 
namespace Admin\Model;
use Think\Model;

class IntegralGoodModel extends IntegralBaseModel {

    protected $_auto = array(
        array('ig_createtime', 'getCurrentTime', self::MODEL_INSERT, 'function'),
        array('ig_modifytime', 'getCurrentTime', self::MODEL_UPDATE, 'function')
    );

    public function getStatusFieldName(){
        return 'ig_status';
    }
}



