<?php
namespace H5\Model;
use Think\Model;
/**
 * @date 2014-11-27
 * @author tww <merry2014@vip.qq.com>
 */
class LotteryModel extends Model{
	public function getLottery(){
		$where = array();
// 		$where['lottery_status'] = 1;
		$where['lottery_is_show'] = 1;
		return $this->where($where)
					->order('lottery_order_weight DESC')
					->select();
	}
	
	
	public function getLotteryInfo($lotteryId) {
	    $condition = array('lottery_id'=>$lotteryId);
	    
	    return $this   ->field('lottery_image, lottery_status, lottery_name, lottery_price, lottery_ahead_endtime')
	                   ->where($condition)
	                   ->find();
	}
	
	public function getLotteryMap(){
		return $this->getField('lottery_id, lottery_name, lottery_image');
	}
}