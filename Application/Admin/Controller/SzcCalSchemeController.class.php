<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2015-3-3
 * @author tww <merry2014@vip.qq.com>
 */
class SzcCalSchemeController extends GlobalController{
	
	public function config(){
		if(IS_POST){
			$lottery_id = I('lottery_id');
			$scheme_id = I('scheme_id');
			if($lottery_id && $scheme_id){
				$result = D('SzcCalScheme')->changeCurrStatus($lottery_id, $scheme_id);
				if(false !== $result){
					$this->success('设置成功！',U('index'));
				}
			}else{
				$this->error('彩种和方案不能为空！');
			}
		}else{
			$this->_assignLotteryMap();
			$this->display();
		}
	}
	
	public function schemes($lottery_id){
		$schemes = D('SzcCalScheme')->getSchemes($lottery_id);
		$this->assign('schemes', $schemes);
		$this->display();
	}
	
	public function _before_index(){
		$this->_assignLotteryMap();
	}
	
	public function _before_add(){
		$this->_assignLotteryMap();
	}
	
	public function _before_edit(){
		$this->_assignLotteryMap();
	}

	private function _assignLotteryMap(){
		$map = D('Lottery')->getAllLottery();
		$this->assign('lottery_map', $map);
	}
	
}