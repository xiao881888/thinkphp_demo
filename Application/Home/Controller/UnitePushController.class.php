<?php

namespace Home\Controller;

use Home\Controller\GlobalController;
use Home\Util\Factory;

/**
 * 统一推送接口
 * @package Home\Controller
 */
class UnitePushController extends GlobalController
{

	const TEMPLATE_OF_SSQ_DLT_PUSH = "%s ! %s%s期开奖号码已出,下期已开始销售,快来抢夺幸运!";
	const TEMPLATE_COUPON_PUSH = "恭喜您获得%s元红包！安卓用户请升级至2.4版本方可查看和使用！";
	const PUSH_OF_PERSONAL = 0;
	const PUSH_OF_ALL = 1;
	const TEMPLATE_PUSH_GOLD_EVENT = "%s %s'  进球!当前比分【%s%s-%s%s】!";//阿森纳59’进球！当前比分【阿森纳2-0皇家马德里】！


	public function pushActivityMessage()
	{
		//设置程序执行时间的函数
		set_time_limit(0);

		//函数设置与客户机断开是否会终止脚本的执行
		ignore_user_abort(true);

		$message = I('msg', ''); //推送信息
		$uidList = I('uid', '');//推送用户组
		$type = I('type', '');//推送类型 1：订单详情， 2：下注页面， 3：充值页面， 4：webview
		$paramers = I('paramers', '');//附加参数

		if (!empty($paramers)) {
			$paramers = json_decode($paramers, true);
		}

		if (!empty($message) && !empty($uidList)) {
			if ($uidList === 'all') {
				//发送全部用户
				$push_device_List = D('PushDevice')->getUnitePushDeviceList($uidList,self::PUSH_OF_ALL);
			} else {
				//发送个人用户
				$push_device_List = D('PushDevice')->getUnitePushDeviceList($uidList,self::PUSH_OF_PERSONAL);
			}

			$result = $this->_unitePush($message, $push_device_List, $type, $paramers);
			$error_code = 0;
			$error_msg = "发送完成，成功{$result['success']}条，失败{$result['fail']}条;";
			$error_msg .= $result['msg'];
		} else {
			$error_code = 1;
			$error_msg = 'message, uid不能为空';
		}

		$resp = array();
		$resp['error_code'] = $error_code;
		$resp['msg'] = $error_msg;

		$this->ajaxReturn($resp);
	}

	/**
	 * 统一推送
	 * @param $message 推送信息
	 * @param $push_device_infos 所有的推送设备列表
	 * @param int $event_type 附加信息
	 * @param array $custom_keys 附加信息
	 * @return array
	 */
	private function _unitePush($message, $push_device_List, $event_type = 0, $custom_keys = array())
	{
		//判断是由后台内容推送过来的还是其他渠道过来的推送信息
		if (array_key_exists('pd_app_package', $push_device_List)) {
			$push_device_List = array($push_device_List);
		}
		$result = array();
		$result['success'] = $result['fail'] = 0;

		//将所有的推送设备数组进行重组，分成IOS跟安卓,再根据不同的包名进行分组
		$new_push_device_List = array();
		foreach ($push_device_List as $push_info) {
			if ($push_info['pd_app_platform'] == OS_OF_ANDROID) {
				$new_push_device_List['android'][$push_info['pd_app_package']][] = $push_info['pd_device_token'];
			}
			if ($push_info['pd_app_platform'] == OS_OF_IOS) {
				$new_push_device_List['ios'][$push_info['pd_app_package']][] = $push_info['pd_device_token'];
			}
		}

		if (isset($new_push_device_List['android']) || isset($new_push_device_List['ios'])) {
			//推送所有安卓设备
			if (!empty($new_push_device_List['android'])) {
				$android_res = $this->_pushDevice(OS_OF_ANDROID, C('PUSH_BRAND.ALI_PUSH'), $new_push_device_List['android'], $message, $event_type, $custom_keys);
			}

			//推送所有IOS设备
			if (!empty($new_push_device_List['ios'])) {
				$ios_res = $this->_pushDevice(OS_OF_IOS, C('PUSH_BRAND.IOS_PUSH'), $new_push_device_List['ios'], $message, $event_type, $custom_keys);
			}

			//统计所有的成功条数和失败条数
			if ($android_res['status'] && $ios_res['status']) {
				$result['success'] += count($push_device_List);
			} elseif ($android_res['status'] && !$ios_res['status']) {
				$result['success'] += count($new_push_device_List['android']);
				$result['fail'] += count($new_push_device_List['ios']);
			} elseif ($ios_res['status'] && !$android_res['status']) {
				$result['success'] += count($new_push_device_List['ios']);
				$result['fail'] += count($new_push_device_List['android']);
			} else {
				$result['fail'] += count($push_device_List);
				$result['msg'] = 'android: ' . $android_res['msg'] . ';ios:' . $ios_res['msg'];
			}
		}
		return $result;
	}


