<?php
namespace Integral\Controller;
use Integral\Util\AppException;
use Think\Controller;
use Think\Exception;

class UserIntegralController extends GlobalController {

    public function getUserIntegralDetail($request_data){
        $uid = $request_data['uid'];
        $offset = $request_data['offset'];
        $limit = $request_data['limit'];
        if(empty($uid)){
            throw new Exception(C('ERROR_MSG.INTEGRAL_USER_NOT_EXIST'), C('ERROR_CODE.INTEGRAL_USER_NOT_EXIST') );
        }
        $user_info = D('User')->getUserInfo($uid);
        if(empty($user_info)){
            throw new Exception(C('ERROR_MSG.INTEGRAL_USER_NOT_EXIST'), C('ERROR_CODE.INTEGRAL_USER_NOT_EXIST') );
        }
        $user_integral_list = D('UserIntegralLog')->getUserIntegralList($request_data,$offset,$limit);
        return $this->_formatUserIntegralList($user_integral_list);
    }

    private function _formatUserIntegralList($user_integral_list){
        $data = array();
        foreach($user_integral_list as $user_integral_info){
            $data[] = array(
                'id' => $user_integral_info['uil_id'],
                'change_time' => $user_integral_info['uil_createtime'],
                'event_type' => $user_integral_info['uil_type'],
                'change_integral' => $user_integral_info['uil_change_integral'],
            );
        }
        return $data;
    }

    public function getIntegralGoodsList(){
        $integral_good_list = D('IntegralGood')->getIntegralGoodsList();
        return $this->_formatIntegralGoodList($integral_good_list);
    }

    private function _formatIntegralGoodList($integral_good_list){
        $data = array();
        foreach($integral_good_list as $integral_good){
            $data[$integral_good['ig_good_type']][] = array(
                'id' => $integral_good['ig_id'],
                'good_name' => $integral_good['ig_good_name'],
                'good_id' => $integral_good['ig_good_id'],
                'good_type' => $integral_good['ig_good_type'],
                'need_integral' => $integral_good['ig_integral'],
                'good_num' => $integral_good['ig_good_num'],
                'image' => $integral_good['ig_img_url'],
                'desc' => $integral_good['ig_desc'],
            );
        }
        return $data;
    }

    public function exchangeGood($request_data){
        $uid = $request_data['uid'];
        $good_id = $request_data['good_id'];

        if(empty($uid) || empty($good_id)){
            throw new Exception(C('ERROR_MSG.INTEGRAL_USER_NOT_EXIST'), C('ERROR_CODE.INTEGRAL_USER_NOT_EXIST') );
        }

        $user_info = D('User')->getUserInfo($uid);
        if(empty($user_info)){
            throw new Exception(C('ERROR_MSG.INTEGRAL_USER_NOT_EXIST'), C('ERROR_CODE.INTEGRAL_USER_NOT_EXIST') );
        }

        $goods_info = D('IntegralGood')->getIntegralGoodsInfo($good_id);
        if(empty($goods_info) || $goods_info['ig_status'] == 0){
            throw new Exception(C('ERROR_MSG.GOODS_OFF_SALE'), C('ERROR_CODE.GOODS_OFF_SALE') );
        }

        $verify_exchange_limit_time = $this->_verifyGoodsExchangeLimitTime($goods_info,$uid);
        if(!$verify_exchange_limit_time){
            throw new Exception(C('ERROR_MSG.INTEGRAL_GOODS_EXCHANGE_TIMES_LIMIT'), C('ERROR_CODE.INTEGRAL_GOODS_EXCHANGE_TIMES_LIMIT') );
        }

        //TODO 库存机制
        if($goods_info['ig_good_num'] <= 0){
            throw new Exception(C('ERROR_MSG.GOODS_OFF_SALE'), C('ERROR_CODE.GOODS_OFF_SALE') );
        }

        $user_integral_balance = D('UserIntegral')->getUserIntegralBalance($uid);
        if($user_integral_balance < $goods_info['ig_integral']){
            throw new Exception(C('ERROR_MSG.INTEGRAL_NOT_ENOUGH'), C('ERROR_CODE.INTEGRAL_NOT_ENOUGH') );
        }

        $lock_status = $this->_lockIntegralGood($good_id);
        if(!$lock_status){
            throw new Exception(C('ERROR_MSG.SERVER_EXCEPTION'), C('ERROR_CODE.SERVER_EXCEPTION') );
        }

        M()->startTrans();

        $reduce_status = D('IntegralGood')->reduceIntegralGoodNum($good_id);
        if(!$reduce_status){
            M()->rollback();
            throw new Exception(C('ERROR_MSG.SERVER_EXCEPTION'), C('ERROR_CODE.SERVER_EXCEPTION') );
        }

        $update_status = D('UserIntegral')->updateUserIntergral($uid,$goods_info['ig_integral'],false);
        if(!$update_status){
            M()->rollback();
            throw new Exception(C('ERROR_MSG.SERVER_EXCEPTION'), C('ERROR_CODE.SERVER_EXCEPTION') );
        }
        $insert_status = $this->_insertUserIntegralLog($uid,$goods_info['ig_integral'],C('GAIN_INTEGRAL_TYPE.EXCHANGE'),$good_id);
        if(!$insert_status){
            M()->rollback();
            throw new Exception(C('ERROR_MSG.SERVER_EXCEPTION'), C('ERROR_CODE.SERVER_EXCEPTION') );
        }

        M()->commit();
        $this->_unlockIntegralGood($good_id);

        $response_data['good_id'] = $goods_info['ig_good_id'];
        $response_data['good_type'] = $goods_info['ig_good_type'];
        return $response_data;
    }

