<?php
/**
 * @date 2014-11-04
 * @author tww <merry2014@vip.qq.com>
 */

if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
	return array(
		# 数据库配置
		'DB_TYPE'   => 'mysqli', // 数据库类型
		'DB_PORT'   => '3306', // 端口
		'DB_PREFIX' => 'ti_', // 数据库表前缀
		'DB_HOST'   => 'fzhcwlkjyxgs.mysql.rds.aliyuncs.com', // 服务器地址
		'DB_NAME'   => 'tigercai_integral', // 数据库名
		'DB_USER'   => 'tc_integral_svr', // 用户名
		'DB_PWD'    => 'FXM&3UR7Q9&oi4#e',  // 密码
	);

} else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
    return array(
        # 数据库配置
        'DB_TYPE'   => 'mysqli', // 数据库类型
        'DB_PORT'   => '3306', // 端口
        'DB_PREFIX' => 'ti_', // 数据库表前缀
        'DB_HOST'   => '123.56.221.173', // 服务器地址
        'DB_NAME'   => 'tigercai_integral_test', // 数据库名
        'DB_USER'   => 'tc_integral_test', // 用户名
        'DB_PWD'    => '2#H2M0Jd&gHAK0ID',  // 密码
    );
} else {
	return array(
			# 数据库配置
			'DB_TYPE'   => 'mysqli', // 数据库类型
			'DB_PORT'   => '3306', // 端口
			'DB_PREFIX' => 'ti_', // 数据库表前缀
			'DB_HOST'   => '192.168.1.172', // 服务器地址
			'DB_NAME'   => 'tigercai_integral', // 数据库名
			'DB_USER'   => 'root', // 用户名
			'DB_PWD'    => '123456',  // 密码
	);
}
