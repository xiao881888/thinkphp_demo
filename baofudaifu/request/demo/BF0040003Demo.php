<?php
require "../../init.php";
require "../TransContent.php";
require "../TransReqData.php";
require "../TransReqDataBF0040003.php";
require "../TransDataUtils.php";

$test = new TransReqDataBF0040003();
$test -> _set("trans_btime", "20121210");
$test -> _set("trans_etime", "20121210");

$transReqDatas = new TransReqData();
$transReqDatas -> __array_push($test -> _getValues());



// 获取trans_reqDatas 数组类型
$trans_reqDatas = $transReqDatas -> __getTransReqDatas();

$trans_content = new TransContent();
$trans_content -> __set("trans_reqDatas", $trans_reqDatas);

// 获取trans_content报文体
$data_content = TransDataUtils :: __array2Xml($trans_content -> __getTransContent());

echo "报文如下：\n", $data_content, "\n";

$request_url = "http://10.0.20.19:8888/baofoo-fopay/pay/BF0040003.do";

// 私钥加密
$encrypted = $baofooSdk -> encryptedByPrivateKey($data_content);
echo "-------Post发送代付退款查询-------\n";
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