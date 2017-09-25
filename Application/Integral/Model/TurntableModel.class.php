<?php 
namespace Integral\Model;
use Think\Model;

class TurntableModel extends Model {

    public function getList($is_del = 0)
    {
        return $this->where(['is_del' => $is_del])->select();
    }

}



