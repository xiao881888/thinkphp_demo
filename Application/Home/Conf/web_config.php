<?php
return array(
		'WEB_PAY_MESSAGE' => array(
				'NETWORK_ERROR' => '此次订单提交已过期，请返回重新投注',
				'PARAMS_ERROR' => '提交数据有误，请重试',
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
				'ORDER_MONEY_TOO_SMALL' => '订单金额不满足红包至少消费金额' 
		),
		'WEB_IDENTIFY_MESSAGE' => array(
				'IDENTITY_BIND_TOO_MUCH' => '该身份证号已被其他用户认证',
				'DATABASE_ERROR' => '实名认证失败，请重试',
		) 
);
