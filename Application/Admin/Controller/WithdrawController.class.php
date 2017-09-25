<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-4
 * @author tww <merry2014@vip.qq.com>
 */
class WithdrawController extends GlobalController{

	private $_lianliandaifu_bank_2_my_bank;

	private $_baofu_bank_2_my_bank;

	public function _initialize(){
		parent::_initialize();

		$this->_lianliandaifu_bank_2_my_bank = array(
			'中国银行' 		=> '中国银行',
			'中国农业银行' 	=> '中国农业银行',
			'中国工商银行' 	=> '中国工商银行',
			'中国建设银行' 	=> '中国建设银行',
			'招商银行' 		=> '招商银行',
			'中国光大银行' 	=> '中国光大银行',
			'浦发银行' 		=> '上海浦东发展银行',
			'交通银行' 		=> '交通银行',
			'邮储银行' 		=> '邮政储蓄',
			'中信银行' 		=> '中信银行',
			'华夏银行' 		=> '华夏银行',
			'民生银行' 		=> '中国民生银行',
			'广发银行' 		=> '广发银行',
			'平安银行' 		=> '平安银行（原深圳发展银行）',
			'兴业银行' 		=> '兴业银行',
			'北京银行' 		=> '北京银行',
			'上海银行' 		=> '上海银行',
			'杭州银行' 		=> '杭州银行股份有限公司',
			'宁波银行' 		=> '宁波银行股份有限公司',
			'广州银行' 		=> '广州银行',
			'珠海华润银行' 	=> '珠海华润银行股份有限公司清算中心',
			);

		$this->_baofu_bank_2_my_bank = array(
			'招商银行' => '招商银行',
			'工商银行' => '中国工商银行',
			'建设银行' => '中国建设银行',
			'浦发银行' => '上海浦东发展银行',
			'农业银行' => '中国农业银行',
			'民生银行' => '中国民生银行',
			'兴业银行' => '兴业银行',
			'交通银行' => '交通银行',
			'光大银行' => '中国光大银行',
			'中国银行' => '中国银行',
			'北京银行' => '北京银行',
			'BEA东亚银行' => '东亚银行',
			'渤海银行' => '渤海银行',
			'平安银行' => '平安银行（原深圳发展银行）',
			'广发银行' => '广发银行',
			'上海农商银行' => '上海农村商业银行',
			'邮政储蓄银行' => '邮政储蓄',
			'中信银行' => '中信银行',
			'杭州银行' => '杭州银行股份有限公司',
			'徽商银行' => '徽商银行股份有限公司',
			'南京银行' => '南京银行股份有限公司',
			'浙商银行' => '浙商银行',
			'晋城银行' => '晋城银行',
			'宁波银行' => '宁波银行股份有限公司',
			'日照银行' => '日照银行股份有限公司',
			'河北银行' => '河北银行股份有限公司',
			'华夏银行' => '华夏银行',
			'温州银行' => '温州银行股份有限公司',
			'广州银行' => '广州银行',
			'大连银行' => '大连银行',
			'东莞银行' => '东莞银行股份有限公司',
			'富滇银行' => '富滇银行股份有限公司运营管理部',
			'上海银行' => '上海银行',
			'稠州银行' => '浙江稠州商业银行',
			'北部湾银行' => '广西北部湾银行',
			'成都银行' => '成都银行',
			'西安银行' => '西安银行股份有限公司',
			'青岛银行' => '青岛银行',
			'湖北银行' => '湖北银行股份有限公司',
			'哈尔滨银行' => '哈尔滨银行结算中心',
			'郑州银行' => '郑州银行',
			'赣州银行' => '赣州银行股份有限公司',
			'盛京银行' => '盛京银行清算中心',
			'贵阳银行' => '贵阳市商业银行',
			'济宁银行' => '济宁银行股份有限公司',
			'邯郸银行' => '邯郸市商业银行股份有限公司',
			'长沙银行' => '长沙银行股份有限公司',
			'绍兴银行' => '绍兴银行股份有限公司营业部',
			'桂林银行' => '桂林银行股份有限公司',
			'齐商银行' => '齐商银行',
			'东营银行' => '东营银行股份有限公司',
			'烟台银行' => '烟台银行股份有限公司',
			'江苏银行' => '江苏银行股份有限公司',
			'长安银行' => '长安银行股份有限公司',
			'厦门银行' => '厦门银行股份有限公司',
			'潍坊银行' => '潍坊银行',
			'海峡银行' => '福建海峡银行股份有限公司',
			'齐鲁银行' => '齐鲁银行',
			'台州银行' => '台州银行股份有限公司',
			'德州银行' => '德州银行股份有限公司',
			'莱商银行' => '莱商市商业银行',
			'汉口银行' => '汉口银行资金清算中心',
			'恒丰银行' => '恒丰银行',
			'衡水银行' => '衡水银行股份有限公司',
			'柳州银行' => '柳州银行股份有限公司清算中心',
			'重庆银行' => '重庆银行',
			'民泰银行' => '浙江民泰商业银行',
			'华融湘江银行' => '华融湘江银行股份有限公司',
			'营口银行' => '营口银行股份有限公司资金清算中心'
			);
		
		$this->assign('lianliandaifu_bank_2_my_bank', $this->_lianliandaifu_bank_2_my_bank);
		$this->assign('baofu_bank_2_my_bank', $this->_baofu_bank_2_my_bank);
	}
	
