<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;

class RobotCouponRecordController extends GlobalController{

    const ACTIVITY_START_TIME = '2016-10-01 00:00:00';
    const ACTIVITY_END_TIME = '2016-10-08 00:00:00';
    const MAX_RANK = 30;

    private $_readDB = 'mysql://tigercai_server:e4huY8J7e4@rr-bp1y62h5vwc8o62yt.mysql.rds.aliyuncs.com:3306/tigercai';


    public function add(){
        if($_POST){
            $rcr_user_tel = I('rcr_user_tel');
            $is_tel = $this->_checkTelFormat($rcr_user_tel);
            if(!$is_tel){
                $this->error('请输入正确的手机格式');
            }

            if($this->_telIsExist($rcr_user_tel)){
                $this->error('该手机号码已经存在');
            }
        }

        parent::add();
    }

    public function edit(){
        if($_POST){
            $rcr_user_tel = I('rcr_user_tel');
            $is_tel = $this->_checkTelFormat($rcr_user_tel);
            if(!$is_tel){
                $this->error('请输入正确的手机格式');
            }

            if($this->_telIsExist($rcr_user_tel)){
                $this->error('该手机号码已经存在');
            }
        }

        parent::edit();
    }

    private function _telIsExist($rcr_user_tel){
        $where['user_telephone'] = $rcr_user_tel;
        return M('User')->where($where)->find();
    }

    private function _checkTelFormat($tel){
        $match = "/1[34578]{1}\d{9}$/";
        if(empty($tel)){
            return false;
        }
        return preg_match($match, $tel);
    }

    public function rank(){

        $where['coupon_id'] = array('IN',$this->_getCouponIdList());
        $where['user_coupon_create_time'] = array(array('EGT',self::ACTIVITY_START_TIME),array('ELT',self::ACTIVITY_END_TIME));
        //$activityCouponRank =  M('UserCoupon')->field('SUM(user_coupon_amount) as amount,uid')->where($where)->group('uid')->limit($offset,$limit)->select();
        $activityCouponRank =  M('UserCoupon')->db(1,$this->_readDB,true)->field('SUM(user_coupon_amount) as amount,uid')->where($where)->group('uid')->select();
        ApiLog('sql:' . M('UserCoupon')->getLastSql(), 'readDB');
        M('UserCoupon')->db(0);
        $activityCouponRank = $this->_sortRankByAmount($activityCouponRank);
        ApiLog('$activityCouponRank:' . print_r($activityCouponRank,true), 'adminCouponRank');
        foreach($activityCouponRank as $k => $coupon){
            if($k >= self::MAX_RANK){
                break;
            }
            $newCouponRank[$k]['amount'] = $coupon['amount'];
            $newCouponRank[$k]['user_tel'] = M('User')->where(array('uid'=>$coupon['uid']))->getField('user_telephone');
            $newCouponRank[$k]['rank'] = $k+1;
        }
        $this->assign('list',$newCouponRank);
        $this->display('rank');
    }

    private function _sortRankByAmount($couponRank){
        foreach($couponRank as  $coupon){
            $amount_arr[] = $coupon['amount'];
        }
        array_multisort($amount_arr, SORT_DESC, $couponRank);
        return $couponRank;
    }

    private function _getCouponIdList(){
        $data = array();
        $condition['coupon_status'] = 1;
        $condition['coupon_is_sell'] = 1;
        $couponList = M('Coupon')->where($condition)->select();
        foreach($couponList as $couponInfo){
            $data[] = $couponInfo['coupon_id'];
        }
        return $data;
    }

}