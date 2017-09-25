<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;

class WinmessageController extends GlobalController{
	
	public function _before_index(){
		$this->_assignLotteryMap();
	}
	
	public function _before_add(){
		$this->_assignLotteryMap();
	}
	
	public function _before_edit(){
		$this->_assignLotteryMap();
	}

    public function schduleIndex(){
        $this->_assignLotteryMap();
    }



    private function _assignLotteryMap(){
        $map = D('Lottery')->getLotteryMap();
        $this->assign('lottery_map', $map);
    }
}