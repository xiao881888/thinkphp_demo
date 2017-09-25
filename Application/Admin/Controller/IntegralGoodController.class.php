<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
use Admin\Model\DrawGoodModel;

class IntegralGoodController extends GlobalController{

    public function add(){
        $this->_assignCouponList();
        parent::add();
    }

    public function edit(){
        $this->_assignCouponList();
        parent::edit();
    }

    private function _assignCouponList(){
        if(!IS_POST){
            $coupon_list = D('Coupon')->getEnableCouponMap();
            $this->assign('coupon_list',$coupon_list);
        }
    }

}