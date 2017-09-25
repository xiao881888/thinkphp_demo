<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
class OpenPageController extends GlobalController{
    
    public function _initialize(){
    	$open_page_type = array();
    	$open_page_type['0'] = '打开网页';
    	$open_page_type['1'] = '购买红包';
    	$open_page_type['2'] = '下注页';
    	$open_page_type['3'] = '充值页';
    	$open_page_type['4'] = '不跳转';

    	$this->assign('open_page_type', $open_page_type);
    	$this->assign('lottery_map', D('Lottery')->getAllLottery());
    	

    	parent::_initialize();
    }
}