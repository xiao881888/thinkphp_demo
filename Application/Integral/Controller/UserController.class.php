<?php
namespace Integral\Controller;
use Think\Exception;
use User\Api\Api;

class UserController extends GlobalController {

    public function getUserIntegralInfo($request_data){
        $uid = $request_data['uid'];
        if(empty($uid)){
            throw new Exception(C('ERROR_MSG.INTEGRAL_USER_NOT_EXIST'), C('ERROR_CODE.INTEGRAL_USER_NOT_EXIST') );
        }
        $user_info = D('User')->getUserInfo($uid);
        if(empty($user_info)){
            throw new Exception(C('ERROR_MSG.INTEGRAL_USER_NOT_EXIST'), C('ERROR_CODE.INTEGRAL_USER_NOT_EXIST') );
        }
        return $this->_getUserIntegralInfo($uid);
    }

    private function _getUserIntegralInfo($uid){
        $user_info = D('User')->getUserInfo($uid);
        $user_vip_info = D('VipLevel')->getVipLevelInfo($user_info['vip_level_id']);
        $next_vip_info = D('VipLevel')->getNextVipLevelInfo($user_info['vip_level_id']);
        $user_integral_info = D('UserIntegral')->getUserIntegralInfo($uid);
        $user_vip_info = $this->_getUserLevelInfo($user_vip_info);
        $vip_exp_value_config = C('VIP_EXP_VALUE_CONFIG');
        return array(
            'user_integral' => $user_integral_info['user_integral_balance'],
            'user_exp' => $user_info['user_exp_value'],
            'pre_level_exp' => $vip_exp_value_config[$user_vip_info['vip_level_id']]['PRE_EXP_VALUE'],
            'next_level_exp' => $vip_exp_value_config[$user_vip_info['vip_level_id']]['NEXT_EXP_VALUE'],
            'user_level_name' => $user_vip_info['vip_level_name'],
            'user_level_img' => $user_vip_info['vip_level_img'],
            'next_level_name' => $next_vip_info['vip_level_name'],
            'next_level_img' => $next_vip_info['vip_level_img'],
            'is_sign' => A('UserSign')->isSignToday($uid),
            'sign_days' => A('UserSign')->getSignCount($uid),
            'gift_interval' => $user_vip_info['vip_level_gift_interval'],
            'level_id' => $user_vip_info['vip_level_id'],
            'free_draw' => 1,
        );
    }

    private function _getUserLevelInfo($user_vip_info){
        if($user_vip_info['vip_level_grade'] == C('MAX_USER_LEVEL_GRADE')){
            $pre_vip_info = D('VipLevel')->getPreVipLevelInfo($user_vip_info['vip_level_id']);
            return $pre_vip_info;
        }else{
            return $user_vip_info;
        }
    }


    public function addUserInfo($request_data){
        if(empty($request_data)){
            throw new Exception(C('ERROR_MSG.DATA_IS_INVALID'), C('ERROR_CODE.DATA_IS_INVALID') );
        }
        $uid = $request_data['uid'];
        $user_info = D('User')->getUserInfo($uid);
        if(!empty($user_info)){
            throw new Exception(C('ERROR_MSG.DATA_IS_INVALID'), C('ERROR_CODE.DATA_IS_INVALID') );
        }
        $add_status = D('User')->addUserInfo($request_data);
        if(!$add_status){
            throw new Exception(C('ERROR_MSG.DATA_IS_INVALID'), C('ERROR_CODE.DATA_IS_INVALID') );
        }
        return $add_status;
    }

    public function addUserExp($uid,$add_exp){
        D('User')->updateUserVipInfo($uid,$add_exp);
        return $this->_insertUserExpLog($uid);
    }

    //插入增加经验日志
    private function _insertUserExpLog($uid){
        $user_info = D('User')->getUserInfo($uid);
        $data['uid'] = $uid;
        $data['uel_balance'] = $user_info['user_exp_value'];
        $data['operator_id'] = $uid;
        return D('UserExpLog')->insertUserExpLog($data);
    }

    public function vipCenter(){
        $userSession = I('encrypt_str', '');
        $uid 	= D('Session')->getTigerUid($userSession);
        $this->_assignUserInfo($uid);
        $this->display('vipCenter');
    }

    private function _assignUserInfo($uid){
        $user_info = D('User')->getUserInfo($uid);
        $user_integral_info = D('UserIntegral')->getUserIntegralInfo($uid);
        $vip_level_id = $user_info['vip_level_id'];
        $current_level_info = D('VipLevel')->getVipLevelInfo($vip_level_id);
        $next_level_info = D('VipLevel')->getNextVipLevelInfo($vip_level_id);


        $vip_gifts_info = D('VipGifts')->getVipGiftsInfoByLevelId($vip_level_id);

        $this->assign('current_vip_level',$current_level_info['vip_level_grade']);
        $this->assign('integral_balance',$user_integral_info['user_integral_balance']);
        $this->assign('diff_exp',$current_level_info['vip_level_min_exp']-$user_info['user_exp_value']);

        $this->_assignVgContentInfo($vip_gifts_info['vg_content']);


    }

    private function _assignVgContentInfo($vg_content){
        $gift_list = array();
        $vg_content_list = json_decode($vg_content,true);
        foreach ($vg_content_list as  $vg_content_info){
            if($vg_content_info['type'] == 1){
                $gift_list[] = array(
                    'name' => $vg_content_info['name'],
                    'num' => $vg_content_info['num'],
                    'type' => $vg_content_info['type'],
                );
            }elseif($vg_content_info['type'] == 2){
                $gift_list[] = array(
                    'name' => $vg_content_info['name'],
                    'integral' => $vg_content_info['integral'],
                    'num' => $vg_content_info['num'],
                    'type' => $vg_content_info['type'],
                );
            }
        }
        $this->assign('gift_list',$gift_list);
    }



}