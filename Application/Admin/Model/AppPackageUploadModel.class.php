<?php 
namespace Admin\Model;
use Think\Model;

class AppPackageUploadModel extends Model {

    protected $_auto = array(
        array('apu_createtime', 'getCurrentTime', self::MODEL_INSERT, 'function'),
        array('apu_modifytime', 'getCurrentTime', self::MODEL_UPDATE, 'function')
    );

}



