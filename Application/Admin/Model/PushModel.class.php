<?php
namespace Admin\Model;
use Think\Model;

class PushModel extends Model{
    
    protected $_validate = array(
    	array('uid', 'require', '接收用户id不能为空！')
    );

    protected $_auto = array(
    	array('push_createtime', 'getCurrentTime', self::MODEL_INSERT, 'function'),
    	array('push_modifytime', 'getCurrentTime', self::MODEL_UPDATE, 'function'),
    	array('push_paramers', 'buildParamers', self::MODEL_BOTH, 'callback')
    	);

    public function buildParamers(){
    	$paramers = array();
    	$paramers['order_id'] 		= I('refer_order_id');
    	$paramers['order_status'] 	= I('refer_order_status');
    	$paramers['lottery_id'] 	= I('refer_lottery_id');
    	$paramers['url'] 			= I('refer_url');

    	return json_encode($paramers);
    }

}