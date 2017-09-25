<?php 
namespace Integral\Model;
use Think\Model;

class UserIntegralLogModel extends LogModel {

    protected $tableName = 'User_integral_log';

    protected $create_table_sql = "CREATE TABLE %s (
                                                        `uil_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                                        `uid` int(10) unsigned NOT NULL DEFAULT 0,
                                                        `uil_balance` int(10) NOT NULL DEFAULT 0 COMMENT '操作后剩余积分',
                                                        `uil_type` int(5) NOT NULL DEFAULT 0 COMMENT '积分变动类型 1:签到获得  2:下单获得  3:兑换红包 4:签到抽奖 5:手动增加 6：手动减少',
                                                        `uil_change_integral` int(10) NOT NULL DEFAULT 0 COMMENT '变动积分',
                                                        `uil_createtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                                                        `uil_create_date` varchar(24) NOT NULL DEFAULT '0000-00-00' COMMENT '积分获得日期',
                                                        `uil_extral_id` int(10) NOT NULL DEFAULT 0 COMMENT '写入红包ID等等',
                                                        `operator_id` int(10) NOT NULL DEFAULT 0 COMMENT '操作人员ID',
                                                      PRIMARY KEY (uil_id),
                                                      KEY uid (uid)
                                                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    public function insertUserIntegralLog($data){
        $this->createTable($data);
        $add_data['uid'] = $data['uid'];
        $add_data['uil_balance'] = empty($data['uil_balance']) ? 0 : $data['uil_balance'];
        $add_data['uil_type'] = empty($data['uil_type']) ? 0 : $data['uil_type'];    //积分变动类型 1:签到获得  2:下单获得  3:兑换红包
        $add_data['uil_createtime'] = getCurrentTime();
        $add_data['uil_create_date'] = getCurrentDate();
        $add_data['uil_change_integral'] = $data['uil_change_integral'];
        $add_data['uil_extral_id'] = isset($data['uil_extral_id'])?$data['uil_extral_id']:0;
        $add_data['operator_id'] = empty($data['operator_id']) ? 0 : $data['operator_id'];    //操作人员ID
        return $this->getTableNameObject($data)->add($add_data);
    }

    public function getUserIntegralList($data,$offset = 0, $limit = 10){
        $this->createTable($data);
        $where['uid'] = $data['uid'];
        return $this->getTableNameObject($data)->where($where)->order('uil_createtime DESC')->limit($offset, $limit)->select();
    }

    public function getTodaySignedIntegral($data){
        $this->createTable($data);
        $where['uid'] = $data['uid'];
        $where['uil_type'] = C('GAIN_INTEGRAL_TYPE.SIGN'); //签到获得
        $where['uil_create_date'] = getCurrentDate();
        return $this->getTableNameObject($data)->where($where)->getField('uil_change_integral');
    }

    //getExchangeGoodsCountById
    public function getExchangeGoodsCountById($data){
        $this->createTable($data);
        $where['uid'] = $data['uid'];
        $where['uil_extral_id'] = $data['goods_id']; //签到获得
        $where['uil_create_date'] = getCurrentDate();
        return $this->getTableNameObject($data)->where($where)->count();
    }



    

}



