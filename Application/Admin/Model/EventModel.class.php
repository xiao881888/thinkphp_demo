<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-25
 * @author tww <merry2014@vip.qq.com>
 */
class EventModel extends Model{
	public function getEventMap(){
		return $this->getField('event_id,event_name');
	}
}