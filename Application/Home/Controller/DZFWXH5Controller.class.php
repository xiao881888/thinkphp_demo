<?php
namespace Home\Controller;
use Think\Controller;
class DZFWXH5Controller extends Controller{

    const ALREADY_RECHARGE = 1;

	public function payRedirect(){
		$recharge_sku 	= I('recharge_sku');
		$money 			= I('money');
		$os 			= I('os');
		$uid 			= I('uid');

        $alread_finished = $this->_verifyIsRecharge($recharge_sku);
        if($alread_finished){
            $message = '订单已经支付，请勿重复发起支付';
            $redirect = false;
        }else{
            $redirect = $this->buildPayUrl($recharge_sku, $money, $uid);

            if ($redirect) {
                $message = '即将打开微信，请在微信中完成支付... ';
            } else {
                if ($money > 50000) {
                    $message = '充值金额超过上限，请选择其他充值方式';
                } else {
                    $message = '充值渠道异常，请联系客服！';
                }
            }
        }


        $this->assign('message', $message);
		$this->assign('redirect', $redirect);
		$this->assign('uid', $uid);
		$this->display();
	}

	private function buildPayUrl($recharge_sku, $money, $uid){
		$app_id = C('DZFWXH5_CONFIG.APP_ID');
		$app_key = C('DZFWXH5_CONFIG.APP_KEY');
		$goods_id = C('DZFWXH5_CONFIG.GOODS_ID');

		$data = array();
		$data['appid'] 		= $app_id;
		$data['goodsid'] 	= $goods_id;
		$data['goodsname'] 	= '产品充值';
		$data['pcorderid'] 	= $recharge_sku;
		$data['money'] 		= bcmul($money, 100);
		$data['currency'] 	= 'CHY';
		$data['pcuserid'] 	= $uid;
		$data['pcprivateinfo'] = 'test';
		$data['notifyurl'] 	= U('Home/DZFWXH5/notifyResult@'.$_SERVER['HTTP_HOST'],'','',true);

		$sign_type = 'MD5';
		$sign = $this->_signData($data, $app_key);
		ApiLog('sign:'.$sign, 'dzfwxh5');
		ApiLog('data:'.print_r($data, true), 'dzfwxh5');
		$prepay_resp = $this->_prepay($data, $sign, $sign_type);
		ApiLog('resp:'.print_r($prepay_resp, true), 'dzfwxh5');

		if (!empty($prepay_resp) && is_array($prepay_resp)) {
			$pay_url = $this->_buildPayUrl($app_id, $app_key, $prepay_resp['transid']);

			return $pay_url;	
		} else {
			return false;
		}

	}	


	
	public function rechargeSuccess(){
		$this->display('success');
	}


	public function notifyResult(){
		$transdata 	= urldecode(I('transdata'));
		$sign 		= urldecode(I('sign'));
		$sign_type 	= I('signtype');

		$transdata_arr = json_decode($transdata, true);
		$app_key = C('DZFWXH5_CONFIG.APP_KEY');
		if ($this->_signData($transdata_arr, $app_key) != $sign) {
			ApiLog('sign error:'.print_r($transdata_arr, true), 'dzfwxh5');
			echo 'faile';
			return;
		}



		$recharge_sku = $transdata_arr['cporderid'];
		$recharge_info = D('Recharge')->getRechargeInfoBySku($recharge_sku);

		if ($recharge_info['recharge_status'] == C('RECHARGE_STATUS.PAID') ||$recharge_info['recharge_status'] == C('RECHARGE_STATUS.FAIL')) {
			ApiLog('result already success:'.$recharge_sku, 'dzfwxh5_notify');
			echo 'SUCCESS';
			return;
		}

		if (bcmul($recharge_info['recharge_amount'], 100) != $transdata_arr['money']) {
			ApiLog('result money check fail:'.$recharge_sku.($recharge_info['recharge_amount'] * 100).'===='.$transdata_arr['money'].var_export(($recharge_info['recharge_amount'] * 100), true), 'dzfwxh5_notify');
			return;
		}

		if ($transdata_arr['result'] == 1) {

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
				ApiLog('result increse result fail:'.$recharge_sku, 'dzfwxh5_notify');
			}

			if ($trans_result) {
				M()->commit();
				A('UserCoupon')->rewardCouponForFirstRecharge($recharge_info['recharge_id']);
				ApiLog('result trans success:'.$recharge_sku, 'dzfwxh5_notify');
				echo 'SUCCESS';
				return;
			} else {
				ApiLog('result trans fail:'.$recharge_sku, 'dzfwxh5_notify');
				M()->rollback();
				return;
			}
		}

	}

	private function _verifyIsRecharge($recharge_sku){
		$recharge_info = D('Recharge')->getRechargeInfoBySku($recharge_sku);
		if($recharge_info['recharge_status'] == self::ALREADY_RECHARGE){
			return true;
		}
		return false;
	}

	private function _signData($data, $app_key){
		$tmp_arr = array();
		foreach ($data as $key => $val) {
			$tmp_arr[] = $key."=".$val;
		}

		sort($tmp_arr);

		$data_str = implode('&', $tmp_arr);
		$data_str .= '&key='.$app_key;
		$sign = md5($data_str);
		ApiLog('sign data:'.print_r($data, true).PHP_EOL.$data_str.PHP_EOL.$sign, 'dzfwxh5');

		return $sign;
	}	

	private function _buildPayUrl($app, $app_key, $trans_id){
		$data = array();
		$data['app'] = $app;
		$data['transid'] = $trans_id;
		$data['backurl'] = U('Home/DZFWXH5/rechargeSuccess@'.$_SERVER['HTTP_HOST'], '', '', true);

		$sign = $this->_signData($data, $app_key);

		$pay_api = 'https://payh5.bbnpay.com/browserh5/paymobile.php';
		
		$pay_url = $pay_api.'?data='.urlencode(json_encode($data)).'&sign='.urlencode($sign).'&signtype=MD5';
		ApiLog('pay url:'.$pay_url, 'dzfwxh5');
		return $pay_url;

	}

	private function _prepay($data, $sign, $sign_type){
		$prepay_api  = 'https://payh5.bbnpay.com/cpapi/place_order.php';

		$params = array();
		$params['transdata'] 	= urlencode(json_encode($data));
		$params['sign'] 		= urlencode($sign);
		$params['signtype'] 	= 'MD5';

		$prepay_resp = curl_post($prepay_api, $params);
		
		if (!empty($prepay_resp)) {
			parse_str($prepay_resp, $prepay_resp_arr);
			
			if (is_array($prepay_resp_arr) && !empty($prepay_resp_arr)) {
				$trans_data = json_decode($prepay_resp_arr['transdata'], true);
				
				if ($trans_data['code'] == '200' && !empty($trans_data['transid'])) {
					return $trans_data;
				} 
			}

		}

		return false;
	}
}