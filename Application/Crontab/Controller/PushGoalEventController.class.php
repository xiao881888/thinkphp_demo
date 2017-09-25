<?php
namespace Crontab\Controller;

use Content\Util\Factory;
use Think\Controller;

class PushGoalEventController extends Controller
{

    private $redis;

    public function __construct()
    {
        $this->_initRedis();
        parent::__construct();
    }

    private function _initRedis()
    {
        $this->redis = Factory::createAliRedisObj();
        $this->redis->select(0);
    }

    public function getTodayScheduleList()
    {
        $jz_lottery_ids = array(601, 602, 603, 604, 605, 606);
        $today = getTodayString();
        $jc_schedule_list = D('JcSchedule')->getScheduleList($today, $jz_lottery_ids);//JcSchedule
        $this->_setJcScheduleListToRedis($jc_schedule_list);
    }

    private function _setJcScheduleListToRedis($jc_schedule_list)
    {
        foreach ($jc_schedule_list as $schedule_info) {
            $schedule_id = $schedule_info['schedule_day'] . $schedule_info['schedule_round_no'];
            $schedule_end_time = $schedule_info['schedule_end_time'];
            $jc_schedule_id = $schedule_info['schedule_id'];
            $schedule_ids[$schedule_id][] = $jc_schedule_id;

            $schedule_data = array(
                'id' => $schedule_id,
                'jc_schedule_ids' => json_encode($schedule_ids[$schedule_id]),
                'schedule_end_time' => $schedule_end_time,
                'push_status' => 1
            );

            foreach ($schedule_data as $redis_hash_k => $redis_hash_v) {
                $this->redis->hSet(C('PUSH_GOAL_EVENT') . 'jc_schedule_list_' . $schedule_id, $redis_hash_k, $redis_hash_v);
            }

            $this->redis->expire(C('PUSH_GOAL_EVENT') . 'jc_schedule_list_' . $schedule_id, 7*24*60*60);

            $this->redis->sAdd(C('PUSH_GOAL_EVENT') . 'jc_schedule_ids', $schedule_id);
        }
    }

    public function pushAttentionUsersToList()
    {
        $schedule_list = $this->_getScheduleListOfBetEnd();
        $this->_pushAttentionUsersToList($schedule_list);
    }

    private function _pushAttentionUsersToList($schedule_list)
    {
        foreach ($schedule_list as $schedule_id) {
            $jc_schedule_ids = json_decode($this->redis->hGet(C('PUSH_GOAL_EVENT') . 'jc_schedule_list_' . $schedule_id, 'jc_schedule_ids'), true);
            $uids = D('JcOrderDetailView')->getUsersByScheduleIds($jc_schedule_ids);
            foreach ($uids as $uid) {
                $this->redis->sAdd(C('PUSH_GOAL_EVENT') . 'notify_uids' . $schedule_id, $uid);
            }
            $this->redis->expire(C('PUSH_GOAL_EVENT') . 'notify_uids' . $schedule_id, 7*24*60*60);
        }
    }

    private function _getScheduleListOfBetEnd()
    {
        $schedule_list = array();
        $current_time = getCurrentTime();
        $schedule_ids = $this->redis->sMembers(C('PUSH_GOAL_EVENT') . 'jc_schedule_ids');
        foreach ($schedule_ids as $schedule_id) {
            $push_status = $this->redis->hGet(C('PUSH_GOAL_EVENT') . 'jc_schedule_list_' . $schedule_id, 'push_status');
            $schedule_end_time = $this->redis->hGet(C('PUSH_GOAL_EVENT') . 'jc_schedule_list_' . $schedule_id, 'schedule_end_time');
            if ($current_time > $schedule_end_time && $push_status == 1) {
                $schedule_list[] = $schedule_id;
            }
        }
        return $schedule_list;
    }

    private function _getFirstCurrDate()
    {
        //获取当天的年份
        $y = date("Y");
        //获取当天的月份
        $m = date("m");
        //获取当天的号数
        $d = date("d");
        //将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
        $firstTodayTime = mktime(0, 0, 0, $m, $d, $y);
        return date('Y-m-d H:i:s', $firstTodayTime);
    }

    private function _getLastCurrDate()
    {
        //获取当天的年份
        $y = date("Y");
        //获取当天的月份
        $m = date("m");
        //获取当天的号数
        $d = date("d");
        //将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
        $LastTodayTime = mktime(0, 0, 0, $m, $d + 1, $y);
        return date('Y-m-d H:i:s', $LastTodayTime);
    }


}
