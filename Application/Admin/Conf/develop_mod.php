<?php
/**
* 关于开发环境差异的配置项
*
*
*/
$config = array();
if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
	$config['APP_PUSH_API'] = 'http://phone.api.tigercai.com/index.php?s=/Home/Push/pushActivityMessage/';
	$config['LIANLIAN_DAIFU_API'] = 'https://yintong.com.cn/traderapi/cardandpay.htm';
	$config['BAOFU_DAIFU_CONFIG'] = array(
		'MEMBER_ID' 	=> 851102,
		'TERMINAL_ID' 	=> 29813,
		'DATA_TYPE' 	=> 'json',
		'PRIVATE_KEY_PATH' => 'baofudaifu/cer/develope/baofu_pri.pfx',
		'PRIVATE_KEY_PASSWORD' => '1234qwer',
		'PUBLIC_KEY_PATH' 	=> 'baofudaifu/cer/develope/baofu_pub.cer',
		'DAIFU_API' => 'https://public.baofoo.com/baofoo-fopay/pay/BF0040001.do',
		'DAIFU_UPDATE_API' => 'https://public.baofoo.com/baofoo-fopay/pay/BF0040002.do',
	);
	$config['GRANT_COUPON_URL'] = 'http://phone.api.tigercai.com/index.php?s=/Home/FullReducedCouponConfig/grantCouponToUser';
}elseif( get_cfg_var('PROJECT_RUN_MODE') == 'TEST' ){
	$config['APP_PUSH_API'] = 'http://test.phone.api.tigercai.com/index.php?s=/Home/Push/pushActivityMessage/';
	$config['LIANLIAN_DAIFU_API'] = '';
	$config['BAOFU_DAIFU_CONFIG'] = array(
		'MEMBER_ID' 	=> 100000178,
		'TERMINAL_ID' 	=> 100000859,
		'DATA_TYPE' 	=> 'json',
		'PRIVATE_KEY_PATH' => 'baofudaifu/cer/test/m_pri.pfx',
		'PRIVATE_KEY_PASSWORD' => '123456',
		'PUBLIC_KEY_PATH' 	=> 'baofudaifu/cer/test/baofoo_pub.cer',
		'DAIFU_API' => 'http://paytest.baofoo.com/baofoo-fopay/pay/BF0040001.do',
		'DAIFU_UPDATE_API' => 'http://paytest.baofoo.com/baofoo-fopay/pay/BF0040002.do',
	);
	$config['GRANT_COUPON_URL'] = 'http://test.phone.api.tigercai.com/index.php?s=/Home/FullReducedCouponConfig/grantCouponToUser';
	/*$config['APP_PUSH_API'] = 'http://test.phone.api.tigercai.com/index.php?s=/Home/Push/pushActivityMessage/';
	$config['LIANLIAN_DAIFU_API'] = '';
	$config['BAOFU_DAIFU_CONFIG'] = array(
		'MEMBER_ID' 	=> 100000178,
		'TERMINAL_ID' 	=> 100000859,
		'DATA_TYPE' 	=> 'json',
		'PRIVATE_KEY_PATH' => 'baofudaifu/cer/test/m_pri.pfx',
		'PRIVATE_KEY_PASSWORD' => '123456',
		'PUBLIC_KEY_PATH' 	=> 'baofudaifu/cer/test/baofoo_pub.cer',
		'DAIFU_API' => 'http://paytest.baofoo.com/baofoo-fopay/pay/BF0040001.do',
		'DAIFU_UPDATE_API' => 'http://paytest.baofoo.com/baofoo-fopay/pay/BF0040002.do',
	);*/
}else {
	$config['APP_PUSH_API'] = 'http://192.168.1.171:81/index.php?s=/Home/Push/pushActivityMessage/';
	$config['LIANLIAN_DAIFU_API'] = '';
	$config['BAOFU_DAIFU_CONFIG'] = array(
		'MEMBER_ID' 	=> 100000178,
		'TERMINAL_ID' 	=> 100000859,
		'DATA_TYPE' 	=> 'json',
		'PRIVATE_KEY_PATH' => 'baofudaifu/cer/test/m_pri.pfx',
		'PRIVATE_KEY_PASSWORD' => '123456',
		'PUBLIC_KEY_PATH' 	=> 'baofudaifu/cer/test/baofoo_pub.cer',
		'DAIFU_API' => 'http://paytest.baofoo.com/baofoo-fopay/pay/BF0040001.do',
		'DAIFU_UPDATE_API' => 'http://paytest.baofoo.com/baofoo-fopay/pay/BF0040002.do',
	);
	$config['GRANT_COUPON_URL'] = 'http://192.168.1.171:81/index.php?s=/Home/FullReducedCouponConfig/grantCouponToUser';
}
return $config;