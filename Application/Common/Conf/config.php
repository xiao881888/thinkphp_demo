<?php

if (isset($_SERVER['SERVER_RUN_MODE']) and $_SERVER['SERVER_RUN_MODE'] == 'PRERELEASE'){
	$h5_url = 'prerelease-h5-api.tigercai.com';
}else if(get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION'){
	$h5_url = 'h5-api.tigercai.com';
}else if(get_cfg_var('PROJECT_RUN_MODE') == 'TEST'){
	$h5_url = 'test-h5-api.tigercai.com';
}else{
	$h5_url = '';
}

return array(
	'LOAD_EXT_CONFIG'	 => 'db,debug,redis,host,integral_db,msg_queue,tel_msg,other_app,cobet_scheme',
    'DEFAULT_MODULE'     => 'Home',
    'MODULE_DENY_LIST'   => array('Common','User','Install'),
    'MODULE_ALLOW_LIST'  => array('Home','Admin','Content','Log','Crontab','Integral','H5'),

    /* 系统数据加密设置 */
    'DATA_AUTH_KEY' => '7w5fSD*Nz_9sg/d[E@361tI>L#OX]oJ^,A<4B:CZ', //默认数据加密KEY

    /* 用户相关设置 */
    'USER_MAX_CACHE'     => 1000, //最大缓存用户数
    'USER_ADMINISTRATOR' => 1, //管理员用户ID

    /* URL配置 */
    'URL_CASE_INSENSITIVE' => true, //默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL'            => 2, //URL模式
    'VAR_URL_PARAMS'       => '', // PATHINFO URL参数变量
    'URL_PATHINFO_DEPR'    => '/', //PATHINFO URL分割符

    /* 全局过滤配置 */
    'DEFAULT_FILTER' => '', //全局过滤函数
    
    
    'SMS_ERROR_CODE' => array( 	'SUCCESS' => '000000'),
    
    'SMS_CONFIG'	=> array(	'ACCOUNT_ID'	=> '8a48b5514800eb2801480b42fc2505e1',
        'SMS_LIFETIME'  => 30*60,
        'ACCOUNT_TOKEN'	=> '0b651d216526458998582593221228c0',
        'APP_ID'		=> '8a48b5515388ec150153970282e111a0',
        'SERVER_IP'		=> 'app.cloopen.com',
        'SERVER_PORT'	=> '8883',
        'SOFT_VERSION'	=> '2013-12-26', ),
		
	'APP_SUB_DOMAIN_DEPLOY' => 1, // 开启子域名配置
	'APP_SUB_DOMAIN_RULES' => array(
			'mg.tigercai.com' => 'Admin',
			$h5_url => 'H5',
	),
	'SMS_WARNING_CONFIG' => array(
			'NEW_WITHDRAW_APPLY_WARNING' => array(
					'15005005784',
					77088 
			) 
	),
	'ACTIVITY_POSITION' => array(
			'ALL' => 0,
			'RECHARGE' => 1,
			'BUY' => 2,
			'ID_CARD' => 3 ,
			'JUMP_TO_JC_BETTING' => 4 ,
			'BUY_COUPON' => 5 ,
	),
    
    'TECH_SUPPORT_CALL' => '400-835-1108',

	'READ_DB' => 'mysql://tigercai_server:e4huY8J7e4@rr-bp1y62h5vwc8o62yt.mysql.rds.aliyuncs.com:3306/tigercai',
    
);