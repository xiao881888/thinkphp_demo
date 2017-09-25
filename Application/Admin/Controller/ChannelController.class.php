<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date
 * @author
 */
class ChannelController extends GlobalController{
	
	public function _initialize(){
		parent::_initialize();
		$salers = M('Saler')->select();
		$salers = reindexArr($salers, 'saler_id');
		$this->assign('salers', $salers);
	}	

}