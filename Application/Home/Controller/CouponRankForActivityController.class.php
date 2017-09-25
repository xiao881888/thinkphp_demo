<?php
namespace Home\Controller;
use Home\Controller\GlobalController;

class CouponRankForActivityController extends GlobalController{

    const ACTIVITY_START_TIME = '2016-10-01 00:00:00';
    const ACTIVITY_END_TIME = '2016-10-08 00:00:00';
    const MAX_RANK = 20;

    const SUCCESS_STATUS = 0;
    const FAIL_STATUS = 1;

    private $_readDB = 'mysql://tigercai_server:e4huY8J7e4@rr-bp1y62h5vwc8o62yt.mysql.rds.aliyuncs.com:3306/tigercai';

    const IP_DEBUG = false;

    public function index(){
        $this->_activityDetail();
        $this->_assignCouponRank();
        $this->_assignBuildUrl();
        $this->_assignBuyCouponURL();
        $this->display();
    }
	
	public function test(){
		if($_REQUEST['u']){
			$userRankInfo = $this->_getActivityCouponRankByUid(intval($_REQUEST['u']));
			print_r($userRankInfo);
		}
	}

    private function _assignBuyCouponURL(){
        $jump_data = urlencode(base64_encode(json_encode(array('id'=>5))));
        $op_code = 10706;
        $this->assign('jump_data',$jump_data);
        $this->assign('op_code',$op_code);
    }

    private function _assignCouponRank(){
        $couponList = $this->_getCouponRank();
        $this->assign('couponList',$couponList);
    }

    private function _assignBuildUrl(){
        //$get_userid_url = 'http://'.$_SERVER['SERVER_ADDR'].':'.$_SERVER['SERVER_PORT'].'/index.php?s='.U('autoLoginForClient');
        $get_userid_url = 'http://'.$_SERVER['HTTP_HOST'].U('autoLoginForClient');
        $this->assign('get_userid_url',$get_userid_url);
    }

    public function autoLoginForClient(){
        $data = array();
        $encrypt_str = I('encrypt_str', '');
        if(empty($encrypt_str)){
            $data['error'] = self::FAIL_STATUS;
            $this->ajaxReturn($data);
        }

        $sessionArr = decryptRsa($encrypt_str);
        $sessionArr = explode('_',$sessionArr);
        $userSession = $sessionArr[1];
        $userInfo 	= $this->getAvailableUser($userSession);
        ApiLog('$userInfo:' . print_r($userInfo, true), 'clientLogin5');
        if(!empty($userInfo)){
            $user_id = $userInfo['uid'];
            $userRankInfo = $this->_getActivityCouponRankByUid($user_id);
            $data['error'] = self::SUCCESS_STATUS;
            $data['userRankInfo'] = $userRankInfo;
        }else{
            $data['error'] = self::FAIL_STATUS;
        }
        $this->ajaxReturn($data);
    }

    private function _getActivityCouponRankByUid($user_id){
        $data = array();
        $amount = $this->_getCouponAmountByUid($user_id);
        $couponList = $this->_getCouponRank();
        $user_tel = getUserTelByUid($user_id);

        foreach($couponList as $coupon){
            if(isset($coupon['user_tel']) && $user_tel == $coupon['user_tel']){
                $data['rank'] = $coupon['rank'];
                $data['amount'] = $amount;
                return $data;
            }
        }

        if(!empty($amount)){
            $robot_rank = $this->_getRobotRankByAmount($amount);
            $activity_rank = $this->_getActivityRankByAmount($amount);
            $data['rank'] = $activity_rank+$robot_rank+1;
            $data['amount'] = $amount;
        }else{
            $data['rank'] = 0;
            $data['amount'] = 0;
        }
        return $data;
    }

    private function _getCouponAmountByUid($uid){
        $where = array();
        $where['coupon_id'] = array('IN',$this->_getCouponIdList());
        $where['user_coupon_create_time'] = array(array('EGT',self::ACTIVITY_START_TIME),array('ELT',self::ACTIVITY_END_TIME));
        $where['uid'] = $uid;
        $amount =  M('UserCoupon')->where($where)->getField('SUM(user_coupon_amount) as amount');
        if(empty($amount)){
            $amount = 0;
        }
        return $amount;

    }


