<?php 
namespace Home\Model;
use Think\Model;

class CobetOrderModel extends Model {
	const ERR				= -1;
	const NO_PAY 			= 1;
	const PAY_FAIL 			= 2;
	const NO_PRIZE 			= 3;
	const NO_WINNER 		= 4;
	const WINNER 			= 5;
	const DISTRIBUTE_ING 	= 6;
	const PRINTOUTING       = 7;
	const PRINTOUT_REFUND	= 8;
	const WIN_OF_PART_ORDER	= 9;
	
	const API_ORDERS_STATUS_WAITING 	= -1;
	const API_ORDERS_STATUS_WINNING 	= 1;
	const API_ORDERS_STATUS_NO_WINNING 	= -2;
	 
    
    public function addOrder($uid, $totalAmount, $issueId, $multiple, $userCouponId, $lotteryId, $orderSku, $first_issue_id=0, $identity='', $currentFollowTimes=0, $followBetId=0,$status='',$request_params=array(),$suite_id = 0) {
		if($status===''){
			$status = ORDER_STATUS_OF_UNPAID;
		}
		$current_time = getCurrentTime();
    	$dataOrder = array(
            'order_sku' => $orderSku,
            'uid' => $uid,
            'order_create_time' => $current_time,
            'order_modify_time' => $current_time,
            'order_total_amount'=> $totalAmount,
            'order_multiple' => $multiple,
            'issue_id' => $issueId,
            'first_issue_id' => $first_issue_id,
    		'order_status' => $status,
            'user_coupon_id' => $userCouponId,
            'lottery_id' => $lotteryId,
        	'current_follow_times' => $currentFollowTimes,
        	'order_identity' => $identity,
            'follow_bet_id' => $followBetId,
            'lp_id' => empty($suite_id) ? 0 : $suite_id,
    	);
    	if(!empty($request_params['play_type'])){
    		$dataOrder['play_type'] = $request_params['play_type'];
    	}
    	if(!empty($request_params['series'])){
    		$dataOrder['bet_type'] = $request_params['series'];
    	}
    	if(!empty($request_params['order_type'])){
    		$dataOrder['order_type'] = $request_params['order_type'];
    	}
    	if(!empty($request_params['content'])){
    		$dataOrder['order_content'] = $request_params['content'];
    	}
        return $this->add($dataOrder);
    }
    

    public function getStatus($order_status, $order_winnings_status, $order_distribute_status){
    	if($order_status == C('ORDER_STATUS.UNPAID')){
    		$status = self::NO_PAY;//未支付
    	}else if($order_status == C('ORDER_STATUS.PRINTOUT_ERROR')){
    		$status = self::PAY_FAIL;//出票失败
    	}else if ($order_status == C('ORDER_STATUS.PAYMENT_SUCCESS') || $order_status == C('ORDER_STATUS.PRINTOUTING')){
    	    $status = self::PRINTOUTING;//出票中
    	}else if($order_status == C('ORDER_STATUS.PRINTOUT_ERROR_REFUND')){
    		$status = self::PRINTOUT_REFUND;//出票失败且退款
    	}else if($order_status == C('ORDER_STATUS.BET_ERROR')){
    		$status = self::PRINTOUT_REFUND;//投注失败 =>出票失败且退款
    	}else if($order_status == C('ORDER_STATUS.PRINTOUTING_PART_REFUND')){
    		$status = self::PRINTOUTING;//出票中，部分失败退款 =>出票中
//     	}else if($order_status == C('ORDER_STATUS.PRINTOUTED_PART_REFUND')){
//     		$status = self::PRINTOUT_REFUND;//投注失败 =>出票失败且退款
    	}
    	
    	else if($order_winnings_status == C('ORDER_WINNINGS_STATUS.WAITING')){
    		$status = self::NO_PRIZE;//未开奖
    	}else if($order_winnings_status == C('ORDER_WINNINGS_STATUS.NO')){
    		$status = self::NO_WINNER;//未中奖
    	}else if($order_winnings_status == C('ORDER_WINNINGS_STATUS.YES')){
    		$status = self::WINNER;//已中奖
    	}else if($order_winnings_status == C('ORDER_WINNINGS_STATUS.PART')){
    		$status = self::WIN_OF_PART_ORDER;
    	}
    	
    	if($order_distribute_status == C('ORDER_DISTRIBUTE_STATUS.YES')){
    		$status = self::DISTRIBUTE_ING;//派奖中
    	}
    	return $status ? $status : self::ERR;
    }
    
