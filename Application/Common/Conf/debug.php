<?php
/**
 * @date 2015-3-26
 * @author tww <merry2014@vip.qq.com>
 */
const FORMAL 		= 1;
const TEST_FORMAL 	= 2;
const TEST			= 3;
$server_conf = array(
		'phone.api.tigercai.com'=> FORMAL,
        'phone-api.tigercai.com'=> FORMAL,
		'mg.tigercai.com'		=> FORMAL,
		'118.178.8.65'			=> FORMAL,
		'prerelease-phone-api.tigercai.com' => FORMAL,
		'test.bee.tigercai.com' => TEST_FORMAL,
		'test.tigercai.com' 	=> TEST,
		'test.phone.api.tigercai.com' 	=> TEST_FORMAL ,
		'test.phone-api.tigercai.com' 	=> TEST_FORMAL ,
		'test-phone-api.tigercai.com' 	=> TEST_FORMAL ,
		'192.168.1.171:81' 	    => TEST,
		'localhost' 			=> TEST,
		'h5-api.tigercai.com' => FORMAL,
		'prerelease-h5-api.tigercai.com' => FORMAL,
		'test-h5-api.tigercai.com'  			=> TEST_FORMAL,
);

$conf_key = $server_conf[$_SERVER['HTTP_HOST']];

$printOurUrl_conf = array(
	FORMAL 			=> 'http://bee.tigercai.com/printout/notice',//外网线上
	TEST_FORMAL		=> 'http://test.bee.tigercai.com/printout/notice',//外网测试
	TEST			=> 'http://192.168.1.171:88/printout/notice',//内网测试
);

$startPrize_conf = array(
	FORMAL 			=> 'http://bee.tigercai.com/szc/prizeIssue',
	TEST_FORMAL		=> 'http://test.bee.tigercai.com/szc/prizeIssue',
	TEST			=> 'http://192.168.1.171:88/szc/prizeIssue',
);

$prize_conf	= array(
	FORMAL			=> 'http://bee.tigercai.com/szc/prizeIssue',
	TEST_FORMAL		=> 'http://test.bee.tigercai.com/szc/prizeIssue',
	TEST			=> 'http://192.168.1.171:88/szc/prizeIssue',
);
$distribute_conf 	= array(
	FORMAL			=> 'http://bee.tigercai.com/distribute/operation',
	TEST_FORMAL		=> 'http://test.bee.tigercai.com/distribute/operation',
	TEST			=> 'http://192.168.1.171:88/distribute/operation',
);

$auto_prize_lottery = array(
	FORMAL			=> array(1,2,3,4,5),
	TEST_FORMAL		=> array(1,2,3,4,5),
	TEST			=> array(1,2,3,4,5),
);

$szc_start_issue_conf 	= array(
	FORMAL			=> 'http://bee.tigercai.com/szc/startIssue',
	TEST_FORMAL		=> 'http://test.bee.tigercai.com/szc/startIssue',
	TEST			=> 'http://192.168.1.171:88/szc/startIssue',
);

