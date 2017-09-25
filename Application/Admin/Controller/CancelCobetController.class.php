<?php
namespace Admin\Controller;
/**
 * @date 2014-12-3
 * @author tww <merry2014@vip.qq.com>
 */
class CancelCobetController extends GlobalController{

    const IS_SZC = 1;

    public function _before_index(){
        $this->_assignLotteryMap();
    }

    public function _before_add(){
        $this->_assignLotteryMap();
    }

    public function _before_edit(){
        $this->_assignLotteryMap();
    }

    private function _assignLotteryMap(){
        $map = D('Lottery')->getLotteryMap();
        $this->assign('lottery_map', $map);
    }

    public function cancel(){
        $id = I('id',0,'intval');
        $cancel_cobet_info = D('CancelCobet')->getInfoById($id);
        if($cancel_cobet_info['cancel_cobet_status'] == 1){
            $this->error('当前状态已取消');
        }

        $scheme_list = $this->getCancelSchemeList($cancel_cobet_info);
        foreach($scheme_list as $scheme_info){
            $this->_cancelScheme($scheme_info);
        }
    }

    private function _cancelScheme($scheme_info){
        $scheme_status = D('Crontab/CobetScheme')->getSchemeStatusById($scheme_info['scheme_id']);
        if(in_array($scheme_status,array(C('COBET_SCHEME_STATUS.NO_BEGIN_BOUGHT'),C('COBET_SCHEME_STATUS.ONGOING'),C('COBET_SCHEME_STATUS.SCHEME_COMPLETE')))){
            ApiLog('当前状态不允许退款$scheme_info:'.print_r($scheme_info,true),'cancelScheme');
            return false;
        }
        M()->startTrans();
        $change_code = D('Crontab/CobetScheme')->changeSchemeStatusById($scheme_info['scheme_id'],C('COBET_SCHEME_STATUS.CANCEL'));
        if(!$change_code){
            ApiLog('sql1:'.D('Crontab/CobetScheme')->getLastSql().'$scheme_info:'.print_r($scheme_info,true),'cancelScheme');
            M()->rollback();
            return false;
        }

        $record_list = D('Crontab/CobetRecord')->getRecordListBySchemeId($scheme_info['scheme_id']);
        foreach($record_list as $record){

            if($scheme_status == C('COBET_SCHEME_STATUS.NO_BEGIN_BOUGHT')){
                if(in_array($record['type'],array(C('COBET_TYPE.GUARANTEE_FROZEN')))){
                    ApiLog('uid:'.$record['uid'].'当前退款有数据异常$record:'.print_r($record,true),'cancelScheme');
                    M()->rollback();
                    return false;
                }
            }elseif($scheme_status == C('COBET_SCHEME_STATUS.ONGOING')){
                if(in_array($record['type'],array(C('COBET_TYPE.GUARANTEE_FROZEN'),C('COBET_TYPE.BOUGHT')))){
                    ApiLog('uid:'.$record['uid'].'当前退款有数据异常$record:'.print_r($record,true),'cancelScheme');
                    M()->rollback();
                    return false;
                }
            }elseif($scheme_status == C('COBET_SCHEME_STATUS.SCHEME_COMPLETE')){
                if(in_array($record['type'],array(C('COBET_TYPE.GUARANTEE_FROZEN')))){
                    ApiLog('uid:'.$record['uid'].'当前跳过冻结保底的金额:'.print_r($record,true),'cancelScheme');
                   continue;
                }
            }

            if($record['type'] == C('COBET_TYPE.BOUGHT')){
                $user_account_log_type = C('USER_ACCOUNT_LOG_TYPE.COBET_BOUGHT_REFUND');
            }else{
                $user_account_log_type = C('USER_ACCOUNT_LOG_TYPE.COBET_GUARANTEE_REFUND');
            }
            $user_coupon_id = $record['user_coupon_id'];
            $refund_coupon_amount = $record['record_user_coupon_consume_amount'];
            $refund_money = $record['record_user_cash_amount'];
            $refund_code = $this->_refundAmount($record['uid'],$user_coupon_id,$refund_coupon_amount,$refund_money,$user_account_log_type);
            if(!$refund_code){
                ApiLog('uid:'.$record['uid'].'当前退款失败$record:'.print_r($record,true),'cancelScheme');
                M()->rollback();
                return false;
            }
        }
        $change_code = D('Crontab/CobetScheme')->changeSchemeStatusById($scheme_info['scheme_id'],C('COBET_SCHEME_STATUS.CANCEL_REFUND'));
        if(!$change_code){
            ApiLog('sql2:'.D('Crontab/CobetScheme')->getLastSql().'当前退款失败$scheme_info:'.print_r($scheme_info,true),'cancelScheme');
            M()->rollback();
            return false;
        }

        M()->commit();
        return true;
    }

    private function _refundAmount($uid,$user_coupon_id,$refund_coupon_amount,$refund_money,$user_account_log_type){
        if($refund_coupon_amount){
            $result = D('Home/UserCoupon')->increaseCoupon($user_coupon_id, $refund_coupon_amount);
            if(empty($result)){
                return false;
            }
        }

        $refund_total_amount = bcadd($refund_money,$refund_coupon_amount);
        if($refund_total_amount > 0){
            $refund_result = D('Home/UserAccount')->refundCobetMoney($uid, $refund_total_amount,$refund_money,$refund_coupon_amount, 0,$user_account_log_type);
            if(empty($refund_result)){
                return false;
            }
        }
        return true;
    }


    public function getCancelSchemeList($cancel_cobet_info){
        if($cancel_cobet_info['cancel_cobet_type'] == self::IS_SZC){
            return  D('CobetScheme')->getSchemeListByIssueId($cancel_cobet_info['issue_id'],
                                            array(C('COBET_SCHEME_STATUS.NO_BEGIN_BOUGHT'),C('COBET_SCHEME_STATUS.ONGOING'),C('COBET_SCHEME_STATUS.SCHEME_COMPLETE')));
        }else{
            $schedule_ids = getScheduleIdsByDayRoundNo($cancel_cobet_info['lottery_id'],
                $cancel_cobet_info['schedule_day'], $cancel_cobet_info['schedule_round_no']);
            $order_ids = D('CobetJcOrderDetail')->getOrderIdsByScheduleIds($schedule_ids);
            $order_ids = array_unique($order_ids);
            return  D('CobetScheme')->getSchemeListByCobetOrderIds($order_ids,
                array(C('COBET_SCHEME_STATUS.NO_BEGIN_BOUGHT'),C('COBET_SCHEME_STATUS.ONGOING'),C('COBET_SCHEME_STATUS.SCHEME_COMPLETE')));
        }

    }
}