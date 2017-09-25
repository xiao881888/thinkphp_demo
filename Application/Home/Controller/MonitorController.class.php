<?php
namespace Home\Controller;
use Think\Controller;

class MonitorController extends Controller{

	const WARNING_TYPE_TICKET_PROXY_OUTLINE = 1;

	const WARNING_TYPE_TICKET_PROXY_PRESSURE = 2;
	
	const WARNING_TYPE_JCW_NOTICE = 3;

	const WARNING_TYPE_DEADLINE_PRESSURE = 4;

	const WARNING_TYPE_TICKET_PRINTOUT_FINISH = 5;

	private $warning_message_template;

	private $warning_message_reason;

	private $service_manager_tel;

	public function _initialize()
	{
		$this->warning_message_template = array(
			self::WARNING_TYPE_TICKET_PROXY_OUTLINE => '82542',
			self::WARNING_TYPE_TICKET_PROXY_PRESSURE => '82542',
			self::WARNING_TYPE_JCW_NOTICE => '82542',
			self::WARNING_TYPE_DEADLINE_PRESSURE => '82542',
			self::WARNING_TYPE_TICKET_PRINTOUT_FINISH => '82542',
		);

		$this->warning_message_reason = array(
			self::WARNING_TYPE_TICKET_PROXY_OUTLINE => '有长时间未出票订单',
			self::WARNING_TYPE_TICKET_PROXY_PRESSURE => '服务器待出票订单太多',
			self::WARNING_TYPE_JCW_NOTICE => '中国竞彩网出新公告了',
			self::WARNING_TYPE_TICKET_PRINTOUT_FINISH => '未出票订单已出票[@完成]',
		);
		$this->service_manager_tel = array(
			self::WARNING_TYPE_TICKET_PROXY_OUTLINE => '18506060108,15980228063,13459461935,18506930687',
			self::WARNING_TYPE_TICKET_PROXY_PRESSURE => '18506060108,15980228063,13459461935,18506930687',
			self::WARNING_TYPE_JCW_NOTICE => '18650455840,18506060108,15980228063,13459461935,18695709596,15806016627,18506930687',
			//self::WARNING_TYPE_JCW_NOTICE => '15980228063',
			self::WARNING_TYPE_DEADLINE_PRESSURE => '15980228063,15859145628,13459461935',
			self::WARNING_TYPE_TICKET_PRINTOUT_FINISH => '18506060108,15980228063,13459461935,18506930687',
		);
		
// 		$this->service_manager_tel = '18506060108,15980228063,13459461935,18695709596';
	}

	public function checkTicketProxyStatus(){
		die('暂时关闭');
		ApiLog('dateH:'.date('H'),'debug_monitor');

		if (intval(date('H')) >= 0 && intval(date('H')) < 9) {
			ApiLog('date:'.date('Y-m-d H:i:s'),'debug_monitor');
			return false;
		}
		echo date('Y-m-d H:i:s').' 监控    出票状态   一次';
		$order_map = array();
		$order_map['order_status'] = array('IN', '1,2');
		$order_map['lottery_id'] = array('IN', '601,602,603,604,605,606');
		$ticket_printing = D('Order')->where($order_map)
			->order('order_id asc')
			->find();
		if ($ticket_printing) {
			$is_warning = $this->_checkJcTicketUnprintoutTime($ticket_printing);
			$lock = $this->getLock(__FUNCTION__);
			if ($is_warning and $lock) {
				print_r($ticket_printing);
				$this->sendWarningMessage(self::WARNING_TYPE_TICKET_PROXY_OUTLINE);
				ApiLog('send_message:'.date('Y-m-d H:i:s'),'debug_monitor');
				echo 'send_message';
				$this->setLock(__FUNCTION__);
			} else {
				if (!$is_warning and !$lock) {
					$this->sendWarningMessage(self::WARNING_TYPE_TICKET_PRINTOUT_FINISH);
					echo 'send_finish';
					ApiLog('send_finish:'.date('Y-m-d H:i:s'),'debug_monitor');
					$this->releaseLock(__FUNCTION__);
				}
			}
			
		}

		echo date('Y-m-d H:i:s').' 监控    出票状态   一次';
	}
	
	private function _checkJcTicketUnprintoutTime($order_info){
		if(empty($order_info)){
			return false;
		}
		
		$hour = date("H", strtotime($order_info['order_create_time']));
		if($hour>=0 && $hour<=9){
			return false;
		}
		
		$warning_time_gap = 30*60;
		$time_gap = time() - strtotime($order_info['order_create_time']);
		if ($time_gap >= $warning_time_gap) {
			return true;
		}
		
		return false;
	}

