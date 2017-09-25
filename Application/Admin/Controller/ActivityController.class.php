<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-3
 * @author tww <merry2014@vip.qq.com>
 */
class ActivityController extends GlobalController{
	public function _before_index(){
		$this->_assignLotteryMap();
	}
	
	public function _before_add(){
		$this->_assignLotteryMap();
	}
	
	public function _before_edit(){
		$this->_assignLotteryMap();
	}
	
	private function _assignLotteryMap(){
		$map = D('Lottery')->getLotteryMap();
		$this->assign('lottery_map', $map);
	}
}