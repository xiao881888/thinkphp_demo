<?php

namespace Home\Controller;

use Home\Controller\GlobalController;

class OrderNoticeController extends GlobalController{
	private $_method_map = array();
	public function __construct(){

		$this->_method_map = array(
			ORDER_NOTICE_TYPE_OF_WIN_PRIZE => '_doWinPrizeAction',
			ORDER_NOTICE_TYPE_OF_FAIL_TO_BUY => '_doFailToBuyTicketAction',
			ORDER_NOTICE_TYPE_OF_FAIL_TO_FOLLOW => '_doFailToFollowAction',
			ORDER_NOTICE_TYPE_OF_FAIL_TO_PRINTOUT_TICKET => '_doFailToPrinoutTicketAction',
			ORDER_NOTICE_TYPE_OF_SUCCESS_TO_PRINTOUT_TICKET => '_doSuccessToPrinoutTicketAction',
				
		);

		parent::__construct();
	}
	
	public function index(){
		$request_params = $this->_parseRequestParams();
		
		if($request_params){
			$method_name = $this->_method_map[$request_params['type']];
			if(method_exists($this,$method_name)){
				$this->$method_name($request_params);
			}
		}else{
			$this->ajaxReturn(array('code'=>1), 'JSON');
		}
		
	}
	
	private function _verifyRequestParams($request_params){
		if(!isset($this->_method_map[$request_params['type']])){
			return false;
		}
		if(!$request_params['order_id']){
			return false;
		}
		if($request_params['type']==ORDER_NOTICE_TYPE_OF_FAIL_TO_PRINTOUT_TICKET){
			if(!$request_params['ticket_seq']){
				return false;
			}
		}
		return true;
	}
	
	private function _doFailToPrinoutTicketAction($request_params){
		A('Push')->pushOrderNotice($request_params);
	}
	
	private function _doWinPrizeAction($request_params){
		$order_info = $request_params['order_info'];
		A('PlusAward')->doOrderPlusAward($order_info);
		A('Push')->pushOrderNotice($request_params);
		//A('StopFollowBet')->prizeStopFollowBet($order_info);
	}
	
	private function _doFailToBuyTicketAction($request_params){
		A('Push')->pushOrderNotice($request_params);
	}
	
	private function _doFailToFollowAction($request_params){
		A('Push')->pushOrderNotice($request_params);
	}

	private function _doSuccessToPrinoutTicketAction($request_params){
		// $order_id = $request_params['order_id'];
		// A('MsgQueueOfOrder')->notifyOrderNotice($order_id);
		// A('GameActivity')->runActivity(json_encode(array('order_id'=>$order_id)));
	}
	
	private function _parseRequestParams(){
		$request_params['type'] = intval($_REQUEST['type']);
		$request_params['order_id'] = intval($_REQUEST['orderId']);
		$request_params['ticket_seq'] = intval($_REQUEST['ticketSeq']);

		$param_is_valid = $this->_verifyRequestParams($request_params);

		if(!$param_is_valid){
			return false;
		}
		
		$order_info = D('Order')->getOrderInfo($request_params['order_id']);

		if(empty($order_info)){
			return false;
		}
		$request_params['order_info'] = $order_info;
		return $request_params;
	}
}

