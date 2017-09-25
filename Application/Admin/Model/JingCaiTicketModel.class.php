<?php
namespace Admin\Model;
use Think\Model;

class JingCaiTicketModel extends TicketModel{

	public function getTicketContent($order_info, $ticket_id){
		$where = array(
			'id' => array('IN', $ticket_id)
			);
		$tickets = $this->where($where)->select();
		$result = array();

		foreach ($tickets as $id => $ticket) {
			$content = json_decode($ticket['ticket_content'], true);
			foreach ($content as $issue) {
				$issue_tmp = explode('*', $issue['issue_no']);
				$issue_no = $issue_tmp[2];
				// $issue_arr[$issue_no][] = showJCPlayOption($issue['lottery_id'], $issue['bet_options']);
				$issue_arr[$issue_no] =  (array)$issue_arr[$issue_no] + $issue['bet_options'];

			}
		}

		dump($issue_arr);exit;
		return $result;
	}
	
	public function getTicketInfos($order_id){
	    $tickets = array();
	    $ticket_list = $this->tickets($order_id);
	    foreach ($ticket_list as $id => $ticket) {
            $result = array();
	        $result['ticket_seq'] = $ticket['ticket_seq'];
	        $result['ticket_id'] = $ticket['ticket_id'];
	        $result['stake_count'] = $ticket['stake_count'];
	        $result['play_type'] = $ticket['play_type'];
	        $result['total_amount'] = $ticket['total_amount'];
	        $result['ticket_status'] = $ticket['ticket_status'];
	        $result['winnings_status'] = $ticket['winnings_status'];
	        $result['winnings_bonus'] = $ticket['winnings_bonus'];
	        $result['bet_type'] = $ticket['bet_type'];	
	        $result['printout_time'] = $ticket['printout_time'];    
	        $result['ticket_multiple'] = $ticket['ticket_multiple'];        
	        
	        $ticket_content = json_decode($ticket['ticket_content'], true);
	        foreach ($ticket_content as $key => $content) {
	            $schedule_tmp = explode('*', $content['issue_no']);
	            $lottery_id = $schedule_tmp[0];
	            $schedule_info = D('JcSchedule')->getScheduleInfoByNo($lottery_id, $content['issue_no']);
	            $result['ticket_content'][$key]['lottery_id'] = $content['lottery_id'];
	            $result['ticket_content'][$key]['round_no'] = $schedule_info['schedule_day'].'-'.$schedule_info['schedule_round_no'];
	            $result['ticket_content'][$key]['vs_team'] = $schedule_info['schedule_home_team'].' VS '.$schedule_info['schedule_guest_team'];
	            $result['ticket_content'][$key]['bet_options'] =  showJCPlayOption($content['lottery_id'], $content['bet_options']);
	        }
	        
	        $printout_content = json_decode($ticket['printout_odds'], true);
	        foreach ($printout_content as $key => $content) {
	            $schedule_tmp = explode('*', $content['issue_no']);
	            $lottery_id = $schedule_tmp[0];
	            $schedule_info = D('JcSchedule')->getScheduleInfoByNo($lottery_id, $content['issue_no']);
	            $result['printout_content'][$key]['lottery_id'] = $content['lottery_id'];
	            $result['printout_content'][$key]['round_no'] = $schedule_info['schedule_day'].'-'.$schedule_info['schedule_round_no'];
	            $result['printout_content'][$key]['vs_team'] = $schedule_info['schedule_home_team'].' VS '.$schedule_info['schedule_guest_team'];
	            
	            foreach($content['odds'] as $option => $odds){
	                if($option == 'letPoint'){
	                   $result['printout_content'][$key]['odds'] .= '(让球数：'.$odds.')<br>';
	                }elseif($option == 'basePoint'){
	                   $result['printout_content'][$key]['odds'] .= '(预设总分：'.$odds.')<br>';
	                }else{
	                   $result['printout_content'][$key]['odds'] .=  showJCPlayOption($content['lottery_id'], array($option));
	                   $result['printout_content'][$key]['odds'] .=  '，赔率:'.$odds.'<br>';
	                }
	            }
	        }
	        $tickets[] = $result;
	    }
	    return $tickets;
	}
	
}