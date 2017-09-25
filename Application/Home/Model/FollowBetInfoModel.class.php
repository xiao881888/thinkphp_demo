<?php 
namespace Home\Model;
use Think\Model;

class FollowBetInfoModel extends Model {

	public function getFollowInfoById($id){
		$condition['fbi_id'] = $id;
		return $this->where($condition)->find();
	}

    public function getFollowInfoByOrderId($order_id){
        $condition['order_id'] = $order_id;
        return $this->where($condition)->find();
    }

    public function addFollowAmount($id,$followed_amount,$fbi_status = 0){
        $where['fbi_id'] = $id;
        return $this->where($where)->setInc('followed_amount',$followed_amount);
    }

    public function changeFollowBetInfoStatus($id,$fbi_status){
        $where['fbi_id'] = $id;
        $save_data['fbi_status'] = $fbi_status;
        return $this->where($where)->save($save_data);
    }

    public function getFollowInfoListOfOngoing($lottery_id){
        $condition['fbi_status'] = C('FOLLOW_BET_INFO_STATUS.ON_GOING');
        $condition['lottery_id'] = $lottery_id;
        return $this->where($condition)->getField('fbi_id',true);
    }

    public function getFollowInfoListOfPrizeStop($lottery_id){
        $condition['fbi_status'] = C('FOLLOW_BET_INFO_STATUS.ON_GOING');
        $condition['lottery_id'] = $lottery_id;
        $condition['fbi_type'] = array('IN',array(C('FOLLOW_BET_INFO_TYPE.PRIZE_STOP'),C('FOLLOW_BET_INFO_TYPE.WIN_STOP_AMOUNT')));
        return $this->where($condition)->getField('fbi_id',true);
    }

    public function getFollowInfoListByType($uid,$type,$offset=0,$limit=10){
        $where['uid'] = $uid;
        if($type == 2){
            $where['fbi_status'] = C('FOLLOW_BET_INFO_STATUS.ON_GOING');
        }elseif($type == 3){
            $where['fbi_status'] = array('GT',C('FOLLOW_BET_INFO_STATUS.ON_GOING'));
        }
        return $this->where($where)->limit($offset, $limit)->order('fbi_createtime DESC')->select();
    }
    
}

?>