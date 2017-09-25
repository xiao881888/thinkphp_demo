<?php 
namespace Home\Model;
use Think\Model;

class IssueLimitNoModel extends Model {
    const ENABLE_STATUS = 1;
    public function getIssueLimitNoListByLotteryId($lottery_id){
        $where['iln_status'] = self::ENABLE_STATUS;
        $where['lottery_id'] = $lottery_id;
        return $this->where($where)->select();
    }

}


?>