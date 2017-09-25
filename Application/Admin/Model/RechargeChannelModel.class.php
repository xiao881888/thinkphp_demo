<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-17
 * @author tww <merry2014@vip.qq.com>
 */
class RechargeChannelModel extends Model{
	public function getRechargeChannelsMap(){
		return $this->getField('recharge_channel_id,recharge_channel_name');
	}
}