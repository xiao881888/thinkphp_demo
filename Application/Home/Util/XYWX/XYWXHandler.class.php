<?php
require('Utils.class.php');
require('RequestHandler.class.php');
require('ClientResponseHandler.class.php');
require('PayHttpClient.class.php');
class XYWXHandler{
	private $_pre_pay_url;
	private $_key;
	private $_mch_id;
	private $_version;
	
	private $resHandler = null;
	private $reqHandler = null;
	private $pay = null;

	public function __construct($config){
		ApiLog('xywx config:' . print_r($config, true), 'xywx');
		$this->_pre_pay_url = $config['pre_pay_url'];
		$this->_key = $config['key'];
		$this->_mch_id = $config['mch_id'];
		
		$this->resHandler = new ClientResponseHandler();
		$this->reqHandler = new RequestHandler();
		$this->pay = new PayHttpClient();
		
		$this->reqHandler->setGateUrl($this->_pre_pay_url);
		$this->reqHandler->setKey($this->_key);
	}

	public function buildParamsForSdk($request_data){
		if(!$request_data){
			return false;
		}
		foreach($request_data as $param_key=>$param_value){
			$this->reqHandler->setParameter($param_key,$param_value);
		}
		$this->reqHandler->setParameter('service','unified.trade.pay');//接口类型：pay.weixin.native
		$this->reqHandler->setParameter('mch_id',$this->_mch_id);//必填项，商户号，由威富通分配
		$this->reqHandler->setParameter('limit_credit_pay','0');   //是否支持信用卡，1为不支持，0为支持
		$this->reqHandler->setParameter('nonce_str',mt_rand(time(),time()+rand()));//随机字符串，必填项，不长于 32 位
		$this->reqHandler->createSign();//创建签名
		
		$data = Utils::toXml($this->reqHandler->getAllParameters());
		//var_dump($data);
		ApiLog('xywx $data:' . print_r($data, true), 'xywx');
		
		$this->pay->setReqContent($this->reqHandler->getGateURL(),$data);
		if($this->pay->call()){
			$this->resHandler->setContent($this->pay->getResContent());
			$this->resHandler->setKey($this->reqHandler->getKey());
			if($this->resHandler->isTenpaySign()){
				//当返回状态与业务结果都为0时才返回支付二维码，其它结果请查看接口文档
				if($this->resHandler->getParameter('status') == 0 && $this->resHandler->getParameter('result_code') == 0){
					$response['token_id'] =  $this->resHandler->getParameter('token_id');
					$response['services'] =  $this->resHandler->getParameter('services');
					return $response;
				}else{
					ApiLog('status =0 error:' . 'Error Code:'.$this->resHandler->getParameter('err_code').' Error Message:'.$this->resHandler->getParameter('err_msg'), 'xywx');
				}
			}
			ApiLog('xywx $this->pay->getResContent():' . print_r($this->pay->getResContent(), true), 'xywx');
			ApiLog('xywx $this->resHandler->debugInfo():' . print_r($this->resHandler->getDebugInfo(), true), 'xywx');
			ApiLog('status !=0  error:' . 'Error Code:'.$this->resHandler->getParameter('status').' Error Message:'.$this->resHandler->getParameter('message'), 'xywx');
		}else{
			ApiLog('call else error:' . 'Response Code:'.$this->pay->getResponseCode().' Error Info:'.$this->pay->getErrInfo(), 'xywx');
		}
		return false;
	}
	
	public function parseNotifyData($xml){
		$this->resHandler->setContent($xml);
		$this->resHandler->setKey($this->_key);
		if($this->resHandler->isTenpaySign()){
			if($this->resHandler->getParameter('status') == 0 && $this->resHandler->getParameter('result_code') == 0){
				return $this->resHandler->getAllParameters();
			}
		}
		return false;
	}
}