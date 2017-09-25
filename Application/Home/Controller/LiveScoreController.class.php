<?php
namespace Home\Controller;

class LiveScoreController extends BaseZcGameDataController
{

    public function getScheduleList($api)
    {
        $request_method = 'liveScore';
        $lottery_id = $api->lottery_id;
        $type = $api->type;
        $schedule_ids = $api->schedule_ids;
        $schedule_ids = $this->addFirstToScheduleIds($schedule_ids);

        $schedule_list = array();
        if ($type == C('API_SCHEDULE_STATUS_OF_FOLLOW')) {
            //过滤关注列表
            $schedule_ids = $this->filterScheduleList($schedule_ids);
            if (!empty($schedule_ids)) {
                $schedule_ids_str = implode('_', $schedule_ids);
                $request_data['id'] = $schedule_ids_str;
                $schedule_list = $this->requestJcDataFromBaseDataService($request_method,$request_data);
                $yesterday = date("Ymd", strtotime('-1 day'));
                $today = date("Ymd");
                $tomorrow = date("Ymd", strtotime('+1 day'));
                $new_schedule_list = array();
                foreach($schedule_list as $schedule){
                    if($schedule['schedule_date'] == $yesterday){
                        $new_schedule_list['yesterday'][] = $schedule;
                    }
                    if($schedule['schedule_date'] == $today){
                        $new_schedule_list['today'][] = $schedule;
                    }
                    if($schedule['schedule_date'] == $tomorrow){
                        $new_schedule_list['tomorrow'][] = $schedule;
                    }
                }
                $schedule_list = array_values($new_schedule_list);
            }
        } else {
            $request_data['status'] = $this->getRequestStatusForGetScheduleList($type);

            $yesterday = date("Ymd", strtotime('-1 day'));
            $today = date("Ymd");
            $tomorrow = date("Ymd", strtotime('+1 day'));

            $this->schedule_odd_list = $this->queryScheduleOddsList(array($yesterday,$today,$tomorrow));

            if($type == C('API_SCHEDULE_STATUS_OF_OVER')){

                $request_data['date'] = $tomorrow;
                $schedule_list[] = $this->requestJcDataFromBaseDataService($request_method,$request_data);

                $request_data['date'] = $today;
                $schedule_list[] = $this->requestJcDataFromBaseDataService($request_method,$request_data);

                $request_data['date'] = $yesterday;
                $schedule_list[] = $this->requestJcDataFromBaseDataService($request_method,$request_data);

            } elseif($type == C('API_SCHEDULE_STATUS_OF_NOBEGIN')){
                $request_data['date'] = $tomorrow;
                $schedule_list[] = $this->requestJcDataFromBaseDataService($request_method,$request_data);
            } else{
                $request_data['date'] = $yesterday;
                $schedule_list[] = $this->requestJcDataFromBaseDataService($request_method,$request_data);

                $request_data['date'] = $today;
                $schedule_list[] = $this->requestJcDataFromBaseDataService($request_method,$request_data);
            }
        }

        $format_data = $this->_formatScheduleList($schedule_list, $schedule_ids,$type);
        return array('result' => array('groups' => $format_data),
            'code' => C('ERROR_CODE.SUCCESS'));
    }

    protected function filterScheduleList($schedule_ids){
        $data = array();
        $request_method = 'liveScore';
        $schedule_list_all = $this->requestJcDataFromBaseDataService($request_method);
        foreach($schedule_list_all as $schedule_info){
            $data[] = $schedule_info['schedule_date'] . $schedule_info['schedule_no'];
        }
        foreach($schedule_ids as $k => $schedule_id){
            if(!in_array($schedule_id,$data)){
                unset($schedule_ids[$k]);
            }
        }
        return $schedule_ids;

    }

    private function _formatScheduleList($schedule_list, $schedule_ids ,$type = '')
    {
        $format_schedule_list = array();
        foreach ($schedule_list as $value) {
            $schedules = array();
            foreach ($value as $schedule_info) {
                $date = emptyToStr($schedule_info['schedule_date']);
                $name = emptyToStr(substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2) . ' ' . count($value) . '场');
                $id = strtotime($date);
                $schedule_data = $this->_formatScheduleInfo($schedule_info, $schedule_ids);
                $schedule_data['schedule_date_timestamp'] = $id;
                $schedules[] = $schedule_data;
            }
            /*if($type == C('API_SCHEDULE_STATUS_OF_OVER')){
                $schedules = $this->formateScheduleByTime($schedules);
            }*/
            if (!empty($value)) {
                $format_schedule_list[] = array(
                    'id' => $id,
                    'name' => $name,
                    'date' => strtotime($date),
                    'date_timestamp' => strtotime($date),
                    'schedules' => $schedules
                );
            }
        }
        return $format_schedule_list;
    }

    private function _formatScheduleInfo($schedule_info, $schedule_ids, $full_id=0)
    {
        if($full_id){
            $schedule_data['id'] = emptyToStr($schedule_info['schedule_date'] . $schedule_info['schedule_no']);
        }else{
            $schedule_data['id'] = emptyToStr($this->reduceFirstToScheduleId($schedule_info['schedule_date'] . $schedule_info['schedule_no']));
        }
        $schedule_data['home'] = emptyToStr($schedule_info['home_team']);
        $schedule_data['guest'] = emptyToStr($schedule_info['guest_team']);
        $schedule_data['league'] = emptyToStr($schedule_info['schedule_class']);
        $schedule_data['begin_date'] = strtotime($schedule_info['schedule_date']) ? strtotime($schedule_info['schedule_date']) : 0;
        $schedule_data['first_half_begin_time'] = strtotime($schedule_info['match_time']);
        $schedule_data['second_half_begin_time'] = strtotime($schedule_info['match_time2']);
        $schedule_data['match_duration'] = emptyToStr($this->calcMinute($schedule_info['schedule_game_status'], $schedule_info['match_time2']));
        $schedule_data['round_no'] = emptyToStr($schedule_info['schedule_no']);
        $schedule_data['half_score'] = emptyToStr($this->buildHalfScoreString($schedule_info['schedule_game_status'], $schedule_info['schedule_half_score']));
        $schedule_data['current_score'] = emptyToStr($this->buildScoreString($schedule_info['schedule_game_status'], $schedule_info['schedule_score']));
        $schedule_data['match_status_description'] = emptyToStr($this->buildScheduleStatusDesc($schedule_info['schedule_game_status']));
        if (in_array($schedule_info['schedule_date'] . $schedule_info['schedule_no'], $schedule_ids)) {
            $schedule_data['follow_status'] = C('API_SCHEDULE_FOLLOW');
        } else {
            $schedule_data['follow_status'] = C('API_SCHEDULE_NO_FOLLOW');
        }
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

    public function fetchScheduleListByScheduleNos($schedule_ids)
    {
        $response_data = array();
        if (!empty($schedule_ids)) {
            $schedule_ids_str = implode('_', $schedule_ids);
            $request_data['id'] = $schedule_ids_str;
            $request_method = 'liveScore';
            $schedule_list = $this->requestJcDataFromBaseDataService($request_method,$request_data);
            if(!empty($schedule_list)){
                $response_data = $this->formatScheduleListByScheduleNos($schedule_list, $schedule_ids);
            }
        }
        return $response_data;
    }

    protected function formatScheduleListByScheduleNos($schedule_list, $schedule_ids)
    {
        $new_schedule_list = array();
        foreach ($schedule_list as $schedule_info) {
            $new_schedule_list[] = $this->_formatScheduleInfo($schedule_info, $schedule_ids ,1);
        }
        return $new_schedule_list;
    }

}