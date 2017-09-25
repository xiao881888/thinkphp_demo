<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-24
 * @author tww <merry2014@vip.qq.com>
 */
class EventLogController extends  GlobalController{
	public function _initialize(){
		parent::_initialize();
		$this->assign('event_map', D('Event')->getEventMap());
	}
}