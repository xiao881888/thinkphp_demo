<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-3
 * @author tww <merry2014@vip.qq.com>
 */
class ApkuploadModel extends Model{
	protected $_auto = array(
			array('updatetime', 'curr_date', self::MODEL_BOTH, 'function'),
	);
}