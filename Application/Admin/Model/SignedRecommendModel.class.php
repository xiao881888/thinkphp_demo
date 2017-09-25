<?php 
namespace Admin\Model;
use Think\Model;

class SignedRecommendModel extends IntegralBaseModel {

    protected $_auto = array(
        array('sr_createtime', 'getCurrentTime', self::MODEL_INSERT, 'function'),
        array('sr_modifytime', 'getCurrentTime', self::MODEL_UPDATE, 'function')
    );

    public function getStatusFieldName(){
        return 'sr_status';
    }
}



