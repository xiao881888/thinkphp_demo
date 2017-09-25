<?php

namespace Home\Controller;

use Think\Controller;

class ServiceController extends Controller{
	
	/*
	 * 注册设备及3des密钥，返回session
	 */
	public function getClientId($api){
		$encryptKey = $api->key;
		$publicKey = getPublicKey();
		
		// 公钥无效时
		if (!$api->public_key || $api->public_key != $publicKey) {
			return array(
					'result' => array(
							'public_key' => $publicKey 
					),
					'code' => C('ERROR_CODE.PUBLIC_KEY_INVALID') 
			);
		}
		// 用RSA密钥解密3des密钥及iv
		$sign = decryptRsa($encryptKey[0]['sign']);
		$sign_iv = decryptRsa($encryptKey[0]['sign_iv']);
		if (empty($sign) || empty($sign_iv)) {
			return array(
					'result' => '',
					'code' => C('ERROR_CODE.PRIVATE_KEY_INVALID') 
			);
		}
		// 用3des密钥进行3des解密
		$decrypted_body = decrypt3des($sign, $sign_iv, base64_decode($api->body));
		$body_data = json_decode($decrypted_body, true);
		
		if (empty($body_data)) {
			return array(
					'result' => '',
					'code' => C('ERROR_CODE.PRIVATE_KEY_INVALID') 
			);
		}

		$device_info = $body_data['device_info'];
		$device_id = D('Device')->addDeviceInfo($device_info,$api->os);
		\AppException::ifNoExistThrowException($device_id, C('ERROR_CODE.DATABASE_ERROR'));
		
		$client_key_info[0]['sign'] = $sign;
		$client_key_info[0]['sign_iv'] = $sign_iv;
		
		$token = D('Session')->saveToken($device_id, $client_key_info);
		\AppException::ifNoExistThrowException($token, C('ERROR_CODE.DATABASE_ERROR'));
		
		$address = C('TIGER_IP_ADDRESS')?C('TIGER_IP_ADDRESS'):'';
		
		//激活设备
		if ($api->os == OS_OF_IOS) {
			$device_info['device_id'] = $device_id;
			$this->activeDevice($device_info);
		}

		return array(
				'result' => array(
						'token' => $token,
						'api_address'=>$address 
				),
				'code' => C('ERROR_CODE.SUCCESS') 
		);
	}

	private function activeDevice($device_info){
		$device = array();
		$device['idfa'] = $device_info['iphone_adfa'];
		$device['mac']  = $device_info['mac'];
		$device['device_id'] = $device_info['device_id'];
		$device['type'] = 'active';


		R('Content/Ad/activeDevice', $device);
	}
}