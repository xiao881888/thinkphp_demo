<?php
namespace Crontab\Controller;
use Crontab\Util\Factory;
use Think\Controller;

class CobetSchemeController extends Controller {

    private $_redis;
    public function __construct(){
        $this->_redis = Factory::createAliRedisObj();
        $this->_redis->select(0);
    }

    const ORDER_TYPE = 3;

    public function completeCobetScheme(){
        set_time_limit(0);
        $ongoing_scheme_list = D('CobetScheme')->getOnGoingSchemeList();
        foreach($ongoing_scheme_list as $scheme_info){

            $lock_status = $this->_lock($scheme_info['scheme_id']);
            if(!$lock_status){
                ApiLog(' trigger:'.$scheme_info['scheme_id'],'$lock_status:'.$lock_status, 'CobetScheme');
                continue;
            }

            if(!$this->_isOverEndTime($scheme_info)){
                continue;
            }

            if($this->_isEnoughForSchemeMoney($scheme_info)){
                M()->startTrans();
                $guarantee_info = $this->_insertGuaranteeRecord($scheme_info);
                if(empty($guarantee_info)){
                    M()->rollback();
                    continue;
                }

                if(!empty($guarantee_info)){
                    $refund_code = $this->_refundGuaranteeAmount($scheme_info,$guarantee_info);
                    if(!$refund_code){
                        M()->rollback();
                        continue;
                    }
                }

                //验证金额跟方案金额是否一致
                $scheme_total_amount = D('CobetRecord')->getBoughtTotalAmount($scheme_info['scheme_id'],array(C('COBET_TYPE.BOUGHT'),C('COBET_TYPE.GUARANTEE')));
                if(bccomp($scheme_total_amount,$scheme_info['scheme_total_amount']) != 0){
                    ApiLog('金额跟方案金额是否一致:$scheme_info:'.print_r($scheme_info,true),'CobetScheme');
                    M()->rollback();
                    continue;
                }

                $change_code = D('CobetScheme')->changeSchemeStatusById($scheme_info['scheme_id'],C('COBET_SCHEME_STATUS.SCHEME_COMPLETE'));
                if(!$change_code){
                    ApiLog('$change_code:'.$change_code.'$scheme_info:'.print_r($scheme_info,true),'CobetScheme');
                    M()->rollback();
                    continue;
                }
                M()->commit();
            }else{
                $refund_code = $this->refundAmountForSchemeFailed($scheme_info);
                if(!$refund_code){
                    $this->notifyWarningMsg('$refund_code:'.$refund_code.'$scheme_id:'.$scheme_info['scheme_id']);
                    ApiLog('$refund_code:'.$refund_code.'$scheme_info:'.print_r($scheme_info,true),'CobetScheme');
                }
            }
        }
    }

    private function _lock($scheme_id,$expire_time = 60){
        $redis_key = $this->_getLockKey($scheme_id);
        $is_lock = $this->_redis->setnx($redis_key,time()+$expire_time);
        if(!$is_lock){
            $lock_time = $this->_redis->get($redis_key);
            if(time()>$lock_time){
                $this->_unlock($scheme_id);
                $is_lock = $this->_redis->setnx($redis_key,time()+$expire_time);
            }
        }
        return $is_lock?true:false;
    }

    private function _getLockKey($scheme_id){
        return 'cobet_scheme:complete_status:'.$scheme_id;
    }

    private function _unlock($scheme_id){
        return $this->_redis->del($this->_getLockKey($scheme_id));
    }

