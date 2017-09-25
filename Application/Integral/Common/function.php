<?php
function ApiLog($msg,$file_name){
    error_log(date('m-d H:i:s').'    :     '.$msg."\n",3,__DIR__.'/../../Runtime/'.$file_name.'_'.date('Y-m-d').'.log');
}

function getRedis(){
    $redis = \Integral\Util\Factory::createAliRedisObj();
    $redis->select(3);
    return $redis;
}

function emptyToStr($str) {
    return ( is_null($str) ? '' : $str );
}

function getCurrentTime() {
    return date('Y-m-d H:i:s');
}

function getCurrentDate() {
    return date('Y-m-d',time());
}

function getIntegralBySignCount($sign_count){
    if($sign_count >= 6){
        $sign_count = 6;
    }
    return C('SIGN_COUNT_GET_INTEGRAL.'.$sign_count);
}


/**
 * 全概率计算
 * @param array $p array(153=>60,154=>26,155=>14)
 * @return string 返回上面数组的key
 */
function random_draw($data){
    $total_data = array();
    $max = array_sum($data);
    foreach ($data as $k=>$v) {
        $v = $v / $max * 100;
        for ($i=0; $i<$v; $i++){
            $total_data[] = $k;
        }
    }
    return $total_data[mt_rand(0,count($total_data)-1)];
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

function getMsgQueueReceiveMsgRedis(){
    return C('REDIS_KEY').'receive_msg';
}

function decryptRsa($encrypted) {
    $keypath = __ROOT__.'Identify/key/';
    import('Home.Util.CryptRsa');
    $rsa = new CryptRsa($keypath);
    return $rsa->privDecrypt($encrypted);
}

function hiddenMobile($mobile)
{
    return substr($mobile, 0, 3).'****'.substr($mobile,7);
}