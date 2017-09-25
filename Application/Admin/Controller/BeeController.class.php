<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2015-3-31
 * @author tww <merry2014@vip.qq.com>
 */
class BeeController extends  GlobalController{
	
	public function startIssue($lottery_id){
		$url = C('SZC_BEE.START_ISSUE');
		$post = array();
		$post['lottery_id'] = $lottery_id;
		$result = curl_post($url, $post);
		$this->curlResult($result);
	}
	
	public function startSchedule($lottery_id, $issue_no=''){
	    $url = C('JC_BEE.START_SCHEDULE');
	    $post = array();
	    $post['lottery_id'] = $lottery_id;
	    if(!empty($issue_no)){
	       $post['issue_no'] = $issue_no;
	    }
    	$result = curl_post($url, $post);
	    $this->curlResult($result);
	}
	
	public function scheduleOddsTrigger($lottery_id){
	    $url = C('JC_BEE.SCHEDULE_ODDS_TRIGGER');
	    $post = array();
	    $post['lottery_id'] = $lottery_id;
	    $result = curl_post($url, $post);
	    $this->curlResult($result);
	}
	
	public function reprintoutSzc($lottery_id, $issue_no){
		$url = C('SZC_BEE.REPRINTTOUT');
		$post = array();
		$post['lottery_id'] = $lottery_id;
		$post['issue_no']	= $issue_no;
		$result = curl_post($url, $post);
		$this->curlResult($result);
	}
	
	public function reprintoutJc($lottery_id, $schedule_day, $schedule_week, $schedule_round_no){
		$post = array();
		$url = C('JC_BEE.REPRINTTOUT');
		
		if(in_array($lottery_id, C('JCZQ'))){
		    $lottery_id = C('JC.JCZQ');
		}elseif(in_array($lottery_id, C('JCLQ'))){
		    $lottery_id = C('JC.JCLQ');
		}
		$post['lottery_id'] = $lottery_id;
		$post['schedule_day'] = $schedule_day;
		$post['schedule_week']	= $schedule_week;
		$post['schedule_round_no']	= $schedule_round_no;
		$result = curl_post($url, $post);
		$this->curlResult($result);
	}
	
	public function revokeSzc($lottery_id, $issue_no){
		$url = C('SZC_BEE.REVOKE');
		$post = array();
		$post['lottery_id'] = $lottery_id;
		$post['issue_no']	= $issue_no;
		$result = curl_post($url, $post);
		$this->curlResult($result);
	}
	
	public function revokeJc($lottery_id, $schedule_day, $schedule_week, $schedule_round_no){
		$post = array();
		$url = C('JC_BEE.REVOKE');
		
		if(in_array($lottery_id, C('JCZQ'))){
		    $lottery_id = C('JC.JCZQ');
		}elseif(in_array($lottery_id, C('JCLQ'))){
		    $lottery_id = C('JC.JCLQ');
		}
		$post['lottery_id'] = $lottery_id;
		$post['schedule_day'] = $schedule_day;
		$post['schedule_week']	= $schedule_week;
		$post['schedule_round_no']	= $schedule_round_no;
		$result = curl_post($url, $post);
		$this->curlResult($result);
	}
	
	public function reprintoutOrders($lottery_id, $issue_id, $order_ids){
		$post = array();
		$url = C('REPRINTOUT_ORDERS_URL');
		$post['lottery_id'] = $lottery_id;
		if(isJc($lottery_id)){
		    $post['issue_no']   = D('JcSchedule')->getScheduleNo($issue_id);
		}else{
		    $post['issue_no']   = D('Issue')->getIssueNo($issue_id);
		}
		$order_ids = array_filter(explode(',', $order_ids));
		foreach($order_ids as $id){
		    $post_order_ids[] = intval($id);
		}
		$post_order_ids = json_encode($post_order_ids);
		$post['order_ids']	= $post_order_ids;
		$result = curl_post($url, $post);
		$this->curlResult($result);
	}
	
	public function revokeOrders($lottery_id, $order_ids){
		$post = array();
		$url = C('REVOKE_ORDERS_URL');
		$post['lottery_id'] = $lottery_id;
		$post['order_ids']	= $order_ids;
		$result = curl_post($url, $post);
		$this->curlResult($result);
	}
	
	public function prizeNumber($lottery_id, $issue_no){
		$url = C('SZC_BEE.PRIZENUMBER');
		$post = array();
		$post['lottery_id'] = $lottery_id;
		$post['issue_no']	= $issue_no;
		$result = curl_post($url, $post);
		$this->curlResult($result);
	}
	
	public function prizeScheme($lottery_id, $issue_no){
		$url = C('SZC_BEE.PRIZESCHEME');
		$post = array();
		$post['lottery_id'] = $lottery_id;
		$post['issue_no']	= $issue_no;
		$result = curl_post($url, $post);
		$this->curlResult($result);
	}
	
	public function prizeIssue($lottery_id, $issue_no){
		$url = C('SZC_BEE.PRIZEISSUE');
		$post = array();
		$post['lottery_id'] = $lottery_id;
		$post['issue_no']	= $issue_no;
		$result = curl_post($url, $post);
		$this->curlResult($result);
	}

	public function resultTrigger($lottery_id, $schedule_day, $schedule_week, $schedule_round_no){
	    $post = array();
	    $url = C('JC_BEE.RESULT_TRIGGER');
	    
	    if(in_array($lottery_id, C('JCZQ'))){
	        $lottery_id = C('JC.JCZQ');
	    }elseif(in_array($lottery_id, C('JCLQ'))){
	        $lottery_id = C('JC.JCLQ');
	    }
	    $post['lottery_id'] = $lottery_id;
	    $post['schedule_day'] = $schedule_day;
	    $post['schedule_week']	= $schedule_week;
	    $post['schedule_round_no']	= $schedule_round_no;
	    $result = curl_post($url, $post);
	    $this->curlResult($result);
	}

	public function prizeTrigger($lottery_id, $schedule_day, $schedule_week, $schedule_round_no){
	    $post = array();
	    $url = C('JC_BEE.PRIZE_TRIGGER');
	    
	    if(in_array($lottery_id, C('JCZQ'))){
	        $lottery_id = C('JC.JCZQ');
	    }elseif(in_array($lottery_id, C('JCLQ'))){
	        $lottery_id = C('JC.JCLQ');
	    }
	    $post['lottery_id'] = $lottery_id;
	    $post['schedule_day'] = $schedule_day;
	    $post['schedule_week']	= $schedule_week;
	    $post['schedule_round_no']	= $schedule_round_no;
	    $result = curl_post($url, $post);
	    $this->curlResult($result);
	}
	
	public function curlResult($result){
		$result_arr = json_decode($result, true);
		$code = $result_arr['code'];
		if($code === 0){
			$this->success('操作成功！');
		}else{
			$this->error('操作失败！'.$result);
		}
	}
}