	public function checkTicketProxyPressure(){
		die('暂时关闭');
		if (intval(date('H')) >= 0 && intval(date('H')) < 9) {
			ApiLog('date:'.date('Y-m-d H:i:s'),'debug_monitor');
			return false;
		}
		
		$warning_order_count = 10;

		$order_map = array();
		$order_map['order_status'] = C('ORDER_STATUS.PRINTOUTING');
		$printing_order_count = D('Order')->where($order_map)->count();

		if ($printing_order_count >= $warning_order_count) {
			$this->sendWarningMessage(self::WARNING_TYPE_TICKET_PROXY_PRESSURE);
		}

		echo date('Y-m-d H:i:s').'监控    出票压力   一次';
	}

	public function checkDeadlineProxyPressure(){
		$deadline = date('Y-m-d H:i:s', time()+1200);
		
		$schedule_map = array(
			'schedule_end_time' => array('elt', $deadline),
			'schedule_status' => 1
			);
		$deadline_schedule = M('JcSchedule')->where($schedule_map)->getField('schedule_id, concat(schedule_day, schedule_round_no) schedule_no', true);

		if (is_array($deadline_schedule) && count($deadline_schedule) > 0) {
			$order_map = array();
			$order_map['order_status'] = array('IN', '1,2,7');
			$order_map['lottery_id'] = array('IN', '601,602,603,604,605,606');
			$order_map['first_issue_id'] = 	array('IN', array_keys($deadline_schedule));
			$printing_order_count = D('Order')->where($order_map)->count();

			if ($printing_order_count > 50) {
				$new_deadline_schedule = reindexArr($deadline_schedule, 'schedule_no');
				$deadline_schedule_count = count($new_deadline_schedule);
				
				$message_data = array('有'.$deadline_schedule_count.'场比赛马上截止投注，目前有'.$printing_order_count.'笔订单出票中，请关注！');
				$this->sendWarningMessage(self::WARNING_TYPE_DEADLINE_PRESSURE, $message_data);
			}
		}

		echo date('Y-m-d H:i:s').'监控 截止时间临近出票压力 一次';
	}

	public function checkJCWNotice(){
		$notices_page = requestPage('http://info.sporttery.cn/iframe/lottery_notice.php');
		// print_r($notices_page);

		if ($notices_page['code'] == '200' && !empty($notices_page['page'])) {
			$notices_page = iconv('gb2312', 'utf-8', $notices_page['page']);

			preg_match_all('/<div\s+class="sales_tit">(.+?)<\/div>/is', $notices_page, $match);
			$latest_notice_title = trim($match[1][0]);

			if ($latest_notice_title) {
				$map = array();
				$map['notice_title'] = $latest_notice_title;
				$is_exist = D('JcwNotice')->where($map)->count();
// 				ApiLog('is_exist:'.$is_exist, 'jcw');
// 				ApiLog('latest_notice_title:'.$latest_notice_title, 'jcw');
				if ($is_exist == 0) {
					$notice_data = array(
						'notice_title' => $latest_notice_title,
						'notice_createtime' => date('Y-m-d H:i:s')
						);

					D('JcwNotice')->add($notice_data);

					$this->sendWarningMessage(self::WARNING_TYPE_JCW_NOTICE);
				}
			}
		}

		echo date('Y-m-d H:i:s').'监控    竞彩网公告    一次';
	}


	private function sendWarningMessage($message_type, $message_data=array()){
		if (empty($message_data)) {
			$message_data = array($this->getReasonByType($message_type));
		}

		$res = sendTemplateSMS($this->service_manager_tel[$message_type], $message_data, $this->getTemplateIDByType($message_type));
// 		ApiLog("phone:".print_r($this->service_manager_tel[$message_type],true).'==='.print_r($res,true),'jcw');

		return $res;
	}

	private function getTemplateIDByType($message_type){
		$template_id = $this->warning_message_template[$message_type];
		return $template_id;
	}

	private function getReasonByType($message_type){
		$reason = $this->warning_message_reason[$message_type];
		return $reason;
	}

	private function getLock($name)
	{
		$lock_name = $this->lockName($name);

		if (empty(S($lock_name))) {
			return true;
		}

		return false;
	}

	private function lockName($name)
	{
		return 'lock:monitor:' . $name;
	}

	private function releaseLock($name)
	{
		return S($this->lockName($name), null);
	}

	private function setLock($name)
	{
		return S($this->lockName($name),$name,3600);
	}

}