<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-4
 * @author tww <merry2014@vip.qq.com>
 */
class CpUserModel extends Model{
	protected $tableName = 'user';

    /* 自动完成规则 */
    protected $_auto = array(
        array('user_bank_card_status', 'resetUserBankCardStatus', self::MODEL_UPDATE, 'callback'),
        array('user_identity_card_status', 'resetUserIdentityCardStatus', self::MODEL_UPDATE, 'callback'),
    );

    public function resetUserBankCardStatus(){
    	if ($_REQUEST['user_bank_card_number'] == '') {
    		return 0;
    	}else{
    	    return 1;
        }
    }

    public function resetUserIdentityCardStatus(){
    	if ($_REQUEST['user_identity_card'] == '') {
    		return 0;
    	}else{
    	    return 1;
        }
    }
	
	public function getStatusFieldName(){
		return 'user_status';
	}

	public function getReadOnlyField(){
		return array('user_telephone');
	}
	
	public function getInfo($uid){
		$where = array();
		$where['uid'] = $uid;
		return $this->where($where)->find();
	}
	
	public function resetPw($uid, $password) {
		$salt = random_string(6);
		$data = array(
				'uid'    				=> $uid,
				'user_login_salt'   	=> $salt,
				'user_login_password' 	=> encryptPassword($password, $salt),
		);
		return $this->save($data);
	}
	
	public function checkIdentityCard($uid){
		$where = array();
		$where['uid'] = $uid;
		return $this->where($where)->getField('user_identity_card');
	}
	
	public function checkBankCard($uid){
		$where = array();
		$where['uid'] = $uid;
		return $this->where($where)->getField('user_bank_card_number');
	}
	
	public function passIdentityCard($uid){
		$where = array();
		$where['uid'] = $uid;
		$pass_status = CARD_STATUS_VERIFIED;
		$data = array();
		$data['user_identity_card_status'] = $pass_status;	
		return $this->where($where)->save($data);
	}
	
	public function checkUserName($uid){
		$where = array();
		$where['uid'] = $uid;
		$field = array('user_real_name', 'user_bank_card_account_name');
		$user = $this->where($where)->find($field);
		$is_pass = false;
		if($user['user_real_name'] && $user['user_bank_card_account_name']){
			if($user['user_real_name'] == $user['user_bank_card_account_name']){
				$is_pass = true;
			}
		}
		return $is_pass;
	}
	
	public function passBankCard($uid){
		$where = array();
		$where['uid'] = $uid;
		$pass_status = CARD_STATUS_VERIFIED;
		$data = array();
		$data['user_bank_card_status'] = $pass_status;
		return $this->where($where)->save($data);
	}
}