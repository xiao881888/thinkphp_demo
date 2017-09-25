<?php
namespace Content\Controller;
use Think\Controller;
/**
 * @date 2014-12-8
 * @author tww <merry2014@vip.qq.com>
 */
class CrontabController extends Controller{

	public function increaseYesterday(){
		$start_time = date('Y-m-d 00:00:00', strtotime('-1 day'));
		$end_time = date('Y-m-d 00:00:00');

		$sql = "SELECT uid, sum(order_total_amount) as amount FROM cp_order WHERE   order_create_time >= '{$start_time}' AND order_create_time < '{$end_time}' AND order_status in (3,8) GROUP BY uid";

		$data = M()->query($sql);


		$flag = false;

		M()->startTrans();

		foreach ($data as $row) {
			$record = array(
				'uid' => $row['uid'],
				'user_total_order' => $row['amount'],
				'user_modifytime' => date('Y-m-d H:i:s')
				);

			$is_exist = M('UserOrderStatics')->find($row['uid']);
			if ($is_exist) {
				$record['user_total_order'] = array('exp', 'user_total_order+'.$row['amount']);
				$result = M('UserOrderStatics')->save($record);
			} else {
				$result = M('UserOrderStatics')->add($record);
			}

			if ($result === false) {
				break;
			}
		}

		$flag = true;

		if ($flag) {
			M()->commit();

			echo $start_time.'~'.$end_time.' 期间订单更新完成';
		} else {
			echo $start_time.'~'.$end_time.' 期间订单更新失败！！！';
		}



	}

	public function staticAll(){
		set_time_limit(0);

		$today = date('Y-m-d 00:00:00');

		$sql1 = "SELECT uid, sum(order_total_amount) as amount FROM cp_order WHERE order_create_time < '{$today}' AND order_status in (3,8) GROUP BY uid";
		$data1 = M()->query($sql1);
		$data1 = reindexArr($data1, 'uid');


		$sql2 = "SELECT uid, sum(order_total_amount) as amount FROM cp_order_backup  WHERE order_status in (3,8) GROUP BY uid";
		$data2 = M()->query($sql2);
		$data2 = reindexArr($data2, 'uid');

		
		$data = array();
		foreach ($data1 as $uid => $row) {
			$data[$uid] = array(
				'uid' => $uid,
				'user_total_order' => intval($data1[$uid]['amount'] + $data2[$uid]['amount']),
				'user_modifytime' => date('Y-m-d H:i:s')
				);
		}


		$new_data = array_chunk($data, 100);

		$flag = false;

		M()->startTrans();

		$delete = M('UserOrderStatics')->where('1')->delete();

		if ($delete !== false) {
			foreach ($new_data as $arr) {
				$insert = M('UserOrderStatics')->addAll($arr);

				if (!$insert) {
					echo M('UserOrderStatics')->getDbError();
					break;
				}

			}

			$flag = true;
		} else {
			echo M('UserOrderStatics')->getDbError();
			echo M('UserOrderStatics')->_sql();
		}

		if ($flag) {
			M()->commit();
			echo 'static all success';
		} else {
			M()->rollback();
			echo 'static all fail';
		}

	}
}