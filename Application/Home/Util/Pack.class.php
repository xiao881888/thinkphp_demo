<?php 
namespace Home\Util;
class Pack {
	/* ================= 打包 ================= */
	public static function packResponse($response, $errorCode, $token, $encryptType, $act = 0, $encryptKey = ''){
        /*if(!in_array($act,C('NOT_LOG_API'))){
            ApiLog('pack response body : ' .$errorCode.'==='. print_r($response, true), 'pack');
        }*/

		$body = json_encode($response);
		$encryptTypes = decToBinArray($encryptType);
		$gzBit = array_shift($encryptTypes);
		$encryptBit = array_pop($encryptTypes);
		
		if ($encryptBit == 1) {
			if ($act != C('ACT_USER_ENCRYPT_KEY')) {
				$encryptKey = self::_getUserEncryptKey($token);
			}
			$aesBit = $encryptTypes[4];
			$desBit = $encryptTypes[5];
			if ($aesBit == 1) {
				// $sign = decryptRsa($encryptKey[1]['sign']);
				$sign = $encryptKey[1]['sign'];
				$body = encryptAes($sign, $body);
			}
			if ($desBit == 1) {
				// $sign = decryptRsa($encryptKey[0]['sign']);
				// $signIv = decryptRsa($encryptKey[0]['sign_iv']);
				$sign = $encryptKey[0]['sign'];
				$signIv = $encryptKey[0]['sign_iv'];
				$body = encrypt3des($sign, $signIv, $body);
			}
		}
		
		if ($gzBit == 1) {
			$body = gzencode($body, 3);
		}
		$body = base64_encode($body);
		$length = strlen($body);
		$header = pack('a8', random_string(8));
		$header .= pack('n', $act);
		$header .= pack('N', $length);
		$header .= pack('N', $errorCode);
		$header .= pack('C', $encryptType);
		$header .= pack('a13', random_string(13));
		
		return ($response ? $header . $body : $header);
	}
    
    
    /* ================= 解包 ================= */
    
    public static function unpackRequest() {
        $packetInfo	 	= self::_unpack();
        $header		 	= $packetInfo['header'];
        $requestBody 	= $packetInfo['requestBody'];
        $encryptTypes 	= decToBinArray($header['encrypt_type']);
        
        $gzBit = array_shift($encryptTypes);
        $encryptBit = array_pop($encryptTypes);
        
        $requestBody = base64_decode($requestBody);
        if( $gzBit == 1 ) {
        	//FIXME 待测试
            $requestBody = gzdecode_for_tiger($requestBody, 3);
        }
        
		if ($encryptBit != 0) {
			$encryptKey = self::_getUserEncryptKey($header['token']);
			
			$aesBit = $encryptTypes[4];
			$desBit = $encryptTypes[5];
			
			if ($aesBit == 1) {
				// $sign = decryptRsa($encryptKey[1]['sign']);
				$sign = $encryptKey[1]['sign'];
				$requestBody = decryptAes($sign, $requestBody);
			}
			if ($desBit == 1) {
				// $sign = decryptRsa($encryptKey[0]['sign']);
				// $signIv = decryptRsa($encryptKey[0]['sign_iv']);
				
				$sign = $encryptKey[0]['sign'];
				$signIv = $encryptKey[0]['sign_iv'];

                /*if(!in_array($header['act'],C('NOT_LOG_API'))){
                    ApiLog('unpack request sig: ' . $sign . '===' . $signIv, 'pack');
                }*/

				
				$requestBody = decrypt3des($sign, $signIv, $requestBody);
			}
		}
        $requestBody = json_decode($requestBody, true);

        /*if(!in_array($header['act'],C('NOT_LOG_API'))){
            ApiLog('unpack request body decrypt : '.print_r($requestBody,true), 'pack');
        }*/
        

        
        return array_merge($header, $requestBody);
    }
    
    
    /*
     * 获取加密密钥
     */
    private function _getUserEncryptKey($token){
        $encryptKey = D('Session')->getEncryptKey($token);
        \AppException::ifNoExistThrowException($encryptKey, C('ERROR_CODE.SESSION_ERROR'));
        
        return json_decode($encryptKey ,true);
    }
    
    
    private function _unpack() {
        $fp = fopen ( 'php://input', 'rb' );
        
//         fseek($fp, 8, SEEK_CUR);
        fread($fp, 8);
        
		$version_arr = unpack('n*', fread($fp, 2)); // 接口版本
		$act_arr = unpack('n*', fread($fp, 2)); // 接口编号
		$length_arr = unpack('N*', fread($fp, 4)); // 包体长度
		$token_id_arr = unpack('a*', fread($fp, 32)); // Token
		$type_arr = unpack('C*', fread($fp, 1));
		
		$header = array();
        $header['sdk_version']	= $version_arr[1];
        $header['act'] 		= intval($act_arr[1]);
        $header['length'] 	= intval($length_arr[1]);
        $header['token']  = $token_id_arr[1];
        $header['encrypt_type'] = $type_arr[1];
        
        fseek( $fp, 15, SEEK_CUR);		//跳过保留填充位
        
        /*ApiLog('unpack request: '.print_r($header,true), 'pack');*/
        
        # 包体数据处理
        $requestBody = '';
        
        if($header['length']){
        	while(!feof($fp)){
        		$requestBody .= fread($fp, $header['length']);
        	}
        }

        fclose ($fp);
        return array(   'header'        => $header,
                        'requestBody'	=> $requestBody);
    }
    
}

?>