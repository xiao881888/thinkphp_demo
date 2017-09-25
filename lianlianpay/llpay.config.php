<?php

/* *
 * 配置文件
 * 版本：1.2
 * 日期：2014-06-13
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 */

//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
//商户编号是商户在连连钱包支付平台上开设的商户号码，为18位数字，如：201306081000001016
$llpay_config['oid_partner'] = '201603041000749504';

$llpay_config['debug'] = true;

// 快捷支付 http://open.lianlianpay.com/#cat=36

// if($llpay_config['debug']) {
// 	$llpay_config['mock_gateway'] = "http://$_SERVER[SERVER_NAME]/index.php?s=/Mock/lianlian";
// } else {
// 	$llpay_config['mock_gateway'] = 'https://yintong.com.cn/llpayh5/payment.htm';
// }
$llpay_config['mock_gateway'] = 'https://yintong.com.cn/llpayh5/payment.htm';
//安全检验码，以数字和字母组成的字符
$llpay_config['key'] = 'gsd21snawa2e6gfdam';

//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

//版本号
$llpay_config['version'] = '1.1';

//请求应用标识 为wap版本，不需修改
$llpay_config['app_request'] = '3';


//签名方式 不需修改
$llpay_config['sign_type'] = strtoupper('MD5');

//订单有效时间  分钟为单位，默认为10080分钟（7天） 
$llpay_config['valid_order'] ="10080";

//字符编码格式 目前支持 gbk 或 utf-8
$llpay_config['input_charset'] = strtolower('utf-8');

//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
$llpay_config['transport'] = 'http';
?>