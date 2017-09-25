<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;

class FullReducedCouponConfigController extends GlobalController{


	public function add(){
		if(empty($_POST)){
			$coupon_list = D('Coupon')->getCouponMap();
			$this->assign('coupon_list',$coupon_list);
		}
		parent::add();
	}

	public function edit(){
		if(empty($_POST)){
			$coupon_list = D('Coupon')->getCouponMap();
			$this->assign('coupon_list',$coupon_list);
		}
		parent::edit();
	}

	public function grantFullReducedCouponToUser(){

		$id = I('id',0);
		$full_reduced_coupon_info = D('FullReducedCouponConfig')->getInfoById($id);
		if(empty($full_reduced_coupon_info)){
			exit('当前信息不存在');
		}


		$user_list = $full_reduced_coupon_info['frcc_user_list'];

		$post_data['full_reduced_coupon_config_id'] = $id;
		$post_data['uids'] = $user_list;
		if(!$this->_checkUserList($user_list)){
			exit('用户id不合法');
		}

		curl_post(C('GRANT_COUPON_URL'),$post_data);
		$this->success("操作成功", U('index'));
	}

	private function _checkUserList($user_list){
		$user_list = explode(',',$user_list);
		foreach($user_list as $uid){
			$user_info = D('User')->getUserInfo($uid);
			if(empty($user_info)){
				return false;
			}
		}
		return true;
	}

}