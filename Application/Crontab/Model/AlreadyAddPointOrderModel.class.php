<?php

namespace Crontab\Model;

use Think\Model;

class AlreadyAddPointOrderModel extends Model
{
	public function getAlreadyAddPointOrder($firstCurrDate, $lastCurrDate)
	{
		$where['aapo_order_createtime'] = array(array('between', array($firstCurrDate,$lastCurrDate)));
		return $this->where($where)->getField('aapo_order_id', true);
	}
}