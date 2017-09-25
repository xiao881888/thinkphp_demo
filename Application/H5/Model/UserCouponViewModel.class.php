<?php
namespace H5\Model;
use Think\Model\ViewModel;

class UserCouponViewModel extends ViewModel {

	const CASH_COUPON = 1;
	const FULL_REDUCED_COUPON = 2;

	const CASH_COUPON_NAME = '现金红包';
	const FULL_REDUCED_COUPON_NAME = '满减红包';
    
    protected $viewFields = array(
        'Coupon' => array(
            'coupon_name'  => 'name',
            'coupon_value' => 'value',
            'coupon_price' => 'price',
            'coupon_image' => 'image',
            'coupon_select_image' => 'select_image',
        	'coupon_valid_date_type' => 'type',
			'coupon_type' => 'coupon_type',
			'coupon_select_image'  => 'select_image',
            'coupon_display_name'  => 'display_name',
            'coupon_support_lottery'  => 'support_lottery',
        ),
        'UserCoupon' => array(
            'user_coupon_id' => 'id',
            'coupon_id' => 'coupon_id',
        	'user_coupon_start_time' => 'start_time',
            'user_coupon_end_time' => 'end_time',
            'user_coupon_balance' => 'balance',
            'user_coupon_amount' => 'coupon_amount',
        	'user_coupon_desc'    => 'user_coupon_desc',
			'coupon_type' => 'user_coupon_type',
			'coupon_min_consume_price' => 'coupon_min_consume_price',
			'coupon_lottery_ids' => 'coupon_lottery_ids',
			'user_coupon_status' => 'user_coupon_status',
            '_on' => 'Coupon.coupon_id = UserCoupon.coupon_id',
        ),
    );
    
    public function queryUserCouponListForWebPay($uid,$lack_money){
    	$map['uid'] = $uid;
    	$map['user_coupon_status'] = C('USER_COUPON_STATUS.AVAILABLE');
    	$map['user_coupon_start_time'] = array('lt', getCurrentTime());
    	$map['user_coupon_end_time'] = array('gt', getCurrentTime());
    	if($lack_money){
    		$map['user_coupon_balance'] = array('egt', $lack_money);
    	}else{
    		$map['user_coupon_balance'] = array('gt', $lack_money);
    	}
    	$order_by = 'user_coupon_end_time ASC,user_coupon_balance DESC';
    	return $this->where($map)->order($order_by)->select();
    }

	//TODO 两个方法何在一起
	public function getUserCouponList($uid, $type, $offset=0, $limit=10, $sort='' ,$sdk_version = 0,$os = 0) {

		if($type==C('USER_OWEN_COUPON.ALL')) {
			$condition = array();
		} elseif ($type==C('USER_OWEN_COUPON.AVAILABLE')) {
			$condition = array(
				'user_coupon_status' => C('USER_COUPON_STATUS.AVAILABLE'),
				'user_coupon_balance' => array('gt', 0),
				'user_coupon_start_time' => array('lt', getCurrentTime()),
				'user_coupon_end_time' => array('gt', getCurrentTime()),
			);
		} elseif ($type==C('USER_OWEN_COUPON.WAIT')) {
			$condition = array(
				'user_coupon_status' => C('USER_COUPON_STATUS.AVAILABLE'),
				'user_coupon_balance' => array('gt', 0),
				'user_coupon_start_time' => array('gt', getCurrentTime()),
			);
		} elseif ($type==C('USER_OWEN_COUPON.EXPIRE')) {
			$complex = array(
				'user_coupon_end_time' => array('lt', getCurrentTime()),
				'user_coupon_balance' => 0,
				'_logic' => 'or',
			);
			$condition['_complex'] = $complex;
		}

		$condition['uid'] = $uid;

		if(!empty($sdk_version) && !empty($os)){
			if($sdk_version < 6 && $os == OS_OF_ANDROID){
				$condition['coupon_type'] = self::CASH_COUPON; //普通红包
			}
		}else{
			$condition['coupon_type'] = self::CASH_COUPON; //普通红包
		}

		if($sort==C('SORT_COUPON_LIST.CREATE_TIME_DESC')) {
			$sordBy = 'coupon_create_time DESC';
		} else {
			$sordBy = 'user_coupon_end_time ASC,user_coupon_balance DESC';
		}

		if ($type==C('USER_OWEN_COUPON.WAIT')) {
			$sordBy = 'user_coupon_end_time ASC';
		}
		$user_coupon_list = $this->where($condition)->limit($offset, $limit)->order($sordBy)->select();
		$formated_user_coupon_list = $this->_formatCouponList($user_coupon_list);
		return $formated_user_coupon_list;
	}

