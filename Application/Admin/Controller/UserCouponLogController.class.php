<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2015-4-22
 * @author tww <merry2014@vip.qq.com>
 */
class UserCouponLogController extends GlobalController{
	public function index($uid){
		$userInfo 			= D('CpUser')->getInfo($uid);
		$this->assign('uid', $uid);
		$this->assign('userInfo', $userInfo);
		
		$sDate = I('s_date');
		$eDate = I('e_date');
		$where = array();
		if($sDate && $eDate){
			$where['user_coupon_create_time'] = array('BETWEEN', array(date('Y-m-d', strtotime($sDate)), date('Y-m-d', strtotime($eDate))));
		}else{
			if($sDate){
				$where['user_coupon_create_time'] = array('EGT', date('Y-m-d', strtotime($sDate)));
			}
			if($eDate){
				$where['user_coupon_create_time'] = array('ELT', date('Y-m-d', strtotime($eDate)));
			}
		}
		$user_coupon_id = I('user_coupon_id',0);
		if($user_coupon_id){
            $where['user_coupon_id'] = $user_coupon_id;
        }

		$this->setLimit($where);
		parent::index();
	}
}