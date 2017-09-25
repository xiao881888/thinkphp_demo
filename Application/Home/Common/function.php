<?php 
use Home\Util\Factory;
/**
 * @param string $len 长度
 * @param string $type 字串类型
 * @return string
 */
function getEmergencyFlag(){
	return PRINTOUT_MODE;
}

function random_string($len, $type='str') {
    if($type=='str') {
        $chars 	= 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    } elseif($type=='int') {
        $chars	= '0123456789';
    }

    $chars	= str_shuffle($chars);
    $str	= substr($chars, 0, $len);
    return $str;
}

function formatDate($date) {
    return date('Y-m-d', strtotime($date));
}

function betOptionAddV(array $betOptions) {
	$data = array();
	foreach ($betOptions as $betOption) {
		$data[] = 'v'.$betOption;
	}
	return $data;
}

function formatbetOption(array $betOptions) {
	$data = array();

	foreach ($betOptions as $betOption) {

		$data[] = substr($betOption,1);
	}
	return $data;
}

function betOptionsAddV(array $betOptionArray) {
	$data = array();
	foreach ($betOptionArray as $key=>$betOption) {
		$betOptionAddV = betOptionAddV($betOption);
		$data[$key] = $betOptionAddV;
	}
	return $data;
}

function pythonRange($num) {
	return range(0, $num-1);
}

function errorLog($msg, $data) {
	error_log(PHP_EOL."============== $msg ============".get_client_ip()."============".getCurrentTime().PHP_EOL, 3, './log.txt');
	error_log(var_export($data,true).PHP_EOL, 3, './log.txt');
}

function getSelectCount($playType) {
    if($playType>=21 && $playType<=33) {
        return C("SYXW_SELECT_COUNT.$playType");
    }
}

function isNumberGame($lotteryId) {
	return !in_array($lotteryId, C('JCZQ')) && !in_array($lotteryId, C('JCLQ'));
}

function isJCLottery($lotteryId){
	return in_array($lotteryId, C('JCZQ')) || in_array($lotteryId, C('JCLQ'));
}

function getRechargeSign($encryptId, $uid) {
    return md5($encryptId.$uid.C('RECHARGE_SALT'));
}

function emptyToStr($str) {
    return ( is_null($str) ? '' : $str );
}

function getCurrentTime() {
    return date('Y-m-d H:i:s');
}

function buildOrderSku($uid) {
	$randomStr = strtoupper(random_string(10));
	return 'TL'.date('ymdhis').$randomStr;
}

function combination($n, $m) {
    if($n < $m) {
        return 0;
    }
    $leftOperand = factorial($n);   // @TODO 提高效率的方法，C(7,10) 用 C(3,10) 实现
    $rightOperand = (factorial($m) * factorial($n - $m)) ;
    $result = round($leftOperand/$rightOperand);
    return intval($result);
}


function factorial($num) {
    return ($num < 1) ? 1 : (factorial($num - 1) * $num);
}

function getRechargeSku($uid, $channle_id = 0) {
//     return 'RECHARGE'.date('Ymd').random_string(6).$uid;
    if($channle_id==0){
    	return 'RC'.date('YmdHis').random_string(6).$uid;
    }elseif($channle_id==9){
    	return 'rchp'.date('YmdHis').strtolower(random_string(6)).$uid.$channle_id;
    }else{
    	return 'RC'.date('YmdHis').random_string(6).$uid.$channle_id;
    }
}

function encryptPassword($password, $salt) {
    $password = trim($password);
    return md5($password.$salt);
}

function requestByCurl($remote_server, $post_string) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $remote_server);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "");
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $data = curl_exec($ch);
    
    if (curl_errno($ch)) {
    	if($remote_server==C('PRINT_OUT_TICKET_URL')){
    		$mail_address = C('NOTICE_EMAILS').','.C('NOTICE_BEE_EMAILS');
    	}else{
    		$mail_address = C('NOTICE_EMAILS');
    	}

    	$error_subject = '请求url失败：'.$remote_server;
    	$error_content = 'url:'.$remote_server."<br>\n curl error:". curl_errno($ch).'==='.curl_error($ch);
    	sendMail($mail_address, $error_subject, $error_content);
    }
    curl_close($ch);
    return $data;
}

