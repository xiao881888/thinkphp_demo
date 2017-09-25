<?php
namespace Admin\Controller;


/**
 * @date 2014-12-3
 * @author tww <merry2014@vip.qq.com>
 */
class IssueLimitNoController extends GlobalController
{

    public function _before_index()
    {
        $this->assign('lottery_map', D('Lottery')->getAllLottery());
    }

    public function _before_add()
    {
        $this->_assignLotteryList();
    }

    public function _before_edit()
    {
        $this->_assignLotteryList();
    }

    private function _assignLotteryList()
    {
        $lottery_list = M('Lottery')->field('lottery_id,lottery_name')->select();
        $this->assign('lottery_list', $lottery_list);
    }


}