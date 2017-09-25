<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date
 * @author 
 */
class ChannelCouponPlanModel extends Model{
    protected $_validate = array(
        array('plan_name','require','名称必须！'), 
        array('plan_total_value','number','红包总金额必须为数字！'), 
        array('plan_devide_section','require','分配规则必须！'), 
        array('plan_devide_date_type','require','间隔单位必须！'), 
        array('plan_devide_step','number','间隔步长必须为数字！'),
    );

    public function getChannelCouponPlanMap(){
        return $this->getField('plan_id, plan_name');
    }
}