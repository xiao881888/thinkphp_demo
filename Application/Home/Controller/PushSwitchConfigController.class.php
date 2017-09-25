<?php

namespace Home\Controller;
/*
 * ios端
 */
class PushSwitchConfigController extends GlobalController
{

    public function savePushSwitchConfig($api)
    {
        $device_token = $api->device_token;
        if(empty($device_token)){
            $device_id = D('Session')->getDeviceId($api->session);
            $device_info = D('PushDevice')->getDeviceInfoByDeviceId($device_id);
            $device_token = $device_info['pd_device_token'];
        }
        $push_config_arr = $api->config;
        if(empty($device_token)){
            \AppException::throwException(C('ERROR_CODE.DEVICE_TOKEN_IS_NULL'), '');
        }

        if(empty($push_config_arr)){
            \AppException::throwException(C('ERROR_CODE.POST_CONFIG_INVALID'), '');
        }

        $this->_savePushSwitchConfigInfo($device_token,$push_config_arr);

        return array( 'result' => '',
                      'code'   => C('ERROR_CODE.SUCCESS') );
    }

    private function _savePushSwitchConfigInfo($device_token,$push_config_arr)
    {
        foreach ($push_config_arr as $key1 => $push_config_infos) {
            foreach ($push_config_infos as $key2 => $push_config_info) {
                $push_switch_config_type = $this->_getPushSwitchConfigType($key1.':'.$key2);
                if(empty($push_switch_config_type)){
                    \AppException::throwException(C('ERROR_CODE.POST_CONFIG_INVALID'));
                }
                $status = $push_config_info['status'];
                $forbid_time = $push_config_info['forbid_time'];
                $is_add = D('PushSwitchConfig')->isAdd($device_token,$push_switch_config_type);
                if($is_add){
                    D('PushSwitchConfig')->saveInfo($device_token,$push_switch_config_type,$status,$forbid_time);
                }else{
                    D('PushSwitchConfig')->addInfo($device_token,$push_switch_config_type,$status,$forbid_time);
                }
            }
        }
    }

    private function _getPushSwitchConfigType($key)
    {
        $api_push_switch_config = C('API_PUSH_SWITCH_CONFIG');
        foreach($api_push_switch_config as $type => $value){
            if($value == $key){
                return $type;
            }
        }
    }

    public function getPushSwitchConfig($api)
    {
        $device_token = $api->device_token;
        if(empty($device_token)){
            $device_id = D('Session')->getDeviceId($api->session);
            $device_info = D('PushDevice')->getDeviceInfoByDeviceId($device_id);
            $device_token = $device_info['pd_device_token'];
        }
        if(empty($device_token)){
            \AppException::throwException(C('ERROR_CODE.DEVICE_TOKEN_IS_NULL'), '');
        }
        $push_switch_list = D('PushSwitchConfig')->getStatusOfOn($device_token);
        $config = $this->_formatPushConfig($push_switch_list);
        return array( 'result' => $config,
                      'code'   => C('ERROR_CODE.SUCCESS') );
    }

    private function _formatPushConfig($push_switch_list)
    {
        foreach ($push_switch_list as $push_switch_info) {
            $push_switch_type = $push_switch_info['pst_id'];
            $push_switch_status = $push_switch_info['psc_status'];
            $not_disturb_time = emptyToStr($push_switch_info['psc_not_disturb_time']);
            $config_key_arr = $this->_getConfigKey($push_switch_type);
            if (!empty($config_key_arr)) {
                $config[$config_key_arr[0]][$config_key_arr[1]]['status'] = $push_switch_status;
                $config[$config_key_arr[0]][$config_key_arr[1]]['forbid_time'] = $not_disturb_time;
            }
        }
        if(!empty($config)){
            $data['config'] = $config;
        }
        return $data;

    }

    private function _getConfigKey($push_switch_type)
    {
        $config_key_arr = array();
        $api_push_switch_config = C('API_PUSH_SWITCH_CONFIG');
        $config_value = $api_push_switch_config[$push_switch_type];
        if (empty($config_value)) {
            return $config_key_arr;
        }
        $config_key_arr = explode(':', $config_value);
        return $config_key_arr;
    }

}