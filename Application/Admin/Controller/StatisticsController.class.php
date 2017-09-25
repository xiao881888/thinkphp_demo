<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;

/**
 * @date 2014-12-7
 * 
 * @author tww <merry2014@vip.qq.com>
 */
class StatisticsController extends GlobalController{

	public function user(){
		$start_date = I('s_date', date('Y-m-d 00:00:00', strtotime('-14 days')));
		$end_date 	= I('e_date', date('Y-m-d 23:59:59'));

		$total_data = D('User')->countUserByDate($start_date, $end_date);
		$total_data = reindexArr($total_data, 'day');
		
		$category 	= $this->buildDayRang($start_date, $end_date);
		$series 	= array();

		$total_series = $this->buildHiCharSeries($category, $this->_combineArr($total_data, 'day', 'c'));
		$series[] = array('name' => '注册人数', 'data'=>$total_series);

		$app_os_data = D('User')->countUserByAppOs($start_date, $end_date);
		
		$android = $this->buildHiCharSeries($category, $this->_combineArr($app_os_data['android'], 'day', 'c'));
		$series[] = array('name' => 'Android注册人数', 'data' => $android);
		
		$ios = $this->buildHiCharSeries($category, $this->_combineArr($app_os_data['ios'], 'day', 'c'));
		$series[] = array('name' => 'iPhone注册人数', 'data' => $ios);
		
		$app_channel_data = D('User')->countUserByChannel($start_date, $end_date);
		
		$series = array_merge($series, $this->buildUserAppChannelData($app_channel_data, $category));

		$average 	= averageData($total_series);
		$sum 		= sumData($total_series);
		
		$this->assign('category', $category);
		$this->assign('series', $series);
		$this->assign('start_date', $start_date);
		$this->assign('end_date', $end_date);
		$this->assign('average', $average);
		$this->assign('sum', $sum);
		$this->display();
			
	}

    public function userExport(){
        $this->_useReadDb();
        $start_date = I('s_date', date('Y-m-d 00:00:00', strtotime('-14 days')));
        $end_date 	= I('e_date', date('Y-m-d 23:59:59'));
        $channel_list = array();
        $channel_list[] = 'all';
        $total_data = D('User')->countUserByDate($start_date, $end_date);
        foreach($total_data as $value){
            $data_list[$value['day']]['all'] = $value['c'];
        }
        $channel_list[] = 'ios';
        $channel_list[] = 'android';
        $app_os_data = D('User')->countUserByAppOs($start_date, $end_date);
        foreach($app_os_data['ios'] as $value){
            $data_list[$value['day']]['ios'] = $value['c'];
        }
        foreach($app_os_data['android'] as $value){
            $data_list[$value['day']]['android'] = $value['c'];
        }
        $app_channel_data = D('User')->countUserByChannel($start_date, $end_date);
        foreach($app_channel_data as $value){
            $data_list[$value['day']][$value['user_app_os'].'-'.$value['user_app_channel_id']] = $value['c'];
            $channel_list[] = $value['user_app_os'].'-'.$value['user_app_channel_id'];
        }
        $channel_list = array_unique($channel_list);
        $this->_userExport($data_list,$channel_list,'用户报表');
    }
    private function _userExport($list,$channel_list,$filename='_'){
        $title[0] = '日期';
        foreach ($channel_list as $channel_id){
            $title[] = $this->_getTitleByChannelId($channel_id);
        }

        $data = array();
        $i = 0;$j=1;
        foreach($list as $date =>$info){
            $data[$i][0] = $date;
            foreach($channel_list as $channel_id){
                $data[$i][$j] = empty($info[$channel_id]) ? 0: $info[$channel_id];
                $j++;
            }
            $i++;
        }

        $this->exportExcel($data,$title,$filename);
    }

    private function _getTitleByChannelId($channel_id){
        if($channel_id == 'ios'){
            return 'IOS注册';
        }elseif($channel_id == 'android'){
            return '安卓注册';
        }if($channel_id == 0){
            return '全部';
        }else{
            $channels = getUserAppChannels();
            $channel_list = explode('-',$channel_id);
            $channel_os = $channel_list[0];
            $channel_name = $channel_list[1];
            if ($channel_os == '0') {
                return '前置注册';
            }
            $platform_filter_channels = $channels[$channel_os];
            if (array_key_exists($channel_name, $platform_filter_channels)) {
                return $platform_filter_channels[$channel_name]['app_name'];
            }
            return $channel_name;
        }
    }

	public function recharge(){
		$start_date = I('s_date', date('Y-m-d', strtotime('-7 days')));
		$end_date = I('e_date', date('Y-m-d'));

		$data = D('Recharge')->sumRechargeMoneyByDate($start_date.' 00:00:00', $end_date.' 23:59:59');
		$data = reindexArr($data, 'day');

		$category = $series = array();
		$days = (strtotime($end_date) - strtotime($start_date))/(3600*24);
		for ($i=0; $i <= $days; $i++) { 
			$day = date('Y-m-d', strtotime("+".$i."days", strtotime($start_date)));
			$category[] = $day;
			$series[] = isset($data[$day]) ? floatval($data[$day]['s']) : 0;
		}

		$average = averageData($series);
		$sum = sumData($series);

		$series = array(array('name' => '新增充值', 'data'=>$series));

		$this->assign('category', $category);
		$this->assign('series', $series);
		$this->assign('start_date', $start_date);
		$this->assign('end_date', $end_date);
		$this->assign('average', $average);
		$this->assign('sum', $sum);
		$this->display();
	}
	
	public function withdraw(){
		$start_date = I('s_date', date('Y-m-d', strtotime('-7 days')));
		$end_date = I('e_date', date('Y-m-d'));

		$data = D('Withdraw')->sumWithdrawMoneyByDate($start_date.' 00:00:00', $end_date.' 23:59:59');
		$data = reindexArr($data, 'day');

		$category = $series = array();
		$days = (strtotime($end_date) - strtotime($start_date))/(3600*24);
		for ($i=0; $i <= $days; $i++) { 
			$day = date('Y-m-d', strtotime("+".$i."days", strtotime($start_date)));
			$category[] = $day;
			$series[] = isset($data[$day]) ? floatval($data[$day]['s']) : 0;
		}

		$average = averageData($series);
		$sum = sumData($series);

		$series = array(array('name' => '新增提现申请', 'data'=>$series));

		$this->assign('category', $category);
		$this->assign('series', $series);
		$this->assign('start_date', $start_date);
		$this->assign('end_date', $end_date);
		$this->assign('average', $average);
		$this->assign('sum', $sum);
		$this->display();
	}
	
	public function winners(){
		$start_date = I('s_date', date('Y-m-d', strtotime('-7 days')));
		$end_date = I('e_date', date('Y-m-d'));

		$win_data = D('Order')->sumWinnerOrderByDate($start_date.' 00:00:00', $end_date.' 23:59:59');
		$win_data = reindexArr($win_data, 'day');

		$map = array(
			'order_status' => 3,
			'order_create_time' => array('between', array($start_date.' 00:00:00', $end_date.' 23:59:59')),
			'order_plus_award_amount' => array('gt', 0)
			);

		$order_award_data = D('Order')->where($map)->group('d')->field('DATE_FORMAT(order_create_time,"%Y-%m-%d") d, sum(order_plus_award_amount) s')->select();
		$order_award_data = reindexArr($order_award_data, 'd');

		$category = $series = array();
		$days = (strtotime($end_date) - strtotime($start_date))/(3600*24);
		for ($i=0; $i <= $days; $i++) { 
			$day = date('Y-m-d', strtotime("+".$i."days", strtotime($start_date)));
			$category[] = $day;
			$serie1[] = isset($win_data[$day]) ? floatval($win_data[$day]['s']) : 0;
			$serie2[] = isset($order_award_data[$day]) ? floatval($order_award_data[$day]['s']) : 0;
		}

		$average = averageData($serie1);
		$sum = sumData($serie1);

		$series = array(
			array('name' => '中奖金额', 'data' => $serie1),
			array('name' => '加奖金额', 'data' => $serie2)
			);

		$this->assign('category', $category);
		$this->assign('series', $series);
		$this->assign('start_date', $start_date);
		$this->assign('end_date', $end_date);
		$this->assign('average', $average);
		$this->assign('sum', $sum);
		$this->display();
	}
	

	
	public function order(){
		$start_date = I('s_date', date('Y-m-d', strtotime('-7 days')));
		$end_date = I('e_date', date('Y-m-d'));

		//todo:根据当前开售彩种显示相应的统计，没有开售的则不显示
		$lottery_infos 	= D('Lottery')->getLotterys('1,2,3,4,5,6,7,8,18,19,20,21,22');
		$category = getDaysRange(date('Y-m-d', strtotime($start_date)), date('Y-m-d', strtotime($end_date)));
		
		$series = array();
		foreach ($lottery_infos as $lottery){
			$temp = array();
			$temp['name'] = $lottery['lottery_name'];
			foreach ($category as $day){
				$temp['data'][$day] = 0;
			}
			$temp['visible'] = false;
			$series[$lottery['lottery_id']] = $temp;
		}

		$series[0] = $series[array_rand($series)];
		$series[0]['name'] = '全部彩种';
		$series[0]['visible'] = true;
		
		$order_infos = D('Order')->getSellOut($start_date.' 00:00:00', $end_date.' 23:59:59');
		foreach ($order_infos as $order_info){
			$lottery_hash = array(
				'601' => '6',
				'602' => '6',
				'603' => '6',
				'604' => '6',
				'605' => '6',
				'606' => '6',
				'701' => '7',
				'702' => '7',
				'703' => '7',
				'704' => '7',
				'705' => '7',
				);
			$y_m_d = substr($order_info['order_create_time'], 0 ,10);
			if (array_key_exists($order_info['lottery_id'], $lottery_hash)) {
				$add_to_lottery_id = $lottery_hash[$order_info['lottery_id']];
			} else {
				$add_to_lottery_id = $order_info['lottery_id'];
			}
			$series[$add_to_lottery_id]['data'][$y_m_d] += $order_info['order_total_amount'];
			$series[0]['data'][$y_m_d] += $order_info['order_total_amount'];
		}
		$average = averageData($series[0]['data']);
		$sum = sumData($series[0]['data']);

		foreach ($series as $key=>$v){
			$series[$key]['data'] = array_values($v['data']);
		}
		$series = array_values($series);
		$category = array_unique($category);

		$this->assign('s_date', date('Y-m-d',strtotime($start_date)));
		$this->assign('e_date', date('Y-m-d',strtotime($end_date)));
		$this->assign('category', json_encode($category));
		$this->assign('series', json_encode($series));
		$this->assign('average', $average);
		$this->assign('sum', $sum);
		$this->display();
		
	}


