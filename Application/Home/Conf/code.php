<?php
return array(
		// 错误码
		'ERROR_CODE' => array(
				'SUCCESS' => 0,
				'PRINTOUT_SUCCESS' => 0,
				'HEADER_ERROR' => 1,
				'SESSION_ERROR' => 2,
				'INVALID_INTERFACE' => 3,
				'DECODE_ERROR' => 4,
				'PARAM_ERROR' => 5, // 传入不存在的参数
				'PARAM_INVALID' => 6, // 数据类型错误，超出边界值
				'ENCODE_ERROR' => 7,
				'DATABASE_ERROR' => 12,
				'SMS_VERIFY_ERROR' => 10005, // 短信验证码错误
				'SMS_SEND_ERROR' => 1010202, // 短信发送失败
				'SMS_LIFETIME_ERROR' => 10004, // 短信验证码过期
				'USER_NO_LOGIN' => 10003, // 用户还未登录
				
				'INSUFFICIENT_FUND' => 10006, // 余额不足
				'ORDER_NO_EXIST' => 10008, // 订单不存在

                'INTEGRAL_USER_NO_EXIST' => 10009, // 积分用户不存在
                'REQUEST_INTEGRAL_API_TOO_MANY' => 10010, // 访问积分接口太频繁
				
				'PUBLIC_KEY_INVALID' => 1010101,
				'PRIVATE_KEY_INVALID' => 1010102,

                'SIGN_IS_ERROR' => 1010801,
                'UPLOAD_PIC_IS_ERROR' => 1010802,
                'TYPE_INVALID' => 1010803,
                'UPLOAD_DATA_ERROR' => 1010804,
				
				'USER_FORBIDDEN' => 1020102, // 用户被禁用
				'USER_NOT_EXIST' => 1020103, // 用户不存在
				'TELEPHONE_ERROR' => 1020104, // 电话格式错误
				'PASSWORD_ERROR' => 1020301, // 密码错误
				'TELEPHONE_REGISTED' => 1020304, // 电话号码已经注册
				'PASSWORD_FORMAT_ERROR' => 1020403, // 密码不符合要求（格式方面）
				'HAS_CHECK_BANK_CARD' => 1020801, // 银行卡信息已经验证过
				'BANK_CARD_BIND_TOO_MUCH' => 1020803, //银行卡信息绑定次数超过限制
				'HAS_NO_ID_CARD' => 1020802, // 银行卡绑定先绑定身份证
				
				'HAS_CHECK_IDENTITY' => 1021401, // 身份证已经验证过

				'IDENTITY_BIND_TOO_MUCH' => 1021402, //同一身份证号绑定次数超过限制
			
		        'OUT_OF_SCHEDULE_TIME'=> 1021601, //对阵已截止
		        'ID_NO_EXIST' => 1021801, // 方案不存在

                'SAVE_USER_AVATAR_FAIL' => 111401,  //头像修改失败

                'SAVE_USER_NICK_NAME_FAIL' => 1022301,  //昵称修改失败
                'NICK_NAME_IS_EXIST' => 1022302,//昵称已存在
		    
				'ISSUE_NO_EXIST' => 1030101, // 彩期号不存在
				'LOTTERY_NO_EXIST' => 1030201, // 彩种不存在
				
				'RECHARGE_NO_EXIST' => 1040401, // 充值记录不存在
				'RECHARGE_OWEN_ERROR' => 1040402, // 充值订单不属于此用户
				'PAY_PASSWORD_ERROR' => 1040201, // 支付密码错误
				
				'RECHARGE_CHANNEL_INVALID' => 1040302, // 充值渠道不存在或者停用
				'BANK_RECHARGE_CHANNEL_NEED_IDENTIFY' => 1040303, // 银行卡充值渠道需要先认证
				
				
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
				'OVER_TICKET_COMBINATION_LIMIT' => 1050115, // 单倍方案超过投注限制，请重新选择投注选项 
				
				'ORDER_DETAIL_NO_EXIST' => 1050301, // 订单详情不存在
				
				'RECHARGE_NO_RECEIVE' => 1050505,  // 充值未到账
				'ORDER_OWEN_ERROR' => 1050506, // 订单不属于此用户
				'ORDER_STATUS_ERROR' => 1050507, // 订单状态错误
				
				'FROZEN_BALANCE_NO_ENOUGH' => 1050601, // 冻结的余额不足
				'FOLLOW_BET_CAN_NOT_CANCEL' => 1050602, // 追号不允许取消
				
				'SCHEDULE_NO_ERROR' => 1050701, // 对阵期号无效
				
				'ISSUE_NO_START' => 1051101, // 彩期未开售

                'PACKAGES_NO_EXIST' => 1051401, // 套餐不存在
				
				'COUPON_NO_EXIST' => 1060101, // 红包不存在
				'COUPON_INVALID' => 1060102, // 红包无效
				'COUPON_EXCHANGE_NO_EXIST' => 1060201, // 兑换码无效
				'COUPON_EXCHANGE_INVALID' => 1060202, // 兑换码已失效
				'THIS_COUPON_IS_EXCHANGEED' => 1060203, // 您已经兑换过该类红包，不能再次兑换
				'COUPON_IS_NOT_FOR_YOU' => 1060204, // 您不符合该红包领取条件，兑换失败
				'COUPON_ERROR_FOR_LIMIT_LOTTERY_IDS' => 1060205, //当前订单的彩种不允许使用该红包
				'COUPON_ERROR_FOR_ORDER_MIN_CONSUME' => 1060206,  //订单金额不满足红包至少消费金额
                'COUPON_EXCHANGE_LIMIT_TIMES' => 1060207,  //超过兑换次数

                'DEVICE_TOKEN_IS_NULL' => 1010601,  //没有传设备TOKEN
                'POST_CONFIG_INVALID' => 1010602,  //传送的配置不合法

                'INTEGRAL_ERROR_GOODS_OFF_SALE' => 1090301,  //商品已下架
                'INTEGRAL_ERROR_INTEGRAL_NO_ENOUGH' => 1090302,  //积分不足
                'INTEGRAL_GOODS_EXCHANGE_TIMES_LIMIT' => 1090303,

                'INTEGRAL_ERROR_USER_IS_RECEIVE' => 1090501,  //用户已领取
                'INTEGRAL_ERROR_USER_NOT_HAVE_THE_GOOD' => 1090502,  //用户没有该奖品

                'SCHEME_UNIT_IS_NOT_ENOUGH' => 1100601,  //份数不足
                'SCHEME_STATUS_IS_END' => 1100602,  //方案截止
                
				'SCHEME_IS_BOUGHT_BY_OTHERS' => 1100701,  //其他用户已认购

				
				)
);