	public function index(){
		$where = array();

		$withdraw_status = I('withdraw_status_et');
		//默认显示待审核申请
		if ($withdraw_status === '') {
			$withdraw_status = WITHDRAW_STATUS_NOVERIFY;
		}

		if ($withdraw_status != '-1') {
			$where['withdraw_status'] = $withdraw_status;
		}

		//待审核列表按照提现金额降序
		if ($withdraw_status == WITHDRAW_STATUS_NOVERIFY) {
			$_REQUEST['_field'] = 'withdraw_amount';
			$_REQUEST['_order'] = 'desc';  
		}

		$user_telephone = I('user_telephone');
		if($user_telephone){
			$where['uid'] = D('User')->getUidByTelephone($user_telephone);
		}
		$pay_mod = I('pay_mod');
		if ($pay_mod) {
			if ($pay_mod == 1) {
				$where['user_bank_card_type'] = array('in', $this->_baofu_bank_2_my_bank);
			} else {
				$where['user_bank_card_type'] = array('not in', $this->_baofu_bank_2_my_bank);
			}
		}

			
		$s_date = I('s_date');
		$e_date = I('e_date');
		if($s_date && $e_date){
			$where['withdraw_request_time'] = array('BETWEEN', array($s_date, $e_date));
		}else{
			if($s_date){
				$where['withdraw_request_time'] = array('EGT', $s_date);
			}
			if($e_date){
				$where['withdraw_request_time'] = array('ELT', $e_date);
			}
		}
		
		$this->setLimit($where);
		$list = parent::index('', true);
		
		$status = getWithdrawStatus();

		$this->assign('list', $list);
		$this->assign('status', $status);
		$this->assign('withdraw_status', (string)$withdraw_status);
		$this->assign('user_map', getUserMap($list));
		$this->display();
	
	}

	private function _changeStatus($id , $status , $remark=''){
		$withdraw = array();
		$withdraw['withdraw_id'] 		= $id;
		$withdraw['withdraw_status'] 	= $status;
		$withdraw['withdraw_remark']	= $remark;	
		$withdraw['withdraw_pay_time']  = getCurrentTime();
		$result = D('Withdraw')->save($withdraw);
		return $result;
	}
	
	/**
	 * 审核通过
	 */
	public function pass(){
		$withdraw_id 	= I('id');
		$withdraw_info  = D('Withdraw')->getWithdrawInfo($withdraw_id);
		$uid = $withdraw_info['uid'];
		$this->_verifyUserStatus($uid);
		$this->_verifyWithdrawInfo($withdraw_id);
		$this->_verifyWithdrawAmount($$withdraw_id);
		$status = WITHDRAW_STATUS_WAITPAY;
		$result = $this->_changeStatus($withdraw_id, $status);
		if($result !== false){

			$user_info = D('User')->getUserInfo($uid);
			$message_data = array(
				$withdraw_info['withdraw_request_time'],
				$withdraw_info['withdraw_amount']
				);

			sendTemplateSMS($user_info['user_telephone'], $message_data, $this->_getWithDrawPassedSmsTemId($user_info));
			$this->success('操作成功！');
		}else{
			$this->error('操作失败！');
		}
	}

