<?php
namespace Content\Model;
use Think\Model;
/**
 * @date 2014-12-12
 * @author tww <merry2014@vip.qq.com>
 */
class ConfigModel extends Model{
	public function getInfoByName($name){
		$where = array();
		$where['name'] = $name;
		return $this->where($where)->find();
	}
}