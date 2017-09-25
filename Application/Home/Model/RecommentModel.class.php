<?php
namespace Home\Model;

use Think\Model;

class RecommentModel extends Model
{

    const IS_RECOMMENT = 1;
    const JZ_LOTTERY = 6;
    const JL_LOTTERY = 7;

    const JL_MIX_LOTTERY_ID = '705';
    const JZ_MIX_LOTTERY_ID = '606';

    /**
     * 获取推荐列表
     * @return mixed
     */
    Public function getJcRecommentLottery($lottery_id_list = array(self::JZ_LOTTERY))
    {
        $now_time = getCurrentTime();
        $where = array(
            'recomment_status'    => self::IS_RECOMMENT,
            'lottery_id'          => $lottery_id_list,
            'recomment_starttime' => array( 'elt', $now_time ),
            'recomment_endtime'   => array( 'egt', $now_time )
        );
        return $this->where($where)
            ->order('recomment_updatetime DESC')
            ->field('recomment_id,lottery_id,recomment_play_id,recomment_play_type,recomment_content,issue_desc,recomment_logo,recomment_logo2')->order('rand()')->find();
    }

    /**
     * 获取推荐列表
     * @return mixed
     */
    Public function getJcRecommentLotteryNew()
    {
        return $this->getJcRecommentLottery(array( self::JZ_LOTTERY, self::JL_LOTTERY, 'or' ));
    }


    /**
     * 获取竞猜的相关信息
     * @param $jcLotteryInfo 推荐竞猜的相关信息
     * @return array
     */
    public function getScheduleInfo($jcLotteryInfo,$api)
    {
        $data = array();
        $schedule_id = $jcLotteryInfo['recomment_play_id'];
        $scheduleInfo = $this->_getScheduleInfoById($schedule_id);
        if (!empty($scheduleInfo)) {
            $lottery_id = $scheduleInfo['lottery_id'];
            $lottery = $this->_getLotteryInfoById($lottery_id);
            $lottery_name = $lottery['lottery_name'];
            $lottery_ahead_endtime = $lottery['lottery_ahead_endtime'];

            $sdk_version = $api->sdk_version;
            if($sdk_version < 8 && $api->os == OS_OF_ANDROID && isJclq($lottery_id)){
                $home_logo  = $jcLotteryInfo['recomment_logo2'];
                $guest_logo = $jcLotteryInfo['recomment_logo'];
            }else{
                $home_logo  = $jcLotteryInfo['recomment_logo'];
                $guest_logo = $jcLotteryInfo['recomment_logo2'];
            }



            $data = array(
                'lottery_id'         => $lottery_id,
                'lottery_name'       => $lottery_name,
                'play_type'          => $this->_exchangePlayType($scheduleInfo['play_type']),
                'bet_number'         => $jcLotteryInfo['recomment_content'],
                'betting_score_odds' => getFormatOdds($lottery_id, $scheduleInfo['schedule_odds']),
                'betting_order'      => $this->_getJcContent($scheduleInfo, $jcLotteryInfo['recomment_content'], $scheduleInfo['lottery_id'], $jcLotteryInfo['recomment_play_type']),
                'schedule_id'        => $schedule_id,
                'home'               => $scheduleInfo['schedule_home_team'],
                'guest'              => $scheduleInfo['schedule_guest_team'],
                'home_logo'          => $home_logo,
                'guest_logo'         => $guest_logo,
                'end_time'           => strtotime($scheduleInfo['schedule_end_time']) - $lottery_ahead_endtime,
                'round_no'           => $scheduleInfo['schedule_round_no'],
            );
            $data = array_map('emptyToStr', $data);
        }
        return $data;
    }

    private function _getScheduleInfoById($schedule_id)
    {
        return M('jcSchedule')->where(array( 'schedule_id' => $schedule_id ))->find();
    }

    private function _getLotteryInfoById($lottery_id)
    {
        return M('lottery')->field('lottery_name,lottery_ahead_endtime')->where(array( 'lottery_id' => $lottery_id ))->find();
    }

    private function _exchangePlayType($play_type)
    {
        if ($play_type == C('JC_PLAY_TYPE.ONE_STAGE')) {
            return 1;
        } elseif ($play_type == C('JC_PLAY_TYPE.MULTI_STAGE')) {
            return 2;
        }
    }

    private function _getJcKey($lotery_id)
    {
        $key_conf = array(
            C('JCZQ.NO_CONCEDE') => 'betting_score_no_concede',
            C('JCZQ.CONCEDE')    => 'betting_score_concede',
            C('JCZQ.SCORES')     => 'betting_score_scores',
            C('JCZQ.BALLS')      => 'betting_score_balls',
            C('JCZQ.HALF')       => 'betting_score_half',
            C('JCZQ.MIX')        => 0,
            C('JCLQ.NO_CONCEDE') => 'betting_score_no_concede',
            C('JCLQ.CONCEDE')    => 'betting_score_concede',
            C('JCLQ.SFC')        => 'betting_score_sfc',
            C('JCLQ.DXF')        => 'betting_score_dxf',
            C('JCLQ.MIX')        => 0,
        );
        return $key_conf[$lotery_id];
    }

    /**
     * 获取选中的竞彩内容
     * @param $schedule 推荐的竞足比赛
     * @param $content 推荐的内容
     * @param $lottery_id 推荐的彩种ID
     * @param $play_type 推荐的玩法
     * @return mixed
     */
    private function _getJcContent($schedule, $content, $lottery_id, $play_type)
    {
        //获取赔率
        $schedule_odds = $schedule['schedule_odds'];
        $schedule_odds = json_decode($schedule_odds, true);
        $isInArray = array();
        if ($lottery_id == self::JZ_MIX_LOTTERY_ID || $lottery_id == self::JL_MIX_LOTTERY_ID) {
            if (!empty($play_type)) {
                $jz_key = $this->_getJcKey($play_type);
            }
            foreach ($schedule_odds as $k => $v) {
                if ($k == $play_type) {

                    if (strpos($content, ',') === false) {
                        $result[$jz_key][$content] = $v[$content];
                    } else {
                        $content_arr = explode(',', $content);
                        foreach ($content_arr as $v_arr) {
                            $result[$jz_key][$v_arr] = $v[$v_arr];
                        }
                    }
                }
            }

        } else {
            $jz_key = $this->_getJcKey($lottery_id);
            if (strpos($content, ',') === false) {
                $result[$jz_key][$content] = $schedule_odds[$content];
            } else {
                $content_arr = explode(',', $content);
                foreach ($content_arr as $v) {
                    $result[$jz_key][$v] = $schedule_odds[$v];
                }
            }
        }
        return $result;
    }

}