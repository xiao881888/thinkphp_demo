<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-29
 * @author tww <merry2014@vip.qq.com>
 */
class EventUserModel extends Model{
	protected $_validate = array(
		array('event_id', 'require', '事件类型必填！'),
		array('uid', 'require', '联系人必填！'),
		array('eu_user_phone', 'require', '手机号必填！'),
		array('eu_user_email', 'require', '邮箱必填！')
	);
}