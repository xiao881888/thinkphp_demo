<?php

return array(
		'ALIPAY_CONFIG' => array(
				'partner' => '2088221181009165', // 这里是你在成功申请支付宝接口后获取到的PID；
				'key' => 'dl8lqj8hk8u7lxrx7dl1eu6mk6wt0syq', // 这里是你在成功申请支付宝接口后获取到的Key
				'private_key_path' => __DIR__ . '/alipay/rsa_private_key.pem',
				'ali_public_key_path' => __DIR__ . '/alipay/alipay_public_key.pem',//支付宝公钥（后缀是.pen）文件相对路径
				'sign_type' => strtoupper('RSA'),
				'input_charset' => strtolower('utf-8'),
				'cacert' => __DIR__ . '/alipay/cacert.pem',
				'seller_id'=>'fujiansihe@163.com',
				'transport' => 'http' ,
				
		) 
);