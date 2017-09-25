<?php 

namespace Home\Model;
use Think\Model;

class PushSwitchConfigModel extends Model {

	public function addInfo($device_token,$push_switch_config_type,$status,$forbid_time){
		$add_data['psc_device_token'] = $device_token;
		$add_data['pst_id'] = $push_switch_config_type;
		$add_data['psc_status'] = $status;
		$add_data['psc_createtime'] = getCurrentTime();
        $add_data['psc_not_disturb_time'] = $forbid_time;
		return $this->add($add_data);
	}

	public function saveInfo($device_token,$push_switch_config_type,$status,$forbid_time){
		$where['psc_device_token'] = $device_token;
		$where['pst_id'] = $push_switch_config_type;
		$save_data['psc_status'] = $status;
		$save_data['psc_modifytime'] = getCurrentTime();
		if(!empty($forbid_time)){
            $save_data['psc_not_disturb_time'] = $forbid_time;
        }
		return $this->where($where)->save($save_data);
	}

	public function isAdd($device_token,$push_switch_config_type){
		$where['psc_device_token'] = $device_token;
		$where['pst_id'] = $push_switch_config_type;
		return $this->where($where)->find();
	}

	public function getInfoById($id){
		$where['psc_id'] = $id;
		return $this->where($where)->find();
	}

	public function getStatusOfOn($device_token){
		$where['psc_device_token'] = $device_token;
		return $this->field('psc_id,pst_id,psc_status,psc_not_disturb_time')->where($where)->select();
	}
	
}