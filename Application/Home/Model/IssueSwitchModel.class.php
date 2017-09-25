<?php 
namespace Home\Model;
use Think\Model;

class IssueSwitchModel extends Model {
    
    public function getSwitchOffList(){
        $where['is_status'] = 1;
        $where['is_switch'] = 0;
        $where['is_type'] = 1;
        return $this->where($where)->getField('is_package_name,is_app_version',true);
    }

    public function getRegisterSwitchOnList(){
        $where['is_status'] = 1;
        $where['is_switch'] = 1;
        $where['is_type'] = 2;
        return $this->where($where)->getField('is_package_name,is_app_version',true);
    }
    
}


?>