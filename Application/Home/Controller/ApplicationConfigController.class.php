<?php

namespace Home\Controller;

class ApplicationConfigController extends GlobalController{

    public function getAppConfigInfo($api){
        $can_register = $this->_getCanRegister($api);
        $smart_follow = $this->_getSmartFollow($api);
        $recharge = $this->_getRecharge($api);
        $withdraw = $this->_getWithdraw($api);
        $cobet_purchase =  $this->_getCobetPurchase($api);
        $is_sale = $this->_isSale($api);
        return array(	'result' => array(
                'can_register'=>$can_register,
                'smart_follow'=>$smart_follow,
                'recharge'=>$recharge,
                'withdraw'=>$withdraw,
                'copurchase'=>$cobet_purchase,
                'is_sale'=>$is_sale,
            ),
            'code'   => C('ERROR_CODE.SUCCESS')
        );
    }

    private function _isSale($api){
        $is_sale = 1;
        $is_in_audit = $this->_getInAudit($api);
        if($is_in_audit){
            $is_sale = 0;
        }
        return $is_sale;
    }

    private function _getRecharge($api){
        $recharge = 1;
        $is_in_audit = $this->_getInAudit($api);
        if($is_in_audit){
            $recharge = 0;
        }
        return $recharge;
    }

    private function _getCobetPurchase($api){
        $cobet_purchase = 1;
        $is_in_audit = $this->_getInAudit($api);
        if($is_in_audit){
            $cobet_purchase = 0;
        }
        return $cobet_purchase;
    }

    private function _getWithdraw($api){
        $withdraw = 1;
        $is_in_audit = $this->_getInAudit($api);
        if($is_in_audit){
            $withdraw = 0;
        }
        return $withdraw;
    }

    private function _getCanRegister($api){
        /*$can_register = 0;
        $audit_version = D('IssueSwitch')->getRegisterSwitchOnList();
        if ($api->os == OS_OF_IOS && array_key_exists($api->channel_id, $audit_version) && $api->app_version === $audit_version[$api->channel_id] ) {
            $can_register = 1;
        }*/

        $can_register = 1;

        if($api->channel_id == 13 || $api->channel_id == 'official' || $api->channel_id == '13'){
            $can_register = 0;
        }

        return $can_register;
    }

    private function _getSmartFollow($api){
        $smart_follow = 1;
        $app_id = getRequestAppId($api->bundleId);
        $app_version = $api->app_version;
        if($app_id == C('APP_ID_LIST.NEW')){
            $smart_follow = 1;
        }

        if($api->os == OS_OF_ANDROID){
            $smart_follow = 1;
        }
        return $smart_follow;
    }

    private function _getInAudit($api){
        $audit_version = D('IssueSwitch')->getSwitchOffList();
        if ($api->os == OS_OF_ANDROID && array_key_exists($api->channel_id, $audit_version) && $api->app_version === $audit_version[$api->channel_id] ) {
            $is_in_audit = true;
        } else {
            $is_in_audit = false;
        }
        return $is_in_audit;
    }

}

