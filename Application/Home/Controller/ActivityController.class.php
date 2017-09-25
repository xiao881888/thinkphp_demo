<?php
namespace Home\Controller;
use Home\Controller\GlobalController;

class ActivityController extends GlobalController {
    
    public function getActivityList($api) {
        $app_id = getRequestAppId($api->bundleId);
        $result = array();
        if(empty($api->type)){
        	$api->type = 0;
        }

        $uid = D('Session')->getUid($api->session);
        if ($uid) {
            $user_info = D('User')->getUserInfo($uid);
        } else {
            $user_info = array();
        }
        $activity_list = D('Activity')->getActivityList($api->offset, $api->limit, $api->type, $api->os, $user_info,$app_id);

        foreach($activity_list as $activity) {
            //AppStore联运包版本太低，提示新版本升级
            if ($api->bundleId != 'com.liuyu.lhcp' && $activity['activity_id'] == 59) {
                continue;
            }
            if($activity['activity_is_popup'] == 1 && !empty($api->size)){
                if($api->os == OS_OF_IOS){
                    $activity_popup_image = $api->size == '1242_2208' ? $activity['activity_popup_img1'] : $activity['activity_popup_img2'];
                }else{
                    switch ($api->size) {
                        case '640_960':
                            $activity_popup_image = $activity['activity_popup_img4'];
                            break;
                        case '640_1136':
                            $activity_popup_image = $activity['activity_popup_img4'];
                            break;
                        case '750_1334':
                            $activity_popup_image = $activity['activity_popup_img5'];
                            break;
                        case '1242_2208':
                            $activity_popup_image = $activity['activity_popup_img5'];
                            break;
                        case '480_800' :
                            $activity_popup_image = $activity['activity_popup_img3'];
                            break;
                        case '720_1280' :
                            $activity_popup_image = $activity['activity_popup_img5'];
                            break;
                        case '1080_1920' :
                            $activity_popup_image = $activity['activity_popup_img5'];
                            break;
                        default:
                            $activity_popup_image = $activity['activity_popup_img4'];
                            break;
                    }
                }
            }

            $result[] = array(
                'id'            => $activity['activity_id'],
                'title'         => $activity['activity_name'],
                'type'          => $activity['activity_type'],
                'target'        => empty($activity['activity_target']) ? 'http://'.$_SERVER['HTTP_HOST'].U('Content/Activity/detail', array('id'=>$activity['activity_id'])) : $activity['activity_target'],
                'image'         => $activity['activity_image'],
                'lottery_id'    => $activity['lottery_id'],
                'schedule_id'   => $activity['activity_schedule_id'],
                'is_recommend'   => $activity['activity_is_popup'],
                'recommend_image'   => emptyToStr($activity_popup_image),
            );
        }

		$audit_version = D('IssueSwitch')->getSwitchOffList();

        if ($api->os == 1 && array_key_exists($api->channel_id, $audit_version) && $api->app_version === $audit_version[$api->channel_id] ) {
            $is_in_audit = true;
        } else {
            $is_in_audit = false;
        }
                // $is_in_audit = false;

        if($is_in_audit){
            $result = array();
        }

        return array(   
            'result' => array('list'=>$result),
            'code'   => C('ERROR_CODE.SUCCESS')
        );
    }

    public function test(){
        echo get_client_ip(0, true);
    }
    
    
}

?>