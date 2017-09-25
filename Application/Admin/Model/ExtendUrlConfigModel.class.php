<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-24
 * @author tww <merry2014@vip.qq.com>
 */
class ExtendUrlConfigConfigModel extends Model{
    protected $_auto = array(
        array('updatetime', 'curr_date', self::MODEL_BOTH, 'function'),
    );
}