<?php
namespace Content\Model;
use Think\Model;
/**
 * @date 2014-12-10
 * @author tww <merry2014@vip.qq.com>
 */
class InformationSupportModel extends Model{

    public function isSupportByUid($informationId,$uid){
        $where['information_support_uid'] = $uid;
        $where['information_support_information_id'] = $informationId;
        return $this->where($where)->find();
    }

    public function getInformationListByUid($uid ,$offset = 0, $limit = 10){
        $where['information_support_uid'] = $uid;
        $where['information_status'] = 1;
        $where['information_check_status'] = 1;
        return $this->alias('ins')->join('cp_information i ON i.information_id = ins.information_support_information_id')->where($where)->select();
    }
	
}