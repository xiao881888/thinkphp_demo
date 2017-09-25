<?php 
namespace Integral\Model;
use Think\Model;

class LogModel extends Model\AdvModel {

    protected $autoCheckFields = false;  //一定要关闭字段缓存，不然会报找不到表的错误

    protected $create_table_sql = '';

    protected $partition = array(
        'field' => 'uid',// 要分表的字段 通常数据会根据某个字段的值按照规则进行分表,我们这里按照用户的id进行分表
        'type' => 'id',// 分表的规则 包括id year mod md5 函数 和首字母，此处选择mod（求余）的方式
        'expr' => '10000',// 分表辅助表达式 可选 配合不同的分表规则，这个参数没有深入研究
        //'num' => '2',// 分表的数目 可选 实际分表的数量，在建表阶段就要确定好数量，后期不能增减表的数量
    );

    public function getTableNameObject($data){
        $data = empty($data) ? $_POST : $data;
        $table_name = $this->getPartitionTableName($data);
        return $this->table($table_name);
    }

    public function getTableNameString($data){
        $data = empty($data) ? $_POST : $data;
        return $this->getPartitionTableName($data);
    }

    protected function createTable($data){
        $is_create = $this->_getCreateTableList($data);
        if($is_create){
            return false;
        }
        $list = $this->getTableNameObject($data)->select();
        if($list === false){
            $create_table_sql = sprintf($this->create_table_sql,$this->getTableNameString($data));
            $model = new Model();
            $model->query($create_table_sql);
            $this->_addCreateTable($data);
        }
    }

    private function _getCreateTableList($data){
        $table_name = $this->getTableNameString($data);
        $redis = getRedis();
        return $redis->sContains($this->_getRedisKey(),$table_name);
    }

    private function _addCreateTable($data){
        $table_name = $this->getTableNameString($data);
        $redis = getRedis();
        return $redis->sAdd($this->_getRedisKey(),$table_name);
    }

    private function _getRedisKey(){
        return 'integral:create_table_list';
    }

}



