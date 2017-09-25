<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-2
 * @author tww <merry2014@vip.qq.com>
 */
class IssueController extends GlobalController{
	
	public function _before_index(){
		$this->_assignLotteryMap();
	}
	
	public function _before_add(){
		$this->_assignLotteryMap();
	}
	
	public function _before_edit(){
		$this->_assignLotteryMap();
	}
	
	public function index(){
        $where = array();
        $is_finish = I('is_finish');
        if($is_finish){
            $where['issue_task_status'] = FINISH_TASK_STATUS;
        }elseif($is_finish !== ""){
            $where['issue_task_status'] = array('NEQ', FINISH_TASK_STATUS);
        }
        
		$this->setLimit($where);
		parent::index();
	}
	
	private function _assignLotteryMap(){
		$map = D('Lottery')->getAllLottery();
		$this->assign('lottery_map', $map);
	}
}