<?php 
namespace Integral\Util;

class Factory {
    private static $_redis;
	private static $_AliRedis;

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