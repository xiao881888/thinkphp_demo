<?php
namespace Content\Model;
use Think\Model;
/**
 * @date 2014-12-8
 * @author tww <merry2014@vip.qq.com>
 */
class ActivityModel extends Model{
	public function getActivities(){
		$field = array(
				'activity_id',
				'activity_name',
				'activity_start_time', 
				'activity_end_time', 
				'activity_carousel', 
				'activity_status',
				'activity_image'
		);
		$where = array();
		$where['activity_status'] = 1;
		$order = 'activity_sort ASC';
		return $this->where($where)->field($field)->order($order)->select();
	}
	
	public function getActivityInfoById($id){
		$where = array();
		$where['activity_id'] = $id;
		return $this->where($where)->find();
	}
}