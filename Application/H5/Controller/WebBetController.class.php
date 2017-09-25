<?php

namespace H5\Controller;
use H5\Middleware\WebBetMiddleware;
use Home\Controller\BettingBaseController;
use Home\Util\Factory;
use Think\Controller;
use Think\Exception;

class WebBetController extends BaseController {

    private $_expire_time = 1800;
    const SIGN_KEY = [
        'YQDS' => 'db919a4013f3c36f20',
        'LHCP' => 'Ehcv2b1AvWAMSey2',
    ];
    const WEB_BET_KEY = 'web_bet:';
    private $_redis = null;

    protected static $pre_bet_id;
    protected static $product_name;

    public function __construct(){
        if(empty($this->_redis)){
            $this->_redis = Factory::createAliRedisObj();
            $this->_redis->select(0);
        }
        //parent::__construct();
    }

    public function preBet(){
        try{
            $lottery_id = I('get.lottery_id');
            H5Log('$lottery_id:'.print_r($lottery_id,true),'debug_base');
            if (isJCLottery($lottery_id) or empty($lottery_id)){
                self::$product_name = I('product_name');
                if (self::$product_name == 'YQDS'){
                    $web_bet_url = $this->_betJCForYQDS();
                }else{
                    parent::__construct();
                    $valid = WebBetMiddleware::boot($this->getResquest());
                    if (!$valid){
                        $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
                    }
                    $this->_betJC();
                }
            }else{
                parent::__construct();
                $valid = WebBetMiddleware::boot($this->getResquest());
                if (!$valid){
                    $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
                }
                if (isZcsfc($lottery_id)) {
                    $web_bet_url = $this->_betLZC();
                }else{
                    $web_bet_url = $this->_betSZC();
                }
            }
        }catch(Exception $e){
            H5Log($product_name.'文件:'.$e->getFile().';行数:'.$e->getLine().';出错信息:'.$e->getMessage(),'WebBetException');
            $response_data['error_code'] = C('ERROR_CODE.FAIL');
            $response_data['code'] = RESPONSE_ERROR_PARAM_FAILS;
            $response_data['msg'] =  $e->getMessage();
            $response_data['data'] = [];
            $this->ajaxReturn($response_data);
        }
        $response_data['error_code'] = C('ERROR_CODE.SUCCESS');
        $response_data['data'] = array(
            'url' => (string)$web_bet_url,
            'tiger_id' => (string)self::$pre_bet_id,
        );
        H5Log('$response_data:'.print_r($response_data,true),'debug');
        $this->ajaxReturn($response_data);
    }

    private function _betSZC()
    {
        //parent::__construct();
//        if (!$this->checkLogin()){
//            $this->responseError(RESPONSE_ERROR_WITHOUT_LOGIN);
//        }
        //$pre_bet_id = $this->_buildPreBetId(uniqid());
        $pre_bet_id = $this->_buildPreBetId(I('get.sign'));
        self::$pre_bet_id = $pre_bet_id;
        self::$product_name = I('get.product_name');
        if (empty(self::$product_name)){
            $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
        }

        //检查签名
        $check_sign = $this->_checkPreBetSign(I('get.sign'),$this->input('lottery_id'),$this->input('issue_id'),$this->input('total_amount'));

        if (!$check_sign){
            $this->responseError(RESPONSE_ERROR_BET_FAILS,'签名错误');
        }

        $redis_key = self::WEB_BET_KEY.self::$product_name.':'.$pre_bet_id;
        $bet_info['multiple'] = $this->input('multiple');
        $bet_info['follow_times'] = $this->input('follow_times');
        $bet_info['lottery_id'] = $this->input('lottery_id');
        //$bet_info['coupon_id'] = (int)$this->input('coupon_id');
        $bet_info['coupon_id'] = 0;
        $bet_info['order_identity'] = $this->input('order_identity');
        $bet_info['tickets'] = json_encode($this->input('tickets'));
        $bet_info['issue_id'] = $this->input('issue_id');
        $bet_info['total_amount'] = (float)$this->input('total_amount');
        $this->_redis->hMset($redis_key,$bet_info);
        $this->_redis->expire($redis_key,300);
        $sign = generateBetSign(['id' => $pre_bet_id,'product_name' => self::$product_name]);
        $this->response([
            'id' => $pre_bet_id,
            'sign' => $sign,
        ]);
    }

