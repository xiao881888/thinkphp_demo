<?php 

namespace Home\Model;
use Think\Model;

class DeviceModel extends Model {
    
    public function addDeviceInfo($device_info,$os=2) {
    	if($os==OS_OF_IOS){
    		if(empty($device_info['imei']) || empty($device_info['mac'])){
    			return false;
    		}

    		$special_device = (trim($device_info['imei'])=='00000000-0000-0000-0000-000000000000') 
    							&& (trim($device_info['mac'])=='020000000000');
    		if($special_device){
    			$deviceIdentify = md5($device_info['imei'].$device_info['mac'].$device_info['iphone_idfv']);
    		}else{
    			$deviceIdentify = md5($device_info['imei'].$device_info['mac']);
    		}
    	}elseif($os==OS_OF_ANDROID){
    		$data['device_factor'] = $device_info['device_factor'];
    		$deviceIdentify = md5($device_info['imei'].$device_info['mac'].$device_info['android_id'].$device_info['sim_serial_number'].$device_info['device_factor']);
    	}
    	
		$data['mac'] = $device_info['mac'];
		$data['imei'] = $device_info['imei'];
		$data['device_identify'] = $deviceIdentify;
		$data['imsi'] = $device_info['imsi'];
		$data['sim_serial_number'] = $device_info['sim_serial_number'];
		$data['network_operator'] = $device_info['network_operator'];
		$data['manufacturer'] = $device_info['manufacturer'];
		$data['root'] = $device_info['root'];
		$data['os_version'] = $device_info['os_version'];
		$data['screen_size'] = $device_info['screen_size'];
		$data['screen_density'] = $device_info['screen_density'];
		$data['screen_pixel_metric'] = $device_info['screen_pixel_metric'];
		$data['unknow_source'] = $device_info['unknow_source'];
		$data['language'] = $device_info['language'];
		$data['country'] = $device_info['country'];
		$data['time_zone'] = $device_info['time_zone'];
		$data['model'] = $device_info['model'];
		$data['cpu_abi'] = $device_info['cpu_abi'];
		$data['network'] = $device_info['network'];
		$data['host_name'] = $device_info['host_name'];
		$data['device_name'] = $device_info['device_name'];
		$data['kernel_boot_time'] = $device_info['kernel_boot_time'];
		$data['wifi_bssid'] = $device_info['wifi_bssid'];
		$data['station_net'] = $device_info['station_net'];
		$data['station_cell_id'] = $device_info['station_cell_id'];
		$data['station_lac'] = $device_info['station_lac'];
		$data['iphone_adfa'] = $device_info['iphone_adfa'];
		$data['iphone_idfv'] = $device_info['iphone_idfv'];
		$data['iphone_udid'] = $device_info['iphone_udid'];
		$data['phone_number'] = $device_info['phone_number'];
		$data['create_time'] = getCurrentTime();
        
        $data = array_map('emptyToStr', $data);
        
        $deviceId = $this->getDeviceId($deviceIdentify);
        
        if($deviceId) {
			$condition = array(
					'device_id'=> $deviceId 
			);
			$saveResult = $this->where($condition)->save($data);
            
            if($saveResult===false) {
                return false;
            }
            return $deviceId;
        } else {
            return $this->add($data);
        }
    }
    
    
    public function getDeviceId($deviceIdentify) {
        $condition = array('device_identify' => $deviceIdentify);
        return $this->where($condition)
                    ->getField('device_id');
    }
    
}


?>