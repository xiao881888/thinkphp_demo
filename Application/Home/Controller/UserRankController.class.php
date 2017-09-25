<?php 
namespace Home\Controller;
use Home\Controller\GlobalController;

class UserRankController extends GlobalController {
   
    public function index() {
//         import('@.Util.Xhprof.Xhprof');
//         $obj_xh = new \Xhprof();
//         $obj_xh->startXhprofProfiler();
//         $obj_xh->registerShutdownFunctionForFinish('userRank','v2.0');
        
        $userAccountList = D('UserAccount')->order('user_account_balance desc')->select();
        $this->printSql();               
        $rankList = array();
        $winningMoney = $this->getWinningMoney();
        foreach($userAccountList as $userAccountInfo){
            $uid =  $userAccountInfo['uid'];
            $map['uid'] = $uid;
            $userInfo = D('User')->where($map)->find();
            $this->printSql();
            $rankItem = $userInfo;
            $rankItem['account_balance'] = $userAccountInfo['user_account_balance'];
            $rankItem['consume'] =$this->getSumConsume($uid);
            $rankItem['reward'] =$winningMoney[$uid];
            $rankList[$uid] = $rankItem;
        }

        $lotterys = range(701,705);
        $user_map = M('User')->getField('uid,user_telephone');
        $winning_map = array();
        foreach ($lotterys as $lottery){
        	$lottery_winning_money = $this->getWinningMoney($lottery);
        	$winning_map[$lottery] = $lottery_winning_money;

        }

        $lottery_map = M('Lottery')->getField('lottery_id,lottery_name');
        
        $this->assign('user_map', $user_map);
        $this->assign('lottery_map', $lottery_map);
       	$this->assign('winning_map', $winning_map);     
        $this->assign('from_date', $this->_from_date);  
        $this->assign('to_date', $this->_to_date);  
        $this->assign('rankList', $rankList);  
        $this->display();


    }
	
//     public function getGroupWinningMoney(){
//     	$new = array();
//     	$result = M('Order')->field('lottery_id,uid,sum(order_winnings_bonus) as money')->group('lottery_id ,uid')->select();

//     	foreach ($result as $v){
//     		$key = $v['lottery_id'].'_'.$v['uid'];
//     		$new[$key] = $v['money'];
//     	}
//     	return $new;
//     }
    
   
    public function getSumConsume($uid){
        $map['uid'] = $uid;
        $from_date = $this->_from_date;
        $to_date = $this->_to_date;
//         $map['order_create_time'] = array('between',array($from_date,$to_date));
		$map['order_status'] = 3;
        $sum_consume = D('Order')->where($map)->sum('order_total_amount');
        $this->printSql();
        return $sum_consume;

    }
    
    public function getWinningMoney($lottery_id = 0){
    	$start_time = '2015-05-21 13:10:00';
    	$this->assign('start_time', $start_time);
    	$where = array();
    	$where['order_create_time'] = array('EGT', $start_time);
    	if($lottery_id){
    		$where['lottery_id'] = $lottery_id;
    	}
    	$field =' sum(order_total_amount) pay ,sum(order_winnings_bonus) as winning,uid';
    	$result = M('Order')->where($where)->field($field)->group('uid')->order('winning DESC,pay DESC')->select();
    	$format_result = array();
    	foreach ($result as $k=>$v){
    		$format_result[$v['uid']] = $v;
    	}
		
    	$user_map = M('User')->getField('uid,user_telephone');  	 
    	foreach ($user_map as $uid=>$mobile){
    		if(!isset($format_result[$uid])){
    			$format_result[$uid] = '0.00';
    		}
    	}
    	return $format_result;
    }
    
    public function printSql(){
         if($_POST['debug']){
                       echo M()->_sql();
                        echo "<BR>"; 
         } 
    }

    
}

?>