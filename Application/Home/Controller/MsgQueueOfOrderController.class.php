<?php
namespace Home\Controller;

use Home\Util\TigerMQ\MsgQueueBase;

class MsgQueueOfOrderController extends MsgQueueBase
{
    //传入主题
    public function __construct(){
        $this->producer = 'tigercai_order_notify_pub';
        $this->topic = 'tigercai_order_notify';
        $this->receive_redis_key = ':order';
        parent::__construct();
    }

    public function notifyOrderNotice($order_id){
        $order['order_id'] = $order_id;
        $msgQueueOfOrder = new MsgQueueOfOrderController();
        $response_data = $msgQueueOfOrder->send($order_id,$order);
    }

    public function dealServiceLogic($data,$msg_id = ''){
        $order_id = $data['order_id'];
        $key = $order_id.'-'.$msg_id;
        $deal_status = $this->isDealData($key);
        if(!$deal_status){
            A('GameActivity')->runActivity(json_encode(array('order_id'=>$order_id)));
            $order_info = D('Order')->getOrderInfo($order_id);

            /*if(!$this->_isAddIntegral($order_info['uid'])){
                ApiLog('uid:'.$order_info['uid'].'不是老虎用户','no_tiger_user2');
                return false;
            }*/

            if($order_info['order_type'] != ORDER_TYPE_OF_COBET){
                if(($order_info['order_status'] == 3 || $order_info['order_status'] == 8)){

                    A('RegisterActivity')->szcLotteryActivity($order_info['uid'],$order_info['lottery_id']);

                    $add_exp = round($order_info['order_total_amount'] - $order_info['order_refund_amount']);
                    $add_integral = $this->_getAddIntegral($order_info);
                    $data['uid'] = $order_info['uid'];
                    $data['order_id'] = $order_id;
                    $data['add_integral'] = $add_integral;
                    $data['add_exp'] = $add_exp;
                    $data['order_type'] = $order_info['order_type'];
                    $request_data['data'] = json_encode($data);
                    $request_data['act_code'] = C('INTEGRAL_ACT.ADD_USER_INTEGRAL');
                    $response_data = requestUserIntegral($request_data);
                }
            }else{
                $scheme_info = D('CobetScheme')->getInfoByOrderId($order_info['order_id']);
                if($scheme_info['scheme_status'] != COBET_SCHEME_STATUS_OF_PRINTOUT){
                    ApiLog('方案状态异常$order_info:'.print_r($order_info,true),'addUserIntegralForOrder');
                }

                $record_list = D('CobetRecord')->getRecordListBySchemeId($scheme_info['scheme_id']);
                foreach($record_list as $record){
                    if(in_array($record['type'],array(COBET_TYPE_OF_BOUGHT,COBET_TYPE_OF_GUARANTEE))){
                        $add_integral = $record['record_user_cash_amount'];
                        $data['uid'] = $record['uid'];
                        $data['order_id'] = $order_id;
                        $data['add_integral'] = $add_integral;
                        $data['add_exp'] = $add_integral;
                        $data['order_type'] = $order_info['order_type'];
                        $request_data['data'] = json_encode($data);
                        $request_data['act_code'] = C('INTEGRAL_ACT.ADD_USER_INTEGRAL');
                        $response_data = requestUserIntegral($request_data);
                    }
                }
            }


            $this->addDealData($key);
        }
        return true;
    }

    private function _isAddIntegral($uid){
        $user_info = D('User')->getUserInfo($uid);
        $app_id = getRegAppId($user_info);
        if($app_id == C('APP_ID_LIST.BAIWAN')) {
            return false;
        }
        return true;
    }

    private function _getAddIntegral($order_info){
        if($order_info['user_coupon_amount'] > $order_info['order_refund_amount']){
            return round($order_info['order_total_amount'] - $order_info['user_coupon_amount']);
        }elseif($order_info['user_coupon_amount'] <= $order_info['order_refund_amount']){
            return round($order_info['order_total_amount'] - $order_info['order_refund_amount']);
        }
    }
}