<?php
namespace Home\Controller;
use Think\Controller;
require_once ("lianlianpay/lib/llpay_notify.class.php");
require_once ('lianlianpay/lib/llpay_submit.class.php');
class LianLianSdkController extends Controller{
    public function getParams($rechange_id, $user_id){
        $recharge_info = D('Recharge')->getRechargeInfo($rechange_id);
        $recharge_channel = D('RechargeChannel')->getPlatformInfo($recharge_info['recharge_channel_id']);
        if($recharge_channel['recharge_channel_status'] == 0) {
            $this->error('充值通道关闭！');
        }
        $create_time = $recharge_info['recharge_create_time'];
        $lianlian_params = array(
            'oid_partner'       => C('LIANLIAN_CONFIG.oid_partner'),
            'sign_type'         => C('LIANLIAN_CONFIG.sign_type'),
            'dt_order'          => date('YmdHis', strtotime($create_time)),
            'no_order'          => $recharge_info['recharge_sku'],
            'busi_partner'      => 101001,
            'name_goods'        => '账户充值',
            'money_order'       => $recharge_info['recharge_amount'],
            'notify_url'        => U($recharge_channel['recharge_channel_notify_url'].'@'.$_SERVER['HTTP_HOST']),
            'risk_item'		    => $this->_getRiskItem($user_id),
//             'user_id' 		    => $user_id, 
          
        );
		        ApiLog('lianlian_params:'.print_r($lianlian_params,true), 'lianlian');

        $llpaySubmit = new \LLpaySubmit(C('LIANLIAN_CONFIG'));
        $params = $llpaySubmit->buildRequestPara($lianlian_params);
        
        $params_list = json_decode($params, true);
        $params_list['user_id']         = $user_id;

        return $params_list;
    }
    
    public function notifyUrl(){
        $request = file_get_contents('php://input');
        $post = json_decode($request, true);
        $llpayNotify    = new \LLpayNotify(C('LIANLIAN_CONFIG'));
        $verify_result = $llpayNotify->verifyReturn($post);
        ApiLog('verfify_result:'.$verify_result.'==='.print_r($post,true), 'lianlian');
        if($verify_result){
            if($post['result_pay'] == 'SUCCESS') {
                $result = $this->_onRechargeSucceed($post['no_order'], $post['money_order'], $post['oid_paybill']);
            }else{
                $result = $this->_onRechargeFailed($post['no_order'], $post['oid_paybill'], C('RECHARGE_STATUS.FAIL'));
            }
             
            if($result){
               	$this->notifyPlatform('success');
            }else{
            	$this->notifyPlatform('error');
            }
        }else{
            $this->error('sign error');
        }
        
    }
    
    private function _getRiskItem($uid){
        $user_info = D('User')->getUserInfo($uid);
    
        $risk_item = array(
            'frms_ware_category' 		=> '1007',//彩票商品类目：1007
            'user_info_mercht_userno' 	=> $uid,
            'user_info_dt_register' 	=> date('YmdHis', strtotime($user_info['user_register_time'])),
            'user_info_full_name'       => $user_info['user_real_name'],
            'user_info_id_no'           => $user_info['user_identity_card'],
        );
       
        $risk_item = array_filter($risk_item);
        return addslashes(json_encode($risk_item));
    }
    
    protected function _onRechargeSucceed($no_order, $money, $payOrderId) {
        $rechargeInfo = D('Recharge')->getRechargeInfoBySku($no_order);
        if( bccomp($rechargeInfo['recharge_amount'], $money) != 0 ) {
            return false;
        }
    
        if($rechargeInfo['recharge_status']==C('RECHARGE_STATUS.PAID')) {
            return true;
        }
    
        $uid = $rechargeInfo['uid'];
        M()->startTrans();
    
        $updateRecharge = D('Recharge')->saveRechargeInfo($rechargeInfo['recharge_id'], C('RECHARGE_STATUS.PAID'), $payOrderId);
        $updateAccount = D('UserAccount')->increaseMoney($uid, $money, $rechargeInfo['recharge_id'], C('USER_ACCOUNT_LOG_TYPE.RECHARGE'));
        if($updateAccount && $updateRecharge) {
            M()->commit();
            A('UserCoupon')->rewardCouponForFirstRecharge($rechargeInfo['recharge_id']);
            return true;
        } else {
            M()->rollback();
            return false;
        }
    }
    
    
    protected function _onRechargeFailed($rechargeSku, $payOrderId, $status) {
        $rechargeInfo = D('Recharge')->getRechargeInfoBySku($rechargeSku);
        M()->startTrans();
        $updateRecharge = D('Recharge')->saveRechargeInfo($rechargeInfo['recharge_id'], $status, $payOrderId);
        if($updateRecharge) {
            M()->commit();
            return true;
        } else {
            M()->rollback();
            return false;
        }
    }
    
    public function notifyPlatform($type='error') {
        if($type=='success') {
            exit("{'ret_code':'0000','ret_msg':'交易成功'}");
        } else {
            exit("{'ret_code':'9999','ret_msg':'验签失败'}");
        }
    }
}