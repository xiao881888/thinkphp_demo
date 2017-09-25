<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date
 * @author 
 */
class ChannelCouponModel extends Model{
    protected $_validate = array(
        array('cc_code','require','兑换码必须！', 0, 'unique'), 
        array('channel_id','require','渠道必选！'), 
        array('plan_id','require','方案必选！'), 
    );

    protected $_auto = array(
        array('cc_status', 0, self::MODEL_INSERT),
        array('cc_createtime', 'getCurrentTime', self::MODEL_INSERT, 'function'),
        array('cc_modifytime', 'getCurrentTime', self::MODEL_BOTH, 'function')
    );

    public function getStatusFieldName(){
        return 'cc_status';
    }
}