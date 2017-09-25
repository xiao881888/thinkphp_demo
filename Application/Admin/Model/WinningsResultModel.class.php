<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-16
 * @author tww <merry2014@vip.qq.com>
 */
class WinningsResultModel extends Model{
	public function getBigWinings($order_ids){
		$where = array();
		$where['order_id'] = array('IN', $order_ids);
		$result = $this->where($where)->select();
		
		$format_result = array();
		foreach ($result as $v){
			$key = $v['order_id'].'_'.$v['ticket_seq'];
			$format_result[$key] = $v;
		}
		return $format_result;
	}
	
	public function getBigWiningsOrderIds($issue_id){
		$where = array();
		$where['wr_is_big'] = 1;
		$where['issue_id']	= $issue_id;
		return $this->where($where)->group('order_id')->getField('order_id', true);
	}
	
	public function getWinningsResult(){
		return $this->select();
	}
}