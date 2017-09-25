<?php

namespace Crontab\Controller;

use Think\Controller;

class UserBetDraftController extends Controller{
	protected $table_draft;
	protected $table_jc_schedule;
	protected $M_read;
	protected $M_write;
	public function __construct(){
		parent::__construct();
		$this->table_draft = C('DB_PREFIX').'user_bet_draft';
		$this->table_jc_schedule = C('DB_PREFIX').'jc_schedule';
		$this->_userReadDb();
	}
	private function _userReadDb(){
		if(get_cfg_var('PROJECT_RUN_MODE')=='PRODUCTION'){
			M()->db(1,'mysql://tigercai_read:2D^h6u#DYR*HfJzVjSn@rr-bp1y62h5vwc8o62yt.mysql.rds.aliyuncs.com:3306/tigercai#utf8');
			M()->db(2,'mysql://tigercai_server:e4huY8J7e4@fzhcwlkjyxgs.mysql.rds.aliyuncs.com:3306/tigercai');
		}elseif(get_cfg_var('PROJECT_RUN_MODE')=='PRODUCTION'){
			M()->db(1,'mysql://tigercai_test:MB8&7Y7cL$zWfC3@FM6@123.56.221.173:3306/tigercai_test');
			M()->db(2,'mysql://tigercai_test:MB8&7Y7cL$zWfC3@FM6@123.56.221.173:3306/tigercai_test');
		}else{
            M()->db(1,'mysql://root:123456@192.168.1.172:3306/lottery_test');
            M()->db(2,'mysql://root:123456@192.168.1.172:3306/lottery_test');
        }
	}
	public function index(){
		$draft_list = $this->getDraftList();
		
		foreach($draft_list as $key=>$draft_row){
			if($draft_row['ubd_status'] == -1){
				continue;
			}
			$schedule_id_arr = explode(',',$draft_row['ubd_schedule_ids']);
			foreach($schedule_id_arr as $schedule_id){
				// 生成以schedule_id作为key的数组
				$schedule_draft_id_arr[$schedule_id][] = $draft_row['ubd_id'];
			}
		}
		
		// 取得相关的对阵资料
		$schedule_list = $this->getScheduleList($schedule_draft_id_arr);
		
		$draft_available_arr = [];
		$draft_unavailable_arr = [];
		if($schedule_list){
			foreach($schedule_list as $schedule_row){
				if(time()<strtotime($schedule_row['schedule_end_time'])&&$schedule_row['schedule_status']==C('SCHEDULE_STATUS.ON_SALE')&&$schedule_row['schedule_stop_sell_status']==C('SCHEDULE_STOP_SELL_STATUS.ON_SALE')){
					$draft_available_arr = array_merge($draft_available_arr,$schedule_draft_id_arr[$schedule_row['schedule_id']]);
				}else{
					$draft_unavailable_arr = array_merge($draft_unavailable_arr,$schedule_draft_id_arr[$schedule_row['schedule_id']]);
				}
			}
			$draft_available_arr = array_unique($draft_available_arr);
			$draft_unavailable_arr = array_unique($draft_unavailable_arr);
			
			$this->updateDraftStatus($draft_available_arr,C('USER_BET_DRAFT_STATUS.AVAILABLE'));
			$this->updateDraftStatus($draft_unavailable_arr,C('USER_BET_DRAFT_STATUS.NO_AVAILABLE'));
		}
		exit();
	}
	private function getDraftList(){
		$map['ubd_first_time'] = [
				'gt',
				getCurrentTime()
		];
		$map['ubd_status'] = C('USER_BET_DRAFT_STATUS.AVAILABLE');
		$map['_logic'] = 'OR';
		$draft_list = M()->db(1)->table($this->table_draft)->where($map)->select();
		return $draft_list;
	}
	private function getScheduleList($schedule_draft_id_arr){
		$schedule_map['schedule_id'] = [
				'in',
				array_keys($schedule_draft_id_arr)
		];
		$schedule_list = M()->db(1)->table($this->table_jc_schedule)->field('schedule_id,schedule_end_time,schedule_status,schedule_stop_sell_status')->where($schedule_map)->select();
		$schedule_list = reindexArr($schedule_list,'schedule_id');
		return $schedule_list;
	}
	private function updateDraftStatus($draft_id_arr,$draft_status){
		if(!$draft_id_arr){
			return false;
		}
		ApiLog('draft update status '.$draft_status.' : '.print_r($draft_id_arr,true),'user_bet_draft');
		
		$map = [
				'ubd_id'=>[
						'in',
						$draft_id_arr
				]
		];
		$data = [
				'ubd_status'=>$draft_status
		];
		
		M()->db(2)->table($this->table_draft)->where($map)->save($data);
	}
}
