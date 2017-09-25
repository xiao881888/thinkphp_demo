<?php

if(defined('MODE_NAME') && MODE_NAME=='CLI'){
	return array(
			'xhprof_switch_open' => true,
			'random_range' => 1,
			'data_class_suffix' => 'XhprofData',
			'db_type' => 'mysql',
			'db_dsn' => 'mysql:dbname=xhtest;host=192.168.1.91;charset=utf8',
			'db_user' => 'root',
			'db_pass' => '',
			'db_options' => array(
					PDO::ATTR_PERSISTENT => false,
					PDO::ATTR_EMULATE_PREPARES => false
			),
			'run_default_tbname' => 'xhprof_total_run',
			'detail_default_tbname' => 'xhprof_total_run_details',
			'run_tbname_hash' => '',
			'detail_tbname_hash' => '',
			'redis_host' => '192.168.1.91',
			'redis_port' => '6379',
			'details_queue_name' => 'xhprof_new_details:total'
	);
}
if (isset($_SERVER['DeveloperMode']) && $_SERVER['DeveloperMode'] == 2) {
	return array(
			'xhprof_switch_open' => true,
			'random_range' => 1,
			'data_class_suffix' => 'XhprofData',
			'db_type' => 'mysql',
			'db_dsn' => 'mysql:dbname=xhtest;host=192.168.1.91;charset=utf8',
			'db_user' => 'root',
			'db_pass' => '',
			'db_options' => array(
					PDO::ATTR_PERSISTENT => false,
					PDO::ATTR_EMULATE_PREPARES => false 
			),
			'run_default_tbname' => 'xhprof_total_run',
			'detail_default_tbname' => 'xhprof_total_run_details',
			'run_tbname_hash' => '',
			'detail_tbname_hash' => '',
			'redis_host' => '192.168.1.91',
			'redis_port' => '6379',
			'details_queue_name' => 'xhprof_new_details:total' 
	);
} elseif (isset($_SERVER['DeveloperMode']) && $_SERVER['DeveloperMode'] == 1) {
	return array(
			'xhprof_switch_open' => true,
			'random_range' => 1,
			'data_class_suffix' => 'XhprofData',
			'db_type' => 'mysql',
			'db_dsn' => 'mysql:dbname=xhtest;host=localhost;charset=utf8',
			'db_user' => 'root',
			'db_pass' => '',
			'db_options' => array(
					PDO::ATTR_PERSISTENT => false,
					PDO::ATTR_EMULATE_PREPARES => false 
			),
			'run_default_tbname' => 'xhprof_total_run',
			'detail_default_tbname' => 'xhprof_total_run_details',
			'run_tbname_hash' => '',
			'detail_tbname_hash' => '',
			'redis_host' => '127.0.0.1',
			'redis_port' => '6379',
			'details_queue_name' => 'xhprof_new_details:total' 
	);
} else {
	return array(
			'xhprof_switch_open' => true,
			'random_range' => 1,
			'data_class_suffix' => 'XhprofData',
			'db_type' => 'mysql',
			'db_dsn' => 'mysql:dbname=xhprof;host=fzhcwlkjyxgs.mysql.rds.aliyuncs.com;charset=utf8',
			'db_user' => 'xhprof_server',
			'db_pass' => 'wPJFGZ9dYxLwpdD7',
			'db_options' => array(
					PDO::ATTR_PERSISTENT => false,
					PDO::ATTR_EMULATE_PREPARES => false 
			),
			'run_default_tbname' => 'xhprof_total_run',
			'detail_default_tbname' => 'xhprof_total_run_details',
			'run_tbname_hash' => '',
			'detail_tbname_hash' => '',
			'redis_host' => '10.168.249.111',
			'redis_port' => '6375',
			'details_queue_name' => 'xhprof_new_details:total' 
	);
}