    public function getOrderId($sku){
    	$where = array();
    	$where['sku'] = $sku;
    	return $this->where($where)->getField('order_id');
    }
    
    public function decreaseCouponAmount($orderId, $couponAmount) {
        $condition = array('order_id'  => $orderId,
        					'order_coupon_consumption'=>array('egt',$couponAmount));
        return $this->where($condition)
                    ->setDec('order_coupon_consumption',$couponAmount);
    }
    
    public function savePaidOrder($orderId, $status, $couponAmount, $order_coupon_amount=0,$order_coupon_id = 0) {
    	$condition = array('order_id'  => $orderId);
    	$data = array(  'order_status' => $status,
    			'order_coupon_consumption'=>$couponAmount,
    			'user_coupon_amount' => $order_coupon_amount, );
    	return $this->where($condition)
    			->save($data);
    }
    
    
    public function getTodayOrderAmount($uid) {
        $startTime = date('Y-m-d 00:00:00');
        $endTime = date('Y-m-d 23:59:59');
        $condition = array( 'uid'=>$uid,
                            'order_create_time' => array('between', array($startTime, $endTime))
        );
        
        return $this->where($condition)
                    ->sum('order_total_amount');
    }
    
    
    public function getFollowBetId($orderId) {
        $condition = array('order_id'=>$orderId);
        return $this->where($condition)
                    ->getField('follow_bet_id');
    }
    
    
    public function saveFollowBetId($orderId, $followBetId) {
        $condition = array('order_id'=>$orderId);
        $data = array('follow_bet_id'=>$followBetId);
        return $this->where($condition)
                    ->save($data);
    }
    
    
    public function getOrderInfo($orderId) {
        $condition = array('order_id'=>$orderId);
        return $this->where($condition)
                    ->find();
    }
    

    public function deleteUnpaidOrder($orderId) {
        $condition = array('order_id'=>$orderId, 'order_status'=>C('ORDER_STATUS.UNPAID'));
        $data = array('order_status' => C('ORDER_STATUS.DELETE'));
        return $this->where($condition)
                    ->save($data);
    }

	public function getOrderIdByIdentity($identify){
		if (empty($identify)) {
			return false;
		}
		$map['order_identity'] =  $identify ;
		return $this->where($map)->find();
	}
    
    public function getOrderInfos($uid, $lottery_id, $order_type, $offset=0, $limit=10,$category = 0){
    	$where = array( 'uid'			=>$uid,
    					'order_status'	=>array('egt',0) );
    	if($lottery_id){
    		$where['lottery_id'] = $lottery_id;
    	}
    	if($order_type == self::API_ORDERS_STATUS_WAITING){//未开奖
    		$where['order_winnings_status'] = C('ORDER_WINNINGS_STATUS.WAITING');
    		$where['order_status'] = C('ORDER_STATUS.PRINTOUTED');
    	}else if($order_type == self::API_ORDERS_STATUS_WINNING){ // 中奖
			$where['order_winnings_status'] = array(
					'IN',
					array(
							C('ORDER_WINNINGS_STATUS.YES'),
							C('ORDER_WINNINGS_STATUS.PART') 
					) 
			);
    		$where['order_status'] = C('ORDER_STATUS.PRINTOUTED');
    	}else if($order_type == self::API_ORDERS_STATUS_NO_WINNING){//未中奖
    		$where['order_winnings_status'] = C('ORDER_WINNINGS_STATUS.NO');
    		$where['order_status'] = C('ORDER_STATUS.PRINTOUTED');
    	}
        $where['order_type'] = $category;
    	
    	return $this->where($where)
			    	->order('order_create_time DESC')
			    	->limit($offset, $limit)
			    	->select();
    }

