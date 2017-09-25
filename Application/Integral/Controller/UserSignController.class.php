<?php
namespace Integral\Controller;
use Integral\Util\AppException;
use Think\Exception;

class UserSignController extends GlobalController
{
    private $_start_timestamp;
    public function __construct(){
        $this->_start_timestamp = mktime(0,0,0,03,22,2017);
        parent::__construct();
    }

    //用户签到
    public function sign($request_data){
        $uid = $request_data['uid'];
        if(empty($uid)){
            throw new Exception(C('ERROR_MSG.INTEGRAL_USER_NOT_EXIST'), C('ERROR_CODE.INTEGRAL_USER_NOT_EXIST') );
        }

        $user_info = D('User')->getUserInfo($uid);
        if(empty($user_info)){
            throw new Exception(C('ERROR_MSG.INTEGRAL_USER_NOT_EXIST'), C('ERROR_CODE.INTEGRAL_USER_NOT_EXIST') );
        }

        $now = isset($request_data['time']) ? $request_data['time'] : time();

        //签到
        $is_sign = $this->_getSignFromRedis($uid,$now);
        if(!$is_sign){
            M()->startTrans();
            $this->_signOnRedis($uid,$now);
            $sign_status = $this->addUserSign($uid,$now);
            if(!$sign_status){
                M()->rollback();
                $this->_cancelSignOnRedis($uid,$now);
                throw new Exception(C('ERROR_MSG.SERVER_EXCEPTION'), C('ERROR_CODE.SERVER_EXCEPTION') );
            }
            //增加积分
            $add_integral = $this->getTodaySignedIntegral($uid,$now);

            $add_status = A('UserIntegral')->addUserIntegral($uid,$add_integral);
            if(!$add_status){
                M()->rollback();
                $this->_cancelSignOnRedis($uid,$now);
                throw new Exception(C('ERROR_MSG.SERVER_EXCEPTION'), C('ERROR_CODE.SERVER_EXCEPTION') );
            }

            //增加经验
            $add_status = A('User')->addUserExp($uid,$add_integral);
            if(!$add_status){
                M()->rollback();
                $this->_cancelSignOnRedis($uid,$now);
                throw new Exception(C('ERROR_MSG.SERVER_EXCEPTION'), C('ERROR_CODE.SERVER_EXCEPTION') );
            }

            $udl_id = A('UserDraw')->addUserDraw($uid,$add_integral);
            if(!$udl_id){
                M()->rollback();
                $this->_cancelSignOnRedis($uid,$now);
                throw new Exception(C('ERROR_MSG.SERVER_EXCEPTION'), C('ERROR_CODE.SERVER_EXCEPTION') );
            }
            M()->commit();
        }else{
            $add_integral = $this->getTodaySignedIntegral($uid,$now);
        }
        $user_draw_info = A('UserDraw')->getTodayUserDrawInfo($uid);
        if(empty($user_draw_info)){
            throw new Exception(C('ERROR_MSG.NOT_DRAW_INFO'), C('ERROR_CODE.NOT_DRAW_INFO') );
        }
        return $this->_getResponseData($user_draw_info,$uid,$now,$add_integral);
    }

    public function getTodaySignedIntegral($uid,$now){
        return getIntegralBySignCount($this->_getSignCountFromRedis($uid,$now));
    }

    private function _getResponseData($user_draw_info,$uid,$now,$add_integral){
        $draw_good_info = D('DrawGood')->getDrawGoodInfo($user_draw_info['dg_id']);
        return array(
            'sign_integral' => $add_integral,
            'sign_days' => $this->_getSignCountFromRedis($uid,$now),
            'gift_url'  => $draw_good_info['dg_img_url'],
            'gift_id'   => $user_draw_info['udl_id'],
            'gift_name'   => $draw_good_info['dg_title'],
            'is_get_gift' => $user_draw_info['udl_status'],
            'sign_list'  => $this->_getSignList($uid),
        );
    }

    private function _getSignList($uid){
        $now = time();
        $sign_count = $this->_getSignCountFromRedis($uid,$now);
        for($i = 0;$i < 5;$i++){
            $data[] = array(
                'point' => getIntegralBySignCount($sign_count),
                'date'  => time() + 24*60*60*$i,
            );
            $sign_count++;
        }
        return $data;
    }

