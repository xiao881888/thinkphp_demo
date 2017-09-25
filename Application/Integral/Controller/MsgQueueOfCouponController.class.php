<?php
namespace Integral\Controller;


use Integral\Util\TigerMQ\MsgQueueBase;

class MsgQueueOfCouponController extends MsgQueueBase
{

    public function __construct(){
        $this->producer = 'tigercai_integral';
        $this->topic = 'tigercai_integral_grant_coupon';
        $this->receive_redis_key = ':coupon';
        parent::__construct();
    }

}