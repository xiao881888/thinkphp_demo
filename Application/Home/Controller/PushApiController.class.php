<?php

namespace Home\Controller;

use Home\Controller\GlobalController;
use Home\Util\Factory;
use Think\Exception;

/**
 * 统一推送接口
 * @package Home\Controller
 */
class PushApiController extends PushBaseController
{

	public function __construct(){
		parent::__construct();
	}

	public function index(){
		$response = array();
		try{
			$act_type = I('act_type');
			$method = $this->_getFunctionNameByActType($act_type);
			$response['msg'] = $this->$method($act_type);
			$response['error_code'] = 0;
		}catch(Exception $e){
			ApiLog('推送出现异常，异常信息:'.$e->getMessage().';异常文件:'.$e->getFile().';异常行数:'.$e->getLine(),'PushException');
			$response['error_code'] = 1;
			$response['msg'] = $e->getMessage();
		}

		$this->ajaxReturn($response);
	}

	private function _getFunctionNameByActType($act_type){
		$act_array = C('UNITE_PUSH_ACT');
		if(!in_array($act_type,array_keys($act_array))){
			throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.METHOD_NOT_EXIST'),C('UNITE_PUSH_EXCEPTION_CODE.METHOD_NOT_EXIST'));
		}
		$method = 'push'.C('UNITE_PUSH_ACT.'.$act_type);
		$method_status = method_exists(__CLASS__,$method);
		if(!$method_status){
			throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.METHOD_NOT_EXIST'),C('UNITE_PUSH_EXCEPTION_CODE.METHOD_NOT_EXIST'));
		}
		return $method;
	}


	public function pushActivityMessage($act_type)
	{
		$error_msg = '';

		//设置程序执行时间的函数
		set_time_limit(0);

		//函数设置与客户机断开是否会终止脚本的执行
		ignore_user_abort(true);

		$message = $this->getPushMessage($act_type); //推送信息
		$uidList = I('uid', '');//推送用户组
		$type = I('type', '');//推送类型 1：订单详情， 2：下注页面， 3：充值页面， 4：webview
		$paramers = I('paramers', '');//附加参数
        $app_id = I('app_id', '');//推送APP——name

		if (!empty($paramers)) {
			$paramers = json_decode($paramers, true);
		}

		if ($uidList === 'all') {
			//发送全部用户
			$error_msg = $this->push($uidList,C('UNITE_PUSH_OF_ALL'),$message,$type,$paramers,0,$app_id);
		} else {
			//发送个人用户
			$error_msg = $this->push($uidList,C('UNITE_PUSH_OF_PERSONAL'),$message,$type,$paramers,0,$app_id);
		}

		return $error_msg;
	}

    public function pushExpireCouponMessage($act_type)
    {
        $error_msg = '';

        //设置程序执行时间的函数
        set_time_limit(0);

        //函数设置与客户机断开是否会终止脚本的执行
        ignore_user_abort(true);

        $coupon_list = I('coupon_list','');
        $coupon_list = json_decode($coupon_list,true);
        foreach($coupon_list as  $coupon_info){
            $coupon_info = D('Coupon')->getCouponInfo($coupon_info['coupon_id']);
            $coupon_name = $coupon_info['coupon_display_name'];
            $send_msg_template = C('UNITE_PUSH_TEMPLATE.EXPIRE_COUPON');
            $push_message = sprintf($send_msg_template,$coupon_name);
            $uid = $coupon_info['uid'];
            $error_msg = $this->push($uid,C('UNITE_PUSH_OF_PERSONAL'),$push_message,'','',0);
        }
        return $error_msg;
    }

