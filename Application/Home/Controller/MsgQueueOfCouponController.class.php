<?php
namespace Home\Controller;
use Home\Util\TigerMQ\MsgQueueBase;

class MsgQueueOfCouponController extends MsgQueueBase
{

    public function __construct(){
        $this->receive_redis_key = ':coupon';
        parent::__construct();
    }

    //TODO 避免队列通知异常导致红包重复发放
    public function dealServiceLogic($data,$msg_id = ''){
        $coupon_id = $data['coupon_id'];
        $uid = $data['uid'];
        if(empty($uid)){
            $user_tel = $data['user_tel'];
            $user_info = D('User')->queryUserInfoByPhone($user_tel);
            $uid = $user_info['uid'];
        }
        $log_type = isset($data['log_type']) ? $data['log_type'] : C('USER_COUPON_LOG_TYPE.GIFT');
        $issue_id = isset($data['issue_id']) ? $data['issue_id'] : 0;
        $key = $uid.'-'.$coupon_id.'-'.$msg_id;
        $deal_status = $this->isDealData($key);
        if(!$deal_status){
            $deal_status = A('UserCoupon')->grantCouponToUser($coupon_id,$uid,$log_type,$issue_id);
            $this->addDealData($key);
        }
        return $deal_status;
    }



}