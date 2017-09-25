<?php
include_once 'lianlianpay/llpay.config.php';


if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
	$redisHost = '10.168.249.111';
	$redisPort = 6374;
	$push_mode = true;
	$apns_host = 'gateway.push.apple.com';
	$apns_cert = __ROOT__.'Identify/key/{$package_name}/push_production.pem';
	$tiger_ip = '118.178.8.65';
}elseif( get_cfg_var('PROJECT_RUN_MODE') == 'TEST' ){
	$redisHost = '123.56.221.173';
	$redisPort = 6373;
	$push_mode = false;
	$apns_host = 'gateway.sandbox.push.apple.com';
	$apns_cert = __ROOT__.'Identify/key/{$package_name}/push_development.pem';
	$tiger_ip = '123.56.221.173';
}else {
	$redisHost = '192.168.1.171';
	$redisPort = 6379;
	$push_mode = false;
	$apns_host = 'gateway.sandbox.push.apple.com';
	$apns_cert = __ROOT__.'Identify/key/{$package_name}/push_development.pem';
	$tiger_ip = '192.168.1.172:81';
}

return array(
	'TIGER_IP_ADDRESS'=>array(
		$tiger_ip
	),
	
	'RECHARGE_TYPE_DESC'=>array(
		'1'=>'账户充值',
	),
		
	'AHEAD_OF_END_TIME'=>array(
		'JC_LOTTERY'=>'120', //2分钟
	),
		
	'PUSH_MESSAGE_TEMPLATE'=> array(
		'WIN_PRIZE'=>'恭喜您！您有一个订单中奖啦，快去看看。',
		'PLUS_WIN_PRIZE'=>'恭喜您有一个$1的订单中奖啦！中奖金额$2，加奖金额$3，总奖金$4，请点击查看！',
		'FAIL_TO_BUY_TICKET'=>'抱歉！您有一个订单出票失败。',
		'FAIL_TO_FOLLOW_TICKET'=>'抱歉！您有一个订单追号失败，追号的期次是$1的$2期，已做退款处理。',
		'FAIL_TO_TICKET_PRINTOUT'=>'抱歉！您有一个订单部分出票失败，请及时查看。',
	),
		
	'IOS_PUSH_TYPE' => array(
		'WIN_PRIZE'=>1,
		'FAIL_TO_BUY_TICKET' => 2,
		'FAIL_TO_FOLLOW_TICKET' => 3,
	),
	'PUSH_NEXT_ACTION_TYPE' => array(
		'ORDER_DETAILE_PAGE' 	=> 1,
		'BUY_LOTTERY_PAGE' 		=> 2,
		'RECHARGE_PAGE' 		=> 3,
		'TARGET_WEBVIEW_PAGE' 	=> 4 
		),
		
	'MAIL_ADDRESS'	 => '', // 邮箱地址
	'MAIL_LOGINNAME' => '', // 邮箱登录帐号
	'MAIL_SMTP'		 => 'smtp.163.com', // 邮箱SMTP服务器
	'MAIL_PASSWORD'	 => '', // 邮箱密码
	'NOTICE_EMAILS' => '15980228063@139.com',
	'NOTICE_BEE_EMAILS' => '13459461935@139.com',
	
	
	'IOS_PUSH_CONFIG' => array(
		'com.tigercai.TigerLottery' => array(
			'IOS_PRODUCTION_MODE' 	=> $push_mode,
			'IOS_PASSPHRASE' 		=> 'com.tiger@sh',
			'IOS_APNS_HOST' 		=> $apns_host,
			'IOS_APNS_CERT' 		=> str_replace('{$package_name}', 'com.tigercai.TigerLottery', $apns_cert),
			'IOS_APNS_PORT' 		=> 2195,
			),
		'com.tigercai.TigerHGY' => array(
			'IOS_PRODUCTION_MODE'	=> $push_mode,
			'IOS_PASSPHRASE'		=> 'com.tiger@sh',	//IOS证书私钥密码
			'IOS_APNS_HOST'			=> $apns_host,	
			'IOS_APNS_CERT'			=> str_replace('{$package_name}', 'com.tigercai.TigerHGY', $apns_cert),	
			'IOS_APNS_PORT'			=> 2195,
			),
		'com.tigercai.TigerLLN' => array(
			'IOS_PRODUCTION_MODE'	=> $push_mode,
			'IOS_PASSPHRASE'		=> 'com.tiger@sh',	//IOS证书私钥密码
			'IOS_APNS_HOST'			=> $apns_host,	
			'IOS_APNS_CERT'			=> str_replace('{$package_name}', 'com.tigercai.TigerLLN', $apns_cert),	
			'IOS_APNS_PORT'			=> 2195,
			),
		'com.liuyu.lhcp' => array(
			'IOS_PRODUCTION_MODE'	=> $push_mode,
			'IOS_PASSPHRASE'		=> 'com.tiger@sh',	//IOS证书私钥密码
			'IOS_APNS_HOST'			=> $apns_host,	
			'IOS_APNS_CERT'			=> str_replace('{$package_name}', 'com.liuyu.lhcp', $apns_cert),	
			'IOS_APNS_PORT'			=> 2195,
			),
		'com.tigercai.TigerGYH' => array(
			'IOS_PRODUCTION_MODE'	=> $push_mode,
			'IOS_PASSPHRASE'		=> 'com.tiger@sh',	//IOS证书私钥密码
			'IOS_APNS_HOST'			=> $apns_host,	
			'IOS_APNS_CERT'			=> str_replace('{$package_name}', 'com.tigercai.TigerGYH', $apns_cert),	
			'IOS_APNS_PORT'			=> 2195,
			),		
		),
		
	'IOS_PRODUCTION_CERT'	=> __ROOT__.'Identify/key/push_production.pem',
	'IOS_DEVELOPMENT_CERT'	=> __ROOT__.'Identify/key/push_development.pem',
		
	'ANDROID_PUSH_CONFIG' => array(
			'com.tigercai.TigerLottery' => array(
					'ACCESS_KEY_ID'=> 'n7fZXCA25Uyr5N25',
					'ACCESS_SECRET'=> 'QGxzgWi3vOd7R8vdTOnd9je2A9iesP',
					'APP_KEY' => '23383332' 
			),
			'co.sihe.tigerlottery' => array(
					'ACCESS_KEY_ID'=> 'n7fZXCA25Uyr5N25',
					'ACCESS_SECRET'=> 'QGxzgWi3vOd7R8vdTOnd9je2A9iesP',
					'APP_KEY' => '23383332'
			),
	),
		
    'ACT_USER_ENCRYPT_KEY' => '10101',
    'LIANLIAN_CONFIG' => $llpay_config,
    'LOAD_EXT_CONFIG' => 'act,code,const,alipay,heepay,yeepay,web_config,baofu,liveScore,indexRecomment,basketball,zxwx_h5,unite_push,pay,integral,upload,lottery_config,first_recharge_activity,follow_bet',
    'URL_HTML_SUFFIX' => '',
    'RECHARGE_DIPOSE' => array(
        1 => 'LianLian/dispose' ,
    ),
		
	'REDIS_HOST' => $redisHost,
	'REDIS_PORT' => $redisPort,
    
    'PLATFORM' => array(
        'FORBIDDEN' => 0,
        'NORMAL' => 1,
    ),
    
    'SMS_TYPE' => array(
        'REGISTER' => 1,
        'SET_LOGIN_PWD' => 2,
        'SET_PAYMENT_PWD' => 3,
        'FIND_LOGIN_PWD' => 4,
        'RECHARGE' => 5,
        'WITHDRAW' => 6,
        'SET_FREE_PWD' => 7,
    ),

    'SMS_TEMPLATE' => array(
        'REGISTER' => 74516,
        'SET_LOGIN_PWD' => 74517,
        'SET_PAYMENT_PWD' => 74518,
        'SET_FREE_PWD' => 74519,
        'FIND_LOGIN_PWD' => 74517,
        'RECHARGE' => 0,
        'WITHDRAW' => 0,
    ),

    'BAIWAN_SMS_TEMPLATE' => array(
        'REGISTER' => 173940,
        'SET_LOGIN_PWD' => 173940,
        'SET_PAYMENT_PWD' => 0,
        'SET_FREE_PWD' => 0,
        'FIND_LOGIN_PWD' => 173940,
        'RECHARGE' => 0,
        'WITHDRAW' => 0,
    ),

    'NEW_SMS_TEMPLATE' => array(
        'REGISTER' => 174162,
        'SET_LOGIN_PWD' => 174161,
        'SET_PAYMENT_PWD' => 0,
        'SET_FREE_PWD' => 0,
        'FIND_LOGIN_PWD' => 174161,
        'RECHARGE' => 0,
        'WITHDRAW' => 0,
    ),
    
    'USER_STATUS' => array(
    	'ENABLE'   => 1,
    	'DISABLE'  => 0,
    ),
    
    'SYXW_SELECT_COUNT' => array(
        '21' => 1, '22' => 2, '23' => 3, '24' => 4, '25' => 5,
        '26' => 6, '27' => 7, '28' => 8, '29' => 1, '30' => 2,
        '31' => 2, '32' => 3, '33' => 3, '34' => 2, '35' => 3,
    	'36' => 4, '37' => 5
    ),
    
    'SORT_PLAY_TYPE' => array(
        '1', '2', '11', '13', '21', '22', '30', '32',
        '23', '24', '25', '26', '27', '28', '31', '33', '36', '37'
    ),
    
    'BET_TYPE' => array(
        'SINGLE' => 1,
        'MULTIPLE' => 2,
        'SURE_OR_NOT' => 3,
        'POSITION_MULTIPLE' => 6,
    ),
		
	'BET_NUMBER_FORMAT_SEPERATOR'=>array(
    	'EACH_NUMBER'=>',',
		'SURE_OR_NOT'=>'@',
		'DIFFERENT_SECTION'=>'#',				
    ),
	'OPTIMIZE_SERIES_TYPE' => array(
			'102',
			'103',
			'106',
			'111',
			'118',
			'128',
			'134' 
	),
		
    'LOTTERY_TYPE' => array( // 彩种类型，lottery_id 作为下标
		'1' => 'ssq',
		'2' => 'fc3d',
		'3' => 'dlt',
		'4' => 'sdsyxw',
		'8' => 'ahsyxw',
		'18' => 'hbsyxw',
		'5' => 'jlks',
		'19' => 'jxks',
    	'20' => 'zcsfc',
    	'21' => 'zcsfc',
        '22' => 'jsks',
    		
        '601' => 'jczq',
        '602' => 'jczq',
        '603' => 'jczq',
        '604' => 'jczq',
        '605' => 'jczq',
        '606' => 'jczq',

    	'701' => 'jclq',
    	'702' => 'jclq',
    	'703' => 'jclq',
    	'704' => 'jclq',
    	'705' => 'jclq',
    ),
		
	'LOTTERY_PRICE' => 2,
    'LOTTERY_ADD_PRICE' => 3,
		
	'BEFORE_DEADLINE' => 120,
		
	'FOLLOW_BET_TYPE' => array(
		'STOP_WHEN_PRIZE' => 1,
		'STOP_UNTIL_AMOUNT' => 2,
	),
    
    'LOTTERY_DISPLAY' => array(
        'YES' => 1,
        'NO'  => 0,  
    ),
		
	'LOTTERY_STATUS' => array(
		'ONSALE' => 1,
		'STOP'  => 0,
	),

	'MERGE_COUNT' => array(
			101 => array('count'=>1, 'series'=>array(1)),
			102 => array('count'=>2, 'series'=>array(2)),
			103 => array('count'=>3, 'series'=>array(3)),
			104 => array('count'=>3, 'series'=>array(2)),
			105 => array('count'=>3, 'series'=>array(2,3)),
			106 => array('count'=>4, 'series'=>array(4)),
			107 => array('count'=>4, 'series'=>array(3)),
			108 => array('count'=>4, 'series'=>array(3,4)),
			109 => array('count'=>4, 'series'=>array(2)),
			110 => array('count'=>4, 'series'=>array(2,3,4)),
			111 => array('count'=>5, 'series'=>array(5)),
			112 => array('count'=>5, 'series'=>array(4)),
			113 => array('count'=>5, 'series'=>array(4,5)), 	
			114 => array('count'=>5, 'series'=>array(2)), 
			115 => array('count'=>5, 'series'=>array(3,4,5)), 	
			116 => array('count'=>5, 'series'=>array(2,3)),
			117 => array('count'=>5, 'series'=>array(2,3,4,5)),	
			118 => array('count'=>6, 'series'=>array(6)),	
			119 => array('count'=>6, 'series'=>array(5)),
			120 => array('count'=>6, 'series'=>array(5,6)),
			121 => array('count'=>6, 'series'=>array(2)),
			122 => array('count'=>6, 'series'=>array(3)),
			123 => array('count'=>6, 'series'=>array(4,5,6)),
			124 => array('count'=>6, 'series'=>array(2,3)),
			125 => array('count'=>6, 'series'=>array(3,4,5,6)),
			126 => array('count'=>6, 'series'=>array(2,3,4)),
			127 => array('count'=>6, 'series'=>array(2,3,4,5,6)),
			128 => array('count'=>7, 'series'=>array(7)),
			129 => array('count'=>7, 'series'=>array(6)),
			130 => array('count'=>7, 'series'=>array(6,7)),
			131 => array('count'=>7, 'series'=>array(5)),
			132 => array('count'=>7, 'series'=>array(4)),
			133 => array('count'=>7, 'series'=>array(2,3,4,5,6,7)),
			134 => array('count'=>8, 'series'=>array(8)),
			135 => array('count'=>8, 'series'=>array(7)),
			136 => array('count'=>8, 'series'=>array(7,8)),
			137 => array('count'=>8, 'series'=>array(6)),
			138 => array('count'=>8, 'series'=>array(5)),
			139 => array('count'=>8, 'series'=>array(4)),
			140 => array('count'=>8, 'series'=>array(2,3,4,5,6,7,8)),
	),
		
	'JCZQ_MATRIX' => array(	601=>1, 602=>2, 603=>3, 604=>4, 605=>5 ),
	'JCLQ_MATRIX' => array( 701=>1, 702=>2, 703=>3, 704=>4 ),
		
	'MAX_MERGE_SCHEDULE' => array(
		'601' => 8,	// 胜平负 最多支持8场串关
		'602' => 8,	// 让球胜平负
		'603' => 4,	// 比分
		'604' => 6,	// 总进球
		'605' => 4,	// 胜负半全场
			
		'701' => 8,	// 胜负 最多支持8场串关
		'702' => 8,	// 让球胜负
		'703' => 4,	// 胜分差
		'704' => 8,	// 大小分
	),
	
	'MAPPINT_JC_PLAY_TYPE' => array(
		'1' => 51,	// 单关
		'2' => 52,	// 过关
	),
		
    'BANK_CARD_STATUS' => array(
        'CHECK' => 1,
        'UNCHECK' => 0,
    ),
    
    'USER_COUPON_STATUS' => array(
        'DISABLED' => 0,
        'WAITING'  => 1,
        'NO_AVAILABLE' => 2,
        'AVAILABLE' => 3,
    ),
    
    'ISSUE_IS_CURRENT' => array(
        'YES' => 1,
        'NO' => 0,
        'NEXT' => 2,
    ),
		
	'ISSUE_PRIZE_STATUS' => array(
		'FINISH' => 3,
	),

    
    'SORT_COUPON_LIST' => array(
        'CREATE_TIME_DESC' => 2,
        'END_TIME_ASC' => 1,
    ),
    
    'USER_OWEN_COUPON' => array(
        'ALL' => 0,
        'AVAILABLE' => 1,
        'WAIT' => 2,
        'EXPIRE' => 3,
    ),
    
    'USER_ACCOUNT_LOG_TYPE' => array(
        // 类型（1 充值、2下注、3购买红包、4提现申请、5提现打款、6中奖、7 拒绝提现、11加奖红包、12夺宝活动红包）
        'RECHARGE' => 1,
        'BET' => 2,
        'BUY_COUPON' => 3,
        'APPLY_WITHDRAW' => 4,
        'PAY_DRAW' => 5,
        'WINNING' => 6,
        'REFUSE_DRAW' => 7,
        'CANCEL_FOLLOW' => 8,
        'FOLLOW_FAILED_REFUND' => 9,
        'FOLLOW_FOR_FROZEN' => 10,
        'PLUS_COUPON_REWARD' => 11,
        'DUOBAO_COUPON_REWARD' => 12,
        'SYSTEM_FIXED_BUG' => 13,
        'INTEGRAL_EXCHANGE' => 14,
        'WCHAT_TRANSFER'=>15,
        'COBET_BOUGHT'=>16,
        'COBET_GUARANTEE'=>17,
        'COBET_BOUGHT_FROZEN'=>18,
        'COBET_GUARANTEE_FROZEN'=>19,
        'COBET_BOUGHT_REFUND'=>20,
        'COBET_GUARANTEE_REFUND'=>21,
        'COBET_COMMISSION'=>22,
        'COBET_WINNING'=>23,
    ),
		
	'USER_ACCOUNT_LOG_TYPE_DESC' => array(
			1 => '账户充值',
			2 => '购买彩票',
			3 => '购买红包',
			4 => '提现申请',
			5 => '提现打款',
			6 => '派奖',
			7 => '拒绝提现',
			8 => '停止追号',
			9 => '追号失败退款' ,
			10 => '追号冻结' ,
			11 => '加奖红包',
			12 => '夺宝活动红包',
			13 => '系统修正',
            14 => '积分兑换',
            15 => '微信转账',
            16 => '合买认购扣款',
            17 => '合买保底扣款',
            18 => '合买认购冻结',
            19 => '合买保底冻结',
            20 => '合买认购退款',
            21 => '合买保底退款',
            22 => '合买提成',
            23 => '合买派奖',
	),
    'USER_COUPON_LOG_TYPE' => array(
        'BUY' => 1,
        'EXCHANGE' => 2,
        'USE' => 3,
        'REFUND' => 4,
        'GIFT' => 5,
        'FIRST_RECHARGE_REWARD' => 6 ,
        'TICKET_FAILED_REFUND' => 9,
        'PLUS_COUPON_REWARD' => 11,
        'DUOBAO_COUPON_REWARD' => 12,
        'INTEGRAL_EXCHANGE' => 14,
        'WCHAT_TRANSFER'=>15,
        'COBET_BOUGHT'=>16,
        'COBET_GUARANTEE'=>17,
        'COBET_BOUGHT_FROZEN'=>18,
        'COBET_GUARANTEE_FROZEN'=>19,
        'COBET_BOUGHT_REFUND'=>20,
        'COBET_GUARANTEE_REFUND'=>21,
        'COBET_COMMISSION'=>22,
        'COBET_WINNING'=>23,
    ),

    'USER_COUPON_LOG_TYPE_DESC' => array(
        1 => '购买红包',
        2 => '兑换红包',
        3 => '使用消费',
        4 => '退款红包',
        5 => '系统赠送',
        6 => '首充40元赠送',
        9 => '出票失败退款',
        11 => '加奖红包',
        12 => '夺宝活动红包',
        14 => '积分兑换',
        15 => '微信转账',
        16 => '合买认购扣款',
        17 => '合买保底扣款',
        18 => '合买认购冻结',
        19 => '合买保底冻结',
        20 => '合买认购退款',
        21 => '合买保底退款',
        22 => '合买提成',
        23 => '合买派奖',
    ),
	
    
    'COUPON_STATUS' => array(
        'NO_AVAILABLE' => 0,
        'AVAILABLE' => 1,
    ),
		
	'RECHARGE_RECORD_STATUS'=>array(
    	'NOTIFIED'=>1
    ),
		
    'RECHARGE_STATUS' => array(
        'UNPAID' => 0,
        'PAID' => 1,
        'FAIL' => 2,
    ),
    
    'RECHARGE_SOURCE' => array(
        'IPHONE' => 1,
        'ANDROID' => 4,
    ),
    
    'COUPON_EXCHANGE_STATUS' => array(
        'NO_AVAILABLE' => 0,
        'NO_RECEIVE' => -1,
        'RECEIVE' => 1,
    ),
    
    'IDENTITY_CARD_STATUS' => array(
        'UNVERIFY' => 0,
        'VERIFY' =>1,
    ),
    
    'FOLLOW_STATUS' => array(
        'DELETE' => -1,
    	'UNPAID' => 0,
    	'NORMAL' => 1,
        'CANCEL' => 3,
    	'FINISH' => 4,
    ),
    
    'ORDER_STATUS' => array(
        'DELETE' => -1, //已删除
        'UNPAID' => 0,	// 未付款
        'PAYMENT_SUCCESS' => 1, //已付款未出票
    	'PRINTOUTING' => 2, //出票中
    	'PRINTOUTED' => 3, //已出票
    	'PRINTOUT_ERROR' => 4, //出票失败
    	'PRINTOUT_ERROR_REFUND' => 5, //出票失败且退款
        'BET_ERROR' => 6, // 下单失败
        'PRINTOUTING_PART_REFUND' => 7, // 出票中，部分失败退款
        'PRINTOUTED_PART_REFUND' => 8, // 已出票，部分失败退款
    ),

    'ORDER_STATUS_DESC' => array(
        -1 => '已删除', //已删除
        0 => '未付款',	// 未付款
        1 => '已付款未出票', //已付款未出票
        2 => '出票中', //出票中
        3 => '已出票', //已出票
        4 => '出票失败', //出票失败
        5 => '出票失败且退款', //出票失败且退款
        6 => '下单失败', // 下单失败
        7 => '出票中，部分失败退款', // 出票中，部分失败退款
        8 => ' 已出票，部分失败退款', // 已出票，部分失败退款
    ),
		
	'JC_PLAY_TYPE' => array(
		'ONE_STAGE' => 51,
		'MULTI_STAGE' => 52,
	),
		
	'ORDER_WINNINGS_STATUS' => array(
		'NO' => -1,
		'YES' => 1,
		'WAITING' => 0,
		'PART'	=> 2	
	),
		
	'ORDER_DISTRIBUTE_STATUS' => array(
		'NO' => 0,
		'YES' => 1,
		'PRIZED' => 2,
		'PART_PRIZED' => 3,
	),
    
    'WINNINGS_STATUS' => array( // 中奖状态
        'YES' => 1,
        'NO' => 0,
    ),
    
    'WITHDRAW_STATUS' => array(
        'NO_AUDIT' 					=> 0,
        'WITHDRAW_STATUS_WAITPAY' 	=> 1,
        'WITHDRAW_STATUS_PAID' 		=> 2,
        'WITHDRAW_STATUS_REFUSE' 	=> 3,
        'WITHDRAW_STATUS_REVOKE' 	=> 4,
    ),
    
    'SMS_MESSAGE_TYPE'	=> array(
        'REGISTER' => 1,
        'RESET_LOGIN_PASSWORD' => 2,
        'RESET_PAYMENT_PASSWORD' => 3,
        'FIND_LOGIN_PASSWORD' => 4,
        'RECHARGE' => 5,
        'WITHDRAW' => 6,
        'SET_FREE_PASSWORD' => 7,
    ),
		
	'SCHEDULE_STATUS' => array(
		'NO_SALE' => 0,
		'ON_SALE' => 1,
		'PRIZE'	  => 4,
	),
    
    'TICKET_STATUS' => array(
        'DELETE' => -1,
        'UN_PRINTOUT' => 0,
        'PRINTOUT' => 1,
        'PRINTOUT_FAIL' => 2,
    ),
		
	'KS_PLAY_TYPE' => array(
		'SUM' 					=> 41,
		'THREE_SAME_NUM_SINGLE' => 42,
		'THREE_SAME_NUM_ALL' 	=> 43,
		'THREE_SEQUENCE_ALL' 	=> 44,
		'THREE_DIFF_NUM'		=> 45,
		'TWO_SAME_NUM_SINGLE'	=> 46,
		'TWO_SAME_NUM_ALL'		=> 47,
		'TWO_DIFF_NUM'			=> 48
	),
	
	'JC' => array(
		'JCZQ' => 6,
		'JCLQ' => 7,	
	),
	'JCZQ' => array(
		'NO_CONCEDE' 	=> 601,
		'CONCEDE' 		=> 602,
		'SCORES' 		=> 603,
		'BALLS' 		=> 604,
		'HALF' 			=> 605,
		'MIX' 			=> 606,
	),
	'JCLQ' => array(
		'NO_CONCEDE'	=> 701,
		'CONCEDE'		=> 702,
		'SFC'			=> 703,
		'DXF'			=> 704,
		'MIX'			=> 705,			
	),
	'TMPL_ACTION_ERROR' 	=> 'Pay:error',
	'TMPL_ACTION_SUCCESS' 	=> 'Pay:success',
	'RECHARGE_SALT' => '!@AWD#@$Fasdsda1116dsa@*^',	
		
	//手机平台ID
	'DEVICE_OS_TYPE' => array(
		'IOS' => 2,
	),

	'TEST_USER_ID' => '638',
	'TEST_USER_ORDER_AMOUNT_LIMIT' => 500,

	'AUTO_REFUSE_WITHDRAW_REMARK' => '据国家相应政策要求，充值后消费金额小于存入金额30%（不含红包消费）的提现申请，将不予通过，例：用户充值50元，在消费超过15元后方能提现，若该用户在下注未超过15元时，使用红包下注获得15元奖金，需将奖金额外提现。谢谢您的合作',
	'AUTO_REFUSE_WITHDRAW_REMARK_2' => '提现金额不能小于2元',

	'USER_BET_DRAFT_STATUS' => array(
		'DELETE' => -1,
		'NO_AVAILABLE' => 0,
		'AVAILABLE' => 1,
	),

    'NOT_LOG_API' => array('10206','10209','10110','10105','10301','10304','10801','10802','10510',
        '10803','10804','10805','10806','10807','10808','10809',
        '10810','10811','10812','10813','10814','10815','10816','10817'),

);
