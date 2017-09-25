<?php
return array(
    // 错误码
    'ERROR_CODE' => array(
        'SUCCESS' => 0,
        'FAIL' => 1,
        'DATA_IS_INVALID'  => 1,
        'SIGN_ERROR' => 2,
        'REDIS_KEY_IS_EXIST' => 3,
        'PRINTOUT_SUCCESS' => 0,
        'USER_COUPON_ERROR' => 1050109, // 红包错误
        'COUPON_ERROR_FOR_LIMIT_LOTTERY_IDS' => 1060205, //当前订单的彩种不允许使用该红包
        'COUPON_ERROR_FOR_ORDER_MIN_CONSUME' => 1060206,  //订单金额不满足红包至少消费金额

        'BET_NUMBER_ERROR' => 1050102, // 选择号码错误
        'TICKET_ERROR' => 1050103, // 彩票错误（彩期、玩法，选号方式）
        'OUT_OF_ISSUE_TIME' => 1050104, // 彩期已截止
        'STAKE_COUNT_NO_EQUAL' => 1050105, // 注数错误
        'TOTAL_AMOUNT_NO_EQUAL' => 1050106, // 价格不一致
        'DEDUCT_MONEY_ERROR' => 1050107, // 扣款失败
        'NEED_PAY_PASSWORD' => 1050108, // 需要验证支付密码
        'USER_COUPON_ERROR' => 1050109, // 红包错误
        'PRINT_OUT_TICKET_ERROR' => 1050110, // 出票失败
        'BET_ERROR' => 1050111, // 下单失败
        'OVER_TICKET_LIMIT' => 1050112, // 超出单票最大投注金额

        'JC_SERIES_TYPE_ERROR' => 1050113, // 串关方式不合法
        'JC_SCHEDULE_OVER_MAX_NUMBER_ERROR' => 1050114, // 超过最大串关数量
    ),

    'ERROR_MSG' => array(
        'DATA_IS_INVALID'  => '数据不合法',
        'SIGN_ERROR' => '签名错误',
        'REDIS_KEY_IS_EXIST' => 'key重复',
    ),

    'WEB_PAY_MESSAGE' => array(
        'NETWORK_ERROR' => '此次订单提交已过期，请返回重新投注',
        'IS_PAID' => '该订单已经支付过，不需要重复提交',
        'LACK_OF_MONEY' => '余额不足，请先返回充值',
        'TICKET_LIST_EMPTY' => '订单数据已过期，请返回重新投注',
        'TICKET_SCHEME_ERROR' => '该订单方案拆票明细数量过多，为了保证出票效率，需要调整投注方案，请您返回重新投注',
        'PAY_FAILED' => '订单支付失败，请返回重新支付订单',
        'ALREADY_FAILED' => '订单已出票失败，请返回重新投注',
        'OUT_OF_TIME' => '对阵已截止投注，请返回重新投注',
        'COUPON_NOT_USEABLE' => '您使用的红包无效，请先返回充值',
        'COUPON_NOT_ENOUGH' => '红包的可用余额不足，请先返回充值',
        'ORDER_INFO_ERROR' => '订单信息错误，请返回重新投注',
        'SZC_ISSUE_NO_EXISTS' => '当前彩期暂时不可投注，请返回重新投注',
        'SZC_LOTTERY_NO_EXISTS' => '当前彩种目前不可投注，请返回重新投注',
        'SZC_OUT_OF_ISSUE_TIME' => '当前彩期已截止投注，请返回重新投注',
        'ISSUE_NO_START' => '当前彩期未开售',
        'ORDER_LIMIT_LOTTERY' => '当前订单的彩种不允许使用该红包',
        'ORDER_MONEY_TOO_SMALL' => '订单金额不满足红包至少消费金额',
        'LIMIT_NUMBER' => '投注号码当前限号',
	'ISSUE_NO_START' => '当前彩期未开售',
        'PACKAGES_NO_EXIST' => '套餐不存在',
    ),
);
