<?php
namespace Crontab\Controller;

class UserStatisticsController extends UserStatisticsInitController{
	public function __construct(){
		parent::__construct();
	}
	public function index(){
		$s_date = date("Y-m-d",strtotime("-1 day"));
		
        $this->parseDateStatistics($s_date);

        $date_before_yesterday = date("Y-m-d",strtotime("-2 day"));
        
        $this->parseDateStatistics($date_before_yesterday);
        exit;
	}
	public function resetOneDate(){
	    $s_date = I('s_date');
	    $rows = $this->delOneDate($s_date);
	    if($rows != -1){
	        $this->parseDateStatistics($s_date);
	    }
	}
    private function delOneDate($date) {
        if(strtotime(date('Y-m-d', strtotime($date))) !== strtotime($date)){
            return -1;
        }
        $rows = M()->db(2)->table($this->table_user_statistics)->where('user_statistics_time="'.$date.'"')->delete();
        return $rows;
    }
    public function resetFirstOrderDate() {
        set_time_limit(0);
        $where['order_status'] = array('in','3,8');
        $order_first_list = M()->db(1)->table($this->table_order_backup)->field('uid,order_total_amount,order_create_time')->order('order_create_time')->group('uid')->where($where)->select();
		foreach ($order_first_list as $row){
		    $date = substr($row['order_create_time'],0,10);
		    $map['uid'] = $row['uid'];
		    $data = array(
		        'order_first_time'=>$date,
		        'order_first_amount'=>$row['order_total_amount']
		    );
		    M()->db(2)->table($this->table_user_statistics)->where($map)->save($data);
		}
    }
}
