<?php 
namespace Admin\Model;
use Think\Model;

class LotteryPackageModel extends Model {

    protected $_auto = array(
        array('lp_createtime', 'getCurrentTime', self::MODEL_INSERT, 'function'),
        array('lp_modifytime', 'getCurrentTime', self::MODEL_UPDATE, 'function')
    );

    public function getStatusFieldName(){
        return 'lp_status';
    }

}