    private function _betLZC()
    {
        $pre_bet_id = $this->_buildPreBetId(I('get.sign'));
        self::$pre_bet_id = $pre_bet_id;
        self::$product_name = I('get.product_name');
        if (empty(self::$product_name)){
            $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
        }

        //检查签名
        $check_sign = $this->_checkPreBetSign(I('get.sign'),$this->input('lottery_id'),$this->input('issue_no'),$this->input('total_amount'));

        if (!$check_sign){
            $this->responseError(RESPONSE_ERROR_BET_FAILS,'签名错误');
        }

        $redis_key = self::WEB_BET_KEY.self::$product_name.':'.$pre_bet_id;
        $bet_info['multiple'] = $this->input('multiple');
        $bet_info['lottery_id'] = $this->input('lottery_id');
        $bet_info['bet_type'] = $this->input('bet_type');
        $bet_info['play_type'] = $this->input('play_type');
        $bet_info['coupon_id'] = 0;
        $bet_info['stake_count'] = $this->input('stake_count');
        $bet_info['schedule_orders'] = json_encode($this->input('schedule_orders'));
        $bet_info['issue_no'] = $this->input('issue_no');
        $bet_info['total_amount'] = (float)$this->input('total_amount');
        $this->_redis->hMset($redis_key,$bet_info);
        $this->_redis->expire($redis_key,300);
        $sign = generateBetSign(['id' => $pre_bet_id,'product_name' => self::$product_name]);
        $this->response([
            'id' => $pre_bet_id,
            'sign' => $sign,
        ]);
    }

    private function _betJC()
    {
        //parent::__construct();
//        if (!$this->checkLogin()){
//            $this->responseError(RESPONSE_ERROR_WITHOUT_LOGIN);
//        }

        self::$product_name = I('get.product_name');
        if (empty(self::$product_name)){
            $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
        }

        //检查签名
        $check_sign = $this->_checkPreBetSign(I('get.sign'),$this->input('lottery_id'),$this->input('play_type'),$this->input('total_amount'));

        if (!$check_sign){
            $this->responseError(RESPONSE_ERROR_BET_FAILS,'签名错误');
        }

        if ($this->input('optimize_ticket_list')){
            $this->_betJCForOptimize();
        }else{
            $this->_betJCForCommon();
        }
    }

    private function _betJCForOptimize()
    {
        $s_key = I('sign');
        $pre_bet_id = $this->_buildPreBetId($s_key);
        $product_name = self::$product_name;
        //$expire_time = $this->_getBetExpireTimeByScheduleOrders($schedule_orders,$lottery_id);
        $expire_time = 1800;
        $bet_info = $this->_buildOrderParamsForOptimize();
        $redis_key = self::WEB_BET_KEY.$product_name.':'.$pre_bet_id;
        $this->_redis->hMset($redis_key,$bet_info);
        $this->_redis->expire($redis_key,$expire_time);
        $sign = generateBetSign(['id' => $pre_bet_id,'product_name' => self::$product_name]);
        $this->response([
            'id' => $pre_bet_id,
            'sign' => $sign,
        ]);
    }

