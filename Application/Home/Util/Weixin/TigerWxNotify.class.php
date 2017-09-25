<?php
ini_set('date.timezone', 'Asia/Shanghai');

require_once "WxPayApi.class.php";
require_once 'WxPayNotify.class.php';

class TigerWxNotify extends WxPayNotify{
	// 查询订单
	public function Queryorder($transaction_id){
		$input = new \WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = \WxPayApi::orderQuery($input);
		if (array_key_exists("return_code", $result) && array_key_exists("result_code", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS") {
			return true;
		}
		return false;
	}
	
	// 重写回调处理函数
	public function NotifyProcess($data, &$msg){
		$notfiyOutput = array();
		
		if (!array_key_exists("transaction_id", $data)) {
			$msg = "输入参数不正确";
			return false;
		}
		// 查询订单，判断订单真实性
		if (!$this->Queryorder($data["transaction_id"])) {
			$msg = "订单查询失败";
			return false;
		}
		ApiLog('data:'.print_r($data,true),'wx');
		return $this->_updateNotifyData($data);
		
	}
	
	private function _updateNotifyData($wx_notify_data){
		$out_trade_no = $wx_notify_data['out_trade_no'];
		
		$map['wx_record_out_trade_no'] = $wx_notify_data['out_trade_no'];
		$record_info = D('WxRecord')->where($map)->find();
		if(empty($record_info)){
			ApiLog('no exist :'.$out_trade_no,'wx');
			$this->_sendFailAndExit();
		}
		if($record_info['wx_record_status']==1){
			$this->_sendSuccessAndExit();
		}
		$notify_data['wx_record_appid'] = $wx_notify_data['appid'];
		$notify_data['wx_record_mch_id'] = $wx_notify_data['mch_id'];
		$notify_data['wx_record_nonce_str'] = $wx_notify_data['nonce_str'];
		$notify_data['wx_record_sign'] = $wx_notify_data['sign'];
		$notify_data['wx_record_result_code'] = $wx_notify_data['result_code'];
		$notify_data['wx_record_openid'] = $wx_notify_data['openid'];
		$notify_data['wx_record_bank_type'] = $wx_notify_data['bank_type'];
		$notify_data['wx_record_cash_fee'] = $wx_notify_data['cash_fee'];
		$notify_data['wx_record_transaction_id'] = $wx_notify_data['transaction_id'];
		$notify_data['wx_record_time_end'] = $wx_notify_data['time_end'];
		$notify_data['wx_record_modifytime'] = date("Y-m-d H:i:s");
			
		if($wx_notify_data['device_info']){
			$notify_data['wx_record_device_info'] = $_POST['device_info'];
		}
		if($wx_notify_data['err_code']){
			$notify_data['wx_record_err_code'] = $_POST['err_code'];
		}
		if($wx_notify_data['err_code_des']){
			$notify_data['wx_record_err_code_des'] = $_POST['err_code_des'];
		}
		if($wx_notify_data['is_subscribe']){
			$notify_data['wx_record_is_subscribe'] = $_POST['is_subscribe'];
		}
		if($wx_notify_data['settlement_total_fee']){
			$notify_data['wx_record_settlement_total_fee'] = $_POST['settlement_total_fee'];
		}
		if($wx_notify_data['fee_type']){
			$notify_data['wx_record_fee_type'] = $_POST['fee_type'];
		}
		if($wx_notify_data['cash_fee_type']){
			$notify_data['wx_record_cash_fee_type'] = $_POST['cash_fee_type'];
		}
		if($wx_notify_data['coupon_fee']){
			$notify_data['wx_record_coupon_fee'] = $_POST['coupon_fee'];
		}
		if($wx_notify_data['coupon_count']){
			$notify_data['wx_record_coupon_count'] = $_POST['coupon_count'];
		}
		
		M()->startTrans();
		if($wx_notify_data['result_code']=='SUCCESS'){
			$money = $record_info['wx_record_total_fee']/100;
			$increse_result = D('UserAccount')->increaseMoney($record_info['uid'],$money,$record_info['recharge_id'],C('USER_ACCOUNT_LOG_TYPE.RECHARGE'),true);
			ApiLog('incre:'.$increse_result,'wx');
			if ($increse_result) {
				$recharge_data['recharge_receive_time'] = $_POST['notify_time'];
				$recharge_data['recharge_status'] = C('RECHARGE_STATUS.PAID');
				$recharge_data['recharge_no'] = $out_trade_no;
				$recharge_map['recharge_id'] = $record_info['recharge_id'];
				$recharge_result = D('Recharge')->where($recharge_map)->save($recharge_data);
			}
			$notify_data['wx_record_status'] = 1;
		}else{
			$notify_data['wx_record_status'] = 1;
				
		}
		
		$notify_map['wx_record_out_trade_no'] = $out_trade_no;
		$save_result = D('WxRecord')->where($notify_map)->save($notify_data);
			
		if($recharge_result && $save_result){
			M()->commit();
			A('UserCoupon')->rewardCouponForFirstRecharge($record_info['recharge_id']);
			return true;
		}else{
			M()->rollback();
			return false;
		}
	}
}

