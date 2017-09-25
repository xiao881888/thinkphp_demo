<?php 
namespace Integral\Model;
use Think\Model;

class UserModel extends Model {

    const ENABLE_STATUS = 1;

    public function getUserInfo($uid){
        $where['uid'] = $uid;
        return $this->where($where)->find();
    }

    public function addUserInfo($user_info = array()){
        $user['uid'] = empty($user_info['uid']) ? 0 : $user_info['uid'];
        $user['user_telephone'] = $user_info['user_telephone'];
        $user['vip_level_id'] = empty($user_info['user_vip_level']) ? 1 : $user_info['user_vip_level'];
        $user['user_exp_value'] = $this->_getUserExp($user_info);
        $user['user_status'] = self::ENABLE_STATUS;
        $user['user_createtime'] = getCurrentTime();
        $user['user_vip_modifytime'] = getCurrentTime();
        return $this->add($user);
    }

    private function _getUserExp($user_info){
        if($user_info['user_exp_value'] >= C('MAX_USER_EXP_VALUE')){
            return C('MAX_USER_EXP_VALUE');
        }else{
            return empty($user_info['user_exp_value']) ? 0 : $user_info['user_exp_value'];
        }
    }

    public function updateUserVipInfo($uid,$add_exp){
        $where['uid'] = $uid;
        $add_status = $this->where($where)->setInc('user_exp_value',$add_exp);
        $user_info = $this->getUserInfo($uid);
        $this->_dealMaxExp($user_info);
        $new_vip_level = D('VipLevel')->getVipLevelId($user_info['user_exp_value']);
        if($new_vip_level != $user_info['vip_level_id']){
            $save_data['vip_level_id'] = $new_vip_level;
            $save_data['user_vip_modifytime'] = getCurrentTime();
            $this->where($where)->save($save_data);
        }
        return $add_status;
    }

    private function _dealMaxExp($user_info){
        if($user_info['user_exp_value'] > C('MAX_USER_EXP_VALUE')){
            $where['uid'] = $user_info['uid'];
            $this->where($where)->setField('user_exp_value',C('MAX_USER_EXP_VALUE'));
        }
    }

}



