<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-20
 * @author tww <merry2014@vip.qq.com>
 */
class UserAccountLogModel extends Model{
	public function addLog($uid, $type, $amount, $frozen_amount, $balance, $frozen_balance, $op_uid, $remark=''){
		$account_log = array();
		$account_log['uid'] 				= $uid;
		$account_log['ual_type'] 			= $type;
		$account_log['ual_amount'] 			= $amount;
		$account_log['ual_frozen_amount']	= $frozen_amount;
		$account_log['ual_balance'] 		= $balance;
		$account_log['ual_frozen_balance'] 	= $frozen_balance;
		$account_log['operator_id'] 		= $op_uid;
		$account_log['ual_create_time'] 	= curr_date();
		$account_log['ual_remark'] 			= $remark;
		return $this->add($account_log);
	}
	
	public function getInfo($uid){
		$where = array();
		$where['uid'] = $uid;
		return $this->where($where)->select();
	}

	public function addRechargeAccountLog($recharge_data, $user_account_data, $operator=0){
		$account_log = array();
		$account_log['uid'] 				= $recharge_data['uid'];
		$account_log['ual_type'] 			= ACCOUNT_OPERATOR_TYPE_RECHARGE;
		$account_log['ual_amount'] 			= $recharge_data['recharge_amount'];
		$account_log['ual_frozen_amount'] 	= 0;
		$account_log['ual_balance'] 		= $user_account_data['user_account_balance'];
		$account_log['ual_frozen_balance'] 	= $user_account_data['user_account_frozen_balance'];
		$account_log['operator_id'] 		= $operator;
		$account_log['ual_create_time'] 	= getCurrentTime();
		$account_log['ual_remark'] 			= $recharge_data['recharge_id'];

		return $this->add($account_log);
	}
}