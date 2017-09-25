<?php
namespace Log\Model;
use Think\Model;
/**
 * @date 2014-12-25
 * @author tww <merry2014@vip.qq.com>
 */
class EventModel extends Model{
	public function getInfo($event_id){
		$where = array();
		$where['event_id'] = $event_id;
		return $this->cache(true)->where($where)->find();
	}
}