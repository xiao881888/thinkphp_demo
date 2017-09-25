<?php
namespace H5\Controller;

use Home\Controller\SmsVerifyController;

class SendController extends BaseController{

    public function msg()
    {
        $type = (int)$this->input('type');
        $app_id = (int)$this->input('app_id');
        if (!$app_id){
            $app_id = C('APP_ID.TIGER');
        }
        $smsTempId = getSmsTempId($type,$app_id);
        if (!$smsTempId){
            $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
        }

        $verifyCode = random_string(6, 'int');
        $tel = $this->input('tel');
        
        if ($type and $tel){
            $sendSuccess = $this->_sendSmsVerifyToUser($tel, $verifyCode, $smsTempId);
            $result = D('SmsVerify')->saveVerificationSms($tel, $verifyCode, $type);
            if ($sendSuccess){
                $this->response();
            }else{
                $this->responseError(RESPONSE_ERROR_UNKNOWN,$sendSuccess['msg']);
            }
        }else{
            $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
        }


    }

    private function _sendSmsVerifyToUser($telephone, $verifyCode, $tempId){
        if( get_cfg_var('PROJECT_RUN_MODE') == 'TEST' ){
            //return true; // 测试代码
        }
        $message = array(
            $verifyCode,
            30
        );

        $data = array(
            'telephone_list' => array($telephone),
            'send_data' => $message,
            'template_id' => $tempId,
        );

        $result = sendTelephoneMsgNew($data);
        ApiLog('sms:' . print_r($result, true) . '===' . $telephone . '==' . $message . '===' . $tempId, 'h5_sms');
        $reponse = json_decode($result,true);
        return array(
            'code' => $reponse['code'],
            'msg' => $reponse['message'],
        );
    }


}