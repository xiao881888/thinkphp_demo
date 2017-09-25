<?php 
namespace Admin\Model;
use Think\Model;

class IssueSwitchModel extends Model {

    protected $_auto = array(
        array('is_createtime', 'getCurrentTime', self::MODEL_INSERT, 'function'),
        array('is_modifytime', 'getCurrentTime', self::MODEL_UPDATE, 'function')
    );

    public function getStatusFieldName(){
        return 'is_status';
    }

}



