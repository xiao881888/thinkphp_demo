<?php 
namespace Home\Model;
use Think\Model;

class NoticeModel extends Model {

    private $_enable_status = 1;

    public function geiNoticeInfo($app_id){
        $where['notice_status'] = array('IN',array($app_id,0));
        $where['notice_status'] = $this->_enable_status;
        return $this->where($where)->find();
    }

}


?>