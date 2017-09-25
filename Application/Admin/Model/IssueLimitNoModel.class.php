<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-3
 * @author tww <merry2014@vip.qq.com>
 */
class IssueLimitNoModel extends Model{
	protected $_auto = array(
        array('iln_createtime', 'curr_date', self::MODEL_INSERT, 'function'),
        array('iln_modifytime', 'curr_date', self::MODEL_UPDATE, 'function'),
	);

	public function getStatusFieldName(){
		return 'iln_status';
	}
}