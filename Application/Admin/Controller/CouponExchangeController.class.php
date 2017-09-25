<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-5
 * @author tww <merry2014@vip.qq.com>
 */
class CouponExchangeController extends GlobalController{
	
	public function index(){
		$where = $this->_getSearchCondition();
		$list = $this->lists('coupon_exchange', $where);

		$users = array();
		if (!empty($list)) {
			$uids = extractArrField($list, 'uid');
			$users = D('User')->getUserMap($uids);
		}

		$this->assign('list', $list);
		$this->assign('users', $users);

		$this->display();
	}

	public function _before_index(){
		$this->_assignCouponMap();
	}

	public function _before_add(){
		$this->_assignCouponMap();
	}
	
	private function _assignCouponMap(){
		$coupons = D('Coupon')->getCouponMap();
		$this->assign('coupons', $coupons);
	}
	
	public function batchAdd(){	
		$coupon_id 		= I('coupon_id');
		$ce_start_time  = I('ce_start_time');
		$ce_end_time	= I('ce_end_time');
		$number 		= I('number');
		if($coupon_id && $ce_start_time && $number &&$ce_end_time){
			$coupon_codes = array();
			for($i=0; $i<$number; $i++){
				$temp = array();
				$temp['coupon_id']			= $coupon_id;
				$temp['ce_exchange_code'] 	= strtoupper(random_string(12));
				$temp['ce_create_time'] 	= curr_date();
				$temp['ce_end_time'] 		= $ce_end_time;
				$temp['ce_start_time']		= $ce_start_time;
				$temp['ce_operator_id']		= get_curr_uid();
				$coupon_codes[] = $temp;
			}
			$result = M('CouponExchange')->addAll($coupon_codes);
			if($result){
				$this->success('操作成功！',U('index'));
			}else{
				$this->error('操作失败！');
			}
		}else{
			$this->error('参数错误！');
			
		}	
	}
}