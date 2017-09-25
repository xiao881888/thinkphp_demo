<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-25
 * @author tww <merry2014@vip.qq.com>
 */
class TaskLogController extends GlobalController{
	public function _before_index(){
		$s_date = I('s_date');
		$e_date = I('e_date');
		$where = array();
		if($s_date && $e_date){
			$where['et_create_time'] = array('BETWEEN', array($s_date, $e_date));
		}else{
			if($s_date){
				$where['et_create_time'] = array('EGT', $s_date);
			}
			if($e_date){
				$where['et_create_time'] = array('ELT', $e_date);
			}
		}
		$this->setLimit($where);
		$this->assign('event_map', D('Event')->getEventMap());
		$this->assign('lottery_map', D('Lottery')->getAllLottery());
	}
}