function ApiLog($msg,$file_name){
	error_log(date('m-d H:i:s').'    :     '.$msg."\n",3,__DIR__.'/../../Runtime/'.$file_name.'_'.date('Y-m-d_H').'.log');
}

function checkOverTicketLimitAmount($stakeCount,$multiple){
	$ticket_amount = $stakeCount*C('LOTTERY_PRICE')*$multiple;
	return ($ticket_amount>20000);
}

function sendMail($address, $title, $message) {

	import('Org.PHPMailer.phpmailer');
	$mail = new PHPMailer();
	$mail->IsSMTP();	// 设置PHPMailer使用SMTP服务器发送Email
	$mail->CharSet = 'UTF-8';
	if(is_array($address)){
		foreach ($address as $val){
			$mail->addAddress($val);
		}
	}else{
		$mail->AddAddress($address);// 添加收件人地址，可以多次使用来添加多个收件人
	}

	// 	$mail->Body = $message;	// 设置邮件正文
	$mail->msgHTML($message);
	$mail->From = C('MAIL_ADDRESS');	// 设置邮件头的From字段。
	$mail->FromName = '日志系统';	// 设置发件人名字
	$mail->Subject = $title;	// 设置邮件标题
	$mail->Host = C('MAIL_SMTP');	// 设置SMTP服务器。
	$mail->SMTPAuth = true;	// 设置为“需要验证”
	// 设置用户名和密码。
	$mail->Username = C('MAIL_LOGINNAME');
	$mail->Password = C('MAIL_PASSWORD');
	$result = $mail->Send();

	return $result;
}



/*
 * 将十进制的转化二进制并放入数组
 *
 * */
function decToBinArray($numerical){
    $binary = decbin($numerical);
    return str_split(str_pad($binary, 8, 0, STR_PAD_LEFT));
}

/*
 * 名称：RSA加密函数
 * 功能：完成RSA加密
 *
 * */
function encryptRsa($data) {
    $keypath = __ROOT__.'Identify/key/';
    import('@.Util.CryptRsa');
    $rsa = new CryptRsa($keypath);
    return $rsa->pubEncrypt($data);
}


/*
 * 名称：getPublicKey
 * 功能：获取公钥
 *
 * */
function getPublicKey() {
    //目前先直接写死，后面再定期自动生成
    $pubkey = 'MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBALyPkO5fdLGNr7HbFLAX9W4tMiMSuU1/oentUfraE/SCDrm9wEoISHIgJcSdC2q8k14gDuk3JhID42gFxsqOrbMCAwEAAQ==';
    return $pubkey;
}


/*
 * 名称：RSA解密函数
 * 功能：完成RSA解密
 *
 * */
function decryptRsa($encrypted) {
    $keypath = __ROOT__.'Identify/key/';
    import('@.Util.CryptRsa');
    $rsa = new CryptRsa($keypath);
    return $rsa->privDecrypt($encrypted);
}

/*
 * 名称：3DES加密函数
 * 功能：完成3DES加密
 *
 * */
function encrypt3des($key, $iv, $input) {
    import ( '@.Util.Crypt3Des' );
    $crypt      = new Crypt3Des ();
    $crypt->key = $key;
    $crypt->iv  = $iv;
    return $crypt->encrypt($input);
}

/*
 * 名称：3DES解密函数
 * 功能：完成3DES解密
 *
 * */
function decrypt3des($key, $iv, $input) {
    import ( '@.Util.Crypt3Des' );
    $crypt      = new Crypt3Des ();
    $crypt->key = $key;
    $crypt->iv  = $iv;
    return $crypt->decrypt($input);
}

/*
 * 名称：AES加密函数
 * 功能：完成AES加密
 *
 * */
function encryptAes($key, $input) {
    import ( '@.Util.CryptAes' );
    import ( '@.Util.CryptAesCtr' );
    return CryptAesCtr::encrypt($input, $key, 256);
}

/*
 * 名称：AES解密函数
 * 功能：完成AES解密
 *
 * */
function decryptAes($key, $input) {
    import ( '@.Util.CryptAes' );
    import ( '@.Util.CryptAesCtr' );

    return CryptAesCtr::decrypt($input, $key, 256);
}

