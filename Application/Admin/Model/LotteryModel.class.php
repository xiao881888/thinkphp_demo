<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-2
 * @author tww <merry2014@vip.qq.com>
 */
class LotteryModel extends Model{
	
	public function getStatusFieldName(){
		return 'lottery_status';
	}
	
	public function getLikeFields(){
		return array('lottery_name');
	}
	
	public function getLotteryList(){
		$where = array();
		$where['lottery_status'] = C('Lottery.ENABLE');
		return $this->where($where)->select();
	}
	
	public function getLotteryMap(){
		$where = array();
		$where['lottery_status'] = C('Lottery.ENABLE');
		return $this->where($where)->getField('lottery_id,lottery_name');
	}

	public function getAllLottery(){
		$where = array();
		return $this->where($where)->getField('lottery_id,lottery_name');	
	}
	
	public function getLotteryName($lottery_id){
		$where = array();
		$where['lottery_id'] = $lottery_id;
		return $this->where($where)->getField('lottery_name');
	}
	
	public function getLotterys($ids=''){
		if (!empty($ids)) {
			$ids = is_array($ids) ? : explode(',', $ids);

			return $this->where(array('lottery_id'=>array('IN', $ids)))->select();
		} else {
			return $this->select();
		}
		
	}
	
	public function getOrderFields(){
		return 'lottery_id ASC';
	}

    public function getLotterySupportPlayTypes($lottery_id){
        $where = array();
        $where['lottery_id'] = $lottery_id;
        return $this->where($where)->getField('support_play_types');
    }
}