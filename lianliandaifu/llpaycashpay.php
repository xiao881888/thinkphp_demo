<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>连连支付代付接口</title>
</head>
<?php


/* *
 * 功能：连连支付WEB交易接口接入页
 * 版本：1.0
 * 修改日期：2014-06-17
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 */
require_once ("llpay.config.php");
require_once ("lib/llpay_apipost_submit.class.php");

/**************************请求参数**************************/

//必填

//商品名称
$no_order = $_POST['no_order'];

//订单时间
$dt_order = $_POST['dt_order'];
$money_order = $_POST['money_order'];
$flag_card = $_POST['flag_card'];
$card_no = $_POST['card_no'];
$acct_name = $_POST['acct_name'];
$bank_code = $_POST['bank_code'];
$city_code = $_POST['city_code'];
$brabank_name = $_POST['brabank_name'];
$info_order = $_POST['info_order'];
$notify_url = $_POST['notify_url'];
$api_version = $_POST['api_version'];
$prcptcd = $_POST['prcptcd'];



//退款地址
$llpay_gateway_new = 'https://yintong.com.cn/traderapi/cardandpay.htm';
//需http://格式的完整路径，不能加?id=123这类自定义参数

/************************************************************/

//构造要请求的参数数组，无需改动
$parameter = array (
	"oid_partner" => trim($llpay_config['oid_partner']),
	"sign_type" => trim($llpay_config['sign_type']),
	"no_order" => $no_order,
	"dt_order" => $dt_order,
	"money_order" => $money_order,
	"flag_card" => $flag_card,
	"card_no" => $card_no,
	"acct_name" => $acct_name,
	"bank_code" => $bank_code,
	"city_code" => $city_code,
	"brabank_name" => $brabank_name,
	"info_order" => $info_order,
	"notify_url" => $notify_url,
	"api_version" => $api_version,
	"prcptcd" => $prcptcd
);

//建立请求
$llpaySubmit = new LLpaySubmit($llpay_config);
$html_text = $llpaySubmit->buildRequestJSON($parameter,$llpay_gateway_new);
echo $html_text;
?>
</body>
</html>