<?php
namespace Home\Model;
use Think\Model\ViewModel;
/**
 * @date 2014-11-19
 * @author tww <merry2014@vip.qq.com>
 */
class CobetOrderViewModel extends ViewModel{

    const API_ORDERS_STATUS_WAITING 	= -1;
    const API_ORDERS_STATUS_WINNING 	= 1;
    const API_ORDERS_STATUS_NO_WINNING 	= -2;

	protected $viewFields = array(
		'CobetScheme'=>array(
				 'scheme_id',
		         'scheme_identity',
				 'scheme_serial_number',
		         'uid',
				 'order_id',
				 'cobet_order_id',
				 'lottery_id',
				 'scheme_createtime',
				 'scheme_bet_content',
				 'scheme_total_amount',
				 'scheme_total_unit',
				 'scheme_amount_per_unit',
				 'scheme_guarantee_unit',
                 'scheme_bought_unit',
		         'scheme_bought_rate',
		         'scheme_status',
				 'scheme_show_status',
                 'scheme_commission_rate',
                 'scheme_winning_status',
                 'scheme_winning_bonus',
                 'scheme_end_time',
                 'scheme_schedule_ids',
                 'scheme_history_record',
                 'scheme_history_data',
                 'scheme_issue_id',
                 'scheme_refund_amount',
                 'scheme_refund_unit',
                 'scheme_commission_amount',
                 '_type'=>'LEFT',
        ),
		'MyOrder'=>array(
				 'order_id',
		         'order_status',
		         'order_winnings_status',
                 'order_total_amount',
                 'order_refund_amount',
                 'order_distribute_status',
				 'order_type',
                '_table' => '__ORDER__',
				 '_on' => 'CobetScheme.order_id = MyOrder.order_id'),
	);

    public function getInfo($scheme_id){
        $map['scheme_id'] = $scheme_id;
        return $this->where($map)->find();
    }

    public function getSchemeListBySchemeIds($scheme_ids,$offset = 0,$limit=10){
        $where['scheme_id'] = array('IN',$scheme_ids);
        return $this->where($where)->order('scheme_createtime DESC')->limit($offset,$limit)->select();
    }
}