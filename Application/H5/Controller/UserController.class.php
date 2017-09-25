<?php
namespace H5\Controller;

use Home\Model\UserAccountModel;
use Home\Model\UserCouponModel;
use Home\Model\UserModel;

class UserController extends BaseController{

    protected $extra_channel_info = array(
        'channel_type' => 3,
        'extra_channel_id' => 1,
    );

    protected $channel_type_list = array(3,4);

    public function index()
    {
        $model = new UserModel();
        $user_info = $model->getUserInfo($this->uid);
        $user_account = (new UserAccountModel())->getUserAccount($this->uid);
        $user_coupon = (new UserCouponModel())->sumUserCouponBalance($this->uid);
        $user_interger = $this->_getUserIntegralInfo();

        $this->response(array(
            'username' => (string) !empty($user_info['user_name']) ? $user_info['user_name'] : hiddenMobile($user_info['user_telephone']),
            'avatar' => (string)$user_info['user_avatar'],
            'balance' => (float)$user_account['user_account_balance'],
            'coupon_balance' => (float)$user_coupon,
            'points' => (int)$user_interger['user_integral'],
            'user_level_name' => (string)$user_interger['user_level_name'],
            'user_level_img' => (string)$user_interger['user_level_img'],
            'user_exp' => (int)$user_interger['user_exp'],
            'next_level_exp' => (int)$user_interger['next_level_exp'],
            'next_level_name' => (string)$user_interger['next_level_name'],
            'next_level_img' => (string)$user_interger['next_level_img'],
        ));
    }

    public function register()
    {
        $tel = $this->input('tel');
        $password = $this->input('passwd');
        $sms_code = $this->input('sms_validation');

        $verifyResult = A('Home/SmsVerify')->checkVerificationCode($tel, $sms_code, C('SMS_TYPE.REGISTER'));
        $this->_validSmsCode($verifyResult);
        $model = new UserModel();
        $uid = $model->getUserId($tel);
        if ($uid){
            $this->responseError(RESPONSE_ERROR_TEL_BE_REGISTER);
        }
        //$new_uid = $model->register($tel,$password,'');
        if (intval($this->input('channel_type')) and intval($this->input('channel_id'))){
            if (!in_array($this->input('channel_type'),$this->channel_type_list)){
                $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
            }
            $channel_info = array(
                'channel_type' => intval($this->input('channel_type')),
                'extra_channel_id' => intval($this->input('channel_id')),
            );
        }else{
            $channel_info = $this->extra_channel_info;
        }
        $new_uid = (new \Home\Controller\UserController())->registerCommonForH5($tel,$password,'','',$channel_info);
        if (!$new_uid){
            $this->responseError(RESPONSE_ERROR_UNKNOWN);
        }
        $addUserAccount = D('UserAccount')->addUserAccount($new_uid);
        $this->_successLoginAction($new_uid);
    }

    public function login()
    {
        $tel = $this->input('tel');
        $password = $this->input('passwd');

        if (!$tel or !$password){
            $this->responseError(RESPONSE_ERROR_PARAM_FAILS);
        }

        $model = new UserModel();
        $uid = $model->getUserId($tel);
        if (!$uid){
            $this->responseError(RESPONSE_ERROR_MOBILE_OR_PASSWORD_FALIS);
        }

        $valid_pass = $model->checkUserPassword($uid,$password);
        if (!$valid_pass){
            $this->responseError(RESPONSE_ERROR_MOBILE_OR_PASSWORD_FALIS);
        }

        $user_info = $model->getUserInfo($uid);
        if ($user_info['user_status'] == C('USER_STATUS.DISABLE')) {
            $this->responseError(RESPONSE_ERROR_USER_FORBIDDEN);
        }

        $this->_successLoginAction($uid);
    }

    public function resetPassword()
    {
        $tel = $this->input('tel');
        $password = $this->input('passwd');
        $sms_code = $this->input('sms_validation');

        $model = new UserModel();
        $uid = $model->getUserId($tel);

        if (!$uid){
            $this->responseError(RESPONSE_ERROR_WITHOUT_USER);
        }

        $verifyResult = A('Home/SmsVerify')->checkVerificationCode($tel, $sms_code, C('SMS_TYPE.FIND_LOGIN_PWD'));
        $this->_validSmsCode($verifyResult);

        $update = $model->setLoginPassword($uid, $password);

        if ($update){
            $this->response();
        }else{
            $this->responseError(RESPONSE_ERROR_UNKNOWN);
        }

    }

    public function logout()
    {
        D('Cookies')->updateCookie($this->uid,true);
        $this->response();
    }

    private function _validSmsCode($verifyResult)
    {
        if (!$verifyResult['inLifetime']){
            $this->responseError(RESPONSE_ERROR_MESSAGE_CODE_EXPIRED);
        }

        if (!$verifyResult['equal']){
            $this->responseError(RESPONSE_ERROR_MESSAGE_CODE_FAILS);
        }
    }

    private function _successLoginAction($uid)
    {
        $token = D('Cookies')->updateCookie($uid);
        setcookie('user_token',$token,C('COOKIE_CACHE_DAYS') * 24 * 3600);

        $this->response(array(
            'status' => true,
            'user_token' => (string)$token,
        ));
    }

    private function _getUserIntegralInfo(){
        $data['uid'] = $this->uid;
        $request_data['data'] = json_encode($data);
        $request_data['act_code'] = C('INTEGRAL_ACT.USER_INTEGRAL_INFO');
        H5Log('request:'.print_r($request_data,true),'h5_integral');
        $response_data = requestUserIntegral($request_data);
        H5Log('response:'.print_r($response_data,true),'h5_integral');
        return $response_data['data'];
    }
}