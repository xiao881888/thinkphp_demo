<?php

namespace Home\Controller;

use Home\Controller\CobetBaseController;
use Home\Util\Factory;

class WBController extends CobetBaseController{
	const JUMP_TYPE_FOR_APP = 0;
	const JUMP_TYPE_FOR_ORDER = 1;
	const JUMP_TYPE_FOR_BET = 2;
	const JUMP_TYPE_FOR_RECHARGE = 3;
	private $_request_params = null;
	private $_session_code = null;
	private $_user_info = null;
	private $_uid = null;
	private $_msg_map = null;
	private $_cash_coupon = 1;
	private $_full_reduced_coupon = 2;

	public function __construct(){
		import('@.Util.AppException');
		parent::__construct();
		$this->_msg_map = C('WEB_PAY_MESSAGE');
		$this->_request_params = $this->_parseWebEncryptedParams();
		$this->_user_info = $this->queryUserInfoBySessionCode($this->_session_code);
		$this->_uid = $this->_user_info['uid'];
	}

	protected function queryUserInfoBySessionCode($session_code){
		$uid = D('Session')->getUid($session_code);
		ApiLog('uid:' . M()->_sql() . '====' . $uid, 'wsess');
		if (empty($uid)) {
			$this->_redirectToFailPageAndExit();
		}
		$user_info = D('User')->getUserInfo($uid);
		$user_is_available = $user_info['user_status'] == C('USER_STATUS.ENABLE');
		ApiLog('$user_info:' . M()->_sql() . '==' . $user_is_available . '==' . print_r($user_info, true), 'wsess');
		
		if (!$user_is_available) {
			$this->_redirectToFailPageAndExit();
		}
		return $user_info;
	}

