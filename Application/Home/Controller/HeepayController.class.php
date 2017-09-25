<?php
namespace Home\Controller;
use Think\Controller;
class HeepayController extends Controller {
	public function __construct(){
		parent::__construct();
	}
	
	private function _parseRequestParams(){
		ApiLog('receive notify origin params:' . print_r($_REQUEST, true), 'heepay');
		
		$request_params['result'] = $_REQUEST['result'];
		$request_params['agent_id'] = $_REQUEST['agent_id'];
		$request_params['jnet_bill_no'] = $_REQUEST['jnet_bill_no'];
		$request_params['agent_bill_id'] = $_REQUEST['agent_bill_id'];
		$request_params['pay_type'] = $_REQUEST['pay_type'];
		$request_params['pay_amt'] = $_REQUEST['pay_amt'];
		$request_params['remark'] = $_REQUEST['remark'];
		$request_params['pay_message'] = $_REQUEST['pay_message'];
		$verify_result = $this->_verifyNotifySign($request_params, $_REQUEST['sign']);
		if(!$verify_result){
			return false;
		}
		$request_params['fbtn'] = $_REQUEST['fbtn'];
		$request_params['sign'] = $_REQUEST['sign'];
		return $request_params;
	}

	private function _verifyNotifySign($request_params, $notify_sign){
		unset($request_params['pay_message']);
		$request_params['key'] = C('HEEPAY_CONFIG.agent_key');
		$sign = $this->_buildHeepaySign($request_params);
		ApiLog('notify sign:'.$sign.'==='.$notify_sign, 'heepay');
		return $sign == $notify_sign;
	}
	
	private function _echoSuccess(){
		exit('ok');
	}
	
	private function _echoFail(){
		exit('error');
	}
	
