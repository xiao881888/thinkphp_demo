<?php 

namespace Home\Controller;
use Home\Controller\GlobalController;
use Think\Verify;

class LargeRechargeController extends GlobalController {

    const SUCCESS_STATUS = 0;
    const FAIL_STATUS = 1;
    const rechargeStatusOfNoDeal = 1;

    const CUSTOMER_SERVICE_TEL1 = 18105918970;
    const CUSTOMER_SERVICE_TEL2 = 18101010101;
    const CUSTOMER_SERVICE_TEL3 = 15959595959;
    const CUSTOMER_SERVICE_TELS = array('18105918970','13665002605','18105919206');

    const IP_DEBUG = false;

    const USER_IS_EMPTY = '用户信息为空，用户未登录';
    const TELE_IS_VAILD = '手机格式不正确';
    const MONEY_LESS_50000 = '充值金额不正确';
    const DATA_ERROR = '数据异常,请联系管理员';
    const TIME_NOT_ALLOW = '请在9点到22点之间进行充值';
    const VERIFY_NOT_RIGHT = "验证码错误";

    const MSG_REQUEST_URL = 'http://push-service.tigercai.com/index.php?s=/Home/Sms/sendSms';

    public function index(){

        if(self::IP_DEBUG){
            if(!$this->_checkIP())die;
        }

        $post_url = $this->_assignBuildUrl();
        $this->display();
    }

    /* 生成验证码 */
     public function verify()
     {
         $config = [
             'fontSize' => 19, // 验证码字体大小
             'length' => 4, // 验证码位数
             'imageH' => 34,
             'codeSet'   =>  '1234567890',
         ];
         $Verify = new Verify($config);
         $Verify->entry();
     }

    private function _checkIP(){
        $ip = '110.83.28.97';
        $client_ip = get_client_ip(0, true);
        if($client_ip == $ip){
            return true;
        }
        return false;
    }

    public function success(){
        $contacts_tel = session('contacts_tel');
        $this->assign('contacts_tel',$contacts_tel);
        $this->display();
    }

    private function _assignBuildUrl(){

        //$post_url = 'http://'.$_SERVER['SERVER_ADDR'].':'.$_SERVER['SERVER_PORT'].'/index.php?s='.U('postLargeRechargeApply');
        $post_url = 'http://'.$_SERVER['HTTP_HOST'].U('postLargeRechargeApply');
        $this->assign('post_url',$post_url);

        //$get_userid_url = 'http://'.$_SERVER['SERVER_ADDR'].':'.$_SERVER['SERVER_PORT'].'/index.php?s='.U('autoLoginForClient');
        $get_userid_url = 'http://'.$_SERVER['HTTP_HOST'].U('autoLoginForClient');
        $this->assign('get_userid_url',$get_userid_url);

        //$verify_url = 'http://'.$_SERVER['SERVER_ADDR'].':'.$_SERVER['SERVER_PORT'].'/index.php?s='.U('verify');
        /*$verify_url = 'http://'.$_SERVER['SERVER_NAME'].U('verify');
        $this->assign('verify_url',$verify_url);*/

    }

    public function autoLoginForClient(){
        $data = array();
        $encrypt_str = I('encrypt_str', '');
        if(empty($encrypt_str)){
            $data['error'] = self::FAIL_STATUS;
            $this->ajaxReturn($data);
        }

        $sessionArr = decryptRsa($encrypt_str);
        $sessionArr = explode('_',$sessionArr);
        $userSession = $sessionArr[1];

        $userInfo 	= $this->getAvailableUser($userSession);

        if(!empty($userInfo)){
            $user_id = $userInfo['uid'];
            $user_name = $userInfo['user_real_name'];
            $user_tel = $userInfo['user_telephone'];
            $data['error'] = self::SUCCESS_STATUS;
            $data['user_id'] = $user_id;
            $data['user_name'] = $user_name;
            $data['user_tel'] = $user_tel;
        }else{
            $data['error'] = self::FAIL_STATUS;
        }
        $this->ajaxReturn($data);
    }

