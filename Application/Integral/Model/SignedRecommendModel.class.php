<?php 
namespace Integral\Model;
use Think\Model;

class SignedRecommendModel extends Model {

    const ENABLE_STATUS = 1;

    public function getSignedRecommendInfo($id){
        $where['sr_id'] = $id;
        return $this->where($where)->find();
    }

    public function getSignedRecommendList(){
        $where['sr_status'] = self::ENABLE_STATUS;
        return $this->where($where)->select();
    }


}



