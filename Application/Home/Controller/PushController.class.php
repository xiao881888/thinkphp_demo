<?php

namespace Home\Controller;
use Home\Controller\GlobalController;

use \Push\Request\V20150827 as Push;

/*
 * ios端
 */
class PushController extends GlobalController {

	public function savePushDeviceConfig($api) {


		$session_info 		= D('Session')->getSessionInfo($api->session);
		
		$app_info 			= array();
		if($api->os==OS_OF_ANDROID){
			$app_info['app_package_name']
                = (property_exists($api, 'bundleId') && !empty($api->bundleId)) ? $api->bundleId : 'co.sihe.tigerlottery';
		}else{
			$app_info['app_package_name']   = (property_exists($api, 'bundleId') && !empty($api->bundleId)) ? $api->bundleId : 'com.tigercai.TigerLottery';
		}


        $app_info['app_id']    = getRequestAppId($app_info['app_package_name']);
		$app_info['app_app_version']    = $api->sdk_version;
		$app_info['app_platform']		= $api->os;
		$app_info['channel_id']			= (property_exists($api, 'channel_id') && !empty($api->channel_id)) ? $api->channel_id : '0';
		$saveDeviceResult  	= D('PushDevice')->savePushDeviceInfo($api->device_token, $session_info, $app_info);

		//save push config
		//中奖提醒，购买提醒
		
		\AppException::ifNoExistThrowException($saveDeviceResult, C('ERROR_CODE.DATABASE_ERROR'));
		return array(   'result' => '',
						'code'   => C('ERROR_CODE.SUCCESS'));
	}

	public function pushFollowFailedMessage($order_id, $issue_info,$fail_order_id){
		$order_info = D('Order')->getOrderInfo($order_id);
		
		$push_device_info = $this->_getPushDeviceInfoByUid($order_info['uid']);
		$device_token = $push_device_info['pd_device_token'];

		$map['lottery_id'] = $order_info['lottery_id'];
		$lotteryInfo = M('Lottery')->where($map)->find();
		
		$message_template = $this->_getPushMessageTemplate(C('IOS_PUSH_TYPE.FAIL_TO_FOLLOW_TICKET'), $order_info);
		$replace_key = array('$1','$2');
		$replace_value = array(
				$lotteryInfo['lottery_name'],
				$issue_info['issue_no']
		);
		
		$push_message = str_replace($replace_key, $replace_value, $message_template);
		
		$pushResult = false;
		if(!empty($push_message) && !empty($device_token)){
			$custom_values['order_id'] = $fail_order_id;
			$pushResult  = $this->_push($push_message, $push_device_info, C('PUSH_NEXT_ACTION_TYPE.ORDER_DETAILE_PAGE'), $custom_values);
			$pushResult = $pushResult['success'] > 0 ? true : false;
		}
		return $pushResult;
	}
	
	public function pushOrderMessage() {
	    $orderId     = $_REQUEST['orderId'];
	    $type        = $_REQUEST['type'];
	    
	    
	    if (!$orderId || !$type) {

	        $this->ajaxReturn(array('code'=>1), 'JSON');
	    }
	    
	    $order_info 	 = D('Order')->getOrderInfo($orderId);
		$push_device_info = $this->_getPushDeviceInfoByUid($order_info['uid']);
		$device_token = $push_device_info['pd_device_token'];

		$push_message 	 = $this->_getPushMessageTemplate($type,$order_info);
		
		if(!empty($push_message) && !empty($device_token)){
			$custom_values['order_id'] = $orderId;
			$custom_values['lottery_id'] = $order_info['lottery_id'];
				
			$pushResult  = $this->_push($push_message, $push_device_info, C('PUSH_NEXT_ACTION_TYPE.ORDER_DETAILE_PAGE'), $custom_values);
			$pushResult = $pushResult['success'] > 0 ? true : false;
		}
		
		$code 		 = $pushResult ? 0 : 1;
		$response 	 = array('code'=>$code);
		$this->ajaxReturn($response, 'JSON');
	}
	
	public function pushOrderNotice($request_params) {
		$orderId     = (string)$request_params['order_id'];
		$type        = $request_params['type'];

        $request_data['act_type'] = 5;
        $request_data['order_id'] = $orderId;
        $request_data['type'] = $type;
		$response_data = requestByCurl(C('REQUEST_HOST').'/Home/PushApi/index',$request_data);
		/*ApiLog('push post:'.$orderId.'==='.$type, 'push');
		if (!$orderId || !$type) {
			ApiLog('ajaxReturn push post:'.$orderId.'==='.$type, 'push');
			$this->ajaxReturn(array('code'=>1), 'JSON');
		}
		 
		$order_info 	 = $request_params['order_info'];
		$push_device_info = $this->_getPushDeviceInfoByUid($order_info['uid']);
		$device_token = $push_device_info['pd_device_token'];
		ApiLog('deviceToken:'.$device_token, 'push');
	
		$push_message 	 = $this->_getPushMessageTemplate($type,$order_info);
		
		ApiLog('$push_message:'.$push_message, 'push');
		
		if(!empty($push_message) && !empty($device_token)){
			$custom_values['order_id'] = $orderId;
			$custom_values['lottery_id'] = $order_info['lottery_id'];
	
			$pushResult  = $this->_push($push_message, $push_device_info, C('PUSH_NEXT_ACTION_TYPE.ORDER_DETAILE_PAGE'), $custom_values);
			$pushResult = $pushResult['success'] > 0 ? true : false;
			ApiLog('pushResult:'.$pushResult.'==='.$push_message, 'push');
		}
	
		$code 		 = $pushResult ? 0 : 1;
		$response 	 = array('code'=>$code);
		$this->ajaxReturn($response, 'JSON');*/
	}

