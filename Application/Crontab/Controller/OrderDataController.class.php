<?php
namespace Crontab\Controller;
use Think\Controller;

class OrderDataController extends Controller {
	private $_table_type = array(
			'order',
			'ahsyxw_ticket',
			'dlt_ticket',
			'fc3d_ticket',
			'jclq_ticket',
			'jczq_ticket',
			'jsks_ticket',
			'sdsyxw_ticket',
			'ssq_ticket',
			'jc_order_detail',
	);
	private $_table_suffix = array(
			's1',
			's2',
			's3',
			's4',
// 			'01',
// 			'02',
// 			'03',
// 			'04',
// 			'05',
// 			'06',
// 			'07',
// 			'08',
// 			'09',
// 			'10',
// 			'11',
// 			'12' 
	);
	
	public function __construct(){
		parent::__construct();
	}
	
	public function genOrderAndTicketTable(){
		die();
		foreach ($this->_table_type as $table_type) {
			foreach ($this->_table_suffix as $table_suffix) {
				$orgi_table_name = C('DB_PREFIX') . $table_type . '_base';
				$table_name = C('DB_PREFIX') . $table_type . '_' . date("Y").$table_suffix;
				$sql = 'CREATE TABLE IF NOT EXISTS ' . $table_name . ' LIKE ' . $orgi_table_name;
				M()->query($sql);
			}
		}
	}
	
	public function moveOldOrder(){
		$time_before_3month = date('Y-m-d H:i:s',strtotime('-3 months'));
		echo $time_before_3month;
		$order_table = C('DB_PREFIX') . 'order';
		$order_backup_table = $order_table . '_backup';
		$where_condition = ' WHERE order_create_time < "'.$time_before_3month.'"';
		$move_sql = 'INSERT INTO ' . $order_backup_table . ' SELECT * FROM ' . $order_table . $where_condition;
		M()->query($move_sql);
		echo $move_sql;
		echo "<br>";
		if(!M()->getDbError()){
			$del_sql = 'DELETE FROM '.$order_table. $where_condition;
			M()->query($del_sql);
			echo $del_sql;
			echo "<br>";
		}
		
	}
}
