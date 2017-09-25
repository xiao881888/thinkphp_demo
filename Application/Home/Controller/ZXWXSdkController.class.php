<?php
namespace Home\Controller;
use Think\Controller;

class ZXWXSdkController extends Controller{

	public function receiveNotifyResult(){
		ApiLog('notify result:'.print_r($_POST, true), 'zxwx_h5');
		
		import('@.Util.ZXWX.ZXWXHandler');
		$zxwx_handler = new \ZXWXHandler(C('zxwx_config'));
		
		$notify_raw_string = $_POST['sendData'];
		
// 		$notify_raw_string = 'eyJiYWNrRW5kVXJsIjoiaHR0cDovL3Bob25lLmFwaS50aWdlcmNhaS5jb20vaW5kZXgucGhwL0hvbWUvWlhXWEg1L25vdGlmeVJlc3VsdCIsImNoYW5uZWxUeXBlIjoiNjAwMiIsImN1cnJlbmN5VHlwZSI6IjE1NiIsImVuY29kaW5nIjoiVVRGLTgiLCJlbmRUaW1lIjoiMjAxNjEyMjkxNzE3MDYiLCJtZXJJZCI6Ijk5NjYwMDAwODAwMDAwNCIsIm9yZGVyQm9keSI6Iui0puaIt#WFheWAvCIsIm9yZGVySWQiOiJSQzIwMTYxMjI5MTcxNjIwdEh1RzlBMzE0Iiwib3JkZXJTdWJPcGVuaWQiOiJvVXZBdndNMlR1bGRLVDdFY1ZiemZZWUpxa1BzIiwib3JkZXJUaW1lIjoiMjAxNjEyMjkxNzE2MjAiLCJwYXlBY2Nlc3NUeXBlIjoiMDIiLCJyZXNwQ29kZSI6IjAwMDAiLCJyZXNwTXNnIjoi5Lqk5piT5oiQ5YqfIiwic2VjTWVySWQiOiIiLCJzZXR0bGVBbXQiOiIxIiwic2V0dGxlQ3VycmVuY3lDb2RlIjoiMTU2Iiwic2V0dGxlRGF0ZSI6IjIwMTYxMjI5Iiwic2lnbkF0dXJlIjoiQ0FERDFCNTI0NjlGRTNENEY3MTRCQTg0RTE2QkU4Q0QiLCJzaWduTWV0aG9kIjoiMDIiLCJ0ZXJtSWQiOiIiLCJ0cmFuc2FjdGlvbklkIjoiNDAwNDc2MjAwMTIwMTYxMjI5NDM5MTk2NzczNCIsInR4bkFtdCI6IjEiLCJ0eG5TZXFJZCI6IjkwMTIwMTYxMjI5MTcyNDE2MTcxNDY3OTEiLCJ0eG5TdWJUeXBlIjoiMDEwMTMyIiwidHhuVGltZSI6IjIwMTYxMjI5MTcxNjE3IiwidHhuVHlwZSI6IjAxIn0=';
		$notify_data = $zxwx_handler->parseData($notify_raw_string);
		ApiLog('$notify_data:'.print_r($notify_data, true), 'zxwx_notify');
		
		
		if (!empty($notify_data) && is_array($notify_data)) {
			$recharge_sku = $notify_data['orderId'];
			$recharge_info = D('Recharge')->getRechargeInfoBySku($recharge_sku);
			ApiLog('$recharge_infos:'.print_r($recharge_info,true), 'zxwx_notify');
				
			if ($recharge_info['recharge_status'] == C('RECHARGE_STATUS.PAID') ||$recharge_info['recharge_status'] == C('RECHARGE_STATUS.FAIL')) {
				ApiLog('result already success:'.$recharge_sku, 'zxwx_notify');
				echo 'SUCCESS';
				return;
			}
		
			if (bcmul($recharge_info['recharge_amount'], 100) != $notify_data['txnAmt']) {
				ApiLog('result money check fail:'.$recharge_sku.($recharge_info['recharge_amount'] * 100).'===='.$notify_data['txnAmt'].var_export(($recharge_info['recharge_amount'] * 100), true).var_export($notify_data['txnAmt'], true), 'zxwx_notify');
				return;
			}
		
			if ($notify_data['respCode'] == '0000' ) {
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
					echo 'SUCCESS';
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
		
		import('@.Util.ZXWX.ZXWXHandler');

		$request_data = array();
		$request_data['termIp'] 	= get_client_ip(0, true);
		$request_data['orderId'] 	= $wx_record_data['wx_record_out_trade_no'];
		$request_data['orderTime'] 	= date('YmdHis');
		$request_data['orderBody']  = $wx_record_data['wx_record_body'];
		$request_data['txnAmt'] 		= strval($wx_record_data['wx_record_total_fee']);
		$request_data['currencyType'] 	= '156';
		$request_data['backEndUrl'] = U('Home/ZXWXSdk/receiveNotifyResult@'.$_SERVER['HTTP_HOST'],'','',true); 
		
		if ($request_params->os == OS_OF_IOS) {
			$request_data['orderDetail'] = 'app_name=赢球大师&bundle_id=com.sihe.HongMi';
		} else {
			$request_data['orderDetail'] = 'app_name=赢球大师&package_name=com.sihe.hongmi';
		}
		
		$zxwx_handler = new \ZXWXHandler(C('zxwx_config'));

		$request_params = $zxwx_handler->buildParamsForSdk($request_data);
		if(!$request_params){
			\AppException::throwException(C('ERROR_CODE.DATABASE_ERROR'));
		}
		$request_params['extraData'] 	= '';
		ApiLog('request resp:'.var_export($request_params, true), 'zxwx_h5');
		return $request_params;
	}
}