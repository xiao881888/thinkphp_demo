<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-4
 * @author tww <merry2014@vip.qq.com>
 */
class OrderModel extends Model{
	public function getLotteryId($order_id){
		$where = array();
		$where['order_id'] = $order_id;
		return $this->where($where)->getField('lottery_id');
	}
	
	public function getOrderInfos($order_ids){
		$where = array();
		$where['order_id'] = array('IN', $order_ids);
		$result =  $this->where($where)->select();	
		$format_result = array();
		foreach ($result as $v){
			$key = $v['order_id'];
			$format_result[$key] = $v;
		}
		return $format_result;
	}
	
	public function getSellOut($start_date, $end_date){
		$where = array();
		if($start_date && $end_date){
			$where['order_create_time'] = array('BETWEEN', array($start_date , $end_date));
		}else{
			if($start_date){
				$where['order_create_time'] = array('EGT', $start_date);
			}
			if($end_date){
				$where['order_create_time'] = array('ELT', $end_date);
			}
		}
		$where['order_status'] = ORDER_STATUS_OUTED;
		$field = array('lottery_id', 'issue_id', 'order_create_time', 'order_total_amount', 'user_coupon_amount');
		return $this->field($field)->where($where)->order('order_create_time ASC')->select();
	}
	
	public function getMaxCreateDate(){ 
		return $this->getField('Max(order_create_time)');
	}
	
	public function getMinCreateDate(){
		return $this->getField('Min(order_create_time)');
	}

	public function sumWinnerOrderByDate($start_date, $end_date){
		$sql = "SELECT DATE_FORMAT(order_create_time, '%Y-%m-%d') `day`, sum(order_winnings_bonus)  `s` FROM ".$this->getTableName()." WHERE order_create_time >= '{$start_date}' AND order_create_time <= '{$end_date}' Group By `day`";

		return $this->query($sql);
	}

	public function getUserConsume($uids){
		if (is_array($uids)) {
			$uids = implode(',', $uids);
		}

		$sql = "SELECT uid, sum(order_total_amount) s FROM ".$this->getTableName()." WHERE uid IN (".$uids.") AND order_status = 3 GROUP BY uid";

		$data = $this->query($sql);

		if (!empty($data)) {
			$data = reindexArr($data, 'uid');
		}

		return $data;
	}
}