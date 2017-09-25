<?php 
namespace Home\Model;
use Think\Model;

class JcModel extends Model {
    
    public function insertAll($dataList,$options=array(),$replace=false) {
        if(empty($dataList)) {
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }
        
        $options =  $this->_parseOptions($options);
        
        // 写入数据到数据库
        $result = $this->executeInsertSql($dataList,$options,$replace);
        if(false !== $result ) {
            $insertId   =   $this->getLastInsID();
            if($insertId) {
                return $insertId;
            }
        }
        return $result;
        
    }
    
    public function executeInsertSql($datas,$options=array(),$replace=false) {
    	if(!is_array($datas[0])) return false;
    	$fields = array_keys($datas[0]);
    	$values  =  array();
    	foreach ($datas as $data){
    		$value   =  array();
    		foreach ($data as $key=>$val){
    			$val =  '\''. addslashes($val).'\'';
    			if(is_scalar($val)) { // 过滤非标量数据
    				$value[]   =  $val;
    			}
    		}
    		$values[]    = '('.implode(',', $value).')';
    	}
    	$sql   =  ($replace?'REPLACE':'INSERT').' INTO '.$options['table'].' ('.implode(',', $fields).') VALUES '.implode(',',$values);
    	return $this->db->execute($sql);
    }
}


?>