    public function pushOrderNotice($act_type) {
	    $order_id = I('order_id');
        $order_info 	 = D('Order')->getOrderInfo($order_id);
        if(empty($order_info)){
            throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.ORDER_NOT_EXIST'),C('UNITE_PUSH_EXCEPTION_CODE.ORDER_NOT_EXIST'));
        }
        $uid_list = $order_info['uid'];
        $push_message 	 = $this->getPushMessage($act_type);
        $type = C('PUSH_NEXT_ACTION_TYPE.ORDER_DETAILE_PAGE');
        $paramers['order_id'] = $order_id;
        $paramers['lottery_id'] = $order_info['lottery_id'];
        $response_msg = $this->push($uid_list,C('UNITE_PUSH_OF_PERSONAL'),$push_message,$type,$paramers);
        return $response_msg;
    }

	public function pushTestIssuePrizeNum($act_type){
		$lottery_id = I('lottery_id','');
		$issue_no   = I('issue_no','');
		$prize_num  = I('prize_number','');
		$uidList = '1,2,106751,117832,361,128104,144296';
		if(!$lottery_id || !$issue_no || !$prize_num){
			throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.MESSAGE_IS_NULL'),C('UNITE_PUSH_EXCEPTION_CODE.MESSAGE_IS_NULL'));
		}

		$aready_push_value = $lottery_id.'-'.$issue_no;
		if($this->_isAreadyPushList($act_type,$aready_push_value)){
			throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.AREADY_PUSHED'),C('UNITE_PUSH_EXCEPTION_CODE.AREADY_PUSHED'));
		}

		$send_msg = $this->getPushMessage($act_type);

		$response_msg = $this->push($uidList,C('UNITE_PUSH_OF_PERSONAL'),$send_msg);

		$this->_setAreadyPushList($act_type,$aready_push_value);

		return $response_msg;

	}

	public function pushIssuePrizeNum($act_type){
		$lottery_id = I('lottery_id','');
		$issue_no   = I('issue_no','');
		$prize_num  = I('prize_number','');
		$uidList = 'all';

		if(!$lottery_id || !$issue_no || !$prize_num){
			throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.MESSAGE_IS_NULL'),C('UNITE_PUSH_EXCEPTION_CODE.MESSAGE_IS_NULL'));
		}
		$aready_push_value = $lottery_id.'-'.$issue_no;
		if($this->_isAreadyPushList($act_type,$aready_push_value)){
			throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.AREADY_PUSHED'),C('UNITE_PUSH_EXCEPTION_CODE.AREADY_PUSHED'));
		}

		$send_msg = $this->getPushMessage($act_type);

		$response_msg = $this->push($uidList,C('UNITE_PUSH_OF_ALL'),$send_msg);

		$this->_setAreadyPushList($act_type,$aready_push_value);

		return $response_msg;
	}

	public function pushFullReducedCouponInfo($act_type,$uid,$coupon_value){
		$_POST['uid'] = $uid;
		$_POST['coupon_value'] = $coupon_value;
		$_POST['act_type'] = $act_type;
		$uidList = $uid;
		$send_msg = $this->getPushMessage($act_type);
		$response_msg = $this->push($uidList,C('UNITE_PUSH_OF_PERSONAL'),$send_msg,6);
		return $response_msg;
	}

    public function pushTestGoalEvent($act_type){

        $data = I('message');
        $data = json_decode($data,true);

        if(empty($data)){
            throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.MESSAGE_IS_NULL'),C('UNITE_PUSH_EXCEPTION_CODE.MESSAGE_IS_NULL'));
        }

        $event_id = I('event_id',0);
        if(empty($data)){
            throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.MESSAGE_IS_NULL'),C('UNITE_PUSH_EXCEPTION_CODE.MESSAGE_IS_NULL'));
        }

        $match_info = $data['match_info'];
        $third_party_schedule_id = $data['schedule_qt_id'];
        $paramers = empty($third_party_schedule_id) ? array() : array('third_party_schedule_id'=>$third_party_schedule_id,'lottery_id'=>6);

        $schedule_id = $match_info['schedule_id'];
        if($this->_isAreadyPushList($act_type,$event_id)){
            throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.AREADY_PUSHED'),C('UNITE_PUSH_EXCEPTION_CODE.AREADY_PUSHED'));
        }

        $uids = array(2,3,5,117832,361,128104,118,144296);
        if(empty($uids)){
            $response_msg = '没有需要推送的用户';
            return $response_msg;
        }

        $uids = $this->_filterPushSwitchOffUids($uids);

        $send_msg = $this->getPushMessage($act_type);

        $response_msg = $this->push($uids,C('UNITE_PUSH_OF_PERSONAL'),$send_msg,7,$paramers,1);

        $this->_setAreadyPushList($act_type,$event_id);

        return $response_msg;

    }

