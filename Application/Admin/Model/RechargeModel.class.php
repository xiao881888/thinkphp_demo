<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-9
 * @author tww <merry2014@vip.qq.com>
 */
class RechargeModel extends Model{
	public function getReportData(){
		$field = array('recharge_create_time','recharge_amount');
		$where = array();
		$where['recharge_status'] = RECHARGE_STATUS_TOACCOUNT;
		$order = 'recharge_create_time ASC';
		return $this->field($field)->where($where)->order($order)->select();
	}

	public function sumRechargeMoneyByDate($start_date, $end_date){
		$sql = "SELECT DATE_FORMAT(recharge_receive_time, '%Y-%m-%d') `day`, sum(recharge_amount)  `s` FROM ".$this->getTableName()." WHERE recharge_status = ".RECHARGE_STATUS_TOACCOUNT." AND recharge_receive_time >= '{$start_date}' AND recharge_receive_time <= '{$end_date}' Group By `day`";

		return $this->query($sql);
	}

}