    public function postLargeRechargeApply(){
        $data = array();

        if(!$this->_checkTime()){
            $data['error'] = self::FAIL_STATUS;
            $data['info'] = self::TIME_NOT_ALLOW;
            $this->ajaxReturn($data);
        }

        /*$verify = I('verify','');
        if(!$this->check_verify($verify)){
            $data['error'] = self::FAIL_STATUS;
            $data['info'] = self::VERIFY_NOT_RIGHT;
            $this->ajaxReturn($data);
        }*/

        $user_id = I('user_id',0);
        if(empty($user_id)){
            $data['error'] = self::FAIL_STATUS;
            $data['info'] = self::USER_IS_EMPTY;
            $this->ajaxReturn($data);
        }
        $user_info = D('User')->getUserInfo($user_id);
        if(empty($user_info)){
            $data['error'] = self::FAIL_STATUS;
            $data['info'] = self::USER_IS_EMPTY;
            $this->ajaxReturn($data);
        }
        $user_name = $user_info['user_real_name'];

        $contacts = I('contacts','');

        $contacts_tel = I('contacts_tel','');
        $is_tel = $this->_checkTelFormat($contacts_tel);
        if ($is_tel === false) {
            $data['error'] = self::FAIL_STATUS;
            $data['info'] = self::TELE_IS_VAILD;
            $this->ajaxReturn($data);
        }

        $recharge_amount = I('recharge_amount',0);
        if(!$this->_checkRechargeAmountFormat($recharge_amount)){
            $data['error'] = self::FAIL_STATUS;
            $data['info'] = self::MONEY_LESS_50000;
            $this->ajaxReturn($data);
        }

        $recharge_remark = I('recharge_remark','');
        $order_sn = $this->_buildOrderSku($user_id);

        $addStatus = $this->_addLargeRechargeRecord($order_sn,$user_id,$user_name,$contacts,$contacts_tel,$recharge_amount,$recharge_remark);
        if(!$addStatus){
            $data['error'] = self::FAIL_STATUS;
            $data['info'] = self::DATA_ERROR;
            $this->ajaxReturn($data);
        }

        $this->_sendMsg($recharge_amount,$contacts_tel);

        $data['error'] = self::SUCCESS_STATUS;
        $this->ajaxReturn($data);
    }

    /* 验证码校验 */
     public function check_verify($code, $id = '')
     {
         $verify = new \Think\Verify();
         return $verify->check($code, $id);
     }

    private function _checkTime(){
        $now_hour = date('H', time());
        if($now_hour >= 9 && $now_hour < 22){
            return true;
        }
        return false;
    }

    private function _sendMsg($recharge_amount,$contacts_tel){
        $telephone = array(self::CUSTOMER_SERVICE_TEL1,self::CUSTOMER_SERVICE_TEL2,self::CUSTOMER_SERVICE_TEL3);

        $telephone = self::CUSTOMER_SERVICE_TELS;

        $data[] = getCurrentTime();
        $data[] = $recharge_amount;
        $data[] = $contacts_tel;

        $send_data['phone'] = json_encode($telephone);
        $send_data['temp_type'] = 120244;
        $send_data['datas'] = json_encode($data);
        $result = requestByCurl(self::MSG_REQUEST_URL,$send_data);
    }

    private function _addLargeRechargeRecord($order_sn,$user_id,$user_name,$contacts,$contacts_tel,$recharge_amount,$recharge_remark=''){
        $data = array();
        $data['lra_order_sn'] = $order_sn;
        $data['lra_uid'] = $user_id;
        $data['lra_user_name'] = $user_name;
        $data['lra_contacts'] = $contacts;
        $data['lra_contacts_tel'] = $contacts_tel;
        $data['lra_recharge_amount'] = $recharge_amount;
        $data['lra_recharge_remark'] = $recharge_remark;
        $data['lra_recharge_createtime'] = getCurrentTime();
        $data['lra_recharge_status'] = self::rechargeStatusOfNoDeal;
        return M('large_recharge')->add($data);
    }

    private function _checkRechargeAmountFormat($recharge_amount){
        return in_array($recharge_amount,array(5000, 10000, 30000,50000,80000,100000,150000,200000));
    }

    private function _checkTelFormat($tel){
        $match = "/1[34578]{1}\d{9}$/";
        if(empty($tel)){
            return false;
        }
        return preg_match($match, $tel);
    }

    private function _buildOrderSku($user_id) {
        $randomStr = strtoupper(random_string(5));
        return 'DE'.date('ymdhis').$user_id.$randomStr;
    }


}

