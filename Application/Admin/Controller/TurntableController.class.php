<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
use Admin\Model\DrawGoodModel;

class TurntableController extends GlobalController{

    public function log()
    {
        $map = [];
        $turntable_id = I('turntable_id',false);
        $start_time = I('start_time',false);
        $end_time = I('end_time',false);
        $uid = I('uid',false);
        if ($turntable_id){
            $map['turntable_id'] = $turntable_id;
        }

        if ($start_time) {
            $map['log_addtime'] = ['EGT',$start_time];
        }

        if ($end_time) {
            $map['log_addtime'] = ['ELT',$end_time];
        }

        if ($uid) {
            $map['uid'] = $uid;
        }

        $model = D('TurntableLog');
        $list = $this->lists($model, $map, 'log_addtime desc', '');
        foreach ($list as $key => $item){
            $user_info = D('User')->getUserInfo($item['uid']);
            $list[$key]['mobile'] = $user_info['user_telephone'];
            $list[$key]['user_name'] = $user_info['user_real_name'];
        }
        $this->assign('list', $list);
        $this->display();
    }

}