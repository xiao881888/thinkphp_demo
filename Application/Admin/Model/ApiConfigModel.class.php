<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-24
 * @author tww <merry2014@vip.qq.com>
 */
class ApiConfigModel extends Model{
	public function getStatusFieldName(){
		return 'ac_status';
	}
}