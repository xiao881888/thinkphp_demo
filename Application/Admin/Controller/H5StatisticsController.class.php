<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;

class H5StatisticsController extends StatisticsController{

//	protected $channel_name_map = [
//		3 => '老虎彩票',
//		4 => '新彩',
//	];

	protected $channel_uids = [];

	const CHANNEL_TYPE = 3;

	public function index()
	{
		$this->channel();
	}

	public function _converChannelName($channel_string,$extra_channel_id)
	{
		$channel_arr = explode('__', $channel_string);
		$channel_id = $channel_arr[0];
		$channel_os = $channel_arr[1];
		if (empty($channel_id)){
			$channel_id = explode('__',I('get.channel'))[0];
		}
		$channel_list = C('H5_CHANNEL_LIST');

		if (!empty($extra_channel_id)){
			$channel_id = $extra_channel_id;
		}

		$channel_name = $channel_list[$channel_id];

		return $channel_name;
	}

	public function getChannelList(){
		$channel_list = M('User')
			->field('count(1) count, concat(extra_channel_id, "__") a, sum(user_account_recharge_amount) recharge, sum(user_account_consume_amount) consume, sum(user_account_balance) balance')
			->where(['channel_type' => self::CHANNEL_TYPE])
			->join('cp_user_account b ON cp_user.uid = b.uid','left')
			->group('extra_channel_id')
			->select();
		return reindexArr($channel_list, 'a');
	}

	public function getRegisterCount($r_where){
		if (empty($r_where)){
			$r_where = ['channel_type' => self::CHANNEL_TYPE];
		}else{
			$r_where = array_merge(['channel_type' => self::CHANNEL_TYPE],$r_where);
		}

		$register_count = M('User')->field('count(1) count, concat(extra_channel_id, "__") a')->where($r_where)->group('extra_channel_id')->select();
		return reindexArr($register_count, 'a');
	}

	public function getStatisticsCount($o_where){
		if (empty($o_where)){
			$o_where = ['channel_type' => self::CHANNEL_TYPE];
		}else{
			$o_where = array_merge(['channel_type' => self::CHANNEL_TYPE],$o_where);
		}
		$statistics_count = M('UserStatistics')->field('concat(extra_channel_id, "__") a,sum(recharge_amount) recharge,sum(order_amount) consume')->where($o_where)->group('extra_channel_id')->select();
		return reindexArr($statistics_count, 'a');
	}

	public function getConsumeSum($o_where){
		if (empty($o_where)){
			$o_where = ['channel_type' => self::CHANNEL_TYPE];
		}else{
			$o_where = array_merge(['channel_type' => self::CHANNEL_TYPE],$o_where);
		}
		$sql_counsume_user = M('UserStatistics')->field('distinct uid')->where($o_where)->select(false);
		$consume_sum = M()->field('count(1) count,concat(extra_channel_id, "__") a')->table($sql_counsume_user.' tmp')->join('cp_user on cp_user.uid=tmp.uid','left')->group('extra_channel_id')->select();
		return reindexArr($consume_sum, 'a');
	}

	public function getNewConsumeSum($n_where){
		if (empty($n_where)){
			$n_where = ['channel_type' => self::CHANNEL_TYPE];
		}else{
			$n_where = array_merge(['channel_type' => self::CHANNEL_TYPE],$n_where);
		}
		$sql_counsume_user_new = M('UserStatistics')->field('distinct uid')->where($n_where)->select(false);
		$consume_sum_new = M()->field('count(1) count,concat(extra_channel_id, "__") a')->table($sql_counsume_user_new.' tmp')->join('cp_user on cp_user.uid=tmp.uid','left')->group('extra_channel_id')->select();
		return reindexArr($consume_sum_new, 'a');
	}

	private function _getChannelUids($channel_id)
	{

	}

	//注册用户列表
	public function parseChannelList(){
		$channel_list = M('User')->field('concat(extra_channel_id, "__") a')->where(['channel_type' => 3])->group('extra_channel_id')->select();
		$channel_list = reindexArr($channel_list, 'a');
		foreach($channel_list as $key=>$row){
			$channel_list[$key]['name'] = $this->_converChannelName($key);
		}
		return $channel_list;
	}

	public function genUserStatisticsWhere(){
		$new_consume_where['order_first_amount'] = array('gt',0);
		$s_date = I('s_date');
		$e_date = I('e_date');
		if($s_date){
			$consume_where['user_statistics_time'] = array('egt',$s_date);
			$new_consume_where['order_first_time'] = array('egt',$s_date);
		}
		if($e_date){
			$consume_where['user_statistics_time'] = $consume_where['user_statistics_time'] ? array(array('egt',$s_date),array('elt',$e_date)) : array('elt',$e_date);
			$new_consume_where['order_first_time'] = $new_consume_where['order_first_time'] ? array(array('egt',$s_date),array('elt',$e_date)) : array('elt',$e_date);
		}
		$new_consume_where['channel_type'] = self::CHANNEL_TYPE;
		$consume_where['channel_type'] = self::CHANNEL_TYPE;
		if(I('channel')){
			$channel_arr = explode('__',I('channel'));
//			$new_consume_where['channel_type'] = $channel_arr[1];
//			$new_consume_where['extra_channel_id'] = $channel_arr[0];

			$new_consume_where['extra_channel_id'] = $channel_arr[0];
			$consume_where['extra_channel_id'] = $channel_arr[0];
		}
		if(I('consume_min') != ''){
			$user_account_where['user_account_consume_amount'] = array('egt',floatval(I('consume_min')));
		}
		if(I('consume_max') != ''){
			$user_account_where['user_account_consume_amount'] = $user_account_where['user_account_consume_amount'] ? array(array('egt',floatval(I('consume_min'))),array('elt',floatval(I('consume_max')))) : array('elt',floatval(I('consume_max')));
		}
		if(I('recharge_min') != ''){
			$user_account_where['user_account_recharge_amount'] = array('egt',floatval(I('recharge_min')));
		}
		if(I('recharge_max') != ''){
			$user_account_where['user_account_recharge_amount'] = $user_account_where['user_account_recharge_amount'] ? array(array('egt',floatval(I('recharge_min'))),array('elt',floatval(I('recharge_max')))) : array('elt',floatval(I('recharge_max')));
		}
		if(I('user_register_time_start')){
			$consume_where['user_register_time'] = array('egt',I('user_register_time_start'));
		}
		if(I('user_register_time_end')){
			$consume_where['user_register_time'] = $consume_where['user_register_time'] ? array(array('egt',I('user_register_time_start')),array('elt',I('user_register_time_end'))) : array('elt',I('user_register_time_end'));
		}
		return array('new_consume_where'=>$new_consume_where,'user_account_where'=>$user_account_where,'consume_where'=>$consume_where);
	}

}
