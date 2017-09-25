<?php
namespace Home\Model;
use Think\Model;

class JcOrderDetailModel extends JcModel {
    
	public function buildDetailData($orderId, $scheduleId, $betContent,$is_sure) {
		return array(
			'order_id' => $orderId,
			'schedule_id' => $scheduleId,
			'bet_content' => $betContent,
			'is_sure' => $is_sure,
		);
	} 
}