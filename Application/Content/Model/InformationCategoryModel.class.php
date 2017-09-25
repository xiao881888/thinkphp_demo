<?php
namespace Content\Model;
use Think\Model;
/**
 * @date 2014-12-10
 * @author tww <merry2014@vip.qq.com>
 */
class InformationCategoryModel extends Model{
	public function getCategoryList(){
		$where = array();
		$where['information_category_status'] = 1;
		$list = $this->where($where)->order('information_category_order desc')->select();
		$result = array();
		foreach ($list as $k=>$v){
			$result[$v['information_category_id']] = $v;
		}
		return $result;
	}
}