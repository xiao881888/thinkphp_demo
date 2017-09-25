<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-9
 * @author tww <merry2014@vip.qq.com>
 */
class LargeRechargeModel extends Model{

    public function getInfoById($lra_id){
        return $this->where(array('lra_id'=>$lra_id))->find();
    }

}