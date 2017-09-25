<?php 
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
		ApiLog('curl error:'.$target_url.'===='.curl_errno($cp).'===='.curl_error(),'vs');

	}

	curl_close($cp);
	return $curl_result;
}

function postByCurl($target_url, $post_data = '', $default_timeout_sec = 15){
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
		ApiLog('curl error:'.$target_url.'===='.curl_errno($cp).'===='.curl_error(),'qt_curl');
		
	}

	curl_close($cp);
	return $curl_result;
}


function ApiLog($msg,$file_name){
	error_log(date('m-d H:i:s').'    :     '.$msg."\n",3,__DIR__.'/../../Runtime/'.$file_name.'_'.date('Y-m-d').'.log');
}

function getCurrentTime(){
    return date('Y-m-d H:i:s',time());
}

function getTodayString(){
	return date('Ymd',time());
}

function isJc($lotteryId)
{
    return in_array($lotteryId, C('JCZQ')) || in_array($lotteryId, C('JCLQ'));
}