if (!function_exists('array_column')) {
	function array_column($input, $column_key, $index_key = null)
	{
		if ($index_key !== null) {
			// Collect the keys
			$keys = array();
			$i = 0; // Counter for numerical keys when key does not exist

			foreach ($input as $row) {
				if (array_key_exists($index_key, $row)) {
					// Update counter for numerical keys
					if (is_numeric($row[$index_key]) || is_bool($row[$index_key])) {
						$i = max($i, (int) $row[$index_key] + 1);
					}

					// Get the key from a single column of the array
					$keys[] = $row[$index_key];
				} else {
					// The key does not exist, use numerical indexing
					$keys[] = $i++;
				}
			}
		}

		if ($column_key !== null) {
			// Collect the values
			$values = array();
			$i = 0; // Counter for removing keys

			foreach ($input as $row) {
				if (array_key_exists($column_key, $row)) {
					// Get the values from a single column of the input array
					$values[] = $row[$column_key];
					$i++;
				} elseif (isset($keys)) {
					// Values does not exist, also drop the key for it
					array_splice($keys, $i, 1);
				}
			}
		} else {
			// Get the full arrays
			$values = array_values($input);
		}

		if ($index_key !== null) {
			return array_combine($keys, $values);
		}

		return $values;
	}
}

function gzdecode_for_tiger($data) {
	$len = strlen($data);
	if ($len < 18 || strcmp(substr($data,0,2),"\x1f\x8b")) {
		return null;	// Not GZIP format (See RFC 1952)
	}
	$method = ord(substr($data,2,1));	// Compression method
	$flags	= ord(substr($data,3,1));	// Flags
	if ($flags & 31 != $flags) {
		// Reserved bits are set -- NOT ALLOWED by RFC 1952
		return null;
	}
	// NOTE: $mtime may be negative (PHP integer limitations)
	$mtime = unpack("V", substr($data,4,4));
	$mtime = $mtime[1];
	$xfl	= substr($data,8,1);
	$os	 = substr($data,8,1);
	$headerlen = 10;
	$extralen	= 0;
	$extra	 = "";
	if ($flags & 4) {
		// 2-byte length prefixed EXTRA data in header
		if ($len - $headerlen - 2 < 8) {
			return false;	 // Invalid format
		}
		$extralen = unpack("v",substr($data,8,2));
		$extralen = $extralen[1];
		if ($len - $headerlen - 2 - $extralen < 8) {
			return false;	 // Invalid format
		}
		$extra = substr($data,10,$extralen);
		$headerlen += 2 + $extralen;
	}
	$filenamelen = 0;
	$filename = "";
	if ($flags & 8) {
		// C-style string file NAME data in header
		if ($len - $headerlen - 1 < 8) {
			return false;	 // Invalid format
		}
		$filenamelen = strpos(substr($data,8+$extralen),chr(0));
		if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
			return false;	 // Invalid format
		}
		$filename = substr($data,$headerlen,$filenamelen);
		$headerlen += $filenamelen + 1;
	}
	$commentlen = 0;
	$comment = "";
	if ($flags & 16) {
		// C-style string COMMENT data in header
		if ($len - $headerlen - 1 < 8) {
			return false;	 // Invalid format
		}
		$commentlen = strpos(substr($data,8+$extralen+$filenamelen),chr(0));
		if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
			return false;	 // Invalid header format
		}
		$comment = substr($data,$headerlen,$commentlen);
		$headerlen += $commentlen + 1;
	}
	$headercrc = "";
	if ($flags & 1) {
		// 2-bytes (lowest order) of CRC32 on header present
		if ($len - $headerlen - 2 < 8) {
			return false;	 // Invalid format
		}
		$calccrc = crc32(substr($data,0,$headerlen)) & 0xffff;
		$headercrc = unpack("v", substr($data,$headerlen,2));
		$headercrc = $headercrc[1];
		if ($headercrc != $calccrc) {
			return false;	 // Bad header CRC
		}
		$headerlen += 2;
	}
	// GZIP FOOTER - These be negative due to PHP's limitations
	$datacrc = unpack("V",substr($data,-8,4));
	$datacrc = $datacrc[1];
	$isize = unpack("V",substr($data,-4));
	$isize = $isize[1];
	// Perform the decompression:
	$bodylen = $len-$headerlen-8;
	if ($bodylen < 1) {
		// This should never happen - IMPLEMENTATION BUG!
		return null;
	}
	$body = substr($data,$headerlen,$bodylen);
	$data = "";
	if ($bodylen > 0) {
		switch ($method) {
			case 8:
				// Currently the only supported compression method:
				$data = gzinflate($body);
				break;
			default:
				// Unknown compression method
				return false;
		}
	} else {
		// I'm not sure if zero-byte body content is allowed.
		// Allow it for now...	Do nothing...
	}
	// Verifiy decompressed size and CRC32:
	// NOTE: This may fail with large data sizes depending on how
	//		PHP's integer limitations affect strlen() since $isize
	//		may be negative for large sizes.
	if ($isize != strlen($data) || crc32($data) != $datacrc) {
		// Bad format!	Length or CRC doesn't match!
		return false;
	}
	return $data;
}


