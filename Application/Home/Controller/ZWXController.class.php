<?php
namespace Home\Controller;
use Think\Controller;
use User\Api\Api;

class ZWXController extends Controller{

    const ALREADY_RECHARGE = 1;

	private function buildPayUrl($recharge_sku, $money, $os='', $return_url = ''){
		import('@.Util.ZWX.ZWXHandler');

		if (empty($return_url)){
			$return_url = U('Home/ZWX/rechargeSuccess@'.$_SERVER['HTTP_HOST'],'','',true);
		}

		$data = array();
		$data['out_trade_no'] 		= $recharge_sku;
		$data['body'] 				= '老虎充值';
		$data['total_fee'] 			= $money*100;
		$data['spbill_create_ip'] 	= get_client_ip(0, true);
		$data['return_url'] 		= $return_url;
		$data['trade_type'] 		= 'trade.weixin.h5pay';
		$data['notify_url']			= U('Home/ZWX/notifyResultForH5pay@'.$_SERVER['HTTP_HOST'],'','',true);
		
		//20161125 梓微星 增加字段
		$data['attach'] 			= 'bank_mch_name=福建雷火网络科技有限公司&bank_mch_id=15122950';
		if($os==OS_OF_ANDROID){
			$data['detail'] 			= 'app_name=赢球大师&package_name=co.sihe.hongmi';
		}elseif($os==OS_OF_IOS){
			$data['detail'] 			= 'app_name=赢球大师&bundle_id=com.sihe.HongMi';
		}else{
			$data['detail'] 			= 'app_name=赢球大师&bundle_id=com.sihe.HongMi&package_name=co.sihe.hongmi';
		}
		
		
		$zxw_handler = new \ZWXHandler(C('ZWX_WEIXIN_CONFIG'));
		$pay_url = $zxw_handler->H5Pay($data);
		ApiLog('ZWX RECHARGE DATA:'.print_r($data,true), 'zwx_recharge');
		return $pay_url;
	}