	/**
	 * 推送新的推送接口
	 * @param $deviceType 设备类型（IOS，安卓）
	 * @param $type 推送类型 (阿里推送,IOS推送)
	 * @param $device_arr 所有的设备分组
	 * @param $message 推送信息
	 * @param int $event_type 附加信息
	 * @param array $custom_keys 附加信息
	 * @return array
	 */
	private function _pushDevice($deviceType, $type, $device_arr, $message, $event_type = 0, $custom_keys = array())
	{
		$data = array();
		if ($deviceType == OS_OF_ANDROID) {
			$post['platform'] = OS_OF_ANDROID;//1 android,2 IOS
		} elseif ($deviceType == OS_OF_IOS) {
			$post['platform'] = OS_OF_IOS;//1 android,2 IOS
		}
		$post['push_brand'] = $type;
		$post['target'] = json_encode($device_arr);
		$post['message'] = $message;

		$server['server'] = $custom_keys;
		$server['server']['event_type'] = $event_type;
		$post['extras'] = json_encode($server);

		$post['xiaomi_activity'] = 'co.sihe.tigerlottery.push.XiaoMiPushActivity';

		$url = C('PUSH_CONFIG.PUSH_SAME_MSG_URL');

		$result = requestByCurl($url, $post);
		$result = json_decode($result, true);
		$return_code = C('PUSH_CONFIG.RETURN_CODE');
		if ($result['code'] === 0) {
			$data['status'] = true;
			$data['msg'] = $return_code[$result['code']];
		} else {
			$data['status'] = false;
			$data['msg'] = $return_code[$result['code']];
		}
		return $data;
	}

	public function testPushIssuePrizeNum(){
		$lottery_id = I('lottery_id','');
		$issue_no   = I('issue_no','');
		$prize_num  = I('prize_number','');
		//$uidList = 'all';
		$uidList = '1,2,106751,117832,361';
		if($lottery_id && $issue_no && $prize_num){
			if($this->_isAreadyPushList($lottery_id,$issue_no)){
				exit;
			}
			$lottery_name = $this->_getLotteryNameById($lottery_id);

			$send_msg_template = self::TEMPLATE_OF_SSQ_DLT_PUSH;
			$send_msg = sprintf($send_msg_template,$prize_num,$lottery_name,$issue_no);

			$this->push($uidList,self::PUSH_OF_PERSONAL,$send_msg);

			$this->_setAreadyPushList($lottery_id,$issue_no);
		}

	}

	public function pushIssuePrizeNum(){
		$lottery_id = I('lottery_id','');
		$issue_no   = I('issue_no','');
		$prize_num  = I('prize_number','');
		$uidList = 'all';

		if($lottery_id && $issue_no && $prize_num){
			if($this->_isAreadyPushList($lottery_id,$issue_no)){
				exit;
			}
			$lottery_name = $this->_getLotteryNameById($lottery_id);

			$send_msg_template = self::TEMPLATE_OF_SSQ_DLT_PUSH;
			$send_msg = sprintf($send_msg_template,$prize_num,$lottery_name,$issue_no);

			$this->push($uidList,self::PUSH_OF_ALL,$send_msg);

			$this->_setAreadyPushList($lottery_id,$issue_no);
		}

	}

	private function _getLotteryNameById($lottery_id){
		if($lottery_id == 1){
			return '双色球';
		}elseif($lottery_id == 3){
			return '大乐透';
		}
	}

	private function _isAreadyPushList($lottery_id,$issue_no){
		$redis = Factory::createAliRedisObj();
		if($redis){
			$redis->select(0);
			return $redis->sContains('tiger_api:unite_push:ssq_dlt_push',$lottery_id.'-'.$issue_no);
		}
		ApiLog('redis未连接','FullReducedCouponConfig');
		return true;
	}

