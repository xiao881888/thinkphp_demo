<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-3
 * @author tww <merry2014@vip.qq.com>
 */
class InformationCategoryController extends GlobalController{



    public function _before_add(){
        $this->_assignRecommentLottery();
    }

    public function _before_edit(){
        $this->_assignRecommentLottery();
    }

    private function _assignRecommentLottery(){
        $lottery_list = D('InformationCategory')->getRecommentLotteryList();

        $this->assign('lottery_list', $lottery_list);
    }
	
}