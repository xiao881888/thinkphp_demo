<?php
namespace Crontab\Controller;
use Think\Controller;

class UserStatisticsInitController extends Controller {
	protected $table_order;
	protected $table_order_backup;
	protected $table_recharge;
	protected $table_user;
	protected $table_user_statistics;
	protected $s_date;
	protected $e_date;
	
	public function __construct(){
		parent::__construct();
		$this->table_order = C('DB_PREFIX').'order';
		$this->table_order_backup = $this->table_order.'_backup';
		$this->table_recharge = C('DB_PREFIX').'recharge';
		$this->table_user = C('DB_PREFIX').'user';
		$this->table_user_statistics = C('DB_PREFIX') . 'user_statistics';
		$this->_userReadDb();
	}
	private function _userReadDb(){
		if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
			M()->db(1,'mysql://tigercai_server:e4huY8J7e4@rr-bp1y62h5vwc8o62yt.mysql.rds.aliyuncs.com:3306/tigercai');
			M()->db(2,'mysql://tigercai_server:e4huY8J7e4@fzhcwlkjyxgs.mysql.rds.aliyuncs.com:3306/tigercai');
		}elseif(get_cfg_var('PROJECT_RUN_MODE')=='TEST'){
            M()->db(1,'mysql://tigercai_test2:4oPJJETM9pH6gwbL@123.56.221.173:3306/tigercai_test');
            M()->db(2,'mysql://tigercai_test2:4oPJJETM9pH6gwbL@123.56.221.173:3306/tigercai_test');
        }else{
            M()->db(1,'mysql://root:123456@192.168.1.172:3306/lottery_test');
            M()->db(2,'mysql://root:123456@192.168.1.172:3306/lottery_test');
        }
		/*if(!$_SERVER['DeveloperMode']){
			$this->M_read = M()->db(1,'mysql://tigercai_server:e4huY8J7e4@rr-bp1y62h5vwc8o62yt.mysql.rds.aliyuncs.com:3306/tigercai');
			$this->M_write = M()->db(2,'mysql://tigercai_server:e4huY8J7e4@fzhcwlkjyxgs.mysql.rds.aliyuncs.com:3306/tigercai');
		}else{
			$this->M_read = M();
			$this->M_write = M();
		}*/
	}
	//初始化资料，只用一次就弃用
	public function initOrder(){
		/*
		$this->s_date = I('s_date');
		$this->e_date = I('e_date');
		$this->_initOrder('backup');
		$this->_initOrder();
		*/
		$s_date = I('s_date');
		$this->parseDateStatistics($s_date);
		exit();
	}
	public function initBackup(){
		$s_date = I('s_date');
		$this->parseDateStatistics($s_date,'backup');
		exit();
	}
	private function _initOrder($type=''){
		$where['order_status'] = array('in','3,8');
		if($type=='backup'){
			$table = $this->table_order_backup;
			$end_list = M()->db(1)->table($table)->field('order_create_time')->where($where)->order('order_create_time desc')->find();
			$end_date = $this->e_date ? $this->e_date : substr($end_list['order_create_time'],0,10);
		}else{
			$table = $this->table_order;
			$end_date = $this->e_date ? $this->e_date : date("Y-m-d",strtotime("-1 day"));
		}
		$start_list = M()->db(1)->table($table)->field('order_create_time')->where($where)->order('order_create_time')->find();
		$start_date = $this->s_date ? $this->s_date : substr($start_list['order_create_time'],0,10);

		//每一天的数据处理
		while($start_date <= $end_date){
			$this->parseDateStatistics($start_date,$type);
			$start_date = date("Y-m-d",strtotime("$start_date+1 day"));
		}
	}
	protected function parseDateStatistics($date,$type=''){
		set_time_limit(0);
		$order_list = $this->getOrderList($date,$type);
		$recharge_list = $this->getRechargeList($date);
		$uid_list = $this->mergeUid($order_list,$recharge_list);

		foreach($uid_list as $uid=>$row){
			/*
			$data = array(
				'uid'=>$uid,
				'user_statistics_time'=>$date
			);
			$id = M()->db(2)->table($this->table_user_statistics)->add($data);
			$log_sql = M()->db(1)->_sql();
			ApiLog('user statistics: ' . $id.' '.$log_sql, 'user_statistics');
			*/

			$this->updateStatistics($uid,$date,$type);
		}
		//更新当天的统计数据
		/*
		foreach($uid_list as $uid=>$row){
		}
		*/
	}
	protected function updateStatistics($uid,$date,$type=''){
// 		$exists = M()->db(2)->table($this->table_user_statistics)->where('uid='.$uid.' and user_statistics_time="'.$date.'"')->count();
// 		if($exists){
// 			return;
// 		}
		$where['uid'] = $uid;
		//用户资料
		$user_info = M()->db(1)->table($this->table_user)->field('user_telephone,user_app_channel_id,user_app_os,user_register_time,channel_type,extra_channel_id')->where($where)->find();

		//首次、当日订单资料
		$where['order_status'] = array('in','3,8');
		$order_first_info = M()->db(1)->table($this->table_order_backup)->field('order_total_amount,order_create_time')->order('order_create_time')->where($where)->find();
		if(intval($order_first_info['order_total_amount']) == 0){
		    $order_first_info = M()->db(1)->table($this->table_order)->field('(order_total_amount-order_refund_amount) order_total_amount ,order_create_time')->order('order_create_time')->where($where)->find();
		}
		if($type == 'backup'){
			$where['order_create_time'] = array('like',$date.'%');
			$order_date_info = M()->db(1)->table($this->table_order_backup)->field('sum(order_total_amount) order_amount')->where($where)->find();
		}else{
			$where['order_create_time'] = array('like',$date.'%');
			$order_date_info = M()->db(1)->table($this->table_order)->field('sum(order_total_amount-order_refund_amount) order_amount')->where($where)->find();
		}
		$order_first_time = $order_first_info['order_create_time'] ? substr($order_first_info['order_create_time'],0,10) : '';
		$order_first_amount = $order_first_info['order_total_amount'] ? $order_first_info['order_total_amount'] : 0;
		$order_amount = $order_date_info['order_amount'] ? $order_date_info['order_amount'] : 0;

		//首次、当日充值资料
		unset($where['order_create_time']);
		unset($where['order_status']);
		$where['recharge_status'] = 1;
		$recharge_first_info = M()->db(1)->table($this->table_recharge)->field('recharge_amount,recharge_receive_time')->order('recharge_receive_time')->where($where)->find();
		$where['recharge_receive_time'] = array('like',$date.'%');
		$recharge_date_info = M()->db(1)->table($this->table_recharge)->field('sum(recharge_amount) recharge_amount')->where($where)->find();

		$recharge_first_time = $recharge_first_info['recharge_receive_time'] ? substr($recharge_first_info['recharge_receive_time'],0,10) : '';
		$recharge_first_amount = $recharge_first_info['recharge_amount'] ? $recharge_first_info['recharge_amount'] : 0;
		$recharge_amount = $recharge_date_info['recharge_amount'] ? $recharge_date_info['recharge_amount'] : 0;

		$data = array(
			'uid'=>$uid,
			'user_statistics_time'=>$date,
			'user_telephone'=>$user_info['user_telephone'],
			'user_app_channel_id'=>$user_info['user_app_channel_id'],
			'user_app_os'=>$user_info['user_app_os'],
			'user_register_time'=>$user_info['user_register_time'],
			'order_first_time'=>$order_first_time,
			'order_first_amount'=>$order_first_amount,
			'recharge_first_time'=>$recharge_first_time,
			'recharge_first_amount'=>$recharge_first_amount,
			'order_amount'=>$order_amount,
			'recharge_amount'=>$recharge_amount,
			'channel_type'=>$user_info['channel_type'],
			'extra_channel_id'=>$user_info['extra_channel_id'],
		);
		$exists_id = M()->db(1)->table($this->table_user_statistics)->where(['uid'=>$uid,'user_statistics_time'=>$date])->getField('user_statistics_id');
		
		if($exists_id){
			$id = M()->db(2)->table($this->table_user_statistics)->where(['user_statistics_id'=>$exists_id])->save($data);
		}else{
			$id = M()->db(2)->table($this->table_user_statistics)->add($data);
		}
		
		//M()->db(2)->table($this->table_user_statistics)->where(array('user_statistics_id'=>$id))->save($data);
	}
	private function getOrderList($date,$type=''){
		$where['order_create_time'] = array('like',$date.'%');
		$where['order_status'] = array('in','3,8');
		if($type == 'backup'){
			$order_list = M()->db(1)->table($this->table_order_backup)->field('distinct uid')->where($where)->select();
		}else{
			$order_list = M()->db(1)->table($this->table_order)->field('distinct uid')->where($where)->select();
		}
		return reindexArr($order_list,'uid');
	}
	private function getRechargeList($date){
		$where['recharge_receive_time'] = array('like',$date.'%');
		$where['recharge_status'] = 1;
		$recharge_list = M()->db(1)->table($this->table_recharge)->field('distinct uid')->where($where)->select();
		return reindexArr($recharge_list,'uid');
	}
	private function mergeUid($order_list,$recharge_list){
		if($order_list && $recharge_list){
			foreach($recharge_list as $uid=>$row){
				if(array_key_exists($uid,$order_list)){
					unset($order_list[$uid]);
				}
			}
		}
		return $order_list+$recharge_list;
	}
}
