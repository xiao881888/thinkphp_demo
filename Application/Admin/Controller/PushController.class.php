<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;

class PushController extends GlobalController{

	const unitePushURL = 'http://192.168.1.171:81/index.php?s=/Home/UnitePush/pushActivityMessage/';
	const IsNewPushAPI = true;

	public function _initialize(){
		parent::_initialize();

		$push_type = array(
			'0' => '跳转到首页',
			'1' => '跳转到订单详情',
			'2' => '跳转下注页',
			'3' => '跳转到充值页',
			'4' => '跳转到浏览器',
			'5' => '跳转到购买红包页',
            '8' => '合买大厅',
            //'9' => '合买详情'
		);
		$this->assign('push_type', $push_type);
		$this->assign('lottery_map', D('Lottery')->getLotteryMap());
	}

	public function edit(){
		$push_id = I('id');

		$vo = D('Push')->find($push_id);
		$this->assign('vo', $vo);

		$paramers = json_decode($vo['push_paramers'], true);
		$refer_order_id 	= $paramers['order_id'];
		$refer_lottery_id 	= $paramers['lottery_id'];
		$refer_order_status = $paramers['order_status'];
		$refer_url 			= $paramers['url'];
		$this->assign('refer_order_id', $refer_order_id);
		$this->assign('refer_lottery_id', $refer_lottery_id);
		$this->assign('refer_order_status', $refer_order_status);
		$this->assign('refer_url', $refer_url);

		$this->display();
	}

	public function doEdit(){
		parent::doEdit();
	}

	public function detail(){
		$push_id = I('id');

		$vo = D('Push')->find($push_id);
		$this->assign('vo', $vo);

		$paramers = json_decode($vo['push_paramers'], true);
		$refer_order_id 	= $paramers['order_id'];
		$refer_lottery_id 	= $paramers['lottery_id'];
		$refer_order_status = $paramers['order_status'];
		$refer_url 			= $paramers['url'];
		$this->assign('refer_order_id', $refer_order_id);
		$this->assign('refer_lottery_id', $refer_lottery_id);
		$this->assign('refer_order_status', $refer_order_status);
		$this->assign('refer_url', $refer_url);

		$this->display();
	}

	public function sendPush(){


		$push_id = I('id');
		$push_info = D('Push')->find($push_id);

		if (empty($push_info)) {
			$error_msg  = 'push不存在，请找管理员确认';
		} elseif ($push_info['push_status'] == 2) {
			$error_msg  = 'push已经发送过，无法再次发送';
		}

		if (!empty($error_msg)) {
			$this->error($error_msg);
			return;
		}

		set_time_limit(0);
		$api_data = array();
		$api_data['msg'] = $push_info['push_content'];
		$api_data['uid'] = $push_info['uid'];
		$api_data['type'] = $push_info['push_type'];
		$api_data['paramers'] = $push_info['push_paramers'];
        $api_data['app_id'] = $push_info['app_id'];
		$api_data['act_type'] = 1;

		/*if(self::IsNewPushAPI){
			//如果是推送全部人员用新的推送接口,其他还是使用原来的接口
			if($push_info['uid'] === 'all'){
				$push_api = C('APP_PUSH_ALL_API');
			}else{
				$push_api = C('APP_PUSH_API');
			}
		}else{
			$push_api = C('APP_PUSH_API');
		}*/

		$push_api = C('APP_PUSH_ALL_API');

		$resp = curl_post($push_api, $api_data);
		if (!empty($resp)) {
			$push_data = array(
				'push_id' => $push_id,
				'push_status' => 2,
				'push_result' => $resp,
				'push_pushtime' => getCurrentTime(),
				'push_modifytime' => getCurrentTime()
			);

			D('Push')->save($push_data);

			$resp = json_decode($resp, true);
			if($resp['error_code'] == 1){
				$this->error($resp['msg']);
			}else{
				$this->success($resp['msg']);
			}
		} else {
			$this->error('推送接口异常，请联系管理员');
		}

		//新推送接口
		/*$resp = json_decode($resp,true);

		if($resp['error_code'] === 0){
			$push_data = array(
				'push_id' => $push_id,
				'push_status' => 2,
				'push_result' => $resp,
				'push_pushtime' => getCurrentTime(),
				'push_modifytime' => getCurrentTime()
			);

			D('Push')->save($push_data);

			$this->success($resp['msg']);

		}else{
			$this->error('推送接口异常，请联系管理员');
		}*/
	}
}