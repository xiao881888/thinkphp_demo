<?php
namespace Home\Controller;
use Home\Util\TigerMQ\MsgQueueBase;

class MsgQueueOfIssueController extends MsgQueueBase
{
    const FOLLOW_BET_ISSUE_START = 1;
    const FOLLOW_BET_AWARD = 2;
    const FOLLOW_BET_PRIZED = 3;

    public function __construct(){
        $this->receive_redis_key = ':issue';
        parent::__construct();
    }

    public function dealServiceLogic($data,$msg_id = ''){
        $lottery_id = $data['lottery_id'];
        $type = $data['type'];
        $issue_id = $data['issue_id'];
        $key = $lottery_id.'-'.$type.'-'.$msg_id;
        $deal_status = $this->isDealData($key);
        if(!$deal_status){
            if($type == self::FOLLOW_BET_ISSUE_START){
                $deal_status = A('FollowWorker')->trigger($lottery_id,$type,0);
            }else{
                $deal_status = A('StopFollowBet')->trigger($lottery_id,$type,$issue_id);
            }
            $this->addDealData($key);
        }
        return $deal_status;
    }



}