<?php 
namespace Admin\Model;
use Think\Model;

class VipGiftsLogModel extends IntegralBaseModel {

    protected $_auto = array(
        array('vgl_createtime', 'getCurrentTime', self::MODEL_INSERT, 'function')
    );

}



