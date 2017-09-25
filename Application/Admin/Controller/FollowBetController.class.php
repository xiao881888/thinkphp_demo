<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-22
 * @author tww <merry2014@vip.qq.com>
 */
class FollowBetController extends GlobalController{
	public function _initialize(){
		parent::_initialize();
		$this->assign('lottery_map', D('Lottery')->getLotteryMap());
	}
}