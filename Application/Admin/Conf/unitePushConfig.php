<?php
/**
 * @date 2014-12-2
 */

if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
	return array(
		'APP_PUSH_ALL_API' => 'http://phone.api.tigercai.com/index.php?s=/Home/PushApi/index',
	);
}elseif( get_cfg_var('PROJECT_RUN_MODE') == 'TEST' ){
	return array(
		'APP_PUSH_ALL_API' => 'http://test.phone.api.tigercai.com/index.php?s=/Home/PushApi/index',
	);
}else {
	return array(
		'APP_PUSH_ALL_API' => 'http://192.168.1.171:81/index.php?s=/Home/PushApi/index',
	);
}