    private function _getWithDrawPassedSmsTemId($user_info){
        $app_id = getRegAppId($user_info);
        if($app_id == C('APP_ID_LIST.TIGER')){
            return C('ADMIN_SMS_TEMPLTE_ID.WITHDRAW_PASSED');
        }elseif($app_id == C('APP_ID_LIST.BAIWAN')){
            return C('ADMIN_BAIWAN_SMS_TEMPLTE_ID.WITHDRAW_PASSED');
        }elseif($app_id == C('APP_ID_LIST.NEW')){
            return C('ADMIN_NEW_SMS_TEMPLTE_ID.WITHDRAW_PASSED');
        }
    }

	/**
	* 通过并代付-连连
	*
	*/
	public function daifu(){
		$withdraw_id = I('id');
		$withdraw_info = D('Withdraw')->getWithdrawInfo($withdraw_id);
		if ($withdraw_info['withdraw_status'] != WITHDRAW_STATUS_NOVERIFY) {
			$this->error('该申请已经处理过了！！！');
		}
		$this->_verifyUserStatus($withdraw_info['uid']);
		$this->_verifyWithdrawInfo($withdraw_id);
		$this->_verifyWithdrawAmount($$withdraw_id);

		$result = false;

		M()->startTrans();
		$withdraw_data = array(
			'withdraw_id' => $withdraw_id,
			'withdraw_status' => WITHDRAW_STATUS_DAIFU,
			'withdraw_operator_id' => UID,
			'withdraw_modify_time' => getCurrentTime(),
			'withdraw_daifu_apply_time' => getCurrentTime(),
			'withdraw_daifu_channel' => WITHDRAW_DAIFU_CHANNEL_LIANLIAN,
			);

		$update_status = D('Withdraw')->save($withdraw_data);
		if ($update_status) {
			$daifu_request = $this->_requestDaifu($withdraw_info);

			if (!empty($daifu_request) && is_array($daifu_request)) {
				
				if ($daifu_request['ret_code'] === '0000') {
					$result = true;
				} else {
					$error = $daifu_request['ret_msg'];
				}
				
			}
		}

		if ($result) {
			M()->commit();

			$user_info = D('User')->getUserInfo($withdraw_info['uid']);
			$message_data = array(
				$withdraw_info['withdraw_request_time'],
				$withdraw_info['withdraw_amount']
				);

			sendTemplateSMS($user_info['user_telephone'], $message_data, $this->_getWithDrawPassedSmsTemId($user_info));

			$this->success('处理成功！请等待代付处理结果！');
		} else {
			M()->rollback();
			$this->error('处理失败！连连代付API返回'.$error.'!!!,请联系管理员！');
		}

		$this->ajaxReturn($result);
	}

