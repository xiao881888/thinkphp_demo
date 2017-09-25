<?php

namespace Crontab\Model;

use Think\Model;

class PushDeviceModel extends Model
{
    public function setTokenListInvaild($invaildTokenList = array())
    {
        //TODO  500条数据为临界点
        if (!empty($invaildTokenList)) {
            $where['pd_device_token'] = array('IN', $invaildTokenList);
            $data['pd_status'] = 0;
            return $this->where($where)->save($data);
        }
    }

    public function getPushDeviceTokenList()
    {
        return $this->distinct('pd_app_package')->getField('pd_app_package',true);
    }

}