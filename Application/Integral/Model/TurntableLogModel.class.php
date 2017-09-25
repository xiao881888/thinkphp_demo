<?php 
namespace Integral\Model;
use Think\Model;

class TurntableLogModel extends Model {

    public function getLogList($uid = 0,$limit = 99, $is_win = false)
    {
        $map = [];
        if ($uid){
            $map['ti_turntable_log.uid'] = $uid;
        }

        $map['log_addtime'] = array('gt',date('Y-m-d H:i:s',strtotime('-30 days')));

        if ($is_win) {
            $map['is_win'] = 1;
        }

        return $this->where($map)
            ->limit($limit)
            ->order('log_addtime desc')
            ->join('left JOIN ti_user ON ti_user.uid = ti_turntable_log.uid')
            ->select();
    }

    public function addLog($uid,$prize_info,$user_integral)
    {
        return $this->add([
            'uid' => $uid,
            'turntable_id' => $prize_info['turntable_id'],
            'turntable_name' => $prize_info['turntable_name'],
            'expense_integral' => C('LOTTO_INTEGRAL_VALUE'),
            'before_integral' => $user_integral['user_integral_balance'],
            'after_intergral' => $user_integral['user_integral_balance'] - C('LOTTO_INTEGRAL_VALUE'),
            'is_win' => $prize_info['turntable_type'] > 0 ? 1 : 0,
            'log_addtime' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateAfterIntegral($log_id,$uid)
    {
        $user_integral = D('UserIntegral')->getUserIntegralInfo($uid);
        return $this->where(['log_id' => $log_id])->save([
            'after_intergral' => $user_integral['user_integral_balance'],
        ]);
    }

}



