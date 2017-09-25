<?php
namespace Home\Model;
use Think\Model;
/**
 * @date 2014-11-27
 * @author tww <merry2014@vip.qq.com>
 */
class WinmessageModel extends Model{

    /**
     * 获取首页中奖信息
     * @return array
     */
    public function getIndexWinmessage(){
        $messageInfo = $this->_getWinmessage();
        $data = array();
        if(!empty($messageInfo)){
            foreach($messageInfo as $k => $v){
                $data[] = array(
                    'id' => emptyToStr($v['id']),
                    'lottery_id' => emptyToStr($v['lottery_id']),
                    'content' => emptyToStr($v['message'])
                );
            }
        }
        return $data;
    }

    private function _getWinmessage(){
        $where = array();
        $where['status'] = 1;
        return $this->where($where)
            ->order('updatetime DESC')
            ->getField('id,lottery_id, message');
    }
}