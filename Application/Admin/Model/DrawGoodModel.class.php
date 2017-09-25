<?php 
namespace Admin\Model;
use Think\Model;

class DrawGoodModel extends IntegralBaseModel {

    protected $_auto = array(
        array('dg_createtime', 'getCurrentTime', self::MODEL_INSERT, 'function'),
        array('dg_modifytime', 'getCurrentTime', self::MODEL_UPDATE, 'function')
    );

    public function getStatusFieldName(){
        return 'dg_status';
    }

    public function getTotalWinningPrecent($id){
        $where = array('dg_id'=>array('neq',$id),'dg_status'=>1);
        return $this->where($where)->sum('dg_winning_percent');
    }

}



