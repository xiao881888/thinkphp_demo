<?php
namespace Crontab\Controller;
use Crontab\Util\Factory;
use Think\Controller;

class FollowBetMonitorController extends Controller {
    private $_redis;
    public function __construct(){
        $this->_redis = Factory::createAliRedisObj();
        $this->_redis->select(0);
    }

    public function warning(){
        $warning_follow_bet_list = $this->_redis->hGetAll('FollowMonitor');
        foreach($warning_follow_bet_list as $warning_follow_bet => $warning_time){
            if(getCurrentTime() > $warning_time){
                $warning_follow_bet_arr = explode('-',$warning_follow_bet);
                $issue_no = D('Home/Issue')->getIssueNoById($warning_follow_bet_arr[1]);
                $lottery_info = D('Home/Lottery')->getLotteryInfo($warning_follow_bet_arr[0]);
                $lottery_name = $lottery_info['lottery_name'];
                $follow_bet_info_list = $this->_getFollowBetInfoList($warning_follow_bet_arr[0]);
                $this->_notifyWarningMsg('lottery_name:'.$lottery_name.',issue_no:'.$issue_no.',fbi_ids:'.json_encode($follow_bet_info_list).'追号中奖停追未通知,请及时处理');
                $this->_redis->hDel('FollowMonitor',$warning_follow_bet);
            }
        }
    }

    private function _getFollowBetInfoList($lottery_id){
        $where['fbi_type'] = 1;
        $where['lottery_id'] = $lottery_id;
        $where['fbi_status'] = 1;
        return M('FollowBetInfo')->where($where)->getField('fbi_id',true);
    }

    private function _notifyWarningMsg($msg=''){
        $data = array(
            'telephone_list' => array('18705085505','18506930687','18610293812','18650309179','13459461935'),
            'send_data' => array(getCurrentTime().':'.get_cfg_var('PROJECT_RUN_MODE').$msg),
            'template_id' => '82542',
        );
        sendTelephoneMsgNew($data);
    }



}
