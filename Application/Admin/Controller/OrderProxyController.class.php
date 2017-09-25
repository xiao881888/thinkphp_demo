<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-4
 * @author tww <merry2014@vip.qq.com>
 */
class OrderProxyController extends GlobalController{

	public function index(){
		$where = array();		
		$readonly_db_config_tiger = array(
		    'db_type'  => 'mysql',
		    'db_user'  => 'tigercai_server',
		    'db_pwd'   => 'e4huY8J7e4',
		    'db_host'  => 'rr-bp1y62h5vwc8o62yt.mysql.rds.aliyuncs.com',
		    'db_port'  => '3306',
		    'db_name'  => 'tigercai'
		);		
		$readonly_db_config_proxy = array(
		    'db_type'  => 'mysql',
		    'db_user'  => 'proxy_server',
		    'db_pwd'   => 'E8ij3E2Jka1p',
		    'db_host'  => 'rr-bp1y62h5vwc8o62yt.mysql.rds.aliyuncs.com',
		    'db_port'  => '3306',
		    'db_name'  => 'ticket-proxy'
		);
		
		$ticket_list = array();
		$order_id = I('order_id');
		$lottery_id = I('lottery_id');
		$schedule_day = I('schedule_day', '', 'trim');
		$schedule_round_no = I('schedule_round_no', '', 'trim');
		
		if(!empty($lottery_id) && !empty($schedule_day) && !empty($schedule_round_no)){
    	    if($lottery_id == 6) {
    	        $is_jc = true;
    			$lottery_id  = C('JCZQ');
    			$where['lottery_id'] = array('IN', $lottery_id);
    		} elseif ($lottery_id == 7) {
    	        $is_jc = true;
    			$lottery_id  = C('JCLQ');
    			$where['lottery_id'] = array('IN', $lottery_id);
    		}else{
    			$where['lottery_id'] = $lottery_id;
    		}
    		
    		if (($is_jc || isJc($lottery_id)) && $schedule_day && $schedule_round_no) {	    
    		    $condition['lottery_id'] = is_array($lottery_id) ? array('IN', $lottery_id) : $lottery_id;
    		    $condition['schedule_day'] = $schedule_day;    		    
    		    $round_nos = explode(",", $schedule_round_no);    		    
    		    $condition['schedule_round_no'] = array("IN", $round_nos);    		    
    		    $schedule_ids = M('JcSchedule', 'cp_', $readonly_db_config_tiger)->where($condition)->getField('schedule_id', true);
    		    $where['first_issue_id'] = array('IN', $schedule_ids);
    		}
    		
    		if($order_id){
    		    $where['order_id'] = $order_id;
    		}
    		if(I('order_status')){
    		    $where['order_status'] = I('order_status');
    		}
    		//$where['order_status'] = array('IN', array(ORDER_STATUS_PAYNOOUT, ORDER_STATUS_OUTING));
    		
    		$order_model = M('Order', 'cp_', $readonly_db_config_tiger);
    		$order_ids = $order_model->where($where)->getField('order_id', true);
    
            $proxy_where['tiger_order_id'] = array('IN', $order_ids);
        }elseif($order_id){
            $proxy_where['tiger_order_id'] = $order_id;
        }
        
        if(!empty($proxy_where)){
            $ticket_status = I('ticket_status');
            if($ticket_status){
                $proxy_where['ticket_status'] = $ticket_status;
            }
            $this->setLimit($proxy_where);
            $ticket_list = parent::index(M('CaidaoTicket', 'proxy_', $readonly_db_config_proxy), true);
        }
        //$ticket_ids = M()->db(1,"mysql://")->query("select caidao_ticket_id from proxy_caidao_ticket where tiger_order_id in(".$condition.")");
		
		$lottery_map['lottery_status'] = C('Lottery.ENABLE');        
        $this->assign('ticket_list', $ticket_list);
		$this->assign('lottery_list', M('Lottery', 'cp_', $readonly_db_config_tiger)->where($lottery_map)->getField('lottery_id,lottery_name'));
		$this->display();
	}

	public function export(){
		$_REQUEST['r'] = 1000000;
	    self::index();
	}
}