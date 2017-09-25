<?php 
namespace Home\Model;
use Think\Model;

class LatestIssuePrizeModel extends Model {
	
    public function getIssuePrizeIds() {
    	$condition = array();
        return $this->where($condition)
                    ->order('lottery_id ASC')
        			->getField('issue_id', true);
    }
    
}


?>