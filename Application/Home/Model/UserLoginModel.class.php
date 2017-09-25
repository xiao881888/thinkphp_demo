<?php
namespace Home\Model;
use Think\Model;

class UserLoginModel extends Model {
    
    public function saveUserLogin($uid) {
        $data = array(
            'uid' => $uid,
            'user_login_time' => getCurrentTime(),
            'user_login_ip' => get_client_ip(0,true),
            'user_login_modify_time' => getCurrentTime(),
        );
        
//       uid 是主键  return $this->add($data);
        
        $userLoginInfo = $this->getUserLoginInfo($uid);
        if($userLoginInfo) {
            $condition = $this->save($data);
        } else {
            return $this->add($data);
        }
    }
    
    
    public function getUserLoginInfo($uid) {
        $condition = array('uid'=>$uid);
        return $this->where($condition)
                    ->find();
    }
    
    
}

?>