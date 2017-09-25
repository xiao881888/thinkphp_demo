<?php
namespace H5\Middleware;

use Think\Exception;

class WebBetMiddleware
{
    public static function boot($request)
    {
        $lottery_id = (int)$request['lottery_id'];
        if (!$lottery_id){
            throw new Exception('无效的彩种ID');
        }
        $lottery_info = D('Lottery')->where(['lottery_id' => $lottery_id])->find();

        if (empty($lottery_info) || $lottery_info['lottery_status'] == 0){
            throw new Exception('彩种不存在或暂停销售');
        }

        if (isJc($lottery_id)){
            return self::validBetJC($request);
        }elseif (isZcsfc($lottery_id)){
            return self::validBetLZC($request);
        }else{
            return self::validBetSZC($request);
        }
        return false;
    }

    public static function validBetSZC($param)
    {
        $valid_regex = [
            'multiple' => '^[1-9]\d{0,3}$',
            'follow_times' => '^[1-9]\d{0,3}$',
            'issue_id' => '^[1-9]\d{0,9}$',
            'total_amount' => '^[1-9]\d{0,7}$',
        ];
        self::_regexList($param,$valid_regex);
        $valid_ticket = [
            'play_type' => '^\d{1,3}$',
            'bet_type' => '^\d{1,3}$',
            'stake_count' => '^[1-9]\d{0,7}$',
            'total_amount' => '^[1-9]\d{0,7}$',
        ];
        foreach ($param['tickets'] as $ticket) {
            self::_regexList($ticket,$valid_ticket);
            //todo 限号处理
        }

        self::_validIssue($param['lottery_id'],$param['issue_id']);

        return true;
    }

    public static function validBetLZC($param)
    {
        $valid_regex = [
            'multiple' => '^[1-9]\d{0,3}$',
            'issue_no' => '^\d{5}$',
            'total_amount' => '^[1-9]\d{0,7}$',
            'stake_count' => '^[1-9]\d{0,7}$',
            'bet_type' => '^\d$',
            'play_type' => '^\d{3}$',
        ];
        self::_regexList($param,$valid_regex);
        foreach ($param['schedule_orders'] as $order){
            $valid_order = [
                'is_sure' => '^[0,1]$',
                'round_id' => '^\d{1,2}$',
            ];
            self::_regexList($order,$valid_order);
        }
        return true;
    }
    
    public static function validBetJC($param)
    {
        $common_regex = [
            'series' => '^\d{1,3}$',
            'play_type' => '^\d{1,3}$',
            'stake_count' => '^[1-9]\d{0,7}$',
            'total_amount' => '^[1-9]\d{0,7}$',
        ];
        self::_regexList($param,$common_regex);
        if (!empty($param['optimize_ticket_list'])){
            self::_validJCOptimeize($param);
        }else{
            self::_validJCCommon($param);
        }

        return true;
    }

    private function _validJCOptimeize($param)
    {
        self::_regexList($param,['order_multiple' => '^[1-9]\d{0,3}$',]);
        foreach ($param['optimize_ticket_list'] as $key => $ticket){
            $valid_ticket = [
                'series_type' => '^\d{3}$',
                'ticket_multiple' => '^[1-9]\d{0,3}$',
            ];
            self::_regexList($ticket,$valid_ticket);
            foreach ($ticket['ticket_schedules'] as $schedule){
                $valid_schedule = [
                    'id' => '^\d{1,11}$',
                    'schedule_lottery_id' => '^[6,7]0\d{1}$',
                    //'bet_options' => '',
                ];
                self::_regexList($schedule,$valid_schedule);
                self::_validSchedule($schedule['id']);
            }
        }
    }

    private function _validJCCommon($param)
    {
        self::_regexList($param,['multiple' => '^[1-9]\d{0,3}$',]);
        foreach ($param['schedule_orders'] as $key => $order){
            $valid_order = [
                'is_sure' => '^[0,1]$',
                'round_no' => '^\d{0,3}$',
                'match_round_id' => '^\d{8}-\d{3}$',
                'schedule_id' => '^\d{1,11}$',
            ];
            self::_regexList($order,$valid_order);
            self::_validSchedule($order['schedule_id'],$order['match_round_id']);
        }
    }

    private function _validSchedule($schedule_id,$no = '')
    {
        $schedule_info = D('JcSchedule')->getIssueInfo($schedule_id);

        if (empty($schedule_info) or $schedule_info['schedule_status'] != 1){
            throw new Exception('找不到指定或包含已暂停销售的场次:'.$schedule_id);
        }

        if (!empty($no)){
            if ($schedule_info['schedule_day'].'-'.$schedule_info['schedule_round_no'] != $no){
                throw new Exception('ID和场次号不一致');
            }
        }

        if (time() > strtotime($schedule_info['schedule_end_time']) - (11*60)){
            throw new Exception('含有已截止的场次:'.$no ? $no : $schedule_id);
        }
    }

    private function _validIssue($lottery_id,$issue_id)
    {
        $issue_info = D('Home/Issue')->where(['lottery_id' => $lottery_id,'issue_no' => $issue_id])->find();
        if (time() > strtotime($issue_info['issue_end_time'])){
            throw new Exception('彩期已截止：'.$issue_id);
        }

        if ($issue_info['issue_is_current'] != 1) {
            throw new Exception('下单失败 '.$issue_id.'不是当前期');
        }

        return $issue_info;
    }

    private function _regexList($param,$regex_list)
    {
        foreach ($regex_list as $key => $regex){
            if (!validByRegex($param[$key],$regex)){
                throw new Exception('参数校验错误：'.$key);
            }
        }

        return true;
    }
}
