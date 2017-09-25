<?php
/**
 * @date 2014-12-8
 * @author tww <merry2014@vip.qq.com>
 */
/*
 * 名称：3DES加密函数
* 功能：完成3DES加密
*
* */
function encrypt3des($key, $iv, $input) {
	import ( 'Home.Util.Crypt3Des' );
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
	import ( 'Home.Util.Crypt3Des' );
	$crypt      = new Crypt3Des ();
	$crypt->key = $key;
	$crypt->iv  = $iv;
	return $crypt->decrypt($input);
}

function ApiLog($msg,$file_name){
    error_log(date('m-d H:i:s').'    :     '.$msg."\n",3,__DIR__.'/../../Runtime/'.$file_name.'_'.date('Y-m-d_H').'.log');
}

/*
 * 名称：RSA解密函数
 * 功能：完成RSA解密
 *
 * */
function decryptRsa($encrypted) {
    $keypath = __ROOT__.'Identify/key/';
    import('Home.Util.CryptRsa');
    $rsa = new CryptRsa($keypath);
    return $rsa->privDecrypt($encrypted);
}

function emptyToStr($str) {
    return ( is_null($str) ? '' : $str );
}