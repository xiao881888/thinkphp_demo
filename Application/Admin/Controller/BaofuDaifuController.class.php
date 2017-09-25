<?php
namespace Admin\Controller;

class BaofuDaifuController extends \Think\Controller {

    public function update(){
        $now = time();
        $withdraw_request_time_rang_end = date('Y-m-d H:i:s', $now-60*10);
        
        $map = array(
            'withdraw_status'           => WITHDRAW_STATUS_DAIFU,
            'withdraw_daifu_channel'    => WITHDRAW_DAIFU_CHANNEL_BAOFU,
            'withdraw_daifu_apply_time' => array('elt', $withdraw_request_time_rang_end),
            );
        
        $all_withdraws = D('Withdraw')->where($map)->select();
        $success = $faile = 0;
        if (!empty($all_withdraws)) {
            $withdraw_arr  = array_chunk($all_withdraws, 5);
            foreach ($withdraw_arr as $withdraws) {
                $resp = $this->_requestDaifuByBaofu($withdraws);
                
                if ($resp && is_array($resp) && $resp['trans_content']['trans_head']['return_code'] == '0000') {
                    $trans = $resp['trans_content']['trans_reqDatas'][0]['trans_reqData'];
                    if (isset($trans['trans_orderid'])) {
                        $trans = array($trans);
                    }
                    foreach ($trans as $tran) {
                        if ($tran['state'] == 1) {
                            $withdraw_info = D('Withdraw')->find($tran['trans_no']);
                            if ($this->_daifuSuccess($withdraw_info, $tran)) {
                                $success++;
                            } else {
                                $faile++;
                            }
                        } elseif ($tran['state'] == -1) {
                            $withdraw_info = D('Withdraw')->find($tran['trans_no']);
                            if ($this->_daifuFaile($withdraw_info, $tran)) {
                                $success++;

                                $user_info = D('User')->getUserInfo($withdraw_info['uid']);
                                $message_data = array(
                                    $withdraw_info['withdraw_request_time']
                                    );

                                sendTemplateSMS($user_info['user_telephone'], $message_data, C('ADMIN_SMS_TEMPLTE_ID.DAIFU_FAILUE'));
                            } else {
                                $faile++;
                            }
                        }
                    }
                } else {
                    $faile += count($withdraws);
                }
            }
        }

        echo "查询到数据".count($all_withdraws)."条，处理完成{$success}条，处理失败{$faile}条";
    }

    private function _daifuSuccess($withdraw_info, $tran){
        if ($withdraw_info['withdraw_status'] != WITHDRAW_STATUS_DAIFU) {
            return false;
        }

        if (!$this->_verifyWithdrawAmount($withdraw_info)) {
            return false;
        }
        
        $result = false;

        M()->startTrans();
        $withdraw_data = array(
            'withdraw_id'       => $withdraw_info['withdraw_id'],
            'withdraw_pay_time' => getCurrentTime(),
            'withdraw_status'   => WITHDRAW_STATUS_PAID,
            'withdraw_modify_time'          => getCurrentTime(),
            'withdraw_daify_receive_time'   => getCurrentTime(),
            'withdraw_daify_result'         => 'SUCCESS',
            'withdraw_daifu_remark'         => $tran['trans_remark'],
            );
        $update_status = D('Withdraw')->save($withdraw_data);
        if ($update_status) {
            $update_user_account = D('UserAccount')->deductFrozenBalance($withdraw_info['uid'], $withdraw_info['withdraw_amount'], $withdraw_info['withdraw_id']);
            if ($update_user_account) {
                $result = true;
            }
        }

        if ($result) {
            M()->commit();
        } else {
            M()->rollback();
        }

        return $result;
    }

    private function _daifuFaile($withdraw_info, $tran){
        if ($withdraw_info['withdraw_status'] != WITHDRAW_STATUS_DAIFU) {
            return false;
        }

        if (!$this->_verifyWithdrawAmount($withdraw_info)) {
            return false;
        }
        
        $result = false;

        M()->startTrans();
        $withdraw_data = array(
            'withdraw_id'       => $withdraw_info['withdraw_id'],
            'withdraw_pay_time' => getCurrentTime(),
            'withdraw_status'   => WITHDRAW_STATUS_REVOKE,
            'withdraw_modify_time'          => getCurrentTime(),
            'withdraw_daify_receive_time'   => getCurrentTime(),
            'withdraw_daify_result'         => 'FAILURE',
            'withdraw_daifu_remark'         => $tran['trans_remark'],
            );
        $update_status = D('Withdraw')->save($withdraw_data);
        if ($update_status) {
            $update_user_account = D('UserAccount')->unfreeze($withdraw_info['uid'], $withdraw_info['withdraw_amount'], $withdraw_info['withdraw_id']);
            if ($update_user_account) {
                $result = true;
            }
        }

        if ($result) {
            M()->commit();
        } else {
            M()->rollback();
        }

        return $result;
    }

    private function _verifyWithdrawAmount($withdraw_info){
        $uid             = $withdraw_info['uid'];
        $withdraw_amount = $withdraw_info['withdraw_amount'];
        
        $user_account_info   = D('UserAccount')->getUserAccountInfo($uid);
        $user_frozen_balance = $user_account_info['user_account_frozen_balance'];
        if ($withdraw_amount > $user_frozen_balance){
            return false;
        } else {
            return true;
        }
    }

    private function _requestDaifuByBaofu($withdraws){
        require_once('baofudaifu/BaofooSdk.php');

        $api_reqData = array();
        foreach ($withdraws as $withdraw) {
            $api_reqData[] = array(
                'trans_batchid' => $withdraw['withdraw_daifu_batch_id'],
                'trans_no'      => $withdraw['withdraw_id']
                );
        }

        $api_data = array(
            'trans_content' => array(
                'trans_reqDatas' => array(
                        array(
                        'trans_reqData' => $api_reqData
                        )
                    ),
                ),
            );
        $api_data = json_encode($api_data);
        
        $baofu_config = C('BAOFU_DAIFU_CONFIG');
        $baofoo_sdk = new \BaofooSdk($baofu_config['MEMBER_ID'], $baofu_config['TERMINAL_ID'], $baofu_config['DATA_TYPE'], $baofu_config['PRIVATE_KEY_PATH'], $baofu_config['PUBLIC_KEY_PATH'], $baofu_config['PRIVATE_KEY_PASSWORD']);

        $api_data_encry = $baofoo_sdk->encryptedByPrivateKey($api_data);
        $resp = $baofoo_sdk->post($api_data_encry, $baofu_config['DAIFU_UPDATE_API']);
        if ($resp) {
            $resp = $baofoo_sdk->decryptByPublicKey($resp);
            $resp = json_decode($resp, true);
            return $resp;
        } else {
            return false;
        }
    }

    public function update_select(){
        $now = time();
        $id = I('id');
        $map = array(
            'withdraw_id'           => $id,
        );

        $all_withdraws = D('Withdraw')->where($map)->select();
        $success = $faile = 0;
        if (!empty($all_withdraws)) {
            $withdraw_arr  = array_chunk($all_withdraws, 5);
            foreach ($withdraw_arr as $withdraws) {
                $resp = $this->_requestDaifuByBaofu($withdraws);


            }
        }

        echo "查询到数据".count($all_withdraws)."条，处理完成{$success}条，处理失败{$faile}条";
    }
}
