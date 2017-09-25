<?php
/**
 * @date 2014-11-04
 * @author tww <merry2014@vip.qq.com>
 */

if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
    $redis_config = array(
		'ALI_REDIS_HOST' => 'r-bp1ca26bee25d1c4.redis.rds.aliyuncs.com',
		'ALI_REDIS_PORT' => 6379,
        'REDIS_KEY' => 'integral:',
	);
}elseif( get_cfg_var('PROJECT_RUN_MODE') == 'TEST' ){
    $redis_config = array(
		'ALI_REDIS_HOST' => '123.56.221.173',
		'ALI_REDIS_PORT' => 6373,
        'REDIS_KEY' => 'integral:',
	);
}else {
    $redis_config = array(
		'ALI_REDIS_HOST' => '192.168.1.171',
		'ALI_REDIS_PORT' => 6379,
        'REDIS_KEY' => 'integral:',
	);
}

return $redis_config;
