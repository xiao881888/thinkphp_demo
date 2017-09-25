<?php
namespace Log\Model;
use Think\Model;
/**
 * @date 2014-12-25
 * @author tww <merry2014@vip.qq.com>
 */
class EventUserModel extends Model{
	public function getInfos($event_id){
		$where = array();
		$where['event_id'] = $event_id;
		return $this->where($where)->select();
	}
}