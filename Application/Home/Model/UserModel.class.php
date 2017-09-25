<?php 
namespace Home\Model;
use Home\Controller\MsgQueueOfUserController;
use Think\Model;

class UserModel extends Model {

	public function register($telephone, $password, $session = array(), $channel_info = array(), $extra_channel_info = array()){
        $salt = random_string(6);
        $data = array(
            'user_telephone'    => $telephone,
            'user_login_salt'   => $salt,
            'user_login_password' => encryptPassword($password, $salt),
            'user_register_ip'  =>get_client_ip(0,true),
            'user_register_time'=>getCurrentTime(),
        );
        
        if(!empty($channel_info)){
        	$data['user_app_package'] = $channel_info['app_package'];
        	$data['user_app_channel_id'] = $channel_info['app_channel_id'];
        	$data['user_app_os'] = $channel_info['app_os'];
        }

        if (!empty($session['device_id'])) {
            $data['user_register_device_id'] = $data['user_login_deivce_id'] = $session['device_id'];
        }
        
        if(!empty($extra_channel_info)){
        	$data['channel_type'] = $extra_channel_info['channel_type'];
        	$data['extra_channel_id'] = $extra_channel_info['extra_channel_id'];
        }

        $uid = $this->add($data);
        return $uid;
    }

    public function updateUserLoginDevice($uid, $session){
        $map = array(
            'uid' => $uid
            );

        $data = array(
            'user_login_deivce_id' => $session['device_id']
            );
        
        return $this->where($map)->save($data);
    }
    
    
    public function getBankCardInfo($uid) {
        $condition = array('uid'=>$uid);
        return $this->field('user_bank_card_type, user_bank_card_address,
                            user_bank_card_account_name, user_bank_card_number, user_bank_card_status')
                    ->where($condition)
                    ->find();
    }
    
    
    public function setLoginPassword($uid, $password) {
        $condition = array('uid'=>$uid);
        $loginSalt = random_string(6);
        $data = array(
            'user_login_salt'   => $loginSalt,
            'user_login_password' => encryptPassword($password, $loginSalt),
        );
        return $this->where($condition)
                    ->save($data);
    }
    
    
    public function setPaymentPassword($uid, $password) {
        $condition = array('uid'=>$uid);
        $paymentSalt = random_string(6);
        $data = array(
            'user_payment_salt'   => $paymentSalt,
            'user_payment_password' => encryptPassword($password, $paymentSalt),
        );
        return $this->where($condition)
                    ->save($data);
    }
    
    
    public function checkUserPassword($uid, $password) {
        $condition = array('uid'=>$uid);
        $passwordInfo = $this   ->field('user_login_password, user_login_salt')
                                ->where($condition)
                                ->find();
        return ( $passwordInfo['user_login_password'] == encryptPassword($password, $passwordInfo['user_login_salt']) );
    }
    
    
    public function checkPayPassword($uid, $password, $payPassword, $paySalt) {
        return ( $payPassword == encryptPassword($password, $paySalt) );
    }
    
    
    public function getUserId($telephone) {
        $condition = array('user_telephone'=>$telephone);
        return $this->where($condition)
                    ->getField('uid');
    }
    
    
    public function saveBankCardInfo($uid, $typeName, $address, $identityNumber, $name) {
        $condition = array('uid'=>$uid);
        $data = array(
            'user_bank_card_type' => $typeName,
            'user_bank_card_address' => $address,
            'user_bank_card_number' => $identityNumber,
            'user_bank_card_account_name' =>$name,
        );
        return $this->where($condition)
                    ->save($data);
    }
    
    
    public function getUserTelephone($uid) {
        $condtion = array('uid'=>$uid);
        return $this->where($condtion)
                    ->getField('user_telephone');
    }
    
    
    public function setFreePasswordInfo($uid, $orderLimit, $dayLimit) {
        $condtion = array('uid'=>$uid);
        $data = array(
            'user_pre_order_limit' => $orderLimit,
            'user_pre_day_limit' => $dayLimit,
        );
        return $this->where($condtion)
                    ->save($data);
    }
    
    
    public function saveIdentityCard($uid, $realname, $number) {
        $condition = array('uid'=>$uid);
        $data = array(
            'user_real_name' => trim($realname),
            'user_identity_card' => $number,
            'user_identity_card_status' => C('IDENTITY_CARD_STATUS.VERIFY')
        );
        return $this->where($condition)
                    ->save($data);
    }
    
    
    public function swithFreePassword($uid, $switch) {
        $condtion = array('uid'=>$uid);
        $data = array('user_password_free'=>$switch);
        return $this->where($condtion)
                    ->save($data);
    }
    
    
    public function getUserInfo($uid) {
        $condtion = array('uid'=>$uid);
        return $this->where($condtion)
                    ->find();
    }

	public function queryUserInfoByPhone($user_phone){
		$map['user_telephone'] = $user_phone;
		return $this->where($map)->find();
	}

    public function countUserByIdCard($id_card){
        $map['user_identity_card'] = $id_card;
        return $this->where($map)->count();
    }

    public function countUserByBankNumber($bank_num){
        $map['user_bank_card_number'] = $bank_num;
        return $this->where($map)->count();
    }

    public function setUserAvatar($uid,$user_avatar){
        $map['uid'] = $uid;
        return $this->where($map)->setField('user_avatar',$user_avatar);
    }

    public function setUserNickName($uid,$nick_name){
        $map['uid'] = $uid;
        return $this->where($map)->setField('user_nick_name',$nick_name);
    }
}


?>