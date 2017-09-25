<?php

if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
    $baiwan_package_name_list = array(
        'com.baiwan.caipiao',
    );
    $new_package_name_list = array(
        'com.xincai.tigerlottery',
        'com.yingqiu.xincai',
    );
} else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
    $baiwan_package_name_list = array(
        'com.tiger.TigerLotteryInnerBeta1',
        'com.baiwan.caipiao',
    );
    $new_package_name_list = array(
        'com.xincai.tigerlottery',
        'com.yingqiu.xincai',
    );
} else {
    $baiwan_package_name_list = array(
        'com.tiger.TigerLotteryInnerBeta1',
        'com.baiwan.caipiao',
    );
    $new_package_name_list = array(
        'com.xincai.tigerlottery',
        'com.yingqiu.xincai',
    );
}

return array(
    'APP_ID_LIST' => array(
        'TIGER' => 1,
        'BAIWAN' => 2,
        'NEW' => 3,
    ),
    'TIGER_PACKAGE_NAME_LIST' 	=> array(),

	'BAIWAN_PACKAGE_NAME_LIST' 	=> $baiwan_package_name_list,
    'BAIWAN_CHANNEL_TYPE' => 5,
    'BAIWAN_CHANNEL_ID' => 1,


    'NEW_PACKAGE_NAME_LIST' 	=> $new_package_name_list,
    'NEW_CHANNEL_TYPE' => 4,
    'NEW_CHANNEL_ID' => 1,
);