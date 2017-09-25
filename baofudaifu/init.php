<?php
	header("Content-type: text/html; charset=utf-8");
	require "BaofooSdk.php";
	$baofooSdk = new BaofooSdk(100000178, 100000859,'xml','D:\\Develop\\workspace\\php\\cer\\m_pri.pfx','D:\\Develop\\workspace\\php\\cer\\baofoo_pub.cer','123456');
?>