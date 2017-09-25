<?php

namespace Home\Controller;

use Home\Controller\GlobalController;
use Home\Util\Factory;

class WebPayController extends BettingBaseController{
	private $_user_info = null;

	private function _genResponseDataForRequestPayUrl($params, $raw_json_params, $szc_order = 0 ){
		$uid = $this->_user_info['uid'];
		
		// 判断余额是否足够
		if($params->act ==10704){
			$order_info 	= D('Order')->getOrderInfo($params->order_id);
			$total_money = $order_info['order_total_amount'];
            $orderTotalAmount = $total_money;
			$lottery_id = $order_info['lottery_id'];
		}elseif($params->act ==10701 || $params->act ==10707 || $params->act ==10708){
		    $szc_follow_info = $this->_getSzcFollowInfo(json_decode($raw_json_params,true));
			$orderTotalAmount = $this->_getOrderTotalAmount($params->tickets, $szc_follow_info['multiple']);
			$total_money = $this->calcTotalPayAmount($params->tickets,$szc_follow_info['follow_detail'],$params->suite_id);//$orderTotalAmount * $params->follow_times;
			$lottery_id = $params->lottery_id;
			if(empty($lottery_id)){
                $issue_info = D('Issue')->getIssueInfo($params->issue_id);
                $lottery_id = $issue_info['lottery_id'];
            }
		}else{
			$total_money = $params->total_amount;
            $orderTotalAmount = $total_money;
			$lottery_id = $params->lottery_id;
		}
		$lack_money = $this->_calculateLackMoneyForPay($uid, $total_money ,$lottery_id,$orderTotalAmount,$params->suite_id);
		if($lack_money){
			$result['money'] = $lack_money;
			ApiLog("lack_money:".$lack_money,'webpay');
			return $result;
		}
		ApiLog('$params:'.print_r($params,true),'uni');
		$encrypt_key = queryClientDesEncryptKeyBySessionCode($params->session);
		if (empty($encrypt_key)) {
			\AppException::throwException(C('ERROR_CODE.SESSION_ERROR'));
		}
		$result['pay_url'] = $this->_buildEncryptPayUrl($encrypt_key, $raw_json_params, $params, $szc_order);
		return $result;
	}

    private function _getSzcFollowInfo($raw_json_params){
        if(empty($raw_json_params['follow_detail'])){
            if(empty($raw_json_params['suite_id'])){
                //普通下单
                $issueId = $raw_json_params['issue_id'];
                $multiple = $raw_json_params['multiple'];
                $follow_times = $raw_json_params['follow_times'];
            }else{
                //追号套餐
                $packagesInfo = D('LotteryPackage')->getPackagesInfoById($raw_json_params['suite_id']);
                $issueId = $raw_json_params['issue_id'];
                $multiple = $packagesInfo['lp_multiple'];
                $follow_times = $packagesInfo['lp_issue_num'];
            }
            $orderTotalAmount = $this->_calcSzcOrderTotalAmountForOneTime($raw_json_params['tickets'], $multiple);
            $follow_detail = $this->_buildFollowDetails($raw_json_params['follow_detail'],$follow_times,$orderTotalAmount,$multiple);
        }else{
            //智能追号
            $follow_detail = $raw_json_params['follow_detail'];
            $multiple = $follow_detail[0]['multiple'];
            $issueId = $follow_detail[0]['issue_id'];
            $follow_times = $raw_json_params['follow_times'];
        }
        return array(
            'follow_detail' => $follow_detail,
            'multiple' => $multiple,
            'issueId' => $issueId,
            'follow_times' => $follow_times,
        );
    }

    private function _buildFollowDetails($followDetails,$userFollowTimes=1,$orderTotalAmount=0,$multiple=1){
        if(empty($followDetails)){
            for($i=0;$i<$userFollowTimes;$i++){
                $followDetails[$i]['multiple'] = $multiple;
                $followDetails[$i]['total_amount'] = $orderTotalAmount;
            }
        }
        return $followDetails;
    }

