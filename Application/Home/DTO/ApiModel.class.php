<?php 
namespace Home\DTO;
class ApiModel extends DataModel {
	public $session, $act;
	public $issue_id, $lottery_id, $size, $channel_id, $bundleId;
	public $offset, $limit;
	public $multiple, $order_multiple, $follow_times;
	public $type, $address, $issueNo, $no, $account;
	public $tel, $sms_validation;
	public $os, $version, $sdk_version, $app_version;
	public $network, $model, $mode;
	public $body, $key, $remark;
	public $money, $total_amount, $stake_count;
	public $schedule_orders, $series, $tickets, $select_schedule_ids, $optimize_ticket_list;
	public $passwd, $pay_passwd;
	public $encrypt_type, $public_key;
	public $order_type, $order_id, $order_identity;
	public $coupon_id, $coupon_code;
	public $order_limit, $day_limit, $switch;
	public $realname, $identity_no;
	public $recharge_channel_id;
	public $recharge_order_id;
	public $sort, $play_type, $bet_type, $device_token;
	public $date, $result_code, $result_info;
	public $schedule_ids, $schedule_id, $team_id;
	public $bonus_range, $id, $draft_identity, $third_party_schedule_id, $company_id;
	public $config;
	public $file, $issue_no;
	public $avatar;
	public $follow_detail, $is_win_stop, $suite_id;
	public $follow_bet_id,$win_stop_amount,$is_independent,$nick_name,$category;
	public $unit_amount, $total_unit, $commission, $subscribe, $ensure, $order_info, $user_id, $project_id;
    public $pay_total_amount,$sub_sort,$filter,$is_copurchase;
    protected $_map = array('token' => 'session', 
    						'extra' => 'remark',
//     						'series' => 'betTypes' 
						);
    
    protected $_validate = array(
        array('act', 	'verifyAct', 	'act 参数错误！', 	0, 'callback'),
        array('session','verifyMd5', 'session 参数错误！', 	0, 'callback'),
        array('tel', 	'verifyTelephone', '电话号码格式不对', 	0, 'callback'),
        array('passwd', 'verifyPassword', '密码格式不对', 		0, 'callback'),
        array('pay_passwd', 'verifyPayPassword', '支付密码格式不对', 0, 'callback'),
        array('sms_validation','verifySms', '验证码格式不对', 0, 'callback'),
        array('coupon_code','verifyCouponExchange', 'Coupon Exchange 格式不对', 0, 'callback'),
        array('multiple',   'verifyMultiple',       'multiple 参数错误！',	  0, 'callback'),
        array('network', array('0','1'), 'netWork 参数错误', 0, 'in'),
        array('os', 	 array('1','2'), 'os 参数错误', 		0, 'in'),
        array('sort', 	 array('1','2','3'), 'sort 参数错误',    0, 'in'),
        array('switch',	 array('1','0'), 'switch 参数错误',  0, 'in'),
        array('order_type', array('-1', 0, '1', '-2', 2, 3), 'order_type 参数错误', 0, 'in'),
        array('offset', 'number',   'offset 参数错误！',	0),
        array('limit', 	'number',   'limit 参数错误！',	0),
        array('mode', 	'number',   'mode 参数错误！',	0),
    	array('issue_id', 	'number', 	'issue_id 参数错误！', 	0),
        array('stake_count', 	'number', 'stake_count 参数错误！',	0),
        array('order_id', 	    'number', 'order_id 参数错误！',	0),
        array('play_type', 	    'number', 'play_type 参数错误！',	0),
        array('coupon_id', 	    'number', 'coupon_id 参数错误！',	0),
        array('follow_times',   'verifyFollowTimes', 'follow_times 参数错误！',	0, 'callback'),
        array('tickets',   'verifyArray', 'tickets 参数错误！',	0, 'callback'),
        array('schedule_orders',   'verifyArray', 'schedule_orders 参数错误！',	0, 'callback'),
    	array('recharge_channel_id', 	'number',   'recharge_channel_id 参数错误！',	0),
        array('lottery_id',     'number', 'lottery_id 参数错误！',   0),
        array('sdk_version', 	'number', 'sdk_version 参数错误！',  0),
    	array('date', 	    	'number', 'date 参数错误！',	0),
    	array('encrypt_type', 	'number', 'limit 参数错误！',		0),
        array('version',    	'require', 'version 参数错误！',	0),
        array('realname',    	'require', 'realname 参数错误！',	0),
        array('order_limit',    'currency', 'order_limit 参数错误！',	 0),
        array('day_limit',      'currency', 'day_limit 参数错误！',	 0),
        array('total_amount',	'currency', 'total_amount 参数错误！', 0),
        array('money',	'currency', 'money 参数错误！',	0),
        array('body',   'require', 'body 参数错误！',	 	0),
        array('model',  'require', 'model 参数错误！',	0),
//     	array('order_identity',  'require', 'order_identity 参数错误！',	0),
    );
    
