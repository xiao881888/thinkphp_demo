<?php
namespace H5\Controller;

use Home\Controller\RecommentController;
use Think\Exception;

class HomeController extends BaseController{

    const H5_BANNER_TYPE_ID = 3;
    const DEFAULT_APP_ID = 1;

    public function getBanner()
    {
        $result = array();
        $this->response($result);
        if ($this->checkLogin()){
            $app_id = getRegAppId(self::getModelInstance('User')->getUserInfo($this->uid));
        }else{
            $app_id = I('app_id',self::DEFAULT_APP_ID);
        }
        $activity_list = self::getModelInstance('Activity')->getActivityList(1, 10, 0, self::H5_BANNER_TYPE_ID, '',(int)$app_id);

        foreach($activity_list as $activity) {
            $result[] = array(
                'id'            => $activity['activity_id'],
                'title'         => $activity['activity_name'],
                'type'          => $activity['activity_type'],
                'target'        => empty($activity['activity_target']) ? $this->_getTargetUrl($activity) : $activity['activity_target'],
                'image'         => $activity['activity_image'],
                'lottery_id'    => $activity['lottery_id'],
                'schedule_id'   => $activity['activity_schedule_id'],
            );
        }

        $this->response($result);
    }

    private function _getTargetUrl($activity)
    {
        if (count(explode('h5',$_SERVER['HTTP_HOST'])) > 1){
            if (isset($_SERVER['SERVER_RUN_MODE']) and $_SERVER['SERVER_RUN_MODE'] == 'PRERELEASE'){
                return U('Content/Activity/detail@prerelease-phone-api.tigercai.com',array('id'=>$activity['activity_id']));
            }else if(get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION'){
                return U('Content/Activity/detail@phone-api-tigercai.com',array('id'=>$activity['activity_id']));
            }else if(get_cfg_var('PROJECT_RUN_MODE') == 'TEST'){
                return U('Content/Activity/detail@test-phone-api.tigercai.com',array('id'=>$activity['activity_id']));
            }
        }
        return 'http://'.$_SERVER['HTTP_HOST'].U('Content/Activity/detail', array('id'=>$activity['activity_id']));
    }

    public function getRecommentIssue()
    {
        $data = $this->_getSzcRecommentData();
        $this->response($data);
    }
    
    private function _getSzcRecommentData()
    {
        $lottery_id = $this->_getSzcRandLottery();
        return (new RecommentController())->getSzcRandContentForH5($lottery_id);
    }

    private function _getSzcRandLottery()
    {
        $lottery_ids = C('RANDOM_LOTTERY.LOTTERY_ID');
        foreach ($lottery_ids as $key => $id){
            $lottery_info = D('Lottery')->getLotteryInfo($id);
            if ($lottery_info['lottery_status'] == C('LOTTERY_STATUS.STOP')){
                unset($lottery_ids[$key]);
            }
        }

        return $lottery_ids[array_rand($lottery_ids)];
    }


}