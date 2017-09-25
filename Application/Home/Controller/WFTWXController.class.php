<?php
namespace Home\Controller;
use Think\Controller;
class WFTWXController extends Controller{

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
		$mch_id = C('WFTWX_CONFIG.MCH_ID');
		$sign_key = C('WFTWX_CONFIG.SIGN_KEY');
		$nonce_str = random_string(16);

		$data = array();
		$data['service'] 		= 'pay.weixin.wappay';
		$data['version']		= '2.0';
		$data['mch_id'] 		= $mch_id;
		$data['out_trade_no'] 	= $recharge_sku;
		$data['body'] 			= 'APP充值';
		$data['total_fee']  	= $money*100;
		$data['mch_create_ip'] 	= get_client_ip('', true);
		$data['notify_url'] 	= U('Home/WFTWX/notifyResult@'.$_SERVER['HTTP_HOST'], array(), '', true);
		$data['callback_url'] 	= U('Home/WFTWX/rechargeSuccess@'.$_SERVER['HTTP_HOST'], array(), '', true);
		$data['device_info'] 	= 'iOS_WAP';
		$data['mch_app_name'] 	= '老虎彩票';
		$data['mch_app_id'] 	= 'com.tigercai.TigerLottery';
		$data['nonce_str'] 		= $nonce_str;
		$data['sign'] 			= $this->_signParams($data, $mch_id, $sign_key);

		$this->assign('data', $data);

		$request_input = $this->fetch('request_input');

		$api = 'https://pay.swiftpass.cn/pay/gateway';

		$resp = postByCurl($api, $request_input);

		ApiLog('request data:'.print_r($data, true), 'WFTWX');
		ApiLog('request_input:'.$request_input, 'WFTWX');
		ApiLog('resp:'.$resp, 'WFTWX');

		if (!empty($resp)) {
			$resp_obj = simplexml_load_string($resp);
			$result = intval($resp_obj->result_code);
			$status = intval($resp_obj->status);

			if ($result == 0 && $status == 0) {
				$pay_url = strval($resp_obj->pay_info);

				return $pay_url;
			}
		}

		return false; 
	}	


	
	public function rechargeSuccess(){
		$this->display('success');
	}


	public function notifyResult(){
		$input = file_get_contents('php://input');
		$input_obj = simplexml_load_string($input);
		
		$input_arr = array();
		foreach ($input_obj as $key => $val) {
			$input_arr[$key] = strval($input_obj->$key);
		}

		ApiLog('resp input:'.$input, 'wftwx_notify');
		ApiLog('resp input obj:'.print_r($input_obj, true), 'wftwx_notify');
		ApiLog('resp input arr:'.print_r($input_arr, true), 'wftwx_notify');

		$mch_id = C('WFTWX_CONFIG.MCH_ID');
		$sign_key = C('WFTWX_CONFIG.SIGN_KEY');
		if($input_arr['sign'] != $this->_signParams($input_arr, $mch_id, $sign_key)){
			echo 'fail';
			return;
		}

		$recharge_sku = $input_arr['out_trade_no'];
		$recharge_info = D('Recharge')->getRechargeInfoBySku($recharge_sku);

		if ($recharge_info['recharge_status'] == C('RECHARGE_STATUS.PAID') ||$recharge_info['recharge_status'] == C('RECHARGE_STATUS.FAIL')) {
			ApiLog('result already success:'.$recharge_sku, 'wftwx_notify');
			echo 'success';
			return;
		}

		if (bcmul($recharge_info['recharge_amount'], 100) != $input_arr['total_fee']) {
			ApiLog('result money check fail:'.$recharge_sku.($recharge_info['recharge_amount'] * 100).'===='.$input_arr['total_fee'].var_export(($recharge_info['recharge_amount'] * 100), true), 'wftwx_notify');
			return;
		}

		if ($input_arr['pay_result'] == 0 && $input_arr['result_code'] == 0 && $input_arr['status'] == 0) {

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
				ApiLog('result increse result fail:'.$recharge_sku, 'wftwx_notify');
			}

			if ($trans_result) {
				M()->commit();
				A('UserCoupon')->rewardCouponForFirstRecharge($recharge_info['recharge_id']);
				ApiLog('result trans success:'.$recharge_sku, 'wftwx_notify');
				echo 'SUCCESS';
				return;
			} else {
				ApiLog('result trans fail:'.$recharge_sku, 'wftwx_notify');
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


	private function _signParams($data, $mch_id, $sign_key){
		$sign_str = '';

		ksort($data);
		foreach ($data as $key => $val) {
			if ($val != '' && $key != 'sign') {
				$sign_str .= $key.'='.$val.'&';
			}
		}
		$sign_str .= 'key='.$sign_key;
		$sign = strtoupper(md5($sign_str));

		return $sign;
	}

}