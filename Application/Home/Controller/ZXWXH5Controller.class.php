<?php
namespace Home\Controller;
use Think\Controller;

class ZXWXH5Controller extends Controller{
	public function payRedirect(){
		if (!verifyUrlData($_GET, RECHARGE_URL_MD5_SALT)) {
			echo '非法访问！';
			exit;
		}

		$recharge_sku = $_GET['recharge_sku'];
		$money = $_GET['money'];
		$os = $_GET['os'];

		$weixin_pay_url = $this->fetchWXPayUrl($recharge_sku, $money, $os);

		$this->assign('redirect', $weixin_pay_url);

		$this->assign('message', '即将打开微信，请在微信中完成支付... ');

		$this->display();
	}

	public function notifyResult(){
		ApiLog('notify result:'.print_r($_POST, true), 'zxwx_h5');
	}

	private function fetchWXPayUrl($order_sku, $money, $os){
		import('@.Util.ZXWX.ZXWXHandler');

		$request_data = array();
		$request_data['termIp'] 	= get_client_ip(0, true);
		$request_data['orderId'] 	= $order_sku;
		$request_data['orderTime'] 	= date('YmdHis');
		$request_data['orderBody']  = '账户充值';
		$request_data['txnAmt'] 		= strval($money*100);
		$request_data['currencyType'] 	= '156';

		if ($os == OS_OF_IOS) {
			$request_data['orderDetail'] = 'app_name=赢球大师&bundle_id=com.sihe.HongMi';
		} else {
			$request_data['orderDetail'] = 'app_name=赢球大师&package_name=com.sihe.hongmi';
		}

		$zxwx_handler = new \ZXWXHandler(C('zxwx_config'));

		$resp = $zxwx_handler->fetchPayUrl($request_data);

		ApiLog('request resp:'.var_export($resp, true), 'zxwx_h5');
	}
}