    public function orderExport(){
        $this->_useReadDb();
        $start_date = I('s_date', date('Y-m-d', strtotime('-7 days')));
        $end_date = I('e_date', date('Y-m-d'));
        $lottery_infos 	= D('Lottery')->getLotterys('1,2,3,4,5,6,7,8,18,19,20,21,22');
        $category = getDaysRange(date('Y-m-d', strtotime($start_date)), date('Y-m-d', strtotime($end_date)));
        $data_list = array();
        $lottery_list = array(0);
        foreach ($lottery_infos as $lottery){
            $lottery_list[] = $lottery['lottery_id'];
        }
        $order_infos = D('Order')->getSellOut($start_date.' 00:00:00', $end_date.' 23:59:59');
        foreach ($order_infos as $order_info){
            $lottery_hash = array(
                '601' => '6',
                '602' => '6',
                '603' => '6',
                '604' => '6',
                '605' => '6',
                '606' => '6',
                '701' => '7',
                '702' => '7',
                '703' => '7',
                '704' => '7',
                '705' => '7',
            );
            $y_m_d = substr($order_info['order_create_time'], 0 ,10);
            if (array_key_exists($order_info['lottery_id'], $lottery_hash)) {
                $add_to_lottery_id = $lottery_hash[$order_info['lottery_id']];
            } else {
                $add_to_lottery_id = $order_info['lottery_id'];
            }
            $data_list[$y_m_d][$add_to_lottery_id] += $order_info['order_total_amount'];
            $data_list[$y_m_d][0] += $order_info['order_total_amount'];
        }
        $this->_orderExport($data_list,$lottery_list,'订单报表');
    }
    private function _orderExport($list,$lottery_list,$filename='_'){
        $title[0] = '日期';
        foreach ($lottery_list as $lottery_id){
            $title[] = $this->_getTitleByLotteryId($lottery_id);
        }
        $data = array();
        $i = 0;$j=1;
        foreach($list as $date =>$info){
            $data[$i][0] = $date;

            foreach($lottery_list as $lottery_id){
                $data[$i][$j] = empty($info[$lottery_id]) ? 0 : $info[$lottery_id];
                $j++;
            }
            $i++;
        }
        $this->exportExcel($data,$title,$filename);
    }

    private function _getTitleByLotteryId($lottery_id){
        if($lottery_id == 0){
            return '全部';
        }elseif($lottery_id == 6){
            return '竞足';
        }elseif($lottery_id == 7){
            return '竞篮';
        }else{
            return D('Home/Lottery')->getLotteryNameById($lottery_id);
        }
    }

    public function lotteryStatistics(){
        $this->_useReadDb();
        $s_date = I('s_date',date('Y-m-d 00:00:00', strtotime('-7 days')));
        $e_date = I('e_date',date('Y-m-d 23:59:59'));

        if($s_date){
            $o_where['order_create_time'] = array('egt',$s_date);
        }
        if($e_date){
            $o_where['order_create_time'] = $o_where['order_create_time'] ? array(array('egt',$s_date),array('elt',$e_date)) : array('elt',$e_date);
        }

        $order_list = $this->getOrderListGroupByLotteryId($o_where);

        $this->assign('order_list', $order_list);
        $this->assign('search_time', array('s_date'=>$s_date,'e_date'=>$e_date));
        $this->display();
    }

    public function getOrderListGroupByLotteryId($o_where){
        $order_list = M('Order')->where($o_where)
            ->field('lottery_id, sum(order_total_amount - order_refund_amount) order_amount, count(*) order_count')->group('lottery_id')->select();
        $order_list = reindexArr($order_list, 'lottery_id');
        foreach($order_list as $key => $order){
            $order_list[$key]['lottery_name'] = D('Home/Lottery')->getLotteryNameById($order['lottery_id']);
            $u_where = $o_where;
            $u_where['lottery_id'] = $order['lottery_id'];
            $user_ids = M('Order')->where($u_where)->group('uid')->getField('uid',true);
            $user_count = count($user_ids);
            $order_list[$key]['user_count'] = $user_count;
        }

        $order_list = $this->_rebuildOrderList($order_list);
        return $order_list;
    }

    private function _rebuildOrderList($order_list){
        $new_order_list = array();
        foreach ($order_list as $lottery_id => $order){
            if(isJz($lottery_id)){
                $new_lottery_id = 6;
                $new_lottery_name = '竞彩足球';
                $order_amount = $new_order_list[$new_lottery_id]['order_amount'] + $order['order_amount'];
                $order_count = $new_order_list[$new_lottery_id]['order_count'] + $order['order_count'];
                $user_count = $new_order_list[$new_lottery_id]['user_count'] + $order['user_count'];
            }elseif(isJl($lottery_id)){
                $new_lottery_id = 7;
                $new_lottery_name = '竞彩篮球';
                $order_amount = $new_order_list[$new_lottery_id]['order_amount'] + $order['order_amount'];
                $order_count = $new_order_list[$new_lottery_id]['order_count'] + $order['order_count'];
                $user_count = $new_order_list[$new_lottery_id]['user_count'] + $order['user_count'];
            }else{
                $new_lottery_id = $order['lottery_id'];
                $new_lottery_name = $order['lottery_name'];
                $order_amount = $order['order_amount'];
                $order_count = $order['order_count'];
                $user_count = $order['user_count'];
            }

            $new_order_list[$new_lottery_id] = array(
                'lottery_id' => $new_lottery_id,
                'lottery_name' => $new_lottery_name,
                'order_amount' => $order_amount,
                'order_count' => $order_count,
                'user_count' => $user_count,
            );
        }
        return $new_order_list;
    }


    public function coupon(){
        $this->_useReadDb();
        $s_date = I('s_date',date('Y-m-d 00:00:00', strtotime('-7 days')));
        $e_date = I('e_date',date('Y-m-d 23:59:59'));

        if($s_date){
            $c_where['user_coupon_create_time'] = array('egt',$s_date);
        }
        if($e_date){
            $c_where['user_coupon_create_time'] = !empty($s_date) ? array(array('egt',$s_date),array('elt',$e_date)) : array('elt',$e_date);
        }

        $coupon_list = $this->_getCouponList($c_where);
        $this->assign('coupon_list', $coupon_list);
        $this->assign('search_time', array('s_date'=>$s_date,'e_date'=>$e_date));
        $this->display();
    }

    private function _getCouponList($c_where){
        $coupon_list = array();
        $user_coupon_list = M('UserCoupon')->alias('uc')->join('cp_coupon c ON c.coupon_id = uc.coupon_id')->where($c_where)->field('uc.coupon_id coupon_id,coupon_name,count(*) count,sum(user_coupon_amount) user_coupon_amount,sum(user_coupon_balance) user_coupon_balance')->group('coupon_id')->select();
        foreach($user_coupon_list as $coupon_data){
            $m_where = $c_where;
            $m_where['coupon_id'] = $coupon_data['coupon_id'];
            $coupon_mem_list = M('UserCoupon')->where($m_where)->group('uid')->getField('uid',true);
            $coupon_list[] = array(
                'coupon_name' => $coupon_data['coupon_name'],
                'coupon_amount' => $coupon_data['user_coupon_amount'],
                'coupon_balance' => $coupon_data['user_coupon_balance'],
                'coupon_count' => $coupon_data['count'],
                'coupon_mem_count' => count($coupon_mem_list),
            );
        }
        return $coupon_list;
    }

    public function couponExport(){
        $this->_useReadDb();
        $s_date = I('s_date',date('Y-m-d', strtotime('-7 days')));
        $e_date = I('e_date',date('Y-m-d'));
        if($s_date){
            $c_where['user_coupon_create_time'] = array('egt',$s_date);
        }
        if($e_date){
            $c_where['user_coupon_create_time'] = $c_where['coupon_create_time'] ? array(array('egt',$s_date),array('elt',$e_date)) : array('elt',$e_date);
        }

        $coupon_list = $this->_getCouponList($c_where);
        $this->_couponExport($coupon_list,'红包报表');
    }
    private function _couponExport($list,$filename='_'){
        $title=array('红包类型','兑换数量','兑换人数','金额','余额');
        $data = array();
        foreach($list as $key => $coupon){
            $data[$key][] = $coupon['coupon_name'];
            $data[$key][] = $coupon['coupon_count'];
            $data[$key][] = $coupon['coupon_mem_count'];
            $data[$key][] = $coupon['coupon_amount'];
            $data[$key][] = $coupon['coupon_balance'];
        }
        $this->exportExcel($data,$title,$filename);
    }