	/**
	* 通过并代付-宝付
	*
	*/
	public function daifuByBaofu(){
		$withdraw_id = I('id');
		$withdraw_info = D('Withdraw')->getWithdrawInfo($withdraw_id);
		if ($withdraw_info['withdraw_status'] != WITHDRAW_STATUS_NOVERIFY) {
			$this->error('该申请已经处理过了！！！');
		}
		$this->_verifyUserStatus($withdraw_info['uid']);
		$this->_verifyWithdrawInfo($withdraw_id);
		$this->_verifyWithdrawAmount($$withdraw_id);

		$result = false;

		M()->startTrans();
		$withdraw_data = array(
			'withdraw_id' 				=> $withdraw_id,
			'withdraw_status' 			=> WITHDRAW_STATUS_DAIFU,
			'withdraw_operator_id' 		=> UID,
			'withdraw_modify_time' 		=> getCurrentTime(),
			'withdraw_daifu_apply_time' => getCurrentTime(),
			'withdraw_daifu_channel' 	=> WITHDRAW_DAIFU_CHANNEL_BAOFU,
			);

		$update_status = D('Withdraw')->save($withdraw_data);
		if ($update_status) {
			$daifu_request = $this->_requestDaifuByBaofu($withdraw_info);

			if (!empty($daifu_request) && is_array($daifu_request)) {
				
				if ($daifu_request['trans_content']['trans_head']['return_code'] === '0000') {
					$withdraw_data = array(
						'withdraw_id' 				=> $withdraw_id,
						'withdraw_daifu_no' 		=> $daifu_request['trans_content']['trans_reqDatas'][0]['trans_reqData']['trans_orderid'],
						'withdraw_daifu_batch_id' 	=> $daifu_request['trans_content']['trans_reqDatas'][0]['trans_reqData']['trans_batchid']
						);

					if (D('Withdraw')->save($withdraw_data)) {
						$result = true;
					}
				} else {
					$error = '错误码：'.$daifu_request['trans_content']['trans_head']['return_code'].',错误信息：'.$daifu_request['trans_content']['trans_head']['return_msg'];
				}
				
			} else {
				ApiLog('api resp error:'.var_export($daifu_request, true), 'baofu');
			}
		}

		if ($result) {
			M()->commit();

			$user_info = D('User')->getUserInfo($withdraw_info['uid']);
			$message_data = array(
				$withdraw_info['withdraw_request_time'],
				$withdraw_info['withdraw_amount']
				);

			sendTemplateSMS($user_info['user_telephone'], $message_data, $this->_getWithDrawPassedSmsTemId($user_info));

			$this->success('处理成功！请等待代付处理结果！');
		} else {
			M()->rollback();
			$this->error('处理失败！宝付代付API返回'.$error.'!!!,请联系管理员！');
		}

		$this->ajaxReturn($result);
	}

	private function _requestDaifuByBaofu($withdraw_info){
		require_once('baofudaifu/BaofooSdk.php');

		$api_reqData = array(
			'trans_no' 		=> $withdraw_info['withdraw_id'],
			'trans_money' 	=> number_format($withdraw_info['withdraw_amount']-$withdraw_info['withdraw_fee'], 2, ".", ""),
			'to_acc_name' 	=> $withdraw_info['user_bank_card_account_name'],
			'to_acc_no' 	=> clearBankAccount($withdraw_info['user_bank_card_number']),
			'to_bank_name' 	=> $this->_bankname2BaofuBankname($withdraw_info['user_bank_card_type']),
			'to_pro_name' 	=> '',
			'to_city_name' 	=> '',
			'to_acc_dept' 	=> '',
			'trans_summary' => '用户提现'
					);

		$api_data = array(
			'trans_content' => array(
				'trans_reqDatas' => array(
					array(
					'trans_reqData' => array(
						$api_reqData
						)
					)
					),
				),
			);
		
		ApiLog('api data:'.print_r($api_data, true), 'baofu');
		$api_data = json_encode($api_data);

		$baofu_config = C('BAOFU_DAIFU_CONFIG');
		$baofoo_sdk = new \BaofooSdk($baofu_config['MEMBER_ID'], $baofu_config['TERMINAL_ID'], $baofu_config['DATA_TYPE'], $baofu_config['PRIVATE_KEY_PATH'], $baofu_config['PUBLIC_KEY_PATH'], $baofu_config['PRIVATE_KEY_PASSWORD']);

		$api_data_encry = $baofoo_sdk->encryptedByPrivateKey($api_data);
		
		$resp = $baofoo_sdk->post($api_data_encry, $baofu_config['DAIFU_API']);
		ApiLog('api url:'.$baofu_config['DAIFU_API'], 'baofu');
		ApiLog('api resp:'.$resp, 'baofu');
		if ($resp) {
			$resp = $baofoo_sdk->decryptByPublicKey($resp);
			$resp = json_decode($resp, true);
			ApiLog('api resp decry:'.print_r($resp, true), 'baofu');
			return $resp;
		} else {
			return false;
		}
	}