	public function pushGoalEvent($act_type){
		$data = I('message');
		$data = json_decode($data,true);
		if(empty($data)){
			throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.MESSAGE_IS_NULL'),C('UNITE_PUSH_EXCEPTION_CODE.MESSAGE_IS_NULL'));
		}
		$event_id = I('event_id',0);
		if(empty($data)){
			throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.MESSAGE_IS_NULL'),C('UNITE_PUSH_EXCEPTION_CODE.MESSAGE_IS_NULL'));
		}
		$match_info = $data['match_info'];
		$schedule_id = $match_info['schedule_id'];
		if($this->_isAreadyPushList($act_type,$event_id)){
			throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.AREADY_PUSHED'),C('UNITE_PUSH_EXCEPTION_CODE.AREADY_PUSHED'));
		}
		$uids = $this->_getPushGoldEventUsers($schedule_id);
		if(empty($uids)){
            $response_msg = '没有需要推送的用户';
            return $response_msg;
        }
        $uids = $this->_filterPushSwitchOffUids($uids);
        $send_msg = $this->getPushMessage($act_type);

        $response_msg = $this->push($uids,C('UNITE_PUSH_OF_PERSONAL'),$send_msg,7,array(),1);
        $this->_setAreadyPushList($act_type,$event_id);
        return $response_msg;
	}

    public function pushVipGifts($act_type)
    {
        $error_msg = '';

        //设置程序执行时间的函数
        set_time_limit(0);

        //函数设置与客户机断开是否会终止脚本的执行
        ignore_user_abort(true);

        $message = $this->getPushMessage($act_type); //推送信息
        $uidList = I('uids', '');//推送用户组
        $app_id = I('app_id', '');//推送APP——name
        $error_msg = $this->push($uidList,C('UNITE_PUSH_OF_PERSONAL'),$message,'','',0,$app_id);
        return $error_msg;
    }

	private function _filterPushSwitchOffUids($uids){
        $limit_uids = array();
        $new_uids = array();
        $push_device_list = $this->_getPushDeviceList($uids);
        foreach($push_device_list as $push_device_info){
            $uid = $push_device_info['uid'];
            $new_uids[] = $uid;
            if($push_device_info['psc_status'] === '0'){
                //禁用
                $limit_uids[] = $uid;
            }elseif($push_device_info['psc_status'] === '1'){
                //启用  勿扰时间
                $not_disturb_time = $push_device_info['psc_not_disturb_time'];
                $is_not_disturb_time = $this->_isNotDisturbTime($not_disturb_time);
                if($is_not_disturb_time){
                    $limit_uids[] = $uid;
                }
            }
        }
        return array_diff($new_uids,$limit_uids);
	}

	private function _getPushDeviceList($uids){
        $where['uid'] = array('IN', $uids);
        $where['pd_status'] = 1; //启用
        $order_by = 'pd_modify_time DESC';
        return  M('PushDevice')->alias('pd')->join('LEFT JOIN cp_push_switch_config psc ON pd.pd_device_token = psc.psc_device_token')->where($where)->group('pd_device_token')->order($order_by)->select();
    }

