<?php
require "../../init.php";
require "../TransContent.php";
require "../TransHead.php";
require "../TransReqData.php";
require "../TransReqDataBF0040004.php";
require "../TransDataUtils.php";

$transReqDatas = new TransReqData();

$test = new TransReqDataBF0040004();
$test -> _set("trans_no", "4ABCDEFG32");
$test -> _set("trans_money", "1");
$test -> _set("to_acc_name", "测试账号");
$test -> _set("to_acc_no", "666666666");
$test -> _set("to_bank_name", "中国工商银行");
$test -> _set("to_pro_name", "上海市");
$test -> _set("to_city_name", "上海市");
$test -> _set("to_acc_dept", "支行");
$test -> _get('to_acc_dept');


$transReqDatas -> __array_push($test -> _getValues());


$test2 = new TransReqDataBF0040004();
$test2 -> _set("trans_no", "4ABCDEFG33");
$test2 -> _set("trans_money", "2");
$test2 -> _set("to_acc_name", "测试账号2");
$test2 -> _set("to_acc_no", "2");
$test2 -> _set("to_bank_name", "中国工商银行");
$test2 -> _set("to_pro_name", "上海市");
$test2 -> _set("to_city_name", "上海市");
$test2 -> _set("to_acc_dept", "支行");
$test2 -> _get('to_acc_dept');

// 添加到trans_reqDatas
$transReqDatas -> __array_push($test2 -> _getValues());

// 获取trans_reqDatas 数组类型
$trans_reqDatas = $transReqDatas -> __getTransReqDatas();


$transHead = new TransHead();
$transHead -> _set("trans_count", "2");
$transHead -> _set("trans_totalMoney", "1");

$trans_content = new TransContent();
$trans_content -> __set("trans_head", $transHead -> _getValues());
$trans_content -> __set("trans_reqDatas", $trans_reqDatas);

 $data_content = TransDataUtils :: __array2Xml($trans_content -> __getTransContent());


 $request_url = "http://10.0.20.19:8888/baofoo-fopay/pay/BF0040004.do";

// 私钥加密

$encrypted = $baofooSdk -> encryptedByPrivateKey($data_content);
echo "-------Post发送代付拆封接口-------\n";
$httpResult = $baofooSdk -> post($encrypted, $request_url);

if(count(explode("trans_content",$httpResult))>1){
	//基本信息处理（明文返回）
	echo '宝付明文同步返回:', $httpResult;
}else{
	//业务逻辑信息处理
	$decrypt = $baofooSdk -> decryptByPublicKey($httpResult);
	echo '宝付加密同步返回:', $decrypt;
}




?>