	private function _requestDaifu($withdraw_info){
		require_once('lianliandaifu/llpay.config.php');
		require_once('lianliandaifu/lib/llpay_apipost_submit.class.php');

		$paramer =  array();
		$paramer['oid_partner'] = trim($llpay_config['oid_partner']);
		$paramer['sign_type'] 	= trim($llpay_config['sign_type']);
		$paramer['no_order'] 	= $withdraw_info['withdraw_id'];
		$paramer['dt_order'] 	= date('YmdHis', strtotime($withdraw_info['withdraw_request_time']));
		$paramer['money_order'] = number_format($withdraw_info['withdraw_amount'] - $withdraw_info['withdraw_fee'], 2, ".", "");
		//$paramer['money_order'] = round($withdraw_info['withdraw_amount'] - $withdraw_info['withdraw_fee'], 2);
		$paramer['flag_card'] 	= "0";
		$paramer['card_no'] 	= clearBankAccount($withdraw_info['user_bank_card_number']);
		$paramer['acct_name'] 	= $withdraw_info['user_bank_card_account_name'];
		$paramer['bank_code'] 	= '';
		$paramer['city_code'] 	= '';
		$paramer['brabank_name'] = '';
		$paramer['info_order'] 	= '用户提现';
		$paramer['notify_url'] 	= 'http://mg.tigercai.com/index.php/LianlianDaifu/notify';
		$paramer['api_version'] = '1.2';
		$paramer['prcptcd'] 	= '';

		$llpay_gateway_new = C('LIANLIAN_DAIFU_API');

		$llpaySubmit 	= new \LLpaySubmit($llpay_config);
		$daifu_response = $llpaySubmit->buildRequestJSON($paramer,$llpay_gateway_new);
		ApiLog('pamamer:'.print_r($paramer, true).'---'.(number_format($withdraw_info['withdraw_amount'] - $withdraw_info['withdraw_fee'], 2, ".", "")), 'daifu');
		ApiLog('response:'.print_r($daifu_response, true), 'daifu');

		if (!empty($daifu_response)) {
			$daifu_response = json_decode($daifu_response, true);
		}

		return $daifu_response;
	}
	
	/**
	 * 打款成功
	 */
	public function withdrawSucc(){
		$withdraw_id 	= I('id');
		$withdraw_info  = D('Withdraw')->getWithdrawInfo($withdraw_id);
		$uid = $withdraw_info['uid'];
		$this->_verifyUserStatus($uid);
		$this->_verifyWithdrawInfo($withdraw_id);
		$this->_verifyWithdrawAmount($$withdraw_id);
		
		$model = D('Withdraw');
		$model->startTrans();
		$status = WITHDRAW_STATUS_PAID;
		$result = $this->_changeStatus($withdraw_id, $status);
		if($result){
			$withdraw_info   = $model->getWithdrawInfo($withdraw_id);
			$uid			 = $withdraw_info['uid'];
			$withdraw_amount = $withdraw_info['withdraw_amount'];
			
			$result = D('UserAccount')->deductFrozenBalance($uid, $withdraw_amount, $withdraw_id);
			if($result){
				$model->commit();
				$this->success('操作成功！');
			}else{
				$model->rollback();
				$this->error('操作失败！');
			}
		}else{
			$this->error('操作状态失败！');
		}
	}
	

	/**
	 * 拒绝
	 */
	public function refuse(){
		if(IS_POST){
			$withdraw_id = I('id');
			$remark 	 = I('withdraw_remark');
			$status 	 = WITHDRAW_STATUS_REFUSE;
			
			$this->_verifyWithdrawAmount($withdraw_id);
			$result = $this->_unfreeze($withdraw_id, $status, $remark);

			if ($result) {
				$withdraw_info = D('Withdraw')->getWithdrawInfo($withdraw_id);
				$user_info = D('User')->getUserInfo($withdraw_info['uid']);

				if ($user_info['user_status'] != C('USER_STATUS.DISABLE')) {
					$message_data = array(
						$withdraw_info['withdraw_request_time'],
						empty($remark) ? '不符合提现规定,具体请咨询客服' : $remark
						);
					sendTemplateSMS($user_info['user_telephone'], $message_data, $this->_getWithDrawRejectedSmsTemId($user_info));
				}

				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}
		}else{
			$this->display();
		}
	}