	public function pushActivityMessage(){
		set_time_limit(0);
		ignore_user_abort(true);

		$message 	= I('msg');
		$uids 		= I('uid');
		$type 		= I('type');
		$paramers   = I('paramers');
		if (!empty($paramers)) {
			$paramers = json_decode($paramers, true);
		}

		if (!empty($message) && !empty($uids)) {
			if ($uids === 'all') {
			//TUDO 容易沾满内存
				$push_device_infos = M('PushDevice')->select();	
			} else {
				$uids = is_array($uids) ? $uids : explode(',', $uids);
				$push_device_info_map = array('uid' => array('IN', $uids));
				$push_device_infos    = M('PushDevice')->where($push_device_info_map)->select();
			}

			$result = array();
			$result['success'] = $result['faile'] = 0;

			$push_deivce_infos_arr = array_chunk($push_device_infos, 10);	
			foreach ($push_deivce_infos_arr as $push_device_infos) {
				$result_tmp = $this->_push($message, $push_device_infos, $type, $paramers);
				$result['success'] += $result_tmp['success'];
				$result['faile']   += $result_tmp['faile'];
			}

			$error_code = 0;
			$error_msg  = "发送完成，成功{$result['success']}条，失败{$result['faile']}条";

		} else {
			$error_code = '1';
			$error_msg  = 'message, uid不能为空';
		}

		$resp = array();
		$resp['code'] = $error_code;
		$resp['msg']  = $error_msg;

		$this->ajaxReturn($resp);
	}
	
	
	private function _getPushDeviceInfoByUid($uid) {
		$push_device_info = D('PushDevice')->getDeviceInfoByUid($uid);
		if(empty($push_device_info['pd_device_token'])){
			$device_id = D('Session')->getDeviceIdByUid($uid);
			$push_device_info = D('PushDevice')->getDeviceInfoByDeviceId($device_id);
		}
		return $push_device_info;
	}
	
	
	private function _getPushMessageTemplate($type, $order_info) {
		$uid = $order_info['uid'];
		//根据uid获取push config 看是否推送
		
		if ($type==1) {

			if($order_info['order_winnings_status'] == 1 && $order_info['order_offical_plus_amount'] > 0){
				$message_template = C('PUSH_MESSAGE_TEMPLATE.PLUS_WIN_PRIZE');
				$map['lottery_id'] = $order_info['lottery_id'];
				$lotteryInfo = M('Lottery')->where($map)->find();
				
				$replace_key = array('$1','$2','$3','$4');
				$replace_value = array(
						$lotteryInfo['lottery_name'],
						bcsub($order_info['order_winnings_bonus'], $order_info['order_offical_plus_amount'], 2),
						$order_info['order_offical_plus_amount'],
						$order_info['order_winnings_bonus']
				);
				$push_message = str_replace($replace_key, $replace_value, $message_template);
				return $push_message;
			}else{
				return C('PUSH_MESSAGE_TEMPLATE.WIN_PRIZE');
			}
		} elseif ($type==2) {
			return C('PUSH_MESSAGE_TEMPLATE.FAIL_TO_BUY_TICKET');
		} elseif ($type==3) {
			return C('PUSH_MESSAGE_TEMPLATE.FAIL_TO_FOLLOW_TICKET');
		} elseif ($type==4) {
			return C('PUSH_MESSAGE_TEMPLATE.FAIL_TO_TICKET_PRINTOUT');
		}
	}
	
	public function testPush(){
		$message = "test";
		$deviceToken = "8454b4e0859db9c552a50fc149d9275197ce7e4c8a440c173d3e1cceea11ffaf";
		$fp = $this->_connectAPNS();
		$body = array(
				'aps' => array(
						'alert' => array("body" => $message, "action-loc-key" => "阅读"),
						'badge' => 1,
						'sound' => 'default'
				),
				
				 );
		
		$payload = json_encode($body);
		$json_size = strlen($payload);
		$msg = chr(0) . pack('n', 32) . pack('H*', trim($deviceToken)) . pack('n', $json_size) . $payload;
		$msg_size = strlen($msg);
		$result = fwrite($fp, $msg, $msg_size);
		echo "result:";
		print_r($result);
		
		fclose($fp);
	}
	
