<?php 
namespace Admin\Model;
use Think\Model;

class UserIntegralLogModel extends IntegralBaseModel {


	protected $autoCheckFields = false;

	public function setTrueTable($uid){
		$table_name = 'ti_user_integral_log_'.ceil($uid/10000);
		$this->trueTableName = $table_name;

		$this->_checkTableInfo();
		return $this;
	}

}



