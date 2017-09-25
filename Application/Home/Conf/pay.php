<?php
return array(
		'XINGYE_BANK_WX_CONFIG' => array(
				'pre_pay_url' => 'https://pay.swiftpass.cn/pay/gateway',
				'version' => '2.0' ,
				'mch_id' => '101540022445',
				'key' => 'bcee934330fcf2fcb09196888a1db719',
				'appid' => 'wx107a478a39f364a8'
				// 'mch_id' => '755437000006',
				// 'key' => '7daa4babae15ae17eee90c9e',
				// 'appid' => 'wx2a5538052969956e'
		),
		//因为丁兆峰渠道风控问题，本渠道最终没有对接成功
		'DZFWXH5_CONFIG' => array(
			'APP_ID' => '3992017060231248',
			'APP_KEY' => 'c2c428594f3ed8bc7b91f5deb659cd7e',
			'GOODS_ID' => 503
			),
		//威富通渠道配置
		'WFTWX_CONFIG' => array(
			'MCH_ID' => '105570029855',
			'SIGN_KEY' => '877c45e9c3a6ebfd904403ba6c090538',
			),

		//派洛贝渠道配置
		'PLBZFB_CONFIG' => array(
			'MCH_ID' => 'a247414e55898577',
			'SIGN_MD5_KEY' => '9129b2f593d46b10c616e08a9dd58fae'
			),
);