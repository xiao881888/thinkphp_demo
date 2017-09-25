<?php
/**
 * @date 2014-11-04
 * @author tww <merry2014@vip.qq.com>
 */

if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
    $tiger_db = array(
        'db_type'  => 'mysql',
        'db_user'  => 'tigercai_server',
        'db_pwd'   => 'e4huY8J7e4',
        'db_host'  => 'fzhcwlkjyxgs.mysql.rds.aliyuncs.com',
        'db_port'  => '3306',
        'db_name'  => 'tigercai',
        'db_charset' => 'utf8',
        'db_prefix' => 'cp_',
    );
} else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
    $tiger_db = array(
        'db_type'  => 'mysql',
        'db_user'  => 'tigercai_test',
        'db_pwd'   => 'MB8&7Y7cL$zWfC3@FM6',
        'db_host'  => '123.56.221.173',
        'db_port'  => '3306',
        'db_name'  => 'tigercai_test',
        'db_charset' => 'utf8',
        'db_prefix' => 'cp_',
    );
} else {
    $tiger_db = array(
        'db_type'  => 'mysql',
        'db_user'  => 'root',
        'db_pwd'   => '123456',
        'db_host'  => '192.168.1.172',
        'db_port'  => '3306',
        'db_name'  => 'lottery_test',
        'db_charset' => 'utf8',
        'db_prefix' => 'cp_',
    );
}

return array(
    'TIGER_DB_CONN'   => $tiger_db,
);