    public function getBigOrders($lottery_id, $order_type, $offset=0, $limit=0,$category=0){
        $where = array(
            'order_status'  => array('egt',0),
            'order_total_amount' => array('egt', C('TEST_USER_ORDER_AMOUNT_LIMIT'))
            );
        if($lottery_id){
            $where['lottery_id'] = $lottery_id;
        }
        if($order_type == self::API_ORDERS_STATUS_WAITING){//未开奖
            $where['order_winnings_status'] = C('ORDER_WINNINGS_STATUS.WAITING');
            $where['order_status'] = C('ORDER_STATUS.PRINTOUTED');
        }else if($order_type == self::API_ORDERS_STATUS_WINNING){ // 中奖
            $where['order_winnings_status'] = array(
                    'IN',
                    array(
                            C('ORDER_WINNINGS_STATUS.YES'),
                            C('ORDER_WINNINGS_STATUS.PART') 
                    ) 
            );
            $where['order_status'] = C('ORDER_STATUS.PRINTOUTED');
        }else if($order_type == self::API_ORDERS_STATUS_NO_WINNING){//未中奖
            $where['order_winnings_status'] = C('ORDER_WINNINGS_STATUS.NO');
            $where['order_status'] = C('ORDER_STATUS.PRINTOUTED');
        }
        $where['order_type'] = $category;
        
        return $this->where($where)
                    ->order('order_create_time DESC')
                    ->limit($offset, $limit)
                    ->select();
    }

	public function queryOrderIdByFollowIdAndIssueId($follow_id, $issue_id){
		$condition = array(
				'issue_id' => $issue_id,
				'follow_bet_id' => $follow_id,
		);
		return $this->where($condition)->getField('order_id');
    }
    
    
    public function getOrderWinningsInfo($followId) {
    	$condition = array('follow_bet_id' => $followId);
    	return $this->where($condition)
    				->getField('order_id, order_id, order_winnings_bonus, order_winnings_status');
    				
    }
    
    
    public function saveOrderStatus($orderId, $status) {
    	$condition = array('order_id'=>$orderId);
    	$data = array('order_status'=>$status);
    	return $this->where($condition)
    				->save($data);
    }
    
    
    public function getLotteryId($order_id){
    	$where = array();
    	$where['order_id'] = $order_id;
    	return $this->where($where)->getField('lottery_id');
    }
    
    public function getIssueId($order_id){
    	$where = array();
    	$where['order_id'] = $order_id;
    	return $this->where($where)->getField('issue_id');
    }
    
    public function getUserWinningAmount($uid){
        $map = array(
            'uid' => $uid,
            'order_winnings_status' => array('IN', array(1, 2)),
            );

        return $this->where($map)->sum('order_winnings_bonus');
    }

	public function queryMyOrderList($uid, $lottery_ids, $offset = 0, $limit = 10){
		$map['uid'] = $uid;
		$map['lottery_id'] = array(
				'IN',
				$lottery_ids 
		);
		$map['order_status'] = array(
				'IN',
				array(
						C('ORDER_STATUS.PRINTOUTED'),
						C('ORDER_STATUS.PRINTOUTED_PART_REFUND') 
				) 
		);
		$order_by = 'order_create_time DESC';
		return $this->where($map)->order($order_by)->limit($offset, $limit)->select();
	}

	public function getTotalReduceAmountByIds($order_ids){
        $where['order_id'] = array('IN',$order_ids);
        return $this->where($where)->sum('order_reduce_amount');
    }

    public function getTotalUsedCouponByIds($order_ids){
        $where['order_id'] = array('IN',$order_ids);
        return $this->where($where)->sum('user_coupon_amount');
    }

    public function getOrderWinningStatusByIds($order_ids){
        $where['order_id'] = array('IN',$order_ids);
        return $this->where($where)->getField('order_winnings_status',true);

    }

    public function getOrderTotalWinningAmountByIds($order_ids){
        $where['order_id'] = array('IN',$order_ids);
        return $this->where($where)->sum('order_winnings_bonus');

    }

    public function getOrderTotalAmountByIds($order_ids){
        $where['order_id'] = array('IN',$order_ids);
        $where['order_status'] = array('IN',array(3,8));
        return $this->where($where)->sum('order_total_amount');
    }

    public function getOrderWinningAmountById($order_id){
        $where['order_id'] = $order_id;
        return $this->where($where)->getField('order_winnings_bonus');
    }

    public function getOrderInfosByIds($order_ids, $offset=0, $limit=10){
        $where['order_id'] = array('IN',$order_ids);
        return $this->where($where)
            ->order('order_create_time DESC')
            ->limit($offset, $limit)
            ->select();
    }

}