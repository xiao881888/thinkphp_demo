<?php
return array(
    'FOLLOW_BET_INFO_STATUS' => array(
        'NO_PAY' => 0,
        'ON_GOING' => 1,
        'PRIZE_STOP' => 2,
        'CANCEL' => 3,
        'ENDING' => 4,
    ),
    'FOLLOW_BET_INFO_API_STATUS' => array(
        'ON_GOING' => 1,
        'ENDING_PRIZE' => 2,
        'ENDING_NO_PRIZE' => 3,
        'CANCEL_PRIZE' => 4,
        'CANCEL_NO_PRIZE' => 5,
    ),
    'FOLLOW_BET_INFO_API_STATUS_DESC' => array(
        'ON_GOING' => '进行中',
        'ENDING_PRIZE' => '已结束已中奖',
        'ENDING_NO_PRIZE' => '已结束未中奖',
        'CANCEL_PRIZE' => '已取消已中奖',
        'CANCEL_NO_PRIZE' => '已取消未中奖',
    ),

    'FOLLOW_BET_DETAIL_STATUS' => array(
        'NO_FOLLOW' => 0,
        'FOLLOWED' => 1,
        'FOLLOWED_NO_PRINTOUT' => 2,
        'IS_CURRENT' => 1,
        'NO_CURRENT' => 0,
    ),
    'FOLLOW_BET_DETAIL_API_STATUS' => array(
        'NO_BEGIN' => 1,
        'WAITING_PRIZE' => 2,
        'PRIZE' => 3,
        'NO_PRIZE' => 4,
        'CANCEL' => 5,
    ),
    'FOLLOW_BET_DETAIL_API_STATUS_DESC' => array(
        'NO_BEGIN' => '未开始',
        'WAITING_PRIZE' => '待开奖',
        'PRIZE' => '中奖',
        'NO_PRIZE' => '未中奖',
        'CANCEL' => '已取消',
    ),


    'FOLLOW_BET_INFO_TYPE' => array(
        'FOLLOW_ISSUE' => 0,
        'PRIZE_STOP' => 1,
        'WIN_STOP_AMOUNT' => 2,
    ),

    'MONITOR_FOLLOW_LOTTERY_IDS' => array(4,8,18),

    'MONITOR_FOLLOW_DELAYED_TIME' => 840,

);