function isJcMix($lotteryId) {
	if($lotteryId==C('JCZQ.MIX') || $lotteryId==C('JCLQ.MIX')){
		return true;
	}
	return false;
// 	return in_array( $lotteryId, array(C('JCZQ.MIX'), C('JCLQ.MIX')) );
}

/**
 * 
 * @param  $lottery_id int
 * @param  $odds json串
 * @return Ambigous <multitype:unknown , number, string>
 */
function getFormatOdds($lottery_id, $odds){
	if(!$lottery_id || !$odds){
		return false;
	}

	$key_conf = array(
			C('JCZQ.NO_CONCEDE') 	=> 'betting_score_no_concede',
			C('JCZQ.CONCEDE')		=> 'betting_score_concede',
			C('JCZQ.SCORES')		=> 'betting_score_scores',
			C('JCZQ.BALLS')			=> 'betting_score_balls',
			C('JCZQ.HALF')			=> 'betting_score_half',
			C('JCZQ.MIX')			=>  0,
			C('JCLQ.NO_CONCEDE')	=>	'betting_score_no_concede',
			C('JCLQ.CONCEDE')		=>	'betting_score_concede',
			C('JCLQ.SFC')			=>	'betting_score_sfc',
			C('JCLQ.DXF')			=>	'betting_score_dxf',
			C('JCLQ.MIX')			=>	0,
	);
	 
	$odds = json_decode($odds, true);
	if(isJcMix($lottery_id)){//混合投注赔率
		$new_odds = array();
		foreach ($odds as $id=>$v){
			$key = $key_conf[$id];
			$formatOdds = formatOdds($v);
			if ($formatOdds) {
			    $new_odds[$key] = $formatOdds;
			}
		}
	}else{
		$new_odds = formatOdds($odds);
	}

	$result = array();
	$lottery_key = $key_conf[$lottery_id];
	if($lottery_key){
	    if ($new_odds) {
	        $result[$lottery_key] = $new_odds;
	    }
	}else{
		$result = $new_odds;
	}
	return $result;
}

function formatOdds($odds){
	foreach ($odds as $k=>$v){
		$low_k = strtolower($k);
		if($low_k == 'letpoint'){
			unset($odds[$k]);
			$k = 'let_point';
		}
		if($low_k == 'basepoint'){
			unset($odds[$k]);
			$k = 'base_point';
		}
		
		if($k == 'let_point' ){
			$odds[$k] = (float)$v;
		}elseif($k == 'base_point'){
			$odds[$k] = (float)$v;
		}else{
			$odds[$k] = number_format($v, 2, '.', '') ? number_format($v, 2, '.', '') : '';
		}
	}
	return ( $odds ? $odds : array() );
}

function isJc($lotteryId) {
	return in_array($lotteryId, C('JCZQ')) || in_array($lotteryId, C('JCLQ'));
}

function getTicktModel($lotteryId){
	if(in_array($lotteryId, C('JCZQ'))){
		return D('JczqTicket');
	} else if (in_array($lotteryId, C('JCLQ'))){
		return D('JclqTicket');
	} else {
		$lotteryType = C("LOTTERY_TYPE.$lotteryId");
		return D(ucfirst($lotteryType).'Ticket');
	}
}

function getCobetTicktModel($lotteryId){
    if(in_array($lotteryId, C('JCZQ'))){
        return D('CobetJczqTicket');
    } else if (in_array($lotteryId, C('JCLQ'))){
        return D('CobetJclqTicket');
    }
}

function buildSchemeSN($uid){
	$randomStr = strtoupper(random_string(10));
	return 'HM' . date('ymdhis') . $uid . $randomStr;
}