    private function _verifyGoodsExchangeLimitTime($goods_info,$uid){
        $exchange_limit_times = $goods_info['ig_exchange_limit_times'];
        if(empty($exchange_limit_times)){
            return true;
        }
        $data['goods_id'] = $goods_info['ig_id'];
        $data['uid'] = $uid;
        $exchanged_count = D('UserIntegralLog')->getExchangeGoodsCountById($data);
        $exchanged_count = empty($exchanged_count) ? 0 : $exchanged_count;
        if($exchanged_count >= $exchange_limit_times){
            return false;
        }
        return true;
    }

    private function _lockIntegralGood($good_id,$expire_time = 5){
        $redis_key = $this->_getIntegralGoodLockKey($good_id);
        $is_lock = $this->redis->setnx($redis_key,time()+$expire_time);
        if(!$is_lock){
            $lock_time = $this->redis->get($redis_key);
            if(time()>$lock_time){
                $this->_unlockIntegralGood($redis_key);
                $is_lock = $this->redis->setnx($redis_key,time()+$expire_time);
            }
        }
        return $is_lock?true:false;
    }

    private function _unlockIntegralGood($good_id){
        return $this->redis->del($this->_getIntegralGoodLockKey($good_id));
    }

    private function _getIntegralGoodLockKey($good_id){
        return C('REDIS_KEY').'integral_good_lock'.$good_id;
    }

    //插入获得积分日志
    private function _insertUserIntegralLog($uid,$add_integral,$gain_type = 1,$extral_id=0){
        $user_integral_info = D('UserIntegral')->getUserIntegralInfo($uid);
        $data['uid'] = $uid;
        $data['uil_balance'] = $user_integral_info['user_integral_balance'];
        $data['uil_type'] = $gain_type;    //积分变动类型 1:签到获得  2:下单获得  3:兑换红包
        $data['uil_change_integral'] = $add_integral;
        $data['uil_extral_id'] = $extral_id;
        $data['operator_id'] = $uid;    //操作人员ID
        return D('UserIntegralLog')->insertUserIntegralLog($data);
    }

    public function getUserIntegralInfo($uid){
        $user_integral_info = D('UserIntegral')->getUserIntegralInfo($uid);
        if(empty($user_integral_info)){
            $add_status = D('UserIntegral')->addUserIntergralRecord(array('uid'=>$uid));
            if(!$add_status){
                throw new Exception(C('ERROR_MSG.SERVER_EXCEPTION'), C('ERROR_CODE.SERVER_EXCEPTION') );
            }
            $user_integral_info = D('UserIntegral')->getUserIntegralInfo($uid);
        }
        return $user_integral_info;
    }

    //增加用户积分
    public function addUserIntegral($uid,$add_integral,$is_add = true,$gain_type = 1,$extral_id=0){
        $user_integral_info = $this->getUserIntegralInfo($uid);
        $update_status = D('UserIntegral')->updateUserIntergral($uid,$add_integral,$is_add);
        //增加积分日志
        $insert_status = $this->_insertUserIntegralLog($uid,$add_integral,$gain_type,$extral_id);
        return $user_integral_info && $update_status && $insert_status;
    }

