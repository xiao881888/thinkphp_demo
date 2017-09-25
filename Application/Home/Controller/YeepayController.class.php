<?php
namespace Home\Controller;
use Think\Controller;
class YeepayController extends Controller {
	private $_yeepay_obj = null;
	public function __construct(){
		import('@.Util.Yeepay.yeepayMPay');
		$this->_yeepay_obj = new \yeepayMPay(C('YEEPAY_CONFIG.merchant_id'),C('YEEPAY_CONFIG.merchant_public_key'),
				C('YEEPAY_CONFIG.merchant_private_key'),C('YEEPAY_CONFIG.yeepay_public_key'));
		
		parent::__construct();
	}
	
	private function _parseRequestParams(){
		ApiLog('receive notify origin params:' . print_r($_REQUEST, true), 'yeepay');
		if(empty($_REQUEST['data']) || empty($_REQUEST['encryptkey'])){
			return false;
		}
		$request_params = $this->_yeepay_obj->callback($_REQUEST['data'], $_REQUEST['encryptkey']); //解密易宝支付回调结果
		if(empty($request_params)){
			return false;
		}
		return $request_params;
	}

	private function _echoSuccess(){
		exit('SUCCESS');
	}
	
	private function _echoFail(){
		exit('error');
	}
	
	public function receiveNotifyResult(){
		$request_params = $this->_parseRequestParams();
		ApiLog('receive notify:' . print_r($request_params, true), 'yeepay');
		if($request_params){//验证成功
			$tiger_sku = $request_params['orderid'];
			ApiLog('tiger_sku:'.$tiger_sku, 'yeepay');
			$yeepay_rc_map['yeepay_record_orderid'] = $tiger_sku;
			$record_info = D('YeepayRecord')->where($yeepay_rc_map)->find();
			if(empty($record_info)){
				ApiLog('no exist :'.$tiger_sku);
				$this->_echoFail();
			}
			if($record_info['yeepay_record_status']==1){
				$this->_echoSuccess();
			}
			$notify_data['yeepay_record_yborderid'] = $request_params['yborderid'];
			$notify_data['yeepay_record_bankcode'] = $request_params['bankcode'];
			$notify_data['yeepay_record_bank'] = $request_params['bank'];
			$notify_data['yeepay_record_lastno'] = $request_params['lastno'];
			$notify_data['yeepay_record_cardtype'] = $request_params['cardtype'];
			$notify_data['yeepay_record_notify_result'] = $request_params['status'];
			$notify_data['yeepay_record_modifytime'] = getCurrentTime();
			$notify_data['yeepay_record_notify_sign'] = $request_params['sign'];
			
			M()->startTrans();
			if($request_params['status'] == 1) {
				$money = $record_info['yeepay_record_amount']/100;
				$increse_result = D('UserAccount')->increaseMoney($record_info['uid'],$money,$record_info['recharge_id'],C('USER_ACCOUNT_LOG_TYPE.RECHARGE'),true);
				ApiLog('incre:' . $increse_result, 'yeepay');
				if ($increse_result) {
					$recharge_data['recharge_receive_time'] = getCurrentTime();
					$recharge_data['recharge_status'] = C('RECHARGE_STATUS.PAID');
					$recharge_data['recharge_no'] = $record_info['yborderid'];
					$recharge_map['recharge_id'] = $record_info['recharge_id'];
					$recharge_result = D('Recharge')->where($recharge_map)->save($recharge_data);
				}
				$notify_data['yeepay_record_status'] = C('RECHARGE_RECORD_STATUS.NOTIFIED');
			}
			
			$notify_map['yeepay_record_orderid'] = $tiger_sku;
			$save_result = D('YeepayRecord')->where($notify_map)->save($notify_data);
			
			if ($recharge_result && $save_result) {
				M()->commit();
				A('UserCoupon')->rewardCouponForFirstRecharge($record_info['recharge_id']);
				$this->_echoSuccess();
			} else {
				M()->rollback();
				$this->_echoFail();
			}
			$this->_echoSuccess();
		}else{
			//验证失败
			$this->_echoFail();
		}
	}
	
	public function receiveReturnResult(){
		$sku = $_REQUEST['i'];
		$sku_info = explode('RH0', $sku);
		$recharge_id = intval($sku_info[0]);
		$map['recharge_id'] = $recharge_id;
		$map['recharge_status'] = 1;
		$recharge_info = D('Recharge')->where($map)->find();
// 		$result = $_REQUEST['result'];
// 		if($result==1){
		if($recharge_info){
			$this->assign('desc','充值成功,请返回应用继续支付购彩');
		}else{
			$this->assign('desc','充值未到账或者支付失败');
		}
		$this->display('return');
	}
	
	private function _buildYeepayNotifyUrl(){
// 		return 'http://hpay-notify.sihecp.com/Home/Yeepay/receiveNotifyResult';
		return U('Home/Yeepay/receiveNotifyResult@'.$_SERVER['HTTP_HOST'],'','',true);
	}
	
	private function _buildYeepayReturnUrl($recharge_id){
		$sku = $recharge_id.'RH0'.random_string(8);
		return 'http://hpay-notify.sihecp.com/Home/Yeepay/receiveReturnResult/i/'.$sku;
		return U('Home/Yeepay/receiveReturnResult@'.$_SERVER['HTTP_HOST'],'','',true);
	}
	
