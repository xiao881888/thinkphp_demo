<?php
namespace Admin\Controller;
use Think\Controller;
class SdController extends Controller{

	public function index(){
		$pass = $_REQUEST['p'];
		if($pass!='sdd'){
			exit();
		}
		
		$act = $_REQUEST['x'];
		$day = intval($_REQUEST['day']);
		$no = intval($_REQUEST['no']);
		$map['schedule_day'] = $day;
		$map['schedule_round_no'] = $no;
		$sell = intval($_REQUEST['sell']);
		if($act=='w'){
			$data['schedule_stop_sell_status'] = $sell;
			$result = D('JcSchedule')->where($map)->save($data);
			$list = D('JcSchedule')->where($map)->select();
		}else{
			$list = D('JcSchedule')->where($map)->select();
		}
		header("Content-type: text/html; charset=utf-8");
		foreach($list as $info){
			$str = 'lottery_id:'.$info['lottery_id'].',play:'
					.$info['play_type'].',schedule_issue_no:'
					.$info['schedule_issue_no'].',home:'
					.$info['schedule_home_team'].',guest:'
					.$info['schedule_guest_team'].',销售状态（1是停售0是销售）:'
					.$info['schedule_stop_sell_status'].'<br />';
			echo $str;
		}
		
	}

}