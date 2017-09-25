<?php
namespace Admin\Controller;

use Admin\Controller\GlobalController;

/**
 * @date 2014-12-3
 * @author tww <merry2014@vip.qq.com>
 */
class InformationController extends GlobalController
{

    protected $is_check = 1;
    protected $not_check = 0;

    protected $success_status = 0;
    protected $error_status = 1;

    protected $schedule_no_exist = '该竞彩比赛不存在';
    protected $data_invaild = '数据不合法';

    public function _before_index()
    {
        $_REQUEST['information_check_status'] = $this->is_check;
        $this->assignInformationCategory();
    }

    public function _before_add()
    {
        $this->assignRecommentLotteryList();
        $this->assignInformationCategory();
    }

    public function _before_edit()
    {
        $this->assignRecommentLotteryList();
        $this->assignInformationCategory();
    }

    protected function assignInformationCategory()
    {
        $category_map = D('InformationCategory')->getCategoryMap();
        $this->assign('category_map', $category_map);
    }

    protected function assignRecommentLotteryList()
    {
        $information_id = I('id');
        $information_category_id = M('Information')->where(array('information_id' => $information_id))->getField('information_category_id');
        $recommentLotteryId = D('InformationCategory')->getRecommentLotteryIdById($information_category_id);
        $this->assign('recommentLotteryId', $recommentLotteryId);

        $lottery_list = D('InformationCategory')->getRecommentLotteryList();
        $this->assign('lottery_list', $lottery_list);
    }

    /**
     * 根据用户输入的场次ID判断竞彩玩法是否存在
     */
    public function checkRecommentPlayId()
    {
        $data = array();
        $schedule_id = I('play_id');
        $schedule = $this->getScheduleInfoById($schedule_id);
        if (empty($schedule)) {
            $data['error_code'] = $this->error_status;
            $data['msg'] = $this->schedule_no_exist;
            $this->ajaxReturn($data);
        }

        $lottery_id = I('lottery_id', 0);
        if (!$this->checkRecommentLotteryId($schedule, $lottery_id)) {
            $data['error_code'] = $this->error_status;
            $data['msg'] = $this->data_invaild;
            $this->ajaxReturn($data);
        }

        $data['error_code'] = $this->success_status;
        $this->ajaxReturn($data);
    }

    /**
     * 判断输入的推荐内容合法
     */
    public function checkContent()
    {
        $data = array();
        $schedule_id = I('play_id');
        $schedule = $this->getScheduleInfoById($schedule_id);
        if (empty($schedule)) {
            $data['error_code'] = $this->error_status;
            $data['msg'] = $this->schedule_no_exist;
            $this->ajaxReturn($data);
        }

        $content = I('content');
        if (!$this->checkRecommentContent($schedule, $content)) {
            $data['error_code'] = $this->error_status;
            $data['msg'] = $this->data_invaild;
            $this->ajaxReturn($data);
        }

        $data['error_code'] = $this->success_status;
        $data['msg'] = $content;
        $this->ajaxReturn($data);

    }

    public function changeStatus(){
        parent::changeStatus(D('Information'));
    }

    public function checkPostForm()
    {
        $data = array();
        $schedule_id = I('play_id');
        $schedule = $this->getScheduleInfoById($schedule_id);
        if (empty($schedule)) {
            $data['error_code'] = $this->error_status;
            $data['msg'] = $this->schedule_no_exist;
            $this->ajaxReturn($data);
        }
        $content = I('content');
        $lottery_id = I('lottery_id', 0);
        if (!$this->checkRecommentLotteryId($schedule, $lottery_id)) {
            $data['error_code'] = $this->error_status;
            $data['msg'] = $this->data_invaild;
            $this->ajaxReturn($data);
        }

        if (!$this->checkRecommentContent($schedule, $content)) {
            $data['error_code'] = $this->error_status;
            $data['msg'] = $this->data_invaild;
            $this->ajaxReturn($data);

        }

        $data['error_code'] = $this->success_status;
        $data['msg'] = $content;
        $this->ajaxReturn($data);
    }

    protected function getScheduleInfoById($schedule_id)
    {
        return M('jcSchedule')->where(array('schedule_id' => $schedule_id))->find();
    }

    protected function checkRecommentLotteryId($schedule, $lottery_id)
    {
        $schedule_lottery_id = $schedule['lottery_id'];
        return ($schedule_lottery_id != $lottery_id) ? false : true;
    }

    protected function checkRecommentContent($schedule, $content)
    {
        //获取赔率
        $schedule_odds = $schedule['schedule_odds'];
        $schedule_odds = json_decode($schedule_odds, true);
        $isInArray = array_keys($schedule_odds);
        if (strpos($content, ',') === false) {
            if (!in_array($content, $isInArray)) {
                return false;
            }
        } else {
            $content_arr = explode(',', $content);
            foreach ($content_arr as $v) {
                if (!in_array($v, $isInArray)) {
                    return false;
                }
            }
        }
        return true;

    }
}