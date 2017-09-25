<?php
namespace Admin\Controller;

class SignedRecommendController extends GlobalController{

    public function add(){
        $this->_assignLotteryList();
        parent::add();
    }

    public function edit(){
        $this->_assignLotteryList();
        parent::edit();
    }

    private function _assignLotteryList(){
        $lottery_list = D('Lottery')->getLotteryList();
        $this->assign('lottery_list',$lottery_list);
    }



}