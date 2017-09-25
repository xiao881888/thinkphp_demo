<?php
if (isset($_SERVER['SERVER_RUN_MODE']) and $_SERVER['SERVER_RUN_MODE'] == 'PRERELEASE'){
    $web_url = 'https://prerelease-h5.tigercai.com';
    $api_debug = false;
    $bet_sign_key = 'NXsuZoyCZq01UWG3';
}else if(get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION'){
    $web_url = 'https://h5.tigercai.com';
    $api_debug = false;
    $bet_sign_key = 'NXsuZoyCZq01UWG3';
}else if(get_cfg_var('PROJECT_RUN_MODE') == 'TEST'){
    $web_url = 'https://test-h5.tigercai.com';
    $api_debug = true;
    $bet_sign_key = '123456';
}else{
    $web_url = 'https://test-h5.tigercai.com';
    $api_debug = true;
    $bet_sign_key = '123456';
}

return array(
    //'配置项'=>'配置值'
    'LOAD_EXT_CONFIG' => 'code,const,response,lottery,third_bet_config,follow_bet,liveScore,basketball',

    'WEB_URL' => $web_url,

    'API_DEBUG' => $api_debug,

    'BET_SIGN_KEY' => $bet_sign_key,

    'BET_ACT' => array(
        'BET_JC' => 1001,
        'BET_SZC' => 1002,
        'BET_LZC' => 1003,
    ),

    'API_AUTH' => array(
        'User/index',
        'User/logout',
        'Order/index',
        'Order/detail',
        'Order/program',
        'Bet/submitConfirm',
        'Bet/submitPay',
        'Recharge/getPlatformList',
        'Recharge/userRecharge',
        'Recharge/getRechargeInfo',
        'WebBet/preBet',
    ),

    'VAILD_PARAM' => array(
        'Send/msg' => array('tel' => '^1\d{10}$'),
        'User/register' => array(
            'tel' => '^1\d{10}$',
            'passwd' => array('^(?=.*\d)[a-zA-Z\d]{6,18}$','密码长度在6-18位，不能有特殊字符'),
            'sms_validation' => '^\d{6}$'
        ),
        'User/login' => array('tel' => '^1\d{10}$','passwd' => '^(?=.*\d)[a-zA-Z\d]{6,18}$'),
        'User/resetPassword' => array(
            'tel' => '^1\d{10}$',
            'passwd' => array('^(?=.*\d)[a-zA-Z\d]{6,18}$','密码长度在6-18位，不能有特殊字符'),
            'sms_validation' => '^\d{6}$'
        ),
    ),

    'COOKIE_CACHE_DAYS' => 7,

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
        'XINCAI_REGISTER' => 174162,
    ),

    'SMS_CONFIG' => array(
        'SMS_LIFETIME' => 30 * 60,
    ),

    'USER_STATUS' => array(
        'ENABLE'   => 1,
        'DISABLE'  => 0,
    ),

    'USER_COUPON_STATUS' => array(
        'DISABLED' => 0,
        'WAITING'  => 1,
        'NO_AVAILABLE' => 2,
        'AVAILABLE' => 3,
    ),

    'INTEGRAL_ACT'=>array(
        'ADD_USER_INFO' => 1001,
        'USER_INTEGRAL_INFO' => 1002,
        'USER_INTEGRAL_DETAIL' => 1003,
        'INTEGRAL_GOOD_LIST' => 1004,
        'EXCHANGE_GOOD' => 1005,
        'USER_SIGN' => 1006,
        'USER_DRAW' => 1007,
        'ADD_USER_INTEGRAL' => 1008,
        'SIGNED_RECOMMEND_LIST' => 1009,
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

    'TICKET_STATUS' => array(
        'DELETE' => -1,
        'UN_PRINTOUT' => 0,
        'PRINTOUT' => 1,
        'PRINTOUT_FAIL' => 2,
    ),

    'ORDER_WINNINGS_STATUS' => array(
        'NO' => -1,
        'YES' => 1,
        'WAITING' => 0,
        'PART'	=> 2
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

    'LOTTERY_DISPLAY' => array(
        'YES' => 1,
        'NO'  => 0,
    ),

    'LOTTERY_STATUS' => array(
        'ONSALE' => 1,
        'STOP'  => 0,
    ),

    'SCHEDULE_STATUS' => array(
        'NO_SALE' => 0,
        'ON_SALE' => 1,
        'PRIZE'	  => 4,
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

    'JC_PLAY_TYPE' => array(
        'ONE_STAGE' => 51,
        'MULTI_STAGE' => 52,
    ),

    'PLATFORM' => array(
        'FORBIDDEN' => 0,
        'NORMAL' => 1,
    ),

    'RECHARGE_SALT' => '!@AWD#@$Fasdsda1116dsa@*^',

    'RECHARGE_STATUS' => array(
        'UNPAID' => 0,
        'PAID' => 1,
        'FAIL' => 2,
    ),

    'LOTTERY_PRICE' => 2,

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
    ),

    /*随机数字彩配置*/
    'RANDOM_LOTTERY' => array(
        'DESC_CONTENT' =>'天天有好运',
        'LOTTERY_ID' => array('SSQ'=>1,'DLT'=>3,'SYYDJ' =>4),
        'RANDOM_SSQ_ARRAY' => array(
            '01','02','03','04','05','06','07','08','09','10',
            '11','12','13','14','15','16','17','18','19','20',
            '21','22','23','24','25','26','27','28','29','30',
            '31','32','33'
        ),
        'RANDOM_SSQ_ARRAY2' => array(
            '01','02','03','04','05','06','07','08','09','10',
            '11','12','13','14','15','16'
        ),
        'RANDOM_SSQ_PLAY_TYPE' => array(
            'PTTZ'   =>'1',
        ),
        'RANDOM_FC3D_ARRAY' => array(
            0,1,2,3,4,5,6,7,8,9
        ),
        'RANDOM_FC3D_PLAY_TYPE_ARRAY' => array(
            '11', '12', '13'
        ),
        'RANDOM_FC3D_PLAY_TYPE' => array(
            'ZHIX'   =>'11',
            'ZUX3' =>'12',
            'ZUX6' =>'13'
        ),
        'RANDOM_DLT_ARRAY' => array(
            '01','02','03','04','05','06','07','08','09','10',
            '11','12','13','14','15','16','17','18','19','20',
            '21','22','23','24','25','26','27','28','29','30',
            '31','32','33','34','35'
        ),
        'RANDOM_DLT_ARRAY2' => array(
            '01','02','03','04','05','06','07','08','09','10',
            '11','12'
        ),
        'RANDOM_DLT_PLAY_TYPE' => array(
            'PTTZ'   =>'1',
        ),

        'RANDOM_11SELECT5_ARRAY' => array(
            '01','02','03','04','05','06','07','08','09','10','11'
        ),
        'RANDOM_11SELECT5_PLAY_TYPE' => array(
            'RX2' => '22',
            'RX3' => '23',
            'RX4' => '24',
            'RX5' => '25',
            'RX6' => '26',
            'RX7' => '27',
            'Q2ZHIX' => '30',
            'Q2ZUX' => '31',
            'Q3ZHIX' => '32',
            'Q3ZUX' => '33'
        ),

        'RANDOM_K3_ARRAY' => array(
            '1','2','3','4','5','6'
        ),
        'RANDOM_K3_PLAY_TYPE' => array(
            'STHDX' => '42',
            'SLHTX' => '44',
            'SBTHTZ' => '45',
            'ETHDX' => '46',
            'EBTHTZ' => '48',
        ),

        'LOTTERY_ID_FOR_METHOD_NAME' => array(
            1 => 'SSQ',
            2 => 'FC3D',
            3 => 'DLT',
            4 => '11SELECT5',
            5 => 'K3',
            8 => '11SELECT5',
        ),
    ),

    'ISSUE_IS_CURRENT' => array(
        'YES' => 1,
        'NO' => 0,
        'NEXT' => 2,
    ),

    'ISSUE_PRIZE_STATUS' => array(
        'FINISH' => 3,
    ),

    'FOLLOW_STATUS' => array(
        'DELETE' => -1,
        'UNPAID' => 0,
        'NORMAL' => 1,
        'CANCEL' => 3,
        'FINISH' => 4,
    ),

    'LOTTERY_PRICE' => 2,
    'LOTTERY_ADD_PRICE' => 3,

    'APP_ID' => array(
        'TIGER' => 1,
        'BAIWAN' => 2,
        'XINCAI' => 3,
    ),


);