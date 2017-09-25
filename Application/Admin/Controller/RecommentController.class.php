<?php
namespace Admin\Controller;

use Admin\Controller\GlobalController;

class RecommentController extends GlobalController
{

    const ERROR_STATUS = 1;
    const SUCCESS_STATUS = 0;

    const SCHEDULE_NOT_EXIST = '该竞彩比赛不存在';
    const DATA_FAIL = '存在数据不合法';

    const JZ_FIX = '606';
    const JL_FIX = '705';

    const IS_FIX = 1;
    const NOT_FIX = 2;


	/**
	 * 根据用户输入的场次ID判断竞彩玩法是否存在
	 */
	public function ajaxPlayType()
	{
		$data = array();
		$schedule_id = I('play_id');
		$schedule = $this->_getScheduleInfoById($schedule_id);
		if (empty($schedule)) {
			$data['error_code'] = self::ERROR_STATUS;
			$data['msg'] = self::SCHEDULE_NOT_EXIST;
			$this->ajaxReturn($data);
		}
		$lottery_id = $schedule['lottery_id'];

        $data['msg'] = $lottery_id;
        $data['error_code'] = self::SUCCESS_STATUS;
        $this->ajaxReturn($data);

		/*if ($lottery_id == self::JZ_FIX || $lottery_id == self::JL_FIX) {
			$data['error_code'] = self::SUCCESS_STATUS;
            if($lottery_id == self::JZ_FIX){
                $data['msg'] = self::JZ_FIX;
            }else{
                $data['msg'] = self::JL_FIX;
            }
			$this->ajaxReturn($data);
		} else {
			$data['error_code'] = self::SUCCESS_STATUS;
			$this->ajaxReturn($data);
		}*/
	}

	/**
	 * 判断场次ID是否存在
	 */
	public function ajaxPlayId()
	{
		$data = array();
		$schedule_id = I('play_id');
		$schedule = $this->_getScheduleInfoById($schedule_id);
		if (empty($schedule)) {
			$data['error_code'] = self::ERROR_STATUS;
			$data['msg'] = self::SCHEDULE_NOT_EXIST;
			$this->ajaxReturn($data);
		}

		$content = I('content');
		$lottery_id = $schedule['lottery_id'];

		//获取赔率
		$schedule_odds = $schedule['schedule_odds'];
		$schedule_odds = json_decode($schedule_odds, true);
		$isInArray = array();
		if ($lottery_id != self::JZ_FIX && $lottery_id != self::JL_FIX) {
			$isInArray = array_keys($schedule_odds);
		} else {
			$play_type = I('play_type');
			foreach ($schedule_odds as $k => $v) {
				if ($k == $play_type) {
					$isInArray = array_keys($v);
				}
			}
		}

		if (strpos($content, ',') === false) {
			if (!in_array($content, $isInArray)) {
				$data['error_code'] = self::ERROR_STATUS;
                $data['msg'] = self::DATA_FAIL;
				$this->ajaxReturn($data);
			}
		} else {
			$content_arr = explode(',', $content);
			foreach ($content_arr as $v) {
				if (!in_array($v, $isInArray)) {
					$data['error_code'] = self::ERROR_STATUS;
                    $data['msg'] = self::DATA_FAIL;
					$this->ajaxReturn($data);
				}
			}
		}
		$data['error_code'] = self::SUCCESS_STATUS;
		$data['msg'] = $content;
		$this->ajaxReturn($data);

	}

    private function _getScheduleInfoById($schedule_id){
        return M('jcSchedule')->where(array('schedule_id' => $schedule_id))->find();
    }


	public function _before_index()
	{
		$this->_assignLotteryMap();
	}

	public function _before_add()
	{
		$this->_assignLotteryMap();
	}

	public function _before_edit()
	{
		$this->_assignLotteryMap();
	}

	public function schduleIndex()
	{
		$this->_assignLotteryMap();
	}


	private function _assignLotteryMap()
	{
		$map = D('Lottery')->getLotteryMap();
		$this->assign('lottery_map', $map);
	}
}