    private function _betJCForCommon()
    {
        $product_name = self::$product_name;
        $multiple = $this->input('multiple');
        $series = $this->input('series');
        $lottery_id = $this->input('lottery_id');
        $play_type = $this->input('play_type');
        $stake_count = $this->input('stake_count');
        $bonus_range = $this->input('bonus_range');
        $sign = $this->input('sign');
        $total_amount = $this->input('total_amount');
        $schedule_orders = $this->input('schedule_orders');
        $s_key = I('sign');
        $pre_bet_id = $this->_savePreBetInfo($product_name,$multiple,$series,$lottery_id,$play_type,$stake_count,$bonus_range,$total_amount,$schedule_orders,$s_key);
        $sign = generateBetSign(['id' => $pre_bet_id,'product_name' => self::$product_name]);
        $this->response([
            'id' => $pre_bet_id,
            'sign' => $sign,
        ]);
    }

    private function _betJCForYQDS()
    {
        $product_name = I('product_name');
        $multiple = I('multiple');
        $series = I('series');
        $lottery_id = I('lottery_id');
        $play_type = I('play_type');
        $stake_count = I('stake_count');
        $bonus_range = I('bonus_range');
        $sign = I('sign');
        $total_amount = I('total_amount');
        $schedule_orders = I('schedule_orders');
        $s_key = I('skey');
        self::$product_name = $product_name;
        $this->_checkPreBetRequestData($product_name,$multiple,$series,$lottery_id,$play_type,$stake_count,$bonus_range,$sign,$total_amount,$schedule_orders,$s_key);
        $pre_bet_id = $this->_savePreBetInfo($product_name,$multiple,$series,$lottery_id,$play_type,$stake_count,$bonus_range,$total_amount,$schedule_orders,$s_key);
        self::$pre_bet_id = $pre_bet_id;
        //todo target test
        $web_bet_url = $this->_buildWebUrl($pre_bet_id,$product_name);
        return $web_bet_url;
    }

    private function _getProductName($_post_tiger_id){
        $tiger_ids = C('THIRD_BET_TIGER_ID');
        foreach($tiger_ids as $product_name=>$tiger_id){
            if($_post_tiger_id == $tiger_id){
                return $product_name;
            }
        }
    }

    private function _buildWebUrl($pre_bet_id,$product_name){
        return C('REQUEST_HOST'). U('Index/index',array('id'=>$pre_bet_id,'product_name'=>$product_name,'type'=>1));
    }

    private function _savePreBetInfo($product_name,$multiple,$series,$lottery_id,$play_type,$stake_count,$bonus_range,$total_amount,$schedule_orders,$s_key){
        $pre_bet_id = $this->_buildPreBetId($s_key);
        $redis_key = self::WEB_BET_KEY.$product_name.':'.$pre_bet_id;
        $exist_status = $this->_redis->exists($redis_key);
        if($exist_status){
            throw new Exception(C('ERROR_MSG.REDIS_KEY_IS_EXIST'),C('ERROR_CODE.REDIS_KEY_IS_EXIST'));
        }

        $expire_time = $this->_getBetExpireTimeByScheduleOrders($schedule_orders,$lottery_id);
        $bet_info['multiple'] = $multiple;
        $bet_info['series'] = $series;
        $bet_info['lottery_id'] = $lottery_id;
        $bet_info['play_type'] = $play_type;
        $bet_info['stake_count'] = $stake_count;
        $bet_info['bonus_range'] = $bonus_range;
        $bet_info['total_amount'] = $total_amount;
        $bet_info['schedule_orders'] = json_encode($schedule_orders,true);

        H5Log('yqds_bet_info:'.print_r($bet_info,true),'yqds_bet');

        $this->_redis->hMset($redis_key,$bet_info);
        $this->_redis->expire($redis_key,$expire_time);
        return $pre_bet_id;
    }

