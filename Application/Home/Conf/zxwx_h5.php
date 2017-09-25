<?php
return array(
		
		'zxwx_config' => array(
				
				// 'pre_pay_url' => 'https://120.27.165.177:8099/MPay/backTransAction.do',
				
				'pre_pay_url' => 'https://120.55.176.124:8092/MPay/backTransAction.do',
				
				'encry_md5_key' => '83178957147531190147849684629545',
				
				'mer_id' => '886600000003791' 
		)
		,
		'ZWX_ALIPAY_CONFIG' => array(
				'PAY_URL' => 'https://api.zwxpay.com/pay/unifiedorder', // 提交订单URL
				'QUERY_URL' => 'https://api.zwxpay.com/pay/orderquery', // 查询订单URL
				'REFUND_URL' => 'https://api.zwxpay.com/secapi/pay/refund', // 退款URL
				'QUERY_REFUND_URL' => 'https://api.zwxpay.com/pay/refundquery', // 查询退款URL
				'MCH_ID' => '15121832',
				'SIGN_KEY' => '0ffe1c3c86760460956092fb0608da5e' 
		),
		'ZWX_WEIXIN_CONFIG' => array(
				'PAY_URL' => 'https://api.ulopay.com/pay/unifiedorder', // 提交订单URL
				'QUERY_URL' => 'https://api.ulopay.com/pay/orderquery', // 查询订单URL
				'REFUND_URL' => 'https://api.ulopay.com/secapi/pay/refund', // 退款URL
				'QUERY_REFUND_URL' => 'https://api.ulopay.com/pay/refundquery', // 查询退款URL
				'MCH_ID' => '26104610',
				'SIGN_KEY' => '19acb111ec9d05f6e8cd62cc78c9ed23' 
		) 
);