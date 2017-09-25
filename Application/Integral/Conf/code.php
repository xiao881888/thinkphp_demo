<?php
return array(
    // 错误码
    'ERROR_CODE' => array(
        'SUCCESS' => 0,
        'DATA_IS_INVALID'  => 1,
        'GOODS_OFF_SALE' => 2,
        'INTEGRAL_NOT_ENOUGH' => 3,
        'SERVER_EXCEPTION' => 4,
        'FUNCTION_NOT_EXIST' => 5,
        'NOT_DRAW_INFO' => 6,
        'USER_IS_RECEIVE' => 7,
        'USER_NOT_HAVE_THE_GOOD' => 8,
        'REQUEST_TOO_MANY' => 9,
        'INTEGRAL_USER_NOT_EXIST' => 10,
        'INTEGRAL_GOODS_EXCHANGE_TIMES_LIMIT' => 11,
    ),

    'ERROR_MSG' => array(
        'DATA_IS_INVALID'  => '数据不合法',
        'GOODS_OFF_SALE' => '商品下架',
        'INTEGRAL_NOT_ENOUGH' => '积分不足',
        'SERVER_EXCEPTION' => '服务器异常',
        'FUNCTION_NOT_EXIST' => '方法不存在',
        'NOT_DRAW_INFO' => '没有抽奖信息',
        'USER_IS_RECEIVE' => '用户已经领取',
        'USER_NOT_HAVE_THE_GOOD' => '用户没有该商品',
        'REQUEST_TOO_MANY' => '用户访问接口太频繁',
        'INTEGRAL_USER_NOT_EXIST' => '积分用户不存在',
        'INTEGRAL_GOODS_EXCHANGE_TIMES_LIMIT' => '每日兑换次数限制',
    ),

    'DRAW_GOOD_TYPE'  => array(
        'GRANT_COUPON' => 1,
        'DOUBLE_INTEGRAL' => 2,
        'NO_WINNING'  => 3,
    ),

    'GAIN_INTEGRAL_TYPE'  => array(
        'SIGN' => 1,
        'ORDER' => 2,
        'EXCHANGE'  => 3,
        'DRAW'  => 4,
        'VIP_GIFTS'  => 7,
    ),

    'SIGN_COUNT_GET_INTEGRAL'  => array(
        1 => 5,
        2 => 6,
        3 => 7,
        4 => 8,
        5 => 9,
        6 => 10,
    ),


);
