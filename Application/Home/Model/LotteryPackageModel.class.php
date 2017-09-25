<?php 
namespace Home\Model;
use Think\Model;

class LotteryPackageModel extends Model {

    const ENABLE_STATUS = 1;
    public function getCategoryIds(){
        $where['lp_status'] = self::ENABLE_STATUS;
        return $this->distinct(true)->where($where)->getField('lp_category_id',true);
    }

    public function getPackagesByCategoryId($category_id,$lottery_id){
        $where['lp_category_id'] = $category_id;
        $where['lp_status'] = self::ENABLE_STATUS;
        $where['lottery_id'] = $lottery_id;
        return $this->where($where)->order('lp_issue_num')->select();
    }

    public function getPackagesInfoById($id){
        $where['lp_id'] = $id;
        $where['lp_status'] = self::ENABLE_STATUS;
        return $this->where($where)->find();
    }

    public function getPackagesPriceById($id){
        $where['lp_id'] = $id;
        return $this->where($where)->getField('lp_price');
    }

    public function getReduceAmount($id){
        $lp_info = $this->getPackagesInfoById($id);
        return bcsub($lp_info['lp_cost_price'],$lp_info['lp_price']);
    }

}


?>