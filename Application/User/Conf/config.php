<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

/**
 * UCenter客户端配置文件
 * 注意：该配置文件请使用常量方式定义
 */

define('UC_APP_ID', 1); //应用ID
define('UC_API_TYPE', 'Model'); //可选值 Model / Service
define('UC_AUTH_KEY', '7w5fSD*Nz_9sg/d[E@361tI>L#OX]oJ^,A<4B:CZ'); //加密KEY

if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
    define('UC_DB_DSN', 'mysql://tigercai_server:e4huY8J7e4@fzhcwlkjyxgs.mysql.rds.aliyuncs.com:3306/tigercai'); // 数据库连接，使用Model方式调用API必须配置此项
} else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
    define('UC_DB_DSN', 'mysql://tigercai_test:MB8&7Y7cL$zWfC3@FM6@123.56.221.173:3306/tigercai_test'); // 数据库连接，使用Model方式调用API必须配置此项
} else {
    define('UC_DB_DSN', 'mysql://root:123456@192.168.1.172:3306/lottery_test'); // 数据库连接，使用Model方式调用API必须配置此项
}
define('UC_TABLE_PREFIX', 'cp_'); // 数据表前缀，使用Model方式调用API必须配置此项
