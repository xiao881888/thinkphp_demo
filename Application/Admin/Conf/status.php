<?php
/**
 * @date 2014-12-2
 * @author tww <merry2014@vip.qq.com>
 */

return array(
	'Lottery'=>array(
			'ENABLE' 	=> 1,
			'DISABLE' 	=> 0,
			'DELETE' 	=> -1
	),
	'ORDER_STATUS' => array(
				ORDER_STATUS_DELETE		=> '客户端已删除',
				ORDER_STATUS_NOPAY 		=> '未付款',
				ORDER_STATUS_PAYNOOUT	=> '已付款未出票',
				ORDER_STATUS_OUTING		=> '出票中',
				ORDER_STATUS_OUTED 		=> '已出票',
				ORDER_STATUS_OUTFAIL 	=> '出票失败',
				ORDER_STATUS_TUIKUAN => '出票失败且退款',
				ORDER_STATUS_FAILE => '下单失败',
				ORDER_STATUS_PRINTOUTING_AND_PART_FAIL => '出票中（部分出票失败）',
				ORDER_STATUS_PRINTOUTED_AND_PART_FAIL => '已出票（部分出票失败）',
	),
	'ORDER_WINNINGS_STATUS'=>array(
			ORDER_WINNINGS_STATUS_NOTWINNING 	=>'未中奖',
			ORDER_WINNINGS_STATUS_WAIT			=>'待开奖',
			ORDER_WINNINGS_STATUS_WINNING 		=>'中奖',
			ORDER_WINNINGS_STATUS_PART_WINNING  => '部分派奖'
	),
	'TICKET_STATUS' => array(
			TICKET_STATUS_OF_DELETE		    => '已删除',
			TICKET_STATUS_OF_UN_PRINTOUT	=> '未出票',
			TICKET_STATUS_OF_PRINTOUTED 	=> '已出票',
			TICKET_STATUS_OF_PRINTOUT_FAIL 	=> '出票失败',
			TICKET_STATUS_OF_PRINTOUT_PAUSE => '暂停出票',
	),
	'WITHDRAW_STATUS'=>array(
			WITHDRAW_STATUS_NOVERIFY 	=> '未审核',
			WITHDRAW_STATUS_WAITPAY 	=> '待打款',
			WITHDRAW_STATUS_PAID 		=> '已打款',
			WITHDRAW_STATUS_REFUSE 		=> '拒绝',
			WITHDRAW_STATUS_REVOKE		=> '撤销',
			WITHDRAW_STATUS_DAIFU		=> '代付确认中'
	),
	
	'LOTTERY_MODEL'=>array(
			'1' => 'SsqTicket',
			'2' => 'Fc3dTicket',
			'3' => 'DltTicket',
			'4' => 'SdsyxwTicket',
			'5' => 'JlksTicket',
			'6' => 'JczqTicket',
			'7' => 'JclqTicket',
			'8' => 'AhsyxwTicket',
	        '9' => 'TjsyxwTicket',
			'10' => 'HebsyxwTicket',
			'11' => 'NmgsyxwTicket',
			'12' => 'JlsyxwTicket',
			'13' => 'HljsyxwTicket',
			'14' => 'ShsyxwTicket',
			'15' => 'JssyxwTicket',
			'16' => 'ZjsyxwTicket',
			'17' => 'FjsyxwTicket',
	        '18' => 'HbsyxwTicket',
	        '19' => 'JxksTicket',
	        '20' => 'ZcsfcTicket',
	        '21' => 'ZcsfcTicket',
	        '22' => 'JsksTicket',
			'601' => 'JczqTicket',
			'602' => 'JczqTicket',
			'603' => 'JczqTicket',
			'604' => 'JczqTicket',
			'605' => 'JczqTicket',
			'606' => 'JczqTicket',
			'701' => 'JclqTicket',
			'702' => 'JclqTicket',
			'703' => 'JclqTicket',
			'704' => 'JclqTicket',
			'705' => 'JclqTicket',
			'706' => 'JclqTicket',
	),

    'COBET_LOTTERY_MODEL'=>array(
        '1' => 'SsqTicket',
        '2' => 'Fc3dTicket',
        '3' => 'DltTicket',
        '4' => 'SdsyxwTicket',
        '5' => 'JlksTicket',
        '6' => 'JczqTicket',
        '7' => 'JclqTicket',
        '8' => 'AhsyxwTicket',
        '9' => 'TjsyxwTicket',
        '10' => 'HebsyxwTicket',
        '11' => 'NmgsyxwTicket',
        '12' => 'JlsyxwTicket',
        '13' => 'HljsyxwTicket',
        '14' => 'ShsyxwTicket',
        '15' => 'JssyxwTicket',
        '16' => 'ZjsyxwTicket',
        '17' => 'FjsyxwTicket',
        '18' => 'HbsyxwTicket',
        '19' => 'JxksTicket',
        '20' => 'ZcsfcTicket',
        '21' => 'ZcsfcTicket',
        '22' => 'JsksTicket',
        '601' => 'CobetJczqTicket',
        '602' => 'CobetJczqTicket',
        '603' => 'CobetJczqTicket',
        '604' => 'CobetJczqTicket',
        '605' => 'CobetJczqTicket',
        '606' => 'CobetJczqTicket',
        '701' => 'CobetJclqTicket',
        '702' => 'CobetJclqTicket',
        '703' => 'CobetJclqTicket',
        '704' => 'CobetJclqTicket',
        '705' => 'CobetJclqTicket',
        '706' => 'CobetJclqTicket',
    ),

	'BANK_TYPE'=>array(
			BANK_TYPE_UNKNOWN 	=> '未知',
			BANK_TYPE_CMB 		=> '招行',
			BANK_TYPE_ICBC 		=> '工行',
			BANK_TYPE_CCB 		=> '建行',
	),
	'CARD_STATUS'=>array(
			CARD_STATUS_NOVERIFY	=> '未认证',
			CARD_STATUS_VERIFIED	=> '已认证',
	),
	'CE_STATUS' => array(
			CE_STATUS_NODRAW 	=> '未领取',
			CE_STATUS_FAILURE	=> '失效',
			CE_STATUS_DRAWN	 	=> '已兑换'
	),
	'CC_STATUS' => array(
			CC_STATUS_NODRAW 	=> '未兑换',
			CC_STATUS_DRAWN	 	=> '已兑换'
	),
	'COUPON_STATUS' => array(
			COUPON_STATUS_FAILURE 		=> '作废',
			COUPON_STATUS_WAITING 		=> '待派发',
			COUPON_STATUS_DISTRIBUTION 	=> '已过期',
			COUPON_STATUS_NORMAL 		=> '可用',
            COUPON_STATUS_USED          => '已使用',
	),
	'RECHARGE_STATUS'=>array(
			RECHARGE_STATUS_NOTOACCOUNT => '未到账',
			RECHARGE_STATUS_TOACCOUNT 	=> '到账',
			RECHARGE_STATUS_PAYFAIL 	=> '支付失败',
	),
	'RECHARGE_SOURCE'=> array(
			RECHARGE_SOURCE_UNKNOWN => '未知',
			RECHARGE_SOURCE_IPHONE 	=> 'iPhone',
			RECHARGE_SOURCE_PC 		=> 'pc',
			RECHARGE_SOURCE_ADMIN 	=> '后台充值',
			RECHARGE_SOURCE_ANDROID => 'Android',
	),
	'PRIZE_STATUS'=> array(
			PRIZE_STATUS_NOPRIZE 			=> '未开奖',
			PRIZE_STATUS_WAITPRIZE 			=> '等待开奖',
			PRIZE_STATUS_WAITDISTRIBUTION 	=> '等待人工派奖',
			PRIZE_STATUS_PRIZED				=> '开奖完成'
	),	

	'ACCOUNT_OPERATOR_TYPE' => array(
			ACCOUNT_OPERATOR_TYPE_RECHARGE 		=> '充值',
			ACCOUNT_OPERATOR_TYPE_BET 			=> '下注',
			ACCOUNT_OPERATOR_TYPE_BUYCOUPON 	=> '购买红包',
			ACCOUNT_OPERATOR_TYPE_APPLYDRAW 	=> '提现申请',
			ACCOUNT_OPERATOR_TYPE_DRAW 			=> '提现打款',
			ACCOUNT_OPERATOR_TYPE_WINNING		=> '中奖',
			ACCOUNT_OPERATOR_TYPE_REFUSEDRAW	=> '拒绝提现'
	),	
	'FOLLOWBET_TYPE' => array(
			FOLLOWBET_TYPE_ISSUE 		=> '期数',
			FOLLOWBET_TYPE_PRIZE 		=> '中奖即停止',
			FOLLOWBET_TYPE_PRIZEAMOUNT 	=> '中奖特定金额'
	),

	'FOLLOWBET_STATUS' => array(
			FOLLOWBET_STATUS_DELETE 	=> '已删除',
			FOLLOWBET_STATUS_NOPAY  	=> '未付款',
			FOLLOWBET_STATUS_EXECUTION  => '进行中',
			FOLLOWBET_STATUS_NOSTART  	=> '未开始',
			FOLLOWBET_STATUS_CANCEL  	=> '取消',
	),
    
    'COUPON_VALID_DATE_TYPE' => array(
        COUPON_VALID_DATE_TYPE_FOREVER  => '永久',
        COUPON_VALID_DATE_TYPE_DAYS     => '有效时间',
        COUPON_VALID_DATE_TYPE_RANGE    => '范围',
    ),
    
    'COUPON_IS_SELL' => array(
        COUPON_IS_SELL_TRUE             => '可购买',
        COUPON_IS_SELL_FALSE            => '不可购买',
    ),
    
    'LOTTERY_STATUS'    => array(
        0   => '禁用',
        1   => '启用'
    ),
    'LOTTERY_IS_SHOW'   => array(
        0   => '隐藏',
        1   => '显示'
    ),
    
    'RECHARGE_CHANNEL_TYPE' => array(
        1   => '客户端内网页充值',
        2   => '客户端SDK充值',
        3   => '浏览器网页充值',
    ),
    'RECHARGE_CHANNEL_STATUS' => array(
        0   => '禁用',
        1   => '正常',
    ),
    
	'USER_STATUS' => array(
			'DISABLE'  => 0,
			'ENABLE'   => 1
	),
	'AC_STATUS' => array(
			'0' => '隐藏',
			'1' => '显示'
	),
	'MESSAGE_SEND_STATUS' => array(
		'0' => '失败',
		'1' => '成功'
	),
	'EVENT_SEND_TYPE' => array(
		1 => '邮件',
		2 => '短信'
	),'EVENT_LEVEL' => array(
		1 => '低',
		2 => '中',
		3 => '高'
	),'SCHEME_STATUS' => array(
		0 => '禁用',
		1 => '启用'
 	),'TASK_STATUS' => array(
 		0 => '初始状态',1 => '补票中',2 => '补票错误',3 => '已补票',4 => '撤票中',5 => '撤票错误',6 => '已撤票',7 => '算奖中',8 => '算奖错误',
 		9 => '已算奖',10 => '核奖中',11 => '核奖错误',12 => '已核奖',13 => '派奖中',14 => '派奖错误',15 => '人工派奖',16 => '人工派奖错误',17 => '已派奖',
 		
 	),'JC' => array(
    		'JCZQ' => 6,
    		'JCLQ' => 7,
	),'JCZQ' => array(
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
    'ACCOUNT_LOG' => array(
    		1 => '充值',
    		2 => '下注',
    		3 => '购买红包',
    		4 => '提现申请',
    		5 => '提现打款',
    		6 => '中奖',
    		7 => '拒绝提现',
    		8 => '取消追号',
    		9 => '撤票',
    		10 => '追号冻结',
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
    'COMMON_STATUS' => array(
            0   => '禁用',
            1   => '正常',
    ),
    'DEVICE_OS_TYPE_LIST' => array(
    		0 => '全部平台',
            1 => 'ANDROID',
            2 => 'IOS',
    ),
    'ACTIVITY_POSITION' => array(
            0 => '首页所有活动',
            1 => '充值活动',
            2 => '购买活动',
            3 => '完善身份证',
            4 => '跳转竞彩投注页面',
    ),
    'ADMIN_SMS_TEMPLTE_ID' => array(
    	'RESET_PASSWORD_MESSAGE' 	=> 90566,
    	'WITHDRAW_SUCCESS' 			=> 77052,
    	'WITHDRAW_REJECTED' 		=> 77050,
    	'WITHDRAW_PASSED'			=> 146307,
    	'RECHARGE_SUCCESS'      	=> 91195,
    	'DAIFU_FAILUE'				=> 93494,
    ),

    'ADMIN_BAIWAN_SMS_TEMPLTE_ID' => array(
        'RESET_PASSWORD_MESSAGE' 	=> 173947,
        'WITHDRAW_SUCCESS' 			=> 177927,
        'WITHDRAW_REJECTED' 		=> 173943,
        'WITHDRAW_PASSED'			=> 177927,
        'RECHARGE_SUCCESS'      	=> 173948,
        'DAIFU_FAILUE'				=> 174183,
    ),

    'ADMIN_NEW_SMS_TEMPLTE_ID' => array(
        'RESET_PASSWORD_MESSAGE' 	=> 174150,
        'WITHDRAW_SUCCESS' 			=> 177928,
        'WITHDRAW_REJECTED' 		=> 174149,
        'WITHDRAW_PASSED'			=> 177928,
        'RECHARGE_SUCCESS'      	=> 174152,
        'DAIFU_FAILUE'				=> 174182,
    ),
    
    'SCHEDULE_STATUS' => array(
            0 => '未开售',
            1 => '销售中',
            2 => '已截止',
            3 => '比赛结束',
            4 => '已兑奖',
            5 => '暂停销售',
            6 => '取消比赛',
            7 => '暂无比赛结果',
    ),
		
	'USER_BET_DRAFT_STATUS' => array(
		'DELETE' => -1,
		'NO_AVAILABLE' => 0,
		'AVAILABLE' => 1,
	),

    'ADMIN_USER_COUPON_LOG_TYPE_DESC' => array(
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

    'ADMIN_USER_COUPON_LOG_TYPE_MARK' => array(
        1 => '+',
        2 => '+',
        3 => '-',
        4 => '+',
        5 => '+',
        6 => '+',
        9 => '+',
        11=> '+',
        12=> '+',
        14=> '+',
        15=> '+',
        16=> '-',
        17=> '-',
        18=> '-',
        19=> '-',
        20=> '+',
        21=> '+',
        22=> '+',
        23=> '+',
    ),

    'ADMIN_ORDER_STATUS_DESC' => array(
        0 => '普通',
        1 => '追号',
        2 => '优化',
        3 => '合买',
    ),	
	
	'PLAY_TYPE_DESC' => array(
		'1'  => '标准玩法',
        '2'  => '追加投注',
        '11' => '直选',
        '12' => '组三',
        '13' => '组六',
        '21' => '任一',
        '22' => '任二',
        '23' => '任三',
        '24' => '任四',
        '25' => '任五',
        '26' => '任六',
        '27' => '任七',
        '28' => '任八',
        '29' => '前一',
        '30' => '前二直选',
        '31' => '前二组选',
        '32' => '前三直选',
        '33' => '前三组选',
        '34' => '乐选二',
        '35' => '乐选三',
        '36' => '乐选四',
        '37' => '乐选五',
        '41' => '和值',
        '42' => '三同号单选',
        '43' => '三同号通选',
        '44' => '三连号通选',
        '45' => '三不同号',
        '46' => '二同号单选',
        '47' => '二同号复选',
        '48' => '二不同号',
        '51' => '单关',
        '52' => '过关',
	),
    'COBET_SCHEME_STATUS' => array(
        COBET_SCHEME_STATUS_OF_NO_BEGIN		=> '未付款',
        COBET_SCHEME_STATUS_OF_NO_BEGIN_BOUGHT 		=> '已付款',
        COBET_SCHEME_STATUS_OF_ONGOING	=> '进行中',
        COBET_SCHEME_STATUS_OF_CANCEL		=> '已取消',
        COBET_SCHEME_STATUS_OF_CANCEL_REFUND 		=> '取消且退款',
        COBET_SCHEME_STATUS_OF_FAILED 	=> '已流单',
        COBET_SCHEME_STATUS_OF_FAILED_REFUND => '流单且退款',
        COBET_SCHEME_STATUS_OF_SCHEME_COMPLETE => '方案完成',
        COBET_SCHEME_STATUS_OF_PRINTOUT => '方案已出票',
    ),

);