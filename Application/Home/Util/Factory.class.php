<?php 
namespace Home\Util;
class Factory {
    private static $_verifySsq;
    private static $_verifySyxw;
    private static $_verifySdsyxw;
    private static $_verifyAhsyxw;
    private static $_verifyHbsyxw;
    private static $_verifyDlt;
    private static $_verifyJsks;
    private static $_verifyJlks;
    private static $_verifyJxks;
    private static $_verifyFc3d;
    private static $_jczqUtil;
    private static $_jclqUtil;
    private static $_redis;
	private static $_AliRedis;

    private static $_randomSsq;
    private static $_randomSyxw;
    private static $_randomSdsyxw;
    private static $_randomAhsyxw;
    private static $_randomHbsyxw;
    private static $_randomDlt;
    private static $_randomJsks;
    private static $_randomJlks;
    private static $_randomJxks;
    private static $_randomFc3d;

    public static function createVerifyObj($lotteryId) {
    	$lotteryType = C("LOTTERY_TYPE.$lotteryId");
        $typeName = ucfirst($lotteryType);
		$param = "_verify{$typeName}";
		if(!self::$$param){
			$obj = "Home\Util\Verify{$typeName}Number";
			self::$$param = new $obj();
		}
		return self::$$param;
    }
    
    
    public static function createVerifyJcObj($lotteryId) {
    	$lotteryType = C("LOTTERY_TYPE.$lotteryId");
    	$param = "_{$lotteryType}Util";
    	if(!self::$$param){
    		$obj = "Home\Util\\{$lotteryType}Util";
    		self::$$param = new $obj();
    	}
    	return self::$$param;
    }
    
    
    public static function createRedisObj() {
    	$param = "_redis";
    	if(!self::$$param){
    		$redis = new \Redis();
    		$is_connected = $redis->connect(C('REDIS_HOST'), C('REDIS_PORT'));
    		if(!$is_connected){
    			$redis = false;
    			sendMail(C('NOTICE_EMAILS'),'redis 连接失败：'.C('REDIS_HOST').':'.C('REDIS_PORT'),'');
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

    public static function createRandomObj($lotteryId) {
        $lotteryType = C("LOTTERY_TYPE.$lotteryId");
        $typeName = ucfirst($lotteryType);
        $param = "_random{$typeName}";
        if(!self::$$param){
            $obj = "Home\Util\Random{$typeName}Number";
            self::$$param = new $obj();
        }
        return self::$$param;
    }
    
    
}

?>