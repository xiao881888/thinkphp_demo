<?php 
namespace Home\Model;
use Think\Model;

class RechargeChannelModel extends Model {
    
    public function getPlatformList($os=2) {
        if($os==OS_OF_ANDROID){
            $order_by = 'recharge_channel_android_order asc';
        }else{
            $order_by = 'recharge_channel_order asc';
        }
        
        $condition = array('recharge_channel_status'=>C('PLATFORM.NORMAL'));
        return $this->field('recharge_channel_id, recharge_channel_type, recharge_channel_name, recharge_channel_image, recharge_channel_descript')
                    ->where($condition)
                    ->order($order_by)
                    ->select();
    }
    
    public function getPlatformInfo($id) {
        $condition = array('recharge_channel_id'=>$id, 'recharge_channel_status'=>C('PLATFORM.NORMAL'));
        return $this->where($condition)
                    ->find();
    }
    
}


?>