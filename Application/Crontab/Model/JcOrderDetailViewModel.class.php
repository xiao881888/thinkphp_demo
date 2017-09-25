<?php

namespace Crontab\Model;

use Think\Model;

class JcOrderDetailViewModel extends Model\ViewModel
{

	public $viewFields = array(
		'myOrder'=>array(
			'order_id',
			'order_status',
			'uid',
			'_table' => '__ORDER__',
		),
		'JcOrderDetail'=>array(
			'jc_order_id' => 'order_id',
			'schedule_id',
			'winning_status',
			'_on'=>'myOrder.order_id=JcOrderDetail.order_id'
		),
	);

	private $_completeStatus = 3;
	private $_partiallyStatus = 8;

	public function getUsersByScheduleIds($schedule_ids)
	{
		$where['schedule_id'] = array('in',$schedule_ids);
		$where['order_status'] = array('in',array($this->_completeStatus,$this->_partiallyStatus));
		if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
			$uids = $this->distinct(true)->db(1,C('READ_DB'),true)->where($where)->getField('uid',true);
			$this->db(0);
		} else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
			$uids = $this->distinct(true)->where($where)->getField('uid',true);
		} else {
			$uids = $this->distinct(true)->where($where)->getField('uid',true);
		}
		return $uids;
	}
}