function getWeekName($week){
	$weekNames = array(
		1 => '周一',
		2 => '周二',
		3 => '周三',
		4 => '周四',
		5 => '周五',
		6 => '周六',
		7 => '周天',
	);
	return $weekNames[$week];
}

function isJczq($lottery_id){
	return in_array($lottery_id, C('JCZQ'));
}

function isJclq($lottery_id){
	return in_array($lottery_id, C('JCLQ'));
}

function isZcsfc($lottery_id){
    return in_array($lottery_id, array(TIGER_LOTTERY_ID_OF_SFC_14,TIGER_LOTTERY_ID_OF_SFC_9));
}

function getEndTime($lotteryId, $endTime, $type='str') {
    $redis  = Factory::createRedisObj();
    $key    = "lottery_{$lotteryId}_ahead_endtime";
    $aheadEndTime = $redis->get($key);
    if (!$aheadEndTime) {   // 防止缓存被意外删除
        $aheadEndTime = D('Lottery')->where(array('lottery_id'=>$lotteryId))
                                    ->getField('lottery_ahead_endtime');
        $redis->add($key, $aheadEndTime);
    }
    $deadLine   = is_numeric($endTime) ? $endTime : strtotime($endTime);
    $returnTime = $deadLine - $aheadEndTime;
    
    if ($type=='str') {
        return date('Y-m-d H:i:s', $returnTime);
    } else {
        return $returnTime;
    }
}

function array_search_value($key, $arr){
	foreach ($arr as $v) {
		$value = '';
		if (is_array($v)) {
			$value = array_search_value($key, $v);
		} else {
			$value = $arr[$key];
		}
		
		if($value){
			return $value;
		}
	}
	return false;
}

function buildUserAccountLogDesc($type,$params){
	$desc = C('USER_ACCOUNT_LOG_TYPE_DESC.'.$type);
	if($params){
		$desc .= ':'.json_encode($params);
	}
	return $desc;
}

function getSmsTempId($type){
    if ($type == C('SMS_TYPE.REGISTER')) {
        return C('SMS_TEMPLATE.REGISTER');
    } elseif ($type == C('SMS_TYPE.SET_LOGIN_PWD')) {
        return C('SMS_TEMPLATE.SET_LOGIN_PWD');
    } elseif ($type == C('SMS_TYPE.SET_PAYMENT_PWD')) {
        return C('SMS_TEMPLATE.SET_PAYMENT_PWD');
    } elseif ($type == C('SMS_TYPE.FIND_LOGIN_PWD')) {
        return C('SMS_TEMPLATE.FIND_LOGIN_PWD');
    } elseif ($type == C('SMS_TYPE.RECHARGE')) {
        return C('SMS_TEMPLATE.RECHARGE');
    } elseif ($type == C('SMS_TYPE.WITHDRAW')) {
        return C('SMS_TEMPLATE.WITHDRAW');
    } elseif ($type == C('SMS_TYPE.SET_FREE_PWD')) {
        return C('SMS_TEMPLATE.SET_FREE_PWD');
    } else {
        return false;
    }
}

function getBaiWanSmsTempId($type){
    if ($type == C('SMS_TYPE.REGISTER')) {
        return C('BAIWAN_SMS_TEMPLATE.REGISTER');
    } elseif ($type == C('SMS_TYPE.SET_LOGIN_PWD')) {
        return C('BAIWAN_SMS_TEMPLATE.SET_LOGIN_PWD');
    } elseif ($type == C('SMS_TYPE.SET_PAYMENT_PWD')) {
        return C('BAIWAN_SMS_TEMPLATE.SET_PAYMENT_PWD');
    } elseif ($type == C('SMS_TYPE.FIND_LOGIN_PWD')) {
        return C('BAIWAN_SMS_TEMPLATE.FIND_LOGIN_PWD');
    } elseif ($type == C('SMS_TYPE.RECHARGE')) {
        return C('BAIWAN_SMS_TEMPLATE.RECHARGE');
    } elseif ($type == C('SMS_TYPE.WITHDRAW')) {
        return C('BAIWAN_SMS_TEMPLATE.WITHDRAW');
    } elseif ($type == C('SMS_TYPE.SET_FREE_PWD')) {
        return C('BAIWAN_SMS_TEMPLATE.SET_FREE_PWD');
    } else {
        return false;
    }
}

