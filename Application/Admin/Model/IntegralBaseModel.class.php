<?php 
namespace Admin\Model;
use Think\Model;

class IntegralBaseModel extends Model {

    //　采用数组方式定义
    protected $connection = array();

    protected $tablePrefix = 'ti_';

    public function __construct(){
        $this->connection = C('INTEGRAL_DB_CONN');
        parent::__construct();
    }
}



