<?php 
namespace Content\Util;

class Factory {
    private static $_redis;
	private static $_AliRedis;
    const REDIS_HOST = '192.168.1.171';
    const REDIS_PORT = 6379;
    const NOTICE_EMAILS = '15980228063@139.com';

    public static function createRedisObj() {
    	$param = "_redis";
    	if(!self::$$param){
    		$redis = new \Redis();
    		$is_connected = $redis->connect(self::REDIS_HOST, self::REDIS_PORT);
    		if(!$is_connected){
    			$redis = false;
    			sendMail(self::NOTICE_EMAILS,'redis 连接失败：'.self::REDIS_HOST.':'.self::REDIS_PORT,'');
    		}
    		self::$$param = $redis;
    	}
    	return self::$$param;
    }

	public static function createAliRedisObj() {
		$param = "_AliRedis";
		if(!self::$$param){
			$redis = new \Redis();
			$is_connected = $redis->connect(C('ALI_REDIS_HOST'), C('ALI_REDIS_PORT'));
			if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
				$redis->auth('Mg3ZHemsH04cVxon');
			}
			if(!$is_connected){
				$redis = false;
				sendMail(C('NOTICE_EMAILS'),'redis 连接失败：'.C('ALI_REDIS_HOST').':'.C('ALI_REDIS_PORT'),'');
			}
			self::$$param = $redis;
		}
		return self::$$param;
	}

}

?>