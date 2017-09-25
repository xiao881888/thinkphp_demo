<?php
namespace Home\Controller;
use Think\Controller;

abstract class PayController extends Controller {
    
    abstract public function dispose($id, $uid, $sign);
    abstract public function notifyUrl();
    abstract public function returnUrl();
    
    public function getRechargeParams($id, $uid, $sign) {
        $rechargeSign = getRechargeSign($id, $uid);
        $rechargeInfo = D('Recharge')->getRechargeInfo($id);
        if($rechargeSign != $sign) {
			$this->error('签名出错！');
        }
        if(!$rechargeInfo){
        	$this->error('充值订单不存在！');
        }
        
        $rechargeChannelInfo = D('RechargeChannel')->getPlatformInfo($rechargeInfo['recharge_channel_id']);
        if($rechargeChannelInfo['recharge_channel_status'] == 0) {
            $this->error('充值通道关闭！');
        }
        if($uid != $rechargeInfo['uid']){
        	$this->error('充值用户出错！');
        }
        
        $money = $rechargeInfo['recharge_amount'];
        $partnerId = $rechargeChannelInfo['recharge_channel_partner_id'];
        
        //构造要请求的参数数组，无需改动
        return array (
        	'user_id' 			=> $uid,
            "partner_id" 		=> $rechargeChannelInfo['recharge_channel_partner_id'],    // 商户ID
            "recharge_sku" 		=> $rechargeInfo['recharge_sku'],
            "recharge_name" 	=> '老虎彩票充值',
            "recharge_remark" 	=> $rechargeInfo['recharge_remark'],
            "money" 			=> $rechargeInfo['recharge_amount'],
            "notify_url" 		=> U($rechargeChannelInfo['recharge_channel_notify_url'].'@'.$_SERVER['HTTP_HOST']),
            "return_url" 		=> U($rechargeChannelInfo['recharge_channel_return_url'].'@'.$_SERVER['HTTP_HOST']),
        );
    }
    

    protected function _onRechargeSucceed($no_order, $money, $payOrderId) {

        M()->startTrans();
        $rechargeInfo = D('Recharge')->getRechargeInfoBySkuOfLock($no_order);
        
        if( bccomp($rechargeInfo['recharge_amount'], $money) != 0 ) {
            M()->rollback();
            return false;
        }
        
        if($rechargeInfo['recharge_status']==C('RECHARGE_STATUS.PAID')) {
            M()->rollback();
            return true;
        }
    
        $uid = $rechargeInfo['uid'];

    
        $updateRecharge = D('Recharge')->saveRechargeInfo($rechargeInfo['recharge_id'], C('RECHARGE_STATUS.PAID'), $payOrderId);
        $updateAccount = D('UserAccount')->increaseMoney($uid, $money, $rechargeInfo['recharge_id'], C('USER_ACCOUNT_LOG_TYPE.RECHARGE'));
    
        if($updateAccount && $updateRecharge) {
            M()->commit();
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
   
}

?>