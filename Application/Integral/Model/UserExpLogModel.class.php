<?php 
namespace Integral\Model;
use Think\Model;

class UserExpLogModel extends LogModel {

    protected $tableName = 'User_exp_log';

    protected $create_table_sql = "CREATE TABLE %s (
                                                         `uel_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                                        `uid` int(10) unsigned NOT NULL DEFAULT 0,
                                                        `uel_balance` int(10) NOT NULL DEFAULT 0 COMMENT '操作后剩余经验值',
                                                        `uel_createtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '用户获取时间',
                                                        `operator_id` int(10) NOT NULL DEFAULT 0 COMMENT '操作人员ID',
                                                        PRIMARY KEY (`uel_id`),
                                                        KEY `uid` (`uid`)
                                                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    public function insertUserExpLog($data){
        $this->createTable($data);
        $add_data['uid'] = $data['uid'];
        $add_data['uel_balance'] = empty($data['uel_balance']) ? 0 : $data['uel_balance'];
        $add_data['uel_createtime'] = getCurrentTime();
        $add_data['operator_id'] = empty($data['operator_id']) ? 0 : $data['operator_id'];    //操作人员ID
        return $this->getTableNameObject($data)->add($add_data);
    }
}



