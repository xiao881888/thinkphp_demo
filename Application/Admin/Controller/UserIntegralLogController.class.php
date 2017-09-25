<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2015-4-22
 * @author tww <merry2014@vip.qq.com>
 */
class UserIntegralLogController extends GlobalController{
	public function index($uid){
		$userInfo 			= D('CpUser')->getInfo($uid);
		$this->assign('uid', $uid);
		$this->assign('userInfo', $userInfo);
		
		$sDate = I('s_date');
		$eDate = I('e_date');
		$where = array();
		if($sDate && $eDate){
			$where['uil_createtime'] = array('BETWEEN', array(date('Y-m-d', strtotime($sDate)), date('Y-m-d', strtotime($eDate))));
		}else{
			if($sDate){
				$where['uil_createtime'] = array('EGT', date('Y-m-d', strtotime($sDate)));
			}
			if($eDate){
				$where['uil_createtime'] = array('ELT', date('Y-m-d', strtotime($eDate)));
			}
		}

		$this->setLimit($where);

		$userIntegralLogModel = D('UserIntegralLog');
		$userIntegralLogModel->setTrueTable($uid);
		parent::index($userIntegralLogModel);
	}
}