    private function _getBetExpireTimeByScheduleOrders($schedule_orders,$lottery_id){
        H5Log('$schedule_orders:'.print_r($schedule_orders,true),'testlifeng');
        $schedule_round_ids = array();
        foreach($schedule_orders as $schedule_order_info){
            H5Log('match_round_id:'.$schedule_order_info['match_round_id'],'testlifeng');
            $match_round_id = explode('-',$schedule_order_info['match_round_id']);
            $schedule_round_ids[] = array(
                'schedule_day' => $match_round_id[0],
                'schedule_round_no' => $match_round_id[1],
            );
        }
        H5Log('$schedule_round_ids:'.print_r($schedule_round_ids,true),'testlifeng');
        $schedule_end_time_list = array();
        foreach($schedule_round_ids as $schedule_info){
            $schedule_end_time_list[] = $this->_getScheduleEndTime($lottery_id,$schedule_info['schedule_day'],$schedule_info['schedule_round_no']);
        }
        H5Log('$schedule_end_time_list:'.print_r($schedule_end_time_list,true),'testlifeng');
        $expire_time =  $this->_getBetExpireTime($schedule_end_time_list);
        H5Log('$expire_time:'.$expire_time,'testlifeng');
        return $expire_time;
    }

    private function _getScheduleEndTime($lottery_id,$schedule_day,$shedule_round_no){//queryScheduleIdsFromDateAndNo
        $schedule_end_time = D('JcSchedule')->getScheduleEndTimeByDayAndRoundNO($schedule_day,$shedule_round_no);
        if(empty($schedule_end_time)){
            return time()+$this->_expire_time;
        }
        $lottery_info = D('Home/Lottery')->getLotteryInfo($lottery_id);
        //TODO  660
        return strtotime($schedule_end_time) - $lottery_info['lottery_ahead_endtime'];
    }

    private function _getBetExpireTime($schedule_end_time_list){
        foreach($schedule_end_time_list as $schedule_end_time){
            $diff_time = $schedule_end_time - time();
            if($diff_time <= $this->_expire_time){
                $this->_expire_time = $diff_time;
            }
        }
        return $this->_expire_time;
    }

    private function _buildPreBetId($s_key){
        $randomStr = strtoupper(random_string(6));
        return 'PB'.date('ymdhis').$s_key.$randomStr;
    }

    private function _checkPreBetRequestData($product_name,$multiple,$series,$lottery_id,$play_type,$stake_count,$bonus_range,$sign,$total_amount,$schedule_orders,$s_key){

        if(empty($product_name) || (int)$multiple < 1 || empty($series) || empty($lottery_id) ||
            empty($play_type) || (int)$stake_count < 1 || empty($bonus_range) ||
            empty($sign) || (int)$total_amount < 1 || empty($schedule_orders) || empty($s_key)){
            throw new Exception(C('ERROR_MSG.DATA_IS_INVALID'),C('ERROR_CODE.DATA_IS_INVALID'));
        }

        if(!$this->_checkPreBetSign($sign,$lottery_id,$series,$bonus_range)){
            throw new Exception(C('ERROR_MSG.SIGN_ERROR'),C('ERROR_CODE.SIGN_ERROR'));
        }

    }

    private function _checkPreBetSign($sign,$lottery_id,$series,$bonus_range){
        $sign_key = self::SIGN_KEY[self::$product_name];
        H5Log('$sign_key:'.$sign_key,'debug');
        H5Log('$sign:'.$sign,'debug');
        H5Log('$sign2:'.md5($lottery_id.$series.$bonus_range.$sign_key),'debug');
        return  $sign == md5($lottery_id.$series.$bonus_range.$sign_key) ? true : false;
    }

    private function _buildOrderParamsForOptimize(){
        $bet_info['lottery_id'] = (int)$this->input('lottery_id');
        $bet_info['total_amount'] = (float)$this->input('total_amount');
        $bet_info['series'] = $this->input('series');
        $bet_info['stake_count'] = (int)$this->input('stake_count');
        $bet_info['order_multiple'] = (int)$this->input('order_multiple');
        $bet_info['coupon_id'] = 0;
        $bet_info['play_type'] = (int)$this->input('play_type');
        $bet_info['select_schedule_ids'] = json_encode($this->input('select_schedule_ids'),true);
        $bet_info['optimize_ticket_list'] = json_encode($this->input('optimize_ticket_list'),true);
        return $bet_info;
    }
}