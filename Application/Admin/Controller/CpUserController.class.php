<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-4
 * @author tww <merry2014@vip.qq.com>
 */
class CpUserController extends GlobalController{

	public function index(){
		$where = $this->_getSearchCondition();

		$user_keyword = I('user_keyword');
		if ($user_keyword) {
			if (is_numeric($user_keyword)) {
				$where['user_telephone'] = $user_keyword;
			} else {
				$where['user_real_name'] = $user_keyword;
			}
		}

		$app_channel_id = I('app_channel_id');
		if ($app_channel_id !== '') {
			$app_channel = explode('__', $app_channel_id);
			$where['user_app_os'] = $app_channel[0];
			$where['user_app_channel_id'] = $app_channel[1];
		}

		$user_register_time_start = I('user_register_time_start');
		if ($user_register_time_start) {
			$where['user_register_time'] = array('egt', $user_register_time_start);
		}

		$user_register_time_end = I('user_register_time_end');
		if ($user_register_time_end) {
			if ($user_register_time_start) {
				$where['user_register_time'] = array('between', array($user_register_time_start, $user_register_time_end));
			} else {
				$where['user_register_time'] = array('lt', $user_register_time_end);
			}
		}
		
		$list = $this->lists(D('CpUser'), $where);
		$list = reindexArr($list, 'uid');
		
		if ($list) {
			$uids = extractArrField($list, 'uid');
			$user_accounts = M('UserAccount')->where(array('uid'=>array('IN', $uids)))->select();
			$user_orders = D('Order')->getUserConsume($uids);

			$user_integral = D('UserIntegral')->where(array('uid'=>array('IN', $uids)))->select();
			$user_integral = reindexArr($user_integral, 'uid');

		} else {
			$user_accounts = array();
			$user_integral = array();
		}
		$user_accounts = reindexArr($user_accounts, 'uid');

		$channels = D('Channel')->getChannelMap();
		$this->assign('channels', $channels);

		$salers = D('Saler')->getField('saler_id, saler_name');
		$this->assign('salers', $salers);

		$user_channels = $this->_buildUserChannels();

		$this->assign('user_channels', $user_channels);
		$this->assign('list', $list);
		$this->assign('user_accounts', $user_accounts);
		$this->assign('user_orders', $user_orders);
		$this->assign('user_integral', $user_integral);
		$this->display();
	}

	public function resetPw(){
		$uid = I('uid');
		$new_password = random_string(6,'int');
		$result = D('CpUser')->resetPw($uid, $new_password);
		if($result){
			$user_info 		= D('User')->getUserInfo($uid);
			$message_data 	= array(
				$new_password
				);

			sendTemplateSMS($user_info['user_telephone'], $message_data, $this->_getSmsTemId($user_info));
			$this->success('重置成功！已将新密码发送至用户手机！');
		}else{
			$this->error('重置失败！');
		}
	}

    private function _getSmsTemId($user_info){
        $app_id = getRegAppId($user_info);
        if($app_id == C('APP_ID_LIST.TIGER')){
            return C('ADMIN_SMS_TEMPLTE_ID.RESET_PASSWORD_MESSAGE');
        }elseif($app_id == C('APP_ID_LIST.BAIWAN')){
            return C('ADMIN_BAIWAN_SMS_TEMPLTE_ID.RESET_PASSWORD_MESSAGE');
        }elseif($app_id == C('APP_ID_LIST.NEW')){
            return C('ADMIN_NEW_SMS_TEMPLTE_ID.RESET_PASSWORD_MESSAGE');
        }
    }
	
	public function passIdCard($uid){
		$identity_card = D('CpUser')->checkIdentityCard($uid);
		if(empty($identity_card)){
			$this->error('请先设置身份证号码！');
		}
		$result = D('CpUser')->passIdentityCard($uid);
		if($result !== false){
			$this->success('操作成功！');
		}else{
			$this->error('操作失败！');
		}
	}
	
	public function passBankCard($uid){
		$bank_card = D('CpUser')->checkBankCard($uid);
		if(empty($bank_card)){
			$this->error('请先设置银行卡号码！');
		}
		$is_equal_username = D('CpUser')->checkUserName($uid);
		if(!$is_equal_username){
			$this->error('账号和银行卡用户名不相符！');
		}
		$result = D('CpUser')->passBankCard($uid);
		if($result !== false){
			$this->success('操作成功！');
		}else{
			$this->error('操作失败！');
		}
	}

	public function before_edit(){
		$salers = M('Saler')->select();
		$this->assign('salers', $salers);

		$channels = M('Channel')->select();
		$this->assign('channels', $channels);

		$banks = M('Bank')->getField('bank_name', true);
		$this->assign('banks', $banks);
	}

	public function editBase(){
		if (IS_POST) {
			unset($_REQUEST['user_vip']);
			unset($_POST['user_vip']);
			unset($_GET['user_vip']);
			unset($_REQUEST['user_rebate']);
			unset($_POST['user_rebate']);
			unset($_GET['user_rebate']);
			unset($_REQUEST['channel_id']);
			unset($_POST['channel_id']);
			unset($_GET['channel_id']);
			unset($_REQUEST['saler_id']);
			unset($_POST['saler_id']);
			unset($_GET['saler_id']);
			parent::edit();
		} else {
			$banks = M('Bank')->getField('bank_name', true);
			$this->assign('banks', $banks);			
			
			$this->setJumpPage('edit_base');
			parent::edit();
		}
	}

	private function _buildUserChannels(){
		$channels = getUserAppChannels();
		$result = array();

		foreach ($channels['1'] as $channel) {
			$result['1__'.$channel['app_channel_id']] = $channel['app_name'];
		}

		foreach ($channels['2'] as $channel) {
			$result['2__'.$channel['app_channel_id']] = $channel['app_name'];
		}

		return $result;
	}
}