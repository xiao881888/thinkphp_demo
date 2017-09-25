<?php
require_once('HttpClient.php');
require_once('RequestHandler.class.php');
require_once('ResponseHandler.class.php');
require_once('Utils.class.php');
require_once('Props.php');

class ZWXHandler {

	private $resHandler;
	private $reqHandler;
	private $pay;
	private $props;

	public function __construct($config){
        $this->resHandler = new ResponseHandler();
        $this->reqHandler = new RequestHandler();
        $this->pay = new HttpClient();
        $this->props = new Props();
        $this->props->setConfig($config);
        $this->reqHandler->setKey($this->props->K('SIGN_KEY'));

        return $this;
	}

	public function H5Pay($data=array()){
        $this->reqHandler->setReqParams($data);
        $this->reqHandler->setParameter('mch_id',$this->props->K('MCH_ID'));//必填项，商户号，由梓微兴分配
        $this->reqHandler->setParameter('nonce_str',mt_rand(time(),time()+rand()));//
        $this->reqHandler->createSign();
        $data = Utils::to($this->reqHandler->getAllParameters());
        $this->pay->setReqContent($this->props->K('PAY_URL'),$data);
        $resp = $this->pay->invoke();
        ApiLog('prepay resp:'.$resp, 'zwx_recharge');
        if($resp){
            $resp = simplexml_load_string($this->pay->getResContent());
            if ($resp->result_code == 'SUCCESS' && $resp->return_code == 'SUCCESS') {
            	return strval($resp->prepay_url);
            } else {
            	return false;
            }
        }else{
        	return false;
        }
	}
	
	public function buildParamsForSdk($data=array()){
		$this->reqHandler->setReqParams($data);
		$this->reqHandler->setParameter('mch_id',$this->props->K('MCH_ID'));//必填项，商户号，由梓微兴分配
		$this->reqHandler->setParameter('nonce_str',mt_rand(time(),time()+rand()));//
		$this->reqHandler->createSign();
		$data = Utils::to($this->reqHandler->getAllParameters());
		$this->pay->setReqContent($this->props->K('PAY_URL'),$data);
		$resp = $this->pay->invoke();
		ApiLog('prepay resp:'.$resp, 'zwx_sk');
		if($resp){
			$resp = simplexml_load_string($this->pay->getResContent());
			ApiLog('prepay resp:'.print_r($resp,true), 'zwx_sk');
				
			if ($resp->result_code == 'SUCCESS' && $resp->return_code == 'SUCCESS') {
				$response['prepay_id'] = $resp->prepay_id;
				$response['package_json'] = $resp->package_json;
				return $response;
			} else {
				return false;
			}
		}else{
			return false;
		}
	}
	
	public function buildParamsForQrCode($data=array()){
		$this->reqHandler->setReqParams($data);
		$this->reqHandler->setParameter('mch_id',$this->props->K('MCH_ID'));//必填项，商户号，由梓微兴分配
		$this->reqHandler->setParameter('nonce_str',mt_rand(time(),time()+rand()));//
		$this->reqHandler->createSign();
		$data = Utils::to($this->reqHandler->getAllParameters());
		$this->pay->setReqContent($this->props->K('PAY_URL'),$data);
		$resp = $this->pay->invoke();
		ApiLog('prepay resp:'.$resp, 'zwx_sk');
		if($resp){
			$resp = simplexml_load_string($this->pay->getResContent());
			ApiLog('prepay resp:'.print_r($resp,true), 'zwx_sk');
	
			if ($resp->result_code == 'SUCCESS' && $resp->return_code == 'SUCCESS') {
				$response['prepay_id'] = $resp->prepay_id;
				$response['code_url'] = $resp->code_url;
				$response['package_json'] = $resp->package_json;
				return $response;
			} else {
				return false;
			}
		}else{
			return false;
		}
	}

	public function parseResp($string){
		$data = Utils::parse($string);
		if (!empty($data) && is_array($data) && $this->isRightSign($data)) {
			return $data;
		} else {
			return false;
		}

	}

	private function isRightSign($data){
		$sign_paramers = "";
		ksort($data);
		foreach($data as $k => $v) {
			if($k != 'sign' && $v != '') {
				$sign_paramers .= $k . "=" . $v . "&";
			}
		}
		$sign_paramers .= "key=" . $this->props->K('SIGN_KEY');
		$sign = strtoupper(md5($sign_paramers));
		
		return $sign == $data['sign'];
	}
}