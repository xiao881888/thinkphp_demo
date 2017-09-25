<?php
namespace Home\Controller;
use Think\Controller;
class ZWXSdkController extends Controller {
	public function __construct(){
		parent::__construct();
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
		$wx_record_data['wx_record_trade_type'] = "trade.weixin.apppay";
		$wx_record_data['wx_record_createtime'] = date("YmdHis");
		return $wx_record_data;
	}
	
	
	private function _sendSuccessAndExit(){
		exit('success');
	}
	
	private function _sendFailAndExit(){
		exit('fail');
	}
	
	public function receiveNotifyResult(){
		ApiLog('alipay notify:'.print_r($_POST,true), 'wx');
		return ;
		$alipay_config = C('ALIPAY_CONFIG');
		$alipayNotify = new \AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyNotify();
// 		if($_POST['trade_no']=='2016021921001004990205309058'){
// 			$verify_result = true;
// 		}
		if($verify_result) {//验证成功
			//商户订单号		
			$out_trade_no = $_POST['out_trade_no'];
			ApiLog('out:'.$out_trade_no, 'alipay');
			$record_info = D('AlipayRecord')->queryInfoByTradeNo($out_trade_no);
			if(empty($record_info)){
				ApiLog('no exist :'.$out_trade_no);
				$this->_sendFailAndExit();
			}
			if($record_info['alipay_record_status']==1){
				$this->_sendSuccessAndExit();
			}
			//支付宝交易号
			$trade_no = $_POST['trade_no'];
			//交易状态
			$trade_status = $_POST['trade_status'];
		
			$notify_data['alipay_record_notify_time'] = $_POST['notify_time'];
			$notify_data['alipay_record_notify_type'] = $_POST['notify_type'];
			$notify_data['alipay_record_notify_id'] = $_POST['notify_id'];
			$notify_data['alipay_record_notify_sign_type'] = $_POST['sign_type'];
			$notify_data['alipay_record_notify_sign'] = $_POST['sign'];
			$notify_data['alipay_record_trade_no'] = $_POST['trade_no'];
			$notify_data['alipay_record_trade_status'] = $_POST['trade_status'];
			$notify_data['alipay_record_buyer_id'] = $_POST['buyer_id'];
			$notify_data['alipay_record_buyer_email'] = $_POST['buyer_email'];
			$notify_data['alipay_record_seller_id'] = $_POST['seller_id'];
			$notify_data['alipay_record_seller_email'] = $_POST['seller_email'];
			
			if($_POST['gmt_createtime']){
				$notify_data['alipay_record_gmt_createtime'] = $_POST['gmt_createtime'];
			}
			
			if($_POST['gmt_payment']){
				$notify_data['alipay_record_gmt_payment'] = $_POST['gmt_payment'];
			}
			
			if($_POST['is_total_fee_adjust']){
				$notify_data['alipay_record_is_total_fee_adjust'] = $_POST['is_total_fee_adjust'];
			}

			if($_POST['use_coupon']){
				$notify_data['alipay_record_use_coupon'] = $_POST['use_coupon'];
			}
			
			if($_POST['discount']){
				$notify_data['alipay_record_discount'] = $_POST['discount'];
				
			}
			if($_POST['refund_status']){
				$notify_data['alipay_record_refund_status'] = $_POST['refund_status'];
				
			}
			if($_POST['gmt_refund']){
					
				$notify_data['alipay_record_gmt_refund'] = $_POST['gmt_refund'];
			}

			M()->startTrans();
			if($_POST['trade_status'] == 'TRADE_FINISHED') {
				//判断该笔订单是否在商户网站中已经做过处理
				//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				//如果有做过处理，不执行商户的业务程序
				//注意：
				//该种交易状态只在两种情况下出现
				//1、开通了普通即时到账，买家付款成功后。
				//2、开通了高级即时到账，从该笔交易成功时间算起，过了签约时的可退款时限（如：三个月以内可退款、一年以内可退款等）后。
				$notify_data['alipay_record_status'] = C('RECHARGE_RECORD_STATUS.NOTIFIED');
				//调试用，写文本函数记录程序运行情况是否正常
				//logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
			} else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
				//判断该笔订单是否在商户网站中已经做过处理
				//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				//如果有做过处理，不执行商户的业务程序
		
				//注意：
				//该种交易状态只在一种情况下出现——开通了高级即时到账，买家付款成功后。
				
				$money = $record_info['alipay_record_total_fee'];
				$increse_result = D('UserAccount')->increaseMoney($record_info['uid'],$money,$record_info['recharge_id'],C('USER_ACCOUNT_LOG_TYPE.RECHARGE'),true);
				ApiLog('incre:'.$increse_result,'alipay');
				if ($increse_result) {
					$recharge_data['recharge_receive_time'] = $_POST['notify_time'];
					$recharge_data['recharge_status'] = C('RECHARGE_STATUS.PAID');
					$recharge_data['recharge_no'] = $out_trade_no;
					$recharge_map['recharge_id'] = $record_info['recharge_id'];
					$recharge_result = D('Recharge')->where($recharge_map)->save($recharge_data);
				}
				
				$notify_data['alipay_record_status'] = C('RECHARGE_RECORD_STATUS.NOTIFIED');
				
				//调试用，写文本函数记录程序运行情况是否正常
				//logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
			}elseif($_POST['trade_status'] == 'WAIT_BUYER_PAY'){
				
			}
			$notify_map['alipay_record_out_trade_no'] = $out_trade_no;
			$save_result = D('AlipayRecord')->where($notify_map)->save($notify_data);
			
			if($recharge_result && $save_result){
				M()->commit();
				A('UserCoupon')->rewardCouponForFirstRecharge($record_info['recharge_id']);
				$this->_sendSuccessAndExit();
			}else{
				M()->rollback();
				$this->_sendFailAndExit();
			}
		} else {
			//验证失败
			$this->_sendFailAndExit();
		}
	}
	
	private function _buildWeixinNotifyUrl(){
		return U('Home/WeixinSdk/receiveNotifyResult@'.$_SERVER['HTTP_HOST'],'','',true);
	}
	
	private function _buildAlipayTradeNo($user_id){
		$trade_no = PAYMENT_CHANNEL_ID_OF_ALIPAY . date('YmdHis') . $user_id . random_string(6);
		return $trade_no;
	}
	
	public function buildParams($request_params,$rechange_id,$user_id){
		$recharge_info = D('Recharge')->getRechargeInfo($rechange_id);
		if(empty($recharge_info['recharge_id'])){
			\AppException::ifNoExistThrowException($rechange_id, C('ERROR_CODE.DATABASE_ERROR'));
		}
		$wx_record_data = $this->_buildWxRecordData($recharge_info);
		import('@.Util.ZWX.ZWXHandler');
		
		$data['out_trade_no'] 		= $wx_record_data['wx_record_out_trade_no'];
		$data['body'] 				= $wx_record_data['wx_record_body'];
		$data['total_fee'] 			= $wx_record_data['wx_record_total_fee'];
		$data['spbill_create_ip'] 	= get_client_ip(0, true);
		$data['trade_type'] 		= $wx_record_data['wx_record_trade_type'];
		$data['notify_url']			= U('Home/ZWXSdk/receiveNotifyResult@'.$_SERVER['HTTP_HOST'],'','',true);
		
		//20161125 梓微星 增加字段
		$data['attach'] 			= 'bank_mch_name=福建虎彩网络科技有限公司&bank_mch_id=15121832';
		if($request_params->os==OS_OF_ANDROID){
			$data['detail'] 			= 'app_name=赢球大师&package_name=co.sihe.hongmi';
		}elseif($request_params->os==OS_OF_IOS){
			$data['detail'] 			= 'app_name=赢球大师&bundle_id=com.sihe.HongMi';
		}else{
			$data['detail'] 			= 'app_name=赢球大师&bundle_id=com.sihe.HongMi&package_name=co.sihe.hongmi';
		}
		
		$zxw_handler = new \ZWXHandler();
		$params = $zxw_handler->buildParamsForSdk($data);
		ApiLog('ZWX RECHARGE DATA:'.print_r($data,true).'==='.print_r($params,true), 'zwx_sk');
		
		ApiLog("result:".print_r($params, true), 'zwx_sk');
		
		return $params;
	}
	
}