    private function _isOverEndTime($scheme_info){
        if(!$scheme_info['scheme_issue_id']){
            $msg = '合买方案彩期为空:'.$scheme_info['scheme_issue_id'];
            $this->notifyWarningMsg($msg);
            return false;
        }

        $lottery_id = $scheme_info['lottery_id'];
        if (isJc($lottery_id)) {
            $endTime = D('Home/JcSchedule')->getEndTime($scheme_info['scheme_issue_id']);
        } else {
            $endTime = D('Home/Issue')->getEndTime($scheme_info['scheme_issue_id']);
        }

        $lottery = D('Home/Lottery')->getLotteryInfo($lottery_id);
        if(time() >= strtotime($endTime) - $lottery['lottery_ahead_endtime']){
            $refund_code = $this->refundAmountForSchemeFailed($scheme_info);
            if(!$refund_code){
                $this->notifyWarningMsg('到时间退款$refund_code:'.$refund_code.'$scheme_id:'.$scheme_info['scheme_id']);
                ApiLog('到时间退款$refund_code:'.$refund_code.'$scheme_info:'.print_r($scheme_info,true),'CobetScheme');
            }
            return false;
        }

        $scheme_end_timestamp = strtotime($endTime) - $lottery['lottery_ahead_endtime'] - C('COBET_SCHEME_AHEAD_END_TIME');
        $scheme_end_time = date('Y-m-d H:i:s',$scheme_end_timestamp);
        M('CobetScheme')->where(array('scheme_id'=>$scheme_info['scheme_id']))->save(array('scheme_end_time'=>$scheme_end_time));
        if(getCurrentTime() < $scheme_end_time){
            return false;
        }
        return true;
    }

    public function notifyWarningMsg($msg=''){
        $data = array(
            'telephone_list' => array('18705085505'),
            'send_data' => array(getCurrentTime().':'.get_cfg_var('PROJECT_RUN_MODE').$msg),
            'template_id' => '82542',
        );
        sendTelephoneMsgNew($data);
    }


    private function _insertGuaranteeRecord($scheme_info){
        $bought_total_amount = D('CobetRecord')->getBoughtTotalAmount($scheme_info['scheme_id'],array(C('COBET_TYPE.BOUGHT')));
        $bought_total_unit = D('CobetRecord')->getBoughtTotalUnit($scheme_info['scheme_id'],array(C('COBET_TYPE.BOUGHT')));

        $guarantee_total_amount = bcsub($scheme_info['scheme_total_amount'],$bought_total_amount);
        $guarantee_total_unit = bcsub($scheme_info['scheme_total_unit'],$bought_total_unit);
        if($guarantee_total_amount != bcmul($guarantee_total_unit,$scheme_info['scheme_amount_per_unit'])){
            $this->notifyWarningMsg('金额不一致$scheme_id:'.$scheme_info['scheme_id']);
            ApiLog('金额不一致$scheme_info:'.print_r($scheme_info,true),'CobetScheme');
            return false;
        }
        if($guarantee_total_amount > bcmul($scheme_info['scheme_guarantee_unit'],$scheme_info['scheme_amount_per_unit'])){
            $this->notifyWarningMsg('保底金额超过最大限制$scheme_id:'.$scheme_info['scheme_id']);
            ApiLog('保底金额超过最大限制$scheme_info:'.print_r($scheme_info,true),'CobetScheme');
            return false;
        }


        $guarantee_frozen_info = D('CobetRecord')->getGuaranteeFrozenInfoBySchemeId($scheme_info['scheme_id']);
        if(!empty($guarantee_frozen_info)){
            $user_coupon_id = $guarantee_frozen_info['user_coupon_id'];

            if(bccomp($guarantee_total_amount,$guarantee_frozen_info['record_user_cash_amount']) >= 0){
                $user_cash_amount = $guarantee_frozen_info['record_user_cash_amount'];
                $user_coupon_consume_amount = bcsub($guarantee_total_amount,$guarantee_frozen_info['record_user_cash_amount']);
                $refund_cash_amount = 0;
                $refund_coupon_amount = bcsub($guarantee_frozen_info['record_user_coupon_consume_amount'],$user_coupon_consume_amount);
            }else{
                $user_cash_amount = $guarantee_total_amount;
                $user_coupon_consume_amount = 0;
                $refund_coupon_amount = $guarantee_frozen_info['record_user_coupon_consume_amount'];
                $refund_cash_amount = bcsub($guarantee_frozen_info['record_user_cash_amount'],$user_cash_amount);
            }


            $add_status = D('CobetRecord')->addRecord($scheme_info['uid'], $scheme_info['scheme_id'], C('COBET_TYPE.GUARANTEE'),
                $user_coupon_id,$user_coupon_consume_amount, $user_cash_amount,$guarantee_total_unit, $guarantee_total_amount,COBET_STATUS_OF_CONSUME);
            if(!$add_status){
                $this->notifyWarningMsg('插入记录失败$scheme_id:'.$scheme_info['scheme_id']);
                ApiLog('sql:'.D('CobetRecord')->getLastSql(),'CobetScheme');
                return false;
            }

            if($guarantee_frozen_info['record_refund_unit'] == $guarantee_total_unit){
                $record_status = COBET_STATUS_OF_REFUND;
            }else{
                $record_status = COBET_STATUS_OF__PART_REFUND;
            }
            $refund_unit = $guarantee_frozen_info['record_bought_unit'] - $guarantee_total_unit;
            $refund_amount = bcsub($guarantee_frozen_info['record_bought_amount'],$guarantee_total_amount);
            if($refund_unit < 0 || $refund_amount < 0){
                $this->notifyWarningMsg('退款数据异常$scheme_info:'.print_r($scheme_info,true));
                ApiLog('退款数据异常$scheme_info:'.print_r($scheme_info,true),'CobetScheme');
                return false;
            }

            $save_status = D('CobetRecord')->saveRefundStatus($guarantee_frozen_info['record_id'],$refund_amount,$refund_unit,$record_status);
            if(!$save_status){
                $this->notifyWarningMsg('保存记录失败$scheme_info:'.print_r($scheme_info,true));
                ApiLog('sql:'.D('CobetRecord')->getLastSql(),'CobetScheme');
                ApiLog('保存记录失败$scheme_info:'.print_r($scheme_info,true),'CobetScheme');
                return false;
            }

            $add_status = D('CobetScheme')->addSchemeBoughtUnit($scheme_info['scheme_id'],$guarantee_total_unit);
            if(!$add_status){
                $this->notifyWarningMsg('addBoughtUnit失败$scheme_info:'.print_r($scheme_info,true));
                ApiLog('sql:'.D('CobetScheme')->getLastSql(),'CobetScheme');
                ApiLog('addBoughtUnit失败$scheme_info:'.print_r($scheme_info,true),'CobetScheme');
                return false;
            }

            return array(
                'guarantee_total_amount' => $guarantee_total_amount,
                'guarantee_total_unit' => $guarantee_total_unit,
                'refund_coupon_amount' => $refund_coupon_amount,
                'refund_cash_amount' => $refund_cash_amount,
                'user_coupon_id' => $user_coupon_id,
            );
        }else{
            return array();
        }

    }


