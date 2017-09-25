<?php 
namespace Home\Model;
use Think\Model;

class CouponModel extends Model {
    
    public function getCouponInfo($couponId) {
        $condition = array('coupon_id'=>$couponId);
        return $this->where($condition)
                    ->find();
    }
    
    
    public function getCouponListInfo($offset=0, $limit=10) {
        $condition = array('coupon_status'=>C('COUPON_STATUS.AVAILABLE'));
        $condition['coupon_sell_end_time'] = array('gt', getCurrentTime());
        return $this->field(array(
                        'coupon_id'     => 'id',
                        'coupon_name'   => 'name',
                        'coupon_value'  => 'value',
                        'coupon_price'  => 'price',
                        'coupon_image'  => 'image',
        				'coupon_select_image' => 'select_image',
                        'UNIX_TIMESTAMP(coupon_sell_start_time)' => 'start_time',
                        'UNIX_TIMESTAMP(coupon_sell_end_time)'   => 'end_time',
                    ))
                    ->order('coupon_sell_end_time ASC')
                    ->limit($offset, $limit)
                    ->where($condition)
                    ->select();
    }

	public function getCouponListForSale($offset = 0, $limit = 10){
		$condition['coupon_status'] = C('COUPON_STATUS.AVAILABLE');
		$condition['coupon_is_sell'] = COUPON_IS_FOR_SALE;
		$order_by = ' coupon_value ASC';
		$coupon_list_for_sale = $this->limit($offset, $limit)->where($condition)->order($order_by)->select();
		return $this->formatCouponList($coupon_list_for_sale);
	}
	
	public function formatCouponList($coupon_list){
		$formated_coupon_list = array();
		foreach($coupon_list as $coupon_info){
			if($this->checkCouponIsOverTime($coupon_info)){
				continue;
			}
			$formated_coupon_list[] = $this->formatCouponInfo($coupon_info);
		}
		return $formated_coupon_list;
	}
    
	public function formatCouponInfo($coupon_info){
		$formated_coupon_info['id'] = $coupon_info['coupon_id'];
		$formated_coupon_info['name'] = $coupon_info['coupon_name'];
		$formated_coupon_info['value'] = $coupon_info['coupon_value'];
		$formated_coupon_info['price'] = $coupon_info['coupon_price'];
		$formated_coupon_info['image'] = $coupon_info['coupon_image'];
		$formated_coupon_info['start_time'] = strtotime($coupon_info['coupon_sell_start_time']);
		$formated_coupon_info['end_time'] = strtotime($coupon_info['coupon_sell_end_time']);
		$formated_coupon_info['slogon'] = (string)$coupon_info['coupon_slogon'];
		$formated_coupon_info['select_image'] = (string)$coupon_info['coupon_select_image'];
        $formated_coupon_info['diaplay_name'] = emptyToStr($coupon_info['coupon_display_name']);
        $formated_coupon_info['condition'] = getCouponCondition($coupon_info['coupon_min_consume_price']);
        $formated_coupon_info['support_lottery'] = emptyToStr($coupon_info['coupon_support_lottery']);
		return $formated_coupon_info;
	}
	
	private function checkCouponIsOverTime($coupon_info){
		$coupon_valid_date_type = $coupon_info['coupon_valid_date_type'];
		if($coupon_valid_date_type==COUPON_VALID_DATE_OF_DATE_RANGE){
			if(strtotime($coupon_info['coupon_sell_start_time']>time())){
				return true;
			}
			if(strtotime($coupon_info['coupon_sell_end_time'])<time()){
				return true;
			}
		}else if($coupon_valid_date_type==COUPON_VALID_DATE_OF_DAY_NUMBER){
			return false;
		}
		return false;
	}
}


?>