	private function _isNotDisturbTime($not_disturb_time){
	    if(empty($not_disturb_time)){
	        return false;
        }
        $not_disturb_time = explode('_',$not_disturb_time);
	    $start_time = (int)substr($not_disturb_time[0],0,2);
        $end_time = (int)substr($not_disturb_time[1],0,2);
        $not_time = (int)date("H");
        $limit_time_list = $this->_getLimitTimeList($start_time,$end_time);
        if(in_array($not_time,$limit_time_list)){
            return true;
        }
        return false;
    }

    private function _getLimitTimeList($start_time,$end_time){
        $data = array();
        if($start_time > $end_time){
            for($i = $start_time; $i <= 24;$i++){
                $data[] = $i;
            }
            for($i = 0; $i <= $end_time;$i++){
                $data[] = $i;
            }
        }else{
            for($i = $start_time; $i <= $end_time;$i++){
                $data[] = $i;
            }
        }
        return $data;
    }

	private function _delScheduleIdFromRedis($schedule_id){
		$this->redis->del('tiger_api:push_goal_event:notify_uids'.$schedule_id);
		$this->redis->del('tiger_api:push_goal_event:jc_schedule_list_'.$schedule_id);
		$this->redis->sRem('tiger_api:push_goal_event:jc_schedule_ids',$schedule_id);
	}

	private function _getPushGoldEventUsers($schedule_id){
		return $this->redis->sMembers('tiger_api:push_goal_event:notify_uids'.$schedule_id);
	}


	private function getPushMessage($act_type){
		$message = '';
		$get_message_method = 'getPushMessageOf'.C('UNITE_PUSH_ACT.'.$act_type);

		$method_status = method_exists(__CLASS__,$get_message_method);
		if(!$method_status){
			throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.METHOD_NOT_EXIST'),C('UNITE_PUSH_EXCEPTION_CODE.METHOD_NOT_EXIST'));
		}

		$message = $this->$get_message_method();

		if(empty($message)){
			throw new Exception(C('UNITE_PUSH_EXCEPTION_MSG.MESSAGE_IS_NULL'),C('UNITE_PUSH_EXCEPTION_CODE.MESSAGE_IS_NULL'));
		}
		return $message;
	}

	public function getPushMessageOfActivityMessage(){
		$message = I('msg','');
		return $message;
	}


	public function getPushMessageOfIssuePrizeNum(){
		$message = '';
		$lottery_id = I('lottery_id','');
		$issue_no   = I('issue_no','');
		$prize_num  = I('prize_number','');
		if($lottery_id && $issue_no && $prize_num){
			$lottery_name = $this->_getLotteryNameById($lottery_id);
			$send_msg_template = C('UNITE_PUSH_TEMPLATE.SSQ_DLT');
			$message = sprintf($send_msg_template,$prize_num,$lottery_name,$issue_no);
		}
		return $message;
	}

	public function getPushMessageOfTestIssuePrizeNum(){
		$message = '';
		$lottery_id = I('lottery_id','');
		$issue_no   = I('issue_no','');
		$prize_num  = I('prize_number','');
		if($lottery_id && $issue_no && $prize_num){
			$lottery_name = $this->_getLotteryNameById($lottery_id);
			$send_msg_template = C('UNITE_PUSH_TEMPLATE.SSQ_DLT');
			$message = sprintf($send_msg_template,$prize_num,$lottery_name,$issue_no);
		}
		return $message;
	}

	public function getPushMessageOfFullReducedCouponInfo(){
		$message = '';
		$coupon_value = I('coupon_value');
		$message = sprintf(C('UNITE_PUSH_TEMPLATE.COUPON'),$coupon_value);
		return $message;
	}

