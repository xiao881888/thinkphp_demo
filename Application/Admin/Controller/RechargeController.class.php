<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
use User\Api\Api;

/**
 * @date 2014-12-4
 * @author tww <merry2014@vip.qq.com>
 */
class RechargeController extends GlobalController{

    const WECHAT_TRANSFER_MIN_AMOUNT = 1000;
    const WECHAT_TRANSFER_COUPON_ID = 212;
    const WECHAT_TRANSFER_RECHARGE_CHANNEL_ID = 18;
    const WECHAT_TRANSFER_LOG_TYPE = 15;

    const LARGE_RECHARGE_MIN_AMOUNT = 5000;
    const LARGE_RECHARGE_COUPON_ID = 214;
    const LARGE_RECHARGE_RECHARGE_CHANNEL_ID = 6;
    const LARGE_RECHARGE_LOG_TYPE = 15;

	public function index(){
		$where = array();
		
		$user_telephone = I('user_telephone');	
		if($user_telephone){
			$where['uid'] = D('User')->getUidByTelephone($user_telephone);
			
		}
			
		$s_date = I('s_date');
		$e_date = I('e_date');
		if($s_date && $e_date){
			$where['recharge_create_time'] = array('BETWEEN', array($s_date, $e_date));
		}else{
			if($s_date){
				$where['recharge_create_time'] = array('EGT', $s_date);
			}
			if($e_date){
				$where['recharge_create_time'] = array('ELT', $e_date);
			}
		}

        $min_amount = intval(I('min_amount'));
        $max_amount = intval(I('max_amount'));

        if ($min_amount && $max_amount ) {
            $where['recharge_amount'] = array('BETWEEN', array($min_amount, $max_amount));
        } else {
            if ($min_amount) {
                $where['recharge_amount'] = array('EGT', $min_amount);
            } 
            if ($max_amount) {
                $where['recharge_amount'] = array('ELT', $max_amount);
            }
        }

		$this->setLimit($where);
		$list = parent::index('', true);
		$this->assign('list', $list);
		$this->assign('user_map', getUserMap($list));
		$this->assign('lottery_map', D('Lottery')->getLotteryMap());
		$this->assign('recharge_channel_map', D('RechargeChannel')->getRechargeChannelsMap());
		$this->display();
	
	}

