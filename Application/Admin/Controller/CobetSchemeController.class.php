<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-4
 * @author tww <merry2014@vip.qq.com>
 */
class CobetSchemeController extends GlobalController{

	public function index(){
		$where = array();
		if(I('user_telephone')){
			//$where['uid'] = D('User')->getUidByTelephone(I('user_telephone'));
            $uid = D('User')->getUidByTelephone(I('user_telephone'));
			$where['scheme_id'] = array('IN',D('CobetRecord')->getSchemeIdsByUid($uid));
		}

		if (I('scheme_createtime_start')) {
			$where['scheme_createtime'] = array('egt', I('scheme_createtime_start'));
		}
		if (I('scheme_createtime_end')) {
			if (I('scheme_createtime_start')) {
				$where['scheme_createtime'] = array('between', array(I('scheme_createtime_start'), I('scheme_createtime_end')));
			} else {
				$where['scheme_createtime'] = array('lt', I('scheme_createtime_end'));
			}
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
		
		if (I('scheme_total_amount_start') && I('scheme_total_amount_end')) {
		    $where['scheme_total_amount'] = array(
                		                      array('egt', I('scheme_total_amount_start', 0)),
                		                      array('lt', I('scheme_total_amount_end', 0)),
                		                   );
		}elseif (I('scheme_total_amount_start')) {
		    $where['scheme_total_amount'] = array('egt', I('scheme_total_amount_start', 0));
		}elseif (I('scheme_total_amount_end')) {
		    $where['scheme_total_amount'] = array('lt', I('scheme_total_amount_end', 0));
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
	
	/*protected function _getSearchCondition($model = ''){
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
	}*/

	public function getBounghtUserList($id){
		$record_list = D('CobetRecord')->getRecordListBySchemeId($id);
		foreach($record_list as $key => $record_info){
		    if($record_info['type'] == COBET_TYPE_OF_GUARANTEE_FROZEN){
		        unset($record_list[$key]);
		        continue;
            }
		    $user_info = D('User')->getUserInfo($record_info['uid']);
            $record_list[$key]['user_telephone'] = $user_info['user_telephone'];
            $record_list[$key]['user_real_name'] = $user_info['user_real_name'];
            $record_list[$key]['user_nick_name'] = $user_info['user_nick_name'];
        }
		$this->assign('record_list', $record_list);
        $this->display('detail');
	}
}