<?php
namespace Integral\Util\TigerMQ;
use Think\Exception;

class MsgQueueBase
{
    protected $redis;

    protected $producer;
    protected $topic;
    protected $key;
    protected $payload;

    protected $receive_redis_key;

    protected $notify_tel;
    protected $warning_message_template_id;
    protected $warning_message;

    public function __construct(){
        $this->notify_tel = array('18705085505');
        $this->warning_message_template_id = '82542';
        if(!$this->redis){
            $this->redis = getRedis();
        }
    }

    //TODO 要处理通知错误的情况(重试)
    public function send($key,$data,$repeat_times=5){
        $this->key = $key;
        $data['project_mode'] = get_cfg_var('PROJECT_RUN_MODE');
        $this->payload = json_encode($data);
        $response_data = requestMsgQueueSendUrl($this->producer,$this->topic,$this->key,$this->payload);
        if($response_data['code'] !== 0){
            $response_data = $this->repeatSend($repeat_times);
        }
        return $response_data;
    }

    public function repeatSend($repeat_times = 5){
        $is_success = false;
        for($i=0;$i<$repeat_times;$i++){
            $response_data = requestMsgQueueSendUrl($this->producer,$this->topic,$this->key,$this->payload);
            if($response_data['code'] === 0){
                $is_success = true;
                break;
            }
        }
        if(!$is_success){
            if(get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION'){
                $this->_notifyWarningMsg($response_data['msgId']);
            }
        }
        return $response_data;
    }

    private function _notifyWarningMsg($key_str=''){
        $this->warning_message = array('MODEL:'.get_cfg_var('PROJECT_RUN_MODE').$key_str.'调用消息队列发送接口失败');
        $data = array(
            'telephone_list' => $this->notify_tel,
            'send_data' => $this->warning_message,
            'template_id' => $this->warning_message_template_id,
        );
        sendTelephoneMsgNew($data);
    }

    public function notify(){
        $msg_list = file_get_contents('php://input');
        $msg_list = json_decode($msg_list,true);
        foreach($msg_list['list'] as $msg){
            $msg_id = $msg['msgId'];
            $lock_status = $this->_lock($msg_id);
            if($lock_status){
                $data = json_decode($msg['payload'],true);  //消息内容
                if($data['project_mode']!=get_cfg_var('PROJECT_RUN_MODE')){
                    $this->_notifyWarningMsg($msg);
                    continue;
                }
                $this->dealServiceLogic($data,$msg_id);
            }
            $response_data = requestMsgQueueConfirmUrl($msg['handle']);
            if($response_data['code'] === 0){
                $this->redis->sAdd($this->getReceiveMsgSuccessRedis(),$msg['msgId']);
            }else{
                $this->redis->sAdd($this->getReceiveMsgFailRedis(),$msg['msgId']);
            }
            $this->_unlock($msg_id);
        }

    }

    private function _lock($msg_id,$expire_time = 5){
        $redis_key = $this->_getLockKey($msg_id);
        $is_lock = $this->redis->setnx($redis_key,time()+$expire_time);
        if(!$is_lock){
            $lock_time = $this->redis->get($redis_key);
            if(time()>$lock_time){
                $this->_unlock($redis_key);
                $is_lock = $this->redis->setnx($redis_key,time()+$expire_time);
            }
        }
        return $is_lock?true:false;
    }

    private function _getLockKey($msg_id){
        return 'msg_queue'.$this->receive_redis_key.':lock:'.$msg_id;
    }

    private function _unlock($msg_id){
        return $this->redis->del($this->_getLockKey($msg_id));
    }

    protected function dealServiceLogic($data,$msg_id = ''){}

    protected function getMsgQueueReceiveMsgRedis(){
        return 'msg_queue'.$this->receive_redis_key.':notify_msg:'.date('Y-m-d',time());
    }

    protected function getReceiveMsgSuccessRedis(){
        return 'msg_queue'.$this->receive_redis_key.':notify_msg_success:'.date('Y-m-d',time());
    }

    protected function getReceiveMsgFailRedis(){
        return 'msg_queue'.$this->receive_redis_key.':notify_msg_fail:'.date('Y-m-d',time());
    }

    protected function getDealDataRedis(){
        return 'msg_queue'.$this->receive_redis_key.':deal_data:'.date('Y-m-d',time());
    }

    protected function isDealData($key){
        return $this->redis->sContains($this->getDealDataRedis(),$key);
    }

    protected function addDealData($key){
        $this->redis->sAdd($this->getDealDataRedis(),$key);
        $this->redis->expire($this->getDealDataRedis(),3*24*60*60);
    }


}