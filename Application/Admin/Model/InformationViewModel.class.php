<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-3
 * @author tww <merry2014@vip.qq.com>
 */
class InformationViewModel extends Model{
    public function getInformationViewByDate($start_date, $end_date){
        $where = array();
        if($start_date && $end_date){
            $where['information_view_createtime'] = array('BETWEEN', array($start_date , $end_date));
        }else{
            if($start_date){
                $where['information_view_createtime'] = array('EGT', $start_date);
            }
            if($end_date){
                $where['information_view_createtime'] = array('ELT', $end_date);
            }
        }
        $field = array('information_view_id', 'information_view_information_id', 'information_view_information_cat_id','information_view_createtime');
        return $this->field($field)->where($where)->order('information_view_createtime ASC')->select();
    }

}