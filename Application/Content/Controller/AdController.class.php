<?php
namespace Content\Controller;
use Think\Controller;

class AdController extends Controller{

	public function isExist(){
		$idfa = $_POST['idfa'];

		$where = array(
			'iphone_adfa' => substr(strtoupper($idfa), 0, 35)
			);

		$is_exist = M('Device')->where($where)->find();

		$result = array(
			$idfa => intval($is_exist)
			);

		echo json_encode($result);
	}

	public function preActive(){
		$mac 	= I('mac');
		$idfa 	= I('idfa');
		$channel  = I('channel');
		$openudid = I('openudid');
		$callback = I('callback');
		$client_ip = I('client_ip');
		$client_ua = I('client_ua');

		if ($channel == 'kuaiyong' || $channel == 'aisi2' || $channel == 'shike') {
			$type = 'active';
		} else {
			$type = 'register';
		}

		if (empty($mac) && empty($idfa)) {
			$this->error('缺少必要参数mac、idfa');
		}

		$mac = $this->formatMac($mac);
		$idfa = $this->formatIdfa($idfa);

		if (!empty($idfa)) {
			$exist_record = $this->findDeviceRecord($mac, $idfa, $openudid);

		} else {
			$exist_record = $this->findDeviceRecordByIpUa($client_ip, $client_ua);
		}

		if ($exist_record) {
			$this->error('该设备已被激活');
		}

		$insert_record_result = $this->insertRecord($mac, $idfa, $channel, $openudid, $callback, $type, $client_ip, $client_ua);

		if (!$insert_record_result) {
			$this->error('操作失败，请重试');
		}

		$this->success('操作成功');
	}

	public function activeDevice($idfa, $mac, $device_id, $type){
		$mac 	= $this->formatMac($mac);
		$idfa 	= $this->formatIdfa($idfa);

		$ip = get_client_ip(0, true);

		//因为虎彩服务端保存的idfa只有前35位这个bug，所以只能拿前35位来做比较。
		if ($type == 'active') {
			$record = $this->findDeviceRecord($mac, $idfa);
			if (empty($record)) {
				$record = $this->findDeviceRecordByIp($ip);
			}
		} else {
			$record = $this->findDeviceRecord2($mac, $idfa);
		}		

		if ($record) {
			if ($record['record_type'] != $type) {
				return;
			}

			if (!empty($record['record_callback']) && $record['record_callback_status'] == 0) {
				$callback_resp = file_get_contents($record['record_callback']);
				$callback_resp = json_decode($callback_resp, true);
				//2345有固定回调格式无法按照我方格式来调整，所以我们特殊处理，兼容他们的格式
				if ($callback_resp['success'] == 'true' || $callback_resp['message'] == 'success' || $callback_resp['msg'] == 'Success') {
					$data = array(
						'record_id' => $record['record_id'],
						'device_id' => $device_id,
						'record_callback_status' => 1,
						'record_modifytime' => date('Y-m-d H:i:s')
						);

					D('PreActiveRecord')->save($data);
				}
			}
		}
	}

	public function testActive(){
		set_time_limit(0);
		$map = array(
			'record_callback_status' => 1
			);
		$list = D('PreActiveRecord')->where($map)->select();
		foreach ($list as $record) {
			// dump($record);
			$device_map = array(
				'iphone_adfa' => $record['record_device_idfa_2']
				);
			$device = D('Device')->where($device_map)->find();
			// dump($device);
			$data = array(
				'record_id' => $record['record_id'],
				'device_id' => $device['device_id']
				);
			// dump($data);continue;
			$save = D('PreActiveRecord')->save($data);
			if ($save === false) {
				dump($record);
			}
		}

		echo 'ok';
	}

	private function findDeviceRecord($mac, $idfa, $openudid=''){
		$map = array();
		// $map['record_device_mac'] = $mac;
		$map['record_device_idfa'] = $idfa;
		if ($openudid) {
			$map['record_device_openudid'] = $openudid;
		}
		
		$record = D('PreActiveRecord')->where($map)->find();

		return $record;
	}

	//因为虎彩服务端保存的idfa只有前35位这个bug，所以只能拿前35位来做比较。
	private function findDeviceRecord2($mac, $idfa, $openudid=''){
		$map = array();
		// $map['record_device_mac'] = $mac;
		$map['record_device_idfa_2'] = $idfa;
		if ($openudid) {
			$map['record_device_openudid'] = $openudid;
		}
		
		$record = D('PreActiveRecord')->where($map)->find();

		return $record;
	}

	//如果idfa查询不到，使用ip匹配
	private function findDeviceRecordByIp($ip){
		$map = array();
		$map['record_client_ip'] = $ip;
		$map['record_callback_status'] = 0;
		$map['record_createtime'] = array('gt', date('Y-m-d H:i:s', time()-86400*7));
		$record = D('PreActiveRecord')->where($map)->find();

		return $record;
	}

	private function findDeviceRecordByIpUa($ip, $ua){
		$map = array();
		$map['record_client_ip'] = $ip;
		$map['record_client_ua'] = $ua;
		$map['record_callback_status'] = 0;
		$map['record_createtime'] = array('gt', date('Y-m-d H:i:s', time()-86400*7));
		$record = D('PreActiveRecord')->where($map)->find();

		return $record;
	}

	private function insertRecord($mac, $idfa, $channel, $openudid, $callback, $type, $ip, $ua){
		$record_data = array(
			'record_device_platform' 	=> 2,
			'record_device_mac' 		=> $mac,
			'record_device_idfa' 		=> $idfa,
			'record_device_idfa_2'		=> substr($idfa, 0, 35),
			'record_device_openudid' 	=> $openudid,
			'record_client_ip'			=> $ip, 
			'record_client_ua'			=> $ua,
			'record_channel' 			=> $channel,
			'record_callback' 			=> $callback,
			'record_callback_status' 	=> 0,
			'record_type'				=> $type,
			'record_createtime' 		=> date('Y-m-d H:i:s')
			);

		$insert_id = D('PreActiveRecord')->add($record_data);

		return $insert_id;
	}

	private function formatMac($mac){
		$mac = strtolower($mac);
		$mac = str_replace(':', '', $mac);
		
		return $mac;
	}

	private function formatIdfa($idfa){
		$idfa = strtolower($idfa);
		$idfa = str_replace(':', '', $idfa);
		
		return $idfa;
	}

	protected function error($msg){
		$resp = array(
			'success' => 'false',
			'message' => $msg
			);

		echo json_encode($resp);
		exit;
		return;
	}

	protected function success($msg){
		$resp = array(
			'success' => 'true',
			'message' => $msg
			);

		echo json_encode($resp);
		exit;
		return;
	}
}