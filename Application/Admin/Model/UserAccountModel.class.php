<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-10
 * @author tww <merry2014@vip.qq.com>
 */
class UserAccountModel extends Model{
	
	protected $_validate = array(
		array('uid', 'require', '用户必须！'),
		array('user_account_balance', 'is_numeric', '金额必须是数字', self::EXISTS_VALIDATE, 'function')
	);
	
	public function recharge($uid, $money){
		$where = array();
		$where['uid'] = $uid;
		return $this->where($where)->setInc('user_account_balance' , $money);
	}
	
	public function getUserAccountInfo($uid){
		$where = array();
		$where['uid'] = $uid;
		return $this->where($where)->find();
	}
	
	public function deductFrozenBalance($uid, $money, $withdraw_id){
		$where = array();
		$where['uid'] = $uid;
		$deduct_result = $this->where($where)->setDec('user_account_frozen_balance', $money);
		if($deduct_result){
			$op_type 			= ACCOUNT_OPERATOR_TYPE_DRAW;
			$user_accountinfo 	= $this->getUserAccountInfo($uid);
			$balance 			= $user_accountinfo['user_account_balance'];
			$frozen_balance 	= $user_accountinfo['user_account_frozen_balance'];
			$op_uid 			= get_curr_uid();
			$amount				= 0;
			$frozen_amount		= -($money);
			
			$log_result = D('UserAccountLog')->addLog($uid, $op_type, $amount, $frozen_amount, $balance, $frozen_balance, $op_uid, $withdraw_id);
			return $log_result ? true : false;
		}else{
			return false;
		}
	}
	
	public function unfreeze($uid, $money, $withdraw_id){
		$where = array();
		$where['uid'] = $uid;

		$unfreeze = $this->where($where)->setDec('user_account_frozen_balance', $money);
		
		if($unfreeze){
			$add_account_balance = $this->where($where)->setInc('user_account_balance', $money);
			if($add_account_balance){
				$op_type 			= ACCOUNT_OPERATOR_TYPE_REFUSEDRAW;
				$user_accountinfo 	= $this->getUserAccountInfo($uid);
				$balance 			= $user_accountinfo['user_account_balance'];
				$frozen_balance 	= $user_accountinfo['user_account_frozen_balance'];
				$op_uid 			= get_curr_uid();
				$amount				= $money;
				$frozen_amount		= -($money);
				
				$log_result = D('UserAccountLog')->addLog($uid, $op_type, $amount, $frozen_amount, $balance, $frozen_balance, $op_uid, $withdraw_id);
				if($log_result){
					return true;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
}