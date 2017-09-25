<?php
namespace Home\Controller;

use Home\Controller\GlobalController;
use Home\Util\Factory;

class WinmessageController extends GlobalController
{
    public function getWinMessage($api)
    {
        //A('FullReducedCouponConfig')->firstLoading($api);
        $winmessage = $this->_getWinMessageList();
        if(empty($winmessage)){
            $winmessage = D('Winmessage')->getIndexWinmessage();
        }
        $result['list'] = $winmessage;

        $audit_version = D('IssueSwitch')->getSwitchOffList();
        if ($api->os == 1 && array_key_exists($api->channel_id, $audit_version) && $api->app_version === $audit_version[$api->channel_id] ) {
            $is_in_audit = true;
        } else {
            $is_in_audit = false;
        }

        if($is_in_audit){
            $result['list'] = array();
        }

        return array('result' => $result,
            'code' => C('ERROR_CODE.SUCCESS'));
    }

    public function testGetWinMessage()
    {
        $win_message_list = $this->_getWinMessageList();
        $result['list'] = $win_message_list;
        return array( 'result' => $result,
                      'code'   => C('ERROR_CODE.SUCCESS') );
    }

    private function _getWinMessageList()
    {
        $win_message_list = array();
        $redis = Factory::createAliRedisObj();
        if($redis){
            $redis->select(0);
            $data = $redis->get('tiger_api:home_page:tiger_api_win_message_list');
            if(!empty($data)){
                $win_message_list = json_decode($data,true);
            }
        }
        return $win_message_list;
    }

}