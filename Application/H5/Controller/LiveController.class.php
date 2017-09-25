<?php
namespace H5\Controller;

use Think\Exception;

class LiveController extends BaseController{

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

    public function index()
    {
        $lottery_id = I('get.lottery_id',false);
        if (!$lottery_id){
            $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
        }
        $lottery_id = I('get.lottery_id',TIGER_LOTTERY_ID_OF_JZ);
        $obj_instance = '\Home\Controller\\'.$this->_obj_map[$lottery_id].'Controller';
        $instance = new $obj_instance();
    }

    private function _getInstanceForRequestData(){
        $lottery_id = I('get.lottery_id',TIGER_LOTTERY_ID_OF_JZ);
        $obj_instance = '\Home\Controller\\'.$this->_obj_map[$lottery_id].'Controller';
        return new $obj_instance();
    }

    public function __call($method, $args){
        $obj_instance = $this->_getInstanceForRequestData();
        $api_param = [
            'lottery_id' => I('get.lottery_id','',intval),
            'type' => I('get.type','',intval),
            'schedule_ids' => I('get.schedule_ids','',intval),
            'issue_no' => I('get.issue_no','',intval),
            'third_party_schedule_id' => I('get.third_party_schedule_id','',intval),
            'team_id' => I('get.team_id','',intval),
            'company_id' => I('get.company_id','',intval),
        ];
        $response = $obj_instance->$method((object)$api_param);
        $this->response($response['result']);
    }



}