	public function addRechargeRecord(){
		if (IS_POST) {
			$uid 				= I('uid');
			$recharge_amount 	= I('recharge_amount');
			$recharge_amount_confirm = I('recharge_amount_confirm');
			$bank_deal_no 		= I('bank_deal_no');
			$recharge_remark 	= I('recharge_remark');
			$recharge_channel_id = I('recharge_channel_id');

			if (empty($uid) || empty($recharge_amount) || empty($recharge_channel_id)) {
				$this->error('缺少必要参数!');
			}

			if ($recharge_amount != $recharge_amount_confirm) {
				$this->error('金额不一致！请谨慎确认！！！');
			}

			$user_info = D('User')->getUserInfo($uid);
			if (empty($user_info)) {
				$this->error('充值用户不存在！');
			}

			$add_success = false;

			M()->startTrans();

			$new_recharge_data = $this->_addManualRechargeRecord($uid, $recharge_amount, $recharge_channel_id, $bank_deal_no, $recharge_remark);
			if ($new_recharge_data) {
				$update_balance = D('UserAccount')->where('uid='.$uid)->setInc('user_account_balance', $recharge_amount);
				if ($update_balance) {
					$update_recharge_static = D('UserAccount')->where('uid='.$uid)->setInc('user_account_recharge_amount', $recharge_amount);
					if ($update_recharge_static) {
						$user_account = D('UserAccount')->getUserAccountInfo($uid);
						$add_account_log = D('UserAccountLog')->addRechargeAccountLog($new_recharge_data, $user_account, UID);
						if ($add_account_log) {
							$add_success = true;
						}
					}
				}
			}

			if ($add_success) {
				M()->commit();

				$message_data = array(
					$new_recharge_data['recharge_create_time'],
					$new_recharge_data['recharge_amount']
					);
				$this->_grantCouponToUser($new_recharge_data['recharge_id']);
                if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
                    sendTemplateSMS($user_info['user_telephone'], $message_data, $this->_getSmsTemId($user_info));
                }
				$this->success('操作成功，当前用户账户可用余额'.$user_account['user_account_balance'], U('UserAccountLog/index', array('uid'=>$uid)));
			} else {
				M()->rollback();
				$this->error('操作失败，请联系管理员！');
			}
		} else {
			$uid = I('uid');
			$user_info = D('User')->getUserInfo($uid);

			$this->assign('user_info', $user_info);
			$this->display();
		}
	}



	private function _getSmsTemId($user_info){
	    $app_id = getRegAppId($user_info);
	    if($app_id == C('APP_ID_LIST.TIGER')){
	        return C('ADMIN_SMS_TEMPLTE_ID.RECHARGE_SUCCESS');
        }elseif($app_id == C('APP_ID_LIST.BAIWAN')){
            return C('ADMIN_BAIWAN_SMS_TEMPLTE_ID.RECHARGE_SUCCESS');
        }elseif($app_id == C('APP_ID_LIST.NEW')){
            return C('ADMIN_NEW_SMS_TEMPLTE_ID.RECHARGE_SUCCESS');
        }
    }

	private function _addManualRechargeRecord($uid, $recharge_amount, $recharge_channel_id, $bank_deal_no, $recharge_remark){
		$recharge_data = array();
		$recharge_data['uid'] 					= $uid;
		$recharge_data['recharge_channel_id'] 	= $recharge_channel_id;
		$recharge_data['recharge_create_time'] 	= getCurrentTime();
		$recharge_data['recharge_receive_time'] = getCurrentTime();
		$recharge_data['recharge_status'] 		= 1;
		$recharge_data['recharge_amount'] 		= $recharge_amount;
		$recharge_data['recharge_operator_id'] 	= UID;
		$recharge_data['recharge_remark'] 		= $recharge_remark;
		$recharge_data['recharge_channel_no'] 	= $bank_deal_no;
		$recharge_data['recharge_no'] 			= '';
		$recharge_data['recharge_sku'] 			= 'RECHARGE'.date('Ymd').random_string(6).$uid;;
		$recharge_data['recharge_source'] 		= 3;
		$recharge_data['recharge_client_code'] 	= '';
		$recharge_data['recharge_client_message'] = '';

		$recharge_id = D('Recharge')->add($recharge_data);

		if ($recharge_id) {
			$recharge_data['recharge_id'] = $recharge_id;
			return $recharge_data;
		} else {
			return false;
		}
	}

	private function _isLargeRecharge($recharge_channel_id){
        return $recharge_channel_id == self::LARGE_RECHARGE_RECHARGE_CHANNEL_ID;
    }

    private function _isWechatTransfer($recharge_channel_id){
        return $recharge_channel_id == self::WECHAT_TRANSFER_RECHARGE_CHANNEL_ID;
    }

	private function _grantCouponToUser($recharge_id){
        $is_grant = false;
        $recharge_info = D('Home/Recharge')->getRechargeInfo($recharge_id);
        ApiLog('$recharge_info:'.print_r($recharge_info,true),'adminRecharge');


        $is_grant = $this->_checkRecharge($recharge_info);
        if(!$is_grant){
            return false;
        }

        $recharge_channel_id = $recharge_info['recharge_channel_id'];

        $coupon_amount = $this->_isWechatTransfer($recharge_channel_id) ? $this->_getWechatTransferGrantCouponAmount($recharge_info['recharge_amount']) : $this->_getLargeGrantCouponAmount($recharge_info['recharge_amount']) ;
        ApiLog('$coupon_amount:'.$coupon_amount,'adminRecharge');

        $coupon_id = $this->_isWechatTransfer($recharge_channel_id) ? self::WECHAT_TRANSFER_COUPON_ID : self::LARGE_RECHARGE_COUPON_ID;
        $log_type = $this->_isWechatTransfer($recharge_channel_id) ? self::WECHAT_TRANSFER_LOG_TYPE : self::LARGE_RECHARGE_LOG_TYPE;

        $grant_status = $this->grantCouponToUser($coupon_id,$recharge_info['uid'],$log_type,$coupon_amount);
        ApiLog('$grant_status:'.$grant_status,'adminRecharge');
        return true;

    }

    //5000<= x < 25000    返现0.3%
    //25000<= x < 100000  返现0.4%
    //x>=100000                返现0.6%
    private function _getLargeGrantCouponAmount($recharge_amount){
        $grant_coupon_amount = 0;
        if($recharge_amount>=5000 && $recharge_amount<25000){
            $grant_coupon_amount = sprintf("%.2f", $recharge_amount*0.003);
        }elseif($recharge_amount>=25000 && $recharge_amount<100000){
            $grant_coupon_amount = sprintf("%.2f", $recharge_amount*0.004);
        }elseif($recharge_amount>=100000){
            $grant_coupon_amount = sprintf("%.2f", $recharge_amount*0.006);
        }
        return $grant_coupon_amount;
    }

	//1000<= x < 5000    返现0.3%
    //5000<= x < 20000  返现0.4%
    //x>=20000                返现0.6%
	private function _getWechatTransferGrantCouponAmount($recharge_amount){
	    $grant_coupon_amount = 0;
        if($recharge_amount>=1000 && $recharge_amount<5000){
            $grant_coupon_amount = sprintf("%.2f", $recharge_amount*0.003);
        }elseif($recharge_amount>=5000 && $recharge_amount<20000){
            $grant_coupon_amount = sprintf("%.2f", $recharge_amount*0.004);
        }elseif($recharge_amount>=20000){
            $grant_coupon_amount = sprintf("%.2f", $recharge_amount*0.006);
        }
        return $grant_coupon_amount;
    }

    private function _checkRecharge($recharge_info){
        if($recharge_info['recharge_status']!=1){
            return false;
        }

        /*if(!in_array($recharge_info['recharge_channel_id'],array(self::WECHAT_TRANSFER_RECHARGE_CHANNEL_ID,self::LARGE_RECHARGE_RECHARGE_CHANNEL_ID))){
            return false;
        }*/
        if(!in_array($recharge_info['recharge_channel_id'],array(self::LARGE_RECHARGE_RECHARGE_CHANNEL_ID))){
            return false;
        }

        $min_amount = $this->_isWechatTransfer($recharge_info['recharge_channel_id']) ? self::WECHAT_TRANSFER_MIN_AMOUNT : self::LARGE_RECHARGE_MIN_AMOUNT;
        if ($recharge_info['recharge_amount'] < $min_amount) {
            return false;
        }

        $uid = $recharge_info['uid'];
        if (empty($uid)){
            return false;
        }

       /* $user_info = D('User')->getUserInfo($uid);
        $app_id = getRegAppId($user_info);
        if($app_id != C('APP_ID_LIST.TIGER')){
            return false;
        }*/

        $recharge_map['recharge_id'] = array(
            'NEQ',
            $recharge_info['recharge_id']
        );
        $recharge_map['recharge_channel_id'] = array('IN',array(self::WECHAT_TRANSFER_RECHARGE_CHANNEL_ID,self::LARGE_RECHARGE_RECHARGE_CHANNEL_ID));
        $recharge_map['recharge_status'] = 1;
        $recharge_map['uid'] = $uid;
        $recharge_map['recharge_create_time'] = array(
            array('LT', $recharge_info['recharge_create_time']),
            array('GT', $this->getCurrentTimeFor0())
        );
        $recharge_count = D('Recharge')->where($recharge_map)->count();
        ApiLog('sql:'.D('Recharge')->getLastSql(),'adminRecharge');
        if ($recharge_count > 0) {
            ApiLog('$uid:'.$uid.'不是首冲','adminRecharge');
            return false;
        }
        return true;
    }

    private function getCurrentTimeFor0(){
        $current_day = date('Y-m-d');
        return $current_day.' 00:00:00';
    }

    public function grantCouponToUser($coupon_id,$uid,$log_type,$coupon_amount){
        $coupon_info = D('Home/Coupon')->getCouponInfo($coupon_id);
        $user_coupon_data['uid'] = $uid;
        $user_coupon_data['coupon_id'] = $coupon_info['coupon_id'];
        $user_coupon_data['user_coupon_balance'] = $coupon_amount;
        $user_coupon_data['user_coupon_status'] = 3;
        $user_coupon_data['user_coupon_amount'] = $coupon_amount;
        $user_coupon_data['user_coupon_desc'] = $this->_getUserCouponDesc($coupon_info);
        $user_coupon_data['user_coupon_start_time'] = getCurrentTime();
        $user_coupon_data['user_coupon_create_time'] = getCurrentTime();
        $user_coupon_data['user_coupon_end_time'] = '2099-12-31 23:59:59';
        $user_coupon_data['coupon_min_consume_price'] = $coupon_info['coupon_min_consume_price'];
        $user_coupon_data['coupon_lottery_ids'] = empty($coupon_info['coupon_lottery_ids']) ? '' : $coupon_info['coupon_lottery_ids'];
        $user_coupon_data['play_type'] = $coupon_info['play_type'];
        $user_coupon_data['bet_type'] = $coupon_info['bet_type'];
        $user_coupon_data['coupon_type'] = $coupon_info['coupon_type'];
        $user_coupon_data['activity_id'] = $coupon_info['activity_id'];

        M()->startTrans();
        $userCouponId = M('UserCoupon')->add($user_coupon_data);
        $log_result = D('Home/UserCouponLog')->addUserCouponLog($uid, $userCouponId, $user_coupon_data['user_coupon_balance'], $user_coupon_data['user_coupon_balance'], $log_type, $uid);
        $consumeCoupon = D('Home/UserAccount')->updateBuyCouponStatics($uid, $user_coupon_data['user_coupon_balance']);
        if ($userCouponId && $consumeCoupon && $log_result) {
            ApiLog($uid . '用户请求兑换红包请求成功!$user_coupon_data:' . print_r($user_coupon_data, true), 'adminRecharge');
            M()->commit();
            return true;
        } else {
            ApiLog($uid . '用户请求兑换红包请求失败!$user_coupon_data:' . print_r($user_coupon_data, true), 'adminRecharge');
            M()->rollback();
            return false;
        }
    }

    private function _getUserCouponDesc($coupon_info){
        $limit_lottery_str = '';
        if(empty($coupon_info['coupon_lottery_ids'])){
            $limit_lottery_str =  '可用彩种: 通用';
        }else{
            $coupon_lottery_ids = explode(',',$coupon_info['coupon_lottery_ids']);
            if(count($coupon_lottery_ids) <= 0){
                $limit_lottery_str =  '可用彩种: 通用';
            }else{
                $limit_lottery_str =  '可用彩种: ';
                foreach($coupon_lottery_ids as $lottery_id){
                    $limit_lottery_str .= $this->_getLotteryName($lottery_id).'  ';
                }
            }
        }
        return $limit_lottery_str;
    }

    private function _getLotteryName($lottery_id){
        return M('Lottery')->where(array('lottery_id'=>$lottery_id))->getField('lottery_name');
    }


}