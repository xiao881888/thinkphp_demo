<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-5
 * @author tww <merry2014@vip.qq.com>
 */
class CouponModel extends Model{
	
	protected $_auto = array(
		array('coupon_create_time', 'curr_date', self::MODEL_INSERT, 'function'),
		array('coupon_modify_time', 'curr_date', self::MODEL_UPDATE, 'function')
	);
	
	
	public function getStatusFieldName(){
		return 'coupon_status';
	}
	
	public function getCouponMap(){
		return $this->getField('coupon_id,coupon_name');
	}

    public function getEnableCouponMap(){
        $where['coupon_status'] = 1;
        return $this->where($where)->getField('coupon_id,coupon_name');
    }

    public function getCouponInfoById($id){
	    $where['coupon_id'] = $id;
        return $this->where($where)->find();
    }

    public function getActivityIds(){
        return $this->group('activity_id')->getField('activity_id',true);
    }

    public function getCouponNameById($id){
        $where['coupon_id'] = $id;
        return $this->where($where)->getField('coupon_name');
    }

}