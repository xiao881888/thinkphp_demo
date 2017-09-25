<?php

namespace Home\Controller;

use Think\Controller;

class ConfigController extends Controller{

	public function fetchBankList($api){
		$map['bank_status'] = 1;
		$bank_list = D('Bank')->where($map)->select();
		$response_bank_list = array();
		foreach ($bank_list as $bank_info) {
			$bank_data['id'] = $bank_info['bank_id'];
			$bank_data['name'] = $bank_info['bank_name'];
			$response_bank_list[] = $bank_data;
		}
		
		return array(
				'result' => array(
						'list' => $response_bank_list 
				),
				'code' => C('ERROR_CODE.SUCCESS') 
		);
	}

	public function queryOpenPageInfo($api){
	    $app_id = getRequestAppId($api->bundleId);
		ApiLog('api:'.print_r($api,true),'wd');
		$map['open_page_expiretime'] = array(
				'egt',
				date("Y-m-d H:i:s") 
		);
		$map['open_page_status'] = 1;
		$map['open_page_os'] = array('IN', array($api->os, 0));
        $map['app_id'] = array('IN', array($app_id, 0));
		$open_page_data = D('OpenPage')->where($map)->group('open_page_level')->find();
		

		$open_page_info = array();
		$open_page_info['target'] 		= $open_page_data['open_page_target_url'] ? $open_page_data['open_page_target_url'] : '';
		$open_page_info['end_time'] 	= intval(strtotime($open_page_data['open_page_expiretime']));
		$open_page_info['type'] 		= isset($open_page_data['open_page_type']) ? $open_page_data['open_page_type'] : -1;
		$open_page_info['lottery_id'] 	= $open_page_data['open_page_lottery_id'] ? $open_page_data['open_page_lottery_id'] : 0;
		switch ($api->size) {
			case '640_960':
				$image_url = $open_page_data['open_page_image_640_2'];

			case '640_1136':
				$image_url = $open_page_data['open_page_image_640'];
				break;
			case '750_1334':
				$image_url = $open_page_data['open_page_image_750'];
				break;
			case '1242_2208':
				$image_url = $open_page_data['open_page_image_1242'];
				break;
			case '480_800' :
				$image_url = $open_page_data['open_page_image_480'];
				break;
			case '720_1280' :
				$image_url = $open_page_data['open_page_image_720'];
				break;
			case '1080_1920' :
				$image_url = $open_page_data['open_page_image_1080'];
				break;
			default:
				$image_url = $open_page_data['open_page_image_640'];
				break;
		}
		$open_page_info['image'] = $image_url ? $image_url : '';
		ApiLog('open_page_info:'.print_r($open_page_info, true).'----'.$api->size, 'wb');
		return array(
				'result' => $open_page_info,
				'code' => C('ERROR_CODE.SUCCESS') 
		);
	}
}
