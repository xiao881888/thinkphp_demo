<?php
namespace Crontab\Model;
use Think\Model;
class CobetSchemeModel extends Model {
    public function getOnGoingSchemeList(){
        $where['scheme_status'] = array('IN',array(COBET_SCHEME_STATUS_OF_NO_BEGIN_BOUGHT,COBET_SCHEME_STATUS_OF_ONGOING));
        return $this->where($where)->select();
    }

    public function getSchemeStatusById($id){
        $where['scheme_id'] = $id;
        return $this->where($where)->getField('scheme_status');
    }

    public function changeSchemeStatusById($id,$status){
        $where['scheme_id'] = $id;
        return $this->where($where)->save(array('scheme_status' => $status));
    }

    public function getUids(){
        return $this->distinct(true)->getField('uid',true);
    }

    public function updateHistoryData($scheme_ids,$history_data){
        $where['scheme_id'] = array('IN',$scheme_ids);
        $save_data['scheme_history_record'] = $history_data['history_record'];
        $save_data['scheme_history_data'] = json_encode($history_data);
        return $this->where($where)->save($save_data);
    }

    public function getSchemeIdsByUid($uid,$status){
        $where['uid'] = $uid;
        if($status){
            $where['scheme_status'] = array('IN',$status);
        }
        return $this->where($where)->getField('scheme_id',true);
    }

    public function getPrizeSchemeCountByIds($scheme_ids){
        $where['scheme_id'] = array('IN',$scheme_ids);
        $where['scheme_winning_status'] = array('IN',array(-1,1,2));
        $scheme_count = $this->where($where)->count();
        return ($scheme_count >= 10) ? 10 : $scheme_count;
    }

    public function getPrizeSchemeIdsByIds($scheme_ids,$limit = 10){
        $where['scheme_id'] = array('IN',$scheme_ids);
        $where['scheme_winning_status'] = array('IN',array(-1,1,2));
        return $this->where($where)->order('scheme_createtime DESC')->limit($limit)->getField('scheme_id',true);
    }

    public function getWinningSchemeCountByIds($scheme_ids){
        $where['scheme_id'] = array('IN',$scheme_ids);
        $where['scheme_winning_status'] = array('IN',array(1,2));
        return  $this->where($where)->count();
    }

    public function addSchemeBoughtUnit($scheme_id,$bought_unit){
        $where['scheme_id'] = $scheme_id;
        $add_status = $this->where($where)->setInc('scheme_bought_unit',$bought_unit);
        $save_status = $this->where($where)->save(array('scheme_bought_rate' => 1));
        return $add_status && $save_status;
    }
    
}