	private function _push($message, $push_device_infos, $event_type=0, $custom_keys=array()) {
		if (array_key_exists('pd_app_package', $push_device_infos)) {
			$push_device_infos = array($push_device_infos);
		}

		$devices = $this->_groupDeviceInfosByPackageName($push_device_infos);

		$success = $faile = 0;
		$android_device_list = array();
		$ios_device_list = array();
		foreach ($devices as $package_name => $device_arr) {
			foreach($device_arr as $device_info){
				if($device_info['pd_app_platform']==OS_OF_ANDROID){
					$android_device_list[] = $device_info;
				}elseif($device_info['pd_app_platform']==OS_OF_IOS){
					$ios_device_list[] = $device_info;
				}else{
					
				}
			}
			if(!empty($android_device_list)){
				foreach($android_device_list as $android_device){
					$android_res = $this->_pushAndroidDevice('alipush', $package_name, $android_device, $message, $event_type, $custom_keys);
					if($android_res){
						$success++;
					} else {
						$faile++;
					}
				}
			}

			if(!empty($ios_device_list)){
				$push_config = C('IOS_PUSH_CONFIG');
				$push_config = $push_config[$package_name];
				
				$fp = $this->_connectAPNS($push_config);
				
				$body = array(
						'aps' => array(
								'alert' => array("body" => $message, "action-loc-key" => "阅读"),
								'badge' => 1,
								'sound' => 'default'
						)
				);
				
				$body['server'] 				= $custom_keys;
				$body['server']['event_type'] 	= $event_type;
				$payload 						= json_encode($body);

				$json_size = strlen($payload);
				foreach ($ios_device_list as $device) {
					$device_push_token = trim($device['pd_device_token']);
					$msg 	  = chr(0) . pack('n', 32) . pack('H*', $device_push_token) . pack('n', $json_size) . $payload;
					$msg_size = strlen($msg);
					$result   = fwrite($fp, $msg, $msg_size);
					// $result_read = fread($fp, 6);

					if ($result !== false) {
						$success++;
					} else {
						$faile++;
					}
				}
				fclose($fp);
			}
		}
		
		$result = array();
		$result['success'] = $success;
		$result['faile']   = $faile;

		return $result;
	}

	private function _connectAPNS($push_config){
		$passphrase = $push_config['IOS_PASSPHRASE'];
		$apns_host 	= $push_config['IOS_APNS_HOST'];
		$apns_cert 	= $push_config['IOS_APNS_CERT'];
		

		$apns_port = $push_config['IOS_APNS_PORT'];
		$ctx = stream_context_create();
		stream_context_set_option($ctx, 'ssl', 'local_cert', $apns_cert);
		stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
		$result = stream_socket_client('ssl://'.$apns_host.':'.$apns_port, $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
		return $result;
	}

	private function _groupDeviceInfosByPackageName($device_infos){
		$devices = array();
		foreach ($device_infos as $device) {
			$devices[$device['pd_app_package']][] = $device;
		}

		return $devices;
	}
	
	public function test(){
		$accessKeyId = "n7fZXCA25Uyr5N25";
		$accessSecret = "QGxzgWi3vOd7R8vdTOnd9je2A9iesP";
		$appKey = '23383332';
		
		$iClientProfile = \DefaultProfile::getProfile("cn-hangzhou", $accessKeyId, $accessSecret);
		$client = new \DefaultAcsClient($iClientProfile);
		
		// 示例: 调用 PushNoticeToAndroidRequest API
		$request = new Push\PushNoticeToAndroidRequest();
		$request->setAppKey($appKey);
		$request->setTarget("device");
		$request->setTargetValue("46d142e2bb4040428f9613530ee40f48");
		$request->setTitle("老虎彩票");
		$request->setSummary("PushMessageToAndroid from OpenAPI by PHP SDK!");
		
		$response = $client->getAcsResponse($request);
		print_r("\r\n");
		print_r($response);
	}
	
	private function _pushAndroidDevice($type, $package_name, $device_arr,$message,$event_type=0,$custom_keys=array()){
		if($type=='alipush'){
			return $this->_pushByAliPush($device_arr, $package_name, $message, $event_type, $custom_keys);
		}
	}

	private function _pushByAliPush($device_arr, $package_name, $message, $event_type = 0, $custom_keys = array()){
		$push_info = C('ANDROID_PUSH_CONFIG');
		$accessKeyId = $push_info[$package_name]['ACCESS_KEY_ID'];
		$accessSecret = $push_info[$package_name]['ACCESS_SECRET'];
		$appKey = $push_info[$package_name]['APP_KEY'];

		$iClientProfile = \DefaultProfile::getProfile("cn-hangzhou", $accessKeyId, $accessSecret);
		$client = new \DefaultAcsClient($iClientProfile);
		
		// 示例: 调用 PushNoticeToAndroidRequest API
		$request = new Push\PushNoticeToAndroidRequest();
		
		$request->setAppKey($appKey);
		$request->setTarget("device");
		$request->setTargetValue(trim($device_arr['pd_device_token']));
		$request->setTitle("老虎彩票");
		$request->setSummary($message);
		
		$body['server'] 				= $custom_keys;
		$body['server']['event_type'] 	= $event_type;
		
		$request->setAndroidExtParameters(json_encode($body)); // 设定android类型设备通知的扩展属性

		$response = $client->getAcsResponse($request);
		if($response->ResponseId){
			return true;
		}
		return false;
	}
		
}