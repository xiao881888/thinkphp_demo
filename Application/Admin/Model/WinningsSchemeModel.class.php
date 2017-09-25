<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-18
 * @author tww <merry2014@vip.qq.com>
 */
class WinningsSchemeModel extends Model{
	public function getSchemeIssueIds($issue_ids){
		$where = array();
		$where['issue_id'] = array('IN', $issue_ids);
		return $this->where($where)->group('issue_id')->getField('issue_id', true);
	}
	
	public function getScheme($issue_ids){
		$where = array();
		$where['issue_id'] = $issue_ids;
		return $this->where($where)->select();
	}
	
	public function getMoneysMap($issue_id){
		$where = array();
		$where['issue_id'] = $issue_id;
		return $this->where($where)->getField('ws_id,ws_bonus_money');
	}
}