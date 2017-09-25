<?php

return array(
		'HEEPAY_CONFIG' => array(
				'pay_url'=>'https://pay.heepay.com/Payment/Index.aspx',//正式交易地址
				'version' => 1, // 当前接口版本号1
				'agent_id' => '2069840', // 商户内码 如1234567（汇付宝商户编号：七位整数数字，可登录商户后台首页查看）
				'pay_type' => 30, // 微信=30，支付宝=22（数据类型：int）
				'is_phone' => 1, // 微信扫码支付：is_phone参数不传，is_frame参数不传；
				'is_frame' => 0, // 微信wap支付：is_phone=1，is_frame=0；微信公众号支付：is_phone=1，is_frame=1；
				'agent_key' => '83AE7F53778F4E5B84E1ADF9',//商户密钥
				'is_test' => 1, // 是否测试，1=测试，可测试接口提交和异步通知，非测试请不用传本参数(如传了此参数，则必须参加MD5的验证)
		) 
);