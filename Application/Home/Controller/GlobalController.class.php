<?php
namespace Home\Controller;
use Think\Controller;

class GlobalController extends Controller {
	
	public function _initialize() {
		import ( '@.Util.AppException' );
		import ( '@.Util.Pack' );
		import ( '@.DTO.ApiModel' );
	}
	
	
	protected function getAvailableUser($session) {
	    $uid = D('Session')->getUid($session);
	    \AppException::ifNoExistThrowException($uid, C('ERROR_CODE.USER_NO_LOGIN'));
	    $userInfo = D('User')->getUserInfo($uid);
	    $allow = $userInfo['user_status'] == C('USER_STATUS.ENABLE');
	    \AppException::ifNoExistThrowException($allow, C('ERROR_CODE.USER_FORBIDDEN'));
	    return $userInfo;
	}
	
	
}