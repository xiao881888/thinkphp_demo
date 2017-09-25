<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-22
 * @author tww <merry2014@vip.qq.com>
 */
class FollowBetInfoController extends GlobalController{
	public function _initialize(){
		parent::_initialize();
		$this->assign('lottery_map', D('Lottery')->getLotteryMap());
	}

    public function index(){
        $where = array();
        if(I('user_telephone')){
            $where['uid'] = D('User')->getUidByTelephone(I('user_telephone'));
        }

        if (I('fbi_createtime_start')) {
            $where['fbi_createtime'] = array('egt', I('fbi_createtime_start'));
        }
        if (I('fbi_createtime_end')) {
            if (I('fbi_createtime_start')) {
                $where['fbi_createtime'] = array('between', array(I('fbi_createtime_start'), I('fbi_createtime_end')));
            } else {
                $where['fbi_createtime'] = array('lt', I('fbi_createtime_end'));
            }
        }

        if (I('lottery_id_ex') != 0) {
            if (I('lottery_id_ex') == C('JC.JCZQ')) {
                $lottery_id = C('JCZQ');
                $is_jc = true;
                $where['lottery_id']  = array('IN', $lottery_id);
            } elseif (I('lottery_id_ex') == C('JC.JCLQ')) {
                $lottery_id = C('JCLQ');
                $is_jc = true;
                $where['lottery_id']  = array('IN', $lottery_id);
            } else {
                $lottery_id = I('lottery_id_ex');
                $where['lottery_id'] = $lottery_id;
            }
        }

        if (I('follow_total_amount_start') && I('follow_total_amount_end')) {
            $where['follow_total_amount'] = array(
                array('egt', I('follow_total_amount_start', 0)),
                array('lt', I('follow_total_amount_end', 0)),
            );
        }elseif (I('follow_total_amount_start')) {
            $where['follow_total_amount'] = array('egt', I('follow_total_amount_start', 0));
        }elseif (I('follow_total_amount_end')) {
            $where['follow_total_amount'] = array('lt', I('follow_total_amount_end', 0));
        }

        if(I('fbi_type')){
            $where['fbi_type']  = I('fbi_type');
        }

        if(I('fbi_status')){
            $where['fbi_status']  = I('fbi_status');
        }else{
            $where['fbi_status'] = array('GT',0);
        }


        $this->setLimit($where);
        $list = parent::index('', true);
        $this->assign('list', $list);

        $user_ids = extractArrField($list, 'uid');
        $users = D('User')->where(array('uid'=>array('IN', $user_ids)))->select();
        $users = reindexArr($users, 'uid');
        $this->assign('users', $users);
        $this->assign('lottery_map', D('Lottery')->getAllLottery());
        $this->display();
    }

    public function detail(){
        $detail_list[] = array(
        );
        $fbi_id = I('fbi_id');
        $follow_bet_detail_current = D('Home/FollowBetInfoView')->getFollowBetDetailCurrentInfo($fbi_id);
        if(empty($follow_bet_detail_current)){
            $fbd_id = D('Home/FollowBetDetail')->getLastFollowDetailByFbiId($fbi_id);
            $follow_bet_detail_current = D('Home/FollowBetInfoView')->getFollowBetDetailIdsByFbdId($fbd_id);
        }
        $follow_detail_list = D('Home/FollowBetDetail')->getFollowBetDetailListByFbiId($fbi_id);
        foreach ($follow_detail_list as $follow_detail){
            $follow_bet_detail_status_desc = $this->_getFollowBetDetailStatusDesc($follow_bet_detail_current,$follow_detail['order_id'],$follow_detail);
            if(!empty($follow_detail['issue_id'])){
                $issue_no = D('Home/Issue')->getIssueNoById($follow_detail['issue_id']);
            }else{
                $last_issue_id = D('Home/FollowBetInfoView')->getFollowBetDetailLastIssueId($follow_detail['fbi_id']);
                $current_index = $follow_bet_detail_current['fbd_index'];
                $issue_limit = $follow_detail['fbd_index'] - $current_index + 1;
                $issue_no_list = D('Home/Issue')->getIssueNoList($follow_detail['lottery_id'],$last_issue_id,$issue_limit);
                $issue_no = array_pop($issue_no_list);
            }
            $order_winnings_bonus = D('Home/Order')->getOrderWinningAmountById($follow_detail['order_id']);
            $detail_list[] = array(
                'index' => $follow_detail['fbd_index'],
                'amount' => $follow_detail['order_total_amount'],
                'status_desc' => $follow_bet_detail_status_desc['status_desc'],
                'winnings_bonus' => empty($order_winnings_bonus) ? 0 : $order_winnings_bonus,
                'issue_no' => empty($issue_no)?'':$issue_no,
            );
        }
        $this->assign('detail_list',$detail_list);
        $this->display();
    }

