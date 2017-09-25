<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-4
 * @author tww <merry2014@vip.qq.com>
 */
class UserModel extends Model{
	public function getUserMap($uids){
		$where = array();
		$where['uid'] = array('IN', $uids);
		return $this->where($where)->getField('uid,user_telephone');
	}
	
	public function getStatusFieldName(){
		return 'user_status';
	}
	
	public function getReportData(){
		$field = array('user_register_time');
		$order = 'user_register_time ASC';
		return $this->field($field)->order($order)->select();
	}
	
	public function getUidByTelephone($user_telephone){
		$where = array();
		$where['user_telephone'] = $user_telephone;
		return $this->where($where)->getField('uid');
	}
	
	public function getUserInfo($uid){
		$where = array();
		$where['uid'] = $uid;
		return $this->where($where)->find();
	}
	
	public function getUserStatus($uid){
		$where = array();
		$where['uid'] = $uid;
		return $this->where($where)->getField('user_status');
	}

	public function countUserByDate($start_date, $end_date){
		$sql = "SELECT DATE_FORMAT(user_register_time, '%Y-%m-%d') `day`, count(1)  `c` FROM ".$this->getTableName()." WHERE user_register_time >= '{$start_date}' AND user_register_time <= '{$end_date}' Group By `day`";
	
		return $this->query($sql);
	}

	public function countUserByAppOs($time_rang_start, $time_rang_end){
		$result = array();

		$sql = "SELECT DATE_FORMAT(user_register_time, '%Y-%m-%d') `day` , count(1) `c` FROM ".$this->getTableName()." WHERE user_register_time >= '{$time_rang_start}' AND user_register_time <= '{$time_rang_end}' and user_app_os = 1 Group By day";

		$result['android'] = $this->query($sql);

		$sql = "SELECT DATE_FORMAT(user_register_time, '%Y-%m-%d') `day` , count(1) `c` FROM ".$this->getTableName()." WHERE user_register_time >= '{$time_rang_start}' AND user_register_time <= '{$time_rang_end}' and user_app_os = 2 Group By day";

		$result['ios'] = $this->query($sql);

		return $result;
	}

	public function countUserByChannel($time_rang_start, $time_rang_end){
		$sql = "SELECT user_app_os, user_app_channel_id , DATE_FORMAT(user_register_time, '%Y-%m-%d') `day`, count(1) `c` FROM ".$this->getTableName()." WHERE user_register_time >= '{$time_rang_start}' AND user_register_time <= '{$time_rang_end}' Group By user_app_os, user_app_channel_id, day";

		return $this->query($sql);
	}

    public function getNoTigerUsers($uids){
	    $where['channel_type'] = array('IN',array(C('BAIWAN_CHANNEL_TYPE'),C('NEW_CHANNEL_TYPE')));
	    $where['uid'] = array('IN',$uids);
	    return $this->where($where)->getField('uid',true);
    }
}