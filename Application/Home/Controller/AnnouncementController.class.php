<?php

namespace Home\Controller;

use Think\Controller;

class AnnouncementController extends Controller{

    const ACTIVITY_START_TIME = '2017-03-22 00:00:00';
    const Iran_ACTIVITY_START_TIME = '2017-03-27 00:00:00';

    public function chinaVSIranAd(){
         $now = getCurrentTime();
         if($now >= self::Iran_ACTIVITY_START_TIME){
             $this->redirect('WebReg/chinaVSIranReg');
             die;
         }else{
             $this->display();
         }
    }

	public function chinaVSkoreaAd(){
        $now = getCurrentTime();
        if($now >= self::ACTIVITY_START_TIME){
            $this->redirect('WebReg/chinaVSkoreaReg');
            die;
        }else{
            $this->display();
        }
	}

	public function getNoticeInfo($api){
        $app_id = getRequestAppId($api->bundleId);
	    $notice_info = D('Notice')->geiNoticeInfo($app_id);
        return array(	'result' => array(
                'content'=>emptyToStr($notice_info['notice_content']),
                'id'=>(int)$notice_info['notice_id']
            ),
            'code'   => C('ERROR_CODE.SUCCESS'));

    }
}

