<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;

class LargeRechargeController extends GlobalController{

    const rechargeStatusOfNoDeal = 1;
    const rechargeStatusOfAreadyLink = 2;
    const rechargeStatusOfRechargeSuccess = 3;
    const rechargeStatusOfRechargeFail = 4;

    const MSG_REQUEST_URL = 'http://push-service.tigercai.com/index.php?s=/Home/Sms/sendSms';

    public function areadyLinkUser(){
        $lra_id = I('lra_id');
        $largeRechargeInfo = D('LargeRecharge')->getInfoById($lra_id);

        if($largeRechargeInfo['lra_recharge_status'] != self::rechargeStatusOfNoDeal){
            $this->error('该充值申请单无法进行此操作，请联系管理员');
        }
        $coustom_service = D('Member')->getNickName(UID);
        $post_data['lra_coustom_service'] = $coustom_service;
        $post_data['lra_recharge_status'] = self::rechargeStatusOfAreadyLink;
        M('LargeRecharge')->where(array('lra_id'=>$lra_id))->save($post_data);
        ApiLog('areadyLinkUser操作人员uid:'.UID,'LargeRecharge');
        $this->success('该充值申请单更改状态为已联系客户');
    }

    public function rechargeSuccess(){
        $lra_id = I('lra_id');
        $largeRechargeInfo = D('LargeRecharge')->getInfoById($lra_id);

        if($largeRechargeInfo['lra_recharge_status'] != self::rechargeStatusOfAreadyLink){
            $this->error('该充值申请单无法进行此操作，请联系管理员');
        }

        $post_data['lra_recharge_status'] = self::rechargeStatusOfRechargeSuccess;
        M('LargeRecharge')->where(array('lra_id'=>$lra_id))->save($post_data);
        ApiLog('rechargeSuccess操作人员uid:'.UID,'LargeRecharge');

        //$this->_sendMsg($largeRechargeInfo);

        $this->success('该充值申请单更改状态为充值成功',U('index'));
    }

    private function _sendMsg($largeRechargeInfo){
        $telephone = array($largeRechargeInfo['lra_contacts_tel']);
        $data[] = $largeRechargeInfo['lra_recharge_createtime'];
        $send_data['phone'] = json_encode($telephone);
        $send_data['temp_type'] = 120242;
        $send_data['datas'] = json_encode($data);
        ApiLog('$send_data:' . print_r($send_data, true), 'sms_test');
        $result = curl_post(self::MSG_REQUEST_URL,$send_data);
        ApiLog('sms:' . print_r($result, true) . '===' . $telephone . '==' . print_r($data,true) . '===' . $data['temp_type'], 'sms_test');
    }

    public function rechargeFail(){
        $lra_id = I('lra_id');
        $largeRechargeInfo = D('LargeRecharge')->getInfoById($lra_id);

        if($largeRechargeInfo['lra_recharge_status'] != self::rechargeStatusOfAreadyLink){
            $this->error('该充值申请单无法进行此操作，请联系管理员');
        }

        $post_data['lra_recharge_status'] = self::rechargeStatusOfRechargeFail;
        M('LargeRecharge')->where(array('lra_id'=>$lra_id))->save($post_data);
        ApiLog('rechargeFail操作人员uid:'.UID,'LargeRecharge');
        $this->success('该充值申请单更改状态为充值失败',U('index'));
    }


}