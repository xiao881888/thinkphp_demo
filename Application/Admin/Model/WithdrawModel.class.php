<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-4
 * @author tww <merry2014@vip.qq.com>
 */
class WithdrawModel extends Model{
	public function getStatusFieldName(){
		return 'withdraw_status';
	}
	
	public function getReportData(){
		$where = array();
		$where['withdraw_status'] = WITHDRAW_STATUS_PAID;
		$field = array('withdraw_request_time', 'withdraw_amount');
		$order = 'withdraw_request_time ASC';
		return $this->field($field)->where($where)->order($order)->select();
	}
	
	public function getWithdrawInfo($id){
		$where = array();
		$where['withdraw_id'] = $id;
		return $this->where($where)->find();
	}

	public function sumWithdrawMoneyByDate($start_date, $end_date){
		$sql = "SELECT DATE_FORMAT(withdraw_pay_time, '%Y-%m-%d') `day`, sum(withdraw_amount)  `s` FROM ".$this->getTableName()." WHERE withdraw_pay_time >= '{$start_date}' AND withdraw_pay_time <= '{$end_date}' Group By `day`";

		return $this->query($sql);
	}
}