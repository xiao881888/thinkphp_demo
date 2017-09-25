<?php

namespace Home\Model;

use Think\Model;

class CobetSchemeModel extends Model{

	public function getInfo($scheme_id){
		$map['scheme_id'] = $scheme_id;
		return $this->where($map)->find();
	}

	public function getInfoByIdentity($scheme_identity){
		$map['scheme_identity'] = $scheme_identity;
		return $this->where($map)->find();
	}

	public function addScheme($uid, $serial_number, $verified_params){
		$scheme_data['scheme_identity'] = $verified_params['scheme_identity'];
		$scheme_data['scheme_serial_number'] = $serial_number;
		$scheme_data['uid'] = $uid;
		$scheme_data['lottery_id'] = $verified_params['lottery_id'];
		$scheme_data['scheme_createtime'] = getCurrentTime();
		$scheme_data['scheme_bet_content'] = $verified_params['order_info'];
		$scheme_data['scheme_total_amount'] = $verified_params['total_amount'];
		$scheme_data['scheme_total_unit'] = $verified_params['total_unit'];
		$scheme_data['scheme_amount_per_unit'] = $verified_params['scheme_amount_per_unit'];
		$scheme_data['scheme_guarantee_unit'] = $verified_params['ensure'];
		$scheme_data['scheme_bought_unit'] = $verified_params['subscribe'];
		$scheme_data['scheme_bought_rate'] = $verified_params['subscribe']/$verified_params['total_unit'];
		$scheme_data['scheme_status'] = COBET_SCHEME_STATUS_OF_NO_BEGIN;
		$scheme_data['scheme_show_status'] = $verified_params['type'];
		$scheme_data['scheme_commission_rate'] = $verified_params['commission'];
		$scheme_data['scheme_issue_id'] = isset($verified_params['issue_id'])?$verified_params['issue_id']:0;
		return $this->add($scheme_data);
	}

	public function getStatus($order_status, $order_winnings_status, $order_distribute_status){
		if ($order_status == C('ORDER_STATUS.UNPAID')) {
			$status = self::NO_PAY; // 未支付
		} else if ($order_status == C('ORDER_STATUS.PRINTOUT_ERROR')) {
			$status = self::PAY_FAIL; // 出票失败
		} else if ($order_status == C('ORDER_STATUS.PAYMENT_SUCCESS') || $order_status == C('ORDER_STATUS.PRINTOUTING')) {
			$status = self::PRINTOUTING; // 出票中
		} else if ($order_status == C('ORDER_STATUS.PRINTOUT_ERROR_REFUND')) {
			$status = self::PRINTOUT_REFUND; // 出票失败且退款
		} else if ($order_status == C('ORDER_STATUS.BET_ERROR')) {
			$status = self::PRINTOUT_REFUND; // 投注失败 =>出票失败且退款
		} else if ($order_status == C('ORDER_STATUS.PRINTOUTING_PART_REFUND')) {
			$status = self::PRINTOUTING; // 出票中，部分失败退款 =>出票中
				                             // }else if($order_status == C('ORDER_STATUS.PRINTOUTED_PART_REFUND')){
				                             // $status = self::PRINTOUT_REFUND;//投注失败 =>出票失败且退款
		} 

		else if ($order_winnings_status == C('ORDER_WINNINGS_STATUS.WAITING')) {
			$status = self::NO_PRIZE; // 未开奖
		} else if ($order_winnings_status == C('ORDER_WINNINGS_STATUS.NO')) {
			$status = self::NO_WINNER; // 未中奖
		} else if ($order_winnings_status == C('ORDER_WINNINGS_STATUS.YES')) {
			$status = self::WINNER; // 已中奖
		} else if ($order_winnings_status == C('ORDER_WINNINGS_STATUS.PART')) {
			$status = self::WIN_OF_PART_ORDER;
		}
		
		if ($order_distribute_status == C('ORDER_DISTRIBUTE_STATUS.YES')) {
			$status = self::DISTRIBUTE_ING; // 派奖中
		}
		return $status ? $status : self::ERR;
	}

	public function getCompleteSchemeList(){
	    $where['scheme_status'] = C('COBET_SCHEME_STATUS.SCHEME_COMPLETE');
	    return $this->where($where)->select();
    }

    public function getSchemeListByUid($status,$scheme_uid = 0,$sort = 0,$offset=0,$limit=10,$lottery_id=0,$sub_sort = 2,$filter = 0,$login_uid = 0,$is_index = 0){
	    if($scheme_uid){
            $where['uid'] = $scheme_uid;
        }
        if($status){
            $where['scheme_status'] = array('IN',$status);
        }
        if($lottery_id){
            if($lottery_id == C('JC.JCZQ')){
                $jz_lottery = C('JCZQ');
                $where['lottery_id'] = array('IN',$jz_lottery);

            }elseif($lottery_id == C('JC.JCLQ')){
                $jl_lottery = C('JCLQ');
                $where['lottery_id'] = array('IN',$jl_lottery);
            }else{
                $where['lottery_id'] = $lottery_id;
            }
        }
	    if($sort == 1){
	        //按进度
	        $order_by = 'scheme_bought_rate';
        }elseif($sort == 2){
            $order_by = 'scheme_history_record';
        }elseif($sort == 3){
            $order_by = 'scheme_total_amount';
        }else{
            $order_by = 'scheme_createtime';
        }
        $order_by_sort = $sub_sort == 2 ? ' DESC' : ' ASC';
        $order_by = $order_by .$order_by_sort;
        if($filter){
            $where['uid'] = $login_uid;
        }
        if($is_index){
            $where['scheme_bought_rate'] = array('LT',1);
        }

	    return $this->where($where)->order($order_by)->limit($offset,$limit)->select();
    }

    public function updateSchemeForSuccess($scheme_id,$order_id){
        $where['scheme_id'] = $scheme_id;
        $data['order_id'] = $order_id;
        $data['scheme_status'] = C('COBET_SCHEME_STATUS.PRINTOUT');
        return $this->where($where)->save($data);
    }

    public function getOrderIdsByUid($uid){
        $where['uid'] = $uid;
        $where['scheme_status'] = C('COBET_SCHEME_STATUS.PRINTOUT');
        return $this->where($where)->getField('order_id',true);
    }

    public function getSchemeIdsByUid($uid){
        $where['uid'] = $uid;
        return $this->where($where)->getField('scheme_id',true);
    }

    public function getHistoryDataById($uid){
        $where['uid'] = $uid;
        $where['scheme_history_data'] = array('NEQ','');
        $where['scheme_status'] = array('IN',array(COBET_SCHEME_STATUS_OF_NO_BEGIN_BOUGHT,COBET_SCHEME_STATUS_OF_ONGOING,
            COBET_SCHEME_STATUS_OF_SCHEME_COMPLETE,COBET_SCHEME_STATUS_OF_PRINTOUT));
        return $this->where($where)->order('scheme_createtime DESC')->getField('scheme_history_data');
    }

    public function getInfoByOrderId($order_id){
        $map['order_id'] = $order_id;
        return $this->where($map)->find();
    }



}