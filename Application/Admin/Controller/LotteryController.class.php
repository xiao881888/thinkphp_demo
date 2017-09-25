<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-2
 * @author tww <merry2014@vip.qq.com>
 */
class LotteryController extends GlobalController{
	public function config(){
		$this->assign('lottery_list', D('Lottery')->getLotteryList());
		$this->display();
	}

	public function add(){
	    if(IS_POST){
            $_POST['support_play_types'] = $this->_getSupportPlayTypes();
        }else{
            $this->_assignSupportPlayTypes();
        }
        parent::add();
    }

    public function edit(){
        $id 	= I('id', 0);
        if(IS_POST){
            $_POST['support_play_types'] = $this->_getSupportPlayTypes();
        }else{
            $this->_assignSupportPlayTypes($id);
        }

        parent::edit();
    }


    public function _assignSupportPlayTypes($id = 0){
	    $support_play_type_list = array();
	    if(!empty($id)){
            $support_play_types = D('Lottery')->getLotterySupportPlayTypes($id);
            $support_play_types = explode(',',$support_play_types);
        }
        $play_type_desc = C('PLAY_TYPE_DESC');
        $play_types = array_keys($play_type_desc);
        foreach($play_types as $play_type){
            if(in_array($play_type,$support_play_types)){
                $support_play_type_list[$play_type]['is_support'] = 1;
            }else{
                $support_play_type_list[$play_type]['is_support'] = 0;
            }
            $support_play_type_list[$play_type]['play_type_desc'] = $play_type_desc[$play_type];
        }
        $this->assign('support_play_type_list',$support_play_type_list);
    }

    public function _getSupportPlayTypes(){
        $support_play_types = I('support_play_types');
        return empty($support_play_types) ? '' : implode(',',$support_play_types);
    }

}