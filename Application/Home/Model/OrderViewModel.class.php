<?php
namespace Home\Model;
use Think\Model\ViewModel;
/**
 * @date 2014-11-19
 * @author tww <merry2014@vip.qq.com>
 */
class OrderViewModel extends ViewModel{
	protected $viewFields = array(
		'myOrder'=>array(
				 'order_id' 				=> 'id',
		         'follow_bet_id', 
				 'order_sku' 				=> 'sku',
		         'order_status', 
				 'order_winnings_bonus' 	=> 'winnings_bonus',
				 'order_winnings_status' 	=> 'winnings_status',
				 'order_total_amount' 		=> 'total_amount',
				 'order_plus_award_amount' 	=> 'order_plus_award_amount',
				 'order_multiple' 			=> 'multiple',
				 'order_create_time' 		=> 'buying_time',
				 'current_follow_times',
				 'order_winnings_status',
				 'order_refund_amount',
                 'order_reduce_consumption',
		         'uid', 
		         'order_type'				=> 'type', 
				 'order_distribute_status',
				 '_table' => '__ORDER__'),
		'Issue'=>array(
				 'issue_no',
		         'issue_id',
		         'issue_prize_number' 	=> 'prize_num',
				 'issue_prize_time' 	=> 'prize_time',
				 'issue_end_time' 		=> 'official_prize_time',
				 '_on' => 'myOrder.issue_id = Issue.issue_id'),
		'Lottery'=>array(   'lottery_name',
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