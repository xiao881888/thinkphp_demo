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
	'ORDER_STATUS'=>array(
			'-1'=>'已删除',
			'0' =>'未付款',
			'1'	=>'已付款未出票',
			'2'	=>'出票中',
			'3'	=>'已出票',
			'4'	=>'出票失败',
			'5' => '出票失败且退款',
			'6' => '下单失败'

	),
	'ORDER_WINNINGS_STATUS'=>array(
			'0' =>'未中奖',
			'1' =>'中奖'
	),
	'WITHDRAW_STATUS'=>array(
			'0' => '未审核',
			'1' => '待打款',
			'2' => '已打款',
			'3' => '拒绝',
			'4'	=> '撤销'
	),
	'WITHDRAW_STATUS_ACTION'=>array(
			'0' => array('1'=>'通过','3'=>'拒绝'),
			'1' => array('2'=>'打款','4'=>'撤销')
	),

	'LOTTERY_MODEL'=>array(
			'1' => 'SsqTicket',
			'2' => 'DltTicket',
			'3' => '',
			'4' => '',
			'5' => '',
			'6' => '',
			'7' => '',
	),
	'BANK_TYPE'=>array(
			'0' => '未知',
			'1' => '招行',
			'2' => '工行',
			'3' => '建行',
	),
	'CARD_STATUS'=>array(
			'0' => '未认证',
			'1' => '已认证',
	),
	'CE_STATUS' => array(
			'-1' => '未领取',
			'0'	 => '失效',
			'1'	 => '已兑换'
	),
	'COUPON_STATUS' => array(
			'0' => '不可使用',
			'1' => '等待派发',
			'2' => '已派发',
			'3' => '可用',
	),
	'RECHARGE_STATUS'=>array(
			'0' => '未到账',
			'1' => '到账',
			'2' => '支付失败',
	),
	'RECHARGE_SOURCE'=> array(
			'0' => '未知',
			'1' => 'iPhone',
			'2' => 'pc',
			'3' => '后台充值',
			'4' => 'Android'
	),
	'PRIZE_STATUS'=> array(
			'0' => '未开奖',
			'1' => '等待开奖',
			'2' => '等待人工派奖',
			'3'	=> '开奖完成'
	),
	
	'ACCOUNT_OPERATOR_TYPE'=>array(
			'1' => '支出',
			'2' => '收入',
			'3' => '冻结',
			'4' => '解冻',
			'5' => '提现'
	),	
	
	//======
// 	ACCOUNT_OPERATOR_TYPE_xx1 = 1,
// 	ACCOUNT_OPERATOR_TYPE_xx2 = 2,	
// 	ACCOUNT_OPERATOR_TYPE_xx3 = 3,	
// 	'ACCOUNT_OPERATOR_TYPE'=>array(
// 			ACCOUNT_OPERATOR_TYPE_xx1 => '支出',
// 			ACCOUNT_OPERATOR_TYPE_xx2 => '收入',
// 			ACCOUNT_OPERATOR_TYPE_xx3 => '冻结',
// 			'4' => '解冻',
// 			'5' => '提现'
// 	),
	
// 	'ACCOUNT_OPERATOR_TYPE'=>array(
// 		1=>'ZHICHU',
// 		2=>'SHOURU'
// 	),
	
// 	ARRAY(
// 	C('ACCOUNT_OPERATOR_TYPE.ZHICHU')=>'支出',
// 	C('SHOURU')=>'收入'	
	
// 	);
	//======
	
	'FOLLOWBET_TYPE' => array(
			'0' => '期数',
			'1' => '中奖即停止',
			'2' => '中奖特定金额'
	),
	'FOLLOWBET_STATUS' => array(
			'-1' => '已删除',
			'0'  => '未付款',
			'1'  => '进行中',
			'2'  => '未开始',
			'3'  => '取消',
	),
	'USER_STATUS' => array(
			'DISABLE'  => 0,
			'ENABLE'   => 1
	)		
	
);