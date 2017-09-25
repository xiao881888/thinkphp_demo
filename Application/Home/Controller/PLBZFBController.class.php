<?php
namespace Home\Controller;
use THink\Controller;

class PLBZFBController extends Controller{

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
            $out_order_info = $this->createOrder($recharge_sku, $money, $uid);
            if ($out_order_info) {
            	$redirect = $this->buildRedirect($out_order_info);
            }


            if ($redirect) {
                $message = '即将跳转支付宝，请在支付宝中完成支付... ';
            } else {
                $message = '充值渠道异常，请联系客服！';
            }
        }


        $this->assign('message', $message);
		$this->assign('redirect', $redirect);
		$this->assign('uid', $uid);
		$this->display();

	}

	private function createOrder($recharge_sku, $money, $uid){
		$mch_id = C('PLBZFB_CONFIG.MCH_ID');
		$sign_key = C('PLBZFB_CONFIG.SIGN_MD5_KEY');

		$data = array();
		$data['version'] 		= '1';
		$data['appId']			= $mch_id;
		$data['outTradeNo'] 	= $recharge_sku;
		$data['payType'] 		= '11';
		$data['payAmount'] 		= $money*100;
		$data['spUno']  	= $uid;
		$data['subject'] 	= '虎彩充值';
		$data['notifyUrl'] 	= U('Home/PLBZFB/notifyResult@'.$_SERVER['HTTP_HOST'], array(), '', true);
		$data['returnUrl'] 	= U('Home/PLBZFB/rechargeSuccess@'.$_SERVER['HTTP_HOST'], array(), '', true);
		$data['signType']	= 'md5';

		$data['sign'] = $this->signData($data, $sign_key);

		$api = 'https://api.peralppay.com/api/v1/create_order';

		$resp = postByCurl($api, $data);

		ApiLog('request data:'.print_r($data, true), 'PLBZFB');
		ApiLog('resp:'.$resp, 'PLBZFB');

		if (!empty($resp)) {
			$resp_arr = json_decode($resp, true);

			if (isset($resp_arr['retCode']) && $resp_arr['retCode'] == 0) {

				return $resp_arr['data'];
			}
		}

		return false; 
	}

	public function rechargeSuccess(){
		$this->display('success');
	}	

	public function notifyResult(){
		$trans_data = $_POST['transData'];
		$trans_data_arr = json_decode($trans_data, true);

		ApiLog('resp input:'.$trans_data, 'plbzfb_notify');
		ApiLog('resp input obj:'.print_r($trans_data_arr, true), 'plbzfb_notify');

		$mch_id = C('PLBZFB_CONFIG.MCH_ID');
		$sign_key = C('PLBZFB_CONFIG.SIGN_MD5_KEY');
		$sign = $trans_data_arr['sign'];
		unset($trans_data_arr['sign']);
		if($sign != $this->signData($trans_data_arr, $sign_key)){
			ApiLog('sign faile:', 'plbzfb_notify');
			echo 'fail';
			return;
		}

		$recharge_sku = $trans_data_arr['outTradeNo'];
		$recharge_info = D('Recharge')->getRechargeInfoBySku($recharge_sku);

		if ($recharge_info['recharge_status'] == C('RECHARGE_STATUS.PAID') ||$recharge_info['recharge_status'] == C('RECHARGE_STATUS.FAIL')) {
			ApiLog('result already success:'.$recharge_sku, 'plbzfb_notify');
			echo 'success';
			return;
		}

		if (bcmul($recharge_info['recharge_amount'], 100) != $trans_data_arr['payAmount']) {
			ApiLog('result money check fail:'.$recharge_sku.($recharge_info['recharge_amount'] * 100).'===='.$trans_data_arr['total_fee'].var_export(($recharge_info['recharge_amount'] * 100), true), 'plbzfb_notify');
			return;
		}

		if ($trans_data_arr['state'] == 1) {

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
				ApiLog('result increse result fail:'.$recharge_sku, 'plbzfb_notify');
			}

			if ($trans_result) {
				M()->commit();
				A('UserCoupon')->rewardCouponForFirstRecharge($recharge_info['recharge_id']);
				ApiLog('result trans success:'.$recharge_sku, 'wftwx_notify');
				echo 'success';
				return;
			} else {
				ApiLog('result trans fail:'.$recharge_sku, 'wftwx_notify');
				M()->rollback();
				return;
			}
		}

	}


	private function signData($data, $key){
		ksort($data);
		$data_str = urldecode(http_build_query($data)).'&key='.$key;
		$sign = md5($data_str);
		ApiLog('data sign:'.$data_str.'--'.$sign, 'PLBZFB');

		return $sign;
	}

	private function buildRedirect($out_order_info){
		$get = array();
		$get['version'] = '1';
		$get['payNo'] 	= $out_order_info['payNo'];
		$get['payType'] = 11;

		$redirect_url = 'https://api.peralppay.com/api/v1/pay_exchange?'.http_build_query($get);

		return $redirect_url;
	}

	private function _verifyIsRecharge($recharge_sku){
		$recharge_info = D('Recharge')->getRechargeInfoBySku($recharge_sku);
		if($recharge_info['recharge_status'] == self::ALREADY_RECHARGE){
			return true;
		}
		return false;
	}
}