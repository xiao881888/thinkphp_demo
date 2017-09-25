<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-3
 * @author tww <merry2014@vip.qq.com>
 */
class CheckInformationController extends InformationController{


    public function index(){
        $_REQUEST['information_check_status'] = $this->not_check;
        $this->assignInformationCategory();
        parent::index(D('Information'));
    }

    public function add(){
        $this->assignInformationCategory();
        $this->assignRecommentLotteryList();
        parent::add(D('Information'));
    }

    public function edit(){
        $this->assignInformationCategory();
        $this->assignRecommentLotteryList();
        parent::edit(D('Information'));
    }

    public function changeStatus(){
        parent::changeStatus(D('Information'));
    }



    public function del(){
        $id = I('id',0);
        $result = M('Information')->where(array('information_id'=>$id))->delete();
        if (false !== $result) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    public function release(){
        $id = I('id',0);
        $save_data['information_check_status'] = $this->is_check;
        $save_data['information_release_time'] = getCurrentTime();
        $result = M('Information')->where(array('information_id'=>$id))->save($save_data);
        if (false !== $result) {
            $this->success('发布成功！');
        } else {
            $this->error('发布失败！');
        }
    }

}