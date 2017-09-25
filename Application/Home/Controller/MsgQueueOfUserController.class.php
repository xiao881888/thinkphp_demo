<?php
namespace Home\Controller;
use Home\Util\TigerMQ\MsgQueueBase;

class MsgQueueOfUserController extends MsgQueueBase
{
    //传入主题
    public function __construct(){
        $this->producer = 'tigercai_user_register_pub';
        $this->topic = 'tigercai_user_register';
        $this->receive_redis_key = ':user';
        parent::__construct();
    }

    public function notifyUserRegister($uid){
        $user_info = D('User')->getUserInfo($uid);
        $data = array(
            'uid' => $uid,
            'user_telephone' => $user_info['user_telephone'],
            'user_name' => $user_info['user_name'],
            'user_real_name' => $user_info['user_real_name'],
            'user_sex' => $user_info['user_sex'],
            'user_register_ip' => $user_info['user_register_ip'],
            'user_register_time' => $user_info['user_register_time'],
            'user_register_device_id' => $user_info['user_register_device_id'],
            'user_login_deivce_id' => $user_info['user_login_deivce_id'],
            'user_app_package' => $user_info['user_app_package'],
            'user_app_channel_id' => $user_info['user_app_channel_id'],
            'user_app_os' => $user_info['user_app_os'],
        );
        $msgQueueOfUser = new MsgQueueOfUserController();
        $response_data = $msgQueueOfUser->send($uid,$data);
        return true;
    }

}