    private function _getWithDrawRejectedSmsTemId($user_info){
        $app_id = getRegAppId($user_info);
        if($app_id == C('APP_ID_LIST.TIGER')){
            return C('ADMIN_SMS_TEMPLTE_ID.WITHDRAW_REJECTED');
        }elseif($app_id == C('APP_ID_LIST.BAIWAN')){
            return C('ADMIN_BAIWAN_SMS_TEMPLTE_ID.WITHDRAW_REJECTED');
        }elseif($app_id == C('APP_ID_LIST.NEW')){
            return C('ADMIN_NEW_SMS_TEMPLTE_ID.WITHDRAW_REJECTED');
        }
    }
	
	
	/**
	 * 撤销
	 */
	public function revoke(){
		if(IS_POST){
			$withdraw_id = I('id');
			$remark 	 = I('withdraw_remark');
			$status 	 = WITHDRAW_STATUS_REVOKE;
			
			$this->_verifyWithdrawAmount($withdraw_id);
			$result = $this->_unfreeze($withdraw_id, $status, $remark);
			if ($result) {
				$withdraw_info = D('Withdraw')->find($withdraw_id);
				$user_info = D('User')->getUserInfo($withdraw_info['uid']);

				if ($user_info['user_status'] != C('USER_STATUS.DISABLE')) {
					$message_data = array(
						$withdraw_info['withdraw_request_time'],
						);

					sendTemplateSMS($user_info['user_telephone'], $message_data, $this->_getWithdDaiFuFaillSmsTemId($user_info));
				}

				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}
		}else{
			$this->display();
		}
	}

    private function _getWithdDaiFuFaillSmsTemId($user_info){
        $app_id = getRegAppId($user_info);
        if($app_id == C('APP_ID_LIST.TIGER')){
            return C('ADMIN_SMS_TEMPLTE_ID.DAIFU_FAILUE');
        }elseif($app_id == C('APP_ID_LIST.BAIWAN')){
            return C('ADMIN_BAIWAN_SMS_TEMPLTE_ID.DAIFU_FAILUE');
        }elseif($app_id == C('APP_ID_LIST.NEW')){
            return C('ADMIN_NEW_SMS_TEMPLTE_ID.DAIFU_FAILUE');
        }
    }
	
	private function _unfreeze($withdraw_id, $status, $remark){
		$model = D('Withdraw');
		$model->startTrans();
		$result = $this->_changeStatus($withdraw_id, $status, $remark);
		if($result){
			$withdraw_info   = D('Withdraw')->getWithdrawInfo($withdraw_id);
			$uid			 = $withdraw_info['uid'];
			$withdraw_amount = $withdraw_info['withdraw_amount'];
				
			$result = D('UserAccount')->unfreeze($uid, $withdraw_amount, $withdraw_id);
			if($result){
				$model->commit();
			}else{
				$model->rollback();
			}
		}else{
			$model->rollback();
		}

		return $result;
	}
	
	private function _verifyWithdrawAmount($withdraw_id){
		$withdraw_info   = D('Withdraw')->getWithdrawInfo($withdraw_id);
		$uid			 = $withdraw_info['uid'];
		$withdraw_amount = $withdraw_info['withdraw_amount'];
		
		$user_account_info 	 = D('UserAccount')->getUserAccountInfo($uid);
		$user_frozen_balance = $user_account_info['user_account_frozen_balance'];
		if ($withdraw_amount > $user_frozen_balance){
			$this->error('提现数据有误，请联系相关技术人员！');
		}
	}
	
	private function _verifyUserStatus($uid){
		$user_status = D('User')->getUserStatus($uid);
		if($user_status == C('USER_STATUS.DISABLE')){
			$this->error('用户账号已被冻结！');
		}
	}
	
	private function _verifyWithdrawInfo($withdraw_id){
		$withdraw_info = D('Withdraw')->getWithdrawInfo($withdraw_id);
		if(!$withdraw_info['user_bank_card_number'] || !$withdraw_info['user_bank_card_address'] 
		|| !$withdraw_info['user_bank_card_account_name'] || !$withdraw_info['user_bank_card_type']){
			$this->error('提现所需信息不完整！');
		}
	}

	private function _bankname2BaofuBankname($bank_name){
		return array_search($bank_name, $this->_baofu_bank_2_my_bank);
	}
}