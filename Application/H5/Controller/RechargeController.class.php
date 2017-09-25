<?php
namespace H5\Controller;


class RechargeController extends BaseController{

    protected static $recharge_source = 5;

    public function getPlatformList(){
        $user_info = self::getModelInstance('User')->getUserInfo($this->uid);
        $uid = $user_info['uid'];
        $platformList = self::getModelInstance('RechargeChannel')->getPlatformList(OS_OF_IOS);
        
        $result = array();
        foreach ($platformList as $platform) {
            H5Log('$platform:'.$platform['recharge_channel_name'], 'h5_rgl');

            $is_hide = $this->_checkChannelNeedToHide($platform['recharge_channel_id'], $uid, $user_info['user_telephone'], get_client_ip(0, true), OS_OF_IOS, 8, $user_info);
            if($is_hide){
                continue;
            }

            $result[] = array(
                'id' => $platform['recharge_channel_id'],
                'type' => $platform['recharge_channel_type'],
                'name' => $platform['recharge_channel_name'],
                'image' => $platform['recharge_channel_image'],
                'description' => $platform['recharge_channel_descript'],
            );
        }

        $this->response($result);
    }

    public function userRecharge() {
        $recharge_channel_id = (int)$this->input('recharge_channel_id');
        $recharge_money = (float)$this->input('money');
        if (!($recharge_money > 0) or !$recharge_channel_id){
            $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
        }

        $recharge_channel_info = self::getModelInstance('RechargeChannel')->getPlatformInfo($recharge_channel_id);
        if (empty($recharge_channel_info)){
            $this->responseError(RESPONSE_ERROR_WITHOUT_RECHARGE_CHANNEL);
        }

        $user_info = self::getModelInstance('User')->getUserInfo($this->uid);
        $recharge_sku = getRechargeSku($this->uid, $recharge_channel_id);
        $recharge_id = self::getModelInstance('Recharge')->addRecharge($this->uid, $recharge_channel_id, $recharge_money, '', $recharge_sku, static::$recharge_source);
        if (empty($recharge_id)){
            $this->responseError(RESPONSE_ERROR_UNKNOWN);
        }

        $os = getClientOS();

        $target_url = $this->_getTargetUrl($recharge_channel_id, $recharge_id, $user_info, $recharge_sku, $recharge_money, $os);

        $result = array(
            'target_url' => $target_url,
            'recharge_channel_id' => $recharge_channel_info['recharge_channel_id'],
            'recharge_channel_type' => $recharge_channel_info['recharge_channel_type'],
            'sku' => $recharge_sku,
            'money' => $recharge_money,
            'recharge_channel_name'=>$recharge_channel_info['recharge_channel_name'],
        );

        H5Log('reulst_response:'.print_r($result,true),'h5_recharge');

        $this->response($result);

    }

    public function getRechargeInfo() {
        $recharge_sku = I('get.recharge_sku',0);
        if (!$recharge_sku){
            $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
        }
        $recharge_info = self::getModelInstance('Recharge')->getRechargeInfoBySku($recharge_sku);
        if (empty($recharge_info)){
            $this->responseError(RESPONSE_ERROR_RECHARGE_NOT_EXIST);
        }

        if ($this->uid != $recharge_info['uid']){
            $this->responseError(RESPONSE_ERROR_RECHARGE_NOT_BELONG_TO_THIS_USER);
        }

        $result = array(
            'sku' => (string)$recharge_info['recharge_sku'],
            'money' => (float)$recharge_info['recharge_amount'],
            'status' => (int)$recharge_info['recharge_status'],
            'extra' => (string)$recharge_info['recharge_remark'],
        );
        H5Log('debug:recharge:'.print_r($result,true),'debug_recharge');
        $this->response($result);
    }

    private function _getTargetUrl($rechargeChannelId, $rechargeId, $user_info, $rechargeSku = '', $money = 0, $os = 0, $api = ''){
        $uid = $user_info['uid'];

        if ($rechargeChannelId == PAYMENT_CHANNEL_ID_OF_ZWX) {
            $data = array();
            $data['money'] = $money;
            $data['recharge_sku'] = $rechargeSku;
            $data['uid'] = $uid;
            $data['os'] = $os;
            $domain = $this->_getDomain();
            return U('Home/ZWX/payRedirectByH5@'.$domain, $data ,'',true);
        }

        $function = C("RECHARGE_DIPOSE.$rechargeChannelId");
        return U("$function@$_SERVER[HTTP_HOST]", array('id'=>$rechargeId, 'uid'=>$uid, 'sign'=>getRechargeSign($rechargeId,$uid)));
    }

    private function _checkChannelNeedToHide($recharge_channel_id, $uid, $phone, $ip, $os, $sdk_version, $user_info){
        if ($recharge_channel_id != PAYMENT_CHANNEL_ID_OF_ZWX) {
            return true;
        }

        return false;
    }

    private function _getDomain()
    {
        $domain = '';
        if (isset($_SERVER['SERVER_RUN_MODE']) and $_SERVER['SERVER_RUN_MODE'] == 'PRERELEASE'){
            $domain = 'prerelease-phone-api.tigercai.com';
        } else if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
            $domain = 'phone-api.tigercai.com';
        } else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
            $domain = 'test-phone-api.tigercai.com';
        }

        return $domain;
    }
}