<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-11
 * @author tww <merry2014@vip.qq.com>
 */
class PrizeController extends GlobalController{
	public function index(){
		$this->_assignLotteryMap();
		$this->display();
	}
	
	public function prizeInfo(){
		$issue_id = I('issue_id');
		if($issue_id){
			$this->assign('issue_id', $issue_id);
			$this->display('prizeInfo');
		}else{
			$this->error('彩期必填！');
		}
	}
	
	public function confirmPrize(){
		$prize_number	= I('prize_number');
		$issue_id 		= I('issue_id');
		
		if($prize_number && $issue_id){
			$prize_status = D('Issue')->getPrizeStatus($issue_id);
			if($prize_status == PRIZE_STATUS_WAITPRIZE){
				$issue_info  = D('Issue')->getIssueInfo($issue_id);
				$db_prize_num   = $issue_info['issue_prize_number'];
				if($prize_number == $db_prize_num){
					$lottery_id = D('Issue')->getLotteryId($issue_id);
					$result = $this->_startPrize($lottery_id, $issue_id);
					$this->assign('result', $result);
					$this->display();
				}else{
					$this->error('开奖号码不匹配，请联系相关技术人员！');
				}
			}else{
				$this->error('该彩期暂时不能开奖！');
			}			
		}else{
			$this->error('参数填写不完整！');
		}
	}
	
	/*
	 * 派奖 1
	 */
	public function distribute(){
		$this->_assignLotteryMap();
		$this->display();
	}
	
	/**
	 * 派奖 2
	 */
	public function prizeScheme(){
		$issue_id = I('issue_id');
		if($issue_id){
			$schemes = D('WinningsScheme')->getScheme($issue_id);
			$this->assign('issue_id', $issue_id);
			$this->assign('schemes', $schemes);
			$this->display();
		}else{
			$this->error('彩期必填！');
		}
	}
	/**
	 * 派奖 3
	 */
	public function winningsInfos(){
		$issue_id		 = I('issue_id');
		$ws_ids 		 = I('ws_id');
		$ws_bonus_moneys = I('ws_bonus_money');
		
		$request_moneys = array();
		foreach ($ws_ids as $key=>$value){
			$request_moneys[$value] = $ws_bonus_moneys[$key];
		}
		
		$schemes_moneys = D('WinningsScheme')->getMoneysMap($issue_id);
		if($schemes_moneys && $request_moneys){
			foreach ($schemes_moneys as $key=>$sm){
				if($schemes_moneys[$key] != $request_moneys[$key]){
					$this->error('开奖方案信息不一致！');
				}
			}
			
		}else{
			$this->error('开奖方案信息有误！');
		}
		$big_order_ids 		= D('WinningsResult')->getBigWiningsOrderIds($issue_id);
		$cp_center_result 	= D('WinningsResult')->getBigWinings($big_order_ids);

		if($cp_center_result){
			$lottery_id 		= D('Issue')->getLotteryId($issue_id);
			$lottery_model 		= D(C('LOTTERY_MODEL.'.$lottery_id));
			$ticket_infos 		= $lottery_model->getTicketInfos($big_order_ids);
			$order_infos 		= D('Order')->getOrderInfos($big_order_ids);
			$calculate_result 	= D('CalculateResult')->getBigWinings($big_order_ids);	
			$result_infos 		= $this->_mergeWinningsInfo($cp_center_result, $calculate_result, $order_infos, $ticket_infos);
			
			$prize_num = D('Issue')->getPrizeNum($issue_id);
			$issue_no = D('Issue')->getIssueNo($issue_id);
			$lottery_name = D('Lottery')->getLotteryName($lottery_id);
			$this->assign('issue_no', $issue_no);
			$this->assign('issue_id', $issue_id);
			$this->assign('lottery_name', $lottery_name);
			$this->assign('prize_num', $prize_num);
			$this->assign('result_infos', $result_infos);
		}else{
			$this->error('暂时没有大奖需要人工处理！');
		}
		
		$this->display();
	}
	/*
	 * 派奖 4
	 */
	public function confirmationDistribute(){
		$issue_id = I('issue_id');
		if(empty($issue_id)){
			$this->error('彩期必填！');
		}
		$prize_status = D('Issue')->getPrizeStatus($issue_id);
		if($prize_status == PRIZE_STATUS_WAITDISTRIBUTION){
			$lottery_id = D('Issue')->getLotteryId($issue_id);
			$big_order_ids = D('WinningsResult')->getBigWiningsOrderIds($issue_id);
			if($big_order_ids){	
				$post = array(
						'lottery_id' => $lottery_id,
						'issue_no'   => D('Issue')->getIssueNo($issue_id),
						'order_id'	 => implode(',', $big_order_ids)
				);
				$result = $this->_startDistribute($post);
				if($result){
					$this->success('派奖完成！', U('Prize/distribute'));
				}else{
					$this->error('请求失败！');
				}	
			}else{
				$this->error('没有需要派奖的订单！');
			}
		}else{
			$this->error('该彩期不需要派奖！');
		}
	}
	
	private function _mergeWinningsInfo($cp_center_result, $calculate_result, $order_infos, $ticket_infos){
		foreach ($cp_center_result as $key=>$val){
			$cp_center_result[$key]['bet_number'] 	= $ticket_infos[$key]['bet_number'];
			$cp_center_result[$key]['formula'] 		= $calculate_result[$key]['cr_bonus_amount'];
			$cp_center_result[$key]['order_sku'] 	= $order_infos[$val['order_id']]['order_sku'];
			
		}
		return $cp_center_result;
	}
	
	public function getIssue(){
		$lottery_id = I('lottery_id');
		$issue_infos = D('Issue')->getWaitPrize($lottery_id);
		$this->assign('issue_infos', $issue_infos);
		$this->display('issue');
	}
	
	public function distributeIssue(){
		$lottery_id = I('lottery_id');
		$issue_infos = D('Issue')->getWaitDistribute($lottery_id);

		$issue_ids  = array();
		foreach($issue_infos as $issue){
			$issue_ids[] = $issue['issue_id'];
		}
		$has_scheme_issue_id = D('WinningsScheme')->getSchemeIssueIds($issue_ids);
		$need_distribute_issue = D('Issue')->getIssueInfos($has_scheme_issue_id);
		$this->assign('issue_infos', $need_distribute_issue);
		$this->display('issue');
	}
	
	private function _startDistribute($post){
		$curl_url 	= C('DISTRIBUTE_URL');
		$result 	= curl_post($curl_url, $post);
		$result 	= json_decode($result, true);
		$code 		= $result['code'];
		
		return $code === 0 ? true :false;
	}
	
	private function _startPrize($lottery_id, $issue_id){
		$post = array();
		$post['lottery_id'] = $lottery_id;
		$post['issue_no']	= D('Issue')->getIssueNo($issue_id);
		$curl_url = C('PRIZE_URL');
		$result = curl_post($curl_url, $post);//TODO 添加签名
		$result = json_decode($result, true);
		$code = $result['code'];
	
		return $code === 0 ? true :false;
	}
	
	private function _assignLotteryMap(){
		$map = D('Lottery')->getLotteryMap();
		$this->assign('lottery_map', $map);
	}
}