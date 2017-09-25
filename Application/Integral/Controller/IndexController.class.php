<?php
namespace Integral\Controller;

use Integral\Util\AppException;
use Think\Controller;
use Think\Exception;

class IndexController extends GlobalController {
    
    public function index() {
        $response_data = array();
        try {
            $act_code = I('act_code');
            $function = C("ACT_MAPPING.$act_code");
            if(empty($function)){
                throw new Exception(C('ERROR_MSG.FUNCTION_NOT_EXIST'), C('ERROR_CODE.FUNCTION_NOT_EXIST') );
            }
            $request_data = $this->_getRequestData();
            //$this->_limitUserRequestTimes($act_code,$request_data);
            $response_data = R($function,$request_data);
        } catch (Exception $e) {
            AppException::log_info($e);
            $data['data'] = $e->getMessage();
            $data['error_code'] = $e->getCode();
            $this->ajaxReturn($data);
        }
        $data['data'] = $response_data;
        $data['error_code'] = C('ERROR_CODE.SUCCESS');
        $this->ajaxReturn($data);
    }

    private function _limitUserRequestTimes($act_code,$request_data){
        $act_limit_request_list = C('LIMIT_ACT_LIST');
        if(in_array($act_code,$act_limit_request_list) && isset($request_data['request_data']['uid'])){
            $uid = $request_data['request_data']['uid'];
            $is_request = $this->redis->setnx($this->_getLimitUserRequestRedisKey($uid,$act_code),getCurrentTime());
            if(!$is_request){
                ApiLog('1:$request_data:'.print_r($request_data,true),'limitUserRequestTimes');
                throw new Exception(C('ERROR_MSG.REQUEST_TOO_MANY'), C('ERROR_CODE.REQUEST_TOO_MANY') );
            }
            $this->redis->expire($this->_getLimitUserRequestRedisKey($uid,$act_code),C('LIMIT_TIME'));
        }
    }

    private function _getLimitUserRequestRedisKey($uid,$act_code){
        return 'limit_request:'.$act_code.'-'.$uid;
    }

    private function _getRequestData(){
        return  array('request_data'=>json_decode(I('data'),true));
    }


    private function _getFunction() {
        $act_code = I('act_code');
        return C("ACT_MAPPING.$act_code");
    }
}