	public function receiveNotifyResult(){
		$request_params = $this->_parseRequestParams();
		ApiLog('receive notify:' . print_r($request_params, true), 'heepay');
		if($request_params){//验证成功
			$tiger_sku = $request_params['agent_bill_id'];
			ApiLog('tiger_sku:'.$tiger_sku, 'heepay');
			$heepay_rc_map['heepay_record_agent_bill_id'] = $tiger_sku;
			$record_info = D('HeepayRecord')->where($heepay_rc_map)->find();
			if(empty($record_info)){
				ApiLog('no exist :'.$tiger_sku);
				$this->_echoFail();
			}
			if($record_info['heepay_record_status']==1){
				$this->_echoSuccess();
			}
			$notify_data['heepay_record_jnet_bill_no'] = $request_params['jnet_bill_no'];
			$notify_data['heepay_record_fbtn'] = $request_params['fbtn'];
			$notify_data['heepay_record_pay_message'] = $request_params['pay_message'];
			$notify_data['heepay_record_notify_sign'] = $request_params['sign'];
			$notify_data['heepay_record_modifytime'] = getCurrentTime();
			$notify_data['heepay_record_notify_result'] = $request_params['result'];
			
			M()->startTrans();
			if($request_params['result'] == 1) {
				$money = $record_info['heepay_record_pay_amt'];
				$increse_result = D('UserAccount')->increaseMoney($record_info['uid'],$money,$record_info['recharge_id'],C('USER_ACCOUNT_LOG_TYPE.RECHARGE'),true);
				ApiLog('incre:' . $increse_result, 'heepay');
				if ($increse_result) {
					$recharge_data['recharge_receive_time'] = getCurrentTime();
					$recharge_data['recharge_status'] = C('RECHARGE_STATUS.PAID');
					$recharge_data['recharge_no'] = $record_info['heepay_record_agent_bill_id'];
					$recharge_map['recharge_id'] = $record_info['recharge_id'];
					$recharge_result = D('Recharge')->where($recharge_map)->save($recharge_data);
				}
				$notify_data['heepay_record_status'] = C('RECHARGE_RECORD_STATUS.NOTIFIED');
			}
			
			$notify_map['heepay_record_agent_bill_id'] = $tiger_sku;
			$save_result = D('HeepayRecord')->where($notify_map)->save($notify_data);
			
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
// 		$sku = $_REQUEST['i'];
// 		$sku_info = explode('RH0', $sku);
// 		$recharge_id = intval($sku_info[0]);
// 		$map['recharge_id'] = $recharge_id;
// 		$map['recharge_status'] = 1;
// 		$recharge_info = D('Recharge')->where($map)->find();
$result = $_REQUEST['result'];
		if($result==1){
			$this->assign('desc','充值成功,请返回应用继续支付购彩');
		}else{
			$this->assign('desc','充值未到账或者支付失败');
		}
		$this->display('return');
	}
	
	private function _buildHeepayNotifyUrl(){
		return 'http://hpay-notify.sihecp.com/Home/Heepay/receiveNotifyResult';
		return U('Home/Heepay/receiveNotifyResult@'.$_SERVER['HTTP_HOST'],'','',true);
	}
	
	private function _buildHeepayReturnUrl($recharge_id){
		$sku = $recharge_id.'RH0'.random_string(8);
		return 'http://hpay-notify.sihecp.com/Home/Heepay/receiveReturnResult/i/'.$sku;
		return 'http://';
		return U('Home/Heepay/receiveReturnResult@'.$_SERVER['HTTP_HOST'],'','',true);
	}
	
	private function _buildHeepaySign($heepay_params){
		foreach($heepay_params as $key=>$val){
			$build_string .= $key.'='.$val.'&';
		}
		$build_string = substr($build_string,0,-1);
		$build_string2 = http_build_query($heepay_params);
		ApiLog('build:'.$build_string.'==='.$build_string2, 'heepay');
		return md5($build_string);
	}

	public function genHeepayTargetUrl($recharge_sku, $money, $rechange_id, $user_id){
		$heepay_params['version'] = C('HEEPAY_CONFIG.version');
		$heepay_params['agent_id'] = C('HEEPAY_CONFIG.agent_id');
		$heepay_params['agent_bill_id'] = $recharge_sku;
		$heepay_params['agent_bill_time'] = date("YmdHis");
		$heepay_params['pay_type'] = C('HEEPAY_CONFIG.pay_type');
		$heepay_params['pay_amt'] = $money;
		$heepay_params['notify_url'] = $this->_buildHeepayNotifyUrl();
		$heepay_params['return_url'] = $this->_buildHeepayReturnUrl($rechange_id);
		$heepay_params['user_ip'] = str_replace('.', '_', get_client_ip(0,true));
// 		$heepay_params['is_test'] = C('HEEPAY_CONFIG.is_test');
		$heepay_params['key'] = C('HEEPAY_CONFIG.agent_key');
		$heepay_params['sign'] = $this->_buildHeepaySign($heepay_params);
		
		$heepay_params['is_phone'] = C('HEEPAY_CONFIG.is_phone');
		$heepay_params['is_frame'] = C('HEEPAY_CONFIG.is_frame');
		$goods_name = '账户充值';
		$heepay_params['goods_name'] = urlencode($goods_name);
		$heepay_params['goods_num'] = 1;
		$heepay_params['goods_note'] = '';
		$heepay_params['remark'] = '';
		
		ApiLog('$heepay_params:'.print_r($heepay_params,true), 'heepay');
		$pay_url = $this->_buildRequestHeepayUrl($heepay_params);
		$heepay_record_data = $this->_buildHeepayRecordData($heepay_params, $goods_name, $rechange_id, $user_id);
		$add_res = D('HeepayRecord')->add($heepay_record_data);
		ApiLog('add res:'.$add_res.'==='.M()->_sql(),'heepay');
		if($add_res){
			return $pay_url;
		}else{
			\AppException::throwException(C('ERROR_CODE.DATABASE_ERROR'));
			return false;
		}
	}
	
	private function _buildRequestHeepayUrl($heepay_params){
		unset($heepay_params['key']);
		$pay_url = C('HEEPAY_CONFIG.pay_url');
		return $pay_url.'?'.http_build_query($heepay_params);
	}
	
	private function _buildHeepayRecordData($heepay_params,$goods_name, $rechange_id,$user_id){
		$heepay_record_data['uid'] = $user_id;
		$heepay_record_data['recharge_id'] = $rechange_id;
		$heepay_record_data['heepay_record_agent_id'] = $heepay_params['agent_id'];
		$heepay_record_data['heepay_record_agent_bill_id'] = $heepay_params['agent_bill_id'];
		$heepay_record_data['heepay_record_pay_type'] = $heepay_params['pay_type'];
		$heepay_record_data['heepay_record_agent_bill_time'] = $heepay_params['agent_bill_time'];
		$heepay_record_data['heepay_record_pay_amt'] = $heepay_params['pay_amt'];
		$heepay_record_data['heepay_record_user_ip'] = $heepay_params['user_ip'];
		if(isset($heepay_params['is_test'])){
			$heepay_record_data['heepay_record_is_test'] = $heepay_params['is_test'];
		}
		$heepay_record_data['heepay_record_goods_name'] = $goods_name;
		$heepay_record_data['heepay_record_goods_num'] = $heepay_params['goods_num'];
		$heepay_record_data['heepay_record_goods_note'] = $heepay_params['goods_note'];
		$heepay_record_data['heepay_record_is_phone'] = $heepay_params['is_phone'];
		$heepay_record_data['heepay_record_is_frame'] = $heepay_params['is_frame'];
		$heepay_record_data['heepay_record_remark'] = $heepay_params['remark'];
		$heepay_record_data['heepay_record_sign'] = $heepay_params['sign'];
		$heepay_record_data['heepay_record_createtime'] = getCurrentTime();
		$heepay_record_data['heepay_record_status'] = 0;
		return $heepay_record_data;
	}
}

