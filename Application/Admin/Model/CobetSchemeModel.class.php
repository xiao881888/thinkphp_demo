<?php

namespace Admin\Model;

use Think\Model;

class CobetSchemeModel extends Model{

    public function getSchemeListByIssueId($issue_id,$scheme_status){
        $where['issue_id'] = $issue_id;
        if($scheme_status){
            $where['scheme_status'] = array('IN',$scheme_status);
        }
        return $this->where($where)->select();
    }

    public function getSchemeListByCobetOrderIds($cobet_order_ids,$scheme_status){
        $where['cobet_order_id'] = array('IN',$cobet_order_ids);
        if($scheme_status){
            $where['scheme_status'] = array('IN',$scheme_status);
        }
        return $this->where($where)->select();
    }

}