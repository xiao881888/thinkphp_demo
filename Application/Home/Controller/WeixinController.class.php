<?php
namespace Home\Controller;
use Think\Controller;
class WeixinController extends Controller {
	
	const WEIXIN_PAYMENT_SIGN_KEY = 'x3d1Pxe32';

	public function __construct(){
		import('@.Util.Weixin.WxPayApi');
		import('@.Util.Weixin.WxPayNotify');
		import('@.Util.Weixin.WxPayNativePay');
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
		$wx_record_data['wx_record_product_id'] = "10001";
		$wx_record_data['wx_record_trade_type'] = "NATIVE";
		$wx_record_data['wx_record_createtime'] = date("YmdHis");
		return $wx_record_data;
	}
	
	public function showQr(){
		$out_trade_no = $_REQUEST['sku'];

		$recharge_info = $this->_queryRechargeInfoBySku($out_trade_no);
		if($recharge_info['recharge_id']!=$_REQUEST['id'] ){
			$this->_sendFailAndExit();
		}
		$wx_record_data = $this->_buildWxRecordData($recharge_info);
		$wx_order_obj = new \WxPayUnifiedOrder();
		$wx_order_obj->SetBody($wx_record_data['wx_record_body']);
		$wx_order_obj->SetAttach($wx_record_data['wx_record_attach']);
		$wx_order_obj->SetOut_trade_no($wx_record_data['wx_record_out_trade_no']);
		$wx_order_obj->SetTotal_fee($wx_record_data['wx_record_total_fee']);
		$wx_order_obj->SetTime_start($wx_record_data['wx_record_starttime']);
		$wx_order_obj->SetTime_expire($wx_record_data['wx_record_expiretime']);
		$wx_order_obj->SetNotify_url($this->_buildWeixinNotifyUrl($recharge_info));
		$wx_order_obj->SetTrade_type($wx_record_data['wx_record_trade_type']);
		$wx_order_obj->SetProduct_id($wx_record_data['wx_record_product_id']);
		
		$wx_native_obj = new \NativePay();
		
		$result = $wx_native_obj->GetPayUrl($wx_order_obj);
		ApiLog("result:".print_r($wx_order_obj, true).PHP_EOL.print_r($result, true), 'wx');
		if(empty($result["code_url"])){
			$this->_sendFailAndExit();
		}

		D('WxRecord')->add($wx_record_data);
		$qr_url = $result["code_url"];
		$this->assign('gen_qr_url', $this->_buildWeixinQrUrl($qr_url));
		$this->assign('qr_url', buildUrl('Home/Weixin/genQrCode'));
		$this->assign('qr_data', $qr_url);
		$this->display('weixin');
	}

	public function genQrCode(){
		import('@.Util.phpqrcode.phpqrcode');
		$url = urldecode($_GET["data"]);
		\QRcode::png($url);
	}
	
	public function receiveNotifyResult(){
		$verify_res = $this->_verifyNotifyParams();
		ApiLog('tiger notify params:'.print_r($_REQUEST,true),'wx');
		
		if(!$verify_res){
			exit('error');
		}
		import('@.Util.Weixin.TigerWxNotify');
		$notify = new \TigerWxNotify();
		$notify->Handle(false);
	}

	private function _verifyNotifyParams(){
		$sku = $_REQUEST['sku'];
		$rand = $_REQUEST['rand'];
		$sign_params = $_REQUEST['sign'];
		$verify_sign = md5($sku.$rand.self::WEIXIN_PAYMENT_SIGN_KEY);
		if($verify_sign==$sign_params){
			return true;
		}
		return false;
	}
	
	private function _buildWeixinNotifyUrl($recharge_info){
		$sku = $recharge_info['recharge_sku'];
		$rand = mt_rand(100000, 99999999);
		$sign = md5($sku.$rand.self::WEIXIN_PAYMENT_SIGN_KEY);

		$params = array(
			'sku' 	=> $sku,
			'rand' 	=> $rand,
			'sign' 	=> $sign
			);
		
		return U('Home/Weixin/receiveNotifyResult@'.$_SERVER['HTTP_HOST'], $params);
	}

	private function _buildWeixinQrUrl($url){
		return buildUrl('Home/Weixin/genQrCode', array('data'=>$url));
	}

	private function _queryRechargeInfoBySku($recharge_sku){
		$recharge_info = D('Recharge')->getRechargeInfoBySku($recharge_sku);
		return $recharge_info;
	}

	private function _sendFailAndExit(){
		exit('fail');
	}	

}