	public function getPushMessageOfGoalEvent(){
        $message = I('message');
        $message = json_decode($message,true);
		$match_info = $message['match_info'];
		$is_home = $message['is_home'];
		$happen_time = $message['happen_time'];
		$home_team_name = $match_info['home_team_name'];
		$guest_team_name = $match_info['guest_team_name'];
		$home_score = $match_info['home_score'];
		$guest_score = $match_info['guest_score'];
		//阿森纳59’进球！当前比分【阿森纳2-0皇家马德里】！
		if($is_home){
			$message = sprintf(C('UNITE_PUSH_TEMPLATE.GOAL_EVENT'),$home_team_name,$happen_time,$home_team_name,$home_score,$guest_score,$guest_team_name);
		}else{
			$message = sprintf(C('UNITE_PUSH_TEMPLATE.GOAL_EVENT'),$guest_team_name,$happen_time,$home_team_name,$home_score,$guest_score,$guest_team_name);
		}
		return $message;
	}

    public function getPushMessageOfTestGoalEvent(){
        $message = I('message');
        $message = json_decode($message,true);
        $match_info = $message['match_info'];
        $is_home = $message['is_home'];
        $happen_time = $message['happen_time'];
        $home_team_name = $match_info['home_team_name'];
        $guest_team_name = $match_info['guest_team_name'];
        $home_score = $match_info['home_score'];
        $guest_score = $match_info['guest_score'];
        //阿森纳59’进球！当前比分【阿森纳2-0皇家马德里】！
        if($is_home){
            $message = sprintf(C('UNITE_PUSH_TEMPLATE.GOAL_EVENT'),$home_team_name,$happen_time,$home_team_name,$home_score,$guest_score,$guest_team_name);
        }else{
            $message = sprintf(C('UNITE_PUSH_TEMPLATE.GOAL_EVENT'),$guest_team_name,$happen_time,$home_team_name,$home_score,$guest_score,$guest_team_name);
        }
        return $message;
    }

    public function getPushMessageOfVipGifts(){
        $message = I('msg','');
        return $message;
    }

    private function getPushMessageOfOrderNotice() {
        $order_id = I('order_id');
        $type = I('type');
        $order_info 	 = D('Order')->getOrderInfo($order_id);
        $uid = $order_info['uid'];
        //根据uid获取push config 看是否推送
        if ($type==1) {
            if($order_info['order_winnings_status'] == 1 && $order_info['order_offical_plus_amount'] > 0){
                $message_template = C('PUSH_MESSAGE_TEMPLATE.PLUS_WIN_PRIZE');
                $map['lottery_id'] = $order_info['lottery_id'];
                $lotteryInfo = M('Lottery')->where($map)->find();

                $replace_key = array('$1','$2','$3','$4');
                $replace_value = array(
                    $lotteryInfo['lottery_name'],
                    bcsub($order_info['order_winnings_bonus'], $order_info['order_offical_plus_amount'], 2),
                    $order_info['order_offical_plus_amount'],
                    $order_info['order_winnings_bonus']
                );
                $push_message = str_replace($replace_key, $replace_value, $message_template);
                return $push_message;
            }else{
                return C('PUSH_MESSAGE_TEMPLATE.WIN_PRIZE');
            }
        } elseif ($type==2) {
            return C('PUSH_MESSAGE_TEMPLATE.FAIL_TO_BUY_TICKET');
        } elseif ($type==3) {
            return C('PUSH_MESSAGE_TEMPLATE.FAIL_TO_FOLLOW_TICKET');
        } elseif ($type==4) {
            return C('PUSH_MESSAGE_TEMPLATE.FAIL_TO_TICKET_PRINTOUT');
        }
    }



	private function _getLotteryNameById($lottery_id){
		if($lottery_id == 1){
			return '双色球';
		}elseif($lottery_id == 3){
			return '大乐透';
		}
	}

	private function _isAreadyPushList($act_type,$aready_push_value){
		return $this->redis->sContains(C('UNITE_PUSH_REDIS_KEY').C('UNITE_PUSH_ACT.'.$act_type),$aready_push_value);
	}

	private function _setAreadyPushList($act_type,$aready_push_value){
		$this->redis->sAdd(C('UNITE_PUSH_REDIS_KEY').C('UNITE_PUSH_ACT.'.$act_type),$aready_push_value);
	}

}