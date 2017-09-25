<?php

class AppException extends \Think\Exception {
	
	public static function ifNoExistThrowException($result, $errorCode) {
		if(!$result) {
			throw new \Think\Exception('', $errorCode);
		}
	}
	
	
	public static function ifExistThrowException($result, $errorCode) {
		if($result) {
			throw new \Think\Exception('', $errorCode);
		}
	}
	
	public static function throwException($error_code, $error_msg='') {
		throw new \Think\Exception($error_msg, $error_code );
	}
}
