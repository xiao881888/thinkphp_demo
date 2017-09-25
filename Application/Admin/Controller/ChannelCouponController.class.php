<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date
 * @author
 */
class ChannelCouponController extends GlobalController{
    
    public function _before_index(){
        $this->_assignChannelMap();
        $this->_assignChannelCouponPlanMap();
    }
    
    public function _before_add(){
        $this->_assignChannelMap();
        $this->_assignChannelCouponPlanMap();
    }
    
    private function _assignChannelMap(){
        $channels = D('Channel')->getChannelMap();
        $this->assign('channels', $channels);
    }
    
    private function _assignChannelCouponPlanMap(){
        $plans = D('ChannelCouponPlan')->getChannelCouponPlanMap();
        $this->assign('plans', $plans);
    }
    
    public function doAdd(){
        $channel_id = I('channel_id');
        $plan_id = I('plan_id');
        $cc_start_time = I('cc_start_time');
        $cc_end_time = I('cc_end_time');
		$number = I('number', 1, 'int');
        if($channel_id && $plan_id && $number){
			$codes = array();
			for($i=0; $i<$number; $i++){
                $data = array();
                $data['cc_code'] 	    = strtoupper(random_string(8));
                $data['channel_id']		= $channel_id;
                $data['plan_id']		= $plan_id;
                $data['cc_start_time']	= $cc_start_time;
                $data['cc_end_time']	= $cc_end_time;
                $data['cc_createtime']  = curr_date();
                $data['cc_modifytime']  = curr_date();
                $codes[] = $data;
            }
            $result = D('ChannelCoupon')->addAll($codes);
            if($result){
                $this->success('操作成功！',U('index'));
            }else{
                $this->error('操作失败！');
            }
        }else{
            $this->error('参数错误！');            	
        }
    }
}