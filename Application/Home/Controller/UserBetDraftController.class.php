<?php

namespace Home\Controller;

use Home\Controller\GlobalController;

const DRAFT_STATUS_EXPIRE_SOON = 2;
const DRAFT_STATUS_NORMAL = 1;
const DRAFT_STATUS_EXPIRE = 0;

class UserBetDraftController extends GlobalController{
	
	public function getDraftList($api){
		$user_info = $this->getAvailableUser($api->session);
		$uid = $user_info['uid'];
		
		$status = C('USER_BET_DRAFT_STATUS.AVAILABLE');
		$draft_list = D('UserBetDraft')->getDraftList($uid,$api->lottery_id,$status,$api->offset,$api->limit);

		$schedule_list = $this->getScheduleList($draft_list);
		
		$result = [];
		
		foreach($draft_list as $key=>$draft_row){
			$data = [];
			$draft_content = json_decode($draft_row['ubd_content'],true);
			foreach ($draft_content as $k=>$draft_content_row){
				$draft_content[$k]['bet_end_time'] = strtotime($schedule_list[$draft_content_row['schedule_id']]['schedule_end_time']);
				$draft_content[$k]['current_odds'] = getFormatOdds($draft_row['lottery_id'], $schedule_list[$draft_content_row['schedule_id']]['schedule_odds']);
				//json_decode($schedule_list[$draft_content_row['schedule_id']]['schedule_odds'],true);
				$draft_content[$k]['home'] = $schedule_list[$draft_content_row['schedule_id']]['schedule_home_team'];
				$draft_content[$k]['guest'] = $schedule_list[$draft_content_row['schedule_id']]['schedule_guest_team'];
				$week_name = getWeekName($schedule_list[$draft_content_row['schedule_id']]['schedule_week']);
				$draft_content[$k]['round_no'] = $week_name.' '.$schedule_list[$draft_content_row['schedule_id']]['schedule_round_no'];
			}
			$data['id'] = $draft_row['ubd_id'];
			$data['modify_date'] = strtotime($draft_row['ubd_modifytime']);
			$data['lottery_id'] = $draft_row['lottery_id'];
			$data['multiple'] = $draft_row['ubd_multiple'];
			$data['play_type'] = $draft_row['play_type'];
			$data['series'] = $draft_row['bet_type'];
			$data['stake_count'] = $draft_row['ubd_stake_count'];
			$data['total_amount'] = $draft_row['ubd_total_amount'];
			$data['bonus_range'] = $draft_row['ubd_bonus_range'];
			
			if((strtotime($draft_row['ubd_first_time'])-time()) <= 1800){
				$data['status'] = DRAFT_STATUS_EXPIRE_SOON;
			}else{
				$data['status'] = DRAFT_STATUS_NORMAL;
			}
			
			$data['schedule_orders'] = $draft_content;
				
			$result[] = $data;
		}
		return [
				'result'=>['list'=>$result],
				'code'=>C('ERROR_CODE.SUCCESS')
		];
	}
	public function addBetDraft($api){
		ApiLog('add api :'.print_r($api,true),'user_bet_draft');
		$this->checkLotteryExists($api->lottery_id);
		
		// 检查客户端唯一码，防止重复插入
		$draft_id = D('UserBetDraft')->getDraftIdByIdentity($api->draft_identity);
		if($draft_id){
			return [
					'result'=>['id'=>$draft_id],
					'code'=>C('ERROR_CODE.SUCCESS')
			];
		}
		
		$data = $this->parseDraftData($api);
		$data['ubd_createtime'] = getCurrentTime();
		
		ApiLog('add data :'.print_r($data,true),'user_bet_draft');
		
		$id = D('UserBetDraft')->addDraft($data);
		if(!$id){
			\AppException::throwException(C('ERROR_CODE.DATABASE_ERROR'));
		}
		return [
				'result'=>['id'=>$id],
				'code'=>C('ERROR_CODE.SUCCESS')
		];
	}
	public function saveBetDraft($api){
		$this->checkDraftExists($api->id);
		$this->checkLotteryExists($api->lottery_id);
		
		$data = $this->parseDraftData($api);
		
		ApiLog('save data :'.print_r($data,true),'user_order_scheme');
		
		$result = D('UserBetDraft')->saveDraft($data,$api->id);
		if(!$result){
			\AppException::throwException(C('ERROR_CODE.DATABASE_ERROR'));
		}
		return [
				'result'=>'',
				'code'=>C('ERROR_CODE.SUCCESS')
		];
	}
	public function delBetDraft($api){
		$user_info = $this->getAvailableUser($api->session);
		$uid = $user_info['uid'];
		
		$draft_info = D('UserBetDraft')->getDraftInfo($api->id);
		\AppException::ifNoExistThrowException($draft_info, C('ERROR_CODE.ID_NO_EXIST'));
		
		$user_owen = ($draft_info['uid'] == $uid);
		\AppException::ifNoExistThrowException($user_owen, C('ERROR_CODE.ID_NO_EXIST'));
		
		$deleted = D('UserBetDraft')->deleteDraft($api->id);
		\AppException::ifExistThrowException($deleted===false, C('ERROR_CODE.DATABASE_ERROR'));
		
		return [
				'result'=>'',
				'code'=>C('ERROR_CODE.SUCCESS')
		];
	}
	public function getDraftCount($api){
		$user_info = $this->getAvailableUser($api->session);
		$uid = $user_info['uid'];
		
		$count = D('UserBetDraft')->getDraftCount($uid,$api->lottery_id,C('USER_BET_DRAFT_STATUS.AVAILABLE'));

		return [
				'result'=>['count'=>$count],
				'code'=>C('ERROR_CODE.SUCCESS')
		];
	}
	private function checkDraftExists($id){
		$draft_info = D('UserBetDraft')->getDraftInfo($id);
		if(empty($draft_info)){
			\AppException::throwException(C('ERROR_CODE.ID_NO_EXIST'));
		}
	}
	private function checkLotteryExists($lottery_id){
		$lottery_info = D('Lottery')->getLotteryInfo($lottery_id);
		if(empty($lottery_info)){
			\AppException::throwException(C('ERROR_CODE.LOTTERY_NO_EXIST'));
		}
	}
	private function parseDraftData($api){
		$user_info = $this->getAvailableUser($api->session);
		$uid = $user_info['uid'];
		foreach($api->schedule_orders as $bet_item){
			$schedule_id_arr[] = $bet_item['schedule_id'];
		}
		$schedule_ids = implode(',',$schedule_id_arr);
		
		$ubd_first_time = $this->getUbdFirstTime($schedule_id_arr);
		
		return [
				'uid'=>$uid,
				'lottery_id'=>$api->lottery_id,
				'ubd_multiple'=>$api->multiple,
				'play_type'=>$api->play_type,
				'bet_type'=>$api->series,
				'ubd_stake_count'=>$api->stake_count,
				'ubd_total_amount'=>$api->total_amount,
				'ubd_bonus_range'=>$api->bonus_range,
				'ubd_identity'=>$api->draft_identity,
				'ubd_schedule_ids'=>$schedule_ids,
				'ubd_content'=>json_encode($api->schedule_orders),
				'ubd_first_time'=>$ubd_first_time
		];
	}
	private function getUbdFirstTime($schedule_id_arr){
		$map['schedule_id'] = [
				'in',
				$schedule_id_arr
		];
		$schedule_info = M('JcSchedule')->field('schedule_end_time')->where($map)->order('schedule_end_time')->find();
		$ubd_first_time = $schedule_info['schedule_end_time'];
		if(time()>=strtotime($ubd_first_time)){
			\AppException::throwException(C('ERROR_CODE.OUT_OF_SCHEDULE_TIME'));
		}
		return $ubd_first_time;
	}
	private function getScheduleList($draft_list){
		foreach($draft_list as $key=>$draft_row){
			$schedule_id_arr = explode(',',$draft_row['ubd_schedule_ids']);
			foreach($schedule_id_arr as $schedule_id){
				// 生成以schedule_id作为key的数组
				$schedule_draft_id_arr[$schedule_id][] = $draft_row['ubd_id'];
			}
		}
		//取得相关的对阵资料
		$schedule_map['schedule_id'] = [
				'in',
				array_keys($schedule_draft_id_arr)
		];
		$schedule_list = M('JcSchedule')->field('schedule_id,schedule_day,schedule_week,schedule_end_time,schedule_round_no,schedule_home_team,schedule_guest_team,schedule_odds')->where($schedule_map)->select();
		$schedule_list = reindexArr($schedule_list, 'schedule_id');
		
		return $schedule_list;
	}
}