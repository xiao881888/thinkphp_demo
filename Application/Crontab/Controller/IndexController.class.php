<?php
namespace Crontab\Controller;
use Think\Controller;

class IndexController extends Controller {
	const TIGER_LOTTERY_ID_OF_JZ = 6;
	const TIGER_LOTTERY_ID_OF_JL = 7;
    const TIGER_LOTTERY_ID_OF_SFC_14 = 20;
    const TIGER_LOTTERY_ID_OF_SFC_9 = 21;
	private $_interface_url_map = '';
	public function __construct(){
		$this->_interface_url_map = C('INTERFACE_URL_MAP');
		parent::__construct();
	}
	
	public function fetchSzcIssue(){
		$request_params['act'] = 1001;
		$ssq_lottery_id = 1;
		$ssq_last_time = $this->_queryIssueLatestTimeByLotteryId($ssq_lottery_id);
		$request_params['params'] = array('begin_time'=>$ssq_last_time,'type'=>'SSQ','count'=>1000);
		$ssq_list = $this->_fetchResponseFromRequestInterface($request_params);
		$this->_updateIssueInfo($ssq_list, $ssq_lottery_id);
		$dlt_lottery_id = 3;
		$dlt_last_time = $this->_queryIssueLatestTimeByLotteryId($dlt_lottery_id);
		$request_params['params'] = array('begin_time'=>$dlt_last_time,'type'=>'DLT','count'=>1000);
		$dlt_list = $this->_fetchResponseFromRequestInterface($request_params);
		$this->_updateIssueInfo($dlt_list, $dlt_lottery_id);
		
	}

	private function _updateIssueInfo($issue_list,$lottery_id){
		if(empty($issue_list)){
			return false;
		}
		foreach ($issue_list as $issue_info){
			$issue_map['issue_no'] = $issue_info['issue'];
			$issue_map['lottery_id'] = $lottery_id;
			$issue_exists = D('CollectIssue')->where($issue_map)->find();
			if($issue_exists){
				continue;
			}
			$issue_data['issue_no'] = $issue_info['issue'];
			$issue_data['lottery_id'] = $lottery_id;
			$issue_data['issue_prize_number'] = $issue_info['number'];
			$issue_data['issue_prize_time'] = $issue_info['time'];
			$issue_data['issue_winnings_pool'] = $issue_info['prize_info']['jackpot'];
			$issue_data['issue_sell_amount'] = $issue_info['betting_amount'];
			$issue_data['issue_winnings_schema'] = json_encode($issue_info['prize_info']);
			D('CollectIssue')->add($issue_data);				
		}
	}
	
	public function fetchScheduleVsData(){
		$schedule_qt_ids = $this->_queryRequestScheduleIds();
		if($schedule_qt_ids){
			$request_params['act'] = 1002;
			$request_params['params'] = array('id'=>$schedule_qt_ids);
			$schedule_info_list = $this->_fetchResponseFromRequestInterface($request_params);

			$this->_updateVsData($schedule_info_list);
		}
	}
	
	public function fetchScheduleVsDataForBasketball(){
		set_time_limit(0);
		$schedule_date_list = $this->_queryRequestScheduleDateList();
		if($schedule_date_list){
			$request_params['act'] = 1003;
			$request_params['params'] = array('schedule_date'=>$schedule_date_list);
				
			$schedule_vs_data_list = $this->_fetchResponseFromRequestInterface($request_params,'get');
			$this->_updateVsDataForJCBasketball($schedule_vs_data_list);
		}
	}

    public function fetchScheduleVsDataForZcsfc(){
        set_time_limit(0);
        $issue_no_list = $this->_queryRequestSfcIssueNo();
        if($issue_no_list){
            $request_params['act'] = 1004;
            foreach($issue_no_list as $issue_no){
                $request_params['params'] = array('issue_no'=>$issue_no);
                $schedule_info_list = $this->_fetchResponseFromRequestInterface($request_params,'get');
                $this->_updateVsDataForZcsfc($schedule_info_list);
            }
        }
    }

    private function _queryRequestSfcIssueNo(){
        $where['lottery_id'] = self::TIGER_LOTTERY_ID_OF_SFC_14;
        $where['issue_is_current'] = array('IN',array(1,2));
        return M('Issue')->where($where)->getField('issue_no',true);
    }

    private function _updateVsDataForZcsfc($schedule_info_list){
        if(empty($schedule_info_list)){
            return false;
        }
        foreach ($schedule_info_list as $key => $schedule_info) {
            $schedule_key = explode('*', $key);
            $schedule_date = $schedule_key[0];
            $schedule_round_no = $schedule_key[1];
            $vs_data = $this->_getVsDataForZcsfc($schedule_info);
            $this->_updateInfoForZcsfc($vs_data,self::TIGER_LOTTERY_ID_OF_SFC_14,$schedule_date,$schedule_round_no);
            $this->_updateInfoForZcsfc($vs_data,self::TIGER_LOTTERY_ID_OF_SFC_9,$schedule_date,$schedule_round_no);
        }
    }