    public function __set($property, $val) {
        return false;
    }
    
    public function __construct($request) {
        $apiData  = $this->create($request);
        \AppException::ifNoExistThrowException($apiData, C('ERROR_CODE.PARAM_ERROR'));

        foreach ( $apiData as $key=>$val ) {
            if( property_exists($this, $key)) {		# 无条件赋值
                $this->$key = $val;
            }
        }
    }
    
    protected function verifyMultiple($multiple) {
//         $result = is_int($multiple) && ($multiple>0 && $multiple<=99);
        $result = is_int($multiple) && ($multiple>0 && $multiple<=9999);
        \AppException::ifNoExistThrowException($result, C('ERROR_CODE.BET_NUMBER_ERROR'));
        return $result;
    }
    
    protected function verifyArray($array) {
    	$result = ( $array && is_array($array) );
    	\AppException::ifNoExistThrowException($result, C('ERROR_CODE.BET_NUMBER_ERROR'));
    	return $result;
    }
    
    protected function verifyPassword($passwd) {
        $result = preg_match('/^\S{6,24}$/', $passwd);
        \AppException::ifNoExistThrowException($result, C('ERROR_CODE.PASSWORD_FORMAT_ERROR'));
        return (bool)$result;
    }
    
    
    protected function verifySeries($series) {
    	$result = preg_match('/^(\d+,?)+$/', $series);
    	\AppException::ifNoExistThrowException($result, C('ERROR_CODE.BET_NUMBER_ERROR'));
    	return (bool)$result;
    }
    
    
    protected function verifyPayPassword($passwd) {
        if(!$passwd) {  // 空密码不验证
            return true;
        }
        $result = preg_match('/^\w{6}$/', $passwd);
        \AppException::ifNoExistThrowException($result, C('ERROR_CODE.PASSWORD_FORMAT_ERROR'));
        return (bool)$result;
    }
    
    
    protected function verifyTelephone($tel) {
        $result = preg_match('/^\+{0,1}(\d|\s){9,17}$/', $tel);
        \AppException::ifNoExistThrowException($result, C('ERROR_CODE.TELEPHONE_ERROR'));
        return (bool)$result;
    }
    
    protected function verifyMd5($session) {
        $result = preg_match('/^\w{32}$/', $session);
        \AppException::ifNoExistThrowException($result, C('ERROR_CODE.SESSION_ERROR'));
        return (bool)$result;
    }
    
    protected function verifyFollowTimes($followTimes) {
        $result = ($followTimes >= 1) && is_numeric($followTimes);
        \AppException::ifNoExistThrowException($result, C('ERROR_CODE.BET_NUMBER_ERROR'));
        return $result;
    }
    
    protected function verifyCouponExchange($code) {
        $coupon_valid = preg_match('/^\w{12}$/', $code);
        $channel_coupon_valid = preg_match('/^\w{8}$/', $code);
        $result = $coupon_valid || $channel_coupon_valid;
        \AppException::ifNoExistThrowException($result, C('ERROR_CODE.COUPON_EXCHANGE_INVALID'));
        return (bool)$result;
    }
    
    protected function verifyAct($act) {
        $result = array_key_exists($act, C('ACT_MAPPING'));
        \AppException::ifNoExistThrowException($result, C('ERROR_CODE.INVALID_INTERFACE'));
        return $result;
    }
    
    protected function verifySms($sms_validation) {
        $result = preg_match('/^\d{6}$/', $sms_validation);
        \AppException::ifNoExistThrowException($result, C('ERROR_CODE.SMS_VERIFY_ERROR'));
        return (bool)$result;
    }
    
}

?>