	public function index(){
		$lottery_info = D('Lottery')->getLotteryInfo($this->_request_params->lottery_id);
		if (isJCLottery($this->_request_params->lottery_id)) {
			$this->assign('lottery_name', $lottery_info['lottery_name']);
		} else {

            if(isZcsfc($this->_request_params->lottery_id)){
                $issue_info = D('Issue')->queryIssueInfoByIssueNo($this->_request_params->lottery_id,$this->_request_params->issue_no);
            }else{
                $issue_info = D('Issue')->getIssueInfo($this->_request_params->issue_id);
            }
			$this->assign('lottery_name', $lottery_info['lottery_name'] . ' ' . $issue_info['issue_no'] . '期');
		}
		
		$this->_queryUserCouponListForWebPay($this->_uid, $this->_request_params->pay_total_amount, $this->_request_params->lottery_id);
		$this->assign('total_amount', $this->_request_params->pay_total_amount);
        $this->assign('app_id',getRequestAppId($this->_request_params->bundleId));
		if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
			$pay_url = 'http://' . $_SERVER['HTTP_HOST'] . '/' . U('WB/payOrder');
		} elseif (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
			$pay_url = 'http://test.phone.api.tigercai.com/index.php?s=/Home/' . U('WB/payOrder');
		} else {
			$pay_url = 'http://192.168.1.171:81/index.php?s=/Home/' . U('WB/payOrder');
		}
		$this->assign('pay_scheme_type', $this->_request_params->act);
		$this->assign('pay_url', $pay_url);
		$this->display();
	}

	private function _redirectToFailPage($error_msg, $jump_page = ''){
		$this->assign('error_msg', $error_msg);
		$this->assign('jump_page', $jump_page);
		$this->display('error');
	}

	private function _redirectToSuccessPage($success_msg){
		if ($this->_request_params->lottery_id) {
			$this->assign('lottery_id', intval($this->_request_params->lottery_id));
		}
		$this->assign('success_msg', $success_msg);
		$this->display('success');
	}

	private function _getLotteryInfo($lottery_id){
		$lottery_info = D('Lottery')->getLotteryInfo($lottery_id);
		$this->assign('lottery_name', $lottery_info['lottery_name']);
	}

	private function _queryUserCouponListForWebPay($uid, $total_amount, $lottery_id, $suite_id = 0){
		$user_balance = D('UserAccount')->getUserBalance($uid);
		$lack_money = 0;
		if ($this->_request_params->pay_total_amount > $user_balance) {
			$lack_money = bcsub($this->_request_params->pay_total_amount, $user_balance, 2);
		}
		$user_coupon_list = D('UserCouponView')->queryUserCouponListForWebPay($uid, $lack_money);
		$user_coupon_list = $this->_filterCouponList($user_coupon_list, $lottery_id, $total_amount, $suite_id, true);
		
		$coupon_count_info = $this->_getCouponCountInfo($user_coupon_list);
		$this->assign('coupon_count_info', $coupon_count_info);
		
		$this->assign('user_balance', $user_balance);
		$this->assign('has_coupon_list', count($user_coupon_list));
		if (count($user_coupon_list) > 0) {
			$user_coupon_list = $this->_addUserCouponEndTimeDesc($user_coupon_list);
			$this->assign('user_coupon_list', $user_coupon_list);
			$this->assign('biggest_user_coupon_info', $user_coupon_list[0]);
			$cost_coupon = $user_coupon_list[0]['balance'] > $this->_request_params->pay_total_amount ? $this->_request_params->pay_total_amount : $user_coupon_list[0]['balance'];
			$this->assign('cost_coupon_money', $cost_coupon);
		}
		return $user_coupon_list;
	}

	private function _addUserCouponEndTimeDesc($user_coupon_list){
		foreach ($user_coupon_list as $key => $user_coupon_info) {
			$time_diff = strtotime($user_coupon_info['end_time']) - time();
			if ($time_diff >= 24 * 60 * 60) {
				$user_coupon_list[$key]['end_time_desc'] = floor($time_diff / (24 * 60 * 60)) . '天后到期';
			} elseif ($time_diff < 24 * 60 * 60 && $time_diff >= 60 * 60) {
				$user_coupon_list[$key]['end_time_desc'] = floor($time_diff / (60 * 60)) . '小时后到期';
			} else {
				$user_coupon_list[$key]['end_time_desc'] = '即将到期';
			}
		}
		return $user_coupon_list;
	}

	private function _getCouponCountInfo($user_coupon_list){
		$data = array();
		$cash_coupon_count = 0;
		$full_reduced_coupon_count = 0;
		foreach ($user_coupon_list as $user_coupon_info) {
			if ($user_coupon_info['coupon_type'] == $this->_cash_coupon) {
				$cash_coupon_count++;
			} elseif ($user_coupon_info['coupon_type'] == $this->_full_reduced_coupon) {
				$full_reduced_coupon_count++;
			}
		}
		$data['cash_coupon_count'] = $cash_coupon_count;
		$data['full_reduced_coupon_count'] = $full_reduced_coupon_count;
		return $data;
	}

	private function _filterCouponList($user_coupon_list, $lottery_id, $order_amount, $suite_id = 0, $is_cobet = 1){
		foreach ($user_coupon_list as $key => $user_coupon_info) {
			if ($suite_id) {
				// 套餐不能不用满减
				if ($user_coupon_info['user_coupon_type'] == 2) {
					unset($user_coupon_list[$key]);
					continue;
				}
			}
			
			if ($is_cobet) {
                if ($user_coupon_info['user_coupon_type'] == 2) {
                    unset($user_coupon_list[$key]);
                    continue;
                }
				if (!$user_coupon_info['coupon_is_cobet']) {
					unset($user_coupon_list[$key]);
					continue;
				}
			}
			
			if ($user_coupon_info['coupon_lottery_ids']) {
				$lottery_list = explode(',', $user_coupon_info['coupon_lottery_ids']);
				if (!in_array($lottery_id, $lottery_list)) {
					unset($user_coupon_list[$key]);
					continue;
				}
			}
			if (bccomp($order_amount, $user_coupon_info['coupon_min_consume_price']) < 0) {
				unset($user_coupon_list[$key]);
				continue;
			}
		}
		$user_coupon_list = array_values($user_coupon_list);
		return $user_coupon_list;
	}

	private function _parseWebEncryptedParams(){
		$session_code = $_REQUEST['s'];
		$this->_session_code = $session_code;
		$decrypt_params = $this->_getParamsFromPayUniqueCode($_REQUEST['t']);
		$request_params = json_decode($decrypt_params);
		$request_params->user_coupon_id = intval($_REQUEST['c']);
		ApiLog('parseParams:' . print_r($request_params, true), 'cobet');
		return $request_params;
	}

	private function _getParamsFromPayUniqueCode($pay_code){
		ApiLog('$$pay_code:' . print_r($pay_code, true), 'uni');
		if (!$pay_code) {
			$this->_redirectToFailPageAndExit();
		}
		$key = 'cobet:' . $pay_code;
		$redis_instance = Factory::createRedisObj();
		$raw_json_params = $redis_instance->get($key);
		ApiLog('$key:' . print_r($raw_json_params, true), 'uni');
		if (empty($raw_json_params)) {
			$this->_redirectToFailPageAndExit();
		}
		return $raw_json_params;
	}

	private function _redirectToFailPageAndExit(){
		$this->_redirectToFailPage($this->_msg_map['NETWORK_ERROR'], self::JUMP_TYPE_FOR_APP);
		exit();
	}

	private function _getUserEncryptKey($session_code){
		$encrypt_key = D('Session')->getEncryptKey($session_code);
		ApiLog('$encrypt_key:' . $session_code . '===' . print_r($encrypt_key, true), 'wsess');
		if (empty($encrypt_key)) {
			$this->_redirectToFailPageAndExit();
		}
		return json_decode($encrypt_key, true);
	}

	public function payOrder(){
		try {
			if ($this->_request_params->act == 10709) {
				$this->submitScheme($this->_request_params);
			} elseif ($this->_request_params->act == 10710) {
				$this->joinScheme($this->_request_params);
			}
			$this->_redirectToSuccessPage();
		} catch (\Think\Exception $e) {
			$this->_redirectToFailPage($e->getMessage(), $e->getCode());
		}
	}

	public function submitScheme($params){
		$user_info = $this->_user_info;
		$uid = $user_info['uid'];
		$verified_params = $this->verifyCobetParams($params, $user_info);
		if (!$verified_params) {
			$this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['PARAMS_ERROR']);
		}
		$lottery_id = $verified_params['lottery_id'];
		$scheme_total_amount = $verified_params['total_amount'];
		$money_pay_for_scheme = bcmul($verified_params['unit_amount'], intval($verified_params['subscribe'] + $verified_params['ensure']), 2);
		$uid = $user_info['uid'];
		// check balance
		$money_to_be_paid = $this->getRemainMoney($uid, $verified_params['user_coupon_id'], $lottery_id, $money_pay_for_scheme, $money_pay_for_scheme);
		if ($money_to_be_paid < 0) {
            $this->_throwExcepiton(self::JUMP_TYPE_FOR_RECHARGE, $this->_msg_map['LACK_OF_MONEY']);
		}
		
		$exist_scheme_info = D('CobetScheme')->getInfoByIdentity($verified_params['order_identity']);
		if ($exist_scheme_info) {
			if ($exist_scheme_info['scheme_status'] > COBET_SCHEME_STATUS_OF_NO_BEGIN) {
				$this->_throwExcepiton(self::JUMP_TYPE_FOR_RECHARGE, $this->_msg_map['LACK_OF_MONEY']);
			}
		}
		
		$scheme_sn = buildSchemeSN($uid);
		try {
			M()->startTrans();
			$scheme_id = D('CobetScheme')->addScheme($uid, $scheme_sn, $verified_params);
			
			if (isJCLottery($lottery_id)) {
				$add_jc_result = $this->addJcOrder($uid, $scheme_sn, $params);
			}
			
			$guarantee_amount = bcmul($verified_params['scheme_amount_per_unit'], $verified_params['scheme_guarantee_unit'], 2);
			$self_buy_amount = bcmul($verified_params['scheme_amount_per_unit'], $verified_params['scheme_bought_unit'], 2);
			ApiLog('$guarantee_amount:' . $guarantee_amount . '===' . $self_buy_amount . '===' . print_r($verified_params, true), 'cobet');
			$pay_result = true;
			if ($guarantee_amount || $self_buy_amount) {
				$pay_result = $this->payAndRecord($uid, $scheme_id, $verified_params['user_coupon_id'], $verified_params, $guarantee_amount, $self_buy_amount);
			}
			$update_result = $this->updateStatusForBegin($scheme_id, $verified_params);
			if ($pay_result && $update_result) {
				M()->commit();
				if (isJCLottery($lottery_id)) {
					$cobet_order_id = $add_jc_result['orderId'];
					$order_map['order_id'] = $cobet_order_id;
					$cobet_order_info = D('CobetOrder')->where($order_map)->find();
					$update_data['scheme_issue_id'] = $cobet_order_info['first_issue_id'];
					$update_data['cobet_order_id'] = $cobet_order_id;
					$scheme_map['scheme_id'] = $scheme_id;
					D('CobetScheme')->where($scheme_map)->save($update_data);
				}
				if ($verified_params['scheme_bought_unit'] == $verified_params['scheme_total_unit']) {
					$this->completeCobetScheme($scheme_id);
				}
			} else {
				M()->rollback();
				$this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['PARAMS_ERROR']);
			}
		} catch (\Think\Exception $e) {
			M()->rollback();
			throw new \Think\Exception($e->getMessage(), $e->getCode());
		}
	}

	public function joinScheme($params){
		$user_info = $this->_user_info;
		$uid = $user_info['uid'];
		$user_coupon_id = $params->coupon_id;
		$scheme_id = $params->project_id;
		$map['scheme_id'] = $params->project_id;
		$scheme_info = D('CobetScheme')->where($map)->find();
		$verified_params = $this->verifyParamsForJoin($params, $user_info, $scheme_info);
		if (!$verified_params) {
			$this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['PARAMS_ERROR']);
		}
		
		$money_pay_for_join = $verified_params['scheme_bought_amount'];
		$money_to_be_paid = $this->getRemainMoney($uid, $verified_params['user_coupon_id'], $verified_params['lottery_id'], $money_pay_for_join, $money_pay_for_join);
		if ($money_to_be_paid < 0) {
			$this->_throwExcepiton(self::JUMP_TYPE_FOR_RECHARGE, $this->_msg_map['LACK_OF_MONEY']);
		}
		
		try {
			M()->startTrans();
			$guarantee_amount = 0;
			$self_buy_amount = $verified_params['scheme_bought_amount'];
			$pay_result = true;
			if ($self_buy_amount) {
				$pay_result = $this->payAndRecord($uid, $scheme_id, $verified_params['user_coupon_id'], $verified_params, 0, $self_buy_amount);
			}
			$scheme_data['scheme_bought_unit'] = $scheme_info['scheme_bought_unit'] + $verified_params['scheme_bought_unit'];
			$scheme_data['scheme_bought_rate'] = $scheme_data['scheme_bought_unit'] / $scheme_info['scheme_total_unit'];
			if ($scheme_info['scheme_status'] == C('COBET_SCHEME_STATUS.NO_BEGIN_BOUGHT')) {
				$scheme_data['scheme_status'] = COBET_SCHEME_STATUS_OF_ONGOING;
			}
			$update_result = D('CobetScheme')->where($map)->save($scheme_data);
			if ($pay_result && $update_result) {
				M()->commit();
				if ($scheme_data['scheme_bought_unit'] == $scheme_info['scheme_total_unit']) {
					$this->completeCobetScheme($scheme_id);
				}
			} else {
				M()->rollback();
				$this->_throwExcepiton(self::JUMP_TYPE_FOR_ORDER, $this->_msg_map['PARAMS_ERROR']);
			}
		} catch (\Think\Exception $e) {
			M()->rollback();
			throw new \Think\Exception($e->getMessage(), $e->getCode());
		}
	}
}