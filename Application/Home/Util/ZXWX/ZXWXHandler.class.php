<?php
class ZXWXHandler{
	private $pre_pay_url;
	private $encry_md5_key;
	private $mer_id;

	public function __construct($config){
		ApiLog('zxwx_h5 config:' . print_r($config, true), 'zxwx_h5');
		$this->pre_pay_url = $config['pre_pay_url'];
		$this->encry_md5_key = $config['encry_md5_key'];
		$this->mer_id = $config['mer_id'];
	}

	public function fetchPayUrl($request_data){
		$request_data['encoding'] = 'UTF-8';
		$request_data['txnType'] = '01';
		$request_data['txnSubType'] = '010133';
		$request_data['channelType'] = '6002';
		$request_data['payAccessType'] = '02';
		$request_data['merId'] = $this->mer_id;
		$request_data['backEndUrl'] = 'http://phone.api.tigercai.com/index.php/Home/ZXWXH5/notifyResult';
		$request_data['signMethod'] = '02';
		$request_data['signAture'] = $this->signData($request_data);
		
		$send_data = str_replace('+', '#', base64_encode(json_encode($request_data)));
		
		$post_data = 'sendData=' . urlencode($send_data);
		
		$resp = requestPage($this->pre_pay_url, $post_data, true);
		ApiLog('request post data:' . var_export($request_data, true), 'zxwx_h5');
		ApiLog('sendData=' . $send_data, 'zxwx_h5');
		if ($resp['code'] == '200' && substr($resp['page'], 0, 9) == 'sendData=') {
			$resp = str_replace('#', '+', substr($resp['page'], 9));
			$resp = json_decode(base64_decode($resp), true);
			
			ApiLog('resp arr:' . print_r($resp, true), 'zxwx_h5');
			$resp_sign = $resp['signAture'];
			unset($resp['signAture']);
			
			if ($resp_sign != $this->signData($resp)) {
				ApiLog('resp sign error:' . $resp_sign . '!=' . $this->signData($resp), 'zxwx_h5');
				return false;
			}
			
			return $resp;
		} else {
			return false;
		}
	}

	public function signData($sign_raw_data){
		ApiLog('md5 key:' . $this->encry_md5_key, 'zxwx_h5');
		$sign_data = $this->_filterEmptyData($sign_raw_data);
		return md5Arr($sign_data, $this->encry_md5_key);
	}

	private function _filterEmptyData($data){
		$filter_data = array();
		foreach ($data as $key => $val) {
			if (empty($val)) {
				continue;
			}
			$filter_data[$key] = $val;
		}
		return $filter_data;
	}

	public function buildParamsForSdk($request_data){
		$request_data['encoding'] = 'UTF-8';
		$request_data['txnType'] = '01';
		$request_data['txnSubType'] = '010132';
		$request_data['channelType'] = '6002';
		$request_data['payAccessType'] = '02';
		$request_data['merId'] = $this->mer_id;
		$request_data['signMethod'] = '02';
		$request_data['signAture'] = $this->signData($request_data);
		
		$send_data = str_replace('+', '#', base64_encode(json_encode($request_data)));
		
		$post_data = 'sendData=' . urlencode($send_data);
		
		$resp = requestPage($this->pre_pay_url, $post_data, true);
		ApiLog('request post data:' . var_export($request_data, true), 'zxwx_h5');
		ApiLog('sendData=' . $send_data, 'zxwx_h5');
		ApiLog('resp outside arr:' . print_r($resp, true), 'zxwx_h5');
		
		if ($resp['code'] == '200' && substr($resp['page'], 0, 9) == 'sendData=') {
			return $this->parseData(substr($resp['page'], 9));
		} else {
			return false;
		}
	}
	
	public function parseData($raw_string){
		$raw_data_string = str_replace('#', '+', $raw_string);
		$response_data = json_decode(base64_decode($raw_data_string), true);
			
		ApiLog('resp arr:' . print_r($response_data, true), 'zxwx_h5');
		$resp_sign = $response_data['signAture'];
		unset($response_data['signAture']);
			
		if ($resp_sign != $this->signData($response_data)) {
			ApiLog('resp sign error:' . $resp_sign . '!=' . $this->signData($response_data), 'zxwx_h5');
			return false;
		}
			
		return $response_data;
	}
}