    private function _updateInfoForZcsfc($vs_data,$lottery_id,$schedule_date,$schedule_round_no){
        $is_add = $this->_isAddVsDataForZcsfc($lottery_id,$schedule_date,$schedule_round_no);
        if($is_add){
            $this->_saveVsDataForZcsfc($vs_data,$lottery_id,$schedule_date,$schedule_round_no);
        }else{
            $this->_addVsDataForZcsfc($vs_data,$lottery_id,$schedule_date,$schedule_round_no);
        }
    }

    private function _addVsDataForZcsfc($vs_data,$lottery_id,$schedule_date,$schedule_round_no){
        $vs_data['lottery_id'] = $lottery_id;
        $vs_data['schedule_date'] = $schedule_date;
        $vs_data['schedule_round_no'] = $schedule_round_no;
        return D('VsData')->add($vs_data);
    }

    private function _saveVsDataForZcsfc($vs_data,$lottery_id,$schedule_date,$schedule_round_no){
        $where['lottery_id'] = $lottery_id;
        $where['schedule_date'] = $schedule_date;
        $where['schedule_round_no'] = $schedule_round_no;
        return D('VsData')->where($where)->save($vs_data);
    }

    private function _getVsDataForZcsfc($schedule_info){
        $schedule_data['schedule_home_rank'] = $schedule_info['home_order'];
        $schedule_data['schedule_guest_rank'] = $schedule_info['guest_order'];
        $schedule_data['third_party_schedule_id'] = $schedule_info['schedule_qt_id'];
        $history_info = $this->_buildHistoryInfo($schedule_info['history_info']);
        $schedule_data['vs_history_data'] = json_encode($history_info);

        $lastest_info['home'] = $this->_buildHistoryInfo($schedule_info['lastest_info']['home']);
        $lastest_info['guest'] = $this->_buildHistoryInfo($schedule_info['lastest_info']['guest']);
        $schedule_data['vs_latest_data'] = json_encode($lastest_info);
        $avg_info['v3'] = $schedule_info['standard_avg']['standard_home_win_avg'];
        $avg_info['v1'] = $schedule_info['standard_avg']['standard_standoff_avg'];
        $avg_info['v0'] = $schedule_info['standard_avg']['standard_guest_avg'];

        $schedule_data['vs_average_rate'] = json_encode($avg_info);
        $schedule_data['vs_detail_url'] = $schedule_info['detail_url'];
        return $schedule_data;
    }

    private function _isAddVsDataForZcsfc($lottery_id,$schedule_date,$schedule_round_no){
        $schedule_map['lottery_id'] = $lottery_id;
        $schedule_map['schedule_date'] = $schedule_date;
        $schedule_map['schedule_round_no'] = $schedule_round_no;
        return D('VsData')->where($schedule_map)->find();
    }
	
	private function _updateVsDataForJCBasketball($schedule_vs_data_list){
		if(empty($schedule_vs_data_list)){
			return false;
		}
		foreach ($schedule_vs_data_list as $schedule_key => $schedule_info) {
			$schedule_data = array();
			$schedule_map['lottery_id'] = self::TIGER_LOTTERY_ID_OF_JL;
			$schedule_map['schedule_date'] = substr($schedule_key,0,8);
			$schedule_map['schedule_round_no'] = substr($schedule_key,8,3);
				
			$history_info = $this->_buildHistoryInfo($schedule_info['history_info']);
			$schedule_data['third_party_schedule_id'] = $schedule_info['schedule_id'];
			$schedule_data['vs_history_data'] = json_encode($history_info);
			$schedule_data['schedule_home_rank'] = $schedule_info['home_order'];
			$schedule_data['schedule_guest_rank'] = $schedule_info['guest_order'];
			$lastest_info['home'] = $this->_buildHistoryInfo($schedule_info['lastest_info']['home']);
			$lastest_info['guest'] = $this->_buildHistoryInfo($schedule_info['lastest_info']['guest']);
			$schedule_data['vs_latest_data'] = json_encode($lastest_info);
			$avg_info['v3'] = $schedule_info['standard_avg']['standard_home_win_avg'];
			$avg_info['v0'] = $schedule_info['standard_avg']['standard_guest_win_avg'];
			$schedule_data['vs_average_rate'] = json_encode($avg_info);
			$vs_info = D('VsData')->where($schedule_map)->find();
	
			if($vs_info){
				$res = D('VsData')->where($schedule_map)->save($schedule_data);
			}else{
				$schedule_data['lottery_id'] = self::TIGER_LOTTERY_ID_OF_JL;
				$schedule_data['schedule_date'] = $schedule_map['schedule_date'];
				$schedule_data['schedule_round_no'] = $schedule_map['schedule_round_no'];
				$res = D('VsData')->add($schedule_data);
			}

		}
	}
	