    private function _getRobotRankByAmount($coupon_amount){
        $where['rcr_user_coupon_amount'] = array('GT',$coupon_amount);
        $where['rcr_status'] = 1;
        return M('RobotCouponRecord')->where($where)->count();
    }

    private function _getActivityRankByAmount($coupon_amount){
		$where['coupon_id'] = array('IN',$this->_getCouponIdList());
        $where['user_coupon_create_time'] = array(array('EGT',self::ACTIVITY_START_TIME),array('ELT',self::ACTIVITY_END_TIME));
		$activityCouponRank =  M('UserCoupon')->db(1,$this->_readDB,true)->field('SUM(user_coupon_amount) as amount,uid')->where($where)->group('uid')->select();
        M('UserCoupon')->db(0);
		$user_count = 0;
		foreach($activityCouponRank as $rank_info){
			if($rank_info['amount']>$coupon_amount){
				$user_count++;
			}
		}
		return $user_count;
		
        $where['user_coupon_amount'] = array('GT',$coupon_amount);
        $where['user_coupon_create_time'] = array(array('EGT',self::ACTIVITY_START_TIME),array('ELT',self::ACTIVITY_END_TIME));
        $where['coupon_id'] = array('IN',$this->_getCouponIdList());
        return M('UserCoupon')->where($where)->count();
    }


    private function _getCouponRank(){
        $robotRank = $this->_getRobotCouponRecord();
        $activityCouponRank = $this->_getActivityCouponRank();
        foreach($activityCouponRank as $key => $activityCoupon){
            $activityCouponRank[$key]['user_tel'] = getUserTelByUid($activityCoupon['uid']);
        }
        if(!empty($robotRank)){
            $couponRank = array_merge($robotRank,$activityCouponRank);
        }else{
            $couponRank = $activityCouponRank;
        }

        $newCouponRank = array();

        $couponRank = $this->_sortRankByAmount($couponRank);


        foreach($couponRank as $k => $coupon){
            if($k >= self::MAX_RANK){
                break;
            }
            $newCouponRank[$k]['amount'] = $coupon['amount'];
            $newCouponRank[$k]['user_tel'] = $coupon['user_tel'];
            $newCouponRank[$k]['rank'] = $k+1;
        }
        return $newCouponRank;
    }

    private function _sortRankByAmount($couponRank){
        foreach($couponRank as  $coupon){
            $amount_arr[] = $coupon['amount'];
        }
        array_multisort($amount_arr, SORT_DESC, $couponRank);
        return $couponRank;
    }

    private function _getRobotCouponRecord(){
        $where['rcr_status'] = 1;
        return M('RobotCouponRecord')->field('rcr_user_tel as user_tel,rcr_user_coupon_amount as amount')->where($where)->select();
    }

    private function _getActivityCouponRank($offset = 0,$limit = 20){
        $where['coupon_id'] = array('IN',$this->_getCouponIdList());
        $where['user_coupon_create_time'] = array(array('EGT',self::ACTIVITY_START_TIME),array('ELT',self::ACTIVITY_END_TIME));
        //$activityCouponRank =  M('UserCoupon')->field('SUM(user_coupon_amount) as amount,uid')->where($where)->group('uid')->limit($offset,$limit)->select();
        $activityCouponRank =  M('UserCoupon')->db(1,$this->_readDB,true)->field('SUM(user_coupon_amount) as amount,uid')->where($where)->group('uid')->select();
        M('UserCoupon')->db(0);
        return $activityCouponRank;
    }

    private function _getCouponIdList(){
        $data = array();
        $couponList =  D('Coupon')->getCouponListForSale();
        foreach($couponList as $couponInfo){
            $data[] = $couponInfo['id'];
        }
        return $data;
    }

    private function _activityDetail(){
        $current_time = getCurrentTime();
        if($current_time <= self::ACTIVITY_START_TIME){
            $this->display('announce');die;
        }
        elseif($current_time >= self::ACTIVITY_END_TIME){
            $this->display('announce');die;
        }


        if(self::IP_DEBUG){
            if(!$this->_checkIP()){
                $this->display('announce');die;
            }
        }

    }

    private function _checkIP(){
        $ip = '110.83.28.97';
        $client_ip = get_client_ip(0, true);
        if($client_ip == $ip){
            return true;
        }
        return false;
    }




}