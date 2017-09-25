<?php

return array(
    'INTEGRAL_ERROR_CODE' => array(
        'GOODS_OFF_SALE' => 2,  //商品已下架
        'INTEGRAL_NO_ENOUGH' => 3,  //积分不足
        'USER_IS_RECEIVE' => 7,
        'USER_NOT_HAVE_THE_GOOD' => 8,
        'REQUEST_TOO_MANY' => 9,
        'INTEGRAL_USER_NOT_EXIST' => 10,
        'INTEGRAL_GOODS_EXCHANGE_TIMES_LIMIT' => 11,
    ),
    'INTEGRAL_ERROR_MSG' => array(
        'GOODS_OFF_SALE' => '商品已下架',
        'INTEGRAL_NO_ENOUGH' => '积分不足',  //
        'USER_IS_RECEIVE' => '用户已领取',
        'USER_NOT_HAVE_THE_GOOD' => '奖品不属于该用户',
        'REQUEST_TOO_MANY' => '用户访问接口太频繁',
        'INTEGRAL_USER_NOT_EXIST' => '积分用户不存在',
        'INTEGRAL_GOODS_EXCHANGE_TIMES_LIMIT' => '每日兑换次数限制',
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

    'USER_DRAW_GOOD_TYPE'  => array(
        'GRANT_COUPON' => 1,
        'DOUBLE_INTEGRAL' => 2,
        'NO_WINNING'  => 3,
    ),

    'INTEGRAL_GOOD_GROUP_TYPE'  => array(
        'COUPON' => 1,
        'GOOD' => 2,
    ),

    'INTEGRAL_GOOD_GROUP_NAME'  => array(
        'COUPON' => '红包',
        'GOOD' => '实物商品',
    ),

    'USER_INTEGRAL_EVENT_TYPE'  => array(
        1 => '签到获得',
        2 => '下单获得',
        3 => '积分兑换',
        4 => '签到抽奖',
        7 => '礼包发放',
        8 => '积分转盘抽奖',
        9 => '积分转盘中奖',
    ),

    'USER_INTEGRAL_CHANGE_TYPE'  => array(
        1 => '+',
        2 => '+',
        3 => '-',
        4 => '+',
        7 => '+',
        8 => '-',
        9 => '+',
    ),
);

