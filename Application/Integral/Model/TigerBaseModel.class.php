<?php 
namespace Integral\Model;
use Think\Model;

class TigerBaseModel extends Model {

    //　采用数组方式定义
    protected $connection = array();

    protected $tablePrefix = 'cp_';

    public function __construct(){
        $this->connection = C('TIGER_DB_CONN');
        parent::__construct();
    }

}