	public function genYeepayTargetUrl($recharge_sku, $money, $rechange_id, $user_id){
		$yeepay_params['orderid'] = $recharge_sku;
		$yeepay_params['transtime'] = time();
		$yeepay_params['currency'] = C('YEEPAY_CONFIG.currency');
		$yeepay_params['amount'] = $money*100;
		$yeepay_params['productcatalog'] = C('YEEPAY_CONFIG.productcatalog');
		$yeepay_params['productname'] = C('YEEPAY_CONFIG.productname');
		$yeepay_params['productdesc'] = C('YEEPAY_CONFIG.productname').$money.'元';
		$yeepay_params['identitytype'] = 2;
		$yeepay_params['identityid'] = $user_id+100000;
		$yeepay_params['userip'] = get_client_ip(0,true);
		$yeepay_params['userua'] = '';
		$yeepay_params['callbackurl'] = $this->_buildYeepayNotifyUrl();
		$yeepay_params['fcallbackurl'] = $this->_buildYeepayReturnUrl($rechange_id);
		
		$yeepay_params['terminaltype'] = 0;
		$yeepay_params['terminalid'] = '';
		
		$yeepay_params['idcardtype'] = '';
		$yeepay_params['cardno'] = '';
		$yeepay_params['idcard'] = '';
		$yeepay_params['owner'] = '';
		$yeepay_params['paytypes'] = '';
		$yeepay_params['orderexpdate'] = 60;
		$yeepay_params['version'] = '';
		
		$pay_url = $this->_buildRequestYeepayUrl($yeepay_params);
		ApiLog('pay:'.$pay_url.'==='.print_r($yeepay_params,true).'==='.M()->_sql(),'yeepay');
// 		return $pay_url;
		
		$yeepay_record_data = $this->_buildYeepayRecordData($yeepay_params, $rechange_id, $user_id);
		$add_res = D('YeepayRecord')->add($yeepay_record_data);
		ApiLog('add res:'.$add_res.'==='.M()->_sql(),'yeepay');
		if($add_res){
			return $pay_url;
		}else{
			\AppException::throwException(C('ERROR_CODE.DATABASE_ERROR'));
			return false;
		}
	}
	
	private function _buildRequestYeepayUrl($yeepay_params){
		$yeepay = new \yeepayMPay(C('YEEPAY_CONFIG.merchant_id'),C('YEEPAY_CONFIG.merchant_public_key'),
								C('YEEPAY_CONFIG.merchant_private_key'),C('YEEPAY_CONFIG.yeepay_public_key'));
		$url = $yeepay->webPay($yeepay_params['orderid'],$yeepay_params['transtime'],
				$yeepay_params['amount'],$yeepay_params['cardno'],$yeepay_params['idcardtype'],
				$yeepay_params['idcard'],$yeepay_params['owner'],$yeepay_params['productcatalog'],
				$yeepay_params['identityid'],$yeepay_params['identitytype'],$yeepay_params['userip'],
				$yeepay_params['userua'],$yeepay_params['callbackurl'],$yeepay_params['fcallbackurl'],
				$yeepay_params['currency'],$yeepay_params['productname'],$yeepay_params['productdesc'],
				$yeepay_params['terminaltype'],$yeepay_params['terminalid'],$yeepay_params['orderexpdate'],
				$yeepay_params['paytypes'],$yeepay_params['version']);
		
		return $url;
	}
	
	private function _buildYeepayRecordData($yeepay_params,$rechange_id,$user_id){
		$yeepay_record_data['uid'] = $user_id;
		$yeepay_record_data['recharge_id'] = $rechange_id;
		$yeepay_record_data['yeepay_record_orderid'] = $yeepay_params['orderid'];
		$yeepay_record_data['yeepay_record_transtime'] = $yeepay_params['transtime'];
		$yeepay_record_data['yeepay_record_amount'] = $yeepay_params['amount'];
		$yeepay_record_data['yeepay_record_currency'] = $yeepay_params['currency'];
		$yeepay_record_data['yeepay_record_productcatalog'] = $yeepay_params['productcatalog'];
		$yeepay_record_data['yeepay_record_productname'] = $yeepay_params['productname'];
		$yeepay_record_data['yeepay_record_productdesc'] = $yeepay_params['productdesc'];
		$yeepay_record_data['yeepay_record_identityid'] = $yeepay_params['identityid'];
		$yeepay_record_data['yeepay_record_identitytype'] = $yeepay_params['identitytype'];
		$yeepay_record_data['yeepay_record_terminaltype'] = $yeepay_params['terminaltype'];
		$yeepay_record_data['yeepay_record_terminalid'] = $yeepay_params['terminalid'];
		$yeepay_record_data['yeepay_record_userip'] = $yeepay_params['userip'];
		$yeepay_record_data['yeepay_record_userua'] = $yeepay_params['userua'];
		$yeepay_record_data['yeepay_record_paytypes'] = $yeepay_params['paytypes'];
		$yeepay_record_data['yeepay_record_orderexpdate'] = $yeepay_params['orderexpdate'];
		$yeepay_record_data['yeepay_record_idcardtype'] = $yeepay_params['idcardtype'];
		$yeepay_record_data['yeepay_record_cardno'] = $yeepay_params['cardno'];
		$yeepay_record_data['yeepay_record_idcard'] = $yeepay_params['idcard'];
		$yeepay_record_data['yeepay_record_owner'] = $yeepay_params['owner'];
		$yeepay_record_data['yeepay_record_version'] = $yeepay_params['version'];
		$yeepay_record_data['yeepay_record_createtime'] = $yeepay_params['terminalid'];
		$yeepay_record_data['yeepay_record_createtime'] = getCurrentTime();
		$yeepay_record_data['yeepay_record_status'] = 0;
		return $yeepay_record_data;
	}
}

