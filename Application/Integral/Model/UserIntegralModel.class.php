<?php 
namespace Integral\Model;
use Think\Model;

class UserIntegralModel extends Model {

    public function getUserIntegralInfo($uid){
        $where['uid'] = $uid;
        return $this->where($where)->find();
    }

    public function getUserIntegralBalance($uid){
        $where['uid'] = $uid;
        return $this->where($where)->getField('user_integral_balance');
    }

    public function addUserIntergralRecord($data){
        $add_data['uid'] = $data['uid'];
        $add_data['user_integral_balance'] = empty($data['user_integral_balance']) ? 0 : $data['user_integral_balance'];
        $add_data['user_integral_amount'] = empty($data['user_integral_amount']) ? 0 : $data['user_integral_amount'];
        $add_data['user_integral_createtime'] = getCurrentTime();
        $add_data['user_integral_modifytime'] = getCurrentTime();
        $add_data['user_integral_status'] = empty($data['user_integral_status']) ? 1 : $data['user_integral_status'];
        return $this->add($add_data);
    }

    //TODO is_add boolean
    public function updateUserIntergral($uid,$update_integral,$is_add = true){
        $save_data = array();
        $where['uid'] = $uid;
        $user_integral = $this->lock(true)->where($where)->find();
        if($is_add){
            $save_data['user_integral_balance'] = $user_integral['user_integral_balance'] + $update_integral;
            $save_data['user_integral_amount'] = $user_integral['user_integral_amount'] + $update_integral;
            $save_data['user_integral_modifytime'] = getCurrentTime();
        }else{
            if($user_integral['user_integral_balance'] < $update_integral){
                return false;
            }
            $save_data['user_integral_balance'] = $user_integral['user_integral_balance'] - $update_integral;
            $save_data['user_integral_modifytime'] = getCurrentTime();
        }
        return $this->where($where)->save($save_data);
    }

    private function updateUserVipLevel($uid){
        $user_vip_level = 0;
        $where['uid'] = $uid;
        $user_vip_level_list = C('USER_VIP_LEVEL');
        $user_integral_amount = $this->where($where)->getField('user_integral_amount');
        foreach($user_vip_level_list as $vip_level => $integral){
            if($user_integral_amount <= $integral){
                $user_vip_level = $vip_level;
            }else{
                $user_vip_level = $vip_level + 1;
            }
        }
        return $this->where($where)->save(array('user_integral_vip_level' => $user_vip_level));
    }



    

}



