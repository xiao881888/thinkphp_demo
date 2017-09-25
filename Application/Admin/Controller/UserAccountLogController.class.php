<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2015-4-22
 * @author tww <merry2014@vip.qq.com>
 */
class UserAccountLogController extends GlobalController{
	public function index($uid){
		$userInfo 			= D('CpUser')->getInfo($uid);
		$userAccountInfo 	= D('UserAccount')->getUserAccountInfo($uid);
		$userIntegralInfo   = D('UserIntegral')->where(array('uid'=>$uid))->find();

		$this->assign('uid', $uid);
		$this->assign('userInfo', $userInfo);
		$this->assign('userAccountInfo', $userAccountInfo);
		$this->assign('userIntegralInfo', $userIntegralInfo);

		$sDate = I('s_date');
		$eDate = I('e_date');
		$where = array();
		if($sDate && $eDate){
			$where['ual_create_time'] = array('BETWEEN', array(date('Y-m-d', strtotime($sDate)), date('Y-m-d', strtotime($eDate))));
		}else{
			if($sDate){
				$where['ual_create_time'] = array('EGT', date('Y-m-d', strtotime($sDate)));
			}
			if($eDate){
				$where['ual_create_time'] = array('ELT', date('Y-m-d', strtotime($eDate)));
			}
		}
		$this->setLimit($where);
		parent::index();
	}
}