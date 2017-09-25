<?php

namespace Home\Model;

use Think\Model;

class AlipayRecordModel extends Model{
	public function queryInfoByTradeNo($trade_no){
		$map['alipay_record_out_trade_no'] = $trade_no;
		return $this->where($map)->find();
	}
}



