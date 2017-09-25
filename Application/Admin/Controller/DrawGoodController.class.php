<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
use Admin\Model\DrawGoodModel;

class DrawGoodController extends GlobalController{

    public function add(){
        $this->_checkWinningPrecent();
        parent::add();
    }

    public function edit(){
        $this->_checkWinningPrecent();
        parent::edit();
    }

    private function _checkWinningPrecent(){
        if(!IS_POST){
            $coupon_list = D('Coupon')->getEnableCouponMap();
            $this->assign('coupon_list',$coupon_list);
        }elseif(IS_POST){
            $id = I('dg_id');
            $total_winning_percent = D('DrawGood')->getTotalWinningPrecent($id);
            if($total_winning_percent + I('dg_winning_percent') > 10000){
                $this->error('赔率大于10000');
            }
        }
    }

    private function _getTotalWinningPrecent($id){
        return D('DrawGood')->getTotalWinningPrecent($id);
    }

}