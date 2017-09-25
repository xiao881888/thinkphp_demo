<?php
/**
 * @date 2014-11-04
 * @author tww <merry2014@vip.qq.com>
 */

if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
    $integral_db = array(
        'db_type'  => 'mysql',
        'db_user'  => 'tc_integral_svr',
        'db_pwd'   => 'FXM&3UR7Q9&oi4#e',
        'db_host'  => 'fzhcwlkjyxgs.mysql.rds.aliyuncs.com',
        'db_port'  => '3306',
        'db_name'  => 'tigercai_integral',
        'db_charset' => 'utf8',
        'db_prefix' => 'ti_',
    );
} else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
    $integral_db = array(
        'db_type'  => 'mysql',
        'db_user'  => 'tc_integral_test',
        'db_pwd'   => '2#H2M0Jd&gHAK0ID',
        'db_host'  => '123.56.221.173',
        'db_port'  => '3306',
        'db_name'  => 'tigercai_integral_test',
        'db_charset' => 'utf8',
        'db_prefix' => 'ti_',
    );
} else {
    $integral_db = array(
        'db_type'  => 'mysql',
        'db_user'  => 'root',
        'db_pwd'   => '123456',
        'db_host'  => '192.168.1.172',
        'db_port'  => '3306',
        'db_name'  => 'tigercai_integral',
        'db_charset' => 'utf8',
        'db_prefix' => 'ti_',
    );
}

return array(
    'INTEGRAL_DB_CONN'   => $integral_db,
);