    private function _isEnoughForSchemeMoney($scheme_info){
        $min_scheme_amount = bcsub($scheme_info['scheme_total_amount'],bcmul($scheme_info['scheme_guarantee_unit'],$scheme_info['scheme_amount_per_unit']));
        $bought_amount = bcmul($scheme_info['scheme_bought_unit'],$scheme_info['scheme_amount_per_unit']);
        if(bccomp($bought_amount,$min_scheme_amount) < 0){
            return false;
        }
        return true;
    }

    public function refundAmountForSchemeFailed($scheme_info){
        if(!in_array($scheme_info['scheme_status'],array(COBET_SCHEME_STATUS_OF_NO_BEGIN_BOUGHT,COBET_SCHEME_STATUS_OF_ONGOING))){
            ApiLog('当前状态不允许退款$scheme_info:'.print_r($scheme_info,true),'CobetScheme');
            return false;
        }
        M()->startTrans();
        $change_code = D('CobetScheme')->changeSchemeStatusById($scheme_info['scheme_id'],C('COBET_SCHEME_STATUS.FAILED'));
        if(!$change_code){
            ApiLog('sql1:'.D('CobetScheme')->getLastSql().'$scheme_info:'.print_r($scheme_info,true),'CobetScheme');
            M()->rollback();
            return false;
        }

        $record_list = D('CobetRecord')->getRecordListBySchemeId($scheme_info['scheme_id']);
        foreach($record_list as $record){

            if($record['type'] == C('COBET_TYPE.GUARANTEE')){
                ApiLog('uid:'.$record['uid'].'当前退款type异常$record:'.print_r($record,true),'CobetScheme');
                M()->rollback();
                return false;
            }

            if($record['record_status'] != COBET_STATUS_OF_CONSUME){
                ApiLog('uid:'.$record['uid'].'当前退款record_status异常$record:'.print_r($record,true),'CobetScheme');
                M()->rollback();
                return false;
            }

            if($record['type'] == C('COBET_TYPE.BOUGHT')){
                $user_account_log_type = C('USER_ACCOUNT_LOG_TYPE.COBET_BOUGHT_REFUND');
            }elseif($record['type'] == C('COBET_TYPE.GUARANTEE_FROZEN')){
                $user_account_log_type = C('USER_ACCOUNT_LOG_TYPE.COBET_GUARANTEE_REFUND');
            }
            $user_coupon_id = $record['user_coupon_id'];
            $refund_coupon_amount = $record['record_user_coupon_consume_amount'];
            $refund_money = $record['record_user_cash_amount'];
            $refund_code = $this->_refundAmount($record['uid'],$user_coupon_id,$refund_coupon_amount,$refund_money,$user_account_log_type);
            if(!$refund_code){
                ApiLog('uid:'.$record['uid'].'当前退款失败$record:'.print_r($record,true),'CobetScheme');
                M()->rollback();
                return false;
            }

            $record_status = COBET_STATUS_OF_REFUND;
            $refund_unit = $record['record_bought_unit'];
            $refund_amount = $record['record_bought_amount'];;
            if($refund_unit < 0 || $refund_amount < 0){
                $this->notifyWarningMsg('2退款数据异常$record_id:'.$record['record_id']);
                ApiLog('2退款数据异常$scheme_info:'.print_r($scheme_info,true),'CobetScheme');
                return false;
            }

            $save_status = D('CobetRecord')->saveRefundStatus($record['record_id'],$refund_amount,$refund_unit,$record_status);
            if(!$save_status){
                $this->notifyWarningMsg('保存记录失败$record:'.$record['record_id']);
                ApiLog('sql:'.D('CobetRecord')->getLastSql(),'CobetScheme');
                ApiLog('保存记录失败$record:'.print_r($record,true),'CobetScheme');
                return false;
            }

        }
        $change_code = D('CobetScheme')->changeSchemeStatusById($scheme_info['scheme_id'],C('COBET_SCHEME_STATUS.FAILED_REFUND'));
        if(!$change_code){
            ApiLog('sql2:'.D('CobetScheme')->getLastSql().'当前退款失败$scheme_info:'.print_r($scheme_info,true),'CobetScheme');
            M()->rollback();
            return false;
        }

        M()->commit();
        return true;
    }

