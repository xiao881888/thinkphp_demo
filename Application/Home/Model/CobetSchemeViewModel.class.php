<?php
namespace Home\Model;
use Think\Model\ViewModel;
/**
 * @date 2014-11-19
 * @author tww <merry2014@vip.qq.com>
 */
class CobetSchemeViewModel extends ViewModel{
	protected $viewFields = array(
		'CobetScheme'=>array(
				 'scheme_id',
		         'scheme_identity',
				 'scheme_serial_number',
		         'uid',
				 'order_id',
				 'cobet_order_id',
				 'lottery_id',
				 'scheme_createtime',
				 'scheme_bet_content',
				 'scheme_total_amount',
				 'scheme_total_unit',
				 'scheme_amount_per_unit',
				 'scheme_guarantee_unit',
                 'scheme_bought_unit',
		         'scheme_bought_rate',
		         'scheme_status',
				 'scheme_show_status',
                 'scheme_commission_rate',
                 'scheme_history_record',
                 '_type'=>'LEFT',
        ),
		'CobetRecord'=>array(
				 'record_id',
		         'scheme_id'=> 'record_scheme_id',
		         'uid' 	=> 'record_uid',
				 'type' 	=> 'prize_time',
				 '_on' => 'CobetScheme.scheme_id = CobetRecord.scheme_id'),
	);

    public function getSchemeListByUid($status,$scheme_uid = 0,$sort = 0,$offset=0,$limit=10,$lottery_id=0,$sub_sort = 2,$filter = 2,$login_uid = 0){
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
            $where['record_uid'] = $login_uid;
        }

        return $this->where($where)->order($order_by)->limit($offset,$limit)->group('scheme_id')->select();
    }


   /* public function getSchemeIdsByUid($status,$uid = 0,$offset=0,$limit=10){
        if($uid){
            $map['uid'] = $uid;
            $map['record_uid'] = $uid;
            $map['_logic'] = 'or';
            $where['_complex'] = $map;
        }
        if($status){
            $where['scheme_status'] = array('IN',$status);
        }

        $order_by = 'scheme_createtime DESC';

        return $this->where($where)->order($order_by)->limit($offset,$limit)->group('scheme_id')->getField('scheme_id',true);
    }*/

    public function getSchemeIdsByUid($uid){
        $where['record_uid'] = $uid;
        $where['uid'] = array('neq',$uid);
        return $this->where($where)->group('scheme_id')->getField('scheme_id',true);
    }


}