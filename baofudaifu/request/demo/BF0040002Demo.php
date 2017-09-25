<?php
require "../../init.php";
require "../TransContent.php";
require "../TransReqData.php";
require "../TransReqDataBF0040002.php";
require "../TransDataUtils.php";

$transReqDatas = new TransReqData();

$test = new TransReqDataBF0040002();
$test -> _set("trans_batchid", "20218703");
$test -> _set("trans_no", "20218703");


$transReqDatas -> __array_push($test -> _getValues());


$test2 = new TransReqDataBF0040002();
$test2 -> _set("trans_batchid", "20218703");
$test2 -> _set("trans_no", "1ABCDEF34");


// 添加到trans_reqDatas
$transReqDatas -> __array_push($test2 -> _getValues());

// 获取trans_reqDatas 数组类型
$trans_reqDatas = $transReqDatas -> __getTransReqDatas();


$trans_content = new TransContent();
$trans_content -> __set("trans_reqDatas", $trans_reqDatas);

 $data_content = TransDataUtils :: __array2Xml($trans_content -> __getTransContent());
 echo "报文如下：\n", $data_content, "\n";

$request_url = "http://10.0.20.19:8888/baofoo-fopay/pay/BF0040002.do";
// 私钥加密
$encrypted = $baofooSdk -> encryptedByPrivateKey($data_content);
echo "商户私钥加密结果：", $encrypted, "\n";
echo "-------Post发送代付订单查询-------\n";
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