    private function _refundAmount($uid,$user_coupon_id,$refund_coupon_amount,$refund_money,$user_account_log_type){
        if($user_coupon_id && $refund_coupon_amount>0){
            $result = D('Home/UserCoupon')->increaseCoupon($user_coupon_id, $refund_coupon_amount);
            $coupon_balance = D('Home/UserCoupon')->getCouponBalance($user_coupon_id);
            D('Home/UserCouponLog')->addUserCouponLog($uid, $user_coupon_id, $refund_coupon_amount, $coupon_balance, $user_account_log_type, $uid,$remark = '合买退回红包退回');
            if(empty($result)){
                ApiLog('$user_coupon_id:'.$user_coupon_id,'CobetScheme');
                ApiLog('$refund_coupon_amount:'.$refund_coupon_amount,'CobetScheme');
                ApiLog('sql2:'.D('Home/UserCoupon')->getLastSql(),'CobetScheme');
                return false;
            }
        }

        $refund_total_amount = bcadd($refund_money,$refund_coupon_amount);
        if($refund_total_amount > 0){
            $refund_result = D('Home/UserAccount')->refundCobetMoney($uid, $refund_total_amount,$refund_money,$refund_coupon_amount, 0,$user_account_log_type);
            if(empty($refund_result)){
                ApiLog('$refund_total_amount:'.$refund_total_amount,'CobetScheme');
                ApiLog('$refund_money:'.$refund_money,'CobetScheme');
                ApiLog('$refund_coupon_amount:'.$refund_coupon_amount,'CobetScheme');
                ApiLog('$user_account_log_type:'.$user_account_log_type,'CobetScheme');
                ApiLog('sql3:'.D('Home/UserAccount')->getLastSql(),'CobetScheme');
                ApiLog('sql4:'.D('Home/UserAccountLog')->getLastSql(),'CobetScheme');
                return false;
            }
        }
        return true;
    }

