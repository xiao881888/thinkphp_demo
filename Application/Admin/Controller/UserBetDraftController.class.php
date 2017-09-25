<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2016-11-23
 * @author laoge <54369798@qq.com>
 */
class UserBetDraftController extends GlobalController{

	public function index(){
		$user_keyword = I('user_keyword');
		if ($user_keyword) {
			if (is_numeric($user_keyword)) {
				$user_where['user_telephone'] = $user_keyword;
			} else {
				$user_where['user_real_name'] = $user_keyword;
			}
			$uid = M('User')->where($user_where)->getField('uid');
		}
		
		if($uid){
			$draft_where['uid'] = $uid;
		}
		//todo 可看所有的，筛选状态
// 		$draft_where['ubd_status'] = C('USER_BET_DRAFT_STATUS.AVAILABLE');
		
		$list = $this->lists(D('UserBetDraft'), $draft_where);
		$list = reindexArr($list, 'ubd_id');
		
		$this->parseDraftList($list);
		
		$this->display();
	}
	
	private function parseDraftList($list) {
		foreach ($list as $key=>$draft_row){
			$uid_arr[] = $draft_row['uid'];
			
			$schedule_id_arr = explode(',',$draft_row['ubd_schedule_ids']);
			foreach($schedule_id_arr as $schedule_id){
				// 生成以schedule_id作为key的数组
				$schedule_draft_id_arr[$schedule_id][] = $draft_row['ubd_id'];
			}
			
			$list[$key]['ubd_content'] = json_decode($draft_row['ubd_content'],true);
			foreach ($list[$key]['ubd_content'] as $k=>$draft_content_row){
				$list[$key]['ubd_content'][$k]['bet_options'] = $this->parseBetOption($draft_content_row['bet_number']);
			}
		}
		$this->assign('list', $list);
		
		//取得相关的对阵资料
		$schedule_map['schedule_id'] = [
				'in',
				array_keys($schedule_draft_id_arr)
		];
		$schedule_list = M('JcSchedule')->field('schedule_id,schedule_day,schedule_issue_no,schedule_round_no,schedule_end_time,schedule_home_team,schedule_guest_team,schedule_odds')->where($schedule_map)->select();
		$schedule_list = reindexArr($schedule_list, 'schedule_id');
		$this->assign('schedule_list', $schedule_list);
		
		//取得用户资料
		$user_list = D('User')->getUserMap($uid_arr);
		$this->assign('user_list', $user_list);
		
		$this->assign('lottery_map', D('Lottery')->getAllLottery());
	}
	
	private function parseBetOption($bet_number) {
		$bet_options = [];
		$bet_arr = explode('|',$bet_number);
		foreach ($bet_arr as $key=>$bet_item){
			$bet_item_arr = explode(':', $bet_item);
			$lottery_id = $bet_item_arr[0];
			$bet_option_arr = explode(',', $bet_item_arr[1]);
			foreach ($bet_option_arr as $k=>$bet_option){
				$bet_option_arr[$k] = 'v'.$bet_option;
			}
			$bet_options[] = showJCPlayOption($lottery_id, $bet_option_arr);
		}
		return $bet_options;
	}
	
}