<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-3
 * @author tww <merry2014@vip.qq.com>
 */
class CheckInformationModel extends Model{
	
	public function getStatusFieldName(){
		return 'information_status';
	}

}