    public function addUserIntegralForOrder($request_data){
        $add_integral = (int)$request_data['add_integral'];
        $add_exp = (int)$request_data['add_exp'];
        $uid = $request_data['uid'];
        $order_id = $request_data['order_id'];
        $order_type = $request_data['order_type'];
        if(empty($uid) || empty($order_id)){
            throw new Exception(C('ERROR_MSG.INTEGRAL_USER_NOT_EXIST'), C('ERROR_CODE.INTEGRAL_USER_NOT_EXIST') );
        }

        $user_info = D('User')->getUserInfo($uid);
        if(empty($user_info)){
            throw new Exception(C('ERROR_MSG.INTEGRAL_USER_NOT_EXIST'), C('ERROR_CODE.INTEGRAL_USER_NOT_EXIST') );
        }

        $add_integral = $this->_getVipAddIntegral($add_integral,$user_info['vip_level_id']);
        if(!is_numeric($add_integral) || $add_integral < 0){
            ApiLog('1:$request_data:'.print_r($request_data,true),'addUserIntegralForOrder');
            throw new Exception(C('ERROR_MSG.DATA_IS_INVALID'), C('ERROR_CODE.DATA_IS_INVALID') );
        }

        if(!is_numeric($add_exp) || $add_exp < 0){
            throw new Exception(C('ERROR_MSG.DATA_IS_INVALID：'), C('ERROR_CODE.DATA_IS_INVALID') );
        }

        if($order_type != 3){
            $already_receive = $this->_isReceivedOrder($order_id);
            //$already_receive = $this->redis->sContains($this->_getOrderRedisKey(),$order_id);
            if($already_receive){
                ApiLog('2:$request_data:'.print_r($request_data,true),'addUserIntegralForOrder');
                throw new Exception(C('ERROR_MSG.SERVER_EXCEPTION'), C('ERROR_CODE.SERVER_EXCEPTION') );
            }
        }
        $this->redis->sAdd($this->_getOrderRedisKey(),$order_id);
        M()->startTrans();
        if($add_integral > 0){
            $add_status = $this->addUserIntegral($uid,$add_integral,true,C('GAIN_INTEGRAL_TYPE.ORDER'),$order_id);
            if(!$add_status){
                M()->rollback();
                ApiLog('3:$request_data:'.print_r($request_data,true),'addUserIntegralForOrder');
                throw new Exception(C('ERROR_MSG.SERVER_EXCEPTION'), C('ERROR_CODE.SERVER_EXCEPTION') );
            }
        }
        //增加经验
        $add_status = A('User')->addUserExp($uid,$add_exp);
        if(!$add_status){
            M()->rollback();
            throw new Exception(C('ERROR_MSG.SERVER_EXCEPTION'), C('ERROR_CODE.SERVER_EXCEPTION') );
        }

        M()->commit();
        return $add_status;
    }

    private function _getVipAddIntegral($add_integral,$vip_level_id){
        $precent = D('VipLevel')->getIntegralOrderPrecent($vip_level_id);
        return round($add_integral*$precent);
    }

    private function _isReceivedOrder($order_id){
        $redis_key_list = $this->_getLastThreeDaysList();
        foreach($redis_key_list as $redis_key){
            $is_exists = $this->redis->sContains($redis_key,$order_id);
            if($is_exists){
                break;
            }
        }
        return $is_exists;
    }

    private function _getLastThreeDaysList(){
        for($i=0;$i<3;$i++){
            $data[] = C('REDIS_KEY').'order_received:'.date('Y-m-d',time()-$i*24*60*60);
        }
        return $data;
    }

    private function _getOrderRedisKey(){
        return C('REDIS_KEY').'order_received:'.getCurrentDate();
    }

    public function addUserIntegralForVipGifts($request_data){
        $add_integral = (int)$request_data['add_integral'];
        $uids = $request_data['uids'];
        $vg_id = $request_data['vg_id'];
        if(empty($uids) || empty($vg_id)){
            throw new Exception(C('ERROR_MSG.INTEGRAL_USER_NOT_EXIST'), C('ERROR_CODE.INTEGRAL_USER_NOT_EXIST') );
        }
        foreach($uids as $uid){
            $user_info = D('User')->getUserInfo($uid);
            if(empty($user_info)){
                throw new Exception(C('ERROR_MSG.INTEGRAL_USER_NOT_EXIST'), C('ERROR_CODE.INTEGRAL_USER_NOT_EXIST') );
            }

            if(!is_numeric($add_integral) || $add_integral < 0){
                throw new Exception(C('ERROR_MSG.DATA_IS_INVALID'), C('ERROR_CODE.DATA_IS_INVALID') );
            }

            $already_receive = $this->_isReceivedVipGifts($uid);
            $already_receive = false;
            if($already_receive){
                ApiLog('2:$request_data:'.print_r($request_data,true),'addUserIntegralForVipGifts');
                throw new Exception(C('ERROR_MSG.SERVER_EXCEPTION'), C('ERROR_CODE.SERVER_EXCEPTION') );
            }
            $this->redis->sAdd($this->_getVipGiftsRedisKey(),$uid);
            M()->startTrans();
            if($add_integral > 0){
                $add_status = $this->addUserIntegral($uid,$add_integral,true,C('GAIN_INTEGRAL_TYPE.VIP_GIFTS'),$vg_id);
                if(!$add_status){
                    M()->rollback();
                    ApiLog('3:$request_data:'.print_r($request_data,true),'addUserIntegralForVipGifts');
                    throw new Exception(C('ERROR_MSG.SERVER_EXCEPTION'), C('ERROR_CODE.SERVER_EXCEPTION') );
                }
            }
            M()->commit();
        }
        return $add_status;
    }

    private function _isReceivedVipGifts($uid){
        return  $this->redis->sContains($this->_getVipGiftsRedisKey(),$uid);
    }


    private function _getVipGiftsRedisKey(){
        return C('REDIS_KEY').'vip_gifts_received:'.getCurrentDate();
    }



}