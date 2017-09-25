<?php 
namespace Home\Model;
use Think\Model;

class ActivityModel extends Model {
    
    public function getActivityList($offset=0, $limit=10, $activity_position=0, $platform=0, $user_info=array(),$app_id = 0) {
        $condition = array( 
            'activity_status'       => 1,
            'activity_start_time'   => array('LT', getCurrentTime()), 
            'activity_end_time'     => array('GT', getCurrentTime()),
            'activity_platform'     => 0
        );

        if ($app_id){
            $condition['app_id'] = array('IN',array($app_id,0));
        }

        if($activity_position){
        	$condition['activity_position'] = $activity_position;
        }

        if ($platform != 0) {
            $condition['activity_platform'] = array('IN', array(0, $platform));
        }

        if ($user_info['user_big_vip'] == 1) {
            $condition['activity_is_protect_vip'] = 0;
        }

        return $this->field('activity_id, activity_name, activity_image, activity_type, activity_target,lottery_id,activity_schedule_id,activity_is_popup,activity_popup_img1,activity_popup_img2,activity_popup_img3,activity_popup_img4,activity_popup_img5')
                    ->where($condition)
                    ->order('activity_sort DESC')
                    ->limit($offset, $limit)
                    ->select();
    }
    
}


?>