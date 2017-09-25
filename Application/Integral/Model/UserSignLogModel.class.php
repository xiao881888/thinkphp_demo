<?php 
namespace Integral\Model;
use Think\Model;

class UserSignLogModel extends LogModel {

    protected $tableName = 'User_sign_log';

    protected $create_table_sql = "CREATE TABLE %s (
                                                         `usl_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                                        `uid` int(10) unsigned NOT NULL DEFAULT 0,
                                                        `usl_last_signtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '用户上次签到时间',
                                                        `usl_signtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '用户签到时间',
                                                        `usl_signdate` varchar(24) NOT NULL DEFAULT '0000-00-00' COMMENT '签到日期',
                                                        `usl_sign_count` int(10) NOT NULL DEFAULT 0 COMMENT '本次连续签到天数',
                                                        PRIMARY KEY (`usl_id`),
                                                        KEY `uid` (`uid`)
                                                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    public function insertUserSignLog($data){
        $this->createTable($data);
        $add_data['uid'] = $data['uid'];
        $add_data['usl_last_signtime'] = $data['usl_last_signtime'];
        $add_data['usl_signtime'] = getCurrentTime();
        $add_data['usl_signdate'] = getCurrentDate();
        $add_data['usl_sign_count'] = $data['usl_sign_count'];
        return $this->getTableNameObject($data)->add($add_data);
    }
}



