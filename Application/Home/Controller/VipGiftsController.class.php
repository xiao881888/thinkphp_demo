<?php
namespace Home\Controller;
use Home\Controller\GlobalController;

class VipGiftsController extends GlobalController {

    public function autoSend(){
        $vg_id = I('vg_id');
        //设置程序执行时间的函数
        set_time_limit(0);
        ApiLog('$vg_id:'.$vg_id.'发放礼包','VipGifts');
        //函数设置与客户机断开是否会终止脚本的执行
        ignore_user_abort(true);
        $vg_info = D('Admin/VipGifts')->getVgInfoById($vg_id);
        if($vg_info['vg_status'] != 1){
            ApiLog($vg_id.'不允许发放','VipGifts');
            die;
        }
        $vg_content_list = json_decode($vg_info['vg_content'],true);
        $uids = D('Admin/IntegralUser')->getUserListByVipLevelId($vg_info['vip_level_id']);
        foreach($vg_content_list as $vg_content){
            if($vg_content['type'] == 1){
                $coupon_id = $vg_content['coupon_id'];
                $num = $vg_content['num'];
                $this->_grantCouponToUsers($uids,$coupon_id,$num);
            }elseif($vg_content['type'] == 2){
                $integral = $vg_content['integral'];
                $this->_grantIntegralToUsers($uids,$vg_id,$integral);
            }
        }

        $this->_insertLogForVipGifts($uids,$vg_info['vip_level_id'],$vg_id);

    }

    private function _grantCouponToUsers($uids,$coupon_id,$num){
        $user_coupon_obj = new UserCouponController();
        foreach($uids as $uid){
            for($i=0;$i<$num;$i++){
                $user_coupon_obj->grantCouponToUser($coupon_id,$uid,5);
            }
        }
    }

    private function _grantIntegralToUsers($uids,$vg_id,$integral){
        $data['uids'] = $uids;
        $data['vg_id'] = $vg_id;
        $data['add_integral'] = $integral;
        $request_data['data'] = json_encode($data);
        $request_data['act_code'] = 1010;
        $response_data = requestUserIntegral($request_data);
        ApiLog('$response_data:'.print_r($response_data,true),'addUserIntegral');
    }

    private function _insertLogForVipGifts($uids,$vip_level_id,$vg_id){
        foreach ($uids as $uid){
            $add_data[] = array(
                'uid' => $uid,
                'vip_level_id' =>$vip_level_id,
                'vg_id' => $vg_id,
                'vgl_createtime' => getCurrentTime(),
            );
        }
        D('Admin/VipGiftsLog')->addAll($add_data);
    }

    public function test(){
        $id = I('vg_id');
        echo $id;die;
    }
    
    
}

?>