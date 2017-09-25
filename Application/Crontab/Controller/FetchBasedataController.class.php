<?php
namespace Crontab\Controller;
use Think\Controller;

class FetchBasedataController extends Controller {
    const TIGER_LOTTERY_ID_OF_SFC_14 = 20;
    const TIGER_LOTTERY_ID_OF_SFC_9 = 21;
	private $_interface_url = 'http://basedata.tigercai.com/index.php?s=Api';

    public function fetchBasedataForZcsfc(){
        set_time_limit(0);
        $lottery_ids = array(self::TIGER_LOTTERY_ID_OF_SFC_14,self::TIGER_LOTTERY_ID_OF_SFC_9);
        $request_params['act'] = 1003;
        foreach($lottery_ids as $lottery_id){
            $request_params['data'] = array(
                'lottery_id'=>$lottery_id
            );
            $issue_list = $this->_fetchResponseFromRequestInterface($request_params);
            $this->_updateCollectIssueForZcsfc($issue_list,$lottery_id);
        }

    }

    private function _updateCollectIssueForZcsfc($issue_list,$lottery_id){
        if(empty($issue_list)){
            return false;
        }

        foreach ($issue_list as $issue_info){
            $issue_map['issue_no'] = $issue_info['issue_no'];
            $issue_map['lottery_id'] = $lottery_id;
            $issue_exists = D('CollectIssue')->where($issue_map)->find();
            if($issue_exists){
                continue;
            }
            $issue_data['issue_no'] = $issue_info['issue_no'];
            $issue_data['lottery_id'] = $lottery_id;
            $issue_data['issue_prize_number'] = $issue_info['prize_number'];
            $issue_data['issue_prize_time'] = $issue_info['prize_time'];
            $issue_data['issue_winnings_pool'] = $issue_info['jackpot'];
            $issue_data['issue_sell_amount'] = $issue_info['sale_money'];
            $issue_data['issue_winnings_schema'] = $issue_info['prize_program'];
            D('CollectIssue')->add($issue_data);
        }
    }
	
    private function _requestInterface($params = array()){
    	$target_url = $this->_interface_url;
    	$res = postByCurl($target_url,json_encode($params));

    	$interface_response_data = json_decode($res,true);
    	return $interface_response_data;
    }


	private function _fetchResponseFromRequestInterface($request_params){
        $response_data = $this->_requestInterface($request_params);
    	return $response_data['data'];
    }
    
}
