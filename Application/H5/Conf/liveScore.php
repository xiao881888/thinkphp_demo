<?php

$config = array(
    'API_SCHEDULE_STATUS_OF_NOBEGIN' => 1,
    'API_SCHEDULE_STATUS_OF_PLAYING' => 2,
    'API_SCHEDULE_STATUS_OF_OVER' => 3,
    'API_SCHEDULE_STATUS_OF_FOLLOW' => 4,

    'JC_SCHEDULE_STATUS_LIST_NOBEGIN' => '0',
    'JC_SCHEDULE_STATUS_LIST_PLAYING' => '0_1_2_3_4_-11_-12_-13_-14',
    'JC_SCHEDULE_STATUS_LIST_OVER' => '-1_-10',

    'API_SCHEDULE_FOLLOW' => 1,
    'API_SCHEDULE_NO_FOLLOW' => 0,

    'API_HISTORY_RECORD' => 1,
    'API_RECENT_RECORD' => 2,
    'API_FUTURE_RECORD' => 3,

    'API_TOTAL_INTERGRAL' => 0,
    'API_HOME_INTERGRAL' => 1,
    'API_GUEST_INTERGRAL' => 2,

    'API_ASIA_ODDS' => 0,
    'API_EUROPE_ODDS' => 1,
	'API_BASEPOINT_ODDS' => 2,
		
    // 比赛状态 0:未开,1:上半场,2:中场,3:下半场,4,加时，-11:待定,-12:腰斩,-13:中断,-14:推迟,-1:完场，-10取消
    'SCHEDULE_STATUS_OF_NO_BEGIN' => 0,
    'SCHEDULE_STATUS_OF_FIRST_HALF' => 1,
    'SCHEDULE_STATUS_OF_MIDFIED' => 2,
    'SCHEDULE_STATUS_OF_SECOND_HALF' => 3,
    'SCHEDULE_STATUS_OF_OVER_TIME' => 4,
    'SCHEDULE_STATUS_OF_UNDETERMINED' => -11,
    'SCHEDULE_STATUS_OF_SCRAPPED' => -12,
    'SCHEDULE_STATUS_OF_BREAK' => -13,
    'SCHEDULE_STATUS_OF_DELAY' => -14,
    'SCHEDULE_STATUS_OF_END' => -1,
    'SCHEDULE_STATUS_OF_CANCEL' => -10,

    'API_EVENT_TYPE_OF_GOAL' => 0,
    'API_EVENT_TYPE_OF_PENALTY' => 1,
    'API_EVENT_TYPE_OF_OWN_GOAL' => 2,
    'API_EVENT_TYPE_OF_YELLOW_CARD' => 3,
    'API_EVENT_TYPE_OF_RED_CARD' => 4,
    'API_EVENT_TYPE_OF_TWO_YELLOW_BROWN' => 5,
    'API_EVENT_TYPE_OF_CHANGE_UP' => 6,
    'API_EVENT_TYPE_OF_CHANGE_DOWN' => 7,
    'API_EVENT_TYPE_OF_SCHEDULE_BEGIN' => 8,
    'API_EVENT_TYPE_OF_MIDFIED' => 9,
    'API_EVENT_TYPE_OF_SCHEDULE_END' => 10,
    'API_EVENT_TYPE_OF_ASSIST' => 11,

    'JC_EVENT_TYPE_OF_GOAL' => 1,
    'JC_EVENT_TYPE_OF_PENALTY' => 7,
    'JC_EVENT_TYPE_OF_OWN_GOAL' => 8,
    'JC_EVENT_TYPE_OF_YELLOW_CARD' => 3,
    'JC_EVENT_TYPE_OF_RED_CARD' => 2,
    'JC_EVENT_TYPE_OF_TWO_YELLOW_BROWN' => 9,
    'JC_EVENT_TYPE_OF_CHANGE' => 11,
    'JC_EVENT_TYPE_OF_MISSED_PENALTY' => 13, //Missed penalty

    'API_EVENT_TYPE_LIST' => array(
        1 => 0,
        7 => 1,
        8 => 2,
        3 => 3,
        2 => 4,
        9 => 5,
        11 => 6
    ),



    "JC_SCHEDULE_STATUS_LIST" => array(
//         '0' => '未开始',
        '0' => '未',
    	'1' => '上半场',
        '2' => '中场',
        '3' => '下半场',
        '4' => '加时',
        '-11' => '待定',
        '-12' => '腰斩',
        '-13' => '中断',
        '-14' => '推迟',
        '-1' => '完场',
        '-10' => '取消'
    ),

    'HOME_WIN_STR' => '胜',
    'HOME_EQUAL_STR' => '平',
    'HOME_FAIL_STR' => '负',

    'SCHEDULE_START_STR' => '比赛开始',
    'SCHEDULE_MIDFIED_STR' => '中场休息',
    'SCHEDULE_END_STR' => '比赛结束',
    'SCHEDULE_OVER_TIME_STR' => '加时',
);

if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
    $config['JC_REQUEST_URL'] = 'http://jcdata.tigercai.com/Home/Api/';
}elseif( get_cfg_var('PROJECT_RUN_MODE') == 'TEST' ){
    $config['JC_REQUEST_URL'] = 'http://jcdata.tigercai.com/Home/Api/';
}else {
    $config['JC_REQUEST_URL'] = 'http://jcdata.tigercai.com/Home/Api/';
}
return $config;