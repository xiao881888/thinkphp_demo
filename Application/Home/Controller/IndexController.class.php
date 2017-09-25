<?php
namespace Home\Controller;
use Home\Controller\GlobalController;
use Home\DTO\ApiModel;
use Home\Util\Pack;

class IndexController extends GlobalController {
    
    public function index() {
        try {
            $request 	= Pack::unpackRequest();
            $api 		= new ApiModel($request);
            if($api->act==10306){
                $start_time = microtime(true);
                ApiLog('response start:'.$start_time,'10306_'.$api->os);
            }
            $response	= $this->_getResponseData($api);
           
            // ApiLog('response:'.print_r($response,true),'sms');
            if($api->act==10306){
            	ApiLog('response:'.print_r($response,true),'10403');
            }
        } catch (\Think\Exception $e) {
            $response	= array('result' => $e->getMessage(),
                				'code'	 => $e->getCode() );
        }
        
        $result = Pack::packResponse($response['result'], $response['code'], $api->session, $api->encrypt_type, $api->act, $api->key);
        // ApiLog('len:'.strlen($result),'sms');
         if($api->act==10306){
                            $end_time = microtime(true);

                ApiLog('response end:'.($end_time-$start_time),'10306_'.$api->os);
         }
        echo $result;
    }
    
    private function _getResponseData($api) {
        $function = C("ACT_MAPPING.$api->act");
        return R($function, array($api));
    }
}