    private  function _calcSzcOrderTotalAmountForOneTime(array $tickets, $multiple){
        $totalAmount = 0;
        foreach ($tickets as $ticket) {
            $totalAmount += $ticket['total_amount'];
        }
        return $totalAmount * $multiple;
    }
	
	private function _getOrderTotalAmount(array $tickets, $multiple){
		$totalAmount = 0;
		foreach ($tickets as $ticket) {
			$totalAmount += $ticket['total_amount'];
		}
		return $totalAmount * $multiple;
	}
	
	private function _buildEncryptPayUrl($encrypt_key, $raw_json_params, $params, $szc_order =0){
		$sign = $encrypt_key[0]['sign'];
		$sign_iv = $encrypt_key[0]['sign_iv'];
		$encrypt_params = encrypt3des($sign, $sign_iv, $raw_json_params);
// 		$url_params['p'] = urlencode(base64_encode($encrypt_params));
		$url_params['t'] = $this->_genPayUniqueCode($raw_json_params, $params);
		$url_params['s'] = $params->session;
		$url_params['l'] = $szc_order;
		$url = U('WP/index', $url_params, true ,true);

		if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
			$url = 'http://'.$_SERVER['HTTP_HOST'].'/' . U('WP/index', $url_params);
		}elseif(get_cfg_var('PROJECT_RUN_MODE') == 'TEST'){
			$url = 'http://test.phone.api.tigercai.com/' . U('WP/index', $url_params);
			//$url = 'http://192.168.3.171:81/index.php?s=/Home/' . U('WP/index', $url_params);
		}else {
			$url = 'http://192.168.1.171:81/index.php?s=/Home/' . U('WP/index', $url_params);
		}
		return $url;
	}

	private function _genPayUniqueCode($raw_json_params, $params){
		$pay_code = $this->_user_info['uid'].md5($params->session . uniqid());
		$key = 'wpcode:'.$pay_code;
		$redis_instance = Factory::createRedisObj();
		$result = $redis_instance->setex($key, 3600, $raw_json_params);
		if(!$result){
			$redis_instance->setex($key, 3600, $raw_json_params);
		}
		return $pay_code;
	}
	
	private function _buildResponse($response_data){
		if($response_data['money']>0){
			$code = C('ERROR_CODE.INSUFFICIENT_FUND');
		}
		if($response_data['pay_url']){
			$code = C('ERROR_CODE.SUCCESS');
		}
		return array(
				'result' => $response_data,
				'code' => $code
		);
	}
	
	public function genPayUrlForSubmitOrder($params){
		$this->_user_info = $this->getAvailableUser($params->session);
		
		$response_data = $this->_genResponseDataForRequestPayUrl($params, $this->_buildOrderParamsForSubmitOrder($params));
		return $this->_buildResponse($response_data);
	}

	public function genPayUrlForSzcOrder($params){
		$this->_user_info = $this->getAvailableUser($params->session);
		$szc_order = 1;
		$response_data = $this->_genResponseDataForRequestPayUrl($params, $this->_buildOrderParamsForSzc($params), $szc_order);
		return $this->_buildResponse($response_data);
	}

	public function genPayUrlForJcOrder($params){
		$this->_user_info = $this->getAvailableUser($params->session);
		
		$response_data = $this->_genResponseDataForRequestPayUrl($params, $this->_buildOrderParamsForJc($params));
		return $this->_buildResponse($response_data);
	}

	public function genPayUrlForOptimizeOrder($params){
		$this->_user_info = $this->getAvailableUser($params->session);
		
		$response_data = $this->_genResponseDataForRequestPayUrl($params, $this->_buildOrderParamsForOptimize($params));
		return $this->_buildResponse($response_data);
	}

	public function genPayUrlForNoPaymentOrder($params){
		$this->_user_info = $this->getAvailableUser($params->session);
		$order_info 	= D('Order')->getOrderInfo($params->order_id);
		$lottery_id = $order_info['lottery_id'];
		if (isJCLottery($lottery_id)) {
			$szc_order = 0;
		} else {
			$szc_order = 1;
		}
		$response_data = $this->_genResponseDataForRequestPayUrl($params, $this->_buildOrderParamsForNoPayment($params),$szc_order);
		return $this->_buildResponse($response_data);
	}
	
	private function _calculateLackMoneyForPay($uid, $total_amount ,$lottery_id,$orderTotalAmount,$suite_id = 0){
		$user_balance = D('UserAccount')->getUserBalance($uid);
		//$max_coupon_balance_info = D('UserCoupon')->getMaxCouponBalanceInfo($uid);
		$user_coupon_list = D('UserCoupon')->getAvailableCouponList($uid);
		$max_coupon_balance_info = $this->_filterCouponList($user_coupon_list,$lottery_id,$orderTotalAmount,$suite_id);


		ApiLog("max_coupon:".$user_balance.'===='.$total_amount.'===='.print_r($max_coupon_balance_info,true),'webpay');
		$lack_money = 0;
		if($max_coupon_balance_info){
			if(($max_coupon_balance_info['user_coupon_balance']+$user_balance)<$total_amount){
				$has_amount = bcadd($max_coupon_balance_info['user_coupon_balance'],$user_balance,2);
// 				$lack_money = $total_amount-$max_coupon_balance_info['user_coupon_balance']-$user_balance;
				$lack_money = bcsub($total_amount,$has_amount,2);
			}
		}else{
			if($user_balance<$total_amount){
// 				$lack_money = $total_amount-$user_balance;
				$lack_money = bcsub($total_amount,$user_balance,2);
			}
		}
		ApiLog("max_coupon lack:".$lack_money.'===='.$user_balance.'===='.$total_amount.'===='.print_r($max_coupon_balance_info,true),'webpay');
		
		return $lack_money;
	}

	private function _filterCouponList($user_coupon_list,$lottery_id,$order_amount,$suite_id = 0){
		$max_coupon_balance_info = array();
		foreach($user_coupon_list as $key => $user_coupon_info){
		    if(!empty($suite_id)){
                if($user_coupon_info['user_coupon_type'] == 2){
                    unset($user_coupon_list[$key]);
                    continue;
                }
            }
			if(!empty($user_coupon_info['coupon_lottery_ids'])){
				$lottery_list = explode(',',$user_coupon_info['coupon_lottery_ids']);
				if(!in_array($lottery_id,$lottery_list)){
					unset($user_coupon_list[$key]);
					continue;
				}
			}
			//bccomp($order_amount, $user_coupon_info['coupon_min_consume_price']) < 0
			/*if( ($order_amount < $user_coupon_info['coupon_min_consume_price'])){*/
            if(bccomp($order_amount, $user_coupon_info['coupon_min_consume_price']) < 0){
				unset($user_coupon_list[$key]);
				continue;
			}
		}
		$user_coupon_list = array_values($user_coupon_list);
		$max_coupon_balance = 0;
		$curr_coupon_balance = 0;
		foreach($user_coupon_list as $key => $user_coupon_info){
			$curr_coupon_balance = $user_coupon_info['user_coupon_balance'];
			if($curr_coupon_balance > $max_coupon_balance){
				$max_coupon_balance = $curr_coupon_balance;
				$max_coupon_balance_info = $user_coupon_info;
			}
		}
		return $max_coupon_balance_info;

	}
	
	private function _buildOrderIdentity(){
		$randomStr = strtoupper(random_string(4));
		return $this->_user_info['user_telephone'].date('ymdhis').$randomStr.$this->_user_info['uid'];
	}
	
	private function _buildOrderParamsForSzc($params){
		$raw_json_params['order_identity'] = $this->_buildOrderIdentity();
		$raw_json_params['follow_times'] = $params->follow_times;
		$raw_json_params['tickets'] = $params->tickets;
        $raw_json_params['lottery_id'] = $params->lottery_id;
		$raw_json_params['issue_id'] = $params->issue_id;
		$raw_json_params['coupon_id'] = $params->coupon_id;
		$raw_json_params['multiple'] = $params->multiple;
		$raw_json_params['coupon_id'] = 0;
		$raw_json_params['act'] = $params->act;
        $raw_json_params['bundleId'] = $params->bundleId;
        $raw_json_params['follow_detail'] = $params->follow_detail;
        $raw_json_params['suite_id'] = $params->suite_id;
        $raw_json_params['is_win_stop'] = $params->is_win_stop;
        $raw_json_params['win_stop_amount'] = $params->win_stop_amount;
        $raw_json_params['is_independent'] = $params->is_independent;
		return json_encode($raw_json_params);
	}

	private function _buildOrderParamsForJc($params){
		$raw_json_params['order_identity'] = $this->_buildOrderIdentity();
		$raw_json_params['lottery_id'] = $params->lottery_id;
		$raw_json_params['total_amount'] = $params->total_amount;
		$raw_json_params['series'] = $params->series;
		$raw_json_params['stake_count'] = $params->stake_count;
		$raw_json_params['multiple'] = $params->multiple;
		$raw_json_params['coupon_id'] = $params->coupon_id;
		$raw_json_params['play_type'] = $params->play_type;
		$raw_json_params['schedule_orders'] = $params->schedule_orders;
		$raw_json_params['act'] = $params->act;
        $raw_json_params['bundleId'] = $params->bundleId;
		return json_encode($raw_json_params);
	}
	
	private function _buildOrderParamsForSubmitOrder($params){
		$raw_json_params['order_identity'] = $this->_buildOrderIdentity();
		$raw_json_params['lottery_id'] = $params->lottery_id;
		$raw_json_params['issue_no'] = $params->issue_no;
		$raw_json_params['total_amount'] = $params->total_amount;
		$raw_json_params['bet_type'] = $params->bet_type;
		$raw_json_params['stake_count'] = $params->stake_count;
		$raw_json_params['multiple'] = $params->multiple;
		$raw_json_params['coupon_id'] = $params->coupon_id;
		$raw_json_params['play_type'] = $params->play_type;
		$raw_json_params['schedule_orders'] = $params->schedule_orders;
		$raw_json_params['act'] = $params->act;
        $raw_json_params['bundleId'] = $params->bundleId;
		return json_encode($raw_json_params);
	}
	
	private function _buildOrderParamsForNoPayment($params){
		$raw_json_params['order_id'] = $params->order_id;
		$order_info 	= D('Order')->getOrderInfo($params->order_id);
		$raw_json_params['lottery_id'] = $order_info['lottery_id'];
		$raw_json_params['total_amount'] = $order_info['order_total_amount'];
		$raw_json_params['stake_count'] = $order_info['order_total_amount']/2;
		$raw_json_params['multiple'] = $order_info['order_multiple'];
		$raw_json_params['coupon_id'] = 0;
		$raw_json_params['play_type'] = $order_info['order_total_amount'];
		$raw_json_params['act'] = $params->act;
        $raw_json_params['bundleId'] = $params->bundleId;
		return json_encode($raw_json_params);
	}

	private function _buildOrderParamsForOptimize($params){
		$raw_json_params['order_identity'] = $this->_buildOrderIdentity();
		$raw_json_params['lottery_id'] = $params->lottery_id;
		$raw_json_params['total_amount'] = $params->total_amount;
		$raw_json_params['series'] = $params->series;
		$raw_json_params['stake_count'] = $params->stake_count;
		$raw_json_params['order_multiple'] = $params->order_multiple;
		$raw_json_params['coupon_id'] = $params->coupon_id;
		$raw_json_params['play_type'] = $params->play_type;
		$raw_json_params['select_schedule_ids'] = $params->select_schedule_ids;
		$raw_json_params['optimize_ticket_list'] = $params->optimize_ticket_list;
		$raw_json_params['act'] = $params->act;
        $raw_json_params['bundleId'] = $params->bundleId;
		return json_encode($raw_json_params);
	}
}