    private function _getFollowBetDetailStatusDesc($follow_bet_info_view,$order_id,$follow_detail){
        $data = array();
        switch ($follow_detail['fbd_status']){
            case C('FOLLOW_BET_DETAIL_STATUS.NO_FOLLOW') :
                if($follow_bet_info_view['fbi_status'] == C('FOLLOW_BET_INFO_STATUS.ON_GOING')){
                    $data['status'] = 10;
                    $data['status_desc'] = C('FOLLOW_BET_DETAIL_API_STATUS_DESC.NO_BEGIN');
                }else{
                    $data['status'] = 11;
                    $data['status_desc'] = C('FOLLOW_BET_DETAIL_API_STATUS_DESC.CANCEL');
                }
                break;
            case C('FOLLOW_BET_DETAIL_STATUS.FOLLOWED') :
                $order_info = D('Home/Order')->getOrderInfo($order_id);
                $data['status_desc'] = $this->getOrderStatusDesc($order_info['order_status'],$order_info['order_winnings_status'], $order_info['order_distribute_status']);
                $data['status'] = $this->getStatus($order_info['order_status'], $order_info['order_winnings_status'], $order_info['order_distribute_status']);
                break;
        }
        return $data;
    }

    public function getOrderStatusDesc($order_status, $order_winnings_status, $order_distribute_status,$is_order_list = false){
        $status_desc = '';
        if($order_status == ORDER_STATUS_NOPAY){
            $status_desc = '未支付';
        }else if($order_status == ORDER_STATUS_OUTFAIL){
            $status_desc = '出票失败';
        }else if ($order_status == ORDER_STATUS_PAYNOOUT || $order_status == ORDER_STATUS_OUTING){
            $status_desc = '出票中';
        }else if($order_status == ORDER_STATUS_TUIKUAN){
            $status_desc = '出票失败';
        }else if($order_status == ORDER_STATUS_FAILE){
            $status_desc = '出票失败';
        }else if($order_status == ORDER_STATUS_PRINTOUTING_AND_PART_FAIL){
            $status_desc = '出票中';
        }else if($order_status == ORDER_STATUS_PRINTOUTED_AND_PART_FAIL){
            $status_desc = '部分出票失败';
        }else if($order_winnings_status == ORDER_WINNINGS_STATUS_WAIT){
            $status_desc = '待开奖';//未开奖
        }else if($order_winnings_status == ORDER_WINNINGS_STATUS_NOTWINNING){
            $status_desc = '未中奖';//未中奖
        }else if($order_winnings_status == ORDER_WINNINGS_STATUS_WINNING){
            $status_desc = '已中奖';
        }else if($order_winnings_status == ORDER_WINNINGS_STATUS_PART_WINNING){
            $status_desc = '部分派奖';
        }
        return $status_desc;
    }

    public function getStatus($order_status, $order_winnings_status, $order_distribute_status){
        if($order_status == ORDER_STATUS_NOPAY){
            $status = 1;//未支付
        }else if($order_status == ORDER_STATUS_OUTFAIL){
            $status = 2;//出票失败
        }else if ($order_status == ORDER_STATUS_PAYNOOUT || $order_status == ORDER_STATUS_OUTING){
            $status = 7;//出票中
        }else if($order_status == ORDER_STATUS_TUIKUAN){
            $status = 8;//出票失败且退款
        }else if($order_status == ORDER_STATUS_FAILE){
            $status = 8;//投注失败 =>出票失败且退款
        }else if($order_status == ORDER_STATUS_PRINTOUTING_AND_PART_FAIL){
            $status = 7;//出票中，部分失败退款 =>出票中
        }

        else if($order_winnings_status == ORDER_WINNINGS_STATUS_WAIT){
            $status = 3;//未开奖
        }else if($order_winnings_status == ORDER_WINNINGS_STATUS_NOTWINNING){
            $status = 4;//未中奖
        }else if($order_winnings_status == ORDER_WINNINGS_STATUS_WINNING){
            $status = 5;//已中奖
        }else if($order_winnings_status == ORDER_WINNINGS_STATUS_PART_WINNING){
            $status = 9;
        }
        return $status ? $status : -1;
    }

    public function test(){
        $id = 7857;
        getFollowStatusDesc($id);
    }


}