<?php
namespace H5\Controller;

use Home\Controller\BettingBaseController;
use Home\Util\Factory;
use H5\Service\WebPayService;

class BetController extends BaseController
{
    const WEB_BET_KEY = 'web_bet:';

    public function __construct()
    {
        parent::__construct();
        self::initializeRedis();
    }

    public function index()
    {
        $order_id = I('get.id',false);
        $product_name = I('get.product_name',false);

        if (!$order_id or !$product_name or !$this->_validBetSign()){
            $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
        }

        $order_info = $this->getPreBetInfo($order_id,$product_name);
        $response_data = $this->_formatOrderResponseData($order_info,$redis_key);
        $this->response($response_data);
    }

    private function getPreBetInfo($order_id,$product_name)
    {
        $redis_key = self::WEB_BET_KEY . $product_name . ':' . $order_id;

        if (!self::redisInstance()->exists($redis_key)) {
            $this->responseError(RESPONSE_ERROR_WITHOUT_BILL);
        }
        $order_info = self::redisInstance()->hGetAll($redis_key);
        $order_info['schedule_orders'] = json_decode($order_info['schedule_orders'], true);
        $order_info['expire_time'] = self::redisInstance()->ttl($redis_key);

        return $order_info;
    }

    private function delPreBetInfo($order_id,$product_name)
    {
        $redis_key = self::WEB_BET_KEY . $product_name . ':' . $order_id;

        if (self::redisInstance()->exists($redis_key)) {
            self::redisInstance()->del($redis_key);
        }
    }

    public function submitConfirm()
    {
        $order_id = $this->input('id');
        $product_name = $this->input('product_name');

        if (!$order_id or !$product_name or !$this->_validBetSign()){
            $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
        }

        $order_info = $this->getPreBetInfo($order_id,$product_name);
        H5Log('submitConfirm:user_id:'.$this->uid.print_r($order_info,true),'h5_bet_submit');

        //检查金额是否足够
        $total_money = (float)$order_info['total_amount'];
        if (!$total_money){
            $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
        }
        $lack_money = $this->_calculateLackMoneyForPay($this->uid, $total_money,$order_info['lottery_id']);

        if ($lack_money > 0){
            $this->responseError(RESPONSE_ERROR_BALANCE_NOT_ENOUGH,'',array('lack_money' => (float)$lack_money));
        }

        $user_balance = self::getModelInstance('UserAccount')->getUserBalance($this->uid);

        $user_coupon_list = D('UserCouponView')->getUserCouponListForNativePay($this->uid);
        $user_coupon_list = $this->_filterCouponListForPay($user_coupon_list, $order_info['lottery_id'], $total_money);
        $user_coupon_list = D('UserCouponView')->formatCouponListForNativePay($user_coupon_list);
        $user_coupon_list = ($user_coupon_list ? $user_coupon_list : array());
        $bet_desc = $this->_getDescLotteryId($order_info['lottery_id']);

        $response_data = array(
            'lack_money' => (float)$lack_money,
            'pay_money' => (float)$total_money,
            'balance' => (float)$user_balance,
            'bet_desc' => $bet_desc,
            'coupon_list' => $user_coupon_list
        );

        $this->response($response_data);
    }

    public function submitPay()
    {
        $order_id = $this->input('id');
        $product_name = $this->input('product_name');
        $coupon_id = (int)$this->input('coupon_id');

        //$order_id = 'PB1704190401005CNEBF';
        //$product_name = 'YQDS';

        if (!$order_id or !$product_name or !$this->_validBetSign()){
            $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
        }

        $order_info = $this->getPreBetInfo($order_id,$product_name);

        $bet_info = $this->_formatBetInfo($order_info,$coupon_id);
        H5Log('bet_info:'.print_r($bet_info,true),'h5_bet_submit');
        $pay = new WebPayService($bet_info);
        $pay_response = $pay->payOrder();
        H5Log('pay_response:'.print_r($pay_response,true),'h5_bet_submit');

        if ($pay_response['code'] > C('ERROR_CODE.SUCCESS')){
            $error_msg = !empty($pay_response['msg']) ? $pay_response['msg'] : $pay->bet_error_msg[$pay_response['code']];
            if (empty($error_msg)){
                $this->responseError(RESPONSE_ERROR_UNKNOWN);
            }
            $this->responseError(RESPONSE_ERROR_BET_FAILS,$error_msg);
        }

        //todo 抛出异常有问题
        if (!(boolean)$pay_response['order_id']){
            $this->responseError(RESPONSE_ERROR_BET_FAILS,'投注失败 发生未知错误');
        }

        $this->delPreBetInfo($order_id,$product_name);

        D('H5BetLog')->addLog($order_id,$this->uid,$pay_response['order_id'],$order_info,$bet_info,$product_name);

        $this->response(array('order_id' => $pay_response['order_id']));
    }

