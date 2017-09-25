<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-4
 * @author tww <merry2014@vip.qq.com>
 */
class OrderController extends GlobalController{

	public function index(){
		$where = array();
		if(I('user_telephone')){
			$where['uid'] = D('User')->getUidByTelephone(I('user_telephone'));
		}

		if (I('order_createtime_start')) {
			$where['order_create_time'] = array('egt', I('order_createtime_start'));
		}
		if (I('order_createtime_end')) {
			if (I('order_createtime_start')) {
				$where['order_create_time'] = array('between', array(I('order_createtime_start'), I('order_createtime_end')));
			} else {
				$where['order_create_time'] = array('lt', I('order_createtime_end'));
			}
		}

		if (I('order_award_amount_status') != 0) {
			$where['order_plus_award_amount'] = I('order_award_amount_status') == 1 ? array('gt', 0) : 0;
		}

		if (I('lottery_id_ex') != 0) {
			if (I('lottery_id_ex') == C('JC.JCZQ')) {
			    $lottery_id = C('JCZQ');
			    $is_jc = true;
				$where['lottery_id']  = array('IN', $lottery_id);
			} elseif (I('lottery_id_ex') == C('JC.JCLQ')) {
			    $lottery_id = C('JCLQ');
			    $is_jc = true;
				$where['lottery_id']  = array('IN', $lottery_id);
			} else {
			    $lottery_id = I('lottery_id_ex');
				$where['lottery_id'] = $lottery_id;
			}
		}
		
		if (I('order_total_amount_start') && I('order_total_amount_end')) {
		    $where['order_total_amount'] = array(
                		                      array('egt', I('order_total_amount_start', 0)),
                		                      array('lt', I('order_total_amount_end', 0)),
                		                   );
		}elseif (I('order_total_amount_start')) {
		    $where['order_total_amount'] = array('egt', I('order_total_amount_start', 0));
		}elseif (I('order_total_amount_end')) {
		    $where['order_total_amount'] = array('lt', I('order_total_amount_end', 0));		    
		}

		if (I('order_winnings_bonus_start') && I('order_winnings_bonus_end')) {
			$where['order_winnings_bonus'] = array(
												array('egt', I('order_winnings_bonus_start')),
												array('lt', I('order_winnings_bonus_end'))
											);
		}elseif (I('order_winnings_bonus_start')) {
			$where['order_winnings_bonus'] = array('egt', I('order_winnings_bonus_start'));
		}elseif (I('order_winnings_bonus_end')) {
			$where['order_winnings_bonus'] = array('lt', I('order_winnings_bonus_end'));
		}

		
		if (($is_jc || isJc(I('lottery_id_ex'))) && I('schedule_day') && I('schedule_round_no')) {
		    $schedule_day = I('schedule_day', '', 'trim');
		    $round_nos = explode(",", I('schedule_round_no', '', 'trim'));
		    $schedule_ids = D('JcSchedule')->getScheduleIdsByDayRoundNo($lottery_id, $schedule_day, $round_nos);
		    $where['first_issue_id'] = array('IN', $schedule_ids);
		}
		$this->setLimit($where);
		$list = parent::index('', true);
		$this->assign('list', $list);

		$user_ids = extractArrField($list, 'uid');
		$users = D('User')->where(array('uid'=>array('IN', $user_ids)))->select();
		$users = reindexArr($users, 'uid');
		$this->assign('users', $users);
		$this->assign('lottery_map', D('Lottery')->getAllLottery());
		$this->display();
	}
	
	protected function _getSearchCondition($model = ''){
		// 生成查询条件
		$model = $this->_checkModel($model);
		$map = array();
		$likeFields = method_exists($model, 'getLikeFields') ? $model->getLikeFields() : '';
	
		foreach ($model->getDbFields() as $val) {
			$currentRequest = trim($_REQUEST[$val]);
			if (isset($_REQUEST[$val]) && $currentRequest != '') {
				if (! empty($likeFields) && is_array($likeFields) && in_array($val, $likeFields)) {
					$map[$val] = array('like', '%' . $currentRequest . '%');
				} else {
					if($val=='order_status' && $currentRequest==99){
						$map[$val] = array('IN',array(5,7,8));
					}else{
						$map[$val] = $currentRequest;
					}
				}
			}
		}
		$limit = $this->getLimit();
		if (! empty($limit)) {
			$map['_complex'] = $limit;
		}
	
		return $map;
	}

	public function detail($id){
		$order_info = D('Order')->find($id);		
		$lottery_model 	= D(C('LOTTERY_MODEL.'.$order_info['lottery_id']));
		$tickets = $lottery_model->getTicketInfos($id);

		$this->assign('tickets', $tickets);
		if (is_subclass_of($lottery_model, 'Admin\Model\JingCaiTicketModel')) {
		 	$this->display('detail_jc');
		} else {
		    $issue_info = D('Issue')->getIssueInfo($order_info['issue_id']);
		    $this->assign('issueInfo', $issue_info);
			$this->display('detail');
		}
	}

	//COBET_LOTTERY_MODEL
    public function cobetDetail($id){
        $order_info = D('CobetOrder')->find($id);
        $lottery_model 	= D(C('COBET_LOTTERY_MODEL.'.$order_info['lottery_id']));
        $tickets = $lottery_model->getTicketInfos($id);

        $this->assign('tickets', $tickets);
        if (is_subclass_of($lottery_model, 'Admin\Model\JingCaiTicketModel')) {
            $this->display('detail_jc');
        } else {
            $issue_info = D('Issue')->getIssueInfo($order_info['issue_id']);
            $this->assign('issueInfo', $issue_info);
            $this->display('detail');
        }
    }
}