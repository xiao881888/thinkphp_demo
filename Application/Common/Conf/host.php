<?php
/**
 * @date 2014-11-04
 * @author tww <merry2014@vip.qq.com>
 */

if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
    $request_host = 'http://'.$_SERVER['HTTP_HOST'];
} else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
    $request_host = 'http://'.$_SERVER['HTTP_HOST'];
} else {
    $request_host = 'http://'.$_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'].'/index.php?s=';
}

return array(
    'REQUEST_HOST'   => $request_host,
);
