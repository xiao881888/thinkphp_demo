<?php 
namespace Integral\Model;
use Think\Model;

class UserSignModel extends Model {

    public function addUserSignRecord($uid){
        $user_sign['uid'] = $uid;
        $user_sign['user_sign_sign_count'] = 0;
        $user_sign['user_sign_total_count'] = 0;
        $user_sign['user_sign_createtime'] = getCurrentTime();
        return $this->add($user_sign);
    }

    public function saveUserSignRecord($uid,$data){
        $where['uid'] = $uid;
        $user_sign['user_sign_sign_count'] = $data['user_sign_sign_count'];
        $user_sign['user_sign_total_count'] = $data['user_sign_total_count'];
        $user_sign['user_sign_modifytime'] = getCurrentTime();
        $user_sign['user_sign_signtime'] = $data['user_sign_signtime'];
        $user_sign['user_sign_last_signtime'] = $data['user_sign_last_signtime'];
        return $this->where($where)->save($user_sign);
    }

    public function getUserSignInfoByUid($uid){
        $where['uid'] = $uid;
        return $this->where($where)->find();
    }

    

}



