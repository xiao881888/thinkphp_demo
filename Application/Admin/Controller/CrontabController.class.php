<?php
namespace Admin\Controller;
use Think\Controller;
/**
 * @date 2014-12-15
 * @author tww <merry2014@vip.qq.com>
 */
class CrontabController extends Controller{
	
	public function getInformation(){
		$last_time = D('Information')->getLastTime();
		$url = C('CURL_INFORMATION_URL');
		$post = array(
				'request_type'	=> 	'fetch_data',
				'limit'			=>	500,
				'history_time'	=> 	$last_time,
				'fetch_owner' 	=> 	'spider_test',
				'fetch_user' 	=> 	'',
				'fetch_pwd'		=> 	'',
				'fetch_module'	=>	'_article',
				'fetch_source'  =>  '',		
		);

		$response = curl_post($url, $post);

		$response = json_decode($response, true);


		$code = $response['code'];
		$data = $response['result']['data'];

		$informations = array();
		if($code === 0){
			foreach ($data as $article){
				$category_name  = $article['article_category'];
				$source_url		= $article['source_url'];
				$count = D('Information')->getSourceUrlCount($source_url);
				if($count){
					continue;
				}
				
				$category_id = D('InformationCategory')->getAutoCategoryId($category_name);
				$information = array();
				$information['information_title'] 		= $article['article_title'];
				$information['information_content'] 	= $article['article_content'];

				$information['information_create_time'] = $article['article_datetime'];
				$information['information_category_id'] = $category_id;
				$information['information_source_url']  = $source_url;
				$information['information_curl_time']   = $article['modify_time'];
				$informations[] = $information;
			}
		}

		M('Information')->addAll($informations);
	}
	
	public function prize(){
		$this->autoPrize();
	}
	
	public function autoPrize(){
		$auto_prize_lottery = C('AUTO_PRIZE_LOTTERY');
		$curl_url = C('START_PRIZE_URL');
		$issue_info = D('Issue')->getWaitPrize($auto_prize_lottery);

		if(empty($issue_info)){
			dump('no issue data!');die();
		}
		
		
		foreach ($issue_info as $issue){
			$issue_no = $issue['issue_no'];
			$lottery_id = $issue['lottery_id'];

			if($issue_no && $lottery_id){				
				$result = $this->_startPrize($lottery_id, $issue_no, $curl_url);
				dump($result);
			}			
		}	
	}
	//请求未开奖的竞彩
	public function autoJcPrize(){
		$curl_url = 'http://192.168.1.171/jc/prize_result';
		$where = array();
		$where['schedule_half_score'] = array('NEQ', '');
		$where['schedule_prize_status'] = array('IN', array(0,1));
		$needSchedule = M('JcSchedule')->where($where)->select();

		foreach ($needSchedule as $v){
			$post = array();
			$post['lotteryId'] = $v['lottery_id'];
			$post['issueNo'] = $v['schedule_issue_no'];
			$result = curl_post($curl_url, $post);
		}
	}
	
	private function _startPrize($lottery_id, $issue_no, $curl_url){
		$post = array();
		$post['lottery_id'] = $lottery_id;
		$post['issue_no']	= $issue_no;
		$result = curl_post($curl_url, $post);
		$result = json_decode($result, true);
		$code = $result['code'];
	
		return $code === 0 ? true :false;
	}

}