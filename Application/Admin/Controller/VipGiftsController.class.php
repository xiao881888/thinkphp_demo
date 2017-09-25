<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
use Admin\Model\DrawGoodModel;
use Home\Controller\UserCouponController;

class VipGiftsController extends GlobalController{

    public function add(){
        if(!IS_POST){
            $this->assign('coupon_list',D('Coupon')->getCouponMap());
            $this->assign('vip_level_list',$this->_getVipLevelList());
        }
        parent::add();
    }

    public function edit(){
        if(!IS_POST){
            $this->assign('coupon_list',D('Coupon')->getCouponMap());
            $this->assign('vip_level_list',$this->_getVipLevelList());
        }
        parent::edit();
    }

    private function _getVipLevelList(){
        return D('VipLevel')->getVipLevelList();
    }

    public function editVipContent(){
        $this->assign('vg_id',I('vg_id'));
        $this->assign('coupon_list',D('Coupon')->getEnableCouponMap());
        $this->assign('vip_level_list',$this->_getVipLevelList());
        $this->display();
    }

    public function getVipContent(){
        $vg_id = I('id');
        $vg_info = D('VipGifts')->getVgInfoById($vg_id);
        $level_name = D('VipLevel')->getVipLevelNameById($vg_info['vip_level_id']);
        $vg_content_list = json_decode($vg_info['vg_content'],true);
        $this->assign('vg_info',$vg_info);
        $this->assign('level_name',$level_name);
        $this->assign('vg_content_list',$vg_content_list);
        $this->display();
    }

    public function addVipContent(){
        $vg_id = I('vg_id');
        if(!$this->_checkVipContentData()){
            $this->error('数据有误');
        }
        $vg_content_type_list = I('vg_content_type');
        $vg_content_name_list = I('vg_content_name');
        $vg_content_coupon_list = I('vg_content_coupon');
        $vg_content_integral_list = I('vg_content_integral');
        $vg_content_num_list = I('vg_content_num');

        $vip_content_config = array();
        foreach($vg_content_type_list as $key => $type){
            $vip_content_config[] = array(
                'type' => $type,
                'name' => $vg_content_name_list[$key],
                'coupon_id' => $vg_content_coupon_list[$key],
                'integral' => $vg_content_integral_list[$key],
                'num' => $vg_content_num_list[$key],
            );
        }
        D('VipGifts')->saveVgContent($vg_id,json_encode($vip_content_config));
        D('VipGifts')->updateSendStatus($vg_id);
        $this->success('编辑礼包成功',U('edit',array('id'=>$vg_id)));
    }

    private function _checkVipContentData(){
        $vg_id = I('vg_id');
        $vg_content_type = I('vg_content_type');
        $vg_content_name = I('vg_content_name');
        $vg_content_coupon = I('vg_content_coupon');
        $vg_content_integral = I('vg_content_integral');
        $vg_content_num = I('vg_content_num');
        if(empty($vg_id) || empty($vg_content_type) || empty($vg_content_name) || empty($vg_content_num)){
            return false;
        }

        if($vg_content_type == 1){
            return empty($vg_content_coupon) ?  false :  true;
        }elseif($vg_content_type == 2){
            return empty($vg_content_integral) ?  false :  true;
        }
        return true;
    }

    public function send(){
        $vg_id = I('id');
        $vg_info = D('VipGifts')->getVgInfoById($vg_id);
        if($vg_info['vg_status'] != 1){
            $this->error('不允许发放');
        }
        if(!$this->_checkVgContent($vg_info['vg_content'])){
            $this->error('数据有误');
        }
        $this->_asynchronousAutoSend($vg_id);
        D('VipGifts')->updateSuccessStatus($vg_id);
        D('VipGifts')->updatePushTime($vg_id);
        $this->success('发放成功');
    }

    private function _asynchronousAutoSend($vg_id){
        if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
            $request_url = 'http://phone.api.tigercai.com/index.php?s=/Home/VipGifts/autoSend';
        }elseif( get_cfg_var('PROJECT_RUN_MODE') == 'TEST' ){
            $request_url = 'http://test.phone.api.tigercai.com/index.php?s=/Home/VipGifts/autoSend';
        }else {
            $request_url = 'http://192.168.1.171:81/index.php?s=/Home/VipGifts/autoSend';
        }
        $request_data['vg_id'] = $vg_id;
        ApiLog('$request_url:'.$request_url,'asynchronousAutoSend');
        $content = curl_post_asy($request_url,$request_data);
        ApiLog('$content:'.$content,'asynchronousAutoSend');
    }

    private function _checkVgContent($vg_content){
        $vg_content_list = json_decode($vg_content,true);
        foreach($vg_content_list as $vg_content_info){
            $type = $vg_content_info['type'];
            if(empty($type) || empty($vg_content_info['name']) || empty($vg_content_info['num'])){
                return false;
            }
            if($type == 1){
                $coupon_info = D('Coupon')->getCouponInfoById($vg_content_info['coupon_id']);
                if(empty($coupon_info)){
                    return false;
                }
            }elseif($type == 2){
                if(empty($vg_content_info['integral'])){
                    return false;
                }
            }
        }
        return true;
    }

    public function push(){
        set_time_limit(0);

        $vg_id = I('id');
        $vg_info = D('VipGifts')->getVgInfoById($vg_id);
        if($vg_info['vg_status'] != 2){
            $this->error('当前状态不允许推送');
        }

        $uids = D('IntegralUser')->getUserListByVipLevelId($vg_info['vip_level_id']);
        $noTigerUids = D('User')->getNoTigerUsers($uids);
        $noTigerUids = empty($noTigerUids) ? array() : $noTigerUids;
        $uids = array_diff($uids,$noTigerUids);
        $api_data = array();
        $api_data['msg'] = $vg_info['vg_push_content'];
        $api_data['uids'] = $uids;
        $api_data['app_id'] = 1;
        $api_data['act_type'] = 6;
        $push_api = C('APP_PUSH_ALL_API');
        $resp = curl_post($push_api, $api_data);
        ApiLog('push:'.print_r($resp,true),'VipGifts');
        $this->success('推送成功');
    }

}