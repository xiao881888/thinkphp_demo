<?php 
namespace Home\Model;
use Think\Model;

class FollowBetInfoViewModel extends Model\ViewModel {
    protected $viewFields = array(
        'FollowBetInfo'=>array(
            'fbi_id',
            'uid',
            'lottery_id',
            'follow_times',
            'follow_total_amount',
            'followed_amount',
            'issue_id' => 'first_issue_id',
            'fbi_type' 			=> 'type',
            'fbi_allow_cancel' 	=> 'allow_cancel',
            'order_id' => 'first_order_id',
            'extra_id',
            'fbi_win_stop_amount',
            'fbi_is_independent',
            'fbi_status',
            'fbi_createtime',
        ),
        'FollowBetDetail'=>array(
            'fbd_id',
            'order_id',
            'issue_id',
            'order_multiple',
            'order_total_amount',
            'fbd_is_current' => 'is_current',
            'fbd_index',
            'fbd_status',
            'fbd_bet_number_list',
            '_on' => 'FollowBetInfo.fbi_id = FollowBetDetail.fbi_id'
        ),
    );

    public function getFollowBetDetailIdsByFbdId($fbd_id){
        $where['fbd_id'] = $fbd_id;
        return $this->where($where)->find();
    }
    
    public function getFollowBetDetailIds($lottery_id,$type=1){
        $where['fbi_status'] = C('FOLLOW_BET_INFO_STATUS.ON_GOING');
        $where['lottery_id'] = $lottery_id;
        $where['fbd_status'] = array('IN',array(C('FOLLOW_BET_DETAIL_STATUS.NO_FOLLOW'),C('FOLLOW_BET_DETAIL_STATUS.FOLLOWED_NO_PRINTOUT')));
        $where['is_current'] = C('FOLLOW_BET_DETAIL_STATUS.IS_CURRENT');
        if($type == 1){
            $where['fbi_type'] = C('FOLLOW_BET_INFO_TYPE.FOLLOW_ISSUE');
        }elseif($type == 2){
            $where['fbi_type'] = array('IN',array(C('FOLLOW_BET_INFO_TYPE.PRIZE_STOP'),C('FOLLOW_BET_INFO_TYPE.WIN_STOP_AMOUNT')));
        }
        return $this->where($where)->getField('fbd_id',true);
    }

    public function getFollowBetDetailByOrderId($order_id,$type = ''){
        $condition['order_id'] = $order_id;
        if($type){
            $condition['fbi_type'] = $type;
        }
        return $this->where($condition)->find();
    }

    public function getFollowBetDetailLastOrderId($fbi_id){
        $where['fbi_id'] = $fbi_id;
        $where['fbd_is_current'] = C('FOLLOW_BET_DETAIL_STATUS.IS_CURRENT');
        $current_index = $this->where($where)->getField('fbd_index');
        $where2['fbi_id'] = $fbi_id;
        $where2['fbd_index'] = $current_index-1;
        return $this->where($where2)->getField('order_id');
    }

    public function getFollowBetDetailLastIssueId($fbi_id){
        $where['fbi_id'] = $fbi_id;
        $where['fbd_is_current'] = C('FOLLOW_BET_DETAIL_STATUS.IS_CURRENT');
        $current_index = $this->where($where)->getField('fbd_index');
        $where2['fbi_id'] = $fbi_id;
        $where2['fbd_index'] = $current_index-1;
        return $this->where($where2)->getField('issue_id');
    }

    public function getFollowBetDetailCurrentInfo($fbi_id){
        $where['fbi_id'] = $fbi_id;
        $where['fbd_is_current'] = C('FOLLOW_BET_DETAIL_STATUS.IS_CURRENT');
        return $this->where($where)->find();
    }

    public function getFollowBetDetailPreInfo($fbi_id){
        $where['fbi_id'] = $fbi_id;
        $where['fbd_is_current'] = C('FOLLOW_BET_DETAIL_STATUS.IS_CURRENT');
        $current_index = $this->where($where)->getField('fbd_index');
        $where2['fbi_id'] = $fbi_id;
        $where2['fbd_index'] = $current_index-1;
        return $this->where($where2)->find();
    }

    public function getFinishTimes($fbi_id){
        $where['fbi_id'] = $fbi_id;
        $where['fbd_is_current'] = C('FOLLOW_BET_DETAIL_STATUS.IS_CURRENT');
        $current_index = $this->where($where)->getField('fbd_index');
        return $current_index-1;
    }

    public function getOrderIdsByUid($uid,$type = 0){
        $where['order_id'] = array('NEQ',0);
        $where['uid'] = $uid;
        if($type == 2){
            $where['fbi_status'] = C('FOLLOW_BET_INFO_STATUS.ON_GOING');
        }elseif($type == 3){
            $where['fbi_status'] = array('GT',C('FOLLOW_BET_INFO_STATUS.ON_GOING'));
        }
        $this->where($where)->getField('order_id',true);
    }


}

?>