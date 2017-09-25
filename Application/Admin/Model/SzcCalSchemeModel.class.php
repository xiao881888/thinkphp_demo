<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2015-3-3
 * @author tww <merry2014@vip.qq.com>
 */
class SzcCalSchemeModel extends Model{
	public function getSchemes($lottery_id){
		$where = array();
		$where['lottery_id'] = $lottery_id;
		return $this->where($where)->select();
	}
	
	public function changeCurrStatus($lottery_id, $scheme_id){
		$where = array();
		$where['lottery_id'] = $lottery_id;
		$this->where($where)->setField('scheme_status', 0);
		
		$where2 = array();
		$where2['scheme_id'] = $scheme_id;
		return $this->where($where2)->setField('scheme_status', 1);
	}
}