    private function _formatOrderResponseData($order_info,$redis_key)
    {
        $input_bonus_range = explode('~',$order_info['bonus_range']);
        $bonus_range = $input_bonus_range[0] == $input_bonus_range[1] ? $input_bonus_range[0] : $order_info['bonus_range'];
        return array(
            'lottery_id' => (int)$order_info['lottery_id'],
            'total_amount' => (float)$order_info['total_amount'],
            'play_type' => (int)$order_info['play_type'],
            'stake_count' => (int)$order_info['stake_count'],
            'series' => (string)$order_info['series'],
            'bonus_range' => (string)$bonus_range,
            'multiple' => (int)$order_info['multiple'],
            'schedule_orders' => $order_info['schedule_orders'],
            'expire_time' => (int)$order_info['expire_time'],
            'sale_status' => 1,
        );
    }

    private function _calculateLackMoneyForPay($uid, $total_amount, $lottery_id)
    {
        $user_balance = self::getModelInstance('UserAccount')->getUserBalance($uid);
        $user_coupon_list = self::getModelInstance('UserCoupon')->getAvailableCouponList($uid);
        $max_coupon_balance_info = $this->_filterCouponList($user_coupon_list, $lottery_id, $total_amount);

        H5Log("max_coupon:" . $user_balance . '====' . $total_amount . '====' . print_r($max_coupon_balance_info, true), 'h5_webpay');
        $lack_money = 0;
        if ($max_coupon_balance_info) {
            if (($max_coupon_balance_info['user_coupon_balance'] + $user_balance) < $total_amount) {
                $has_amount = bcadd($max_coupon_balance_info['user_coupon_balance'], $user_balance, 2);
                $lack_money = bcsub($total_amount, $has_amount, 2);
            }
        } else {
            if ($user_balance < $total_amount) {
                $lack_money = bcsub($total_amount, $user_balance, 2);
            }
        }
        H5Log("max_coupon lack:" . $lack_money . '====' . $user_balance . '====' . $total_amount . '====' . print_r($max_coupon_balance_info, true), 'webpay');

        return $lack_money;
    }

    private function _filterCouponList($user_coupon_list,$lottery_id,$order_amount){
        $max_coupon_balance_info = array();
        foreach($user_coupon_list as $key => $user_coupon_info){
            if(!empty($user_coupon_info['coupon_lottery_ids'])){
                $lottery_list = explode(',',$user_coupon_info['coupon_lottery_ids']);
                if(!in_array($lottery_id,$lottery_list)){
                    unset($user_coupon_list[$key]);
                    continue;
                }
            }

            if(bccomp($order_amount, $user_coupon_info['coupon_min_consume_price']) < 0){
                unset($user_coupon_list[$key]);
                continue;
            }
        }
        $user_coupon_list = array_values($user_coupon_list);
        $max_coupon_balance = 0;
        $curr_coupon_balance = 0;
        foreach($user_coupon_list as $key => $user_coupon_info){
            $curr_coupon_balance = $user_coupon_info['user_coupon_balance'];
            if($curr_coupon_balance > $max_coupon_balance){
                $max_coupon_balance = $curr_coupon_balance;
                $max_coupon_balance_info = $user_coupon_info;
            }
        }
        return $max_coupon_balance_info;

    }