    public function orderRegionDetail(){
        $this->_useReadDb();
        $s_date = I('s_date',date('Y-m-d', strtotime('-7 days')));
        $e_date = I('e_date',date('Y-m-d'));

        if($s_date){
            $o_where['order_create_time'] = array('egt',$s_date);
        }
        if($e_date){
            $o_where['order_create_time'] = $o_where['order_create_time'] ? array(array('egt',$s_date),array('elt',$e_date)) : array('elt',$e_date);
        }

        $lottery_id = I('lottery_id');
        if($lottery_id == 6){
            $o_where['lottery_id'] = array('IN',C('JCZQ'));
            $lottery_name = '竞彩足球';
        }elseif($lottery_id == 7){
            $o_where['lottery_id'] = array('IN',C('JCLQ'));
            $lottery_name = '竞彩篮球';
        }else{
            $o_where['lottery_id'] = $lottery_id;
            $lottery_name = D('Home/Lottery')->getLotteryNameById($lottery_id);
        }
        $order_region_list = $this->getOrderRegionListByLotteryId($o_where);
        $this->assign('lottery_name', $lottery_name);
        $this->assign('lottery_id', $lottery_id);
        $this->assign('order_region_list', $order_region_list);
        $this->assign('search_time', array('s_date'=>$s_date,'e_date'=>$e_date));
        $this->display();
    }

    public function getOrderRegionListByLotteryId($o_where){
        $order_region_list = array();
        for($i=1;$i<=9;$i++){
            $amount_region = $this->_getAmountRegion($i);
            $order_region_list[$i]['title'] = $amount_region['title'];
            $o_where['_string'] = 'order_total_amount - order_refund_amount > '.$amount_region['min'] . ' AND order_total_amount - order_refund_amount <= '.$amount_region['max'];
            $order_info = M('Order')->where($o_where)
                ->field('lottery_id, sum(order_total_amount - order_refund_amount) order_amount, count(*) order_count')->find();
            $order_amount = $order_info['order_amount'];
            $order_count = $order_info['order_count'];
            $user_ids = M('Order')->where($o_where)->group('uid')->getField('uid',true);
            $user_count = count($user_ids);
            $order_region_list[$i]['order_amount'] = $order_amount;
            $order_region_list[$i]['order_count'] = $order_count;
            $order_region_list[$i]['user_count'] = $user_count;
        }
        return $order_region_list;
    }

    private function _getAmountRegion($index){
        switch ($index){
            case 1:
                $data['min'] = 0;
                $data['max'] = 10;
                $data['title'] = '2-10 元';
                break;
            case 2:
                $data['min'] = 10;
                $data['max'] = 100;
                $data['title'] = '10.1-100 元';
                break;
            case 3:
                $data['min'] = 100;
                $data['max'] = 500;
                $data['title'] = '100.1-500 元';
                break;
            case 4:
                $data['min'] = 500;
                $data['max'] = 1000;
                $data['title'] = '500.1-1000 元';
                break;
            case 5:
                $data['min'] = 1000;
                $data['max'] = 2000;
                $data['title'] = '1000.1-2000 元';
                break;
            case 6:
                $data['min'] = 2000;
                $data['max'] = 3000;
                $data['title'] = '2000.1-3000 元';
                break;
            case 7:
                $data['min'] = 3000;
                $data['max'] = 5000;
                $data['title'] = '3000.1-5000 元';
                break;
            case 8:
                $data['min'] = 5000;
                $data['max'] = 10000;
                $data['title'] = '5000.1-10000 元';
                break;
            case 9:
                $data['min'] = 10000;
                $data['max'] = 99999999;
                $data['title'] = '10000.1 元以上';
                break;
        }
        return $data;
    }

    public function orderChannelDetail(){
        $this->_useReadDb();
        $s_date = I('s_date');
        $e_date = I('e_date');

        if($s_date){
            $o_where['order_create_time'] = array('egt',$s_date);
        }
        if($e_date){
            $o_where['order_create_time'] = $o_where['order_create_time'] ? array(array('egt',$s_date),array('elt',$e_date)) : array('elt',$e_date);
        }

        if($s_date){
            $r_where['user_register_time'] = array('egt',$s_date);
            $o_where['user_statistics_time'] = array('egt',$s_date);
            $n_where['order_first_time'] = array('egt',$s_date);
        }
        if($e_date){
            $r_where['user_register_time'] = $r_where['user_register_time'] ? array(array('egt',$s_date),array('elt',$e_date.' 23:59:59')) : array('elt',$e_date.' 23:59:59');
            $o_where['user_statistics_time'] = $o_where['user_statistics_time'] ? array(array('egt',$s_date),array('elt',$e_date)) : array('elt',$e_date);
            $n_where['order_first_time'] = $n_where['order_first_time'] ? array(array('egt',$s_date),array('elt',$e_date)) : array('elt',$e_date);
        }
        //$o_where['order_first_amount'] = array('gt',0);
        //$n_where['order_first_amount'] = array('gt',0);

        //字段：c 注册总数，a 渠道，总充值金额，总消费金额，总用户余额，ARPU值 总消费/总用户
        $channel_list = $this->getChannelList();
        //用户注册数
        $register_count = $this->getRegisterCount($r_where);
        //充值、消费金额
        $statistics_count = $this->getStatisticsCount($o_where);
        //消费用户数
        $consume_sum = $this->getConsumeSum($o_where);


        $lottery_id = I('lottery_id');
        if($lottery_id == 6){
            $o_where['lottery_id'] = array('IN',C('JCZQ'));
            $lottery_name = '竞彩足球';
        }elseif($lottery_id == 7){
            $o_where['lottery_id'] = array('IN',C('JCLQ'));
            $lottery_name = '竞彩篮球';
        }else{
            $o_where['lottery_id'] = $lottery_id;
            $lottery_name = D('Home/Lottery')->getLotteryNameById($lottery_id);
        }
        $order_region_list = $this->getOrderRegionListByLotteryId($o_where);
        $this->assign('lottery_name', $lottery_name);
        $this->assign('order_region_list', $order_region_list);
        $this->assign('search_time', array('s_date'=>$s_date,'e_date'=>$e_date));
        $this->display();
    }

    public function channelDetail(){
        $this->_useReadDb();
        $app_channel_string = I('app_channel_string');

        $channel_arr 	= explode('__', $app_channel_string);
        $app_channel_id 	= $channel_arr[0];
        $app_os   	= $channel_arr[1];

        $channel_name = $this->_getChannelName($app_channel_id,$app_os);

        $channel_detail = array();
        for($i=1;$i<=30;$i++){
            $day = date('Y-m-d',time() - $i*24*60*60);
            $channel_detail[]['title'] = $day;
            $r_where['user_register_time'] = array(array('egt',$day),array('elt',$day.' 23:59:59'));
            $r_where['user_app_channel_id'] = $app_channel_id;
            $r_where['user_app_os'] = $app_os;
            $o_where['user_statistics_time'] = array(array('egt',$day),array('elt',$day.' 23:59:59'));
            $o_where['user_app_channel_id'] = $app_channel_id;
            $o_where['user_app_os'] = $app_os;
            $n_where['order_first_time'] = array(array('egt',$day),array('elt',$day.' 23:59:59'));
            $n_where['user_app_channel_id'] = $app_channel_id;
            $n_where['user_app_os'] = $app_os;
            $c_where['user_app_channel_id'] = $app_channel_id;
            $c_where['user_app_os'] = $app_os;

            $channel_detail[]['channel_detail'] = $this->getChannelDetail($c_where);
            //用户注册数
            $channel_detail[]['register_count'] = $this->getDetailRegisterCount($r_where);
            //充值、消费金额
            $channel_detail[]['statistics_count'] = $this->getDetailStatisticsCount($o_where);
            //消费用户数
            $channel_detail[]['consume_sum'] = $this->getDetailConsumeSum($o_where);
            //新增消费用户数
            $channel_detail[]['consume_sum_new'] = $this->getNewConsumeSum($n_where);
        }
        $this->assign('channel_detail', $channel_detail);
        $this->assign('channel_name', $channel_name);
        $this->display();
    }

    private function _getChannelName($channel_name,$channel_os){
        $channels = getUserAppChannels();
        if ($channel_os == '0') {
            return '前置注册';
        }

        $platform_filter_channels = $channels[$channel_os];

        if (array_key_exists($channel_name, $platform_filter_channels)) {
            return $platform_filter_channels[$channel_name]['app_name'];
        }

        return $channel_name;
    }

    public function getChannelDetail($c_where){
        $channel_detail = M('User')->field('count(1) count, sum(user_account_recharge_amount) recharge, sum(user_account_consume_amount) consume, sum(user_account_balance) balance')
                                    ->join('cp_user_account b ON cp_user.uid = b.uid','left')
                                    ->where($c_where)->find();
        return $channel_detail;
    }
    public function getDetailRegisterCount($r_where){
        return M('User')->where($r_where)->count();
    }
    public function getDetailStatisticsCount($o_where){
        return M('UserStatistics')->field('sum(recharge_amount) recharge,sum(order_amount) consume')
                                ->where($o_where)->find();
    }
    public function getDetailConsumeSum($o_where){
        $sql_counsume_user = M('UserStatistics')->field('distinct uid')->where($o_where)->select(false);
        return M()->field('count(1) count,concat(user_app_channel_id, "__", user_app_os) a')->table($sql_counsume_user.' tmp')
                            ->join('cp_user on cp_user.uid=tmp.uid','left')->find();
    }

