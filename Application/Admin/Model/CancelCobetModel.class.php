<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-3
 * @author tww <merry2014@vip.qq.com>
 */
class CancelCobetModel extends Model{

    protected $_auto = array(
        array('cancel_modifytime', 'getCurrentTime', self::MODEL_UPDATE, 'function')
    );

	public function getStatusFieldName(){
		return 'cancel_cobet_status';
	}

	public function getInfoById($id){
	    $where['cancel_cobet_id'] = $id;
	    return $this->where($where)->find();
    }

}