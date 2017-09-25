<?php
namespace Integral\Controller;
use Integral\Util\AppException;
use Think\Controller;
use Think\Exception;

class UserDrawController extends GlobalController {

    const ALREADY_RECEIVE = 1;

    public function draw($request_data){
        $uid = $request_data['uid'];
        $id = $request_data['id'];
        if(empty($uid) || empty($id)){
            throw new Exception(C('ERROR_MSG.INTEGRAL_USER_NOT_EXIST'), C('ERROR_CODE.INTEGRAL_USER_NOT_EXIST') );
        }

        $user_info = D('User')->getUserInfo($uid);
        if(empty($user_info)){
            throw new Exception(C('ERROR_MSG.INTEGRAL_USER_NOT_EXIST'), C('ERROR_CODE.INTEGRAL_USER_NOT_EXIST') );
        }

        $user_draw_info = $this->getUserDrawInfo($uid,$id);
        if(empty($user_draw_info)){
            throw new Exception(C('ERROR_MSG.USER_NOT_HAVE_THE_GOOD'), C('ERROR_CODE.USER_NOT_HAVE_THE_GOOD') );
        }

        $already_receive = $this->redis->sContains($this->_getDrawRedisKey(),$uid);
        $receive_status = $this->getDrawGoodOfReceive($uid,$id);
        if($already_receive || $receive_status){
            throw new Exception(C('ERROR_MSG.USER_IS_RECEIVE'), C('ERROR_CODE.USER_IS_RECEIVE') );
        }
        $this->redis->sAdd($this->_getDrawRedisKey(),$uid);
        return $this->_grantDrawGoodToUser($uid,$user_draw_info);
    }

    private function _grantDrawGoodToUser($uid,$user_draw_info){

        $dg_type = $user_draw_info['dg_type'];
        if($dg_type == C('DRAW_GOOD_TYPE.GRANT_COUPON')){
            //对接异步通知接口
            $coupon_id = $user_draw_info['udl_extral_info'];
            $data['uid'] = $uid;
            $data['coupon_id'] = $coupon_id;
            $data['log_type'] = 14;
            $msgQueueOfCoupon = new MsgQueueOfCouponController();
            $response_data = $msgQueueOfCoupon->send($coupon_id,$data);
        }elseif($dg_type == C('DRAW_GOOD_TYPE.DOUBLE_INTEGRAL')){
            M()->startTrans();
            $sign_integral = $user_draw_info['udl_extral_info'];
            $add_status = A('UserIntegral')->addUserIntegral($uid,$sign_integral,true,C('GAIN_INTEGRAL_TYPE.DRAW'));
            if(!$add_status){
                M()->rollback();
                throw new Exception(C('ERROR_MSG.SERVER_EXCEPTION'), C('ERROR_CODE.SERVER_EXCEPTION') );
            }
            M()->commit();
        }
        return $this->_updateUserDrawToReceiveStatus($uid);
    }

    private function _updateUserDrawToReceiveStatus($uid){
        $data['uid'] = $uid;
        $data['status'] = self::ALREADY_RECEIVE;
        return D('UserDrawLog')->updateUserDrawStatus($data);
    }

    public function getUserDrawInfo($uid,$id){
        $data['uid'] = $uid;
        $data['id'] = $id;
        return D('UserDrawLog')->getUserDrawLogInfo($data);
    }

    public function getTodayUserDrawInfo($uid){
        $data['uid'] = $uid;
        $user_draw_info = D('UserDrawLog')->getTodayUserDrawLogInfo($data);
        return $user_draw_info;
    }

    public function getDrawGoodOfReceive($uid,$id){
        $data['uid'] = $uid;
        $data['id'] = $id;
        return D('UserDrawLog')->getDrawGoodOfReceive($data);
    }

    public function addUserDraw($uid,$add_integral){
        $data = $this->_getWinningPreSentList();
        $id = random_draw($data);
        $draw_good_info = D('DrawGood')->getDrawGoodInfo($id);
        if(empty($draw_good_info)){
            return false;
        }
        return $this->_insertUserDrawLog($uid,$draw_good_info,0,$add_integral);
    }

    //插入签到日志
    private function _insertUserDrawLog($uid,$draw_good_info = array(),$status = 0,$add_integral=0){
        $dg_type = $draw_good_info['dg_type'];
        if($dg_type == C('DRAW_GOOD_TYPE.GRANT_COUPON')){
            $data['udl_extral_info'] = $draw_good_info['dg_extral_id'];
        }elseif($dg_type == C('DRAW_GOOD_TYPE.DOUBLE_INTEGRAL')){
            $now = time();
            $data['udl_extral_info'] = $add_integral;
        }elseif($dg_type == C('DRAW_GOOD_TYPE.NO_WINNING')){
            $data['udl_extral_info'] = '';
        }
        $data['dg_id'] = $draw_good_info['dg_id'];
        $data['dg_type'] = $dg_type;
        $data['uid'] = $uid;
        $data['udl_status'] = $status;    //操作人员ID
        return D('UserDrawLog')->insertUserDrawLog($data);
    }

    //TODO
    private function _getWinningPreSentList(){
        $total_precent = 0;
        $data = array();
        $draw_good_list = D('DrawGood')->getAllDrawGood();
        foreach($draw_good_list as $good){
            $data[$good['dg_id']] = $good['dg_winning_percent'];
            $total_precent += $good['dg_winning_percent'];
        }

        $data[1] += 10000 - $total_precent;

        return $data;
    }

    private function _getDrawRedisKey(){
        return C('REDIS_KEY').'draw_received:'.getCurrentDate();
    }





}