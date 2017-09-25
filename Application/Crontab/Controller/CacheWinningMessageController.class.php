<?php
namespace Crontab\Controller;

use Crontab\Util\Factory;
use Think\Controller;

class CacheWinningMessageController extends Controller
{

    private $redis;

    public function __construct(){
        $this->_initRedis();
        parent::__construct();
    }

    private function _initRedis(){
        $this->redis = Factory::createAliRedisObj();
        $this->redis->select(0);
    }


    private $_lotteryList = array(
        'ElevenChooseFive' => array( 4, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18 ),
        'Jz'               => array( 601, 602, 603, 604, 605, 606 ),
        'Jl'               => array( 701, 702, 703, 704, 705 ),
        'Ssq'              => array( 1 ),
        'Dlt'              => array( 3 ),
        'K3'               => array( 5, 19 )
    );

    private $_minWinPrice = array(
        'ElevenChooseFive' => 1000,
        'Jz'               => 20000,
        'Jl'               => 5000,
        'Ssq'              => 5000,
        'Dlt'              => 5000,
        'K3'               => 5000
    );

    const WIN_STATUS = 1;
    const THE_PART_OF_WIN_STATUS = 2;

    const WIN_MESSAGE_TEMPLATE = "恭喜%s%s喜中%s元";

    private $lottery_type_arr = array(
        'Jz'               => 6,
        'Jl'               => 7,
        'Ssq'              => 1,
        'Dlt'              => 3,
        'ElevenChooseFive' => 4,
    );

    public function cacheWinningMessage()
    {
        $lottery_type_list = $this->_getLotteryTypeList();
        foreach ($lottery_type_list as $lottery_type) {
            $data[] = $this->_getWinningOrderList($lottery_type);
        }
        $win_message_list = $this->_formateWinMessage($data);
        $this->_setCacheOfWinMessageList($win_message_list);
    }

    private function _getLotteryTypeList()
    {
        $lottery_type_list = $this->lottery_type_arr;
        return array_keys($lottery_type_list);
    }


    private function _setCacheOfWinMessageList($win_message_list)
    {
        $this->redis->set('tiger_api:home_page:tiger_api_win_message_list', json_encode($win_message_list));
    }

    private function _formateWinMessage($win_order_type_list)
    {
        $win_message = array();
        $id = 1;
        foreach ($win_order_type_list as $win_order_list) {
            foreach ($win_order_list as $info) {
                $user_telephone = substr_replace($info['user_telephone'], "****", 3, 4);
                $lottery_name = $this->_getLotteryShowNameByLotteryId($info['lottery_id']);
                $win_monery = $info['order_winnings_bonus'];
                $win_message[] = array(
                    'id'         => $id++,
                    'lottery_id' => $info['lottery_id'],
                    'content'    => sprintf(self::WIN_MESSAGE_TEMPLATE, $user_telephone, $lottery_name, $win_monery),
                );
            }
        }
        return $win_message;
    }

    private function _getWinningOrderList($type)
    {
        $lottery_id_list = $this->_lotteryList[$type];
        $start_time = date('Y-m-d H:i:s', time() - 7 * 24 * 60 * 60);
        $end_time = getCurrentTime();
        $where = array();
        $where['order_create_time'] = array( array( 'egt', $start_time ), array( 'elt', $end_time ) );
        $where['lottery_id'] = array( 'in', $lottery_id_list );
        $where['order_winnings_status'] = array( self::THE_PART_OF_WIN_STATUS, self::WIN_STATUS, 'or' );
        $where['order_winnings_bonus'] = array( 'egt', $this->_minWinPrice[$type] );
        if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
            $winningOrderList = M('order')->db(1,C('READ_DB'),true)->alias('o')->join('cp_user u ON u.uid = o.uid')
                ->field('lottery_id,user_telephone,order_winnings_bonus')
                ->where($where)->select();
            M('order')->db(0);
        } else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
            $winningOrderList = M('order')->alias('o')->join('cp_user u ON u.uid = o.uid')
                ->field('lottery_id,user_telephone,order_winnings_bonus')
                ->where($where)->select();
        } else {
            $winningOrderList = M('order')->alias('o')->join('cp_user u ON u.uid = o.uid')
                ->field('lottery_id,user_telephone,order_winnings_bonus')
                ->where($where)->select();
        }
        return $winningOrderList;
    }

    private function _getLotteryShowNameByLotteryId($lottery_id)
    {
        switch ($lottery_id) {
            case in_array($lottery_id, $this->_lotteryList['Jz']):
                return '竞彩足球';
            case in_array($lottery_id, $this->_lotteryList['Jl']):
                return '竞彩篮球';
            default:
                $lottery_name = $this->_getLotteryNameById($lottery_id);
                return $lottery_name;
        }
    }

    private function _getLotteryNameById($lottery_id)
    {
        return M('Lottery')->where(array( 'lottery_id' => $lottery_id ))->getField('lottery_name');
    }


}