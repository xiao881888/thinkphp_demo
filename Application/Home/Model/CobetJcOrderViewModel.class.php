<?php
namespace Home\Model;
use Think\Model\ViewModel;
/**
 * @date 2015-1-13
 * @author tww <merry2014@vip.qq.com>
 */
class CobetJcOrderViewModel extends ViewModel{
	protected $viewFields = array(
			'myOrder'=>array(
					'order_id' 					=> 'id',
					'follow_bet_id',
					'order_sku' 				=> 'sku',
					'order_type' 			=> 'type',
					'order_status',
					'order_winnings_bonus' 		=> 'winnings_bonus',
					'order_winnings_status' 	=> 'winnings_status',
					'order_total_amount' 		=> 'total_amount',
					'order_multiple' 			=> 'multiple',
					'order_create_time' 		=> 'buying_time',
					'order_plus_award_amount' 		=> 'order_plus_award_amount',
					'current_follow_times',
					'order_winnings_status',
			        'uid', 
					'order_distribute_status',
					'_table' => '__COBET_ORDER__'),
			'JcSchedule'=>array(
					'schedule_issue_no' 	=> 'issue_no',
					'schedule_id'			=> 'issue_id',
					'schedule_prize_result' => 'prize_num',
					'schedule_end_time' 	=> 'prize_time',
					'_on' => 'myOrder.issue_id = JcSchedule.schedule_id'),
			'Lottery'=>array(   
					'lottery_name',
					'lottery_image',
					'lottery_id',
					'_on' => 'myOrder.lottery_id = Lottery.lottery_id'),
	);
	
	public function getOrderInfoByOrderId($order_id){
		$where = array();
		$where['order_id'] = $order_id;
		return $this->where($where)->find();
	}
}