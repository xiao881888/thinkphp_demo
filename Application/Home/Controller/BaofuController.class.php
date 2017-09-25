<?php
namespace Home\Controller;
use Think\Controller;
class BaofuController extends Controller{

	public function recharge(){
		$id = I('id');
		$rd = I('rd');
		$md = I('md');

		if (md5($id.$rd.RECHARGE_URL_MD5_SALT) != $md) {
			echo '异常访问！';exit;
		}

		$recharge_info = D('Recharge')->getRechargeInfo($id);
		if (empty($recharge_info)) {
			echo '异常访问！';exit;
		}

		$member_id = C('baofu_wap_config.member_id');
		$terminal_id = C('baofu_wap_config.terminal_id');
		$interface_version = C('baofu_wap_config.interface_version');

		$this->assign('member_id', $member_id);
		$this->assign('terminal_id', $terminal_id);
		$this->assign('interface_version', $interface_version);

		$pay_url		= C('baofu_wap_config.pay_url');
		$pay_id 		= 'all';
		$trade_date 	= date('YmdHis');
		$trans_id 		= $recharge_info['recharge_sku'];
		$order_money 	= $recharge_info['recharge_amount'] * 100;
		$page_url 		= U('Home/Baofu/complete@'.$_SERVER['HTTP_HOST'], array(), '', true);
		$return_url 	= U('Home/Baofu/notify@'.$_SERVER['HTTP_HOST'], array(), '', true);
		$notify_type 	= 1;
		$key_type 		= 1;
		
		$sep = '|';
		$signature = md5($member_id.$sep.$pay_id.$sep.$trade_date.$sep.$trans_id.$sep.$order_money.$sep.$page_url.$sep.$return_url.$sep.$notify_type.$sep.C('baofu_wap_config.signature_key'));
		ApiLog('page_url:'.$page_url, 'hgy_test');
		ApiLog('return_url'.$return_url, 'hgy_test');
		ApiLog('param:'.$member_id.$sep.$pay_id.$sep.$trade_date.$sep.$trans_id.$sep.$order_money.$sep.$page_url.$sep.$return_url.$sep.$notify_type.$sep.C('baofu_wap_config.signature_key'), 'hgy_test');
		ApiLog('signature:'.$signature, 'hgy_test');
		ApiLog('pay_url'.$pay_url, 'hgy_test');
		$this->assign('pay_url', 	$pay_url);
		$this->assign('pay_id', 	$pay_id);
		$this->assign('trade_date', $trade_date);
		$this->assign('trans_id', 	$trans_id);
		$this->assign('order_money', $order_money);
		$this->assign('page_url', 	$page_url);
		$this->assign('return_url', $return_url);
		$this->assign('notify_type', $notify_type);
		$this->assign('key_type', 	$key_type);
		$this->assign('signature', 	$signature);

		$this->display();
	}

	public function complete(){
		$result = I('Result');
		$result_desc = I('ResultDesc');

		$this->assign('result', $result);
		$this->assign('result_desc', $result_desc);

		$this->display();
	}

	public function notify(){
		$result 		 = I('Result');
		$result_desc 	 = I('ResultDesc');
		$fact_money 	 = I('FactMoney');
		$additional_info = I('AdditionalInfo');
		$success_time 	 = I('SuccTime');
		$md5_signature 	 = I('Md5Sign');
		$member_id 		 = I('MemberID');
		$terminal_id 	 = I('TerminalID');
		$trans_id 		 = I('TransID');
		$bank_id 		 = I('BankID');

		$sep = '~|~';

		$sign = md5('MemberID='.$member_id.$sep.'TerminalID='.$terminal_id.$sep.'TransID='.$trans_id.$sep.'Result='.$result.$sep.'ResultDesc='.$result_desc.$sep.'FactMoney='.$fact_money.$sep.'AdditionalInfo='.$additional_info.$sep.'SuccTime='.$success_time.$sep.'Md5Sign='.C('baofu_wap_config.signature_key'));

		if ($sign == $md5_signature) {
			$recharge_info = D('Recharge')->getRechargeInfoBySku($trans_id);
			if (!empty($recharge_info)) {
				if ($recharge_info['recharge_status'] == C('RECHARGE_STATUS.PAID') ||$recharge_info['recharge_status'] == C('RECHARGE_STATUS.FAIL')) {
					$this->_sendMsgAndExit('OK');
				}

				if ($result == 1) {
					$trans_result = false;

					M()->startTrans();
	
					$money = $fact_money/100;
					
					$increse_result = D('UserAccount')->increaseMoney($recharge_info['uid'], $money, $recharge_info['recharge_id'], C('USER_ACCOUNT_LOG_TYPE.RECHARGE'), true);
					
					if ($increse_result) {
						$recharge_data['recharge_receive_time'] = getCurrentTime();
						$recharge_data['recharge_status'] 		= C('RECHARGE_STATUS.PAID');
						$recharge_map['recharge_id'] 			= $recharge_info['recharge_id'];
						$recharge_result = D('Recharge')->where($recharge_map)->save($recharge_data);

						if ($recharge_result) {
							$trans_result = true;
						}

					}

					if ($trans_result) {
						M()->commit();
						A('UserCoupon')->rewardCouponForFirstRecharge($recharge_info['recharge_id']);
						$this->_sendMsgAndExit('OK');
					} else {
						M()->rollback();
					}
				}
			}
		}
	}

	private function _verifyNotify(){

	}

	private function _sendMsgAndExit($msg){
		echo $msg;
		exit;
	}
}