	public function payRedirect(){
		$recharge_sku = I('recharge_sku');
		$money = I('money');
		$os = I('os');
		$uid = I('uid');
        $is_recharge = $this->_verifyIsRecharge($recharge_sku);
        if($is_recharge){
            $message = '订单已经支付，请勿重复发起支付';
            $redirect = false;
        }else{
            $redirect = $this->buildPayUrl($recharge_sku, $money, $os);

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

	public function payRedirectByH5()
	{
		$recharge_sku = I('recharge_sku');
		$money = I('money');
		$os = I('os');
		$uid = I('uid');

		$return_url = U('H5/Index/index/type/2/lack/'.$money.'/recharge_sku/'.$recharge_sku.'@'.$_SERVER['HTTP_HOST'],'','',true);
		$redirect = $this->buildPayUrl($recharge_sku, $money, $os,$return_url);
		$is_recharge = $this->_verifyIsRecharge($recharge_sku);
		if($is_recharge){
			$message = '订单已经支付，请勿重复发起支付';
			$redirect = false;
		}else {
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
		$this->display('payRedirect');
	}

	private function _verifyIsRecharge($recharge_sku){
		$recharge_info = D('Recharge')->getRechargeInfoBySku($recharge_sku);
		if($recharge_info['recharge_status'] == self::ALREADY_RECHARGE){
			return true;
		}
		return false;
	}

	public function payRedirectByQrcode(){
		$recharge_sku = I('recharge_sku');
		$money = I('money');
		$os = I('os');
		$uid = I('uid');
        $is_recharge = $this->_verifyIsRecharge($recharge_sku);
        if($is_recharge) {
            $message = '订单已经支付，请勿重复发起支付';
            $params['code_url'] = '';
            $params['prepay_id'] = '';
        }else{
            $params = $this->buildParamsForQrCode($recharge_sku, $money, $os);
            if ($params) {
                $message = '即将打开支付宝，请在支付宝中完成支付... ';
            } else {
                if ($money > 50000) {
                    $message = '充值金额超过上限，请选择其他充值方式';
                } else {
                    $message = '充值渠道异常，请联系客服！';
                }
            }
        }
	
		$this->assign('message', $message);
		$this->assign('code_url', $params['code_url']);
		$this->assign('prepay_id', $params['prepay_id']);
		$this->assign('money', $money * 100);
		$this->assign('uid', $uid);
		$this->display();
	}
	
	private function buildParamsForQrCode($recharge_sku, $money, $os=''){
		import('@.Util.ZWX.ZWXHandler');
	
		$data = array();
		$data['out_trade_no'] 		= $recharge_sku;
		$data['body'] 				= '老虎充值';
		$data['total_fee'] 			= $money*100;
		$data['spbill_create_ip'] 	= get_client_ip(0, true);
		$data['return_url'] 		= U('Home/ZWX/rechargeSuccess@'.$_SERVER['HTTP_HOST'],'','',true);
		$data['trade_type'] 		= 'trade.alipay.native';
		$data['notify_url']			= U('Home/ZWX/notifyResultForQrCode@'.$_SERVER['HTTP_HOST'],'','',true);
	
		//20161125 梓微星 增加字段
		$data['attach'] 			= 'bank_mch_name=福建虎彩网络科技有限公司&bank_mch_id=15121832';
		if($os==OS_OF_ANDROID){
			$data['detail'] 			= 'app_name=赢球大师&package_name=co.sihe.hongmi';
		}elseif($os==OS_OF_IOS){
			$data['detail'] 			= 'app_name=赢球大师&bundle_id=com.sihe.HongMi';
		}else{
			$data['detail'] 			= 'app_name=赢球大师&bundle_id=com.sihe.HongMi&package_name=co.sihe.hongmi';
		}
	
	
		$zxw_handler = new \ZWXHandler(C('ZWX_ALIPAY_CONFIG'));
		$params = $zxw_handler->buildParamsForQrCode($data);
		return $params;
	}
	
	public function rechargeSuccess(){
		$this->display('success');
	}
	
	public function notifyResultForH5pay(){
		$this->notifyResult(C('ZWX_WEIXIN_CONFIG'));
	}
	
	public function notifyResultForQrCode(){
		$this->notifyResult(C('ZWX_ALIPAY_CONFIG'));
	}

	public function notifyResult($config){
		if(empty($config)){
			$config = C('ZWX_ALIPAY_CONFIG');
		}
		import('@.Util.ZWX.ZWXHandler');
		$zxw_handler = new \ZWXHandler($config);

		$input = file_get_contents("php://input");
		$resp = $zxw_handler->parseResp($input);
		if (!empty($resp) && is_array($resp)) {
			$recharge_sku = $resp['out_trade_no'];

            M()->startTrans();
            $recharge_id = D('Recharge')->getRechargeIdBySku($recharge_sku);
            $recharge_info = D('Recharge')->getRechargeInfoOfLock($recharge_id);

			if ($recharge_info['recharge_status'] == C('RECHARGE_STATUS.PAID') ||$recharge_info['recharge_status'] == C('RECHARGE_STATUS.FAIL')) {
                M()->rollback();
				ApiLog('result already success:'.$recharge_sku, 'zwx_notify');
				echo 'SUCCESS';
				return;
			}

			if (bcmul($recharge_info['recharge_amount'], 100) != $resp['total_fee']) {
                M()->rollback();
				ApiLog('result money check fail:'.$recharge_sku.($recharge_info['recharge_amount'] * 100).'===='.$resp['total_fee'].var_export(($recharge_info['recharge_amount'] * 100), true).var_export($resp['total_fee'], true), 'zwx_notify');
				return;
			}

			if ($resp['result_code'] == 'SUCCESS' && $resp['return_code'] == 'SUCCESS') {

				$trans_result = false;


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
					ApiLog('result increse result fail:'.$recharge_sku, 'zwx_notify');
				}

				if ($trans_result) {
					M()->commit();
					A('UserCoupon')->rewardCouponForFirstRecharge($recharge_info['recharge_id']);
					ApiLog('result trans success:'.$recharge_sku, 'zwx_notify');
					echo 'SUCCESS';
					return;
				} else {
					ApiLog('result trans fail:'.$recharge_sku, 'zwx_notify');
					M()->rollback();
					return;
				}
			}else{
                M()->rollback();
                return;
            }
		} else {
			echo 'fail';
		}

	}
}