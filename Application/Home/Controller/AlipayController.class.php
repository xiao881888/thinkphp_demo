<?php
namespace Home\Controller;
use Think\Controller;
class AlipayController extends Controller {
	const ALIPAY_PAYMENT_TYPE=1;
	private $_upFileConfig = '';
	public function __construct(){
		import('@.Util.Alipay.alipay_notify');
		parent::__construct();
	}
	
	private function _sendSuccessAndExit(){
		exit('success');
	}
	
	private function _sendFailAndExit(){
		exit('fail');
	}
	
	public function receiveNotifyResult(){
		$alipay_config = C('ALIPAY_CONFIG');
		$alipayNotify = new \AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyNotify();
// 		if($_POST['trade_no']=='2016021921001004990205309058'){
// 			$verify_result = true;
// 		}
		if($verify_result) {//验证成功
			//商户订单号		
			$out_trade_no = $_POST['out_trade_no'];
			$record_info = D('AlipayRecord')->queryInfoByTradeNo($out_trade_no);
			if(empty($record_info)){
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
	
	private function _buildAlipayNotifyUrl(){
		return U('Home/Alipay/receiveNotifyResult@'.$_SERVER['HTTP_HOST'],'','',true);
	}
	
	private function _buildAlipayTradeNo($user_id){
		$trade_no = PAYMENT_CHANNEL_ID_OF_ALIPAY . date('YmdHis') . $user_id . random_string(6);
		return $trade_no;
	}
	
	public function genAlipayParams($request_params,$rechange_id,$user_id){
		import('@.Util.Alipay.alipay_notify');
		
		$alipay_params['service'] = 'mobile.securitypay.pay';
		$alipay_params['partner'] = C('ALIPAY_CONFIG.partner');
		$alipay_params['_input_charset'] = C('ALIPAY_CONFIG.input_charset');
		$alipay_params['seller_id'] = C('ALIPAY_CONFIG.seller_id');
		$alipay_params['notify_url'] = $this->_buildAlipayNotifyUrl();
		$alipay_params['out_trade_no'] = $this->_buildAlipayTradeNo($user_id);
		$alipay_params['payment_type'] = self::ALIPAY_PAYMENT_TYPE;
		$alipay_params['total_fee'] = floatval($request_params->money);

		$sub_body_params = $this->_buildAlipaySubjectAndBody();
		$alipay_params['subject'] = $sub_body_params['subject'];
		$alipay_params['body'] = $sub_body_params['body'];

		$alipay_params = argSort($alipay_params);
		$string_for_sign = createLinkstringInQuotes($alipay_params);

		$alipay_params['sign'] = urlencode(rsaSign($string_for_sign, C('ALIPAY_CONFIG.private_key_path')));
		$alipay_params['sign_type'] = C('ALIPAY_CONFIG.sign_type');

		$request_string = createLinkstringInQuotes($alipay_params);
			
		$alipay_record_data = $this->_buildAlipayRecordData($alipay_params,$rechange_id,$user_id);
		$record_id = D('AlipayRecord')->add($alipay_record_data);
		\AppException::ifNoExistThrowException($record_id, C('ERROR_CODE.DATABASE_ERROR'));
		return $request_string;
	}
	
	private function _buildAlipaySubjectAndBody($recharge_type='',$extra=''){
		$recharge_type_desc = C('RECHARGE_TYPE_DESC');
		$recharge_type = self::ALIPAY_PAYMENT_TYPE;
		$subject = $recharge_type_desc[$recharge_type];
		$body = $subject;
		$params['subject'] = $subject;
		$params['body'] = $body;
		return $params;
	}
	
	private function _buildAlipayRecordData($alipay_params,$rechange_id,$user_id){
		$alipay_record_data['uid'] = $user_id;
		$alipay_record_data['recharge_id'] = $rechange_id;
		$alipay_record_data['alipay_record_out_trade_no'] = $alipay_params['out_trade_no'];
		$alipay_record_data['alipay_record_payment_type'] = $alipay_params['payment_type'];
		$alipay_record_data['alipay_record_total_fee'] = $alipay_params['total_fee'];
		$alipay_record_data['alipay_record_subject'] = $alipay_params['subject'];
		$alipay_record_data['alipay_record_body'] = $alipay_params['body'];
	
		$alipay_record_data['alipay_record_sign_type'] = $alipay_params['sign_type'];
		$alipay_record_data['alipay_record_sign'] = $alipay_params['sign'];
		$alipay_record_data['alipay_record_status'] = 0;
		return $alipay_record_data;
	}
}