$szc_reprintout_conf = array(
	FORMAL			=> 'http://bee.tigercai.com/reprintout/szc',
	TEST_FORMAL		=> 'http://test.bee.tigercai.com/reprintout/szc',
	TEST			=> 'http://192.168.1.171:88/reprintout/szc',
);
$jc_reprintout_conf	 = array(
	FORMAL			=> 'http://bee.tigercai.com/reprintout/jc',
	TEST_FORMAL		=> 'http://test.bee.tigercai.com/reprintout/jc',
	TEST			=> 'http://192.168.1.171:88/reprintout/jc',
);
$szc_revoke_conf 	 = array(
	FORMAL			=> 'http://bee.tigercai.com/revoke/szc',
	TEST_FORMAL		=> 'http://test.bee.tigercai.com/revoke/szc',
	TEST			=> 'http://192.168.1.171:88/revoke/szc',
);
$jc_revoke_conf 	 = array(
	FORMAL			=> 'http://bee.tigercai.com/revoke/jc',
	TEST_FORMAL		=> 'http://test.bee.tigercai.com/revoke/jc',
	TEST			=> 'http://192.168.1.171:88/revoke/jc',
);
$szc_prizenumber_conf = array(
	FORMAL			=> 'http://bee.tigercai.com/szc/updatePrizeNumberTrigger',
	TEST_FORMAL		=> 'http://test.bee.tigercai.com/szc/updatePrizeNumberTrigger',
	TEST			=> 'http://192.168.1.171:88/szc/updatePrizeNumberTrigger',
);
$szc_prizeScheme_conf = array(
	FORMAL			=> 'http://bee.tigercai.com/szc/updatePrizeSchemeTrigger',
	TEST_FORMAL		=> 'http://test.bee.tigercai.com/szc/updatePrizeSchemeTrigger',
	TEST			=> 'http://192.168.1.171:88/szc/updatePrizeSchemeTrigger',
);
$szc_prizeIssue_conf = array(
	FORMAL			=> 'http://bee.tigercai.com/szc/updatePrizeIssueTrigger',
	TEST_FORMAL		=> 'http://test.bee.tigercai.com/szc/updatePrizeIssueTrigger',
	TEST			=> 'http://192.168.1.171:88/szc/updatePrizeIssueTrigger',
);
$reprintout_orders_conf = array(
	FORMAL			=> 'http://bee.tigercai.com/reprintout/orders',
	TEST_FORMAL		=> 'http://test.bee.tigercai.com/reprintout/orders',
	TEST			=> 'http://192.168.1.171:88/reprintout/orders',
);
$revoke_orders_conf = array(
	FORMAL			=> 'http://bee.tigercai.com/revoke/orders',
	TEST_FORMAL		=> 'http://test.bee.tigercai.com/revoke/orders',
	TEST			=> 'http://192.168.1.171:88/revoke/orders',
);
$jc_start_schedule_conf = array(
    FORMAL			=> 'http://bee.tigercai.com/jc/startSchedule',
    TEST_FORMAL		=> 'http://test.bee.tigercai.com/jc/startSchedule',
    TEST			=> 'http://192.168.1.171:88/jc/startSchedule',
);
$jc_schedule_odds_trigger_conf = array(
    FORMAL			=> 'http://bee.tigercai.com/jc/schedulesOddsTrigger',
    TEST_FORMAL		=> 'http://test.bee.tigercai.com/jc/schedulesOddsTrigger',
    TEST			=> 'http://192.168.1.171:88/jc/schedulesOddsTrigger',
);
$jc_result_trigger_conf = array(
	FORMAL			=> 'http://bee.tigercai.com/jc/scheduleResultTrigger',
	TEST_FORMAL		=> 'http://test.bee.tigercai.com/jc/scheduleResultTrigger',
	TEST			=> 'http://192.168.1.171:88/jc/scheduleResultTrigger',
);
$jc_prize_trigger_conf = array(
	FORMAL			=> 'http://bee.tigercai.com/jc/prizeResultTrigger',
	TEST_FORMAL		=> 'http://test.bee.tigercai.com/jc/prizeResultTrigger',
	TEST			=> 'http://192.168.1.171:88/jc/prizeResultTrigger',
);
$integral_url_conf = array(
	FORMAL			=> 'http://phone.api.tigercai.com/Integral',
	TEST_FORMAL		=> 'http://test.phone.api.tigercai.com/Integral',
	TEST			=> 'http://192.168.1.171:81/index.php?s=Integral',
);

return array(
		'PRINT_OUT_TICKET_URL' 	=> $printOurUrl_conf[$conf_key],
		'START_PRIZE_URL'		=> $startPrize_conf[$conf_key],
		'AUTO_PRIZE_LOTTERY'	=> $auto_prize_lottery[$conf_key],
		'PRIZE_URL'				=> $prize_conf[$conf_key],
		'DISTRIBUTE_URL'		=> $distribute_conf[$conf_key],
		'REPRINTOUT_ORDERS_URL'	=> $reprintout_orders_conf[$conf_key],
		'REVOKE_ORDERS_URL'	    => $revoke_orders_conf[$conf_key],
		'SZC_BEE' 	=> array(
			'START_ISSUE'		=> $szc_start_issue_conf[$conf_key],
			'REPRINTTOUT'		=> $szc_reprintout_conf[$conf_key],
			'REVOKE'			=> $szc_revoke_conf[$conf_key],
			'PRIZENUMBER'		=> $szc_prizenumber_conf[$conf_key],
			'PRIZESCHEME'		=> $szc_prizeScheme_conf[$conf_key],
			'PRIZEISSUE'		=> $szc_prizeIssue_conf[$conf_key]
		),
		'JC_BEE'	=> array(
			'START_SCHEDULE'    => $jc_start_schedule_conf[$conf_key],
			'SCHEDULE_ODDS_TRIGGER' => $jc_schedule_odds_trigger_conf[$conf_key],
			'REPRINTTOUT'		=> $jc_reprintout_conf[$conf_key],
			'REVOKE'			=> $jc_revoke_conf[$conf_key],
		    'RESULT_TRIGGER'    => $jc_result_trigger_conf[$conf_key],
		    'PRIZE_TRIGGER'     => $jc_prize_trigger_conf[$conf_key],
		),
		'INTEGRAL_URL' => $integral_url_conf[$conf_key],
);