    private function _getYqUids(){
        return  M('User')->alias('u')->join('cp_yq_user yq ON yq.user_telephone = u.user_telephone')->getField('uid',true);
    }

    public function YqChannel(){
        $this->_useReadDb();
        $s_date = I('s_date');
        $e_date = I('e_date');

        if($s_date){
            $r_where['user_register_time'] = array('egt',$s_date);
            $o_where['user_statistics_time'] = array('egt',$s_date);
            $n_where['order_first_time'] = array('egt',$s_date);
        }
        if($e_date){
            $r_where['user_register_time'] = $r_where['user_register_time'] ? array(array('egt',$s_date),array('elt',$e_date.' 23:59:59')) : array('elt',$e_date.' 23:59:59');
            $o_where['user_statistics_time'] = $o_where['user_statistics_time'] ? array(array('egt',$s_date),array('elt',$e_date)) : array('elt',$e_date);
            $n_where['order_first_time'] = $n_where['order_first_time'] ? array(array('egt',$s_date),array('elt',$e_date)) : array('elt',$e_date);
        }

        $uids = $this->_getYqUids();
        $r_where['uid'] = array('IN',$uids);
        $o_where['uid'] = array('IN',$uids);
        $n_where['uid'] = array('IN',$uids);



        //$o_where['order_first_amount'] = array('gt',0);
        //$n_where['order_first_amount'] = array('gt',0);

        //字段：c 注册总数，a 渠道，总充值金额，总消费金额，总用户余额，ARPU值 总消费/总用户
        $channel_list = $this->getYqChannelList($uids);
        //用户注册数
        $register_count = $this->getYqRegisterCount($r_where);
        //充值、消费金额
        $statistics_count = $this->getYqStatisticsCount($o_where);
        //消费用户数
        $consume_sum = $this->getYqConsumeSum($o_where);
        //新增消费用户数
        if($s_date){
            $consume_sum_new = $this->getYqNewConsumeSum($n_where);
        }else{
            $consume_sum_new = $consume_sum;
        }
        $yq_data = array();
        $yq_data[0]['channel_name'] = '赢球';
        $yq_data[0]['count'] = $channel_list['count'];
        $yq_data[0]['recharge'] = $channel_list['recharge'];
        $yq_data[0]['consume'] = $channel_list['consume'];
        $yq_data[0]['balance'] = $channel_list['balance'];
        $yq_data[0]['register_count'] = $register_count;
        $yq_data[0]['recharge_count'] = $statistics_count['recharge'];
        $yq_data[0]['consume_count'] = $statistics_count['consume'];
        $yq_data[0]['consume_sum'] = $consume_sum;
        $yq_data[0]['consume_sum_new'] = $consume_sum_new;
        ApiLog('$yq_data:'.print_r($yq_data,true),'testlifeng000');

        $this->assign('search_button', $this->channelButtons());
        $this->assign('yq_data', $yq_data);
        $this->assign('search_time', array('s_date'=>$s_date,'e_date'=>$e_date));
        $this->display();
    }

    public function getYqChannelList($uids){
        $where['_string'] = 'cp_user.uid IN ('.implode(',',$uids).')';
        $channel_list = M('User')->where($where)->field('count(1) count, sum(user_account_recharge_amount) recharge, sum(user_account_consume_amount) consume, sum(user_account_balance) balance')->join('cp_user_account b ON cp_user.uid = b.uid','left')->find();
        return $channel_list;
    }
    public function getYqRegisterCount($r_where){
        $register_count = M('User')->where($r_where)->count();
        return $register_count;
    }
    public function getYqStatisticsCount($o_where){
        $statistics_count = M('UserStatistics')->field('sum(recharge_amount) recharge,sum(order_amount) consume')->where($o_where)->find();
        return $statistics_count;
    }
    public function getYqConsumeSum($o_where){
        $sql_counsume_user = M('UserStatistics')->field('distinct uid')->where($o_where)->select(false);
        $consume_sum = M()->table($sql_counsume_user.' tmp')->join('cp_user on cp_user.uid=tmp.uid','left')->count();
        return $consume_sum;
    }
    public function getYqNewConsumeSum($n_where){
        $sql_counsume_user_new = M('UserStatistics')->field('distinct uid')->where($n_where)->select(false);
        $consume_sum_new = M()->table($sql_counsume_user_new.' tmp')->join('cp_user on cp_user.uid=tmp.uid','left')->count();
        return $consume_sum_new;
    }


	public function channel(){
		$this->_useReadDb();
		$s_date = I('s_date');
		$e_date = I('e_date');
		$consume_min = I('consume_min');
		$consume_max = I('consume_max');
		
		if($s_date){
			$r_where['user_register_time'] = array('egt',$s_date);
			$o_where['user_statistics_time'] = array('egt',$s_date);
			$n_where['order_first_time'] = array('egt',$s_date);
		}
		if($e_date){
			$r_where['user_register_time'] = $r_where['user_register_time'] ? array(array('egt',$s_date),array('elt',$e_date.' 23:59:59')) : array('elt',$e_date.' 23:59:59');
			$o_where['user_statistics_time'] = $o_where['user_statistics_time'] ? array(array('egt',$s_date),array('elt',$e_date)) : array('elt',$e_date);
			$n_where['order_first_time'] = $n_where['order_first_time'] ? array(array('egt',$s_date),array('elt',$e_date)) : array('elt',$e_date);
		}
		//$o_where['order_first_amount'] = array('gt',0);
		//$n_where['order_first_amount'] = array('gt',0);

		//字段：c 注册总数，a 渠道，总充值金额，总消费金额，总用户余额，ARPU值 总消费/总用户
		$channel_list = $this->getChannelList();
		//用户注册数
		$register_count = $this->getRegisterCount($r_where);
		//充值、消费金额
		$statistics_count = $this->getStatisticsCount($o_where);
		//消费用户数
		$consume_sum = $this->getConsumeSum($o_where);
		//新增消费用户数
		if($s_date){
			$consume_sum_new = $this->getNewConsumeSum($n_where);
		}else{
			$consume_sum_new = $consume_sum;
		}

		$all = array();
        $android = array();
        $ios = array();
		//消费金额筛选
		foreach($channel_list as $key=>$row){
			$channel_list[$key]['channel_name'] = $this->_converChannelName($key);
			if($consume_min){
				if(array_key_exists($key,$statistics_count)){
					if($statistics_count[$key]['consume'] < floatval($consume_min)){
						unset($channel_list[$key]);
						continue;
					}
				}else{
					unset($channel_list[$key]);
					continue;
				}
			}
			if($consume_max){
				if(array_key_exists($key,$statistics_count)){
					if($statistics_count[$key]['consume'] > floatval($consume_max)){
						unset($channel_list[$key]);
						continue;
					}
				}else{
					unset($channel_list[$key]);
					continue;
				}
			}
			//总体
			$all['count'] += $row['count'];
			$all['recharge'] += $row['recharge'];
			$all['consume'] += $row['consume'];
			$all['balance'] += $row['balance'];
			$all['register_count'] += $register_count[$key]['count'];
			$all['recharge_count'] += $statistics_count[$key]['recharge'];
			$all['consume_count'] += $statistics_count[$key]['consume'];
			$all['consume_sum'] += $consume_sum[$key]['count'];
			$all['consume_sum_new'] += $consume_sum_new[$key]['count'];

            $channel_arr 	= explode('__', $key);
            $channel_os   	= $channel_arr[1];
            if($channel_os == 1){
                $android['count'] += $row['count'];
                $android['recharge'] += $row['recharge'];
                $android['consume'] += $row['consume'];
                $android['balance'] += $row['balance'];
                $android['register_count'] += $register_count[$key]['count'];
                $android['recharge_count'] += $statistics_count[$key]['recharge'];
                $android['consume_count'] += $statistics_count[$key]['consume'];
                $android['consume_sum'] += $consume_sum[$key]['count'];
                $android['consume_sum_new'] += $consume_sum_new[$key]['count'];
            }elseif($channel_os == 2){
                $ios['count'] += $row['count'];
                $ios['recharge'] += $row['recharge'];
                $ios['consume'] += $row['consume'];
                $ios['balance'] += $row['balance'];
                $ios['register_count'] += $register_count[$key]['count'];
                $ios['recharge_count'] += $statistics_count[$key]['recharge'];
                $ios['consume_count'] += $statistics_count[$key]['consume'];
                $ios['consume_sum'] += $consume_sum[$key]['count'];
                $ios['consume_sum_new'] += $consume_sum_new[$key]['count'];
            }
        }
        array_unshift($channel_list,array('channel_name'=>'IOS','a'=>'IOS','recharge'=>$ios['recharge'],'count'=>$ios['count'],'consume'=>$ios['consume'],'balance'=>$ios['balance']));
        array_unshift($channel_list,array('channel_name'=>'Android','a'=>'Android','recharge'=>$android['recharge'],'count'=>$android['count'],'consume'=>$android['consume'],'balance'=>$android['balance']));
		array_unshift($channel_list,array('channel_name'=>'总体','a'=>'','recharge'=>$all['recharge'],'count'=>$all['count'],'consume'=>$all['consume'],'balance'=>$all['balance']));

        array_unshift($register_count,array('a'=>'IOS','count'=>$ios['register_count']));
        array_unshift($register_count,array('a'=>'Android','count'=>$android['register_count']));
        array_unshift($register_count,array('a'=>'','count'=>$all['register_count']));


        array_unshift($statistics_count,array('a'=>'IOS','recharge'=>$ios['recharge_count'],'consume'=>$ios['consume_count']));
        array_unshift($statistics_count,array('a'=>'Android','recharge'=>$android['recharge_count'],'consume'=>$android['consume_count']));
        array_unshift($statistics_count,array('a'=>'','recharge'=>$all['recharge_count'],'consume'=>$all['consume_count']));

        array_unshift($consume_sum,array('a'=>'IOS','count'=>$ios['consume_sum']));
        array_unshift($consume_sum,array('a'=>'Android','count'=>$android['consume_sum']));
		array_unshift($consume_sum,array('a'=>'','count'=>$all['consume_sum']));

        array_unshift($consume_sum_new,array('a'=>'IOS','count'=>$ios['consume_sum_new']));
        array_unshift($consume_sum_new,array('a'=>'Android','count'=>$android['consume_sum_new']));
		array_unshift($consume_sum_new,array('a'=>'','count'=>$all['consume_sum_new']));


		$this->assign('search_button', $this->channelButtons());
		$this->assign('channel_list', $channel_list);
		$this->assign('register_count', $register_count);
		$this->assign('statistics_count', $statistics_count);
		$this->assign('consume_sum', $consume_sum);
		$this->assign('consume_sum_new', $consume_sum_new);
		$this->assign('search_time', array('s_date'=>$s_date,'e_date'=>$e_date));
		$this->display();
	}
	public function getChannelList(){
		$channel_list = M('User')->field('count(1) count, concat(user_app_channel_id, "__", user_app_os) a, sum(user_account_recharge_amount) recharge, sum(user_account_consume_amount) consume, sum(user_account_balance) balance')->join('cp_user_account b ON cp_user.uid = b.uid','left')->group('user_app_os, user_app_channel_id')->select();
		return reindexArr($channel_list, 'a');
	}
	public function getRegisterCount($r_where){
		$register_count = M('User')->field('count(1) count, concat(user_app_channel_id, "__", user_app_os) a')->where($r_where)->group('user_app_os, user_app_channel_id')->select();
		return reindexArr($register_count, 'a');
	}
	public function getStatisticsCount($o_where){
		$statistics_count = M('UserStatistics')->field('concat(user_app_channel_id, "__", user_app_os) a,sum(recharge_amount) recharge,sum(order_amount) consume')->where($o_where)->group('user_app_os, user_app_channel_id')->select();
		return reindexArr($statistics_count, 'a');
	}
	public function getConsumeSum($o_where){
		$sql_counsume_user = M('UserStatistics')->field('distinct uid')->where($o_where)->select(false);
		$consume_sum = M()->field('count(1) count,concat(user_app_channel_id, "__", user_app_os) a')->table($sql_counsume_user.' tmp')->join('cp_user on cp_user.uid=tmp.uid','left')->group('user_app_os, user_app_channel_id')->select();
		return reindexArr($consume_sum, 'a');
	}
	public function getNewConsumeSum($n_where){
			$sql_counsume_user_new = M('UserStatistics')->field('distinct uid')->where($n_where)->select(false);
			$consume_sum_new = M()->field('count(1) count,concat(user_app_channel_id, "__", user_app_os) a')->table($sql_counsume_user_new.' tmp')->join('cp_user on cp_user.uid=tmp.uid','left')->group('user_app_os, user_app_channel_id')->select();
			return reindexArr($consume_sum_new, 'a');
	}