    private function _filterCouponListForPay($user_coupon_list,$lottery_id,$order_amount){
        foreach($user_coupon_list as $key => $user_coupon_info){
            if(!empty($user_coupon_info['coupon_lottery_ids'])){
                $lottery_list = explode(',',$user_coupon_info['coupon_lottery_ids']);
                if(!in_array($lottery_id,$lottery_list)){
                    unset($user_coupon_list[$key]);
                    continue;
                }
            }

            if(bccomp($order_amount, $user_coupon_info['coupon_min_consume_price']) < 0){
                unset($user_coupon_list[$key]);
                continue;
            }
        }

        return $user_coupon_list;
    }

    private function _formatBetInfo($order_info,$coupon_id)
    {
        $lottery_id = $order_info['lottery_id'];
        if (isJCLottery($lottery_id)){
            if (!empty($order_info['optimize_ticket_list'])){
                return $this->_betJCForOptimize($order_info,$coupon_id);
            }else{
                return $this->_betJC($order_info,$coupon_id);
            }
        }elseif (isZcsfc($lottery_id)){
            return $this->_betLCZ($order_info,$coupon_id);
        }
        return $this->_betSZC($order_info,$coupon_id);
    }

    private function _betJCForOptimize($order_info,$coupon_id)
    {
        //todo 需要补上校验场次是否开售
        $order_info['act'] = C('BET_ACT.BET_JC');
        $order_info['order_identity'] = '';
        $order_info['uid'] = (int)$this->uid;
        $order_info['user_coupon_id'] = (int)$coupon_id;
        $order_info['select_schedule_ids'] = json_decode($order_info['select_schedule_ids'],true);
        $order_info['optimize_ticket_list'] = json_decode($order_info['optimize_ticket_list'],true);
        foreach ($order_info['select_schedule_ids'] as $schedule_id){
            $schedule = D('JcSchedule')->where(array('schedule_id' => $schedule_id))->find();
            $this->_validSchedule($schedule,$order_info,'',true);
        }
        
        return $order_info;
    }

    private function _betJC($order_info,$coupon_id)
    {
        $schedule_orders = array();
        $play_type = $order_info['play_type'] == 2 ? JC_PLAY_TYPE_MULTI_STAGE : JC_PLAY_TYPE_SINGLE_STAGE;

        foreach ($order_info['schedule_orders'] as $schedule_item){
            if (isJclq($order_info['lottery_id'])){
                $no = $schedule_item['match_round_id'];
            }else{
                $no = substr($schedule_item['match_round_id'],2,10);
            }
            $schedule_issue_no = $order_info['lottery_id'].'*'.$play_type.'*'.$no;
            $schedule = D('JcSchedule')->where(array('schedule_issue_no' => $schedule_issue_no))->find();
            $this->_validSchedule($schedule,$order_info,$schedule_item['bet_number']);

            $schedule_orders[] = array(
                'bet_number' => $schedule_item['bet_number'],
                'is_sure' => (int)$schedule_item['is_sure'],
                'schedule_id' => $schedule['schedule_id'],
            );
        }

        return array(
            'act' => C('BET_ACT.BET_JC'),
            'order_identity' => '',
            'stake_count' => (int)$order_info['stake_count'],
            'uid' => (int)$this->uid,
            'lottery_id' => (int)$order_info['lottery_id'],
            'series' => $order_info['series'],
            'user_coupon_id' => (int)$coupon_id,
            'total_amount' => (float)$order_info['total_amount'],
            'multiple' => $order_info['multiple'],
            'play_type' => (int)$order_info['play_type'],
            'schedule_orders' => $schedule_orders,
        );
    }

