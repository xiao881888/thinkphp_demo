<?php
namespace Admin\Controller;

class LianlianDaifuController extends \Think\Controller {

    public function notify(){
        $request = file_get_contents('php://input');
        $post = json_decode($request, true);
        ApiLog('notify:'.print_r($post, true), 'daifu_notify');

        $resp = array();
        $resp['ret_code'] = '-1';
        $resp['ret_msg']  = '未知错误';

        require_once('lianliandaifu/llpay.config.php');
        require_once('lianliandaifu/lib/llpay_notify.class.php');
        $llpay_config['sign_type'] = 'md5';

        $llpayNotify    = new \LLpayNotify($llpay_config);
        $verify_result = $llpayNotify->verifyReturn($post);
        ApiLog('verify_result:'.var_export($verify_result, true), 'daifu_notify');
        if ($verify_result || 1) {
            $withdraw_id = $post['no_order'];
            $withdraw_info = D('Withdraw')->find($withdraw_id);

            if ($withdraw_info['withdraw_status'] == WITHDRAW_STATUS_DAIFU) {
                $daifu_result = $post['result_pay'];
                if (strtoupper($daifu_result) == 'SUCCESS') {
                    $result = $this->_daifuSuccess($withdraw_info, $post);
                } elseif (strtoupper($daifu_result) == 'FAILURE') {
                    $result = $this->_daifuFaile($withdraw_info, $post);

                    $user_info = D('User')->getUserInfo($withdraw_info['uid']);
                    $message_data = array(
                        $withdraw_info['withdraw_request_time']
                        );

                    sendTemplateSMS($user_info['user_telephone'], $message_data, C('ADMIN_SMS_TEMPLTE_ID.DAIFU_FAILUE'));
                } else {

                }

                if ($result) {
                    $resp['ret_code'] = '0000';
                    $resp['ret_msg']  = '交易成功';
                }
            }
        }

        ApiLog('resp:'.print_r($resp, true), 'daifu_notify');

        echo json_encode($resp);
    }

    private function _daifuSuccess($withdraw_info, $post){
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
            'withdraw_daify_result'         => $post['result_pay'],
            'withdraw_daifu_no'             => $post['oid_paybill'],
            'withdraw_daifu_remark'         => $post['info_order'],
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

    private function _daifuFaile($withdraw_info, $post){
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
            'withdraw_daify_result'         => $post['result_pay'],
            'withdraw_daifu_no'             => $post['oid_paybill'],
            'withdraw_daifu_remark'         => $post['info_order'],
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
}