	public function channelButtons(){
		return array(
			array('name'=>'今天','start'=>date("Y-m-d",time()),'end'=>date("Y-m-d",time())),
		    array('name'=>'昨天','start'=>date("Y-m-d",strtotime("-1 day")),'end'=>date("Y-m-d",strtotime('-1 day'))),
			array('name'=>'本周','start'=>date("Y-m-d",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y"))),'end'=>date("Y-m-d",mktime(0, 0 , 0,date("m"),date("d")-date("w")+7,date("Y")))),
			array('name'=>'本月','start'=>date("Y-m-d",mktime(0, 0 , 0,date("m"),1,date("Y"))),'end'=>date("Y-m-d",mktime(0,0,0,date("m"),date("t"),date("Y")))),
			array('name'=>'全部','start'=>'','end'=>''),
		);
	}

	public function parseChannelList(){
		$channel_list = M('User')->field('concat(user_app_channel_id, "__", user_app_os) a')->group('user_app_os, user_app_channel_id')->select();
		$channel_list = reindexArr($channel_list, 'a');
		foreach($channel_list as $key=>$row){
			$channel_list[$key]['name'] = $this->_converChannelName($key);
		}
		return $channel_list;
	}
	public function newConsumeUser(){
		$this->_useReadDb();
		//渠道下拉选项
		$channel_list = $this->parseChannelList();

		$REQUEST = (array)I('request.');
		$w = $this->genUserStatisticsWhere();
		$model = M('UserStatistics');
		$table = $model->where($w['new_consume_where'])->group('uid')->select(false);
		$model->table($table.' tmp')->join('cp_user_account on tmp.uid=cp_user_account.uid','left');
		$list = $this->lists($model,$w['user_account_where'],'order_first_time','user_telephone,concat(user_app_channel_id, "__", user_app_os) a,user_register_time,order_first_time,order_first_amount,recharge_first_time,recharge_first_amount,user_account_balance,user_account_recharge_amount,user_account_consume_amount,user_account_coupon_amount,user_account_coupon_consumption');
		foreach($list as $key=>$rows){
			$list[$key]['channel_name'] = $this->_converChannelName($rows['a']);
		}
		
		$this->assign('list', $list);
		$this->assign('channel_list', $channel_list);
		$this->assign('request', $REQUEST);
		$this->display();
	}
	public function genUserStatisticsWhere(){
		$new_consume_where['order_first_amount'] = array('gt',0);
		$s_date = I('s_date');
		$e_date = I('e_date');
		if($s_date){
			$consume_where['user_statistics_time'] = array('egt',$s_date);
			$new_consume_where['order_first_time'] = array('egt',$s_date);
		}
		if($e_date){
			$consume_where['user_statistics_time'] = $consume_where['user_statistics_time'] ? array(array('egt',$s_date),array('elt',$e_date)) : array('elt',$e_date);
			$new_consume_where['order_first_time'] = $new_consume_where['order_first_time'] ? array(array('egt',$s_date),array('elt',$e_date)) : array('elt',$e_date);
		}
		if(I('channel')){
			$channel_arr = explode('__',I('channel'));
			$new_consume_where['user_app_os'] = $channel_arr[1];
			$new_consume_where['user_app_channel_id'] = $channel_arr[0];
			$consume_where['user_app_os'] = $channel_arr[1];
			$consume_where['user_app_channel_id'] = $channel_arr[0];
		}
		if(I('consume_min') != ''){
			$user_account_where['user_account_consume_amount'] = array('egt',floatval(I('consume_min')));
		}
		if(I('consume_max') != ''){
			$user_account_where['user_account_consume_amount'] = $user_account_where['user_account_consume_amount'] ? array(array('egt',floatval(I('consume_min'))),array('elt',floatval(I('consume_max')))) : array('elt',floatval(I('consume_max')));
		}
		if(I('recharge_min') != ''){
			$user_account_where['user_account_recharge_amount'] = array('egt',floatval(I('recharge_min')));
		}
		if(I('recharge_max') != ''){
			$user_account_where['user_account_recharge_amount'] = $user_account_where['user_account_recharge_amount'] ? array(array('egt',floatval(I('recharge_min'))),array('elt',floatval(I('recharge_max')))) : array('elt',floatval(I('recharge_max')));
		}
		if(I('user_register_time_start')){
			$consume_where['user_register_time'] = array('egt',I('user_register_time_start'));
		}
		if(I('user_register_time_end')){
			$consume_where['user_register_time'] = $consume_where['user_register_time'] ? array(array('egt',I('user_register_time_start')),array('elt',I('user_register_time_end'))) : array('elt',I('user_register_time_end'));
		}
		return array('new_consume_where'=>$new_consume_where,'user_account_where'=>$user_account_where,'consume_where'=>$consume_where);
	}
	public function newConsumeUserAmount(){
		$this->_useReadDb();
		$w = $this->genUserStatisticsWhere();
		$model = M('UserStatistics');
		$table = $model->where($w['new_consume_where'])->group('uid')->select(false);
		$amount = $model->table($table.' tmp')->join('cp_user_account on tmp.uid=cp_user_account.uid','left')->field('sum(user_account_recharge_amount) recharge_amount,sum(user_account_consume_amount) consume_amount,sum(user_account_balance) balance,sum(user_account_coupon_amount) coupon_amount,sum(user_account_coupon_consumption) coupon_consumption')->where($w['user_account_where'])->find();
		$this->ajaxReturn($amount,'JSON');
	}
	public function newConsumeUserExport(){
		$this->_useReadDb();
		$w = $this->genUserStatisticsWhere();
		$model = M('UserStatistics');
		$table = $model->where($w['new_consume_where'])->group('uid')->select(false);
		$list = $model->table($table.' tmp')->join('cp_user_account on tmp.uid=cp_user_account.uid','left')->field('user_telephone,concat(user_app_channel_id, "__", user_app_os) a,user_register_time,order_first_time,order_first_amount,recharge_first_time,recharge_first_amount,user_account_balance,user_account_recharge_amount,user_account_consume_amount,user_account_coupon_amount,user_account_coupon_consumption')->order('order_first_time')->where($w['user_account_where'])->select();
		$this->_consumeUserExport($list,'新增消费用户列表');
	}
	private function _consumeUserExport($list,$filename='_'){
		$title=array('用户名','渠道','注册时间','首次充值时间','首次充值金额','总充值金额','首次消费时间','首次消费金额','总消费金额','当前余额','红包获取','红包消费');
		$data = array();
		foreach($list as $key=>$rows){
			$data[$key][] = $rows['user_telephone'];
			$data[$key][] = $this->_converChannelName($rows['a']);
			$data[$key][] = $rows['user_register_time'];
			$data[$key][] = $rows['recharge_first_time'];
			$data[$key][] = $rows['recharge_first_amount'];
			$data[$key][] = $rows['user_account_recharge_amount'];
			$data[$key][] = $rows['order_first_time'];
			$data[$key][] = $rows['order_first_amount'];
			$data[$key][] = $rows['user_account_consume_amount'];
			$data[$key][] = $rows['user_account_balance'];
			$data[$key][] = $rows['user_account_coupon_amount'];
			$data[$key][] = $rows['user_account_coupon_consumption'];
		}
		$this->exportExcel($data,$title,$filename);
	}
	protected function exportExcel($data=array(),$title=array(),$filename='export'){
		$filename=iconv("UTF-8", "GB2312",$filename);
		header("Content-type:application/octet-stream");
		header("Accept-Ranges:bytes");
		header("Content-type:application/vnd.ms-excel");  
		header("Content-Disposition:attachment;filename=".$filename.".xls");
		header("Pragma: no-cache");
		header("Expires: 0");
		//导出xls 开始
		if (!empty($title)){
			foreach ($title as $k => $v) {
				$title[$k]=iconv("UTF-8", "GB2312",$v);
			}
			$title= implode("\t", $title);
			echo "$title\n";
		}
		if (!empty($data)){
			foreach($data as $key=>$val){
				foreach ($val as $ck => $cv) {
					$data[$key][$ck]=iconv("UTF-8", "GB2312", $cv);
				}
				$data[$key]=implode("\t", $data[$key]);
			}
			echo implode("\n",$data);
		}
	}
	public function consumeUser(){
		$this->_useReadDb();
		//渠道下拉选项
		$channel_list = $this->parseChannelList();

		$REQUEST = (array)I('request.');
		$w = $this->genUserStatisticsWhere();
		$model = M('UserStatistics');
		$table = $model->where($w['consume_where'])->group('uid')->select(false);
		$model->table($table.' tmp')->join('cp_user_account on tmp.uid=cp_user_account.uid','left');
		$list = $this->lists($model,$w['user_account_where'],'','user_telephone,concat(user_app_channel_id, "__", user_app_os) a,extra_channel_id,user_register_time,order_first_time,order_first_amount,recharge_first_time,recharge_first_amount,user_account_balance,user_account_recharge_amount,user_account_consume_amount,user_account_coupon_amount,user_account_coupon_consumption');

		foreach($list as $key=>$rows){
			$list[$key]['channel_name'] = $this->_converChannelName($rows['a'],$rows['extra_channel_id']);
		}

		$this->assign('list', $list);
		$this->assign('channel_list', $channel_list);
		$this->assign('request', $REQUEST);
		$this->display();
	}
	public function consumeUserAmount(){
		$this->_useReadDb();
		$w = $this->genUserStatisticsWhere();
		$model = M('UserStatistics');
		$table = $model->where($w['consume_where'])->group('uid')->select(false);
		$amount = $model->table($table.' tmp')->join('cp_user_account on tmp.uid=cp_user_account.uid','left')->field('sum(user_account_recharge_amount) recharge_amount,sum(user_account_consume_amount) consume_amount,sum(user_account_balance) balance,sum(user_account_coupon_amount) coupon_amount,sum(user_account_coupon_consumption) coupon_consumption')->where($w['user_account_where'])->find();
		$this->ajaxReturn($amount,'JSON');
	}
	public function consumeUserExport(){
		$this->_useReadDb();
		$w = $this->genUserStatisticsWhere();
		$model = M('UserStatistics');
		$table = $model->where($w['consume_where'])->group('uid')->select(false);
		$list = $model->table($table.' tmp')->join('cp_user_account on tmp.uid=cp_user_account.uid','left')->field('user_telephone,concat(user_app_channel_id, "__", user_app_os) a,user_register_time,order_first_time,order_first_amount,recharge_first_time,recharge_first_amount,user_account_balance,user_account_recharge_amount,user_account_consume_amount,user_account_coupon_amount,user_account_coupon_consumption')->where($w['user_account_where'])->select();
		$this->_consumeUserExport($list,'用户列表');
	}

	private function _useReadDb(){
		if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
			M()->db(1,'mysql://tigercai_server:e4huY8J7e4@rr-bp1y62h5vwc8o62yt.mysql.rds.aliyuncs.com:3306/tigercai');
		}
		/*if(!$_SERVER['DeveloperMode']){
			M()->db(1,'mysql://tigercai_server:e4huY8J7e4@rr-bp1y62h5vwc8o62yt.mysql.rds.aliyuncs.com:3306/tigercai');
		}*/
	}

	public function funds(){
		$this->_useReadDb();
		$s_date = I('s_date');
		$e_date = I('e_date');
		if($s_date){
			$user_coupon_where['user_coupon_create_time'] = array('egt',$s_date.' 00.00.00');
			$recharge_where['recharge_create_time'] = array('egt',$s_date.' 00.00.00');
			$baofu_daifu_map['withdraw_request_time'] = array('egt',$s_date.' 00.00.00');
		}
		if($e_date){
			$user_coupon_where['user_coupon_create_time'] = $user_coupon_where['user_coupon_create_time'] ? array(array('egt',$s_date.' 00.00.00'),array('elt',$e_date.' 23.59.59')) : array('elt',$e_date.' 23.59.59');
			$recharge_where['recharge_create_time'] = $recharge_where['recharge_create_time'] ? array(array('egt',$s_date.' 00.00.00'),array('elt',$e_date.' 23.59.59')) : array('elt',$e_date.' 23.59.59');
			$baofu_daifu_map['withdraw_request_time'] = $baofu_daifu_map['withdraw_request_time'] ? array(array('egt',$s_date.' 00.00.00'),array('elt',$e_date.' 23.59.59')) : array('elt',$e_date.' 23.59.59');
		}

		$user_coupon_where['coupon_id'] = 9;
		$recharge40 = M('UserCoupon')->field('sum(user_coupon_amount) - sum(user_coupon_balance) s')->where($user_coupon_where)->find();
		$this->assign('recharge40', $recharge40['s']);

		$user_coupon_where['coupon_id'] = 40;
		$recharge20 = M('UserCoupon')->field('sum(user_coupon_amount) - sum(user_coupon_balance) s')->where($user_coupon_where)->find();
		$this->assign('recharge20', $recharge20['s']);

		$user_coupon_where['coupon_id'] = array('in', array(167,168,169,170,171,172,173,174));
		$recharge_new = M('UserCoupon')->field('sum(user_coupon_amount) - sum(user_coupon_balance) s')->where($user_coupon_where)->find();
		$this->assign('recharge_new', $recharge_new['s']);

		$user_coupon_where['coupon_id'] = 8;
		$register5 = M('UserCoupon')->field('sum(user_coupon_amount) - sum(user_coupon_balance) s')->where($user_coupon_where)->find();
		$this->assign('register5', $register5['s']);

		$user_coupon_where['coupon_id'] = 17;
		$order_award = M('UserCoupon')->field('sum(user_coupon_amount) s')->where($user_coupon_where)->find();
		$this->assign('order_award', $order_award['s']);

		unset($user_coupon_where['coupon_id']);
		$user_coupon_where['coupon_is_sell'] = 1;
		$buy_coupon = M('UserCoupon')->field('sum(user_coupon_amount) - sum(coupon_price) s')->join('cp_coupon on cp_coupon.coupon_id=cp_user_coupon.coupon_id','left')->where($user_coupon_where)->find();
		$this->assign('buy_coupon', $buy_coupon['s']);

		$recharge_where['recharge_status'] = 1;
		$recharge_fee = M('Recharge')->field('cp_recharge.recharge_channel_id,sum(recharge_amount)*recharge_channel_factorage fee')->join('cp_recharge_channel on cp_recharge_channel.recharge_channel_id=cp_recharge.recharge_channel_id')->where($recharge_where)->group('cp_recharge.recharge_channel_id')->select();
		$recharge_fee = reindexArr($recharge_fee, 'recharge_channel_id');
		$this->assign('recharge_fee', $recharge_fee);

		$baofu_daifu_map['withdraw_daifu_channel'] = 'baofu';
		$baofu_daifu_times = M('Withdraw')->where($baofu_daifu_map)->count();
		$baofu_daifu_fee = $baofu_daifu_times*2;
		$this->assign('baofu_daifu_fee', $baofu_daifu_fee);

		$lianlian_daifu_times = 3891;
		$lianlian_daifu_fee = $lianlian_daifu_times*2;
		$this->assign('lianlian_daifu_fee', $lianlian_daifu_fee);

		$user_account_sql = 'select sum(user_account_balance) balance, sum(user_account_frozen_balance) frozen from cp_user a left join cp_user_account b on a.uid = b.uid where user_status = 1';
		$user_account = M('UserAccount')->query($user_account_sql);
		$user_account = $user_account[0];
		$this->assign('user_account', $user_account);

		$illegal_user_account_sql = 'select sum(user_account_balance) balance, sum(user_account_frozen_balance) frozen from cp_user a left join cp_user_account b on a.uid = b.uid where user_status = 0';
		$illegal_user_account = M('UserAccount')->query($illegal_user_account_sql);
		$illegal_user_frozen = $illegal_user_account[0]['balance'] + $illegal_user_account[0]['frozen'];
		$this->assign('illegal_user_frozen', $illegal_user_frozen);

		$user_integral_static = D('UserIntegral')->field('sum(user_integral_balance) as integral_balance, sum(user_integral_amount) integral_amount')->find();
		$integral_balance = $user_integral_static['integral_balance'];
		$integral_amount = $user_integral_static['integral_amount'];
		$integral_consume = $integral_amount - $integral_balance;
		$this->assign('integral_balance', $integral_balance);
		$this->assign('integral_consume', $integral_consume);
		$this->assign('integral_amount', $integral_amount);

		$user_coupon_where = array(
			'coupon_id' => array('in', array(178,179,180,181,182,183,184,185))
			);
		$user_integral_hongbao = M('UserCoupon')->field('sum(user_coupon_amount) s1,sum(user_coupon_balance) s2')->where($user_coupon_where)->find();
		$this->assign('user_integral_hongbao_amount', $user_integral_hongbao['s1']);
		$this->assign('user_integral_hongbao_custom', $user_integral_hongbao['s1']-$user_integral_hongbao['s2']);

		$this->display();
	}
	public function orderAward(){
		$start_date = I('s_date', date('Y-m-d', strtotime('-7 days')));
		$end_date = I('e_date', date('Y-m-d'));

		$map = array(
			'order_status' => 3,
			'order_create_time' => array('between', array($start_date.' 00:00:00', $end_date.' 23:59:59')),
			'order_plus_award_amount' => array('gt', 0)
			);
		$map_zc = array(
			'lottery_id' => array('IN', array(601, 602, 603, 604, 605, 606))
			);
		$map_lc = array(
			'lottery_id' => array('IN', array(701, 702, 703, 704, 705))
			);
		$map_zc = array_merge($map, $map_zc);
		$map_lc = array_merge($map, $map_lc);

		$zc_data = D('Order')->where($map_zc)->group('d')->field('DATE_FORMAT(order_create_time,"%Y-%m-%d") d, sum(order_plus_award_amount) s')->select();
		$zc_data = reindexArr($zc_data, 'd');
		$lc_data = D('Order')->where($map_lc)->group('d')->field('DATE_FORMAT(order_create_time,"%Y-%m-%d") d, sum(order_plus_award_amount) s')->select();
		$lc_data = reindexArr($lc_data, 'd');


		$category = $series_zc = $series_lc = $series_all = array();
		$days = (strtotime($end_date) - strtotime($start_date))/(3600*24);
		for ($i=0; $i <= $days; $i++) { 
			$day = date('Y-m-d', strtotime("+".$i."days", strtotime($start_date)));
			$category[] = $day;
			$zc_day_data = isset($zc_data[$day]) ? floatval($zc_data[$day]['s']) : 0;
			$lc_day_data = isset($lc_data[$day]) ? floatval($lc_data[$day]['s']) : 0;
			$series_zc[] = $zc_day_data;
			$series_lc[] = $lc_day_data; 
			$series_all[] = $zc_day_data + $lc_day_data; 
		}

		$average = averageData($series_all);
		$sum = sumData($series_all);

		$series = array(
			array('name' => '足彩加奖', 'data' => $series_zc),
			array('name' => '篮彩加奖', 'data' => $series_lc),
			array('name' => '总计', 'data' => $series_all)
			);

		$this->assign('category', $category);
		$this->assign('series', $series);
		$this->assign('start_date', $start_date);
		$this->assign('end_date', $end_date);
		$this->assign('average', $average);
		$this->assign('sum', $sum);
		$this->display();
	}

	public function countUserHours(){
		$start_date = I('s_date') ? I('s_date') : date('Y-m-d', strtotime('-6 days'));
		$end_date = I('e_date') ? I('e_date') : date('Y-m-d');

		$map = array(
			'user_register_time' => array('between', array($start_date.' 00:00:00', $end_date.' 23:59:59')),
			);
		$data = D('User')->where($map)->field("DATE_FORMAT(user_register_time,'%H') h, DATE_FORMAT(user_register_time,'%Y-%m-%d') d, DATE_FORMAT(user_register_time,'%Y%m%d%H') g,count(1) amount")->group('g')->select();

		$data = groupArrByField($data, 'd');

		$category = array('00', '01', '02', '03', '04', '05', '06', '07', '08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24');

		$day_range = getDayRange($start_date, $end_date);

		$series = array();
		foreach ($day_range as $date) {
			$date_hour = array();

			$data[$date] = reindexArr($data[$date], 'h');
			
			foreach ($category as $hour) {
				$date_hour[] = floatval($data[$date][$hour]['amount']);	
			}

			$series[] = array(
				'name' => $date,
				'data' => $date_hour
				);
		}

		$this->assign('category', $category);
		$this->assign('series', $series);
		$this->assign('start_date', $start_date);
		$this->assign('end_date', $end_date);
		$this->display();
	}

	public function countOrderHours(){
		$start_date = I('s_date') ? I('s_date') : date('Y-m-d', strtotime('-6 days'));
		$end_date = I('e_date') ? I('e_date') : date('Y-m-d');

		$map = array(
			'order_create_time' => array('between', array($start_date.' 00:00:00', $end_date.' 23:59:59')),
			'order_status' => array('in', array(3,8))
			);
		$data = D('Order')->where($map)->field("DATE_FORMAT(order_create_time,'%H') h, DATE_FORMAT(order_create_time,'%Y-%m-%d') d, DATE_FORMAT(order_create_time,'%Y%m%d%H') g,sum(order_total_amount) amount")->group('g')->select();

		$data = groupArrByField($data, 'd');

		$category = array('00', '01', '02', '03', '04', '05', '06', '07', '08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24');

		$day_range = getDayRange($start_date, $end_date);

		$series = array();
		foreach ($day_range as $date) {
			$date_hour = array();

			$data[$date] = reindexArr($data[$date], 'h');
			
			foreach ($category as $hour) {
				$date_hour[] = floatval($data[$date][$hour]['amount']);	
			}

			$series[] = array(
				'name' => $date,
				'data' => $date_hour
				);
		}

		$this->assign('category', $category);
		$this->assign('series', $series);
		$this->assign('start_date', $start_date);
		$this->assign('end_date', $end_date);
		$this->display();
	}

	public function countRechargeHours(){
		$start_date = I('s_date') ? I('s_date') : date('Y-m-d', strtotime('-6 days'));
		$end_date = I('e_date') ? I('e_date') : date('Y-m-d');

		$map = array(
			'recharge_create_time' => array('between', array($start_date.' 00:00:00', $end_date.' 23:59:59')),
			'recharge_status' => 1
			);
		$data = D('Recharge')->where($map)->field("DATE_FORMAT(recharge_create_time,'%H') h, DATE_FORMAT(recharge_create_time,'%Y-%m-%d') d, DATE_FORMAT(recharge_create_time,'%Y%m%d%H') g,sum(recharge_amount) amount")->group('g')->select();

		$data = groupArrByField($data, 'd');

		$category = array('00', '01', '02', '03', '04', '05', '06', '07', '08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24');

		$day_range = getDayRange($start_date, $end_date);

		$series = array();
		foreach ($day_range as $date) {
			$date_hour = array();

			$data[$date] = reindexArr($data[$date], 'h');
			
			foreach ($category as $hour) {
				$date_hour[] = floatval($data[$date][$hour]['amount']);	
			}

			$series[] = array(
				'name' => $date,
				'data' => $date_hour
				);
		}

		$this->assign('category', $category);
		$this->assign('series', $series);
		$this->assign('start_date', $start_date);
		$this->assign('end_date', $end_date);
		$this->display();
	}

	private function buildHiCharSeries($category, $data){
		$series = array();

		foreach ($category as $cate) {
			if (isset($data[$cate])) {
				$series[] = intval($data[$cate]);
			} else {
				$series[] = 0;
			}
		}

		return $series;
	}

	private function buildDayRang($start, $end){
		$rang = array();

		$days = (strtotime($end) - strtotime($start))/86400;
		for ($i=0; $i < $days; $i++) { 
			$rang[] = date('Y-m-d', strtotime("+{$i} days", strtotime($start)));
		}

		return $rang;
	}

	private function _combineArr($arr, $key_fiele, $val_field){
		$key_arr = extractArrField($arr, $key_fiele);
		$val_arr = extractArrField($arr, $val_field);

		$result = array_combine($key_arr, $val_arr);
		
		return $result;
	}

	private function buildUserAppChannelData($data, $category){
		$new_data = array();

		foreach ($data as $row) {
			$new_data[$row['user_app_channel_id'].'__'.$row['user_app_os']][] = $row;
		}

		$series = array();
		foreach ($new_data as $channel=>$row) {
			$series[] = array('name' => $this->_converChannelName($channel), 'data'=>$this->buildHiCharSeries($category, $this->_combineArr($row, 'day', 'c')), 'visible' => false);
		}
		
		return $series;
	}

	public function _converChannelName($channel_string){
		$channel_arr 	= explode('__', $channel_string);
		$channel_name 	= $channel_arr[0];
		$channel_os   	= $channel_arr[1];

		$channels = getUserAppChannels();


		if ($channel_os == '0') {
			return '前置注册';
		}

		$platform_filter_channels = $channels[$channel_os];

		if (array_key_exists($channel_name, $platform_filter_channels)) {
			return $platform_filter_channels[$channel_name]['app_name'];
		}

		return $channel_name;
	}

	public function informationView(){
		$start_date = I('s_date', date('Y-m-d', strtotime('-7 days')));
		$end_date = I('e_date', date('Y-m-d'));

		$category = getDaysRange(date('Y-m-d', strtotime($start_date)), date('Y-m-d', strtotime($end_date)));

		$series = array();
		$information_category_list = M('InformationCategory')->select();
		foreach($information_category_list as $information_category){
			$temp = array();
			$temp['name'] = $information_category['information_category_name'];
			foreach ($category as $day){
				$temp['data'][$day] = 0;
			}
			$temp['visible'] = false;
			$series[$information_category['information_category_id']] = $temp;
		}



		$series[0] = $series[array_rand($series)];
		$series[0]['name'] = '全部频道';
		$series[0]['visible'] = true;

		$InformationList = D('InformationView')->getInformationViewByDate($start_date.' 00:00:00', $end_date.' 23:59:59');

		foreach($InformationList as $informationInfo){
			$y_m_d = substr($informationInfo['information_view_createtime'], 0 ,10);
			$series[$informationInfo['information_view_information_cat_id']]['data'][$y_m_d] += 1;
			$series[0]['data'][$y_m_d] += 1;
		}


		$average = averageData($series[0]['data']);
		$sum = sumData($series[0]['data']);

		foreach ($series as $key=>$v){
			$series[$key]['data'] = array_values($v['data']);
		}
		$series = array_values($series);
		$category = array_unique($category);

		$this->assign('s_date', date('Y-m-d',strtotime($start_date)));
		$this->assign('e_date', date('Y-m-d',strtotime($end_date)));
		$this->assign('category', json_encode($category));
		$this->assign('series', json_encode($series));
		$this->assign('average', $average);
		$this->assign('sum', $sum);
		$this->display();

	}

    public function channelOfYQ(){
        $this->_useReadDb();
        $s_date = I('s_date');
        $e_date = I('e_date');
        $consume_min = I('consume_min');
        $consume_max = I('consume_max');

        if($s_date){
            $r_where['user_register_time'] = array('egt',$s_date);
            $o_where['user_statistics_time'] = array('egt',$s_date);
            $n_where['order_first_time'] = array('egt',$s_date);
        }
        if($e_date){
            $r_where['user_register_time'] = $r_where['user_register_time'] ? array(array('egt',$s_date),array('elt',$e_date.' 23:59:59')) : array('elt',$e_date.' 23:59:59');
            $o_where['user_statistics_time'] = $o_where['user_statistics_time'] ? array(array('egt',$s_date),array('elt',$e_date)) : array('elt',$e_date);
            $n_where['order_first_time'] = $n_where['order_first_time'] ? array(array('egt',$s_date),array('elt',$e_date)) : array('elt',$e_date);
        }
        //$o_where['order_first_amount'] = array('gt',0);
        //$n_where['order_first_amount'] = array('gt',0);

        //字段：c 注册总数，a 渠道，总充值金额，总消费金额，总用户余额，ARPU值 总消费/总用户
        $channel_list = $this->getChannelList();
        //用户注册数
        $register_count = $this->getRegisterCount($r_where);
        //充值、消费金额
        $statistics_count = $this->getStatisticsCount($o_where);
        //消费用户数
        $consume_sum = $this->getConsumeSum($o_where);
        //新增消费用户数
        if($s_date){
            $consume_sum_new = $this->getNewConsumeSum($n_where);
        }else{
            $consume_sum_new = $consume_sum;
        }

        $all = array();
        $android = array();
        $ios = array();
        //消费金额筛选
        foreach($channel_list as $key=>$row){
            $channel_list[$key]['channel_name'] = $this->_converChannelName($key);
            if($consume_min){
                if(array_key_exists($key,$statistics_count)){
                    if($statistics_count[$key]['consume'] < floatval($consume_min)){
                        unset($channel_list[$key]);
                        continue;
                    }
                }else{
                    unset($channel_list[$key]);
                    continue;
                }
            }
            if($consume_max){
                if(array_key_exists($key,$statistics_count)){
                    if($statistics_count[$key]['consume'] > floatval($consume_max)){
                        unset($channel_list[$key]);
                        continue;
                    }
                }else{
                    unset($channel_list[$key]);
                    continue;
                }
            }
            //总体
            $all['count'] += $row['count'];
            $all['recharge'] += $row['recharge'];
            $all['consume'] += $row['consume'];
            $all['balance'] += $row['balance'];
            $all['register_count'] += $register_count[$key]['count'];
            $all['recharge_count'] += $statistics_count[$key]['recharge'];
            $all['consume_count'] += $statistics_count[$key]['consume'];
            $all['consume_sum'] += $consume_sum[$key]['count'];
            $all['consume_sum_new'] += $consume_sum_new[$key]['count'];

            $channel_arr 	= explode('__', $key);
            $channel_os   	= $channel_arr[1];
            if($channel_os == 1){
                $android['count'] += $row['count'];
                $android['recharge'] += $row['recharge'];
                $android['consume'] += $row['consume'];
                $android['balance'] += $row['balance'];
                $android['register_count'] += $register_count[$key]['count'];
                $android['recharge_count'] += $statistics_count[$key]['recharge'];
                $android['consume_count'] += $statistics_count[$key]['consume'];
                $android['consume_sum'] += $consume_sum[$key]['count'];
                $android['consume_sum_new'] += $consume_sum_new[$key]['count'];
            }elseif($channel_os == 2){
                $ios['count'] += $row['count'];
                $ios['recharge'] += $row['recharge'];
                $ios['consume'] += $row['consume'];
                $ios['balance'] += $row['balance'];
                $ios['register_count'] += $register_count[$key]['count'];
                $ios['recharge_count'] += $statistics_count[$key]['recharge'];
                $ios['consume_count'] += $statistics_count[$key]['consume'];
                $ios['consume_sum'] += $consume_sum[$key]['count'];
                $ios['consume_sum_new'] += $consume_sum_new[$key]['count'];
            }
        }

        array_unshift($channel_list,array('channel_name'=>'总体','a'=>'','recharge'=>$all['recharge'],'count'=>$all['count'],'consume'=>$all['consume'],'balance'=>$all['balance']));
        array_unshift($channel_list,array('channel_name'=>'IOS','a'=>'IOS','recharge'=>$ios['recharge'],'count'=>$ios['count'],'consume'=>$ios['consume'],'balance'=>$ios['balance']));
        array_unshift($channel_list,array('channel_name'=>'Android','a'=>'Android','recharge'=>$android['recharge'],'count'=>$android['count'],'consume'=>$android['consume'],'balance'=>$android['balance']));

        array_unshift($register_count,array('a'=>'','count'=>$all['register_count']));
        array_unshift($register_count,array('a'=>'IOS','count'=>$ios['register_count']));
        array_unshift($register_count,array('a'=>'Android','count'=>$android['register_count']));

        array_unshift($statistics_count,array('a'=>'','recharge'=>$all['recharge_count'],'consume'=>$all['consume_count']));
        array_unshift($statistics_count,array('a'=>'IOS','recharge'=>$ios['recharge_count'],'consume'=>$ios['consume_count']));
        array_unshift($statistics_count,array('a'=>'Android','recharge'=>$android['recharge_count'],'consume'=>$android['consume_count']));

        array_unshift($consume_sum,array('a'=>'','count'=>$all['consume_sum']));
        array_unshift($consume_sum,array('a'=>'IOS','count'=>$ios['consume_sum']));
        array_unshift($consume_sum,array('a'=>'Android','count'=>$android['consume_sum']));

        array_unshift($consume_sum_new,array('a'=>'','count'=>$all['consume_sum_new']));
        array_unshift($consume_sum_new,array('a'=>'IOS','count'=>$ios['consume_sum_new']));
        array_unshift($consume_sum_new,array('a'=>'Android','count'=>$android['consume_sum_new']));

        $this->assign('search_button', $this->channelButtons());
        $this->assign('channel_list', $channel_list);
        $this->assign('register_count', $register_count);
        $this->assign('statistics_count', $statistics_count);
        $this->assign('consume_sum', $consume_sum);
        $this->assign('consume_sum_new', $consume_sum_new);
        $this->assign('search_time', array('s_date'=>$s_date,'e_date'=>$e_date));
        $this->display();
    }

    public function userComsumeRank(){
        $sql = 'SELECT a.uid, user_real_name, user_telephone, user_total_order FROM cp_user_order_statics a left join cp_user b on a.uid = b.uid ORDER BY a.user_total_order desc LIMIT 1000';

        $data = M()->query($sql);

        $this->assign('list', $data);

        $this->display();
    }

}
