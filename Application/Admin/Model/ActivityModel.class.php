<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-3
 * @author tww <merry2014@vip.qq.com>
 */
class ActivityModel extends Model{
	
	public function getLikeFields(){
		return array('activity_name');
	}
	
	public function getStatusFieldName(){
		return 'activity_status';
	}
	
	public function getOrderFields(){
		return 'activity_id desc';
	}
}