<?php 
namespace Home\Model;
use Think\Model;

class LatestSchedulePrizeModel extends Model {
    
    public function getSchedulePrizeIds() {
    	$condition = array();
    	return $this->field('schedule_day, schedule_week, schedule_round_no, lottery_id')
                    ->where($condition)
                    ->order('lottery_id ASC')
    				->select();
    }
    
}


?>