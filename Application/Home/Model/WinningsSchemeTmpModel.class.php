<?php
namespace Home\Model;
use Think\Model;
/**
 * @date 2015-5-9
 * @author tww <merry2014@vip.qq.com>
 */
class WinningsSchemeTmpModel extends Model{
	public function getWinningsList($issueId) {
		$condition = array('issue_id'=>$issueId);
		return $this->field('ws_bonus_name, ws_winning_num, ws_bonus_money')
		->where($condition)
		->order('ws_bonus_level ASC')
		->select();
	}
	
}