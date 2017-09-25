<?php 

namespace Home\Model;
use Think\Model;


class SessionModel extends Model {
    
    public function getUid($session) {
        $condition = array('session_code'=>$session);
        return $this->where($condition)
                    ->getField('uid');
    }

	public function saveToken($device_id, $client_key_info){
		if(empty($device_id) || empty($client_key_info)){
			return false;
		}
		
		$token = md5($device_id . random_string(20));
		$session_data['session_code'] = $token;
		$session_data['session_create_time'] = getCurrentTime();
		$session_data['session_encrypt_Key'] = json_encode($client_key_info);
		
		$session_info = $this->getSessionInfoByDeviceId($device_id);
		if (empty($session_info)) {
			$session_data['device_id'] = $device_id;
			$add_result = $this->add($session_data);
			if ($add_result) {
				return $token;
			}
		} else {
			if (empty($session_info['uid'])) {
				$condition = array(
						'device_id' => $device_id 
				);
				$save_result = $this->where($condition)->save($session_data);
			} else {
				$session_data['device_id'] = $device_id;
				$map['uid'] = $session_info['uid'];
				$save_result = $this->where($map)->save($session_data);
			}
			if ($save_result) {
				return $token;
			}
		}
		return false;
	}
    
    
    public function getOtherLoginUser($uid, $session) {
        $condition = array( 'uid' => $uid,
                            'session_code' => array('neq', $session),
        );
        return $this->where($condition)
                    ->getField('session_id', true);
    }
    
    
    public function getSessionInfoByDeviceId($deviceId) {
        $condition = array('device_id'=>$deviceId);
        return $this->where($condition)
                    ->find();
    }
    
    
    public function getSessionId($session) {
        $condition = array('session_code'=>$session);
        return $this->where($condition)
                    ->getField('session_id');
    }
    
    
    public function saveSession($uid, $session) {
        $condition = array('session_code'=>$session);
        $data = array('uid' => $uid);
        return $this->where($condition)->save($data);
    }
    
    
    public function getDeviceId($session) {
        $condition = array('session_code'=>$session);
        return $this->where($condition)
                    ->getField('device_id');
    }
    
    
    public function getDeviceIdByUid($uid) {
        $condition = array('uid'=>$uid);
        return $this->where($condition)
                    ->getField('device_id');
    }
    
    
    public function getEncryptKey($token) {
        if (!$token) { return false;}
        $condition = array('session_code'=>$token);
        return $this->where($condition)
                    ->getField('session_encrypt_Key');
    }
    
    
    public function deleteSession($session) {
        $condition = array('session_code'=>$session);
        $data = array('uid' => 0);
        return $this->where($condition)
                    ->save($data);
    }
    
    
    public function deleteSessionById($sessionId) {
        $condition = array('session_id' => array('in', $sessionId));
        return $this->where($condition)
                    ->delete();
    }
    
    
    public function getSessionInfo($session) {
    	$condition = array('session_code'=>$session);
    	return $this->where($condition)
    				->find();
    }
    
}




?>