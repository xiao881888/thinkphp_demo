<?php 
namespace Integral\Model;
use Think\Model;

class UserDrawLogModel extends LogModel {

    protected $tableName = 'User_draw_log';

    protected $create_table_sql = "CREATE TABLE %s (
                                                         `udl_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                                        `uid` int(10) unsigned NOT NULL DEFAULT 0,
                                                        `dg_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '签到抽奖信息ID',
                                                        `dg_type` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '签到抽奖类型',
                                                        `udl_createtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '用户抽奖时间',
                                                        `udl_create_date` varchar(24) NOT NULL DEFAULT '0000-00-00' COMMENT '用户抽奖日期',
                                                        `udl_status` tinyint(3) NOT NULL DEFAULT 0 COMMENT '0:未领取  1:已领取',
                                                        `udl_extral_info` int(10) NOT NULL DEFAULT 0 COMMENT '红包ID,签到积分',
                                                        PRIMARY KEY (`udl_id`),
                                                        KEY `uid` (`uid`)
                                                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";


    public function getUserDrawLogInfo($data){
        $where['uid'] = $data['uid'];
        $where['udl_id'] = $data['id'];
        return $this->getTableNameObject($data)->where($where)->find();
    }

    public function getTodayUserDrawLogInfo($data){
        $where['uid'] = $data['uid'];
        $where['udl_create_date'] = getCurrentDate();
        return $this->getTableNameObject($data)->where($where)->find();
    }

    public function updateUserDrawStatus($data){
        $where['uid'] = $data['uid'];
        return $this->getTableNameObject($data)->where($where)->setField('udl_status',$data['status']);
    }

    public function getDrawGoodOfReceive($data){
        $where['udl_id'] = $data['id'];
        return $this->getTableNameObject($data)->where($where)->getField('udl_status');
    }

    public function insertUserDrawLog($data){
        $this->createTable($data);
        $add_data['uid'] = $data['uid'];
        $add_data['dg_id'] = $data['dg_id'];
        $add_data['dg_type'] = $data['dg_type'];
        $add_data['udl_createtime'] = getCurrentTime();
        $add_data['udl_create_date'] = getCurrentDate();
        $add_data['udl_status'] = empty($data['udl_status']) ? 0 : $data['udl_status'];    //操作人员ID
        $add_data['udl_extral_info'] = $data['udl_extral_info'];
        return $this->getTableNameObject($data)->add($add_data);
    }
    

}



