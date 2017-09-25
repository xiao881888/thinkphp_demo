<?php
namespace Home\Controller;
use Home\Controller\PayController;

require_once ("lianlianpay/lib/llpay_notify.class.php");
require_once ('lianlianpay/lib/llpay_cls_json.php');
require_once ('lianlianpay/lib/llpay_submit.class.php');

class LianLianController extends PayController {
    public function dispose($id, $uid, $sign) {
        $parameter = $this->getRechargeParams($id, $uid, $sign);
  
        $lianlianParams = array(
        	'version'		=> C('LIANLIAN_CONFIG.version'),
        	'oid_partner' 	=> C('LIANLIAN_CONFIG.oid_partner'),
        	'user_id' 		=> $parameter['user_id'],
        	'app_request' 	=> C('LIANLIAN_CONFIG.app_request'),
        	'sign_type' 	=> C('LIANLIAN_CONFIG.sign_type'),
            'busi_partner' 	=> 101001,//虚拟商品销售
        	'no_order' 		=> $parameter['recharge_sku'],
        	'dt_order'		=> date('YmdHis'),
        	'name_goods'	=> $parameter['recharge_name'],
        	'info_order'	=> $parameter['recharge_remark'],
        	'money_order'	=> $parameter['money'],
        	'notify_url' 	=> $parameter['notify_url'],
        	'url_return' 	=> $parameter['return_url'],
        	'valid_order' 	=> C('LIANLIAN_CONFIG.valid_order'),
        	'risk_item'		=> $this->_getRiskItem($uid),	

//         	'id_type'=>0,			
//         	'id_no'=> '',
//         	'acct_name' => '',
// 			'flag_modify'=>'',
//         	'shareing_date'=>'',
//         	'card_no' => '',

        );
        //建立请求
        $llpaySubmit = new \LLpaySubmit(C('LIANLIAN_CONFIG'));
        $html_text = $llpaySubmit->buildRequestForm($lianlianParams, "post", "确认");
        echo $html_text;
    }
    
    public function notifyUrl() {
        $llpayNotify = new \LLpayNotify(C('LIANLIAN_CONFIG'));
        $verify_result = $llpayNotify->verifyNotify();
        if ($verify_result) {
            $rechargeSku 		= $llpayNotify->notifyResp['no_order'];           //商户订单号
            $lianlianOrderId 	= $llpayNotify->notifyResp['oid_paybill'];     //连连支付单号
            $payResult 			= $llpayNotify->notifyResp['result_pay'];       //支付结果，SUCCESS：为支付成功
            $money 				= $llpayNotify->notifyResp['money_order'];     // 支付金额
            
            if($payResult == 'SUCCESS') {
            	$result = $this->_onRechargeSucceed($rechargeSku, $money, $lianlianOrderId);
            }else{
            	$result = $this->_onRechargeFailed($rechargeSku, $lianlianOrderId, C('RECHARGE_STATUS.FAIL'));
            }
               
            if($result){
               	$this->notifyPlatform('success');
            }else{
            	$this->notifyPlatform('error');
            }
        } else {
            $this->notifyPlatform('error');
        }
    }
    
    
    public function returnUrl() {
        $llpayNotify 	= new \LLpayNotify(C('LIANLIAN_CONFIG'));
        $res_data 		= $_POST['res_data'];
        $post 			= json_decode($res_data, true);
        
        $verify_result = $llpayNotify->verifyReturn($post);    
        if($verify_result){
        	$rechargeSku 		= $post['no_order' ];        //商户订单号
        	$lianlianOrderId 	= $post['oid_paybill'];     //支付单号
        	$money 				= $post['money_order'];     //交易金额
        	$payResult 			= $post['result_pay'];       //支付结果

        	if($payResult == 'SUCCESS') {
        		$result = $this->_onRechargeSucceed($rechargeSku, $money, $lianlianOrderId);
        	}else{
        		$result = $this->_onRechargeFailed($rechargeSku, $lianlianOrderId, C('RECHARGE_STATUS.FAIL'));
        	}
        	
            if($result) {
                $this->success('充值成功！');
            }else {
                $this->error('充值失败！');
            }
        } else {
            $this->error('参数出错！');
        }
    }
    
    
    public function notifyPlatform($type='error') {
        if($type=='success') {
            exit("{'ret_code':'0000','ret_msg':'交易成功'}"); 
        } else {
            exit("{'ret_code':'9999','ret_msg':'验签失败'}");
        }
    }
    
    
    private function _getRiskItem($uid){
    	$user_info = D('User')->getUserInfo($uid);
    	$risk_item = array(
    			'frms_ware_category' 		=> '1007',//彩票商品类目：1007
    			'user_info_mercht_userno' 	=> $uid,
    			'user_info_dt_register' 	=> date('YmdHis', strtotime($user_info['user_register_time'])),
    			'user_info_full_name'		=> $user_info['user_real_name'],
    			'user_info_id_no'			=> $user_info['user_identity_card']
    	);
    	return addslashes(json_encode($risk_item));
    }
}


?>