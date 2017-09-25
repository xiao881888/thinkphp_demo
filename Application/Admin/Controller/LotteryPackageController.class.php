<?php
namespace Admin\Controller;
/**
 * @date 2014-12-3
 * @author tww <merry2014@vip.qq.com>
 */
class LotteryPackageController extends GlobalController{

    public function edit(){
        if(IS_POST){
            $_POST['lp_cost_price'] = $this->_getCostPrice($_POST['lp_stake_count'],$_POST['lp_issue_num']);
        }
        parent::edit();
    }

    public function add(){
        if(IS_POST){
            $_POST['lp_cost_price'] = $this->_getCostPrice($_POST['lp_stake_count'],$_POST['lp_issue_num']);
        }

        parent::add();
    }

    private function _getCostPrice($stake_count = 1,$issue_num = 1,$multiple = 1){
        return $stake_count*$issue_num*$multiple*2;
    }

}