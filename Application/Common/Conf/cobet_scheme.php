<?php
define('COBET_SCHEME_STATUS_OF_NO_BEGIN', 0); // 未付款
define('COBET_SCHEME_STATUS_OF_NO_BEGIN_BOUGHT', 1); // 已付款
define('COBET_SCHEME_STATUS_OF_ONGOING', 2); //进行中
define('COBET_SCHEME_STATUS_OF_CANCEL', 3); //合买进行中
define('COBET_SCHEME_STATUS_OF_CANCEL_REFUND', 4); //认购完成
define('COBET_SCHEME_STATUS_OF_FAILED', 5); //认购失败，流单
define('COBET_SCHEME_STATUS_OF_FAILED_REFUND', 6); //认购失败且退款
define('COBET_SCHEME_STATUS_OF_SCHEME_COMPLETE', 7); //认购完成并出票
define('COBET_SCHEME_STATUS_OF_PRINTOUT', 8); //认购完成但出票失败

define('COBET_TYPE_OF_BOUGHT', 0); //认购
define('COBET_TYPE_OF_GUARANTEE', 1); //保底
define('COBET_TYPE_OF_GUARANTEE_FROZEN', 2); //保底扣款

define('COBET_STATUS_OF_CONSUME', 1); //已消费
define('COBET_STATUS_OF_REFUND', 2); //已退款
define('COBET_STATUS_OF__PART_REFUND', 3); //部分退款

return array(
	'COBET_SCHEME_STATUS' => array(
	    'NO_BEGIN' => 0,
        'NO_BEGIN_BOUGHT' => 1,
        'ONGOING' => 2,
        'CANCEL' => 3,
        'CANCEL_REFUND' => 4,
        'FAILED' => 5,
        'FAILED_REFUND' => 6,
        'SCHEME_COMPLETE' => 7,
        'PRINTOUT' => 8,
    ),

    'API_COBET_SCHEME_STATUS' => array(
        'ONGOING' => 12,
        'FAILED' => 13,
        'FAILED_REFUND' => 14,
        'CANCEL' => 15,
        'CANCEL_REFUND' => 16,
    ),

    'API_COBET_SCHEME_STATUS_DESC' => array(
        'ONGOING' => '进行中',
        'FAILED' => '流单',
        'FAILED_REFUND' => '流单并退款',
        'CANCEL' => '发起人撤单',
        'CANCEL_REFUND' => '发起人撤单',
    ),

    'COBET_TYPE' => array(
        'BOUGHT' => 0,
        'GUARANTEE' => 1,
        'GUARANTEE_FROZEN' => 2,
    ),

    'COBET_SCHEME_AHEAD_END_TIME' => 15*60,

    'COBET_HISTORY_RECORD_DESC' => '近%s中%s',

);
