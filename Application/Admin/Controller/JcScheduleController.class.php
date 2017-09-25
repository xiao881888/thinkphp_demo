<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date
 * @author
 */
class JcScheduleController extends GlobalController{
	
	public function _before_index(){
		$this->_assignLotteryMap();
	}
	
	public function index(){
        $where = array();
        $is_finish = I('is_finish');
        $lottery_id = I('lottery_id_ex');
        if ($lottery_id){
            if ($lottery_id == C('JC.JCZQ')) {
                $where['lottery_id']  = array('IN', C('JCZQ'));
            } elseif ($lottery_id == C('JC.JCLQ')) {
                $where['lottery_id']  = array('IN', C('JCLQ'));
            } else {
                $where['lottery_id'] = $lottery_id;
            }
        }
        if($is_finish){
            $where['schedule_task_status'] = FINISH_TASK_STATUS;
        }elseif($is_finish !== ""){
            $where['schedule_task_status'] = array('NEQ', FINISH_TASK_STATUS);
        }
		$this->setLimit($where);
		parent::index();
	}

    /**
     * 获取推荐竞足彩票列表
     */
    public function recommentIndex(){
        $this->_assignLotteryMap();
        $model = 'JcSchedule';
        $model 	= D($model);
        if (! empty($model)) {
            $order = 'schedule_id DESC';
            $list 	= $this->lists($model, $this->_getSearchCondition($model), $order, '');
            foreach($list as $k =>$v){
                $list[$k]['caiqi'] = $v['schedule_day'].'-周'.$v['schedule_week'].'-'.$v['schedule_round_no'];
            }
            $this->assign('list', $list);
        }
        $this->display('recommentIndex');
    }
	
	private function _assignLotteryMap(){
		$map = D('Lottery')->getLotteryMap();
		$this->assign('lottery_map', $map);
	}
}