function getNewSmsTempId($type){
    if ($type == C('SMS_TYPE.REGISTER')) {
        return C('NEW_SMS_TEMPLATE.REGISTER');
    } elseif ($type == C('SMS_TYPE.SET_LOGIN_PWD')) {
        return C('NEW_SMS_TEMPLATE.SET_LOGIN_PWD');
    } elseif ($type == C('SMS_TYPE.SET_PAYMENT_PWD')) {
        return C('NEW_SMS_TEMPLATE.SET_PAYMENT_PWD');
    } elseif ($type == C('SMS_TYPE.FIND_LOGIN_PWD')) {
        return C('NEW_SMS_TEMPLATE.FIND_LOGIN_PWD');
    } elseif ($type == C('SMS_TYPE.RECHARGE')) {
        return C('NEW_SMS_TEMPLATE.RECHARGE');
    } elseif ($type == C('SMS_TYPE.WITHDRAW')) {
        return C('NEW_SMS_TEMPLATE.WITHDRAW');
    } elseif ($type == C('SMS_TYPE.SET_FREE_PWD')) {
        return C('NEW_SMS_TEMPLATE.SET_FREE_PWD');
    } else {
        return false;
    }
}

function queryClientDesEncryptKeyBySessionCode($session_code){
	$encrypt_key = D('Session')->getEncryptKey($session_code);
	if (empty($encrypt_key)) {
		return false;
	}
	return json_decode($encrypt_key, true);
}

function buildUrl($path, $data){
	$domain = (is_ssl() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].'/';
	$url = $domain.'index.php?s=/'.$path;

	if (is_string($data)) {
		parse_str($data, $data);
	}

	if (!empty($data)) {
		$params = array();
		foreach ($data as $key => $val) {
			$params[] = '/'.urlencode($key).'/'.urlencode($val);
		}
	}

	$params = implode('/', $params);

	$url .= $params;

	return $url;
}

/*
 16-19 位卡号校验位采用 Luhm 校验方法计算：
1，将未带校验位的 15 位卡号从右依次编号 1 到 15，位于奇数位号上的数字乘以 2
2，将奇位乘积的个十位全部相加，再加上所有偶数位上的数字
3，将加法和加上校验位能被 10 整除。
*/
function validate_bankcard_by_luhm($s) {
	if(strlen($s)<16 && strlen($s)>19){
		return false;
	}
	$n = 0;
	for ($i = strlen($s); $i >= 1; $i--) {
		$index=$i-1;
		//偶数位
		if ($i % 2==0) {
			$n += $s{$index};
		} else {//奇数位
			$t = $s{$index} * 2;
			if ($t > 9) {
				$t = (int)($t/10)+ $t%10;
			}
			$n += $t;
		}
	}
	return ($n % 10) == 0;
}

function validation_filter_id_card($id_card){
	if(strlen($id_card)==18){
		return idcard_checksum18($id_card);
	}elseif((strlen($id_card)==15)){
		$id_card=idcard_15to18($id_card);
		return idcard_checksum18($id_card);
	}else{
		return false;
	}
}
// 计算身份证校验码，根据国家标准GB 11643-1999
function idcard_verify_number($idcard_base){
	if(strlen($idcard_base)!=17){
		return false;
	}
	//加权因子
	$factor=array(7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2);
	//校验码对应值
	$verify_number_list=array('1','0','X','9','8','7','6','5','4','3','2');
	$checksum=0;
	for($i=0;$i<strlen($idcard_base);$i++){
		$checksum += substr($idcard_base,$i,1) * $factor[$i];
	}
	$mod=$checksum % 11;
	$verify_number=$verify_number_list[$mod];
	return $verify_number;
}
// 将15位身份证升级到18位
function idcard_15to18($idcard){
	if(strlen($idcard)!=15){
		return false;
	}else{
		// 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
		if(array_search(substr($idcard,12,3),array('996','997','998','999')) !== false){
			$idcard=substr($idcard,0,6).'18'.substr($idcard,6,9);
		}else{
			$idcard=substr($idcard,0,6).'19'.substr($idcard,6,9);
		}
	}
	$idcard=$idcard.idcard_verify_number($idcard);
	return $idcard;
}
// 18位身份证校验码有效性检查
function idcard_checksum18($idcard){
	if(strlen($idcard)!=18){
		return false;
	}
	$idcard_base=substr($idcard,0,17);
	if(idcard_verify_number($idcard_base)!=strtoupper(substr($idcard,17,1))){
		return false;
	}else{
		return true;
	}
}