    private function _betSZC($order_info,$coupon_id)
    {
        $issue_no = self::getModelInstance('Issue')->queryIssueInfoByIssueNo($order_info['lottery_id'],$order_info['issue_id']);
        if (empty($issue_no)){
            $this->responseError(RESPONSE_ERROR_BET_FAILS,$issue_no.'投注期次不存在');
        }
        return array(
            'act' => C('BET_ACT.BET_SZC'),
            'uid' => (int)$this->uid,
            'multiple' => $order_info['multiple'],
            'follow_times' => (int)$order_info['follow_times'],
            'issue_id' => (int)$issue_no['issue_id'],
            'user_coupon_id' => (int)$coupon_id,
            'order_identity' => '',
            'is_win_stop' => 0,
            'suite_id' => '',
            'tickets' => json_decode($order_info['tickets'],true),
        );
    }

    private function _betLCZ($order_info,$coupon_id)
    {
        return array(
            'act' => C('BET_ACT.BET_LZC'),
            'uid' => (int)$this->uid,
            'multiple' => (int)$order_info['multiple'],
            'issue_no' => (int)$order_info['issue_no'],
            'play_type' => (int)$order_info['play_type'],
            'bet_type' => (int)$order_info['bet_type'],
            'user_coupon_id' => (int)$coupon_id,
            'lottery_id' => (int)$order_info['lottery_id'],
            'total_amount' => (int)$order_info['total_amount'],
            'schedule_orders' => $order_info['schedule_orders'],
        );
    }

    private function _getDescLotteryId($lottery_id)
    {
//        $bet_desc = '其他彩种';
//        if (isJclq($lottery_id)) {
//            $bet_desc = '竞彩篮球';
//        } elseif (isJczq($lottery_id)) {
//            $bet_desc = '竞彩足球';
//        }elseif ($lottery_id == TIGER_LOTTERY_ID_OF_AH_11X5){
//            $bet_desc = '快乐11选5';
//        }elseif ($lottery_id == TIGER_LOTTERY_ID_OF_HB_11X5){
//            $bet_desc = '新11选5';
//        }elseif ($lottery_id == TIGER_LOTTERY_ID_OF_SD_11X5){
//            $bet_desc = '老11选5';
//        }elseif ($lottery_id == TIGER_LOTTERY_ID_OF_DLT){
//            $bet_desc = '大乐透';
//        }elseif ($lottery_id == TIGER_LOTTERY_ID_OF_SSQ){
//            $bet_desc = '双色球';
//        }
        $lottery_info = D('Lottery')->getLotteryInfo($lottery_id);
        return (string)$lottery_info['lottery_name'];
    }

    private function _validBetSign()
    {
        $id = $this->input('id') ? $this->input('id') : I('get.id');
        $product_name = $this->input('product_name') ? $this->input('product_name') : I('get.product_name');
        $sign = generateBetSign(array('id' => $id,'product_name' => $product_name));
        $sign_input = $this->input('sign') ? $this->input('sign') : I('get.sign');

        H5Log('sign_input:'.print_r($sign_input,true),'h5_bet_submit');
        H5Log('sign:'.print_r($sign,true),'h5_bet_submit');

        if ($sign_input != $sign){
            return false;
        }

        return true;
    }

    private function _validSchedule($schedule,$order_info,$bet_number,$is_optimize = false)
    {
        if (empty($schedule)){
            $this->responseError(RESPONSE_ERROR_BET_FAILS,'找不到指定的场次信息');
        }

        if (strtotime($schedule['schedule_official_end_time']) < time()){
            $this->responseError(RESPONSE_ERROR_BET_FAILS,'含有已经截止的场次：'.numberToWeek($schedule['schedule_week']).$schedule['schedule_round_no']);
        }

        $error = 0;
        if (isJcMix($order_info['lottery_id']) and !$is_optimize){
            $bet_play_type = explode('|',$bet_number);
            foreach ($bet_play_type as $types){
                $type = explode(':',$types);
                $play_lottery_id = $type[0];
                $odds = json_decode($schedule['schedule_odds'],true);
                if (!isset($odds[$play_lottery_id])){
                    $error++;
                    break;
                }
            }
        }

        if ($schedule['schedule_status'] !=1 or $error){
            $this->responseError(RESPONSE_ERROR_BET_FAILS,'含有不可投注或暂停销售的场次：'.numberToWeek($schedule['schedule_week']).$schedule['schedule_round_no']);
        }
    }
}