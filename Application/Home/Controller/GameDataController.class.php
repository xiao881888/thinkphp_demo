<?php

namespace Home\Controller;

use Home\Controller\GlobalController;

class GameDataController extends GlobalController{
	private $_obj_map = array(
		6 => 'LiveScore',
		601 => 'LiveScore',
		602 => 'LiveScore',
		603 => 'LiveScore',
		604 => 'LiveScore',
		605 => 'LiveScore',
		606 => 'LiveScore',
		7 => 'BasketballGameData',
		701 => 'BasketballGameData',
		702 => 'BasketballGameData',
		703 => 'BasketballGameData',
		704 => 'BasketballGameData',
		705 => 'BasketballGameData',
        20 => 'ZcsfcGameData',
        21 => 'ZcsfcGameData',
	);

	private function _getInstanceForRequestData($api){
		$lottery_id = empty($api->lottery_id) ? TIGER_LOTTERY_ID_OF_JZ : $api->lottery_id;
		$obj_instance = $this->_obj_map[$lottery_id];
		return A($obj_instance);
	}
	
	public function __call($method, $args){
		$obj_instance = $this->_getInstanceForRequestData($args[0]);
		return $obj_instance->$method($args[0]);
	}
}