function getMaxMultipleByLotteryId($lottery_id){
	$ticai_lottery_ids = C('LOTTERY_CATEGORY.TIYU');
	if (isJCLottery($lottery_id) || in_array($lottery_id, $ticai_lottery_ids)) {
		return BET_JC_TICKET_TIME_LIMIT;
	}
	return BET_SZC_TICKET_TIME_LIMIT;
}

function notEmpty($str){
    if(empty($str)){
        $str = '';
    }
    return $str;
}

function curl_post($url, array $post = NULL, array $options = array()) {
    $defaults = array(
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_URL => $url,
        CURLOPT_FRESH_CONNECT => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FORBID_REUSE => 1,
        // CURLOPT_TIMEOUT => 60,
        CURLOPT_POSTFIELDS => http_build_query($post)
    );
    $ch = curl_init();

    curl_setopt_array($ch, ($options + $defaults));
    if( ! $result = curl_exec($ch)) {
        trigger_error(curl_error($ch));
    }

    curl_close($ch);
    return $result;
}

function getUserTelByUid($uid){
    $userInfo = D('User')->getUserInfo($uid);
    return $userInfo['user_telephone'];
}

function getTodayString(){
	return date('Y-m-d',time());
}


function getByCurl($target_url, $request_params = '', $default_timeout_sec = 15){
	$start_time_ms = microtime(true);
	$post_fields = is_array($request_params) ? http_build_query($request_params) : $request_params;
	$target_url .= '?'.http_build_query($request_params) ;
	$cp = curl_init();
	curl_setopt($cp, CURLOPT_URL, $target_url);
	curl_setopt($cp, CURLOPT_HEADER, false);
	curl_setopt($cp, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($cp, CURLOPT_TIMEOUT, $default_timeout_sec);

	$curl_result = curl_exec($cp);

	if (curl_errno($cp)) {
		ApiLog('curl error:'.$target_url.'===='.curl_errno($cp).'===='.curl_error(),'qt_curl');

	}

	curl_close($cp);
	return $curl_result;
}

function getCouponCondition($coupon_min_consume_price){
    if($coupon_min_consume_price <= 0){
        return '无金额门槛';
    }
    return sprintf('满%s使用',(int)$coupon_min_consume_price);
}

function requestUserIntegral($request_data = ''){
    $request_url = C('REQUEST_HOST').U('Integral/Index/index');
    $result = requestByCurl($request_url,$request_data);
    $result = json_decode($result,true);
    if($result['error_code'] !== 0){
        ApiLog('$request_data:'.print_r($request_data,true),'requestUserIntegral');
        ApiLog('$request_url:'.$request_url.';msg:'.$result['data'],'requestUserIntegral');
    }
    return $result;
}

function getUserCouponEndTime($coupon_info){
    if($coupon_info['coupon_valid_date_type'] == 0){
        return '2099-12-31 23:59:59';
    }elseif($coupon_info['coupon_valid_date_type'] == 1){
        return date('Y-m-d H:i:s',time()+$coupon_info['coupon_duration_time']);
    }elseif($coupon_info['coupon_valid_date_type'] == 2){
        return $coupon_info['coupon_sell_end_time'];
    }

}

function postByCurl($target_url, $post_data = '', $default_timeout_sec = 15)
{
        $start_time_ms = microtime(true);
        $post_fields = is_array($post_data) ? http_build_query($post_data) : $post_data;
        $cp = curl_init();
        curl_setopt($cp, CURLOPT_URL, $target_url);
        curl_setopt($cp, CURLOPT_HEADER, false);
        curl_setopt($cp, CURLOPT_POST, true);
        curl_setopt($cp, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($cp, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cp, CURLOPT_TIMEOUT, $default_timeout_sec);

        $curl_result = curl_exec($cp);

        if (curl_errno($cp)) {
            ApiLog('curl error:' . $target_url . '====' . curl_errno($cp) . '====' . curl_error(), 'qt_curl');

        }

        curl_close($cp);
        return $curl_result;
}


