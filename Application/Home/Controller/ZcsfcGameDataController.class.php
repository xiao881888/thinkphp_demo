<?php
namespace Home\Controller;

use Home\Controller\GlobalController;

class ZcsfcGameDataController extends BaseZcGameDataController
{
    public function getScheduleList($api)
    {
        $request_method = 'liveScore';
        $issue_no = $api->issue_no;
        if(empty($issue_no)){
            $issue_no_list = $this->_getIssueNoList();
        }else{
            $issue_no_list[] = $issue_no;
        }
        foreach($issue_no_list as $issue_no){
            $request_data['issue_no'] = $issue_no;
            $schedule_list[$issue_no] = $this->requestJcDataFromBaseDataService($request_method,$request_data);
        }
        $schedule_list = $this->_formatScheduleList($schedule_list);
        return array('result' => array('groups'=>$schedule_list),
            'code' => C('ERROR_CODE.SUCCESS'));
    }

    public function fetchScheduleListByIssueNo($issue_no)
    {
        $request_method = 'liveScore';
        $request_data['issue_no'] = $issue_no;
        $schedule_list[$issue_no] = $this->requestJcDataFromBaseDataService($request_method,$request_data);
        return $this->_formatScheduleListByIssueNo($schedule_list,$issue_no);
    }

    private function _formatScheduleListByIssueNo($schedule_list,$issue_no)
    {
        $schedule_data = array();
        foreach ($schedule_list as $issue_no => $issue_no_schedule_list) {
            foreach ($issue_no_schedule_list as $schedule_info) {
                $schedule_data[] = $this->_formatScheduleInfo($schedule_info,$issue_no);
            }
        }
        return $schedule_data;
    }


    private function _formatScheduleList($schedule_list)
    {
        $data = array();
        $current_issue_no = $this->_getCurrentIssueNo();

        foreach ($schedule_list as $issue_no => $issue_no_schedule_list) {
            $is_current = 0;
            $schedule_data = array();
            foreach ($issue_no_schedule_list as $schedule_info) {
                $schedule_data[] = $this->_formatScheduleInfo($schedule_info,$issue_no);
            }
            if($current_issue_no == $issue_no){
                $is_current = 1;
            }
            $data[] = array(
                'id' => $issue_no,
                'is_current' => $is_current,
                'schedules' => $schedule_data
            );
        }
        return $data;
    }

    private function _getCurrentIssueNo(){
        $where['lottery_id'] = TIGER_LOTTERY_ID_OF_SFC_14;
        $where['issue_is_current'] = C('ISSUE_IS_CURRENT.YES');
        $issue_no = M('Issue')->where($where)->order('issue_no')->getField('issue_no');
        if(!empty($issue_no)){
            return $issue_no;
        }

        $where['lottery_id'] = TIGER_LOTTERY_ID_OF_SFC_14;
        $where['issue_is_current'] = 3;
        $issue_no = M('Issue')->where($where)->order('issue_no DESC')->getField('issue_no');
        return $issue_no;
    }

    private function _formatScheduleInfo($schedule_info, $issue_no='')
    {
        $schedule_data['id'] = $issue_no.$schedule_info['schedule_no'];
        $schedule_data['home'] = emptyToStr($schedule_info['home_team']);
        $schedule_data['guest'] = emptyToStr($schedule_info['guest_team']);
        $schedule_data['league'] = emptyToStr($schedule_info['schedule_class']);
        $schedule_data['begin_date'] = strtotime($schedule_info['schedule_date']) ? strtotime($schedule_info['schedule_date']) : 0;
        $schedule_data['first_half_begin_time'] = strtotime($schedule_info['match_time']) ? strtotime($schedule_info['match_time']) : 0;
        $schedule_data['second_half_begin_time'] = strtotime($schedule_info['match_time2']) ? strtotime($schedule_info['match_time2']) : 0;
        $schedule_data['match_duration'] = emptyToStr($this->calcMinute($schedule_info['schedule_game_status'], $schedule_info['match_time2']));
        $schedule_data['round_no'] = emptyToStr($schedule_info['schedule_no']);
        $schedule_data['issue_no'] = $issue_no;
        $schedule_data['half_score'] = emptyToStr($this->buildHalfScoreString($schedule_info['schedule_game_status'], $schedule_info['schedule_half_score']));
        $schedule_data['current_score'] = emptyToStr($this->buildScoreString($schedule_info['schedule_game_status'], $schedule_info['schedule_score']));
        $schedule_data['match_status_description'] = emptyToStr($this->buildScheduleStatusDesc($schedule_info['schedule_game_status']));
        $schedule_data['match_status'] = emptyToStr($this->buildScheduleStatus($schedule_info['schedule_game_status']));
        $key = $schedule_info['schedule_date'] . $schedule_info['schedule_no'];
        $schedule_data['result_odds'] = json_decode($this->schedule_odd_list[$key],true);
        if($schedule_info['has_data'] == 1){
            $schedule_data['third_party_schedule_id'] = $schedule_info['schedule_qt_id'];
        }else{
            $schedule_data['third_party_schedule_id'] = '';
        }
        if(!empty($schedule_info['technic'])){
            $schedule_data['home_info'] = $this->getImportantData($schedule_info['technic'],'home');
            $schedule_data['guest_info'] = $this->getImportantData($schedule_info['technic'],'guest');
        }
        return $schedule_data;
    }

    private function _getIssueNoList(){
        //$where['issue_is_current'] = self::IS_CURRENT_ISSUE;
        $where['lottery_id'] = TIGER_LOTTERY_ID_OF_SFC_14;
        return M('Issue')->where($where)->order('issue_no DESC')->limit(0,6)->getField('issue_no',true);
    }




}