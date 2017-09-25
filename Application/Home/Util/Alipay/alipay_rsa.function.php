<?php
/* *
 * 支付宝接口RSA函数
 * 详细：RSA签名、验签、解密
 * 版本：3.3
 * 日期：2012-07-23
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。
 */

/**
 * RSA签名
 * @param $data 待签名数据
 * @param $private_key_path 商户私钥文件路径
 * return 签名结果
 */
function rsaSign($data, $private_key_path) {
    $priKey = file_get_contents($private_key_path);
    ApiLog('rsa prikey:'.$priKey, 'sign');
    ApiLog('rsa data:'.print_r($data,true), 'sign');
    
    $res = openssl_get_privatekey($priKey);
    ApiLog('rsa res:'.print_r($res,true), 'sign');
    
   	$sign_result = openssl_sign($data, $sign, $res);
    ApiLog('rsa sign_result:'.$sign_result, 'sign');
    ApiLog('rsa sign:'.$sign, 'sign');
    
    openssl_free_key($res);
	//base64编码
    $sign = base64_encode($sign);
    ApiLog('rsa:'.$sign, 'sign');
    return $sign;
}

/**
 * RSA验签
 * @param $data 待签名数据
 * @param $ali_public_key_path 支付宝的公钥文件路径
 * @param $sign 要校对的的签名结果
 * return 验证结果
 */
function rsaVerify($data, $ali_public_key_path, $sign)  {
	ApiLog('rsaVerify 1:'.print_r($sign,true), 'alipay');
	
	$pubKey = file_get_contents($ali_public_key_path);
	ApiLog('rsaVerify 2 pubKey:'.print_r($pubKey,true), 'alipay');
	
    $res = openssl_get_publickey($pubKey);
    $result = (bool)openssl_verify($data, base64_decode($sign), $res);
    ApiLog('rsaVerify 3:'.$result.'=='.openssl_verify($data, base64_decode($sign), $res), 'alipay');
    
    openssl_free_key($res);    
    return $result;
}

/**
 * RSA解密
 * @param $content 需要解密的内容，密文
 * @param $private_key_path 商户私钥文件路径
 * return 解密后内容，明文
 */
function rsaDecrypt($content, $private_key_path) {
    $priKey = file_get_contents($private_key_path);
    $res = openssl_get_privatekey($priKey);
	//用base64将内容还原成二进制
    $content = base64_decode($content);
	//把需要解密的内容，按128位拆开解密
    $result  = '';
    for($i = 0; $i < strlen($content)/128; $i++  ) {
        $data = substr($content, $i * 128, 128);
        openssl_private_decrypt($data, $decrypt, $res);
        $result .= $decrypt;
    }
    openssl_free_key($res);
    return $result;
}
?>