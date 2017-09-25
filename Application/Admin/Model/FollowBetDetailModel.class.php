<?php 
namespace Admin\Model;
use Think\Model;

class FollowBetDetailModel extends Model {

    public function getFollowDetailByFbiId($fbi_id){
        $condition['fbi_id'] = $fbi_id;
        return $this->where($condition)->order('fbd_id DESC')->select();
    }

}

?>