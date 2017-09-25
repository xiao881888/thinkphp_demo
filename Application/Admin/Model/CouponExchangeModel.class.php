<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-6
 * @author tww <merry2014@vip.qq.com>
 */
class CouponExchangeModel extends Model{
public function getStatusFieldName(){
		return 'ce_status';
	}
}