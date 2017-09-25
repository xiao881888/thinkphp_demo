<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-29
 * @author tww <merry2014@vip.qq.com>
 */
abstract class TicketModel extends Model{
	
	public function tickets($id){
		$where = array();
		$where['order_id'] = $id;
		return $this->where($where)->select();
	}
	
	public function getTicketInfos($order_ids){
		$where = array();
		$where['order_id'] 	= array('IN', $order_ids);
		$result = $this->where($where)->select();
		
		$format_result = array();
		foreach ($result as $v){
			$key = $v['order_id'].'_'.$v['ticket_seq'];
			$format_result[$key] = $v;
		}
		return $format_result;
	}
}