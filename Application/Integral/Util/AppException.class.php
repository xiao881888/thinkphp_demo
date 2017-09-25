<?php
namespace Integral\Util;
use Think\Exception;

class AppException extends Exception {
    public static function throwException($error_code, $error_msg='') {
        throw new Exception($error_msg, $error_code );
    }

    public static function log_info($e) {
        ApiLog('文件:'.$e->getFile().';行数:'.$e->getLine().';出错信息:'.$e->getMessage(),'IntegralException');
    }
}

