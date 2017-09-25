<?php

namespace Crontab\Model;

use Think\Model;

class OrderModel extends Model
{

	private $_completeStatus = 3;
	private $_partiallyStatus = 8;
    private $_readDB = 'mysql://tigercai_server:e4huY8J7e4@rr-bp1y62h5vwc8o62yt.mysql.rds.aliyuncs.com:3306/tigercai';

	public function getTodayOrderList($firstCurrDate, $lastCurrDate, $alreadyAddPointOrderList = array())
	{
		$where['order_create_time'] = array(array('egt', $firstCurrDate),array('elt', $lastCurrDate));
		if(!empty($alreadyAddPointOrderList)){
			$where['order_id'] = array('not in', $alreadyAddPointOrderList);
		}
		$where['order_status'] = array($this->_completeStatus,$this->_partiallyStatus,'or');

		if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
			$todayOrderList = $this->db(1,$this->_readDB,true)->where($where)->select();
			$this->db(0);
		} else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
			$todayOrderList = $this->where($where)->select();
		} else {
			$todayOrderList = $this->where($where)->select();
		}
		return $todayOrderList;
	}

	public function getOrderUsersByScheduleIds($schedule_ids)
	{
		$where['issue_id'] = array('in',$schedule_ids);
		$where['order_status'] = array($this->_completeStatus,$this->_partiallyStatus,'or');
		if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
			$uids = $this->distinct(true)->db(1,C('READ_DB'),true)->where($where)->getField('uid',true);
			$this->db(0);
		} else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
			$uids = $this->distinct(true)->where($where)->getField('uid',true);
		} else {
			$uids = $this->distinct(true)->where($where)->getField('uid',true);
		}
		return $uids;
	}

    public function getOrderTotalAmountByUid($uid,$order_type){
        $where['uid'] = $uid;
        if($order_type){
            $where['order_type'] = $order_type;
        }

        if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
            return $this->db(1,C('READ_DB'),true)->where($where)->sum('order_total_amount - order_refund_amount');
        } else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
            return $this->where($where)->sum('order_total_amount - order_refund_amount');
        } else {
            return $this->where($where)->sum('order_total_amount - order_refund_amount');
        }

    }

    public function getOrderWinningAmountByUid($uid,$order_type){
        $where['uid'] = $uid;
        if($order_type){
            $where['order_type'] = $order_type;
        }
        $where['order_winnings_status'] = array('IN',array(1,2));
        if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
            return $this->db(1,C('READ_DB'),true)->where($where)->sum('order_winnings_bonus');
        } else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
            return $this->where($where)->sum('order_winnings_bonus');
        } else {
            return $this->where($where)->sum('order_winnings_bonus');
        }
    }

    public function getPrizeOrderIdsByUid($uid,$order_type,$limit=10){
        $where['uid'] = $uid;
        if($order_type){
            $where['order_type'] = $order_type;
        }
        $where['order_winnings_status'] = array('IN',array(-1,1,2));
        if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
            return $this->db(1,C('READ_DB'),true)->where($where)->limit($limit)->getField('order_id',true);
        } else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
            return $this->where($where)->limit($limit)->getField('order_id',true);
        } else {
            return $this->where($where)->limit($limit)->getField('order_id',true);
        }
    }

    public function getWiningOrderIdsByUid($uid,$order_type,$order_ids){
        $where['uid'] = $uid;
        if($order_type){
            $where['order_type'] = $order_type;
        }
        $where['order_id'] = array('IN',$order_ids);
        $where['order_winnings_status'] = array('IN',array(1,2));
        if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
            return $this->db(1,C('READ_DB'),true)->where($where)->getField('order_id',true);
        } else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
            return $this->where($where)->getField('order_id',true);
        } else {
            return $this->where($where)->getField('order_id',true);
        }
    }
}