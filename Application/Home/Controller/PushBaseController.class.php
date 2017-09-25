<?php

namespace Home\Controller;

use Home\Util\Factory;
use Think\Exception;

/**
 * 统一推送接口
 * @package Home\Controller
 */
class PushBaseController extends GlobalController
{

	protected $redis;

	public function __construct(){
		if(!$this->redis){
			$this->redis = $this->getRedis();
		}
		parent::__construct();
	}

	protected function push($uidList,$push_type,$send_msg,$type = 0, $paramers = array(),$is_hurry = 0,$app_id = 0){

		if(!isset($push_type)){
			throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.PUSH_TYPE_IS_NULL'),C('UNITE_PUSH_EXCEPTION_CODE.PUSH_TYPE_IS_NULL'));
		}

		if(empty($uidList)){
			throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.UID_IS_NULL'),C('UNITE_PUSH_EXCEPTION_CODE.UID_IS_NULL'));
		}

		if(empty($send_msg)){
			throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.MESSAGE_IS_NULL'),C('UNITE_PUSH_EXCEPTION_CODE.MESSAGE_IS_NULL'));
		}


		$push_device_List = D('PushDevice')->getUnitePushDeviceList($uidList,$push_type,$app_id);
		$result = $this->_unitePush($send_msg, $push_device_List,$type,$paramers,$is_hurry);
		$error_msg = "发送完成，成功{$result['success']}条，失败{$result['fail']}条;";
		$error_msg .= $result['msg'];
		return $error_msg;

	}

	private function getRedis(){
		$redis = Factory::createAliRedisObj();
		if(!$redis){
			throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.REDIS_NOT_COMMIT'),C('UNITE_PUSH_EXCEPTION_CODE.REDIS_NOT_COMMIT'));
		}
		$redis->select(0);
		return $redis;

	}


	/**
	 * 统一推送
	 * @param $message 推送信息
	 * @param $push_device_infos 所有的推送设备列表
	 * @param int $event_type 附加信息
	 * @param array $custom_keys 附加信息
	 * @return array
	 */
	private function _unitePush($message, $push_device_List, $event_type = 0, $custom_keys = array(),$is_hurry = 0)
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
				$android_res = $this->_pushDevice(OS_OF_ANDROID, C('PUSH_BRAND.ALI_PUSH'), $new_push_device_List['android'], $message, $event_type, $custom_keys,$is_hurry);
			}

			//推送所有IOS设备
			if (!empty($new_push_device_List['ios'])) {
				$ios_res = $this->_pushDevice(OS_OF_IOS, C('PUSH_BRAND.IOS_PUSH'), $new_push_device_List['ios'], $message, $event_type, $custom_keys,$is_hurry);
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
				$fail_count = count($push_device_List);
				$return_msg = 'android: ' . $android_res['msg'] . ';ios:' . $ios_res['msg'];
                throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.PUSH_API_IS_FAIL').';$fail_count:'.$fail_count.';$return_msg:'.$return_msg,C('UNITE_PUSH_EXCEPTION_CODE.PUSH_API_IS_FAIL'));
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
	private function _pushDevice($deviceType, $type, $device_arr, $message, $event_type = 0, $custom_keys = array(),$is_hurry = 0)
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
        $post['hurry'] = $is_hurry;

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

}