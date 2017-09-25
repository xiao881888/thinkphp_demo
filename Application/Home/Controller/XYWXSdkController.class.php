<?php
namespace Home\Controller;
use Think\Controller;

class XYWXSdkController extends Controller{

	public function receiveNotifyResult(){
		$xml = file_get_contents('php://input');
		ApiLog('notify result:'.print_r($xml, true), 'xywx_notify');
		import('@.Util.XYWX.XYWXHandler');
		$xywx_handler = new \XYWXHandler(C('XINGYE_BANK_WX_CONFIG'));
		$notify_data = $xywx_handler->parseNotifyData($xml);
		ApiLog('$notify_data:'.print_r($notify_data, true), 'xywx_notify');
		
		// die();
		
		// ApiLog('$notify_data:'.print_r($notify_data, true), 'xywx_notify');
		
		
		if (!empty($notify_data) && is_array($notify_data)) {
			$recharge_sku = $notify_data['out_trade_no'];
			$recharge_info = D('Recharge')->getRechargeInfoBySku($recharge_sku);
			ApiLog('$recharge_infos:'.print_r($recharge_info,true), 'xywx_notify');
				
			if ($recharge_info['recharge_status'] == C('RECHARGE_STATUS.PAID') ||$recharge_info['recharge_status'] == C('RECHARGE_STATUS.FAIL')) {
				ApiLog('result already success:'.$recharge_sku, 'xywx_notify');
				echo 'success';
				return;
			}
		
			if (bcmul($recharge_info['recharge_amount'], 100) != $notify_data['total_fee']) {
				ApiLog('result money check fail:'.$recharge_sku.($recharge_info['recharge_amount'] * 100).'===='.$notify_data['total_fee'].var_export(($recharge_info['recharge_amount'] * 100), true).var_export($notify_data['total_fee'], true), 'xywx_notify');
				return;
			}
		
			if ($notify_data['result_code'] == 0 && $notify_data['pay_result'] == 0) {
				$trans_result = false;
		
				M()->startTrans();
		
				$money = $recharge_info['recharge_amount'];
		
				$increse_result = D('UserAccount')->increaseMoney($recharge_info['uid'], $money, $recharge_info['recharge_id'], C('USER_ACCOUNT_LOG_TYPE.RECHARGE'), true);
		
				if ($increse_result) {
					$recharge_data['recharge_receive_time'] = getCurrentTime();
					$recharge_data['recharge_status'] 		= C('RECHARGE_STATUS.PAID');
					$recharge_map['recharge_id'] 			= $recharge_info['recharge_id'];
					$recharge_result = D('Recharge')->where($recharge_map)->save($recharge_data);
		
					if ($recharge_result) {
						$trans_result = true;
					}
		
				} else {
					ApiLog('result increse result fail:'.$recharge_sku, 'zxwx_notify');
				}
		
				if ($trans_result) {
					M()->commit();
					A('UserCoupon')->rewardCouponForFirstRecharge($recharge_info['recharge_id']);
					ApiLog('result trans success:'.$recharge_sku, 'zxwx_notify');
					echo 'success';
					return;
				} else {
					ApiLog('result trans fail:'.$recharge_sku, 'zxwx_notify');
					M()->rollback();
					return;
				}
			}
		} else {
			ApiLog('result resp error:'.$recharge_sku, 'zxwx_notify');
			echo 'fail';
		}
	}

	private function _buildWxRecordData($recharge_info){
		$body_name 	= '账户充值';
		$wx_record_data['recharge_id'] = $recharge_info['recharge_id'];
		$wx_record_data['uid'] = $recharge_info['uid'];
		$wx_record_data['wx_record_out_trade_no'] = $recharge_info['recharge_sku'];
		$wx_record_data['wx_record_total_fee'] = $recharge_info['recharge_amount'] * 100;
		$wx_record_data['wx_record_body'] = $body_name;
		$wx_record_data['wx_record_attach'] = $body_name;
		$wx_record_data['wx_record_starttime'] = date("YmdHis");
		$wx_record_data['wx_record_expiretime'] = date("YmdHis", time() + 600);
		$wx_record_data['wx_record_product_id'] = "10002";
		$wx_record_data['wx_record_trade_type'] = "APP";
		$wx_record_data['wx_record_createtime'] = date("YmdHis");
		return $wx_record_data;
	}
	
	public function buildParams($request_params, $rechange_id,$user_id){
		$recharge_info = D('Recharge')->getRechargeInfo($rechange_id);
		if(empty($recharge_info['recharge_id'])){
			\AppException::ifNoExistThrowException($rechange_id, C('ERROR_CODE.DATABASE_ERROR'));
		}
		$wx_record_data = $this->_buildWxRecordData($recharge_info);

		$request_data = array();
		$request_data['mch_create_ip'] 	= get_client_ip(0, true);
		$request_data['out_trade_no'] 	= $wx_record_data['wx_record_out_trade_no'];
		$request_data['time_start'] 	= date('YmdHis');
		$request_data['body']  = $wx_record_data['wx_record_body'];
		$request_data['total_fee'] 		= strval($wx_record_data['wx_record_total_fee']);
		$request_data['notify_url'] = U('Home/XYWXSdk/receiveNotifyResult@'.$_SERVER['HTTP_HOST'],'','',true); 
		
		import('@.Util.XYWX.XYWXHandler');
		$xywx_handler = new \XYWXHandler(C('XINGYE_BANK_WX_CONFIG'));

		$request_params = $xywx_handler->buildParamsForSdk($request_data);
		if(!$request_params){
			\AppException::throwException(C('ERROR_CODE.DATABASE_ERROR'));
		}
		$request_params['total_fee'] = $request_data['total_fee'];
		$request_params['appid'] = C('XINGYE_BANK_WX_CONFIG.appid');
		$request_params['extraData'] 	= '';
		ApiLog('request resp:'.var_export($request_params, true), 'xywx');
		return $request_params;
	}
}