	private function _queryIssueLatestTimeByLotteryId($lottery_id){
		$issue_map['lottery_id'] = $lottery_id;
		$order = 'issue_prize_time desc';
		$latest_issue_info = D('CollectIssue')->where($issue_map)->order($order)->find();
		return $latest_issue_info['issue_prize_time'];
	}
	
	private function _buildHistoryInfo($history_info){
		$res_history_info['win'] = $history_info['win'];
		$res_history_info['equal'] = intval($history_info['flat']);
		$res_history_info['lose'] = $history_info['fail'];
// 		$res_history_info['games_count'] = $history_info['count'];
		$res_history_info['games_count'] = intval($history_info['win'])+
											intval($history_info['flat'])+
											intval($history_info['fail']);
		return $res_history_info;
	}
	
	private function _updateVsData($schedule_info_list){
		if(empty($schedule_info_list)){
			return false;
		}
		foreach ($schedule_info_list as $key => $schedule_info) {
			$schedule_data = array();
			$schedule_key = explode('*', $key);
			$schedule_map['lottery_id'] = self::TIGER_LOTTERY_ID_OF_JZ;
			$schedule_map['schedule_date'] = $schedule_key[0];
			$schedule_map['schedule_round_no'] = $schedule_key[1];
			$schedule_data['schedule_home_rank'] = $schedule_info['home_order'];
			$schedule_data['schedule_guest_rank'] = $schedule_info['guest_order'];
			$schedule_data['third_party_schedule_id'] = $schedule_info['schedule_qt_id'];
				
			$history_info = $this->_buildHistoryInfo($schedule_info['history_info']);
			$schedule_data['vs_history_data'] = json_encode($history_info);
			
			$lastest_info['home'] = $this->_buildHistoryInfo($schedule_info['lastest_info']['home']);
			$lastest_info['guest'] = $this->_buildHistoryInfo($schedule_info['lastest_info']['guest']);
			$schedule_data['vs_latest_data'] = json_encode($lastest_info);
			$avg_info['v3'] = $schedule_info['standard_avg']['standard_home_win_avg'];
			$avg_info['v1'] = $schedule_info['standard_avg']['standard_standoff_avg'];
			$avg_info['v0'] = $schedule_info['standard_avg']['standard_guest_avg'];
				
			$schedule_data['vs_average_rate'] = json_encode($avg_info);
			$schedule_data['vs_detail_url'] = $schedule_info['detail_url'];
			$vs_info = D('VsData')->where($schedule_map)->find();

			if($vs_info){
				$res = D('VsData')->where($schedule_map)->save($schedule_data);
			}else{
				$schedule_data['lottery_id'] = self::TIGER_LOTTERY_ID_OF_JZ;
				$schedule_data['schedule_date'] = $schedule_key[0];
				$schedule_data['schedule_round_no'] = $schedule_key[1];
				$res = D('VsData')->add($schedule_data);
			}
		}
	}

	private function _queryRequestScheduleIds(){
		$schedule_no_list = D('JcSchedule')->queryScheduleNoList();
		$request_schedule_no_list = array();
		foreach($schedule_no_list as $schedule_no_item){
			if(in_array($schedule_no_item['lottery_id'],array(701,702,703,704,705))){
				continue;
			}
			$schedule_no = $schedule_no_item['schedule_day'].'*'.$schedule_no_item['schedule_round_no'];
			$request_schedule_no_list[] = $schedule_no;
		}
		$schedule_qt_ids = '';
		if($request_schedule_no_list){
			$schedule_qt_ids = implode(',',$request_schedule_no_list);
		}
		return $schedule_qt_ids;
	}

	private function _queryRequestScheduleDateList(){
		$schedule_date_list = D('JcSchedule')->queryScheduleDateList();
		return implode('_', $schedule_date_list);
	}
	
    private function _requestInterface($act_code, $params = array()){
    	$target_url = $this->_interface_url_map[$act_code];
    	$res = postByCurl($target_url,$params);

    	$interface_response_data = json_decode($res,true);
    	return $interface_response_data;
    }
    
    private function _requestInterfaceByGET($act_code, $params = array()){
    	$target_url = $this->_interface_url_map[$act_code];

    	$res = getByCurl($target_url,$params, 60);
    	$interface_response_data = json_decode($res,true);
    	return $interface_response_data;
    }

	private function _fetchResponseFromRequestInterface($request_params, $request_type = 'post'){
    	$act_config = $this->_interface_act_map[$request_params['act']];
    	if($request_type=='get'){
    		$response_data = $this->_requestInterfaceByGET($request_params['act'],$request_params['params']);
    	}else{
    		$response_data = $this->_requestInterface($request_params['act'],$request_params['params']);
    	}
    	return $response_data['data'];
    }
    
}
