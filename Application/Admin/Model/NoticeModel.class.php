<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-3
 * @author tww <merry2014@vip.qq.com>
 */
class NoticeModel extends Model{
	
	public function getLikeFields(){
		return array('notice_title');
	}
	
	public function getStatusFieldName(){
		return 'notice_status';
	}
	
}