    public function addUserSign($uid,$now){
        $user_sign_info = $this->getUserSignInfo($uid);
        $update_status = $this->_updateUserSignInfo($user_sign_info,$now);

        //增加签到日志
        $insert_log = $this->_insertUserSignLog($uid,$now);
        return $user_sign_info && $update_status && $insert_log;
    }

    public function getUserSignInfo($uid){
        $user_sign_info = D('UserSign')->getUserSignInfoByUid($uid);
        if(empty($user_sign_info)){
            $add_status = D('UserSign')->addUserSignRecord($uid);
            if(!$add_status){
                AppException::throwException(C('ERROR_CODE.SERVER_EXCEPTION'),C('ERROR_MSG.SERVER_EXCEPTION'));
            }
            $user_sign_info = D('UserSign')->getUserSignInfoByUid($uid);
        }
        return $user_sign_info;
    }

    public function isSignToday($uid){
        $now = time();
        return $this->_getSignFromRedis($uid,$now);
    }

    public function getSignCount($uid){
        $now = time();
        $sign_count = 0;
        $is_sign = $this->_getSignFromRedis($uid,$now);
        if($is_sign){
            $sign_count = $this->_getSignCountFromRedis($uid,$now);
        }else{
            $yesterday = $now - 24*60*60;
            $sign_count = $this->_getSignCountFromRedis($uid,$yesterday);
        }
        return $sign_count;

    }

    //更改用户签到信息
    private function _updateUserSignInfo($user_sign_info,$now){
        $data['user_sign_sign_count'] = $this->_getSignCountFromRedis($user_sign_info['uid'],$now);
        $data['user_sign_total_count'] = $this->_getTotalSignCountFromRedis($user_sign_info['uid']);
        $data['user_sign_signtime']  = getCurrentTime();
        $data['user_sign_last_signtime'] = $user_sign_info['user_sign_signtime'];
        $data['user_sign_modifytime']  = getCurrentTime();
        return M('UserSign')->where(array('uid'=>$user_sign_info['uid']))->save($data);
    }

    //插入签到日志
    private function _insertUserSignLog($uid,$now){
        $data['uid'] = $uid;
        $data['usl_last_signtime'] = getCurrentTime();
        $data['usl_sign_count']  = $this->_getSignCountFromRedis($uid,$now);
        return D('UserSignLog')->insertUserSignLog($data);
    }

    private function _signOnRedis($uid, $now = 0) {
        if (empty($now)) {
            $now = time();
        }
        $offset = $this->_getRedisOffset($now);
        return $this->redis->setBit($this->_getSignRedisKey($uid), $offset, 1);
    }

    private function _cancelSignOnRedis($uid, $now = 0) {
        if (empty($now)) {
            $now = time();
        }
        $offset = $this->_getRedisOffset($now);
        return $this->redis->setBit($this->_getSignRedisKey($uid), $offset, 0);
    }

    private function _getSignFromRedis($uid, $now = 0) {
        if (empty($now)) {
            $now = time();
        }
        $offset = $this->_getRedisOffset($now);
        return $this->redis->getBit($this->_getSignRedisKey($uid), $offset);
    }

    private function _getSignCountFromRedis($uid ,$now = 0) {
        if (empty($now)) {
            $now = time();
        }
        $sign_count = 0;
        $offset = $this->_getRedisOffset($now);
        $is_signed = $this->redis->getBit($this->_getSignRedisKey($uid), $offset);
        while($is_signed){
            $offset--;
            $sign_count++;
            $is_signed = $this->redis->getBit($this->_getSignRedisKey($uid), $offset);
        }
        return $sign_count;
    }

    private function _getRedisOffset($now){
        return intval(($now - $this->_start_timestamp) / (60*60*24)) + 1;
    }

    private function _getTotalSignCountFromRedis($uid) {
        return $this->redis->bitCount($this->_getSignRedisKey($uid));
    }

    private function _getSignRedisKey($uid){
        return C('REDIS_KEY').'sign:'.$uid;
    }




}