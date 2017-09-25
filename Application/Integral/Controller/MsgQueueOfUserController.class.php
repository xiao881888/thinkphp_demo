<?php
namespace Integral\Controller;
use Integral\Util\TigerMQ\MsgQueueBase;

class MsgQueueOfUserController extends MsgQueueBase
{

    public function __construct()
    {
        $this->receive_redis_key = ':user';
        parent::__construct();
    }


    public function dealServiceLogic($data,$msg_id = ''){
        $key = $data['uid'].'-'.$msg_id;
        $deal_status = $this->isDealData($key);
        if(!$deal_status){
            $user_info['uid'] = $data['uid'];
            $user_info['user_telephone'] = $data['user_telephone'];
            return D('User')->addUserInfo($user_info);
        }
        return $deal_status;
    }

}