	public function getUserCouponListForNativePay($uid) {

		$condition = array(
			'user_coupon_status' => C('USER_COUPON_STATUS.AVAILABLE'),
			'user_coupon_balance' => array('gt', 0),
			'user_coupon_start_time' => array('lt', getCurrentTime()),
			'user_coupon_end_time' => array('gt', getCurrentTime()),
			'uid' => $uid
		);

		$sordBy = 'user_coupon_end_time ASC,user_coupon_balance DESC';
		$user_coupon_list = $this->where($condition)->order($sordBy)->select();
		return $user_coupon_list;
	}

	public function formatCouponListForNativePay($user_coupon_list){
		$new_coupon_list = array();
		$user_coupon_list = array_values($user_coupon_list);
		foreach($user_coupon_list as $key => $user_coupon_info){
			if($user_coupon_info['coupon_type'] == self::CASH_COUPON){
				$new_coupon_list[0]['group_name'] = self::CASH_COUPON_NAME;
				$new_coupon_list[0]['list'][] = $this->_formatCouponInfoForPay($user_coupon_info,$key);
			}else{
				$new_coupon_list[1]['group_name'] = self::FULL_REDUCED_COUPON_NAME;
				$new_coupon_list[1]['list'][] = $this->_formatCouponInfoForPay($user_coupon_info,$key);
			}
		}
		ksort($new_coupon_list);
		$new_coupon_list = array_values($new_coupon_list);
		return $new_coupon_list;
	}

	private function _formatCouponInfoForPay($user_coupon_info,$index){
		$new_user_coupon_info['id'] = $user_coupon_info['id'];
		$new_user_coupon_info['name'] = $user_coupon_info['user_coupon_desc'];
		$new_user_coupon_info['type'] = $user_coupon_info['coupon_type'];
		$new_user_coupon_info['value'] = $user_coupon_info['value'];
		$new_user_coupon_info['price'] = $user_coupon_info['price'];
		$new_user_coupon_info['image'] = $user_coupon_info['image'];
		$new_user_coupon_info['select_image'] = $user_coupon_info['select_image'];
		$new_user_coupon_info['is_default'] = $index == 0 ? 1 : 0;
		$new_user_coupon_info['start_time'] = strtotime($user_coupon_info['start_time']);
		if($user_coupon_info['end_time']==COUPON_DATE_FOREVER){
			$new_user_coupon_info['end_time'] = 0;
		}else{
			$new_user_coupon_info['end_time'] = strtotime($user_coupon_info['end_time']);
		}
		$new_user_coupon_info['balance'] = $user_coupon_info['balance'];

        $coupon_id = $user_coupon_info['coupon_id'];
        $coupon_info =  D('Home/Coupon')->getCouponInfo($coupon_id);
        $new_user_coupon_info['display_name'] = emptyToStr($coupon_info['coupon_display_name']);
        $new_user_coupon_info['condition'] = ($user_coupon_info['coupon_min_consume_price'] == 0.00) ? '' : getCouponCondition($user_coupon_info['coupon_min_consume_price']);
        $new_user_coupon_info['support_lottery'] = emptyToStr($coupon_info['coupon_support_lottery']);

		return $new_user_coupon_info;
	}

    
    private function _formatCouponList($user_coupon_list){
    	$new_coupon_list = array();
    	foreach($user_coupon_list as $user_coupon_info){
    		$new_coupon_list[] = $this->_formatCouponInfo($user_coupon_info);
    	}
    	ApiLog('$new_coupon_list:'.print_r($new_coupon_list,true),'testUserCoupon');
    	return $new_coupon_list;
    }

	private function _formatCouponInfo($user_coupon_info){
		ApiLog('cou info:'.print_r($user_coupon_info,true), 'bet');
		$new_user_coupon_info['name'] = $user_coupon_info['user_coupon_desc'];
		$new_user_coupon_info['value'] = $user_coupon_info['value'];
		$new_user_coupon_info['price'] = $user_coupon_info['price'];
		$new_user_coupon_info['image'] = $user_coupon_info['image'];
		$new_user_coupon_info['id'] = $user_coupon_info['id'];
		$new_user_coupon_info['start_time'] = strtotime($user_coupon_info['start_time']);
		if($user_coupon_info['end_time']==COUPON_DATE_FOREVER){
			$new_user_coupon_info['end_time'] = 0;
		}else{
			$new_user_coupon_info['end_time'] = strtotime($user_coupon_info['end_time']);
		}
		$new_user_coupon_info['balance'] = $user_coupon_info['balance'];

		$coupon_id = $user_coupon_info['coupon_id'];
		$coupon_info =  D('Home/Coupon')->getCouponInfo($coupon_id);
        $new_user_coupon_info['display_name'] = emptyToStr($coupon_info['coupon_display_name']);
        $new_user_coupon_info['condition'] = getCouponCondition($user_coupon_info['coupon_min_consume_price']);
        $new_user_coupon_info['support_lottery'] = emptyToStr($coupon_info['coupon_support_lottery']);
		return $new_user_coupon_info;
	}


}


