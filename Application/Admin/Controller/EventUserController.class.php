<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-25
 * @author tww <merry2014@vip.qq.com>
 */
class EventUserController extends GlobalController{
	
	public function _initialize(){
		parent::_initialize();
		$this->assign('event_map', D('Event')->getEventMap());
		$this->assign('user_map', D('Member')->getMembers());
	}
}