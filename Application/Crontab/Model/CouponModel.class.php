<?php

namespace Crontab\Model;

use Think\Model;

class CouponModel extends Model
{
	public function getCouponList()
	{
		$where['coupon_is_sell'] = 1;
		$where['coupon_status'] = 1;
		return $this->where($where)->getField('coupon_id', true);
	}
}