    private function _refundGuaranteeAmount($scheme_info,$guarantee_info){
        $guarantee_total_amount = $guarantee_info['guarantee_total_amount'];
        $refund_coupon_amount = $guarantee_info['refund_coupon_amount'];
        $refund_cash_amount = $guarantee_info['refund_cash_amount'];
        $user_coupon_id = $guarantee_info['user_coupon_id'];

        //退回保底的剩余金额
        $refund_guarantee_amount = bcsub(bcmul($scheme_info['scheme_guarantee_unit'],$scheme_info['scheme_amount_per_unit']),$guarantee_total_amount);
        if(bccomp($refund_guarantee_amount,($refund_coupon_amount+$refund_cash_amount)) != 0){
            $this->notifyWarningMsg('退回保底金额算错$scheme_info:'.print_r($scheme_info,true));
            ApiLog('退回保底金额算错$scheme_info:'.print_r($scheme_info,true),'CobetScheme');
            return false;
        }

        $user_account_log_type = C('USER_ACCOUNT_LOG_TYPE.COBET_GUARANTEE_REFUND');
        $refund_code = $this->_refundAmount($scheme_info['uid'],$user_coupon_id,$refund_coupon_amount,$refund_cash_amount,$user_account_log_type);
        if(!$refund_code){
            $this->notifyWarningMsg('uid:'.$scheme_info['uid'].'当前退保底错误$record:'.$refund_guarantee_amount['record_id']);
            ApiLog('uid:'.$scheme_info['uid'].'当前退保底错误$record:'.print_r($scheme_info,true),'CobetScheme');
            return false;
        }
        return true;
    }

    public function updateHistoryData(){
        set_time_limit(0);
        $uids = D('CobetScheme')->getUids();
        foreach($uids as $uid){
            $data['history_gain'] = $this->_getHistoryGain($uid);
            $history_record = $this->_getHistoryRecord($uid);
            $data['history_record_desc'] = $history_record['history_record_desc'];
            $data['history_record'] = $history_record['history_record'];
           $status_list = array(COBET_SCHEME_STATUS_OF_NO_BEGIN_BOUGHT,COBET_SCHEME_STATUS_OF_ONGOING,
                                COBET_SCHEME_STATUS_OF_SCHEME_COMPLETE,COBET_SCHEME_STATUS_OF_PRINTOUT);
            $scheme_ids = D('CobetScheme')->getSchemeIdsByUid($uid,$status_list);
            D('CobetScheme')->updateHistoryData($scheme_ids,$data);
        }
    }

    private function _getHistoryGain($uid){
        $order_total_amount = D('Order')->getOrderTotalAmountByUid($uid,self::ORDER_TYPE);
        $order_winning_amount = D('Order')->getOrderWinningAmountByUid($uid,self::ORDER_TYPE);
        $order_total_amount_backup = D('OrderBackup')->getOrderTotalAmountByUid($uid,self::ORDER_TYPE);
        $order_winning_amount_backup = D('OrderBackup')->getOrderWinningAmountByUid($uid,self::ORDER_TYPE);
        $history_gain = bcdiv(($order_winning_amount+$order_winning_amount_backup),($order_total_amount+$order_total_amount_backup),2);
        $history_gain = $history_gain*100;
        return $history_gain;
    }

    private function _getHistoryRecord($uid){
        $data['history_record_desc'] = '0/0';
        $data['history_record'] = 0;
        $order_ids = D('Order')->getPrizeOrderIdsByUid($uid,self::ORDER_TYPE);
        $prize_order_count = count($order_ids);
        $wining_order_ids = D('Order')->getWiningOrderIdsByUid($uid,self::ORDER_TYPE,$order_ids);
        $wining_order_count = count($wining_order_ids);
        $data['history_record_desc'] =  $wining_order_count.'/'.$prize_order_count;
        if(!empty($prize_order_count) && !empty($wining_order_count)){
            $data['history_record'] = bcdiv($wining_order_count,$prize_order_count,2);
        }else{
            $data['history_record'] = 0;
        }
        return $data;
    }
    
}
