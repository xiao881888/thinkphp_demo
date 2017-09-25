<?php 

namespace Home\Model;
use Think\Model;

class PushDeviceModel extends Model {

	public function getDeviceInfoByDeviceId($device_id){
		$map = array(
				'device_id' => $device_id 
		);
		$order_by = "pd_id DESC";
		return $this->where($map)->order($order_by)->find();
	}

	public function getDeviceInfoByUid($uid){
		$map = array(
				'uid' => $uid 
		);
		$order_by = "pd_id DESC";
		return $this->where($map)->order($order_by)->find();
	}
	
	public function savePushDeviceInfo($device_token, $session_info, $app_info) {
		if(empty($session_info['device_id'])){
			//sms
			return false;
		}
		
		$map['device_id'] = $session_info['device_id'];
		$push_device_info = $this->where($map)->find();
			
		if($session_info['uid']){
			$old_push_device_ids = $this->where(array('uid' => $session_info['uid']))->getField('pd_id', true);
			if(!empty($old_push_device_ids)){
				$this->where(array('pd_id'=>array('IN', $old_push_device_ids)))->save(array('uid'=>'0'));
			}
		}

		$push_device_data['pd_device_token']	= $device_token;
		$push_device_data['pd_app_package'] 	= $app_info['app_package_name'];
		$push_device_data['pd_app_version'] 	= $app_info['app_app_version'];
		$push_device_data['pd_app_platform'] 	= $app_info['app_platform'];
        $push_device_data['app_id'] 	        = $app_info['app_id'];
		$push_device_data['pd_status']			= 1;

		if($session_info['uid']){
			$push_device_data['uid'] = $session_info['uid'];
		}

		if(empty($push_device_info)){
			$push_device_data['device_id'] = $session_info['device_id'];
			$push_device_data['pd_create_time'] = getCurrentTime();
			return $this->add($push_device_data);
		}else{
			$push_device_data['pd_modify_time'] = getCurrentTime();
			return $this->where($map)->save($push_device_data);
		}
	}
	
	public function deleteConfig($uid) {
		$condition = array('uid'=>$uid);
		$data = array('uid'=>0);
		return $this->where($condition)
					->save($data);
	}
	
	
	public function saveUserId($deviceId, $uid) {
	    $condition = array('device_id'=>$deviceId);
	    $data = array('uid' => $uid,);
	    return $this->where($condition)->save($data);
	}

	/**
	 * @param string $uidList
	 * @param $type 0:部分用户，1:全部用户
	 * @return mixed
	 */
	public function getUnitePushDeviceList($uidList = '',$type=0,$app_id=0){
		if(!empty($uidList)){
			if($type === 0){
				$uidList = is_array($uidList) ? $uidList : explode(',', $uidList);
				$where['uid'] = array('IN', $uidList);
			}

			if(!empty($app_id)){
                $where['app_id'] = $app_id;
            }

			$where['pd_status'] = 1;
			$order_by = 'pd_modify_time DESC';
			$field = 'pd_app_platform,pd_app_package,pd_device_token';
			return  $this->field($field)->where($where)->group('pd_device_token')->order($order_by)->select();
		}
	}
	
}