	private function _setAreadyPushList($lottery_id,$issue_no){
		$redis = Factory::createAliRedisObj();
		if($redis){
			$redis->select(0);
			$redis->sAdd('tiger_api:unite_push:ssq_dlt_push',$lottery_id.'-'.$issue_no);
		}else{
			ApiLog('redis未连接','FullReducedCouponConfig');
		}
	}

	public function pushFullReducedCouponInfo($uid,$coupon_value){
		$uidList = $uid;
		$send_msg = sprintf(self::TEMPLATE_COUPON_PUSH,$coupon_value);
		$this->push($uidList,self::PUSH_OF_PERSONAL,$send_msg,6);
	}

	public function push($uidList,$push_type,$send_msg,$type = 0, $paramers = array()){
		$push_device_List = D('PushDevice')->getUnitePushDeviceList($uidList,$push_type);
		$result = $this->_unitePush($send_msg, $push_device_List,$type,$paramers);
		$error_msg = "发送完成，成功{$result['success']}条，失败{$result['fail']}条;";
		$error_msg .= $result['msg'];
		return $error_msg;

	}

	public function testPush(){
		$id = I('id',0);
		if(empty($id)){
			echo 'uid不能为空';die;
		}
		$uidList = $id;
		$send_msg = '[篮彩推荐] NBA: 鹈鹕 VS 76人, 浓眉哥迎战恩比德, 详情请点击查看';
		$this->push($uidList,self::PUSH_OF_PERSONAL,$send_msg);
		echo 'success';
	}

	public function pushGoldEvent(){

		$data = I('message');
		ApiLog('$data:'.$data,'pushGoldEvent');
		$data = json_decode($data,true);
		if(empty($data)){
			exit('信息为空');
		}

		$match_info = $data['match_info'];
		$happen_time = $data['happen_time'];
		$is_home = $data['is_home'];

		$schedule_id = $match_info['schedule_id'];
		if($this->_isAreadyPushGoldEvent($schedule_id)){
			ApiLog('该进球事件已经推送','pushGoldEvent');
			exit('该进球事件已经推送');
		}

		$uids = $this->_getPushGoldEventUsers($schedule_id);
		ApiLog('$uids:'.print_r($uids,true),'pushGoldEvent');
		if(!$uids){
			exit('推送的用户不存在');
		}

		$push_message = $this->_makePushMessage($match_info,$is_home,$happen_time);

		$this->push($uids,self::PUSH_OF_PERSONAL,$push_message);

		$this->_setAreadyPushGoldEvent($schedule_id);

		$this->_delScheduleIdFromRedis($schedule_id);


	}

	private function _delScheduleIdFromRedis($schedule_id){
		$redis = $this->getRedis();
		$redis->del('tiger_api:push_gold_event:notify_uids'.$schedule_id);
		$redis->del('tiger_api:push_gold_event:jc_schedule_list_'.$schedule_id);
		$redis->sRem('tiger_api:push_gold_event:jc_schedule_ids',$schedule_id);
	}

	private function _makePushMessage($match_info,$is_home,$happen_time){
		$home_team_name = $match_info['home_team_name'];
		$guest_team_name = $match_info['guest_team_name'];
		$home_score = $match_info['home_score'];
		$guest_score = $match_info['guest_score'];
		//阿森纳59’进球！当前比分【阿森纳2-0皇家马德里】！
		if($is_home){
			return sprintf(self::TEMPLATE_PUSH_GOLD_EVENT,$home_team_name,$happen_time,$home_team_name,$home_score,$guest_score,$guest_team_name);
		}else{
			return sprintf(self::TEMPLATE_PUSH_GOLD_EVENT,$guest_team_name,$happen_time,$home_team_name,$home_score,$guest_score,$guest_team_name);
		}

	}

	private function _getPushGoldEventUsers($schedule_id){
		$redis = $this->getRedis();
		return $redis->sMembers('tiger_api:push_gold_event:notify_uids'.$schedule_id);
	}


	private function _isAreadyPushGoldEvent($schedule_id){
		$redis = $this->getRedis();
		return $redis->sContains('tiger_api:push_gold_event:aready_push_gold_event',$schedule_id);
	}

	private function _setAreadyPushGoldEvent($schedule_id){
		$redis = $this->getRedis();
		$redis->sAdd('tiger_api:push_gold_event:aready_push_gold_event',$schedule_id);
	}

	private function getRedis(){
		$redis = Factory::createAliRedisObj();
		if(!$redis){
			ApiLog('redis未连接','push_gold_event');exit;
		}
		$redis->select(0);
		return $redis;

	}

}