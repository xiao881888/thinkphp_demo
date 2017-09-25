<?php 
namespace H5\Model;
use Think\Model;

class FollowBetDetailModel extends Model {

	public function getFollowDetailById($id){
		$condition['fbd_id'] = $id;
		return $this->where($condition)->find();
	}

    public function getFollowDetailByFbiId($fbi_id,$index = 1){
        $condition['fbi_id'] = $fbi_id;
        $condition['fbd_index'] = $index;
        return $this->where($condition)->find();
    }

	public function updateCurrentData($id,$issue_id,$order_id){
        $where['fbd_id'] = $id;
        $save_data['issue_id'] = $issue_id;
        $save_data['order_id'] = $order_id;
        $save_data['fbd_modifytime'] = getCurrentTime();
        $save_data['fbd_is_current'] = C('FOLLOW_BET_DETAIL_STATUS.NO_CURRENT');
        $save_data['fbd_status'] = C('FOLLOW_BET_DETAIL_STATUS.FOLLOWED_NO_PRINTOUT');
        return $this->where($where)->save($save_data);
    }

    public function updateNextData($fbi_id,$index){
	    $where['fbi_id'] = $fbi_id;
        $where['fbd_index'] = ($index+1);
        return $this->where($where)->save(array('fbd_is_current' => C('FOLLOW_BET_DETAIL_STATUS.IS_CURRENT')));
    }

    public function isLastIssue($fbi_id,$index){
        $where['fbi_id'] = $fbi_id;
        $where['fbd_index'] = array('gt',$index);
        $info = $this->where($where)->find();
        return empty($info) ? true : false;
    }

    public function changeFollowBetDetailStatus($id,$fbd_status){
        $where['fbd_id'] = $id;
        $save_data['fbd_status'] = $fbd_status;
        return $this->where($where)->save($save_data);
    }

    public function getFollowBetOrderListByFbiId($fbi_id){
        $where['fbi_id'] = $fbi_id;
        $where['fbd_status'] = C('FOLLOW_BET_DETAIL_STATUS.FOLLOWED');
        return $this->where($where)->getField('order_id',true);
    }


}

?>