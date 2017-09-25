<?php
namespace Content\Controller;
use Think\Controller;
/**
 * @date 2014-12-8
 * @author tww <merry2014@vip.qq.com>
 */
class ActivityController extends Controller{
	public function index(){
		$activity_list = D('Activity')->getActivities();
		$this->assign('activity_list', $activity_list);
		$this->display();
	}
	
	public function detail($id){
		$activity_detail = D('Activity')->getActivityInfoById($id);

		if (!empty($activity_detail['activity_target'])) {
			redirect($activity_detail['activity_target']);
			return;
		}

		if($activity_detail['activity_position']==C('ACTIVITY_POSITION.RECHARGE')){
			$jump_data = urlencode(base64_encode(json_encode(array('money'=>20))));
			$description_text = '现在就去充值'; 
			$op_code = 10702;
		}elseif($activity_detail['activity_position']==C('ACTIVITY_POSITION.BUY')){
			$jump_data = urlencode(base64_encode(json_encode(array('lottery_id'=>$activity_detail['lottery_id']))));
			$description_text = '立即购买';
			$op_code = 10701;
		}elseif($activity_detail['activity_position']==C('ACTIVITY_POSITION.ID_CARD')){
			$jump_data = '';
			$description_text = '马上去完善身份证信息';
			$op_code = 10703;
// 		} elseif ($activity_detail['activity_position'] == C('ACTIVITY_POSITION.JUMP_TO_JC_BETTING')) {
// 			$jump_data = '';
// 			$description_text = '马上投注比赛';
// 			$op_code = 10705;
		}elseif($activity_detail['activity_position']==C('ACTIVITY_POSITION.BUY_COUPON')){
			$jump_data = urlencode(base64_encode(json_encode(array('id'=>$activity_detail['coupon_id']))));
			$description_text = '购买红包';
			$op_code = 10706;
		} else {
			$jump_data = '';
			$op_code = '';
		}
		$this->assign('op_code', $op_code);
		$this->assign('des', $description_text);
		$this->assign('jump_data', $jump_data);
		$this->assign('activity_detail', $activity_detail);
		$this->display();
	}

	public function fz518(){
		$channel_key = substr($_SERVER['PATH_INFO'], 17);
		$channel_id = M('Channel')->where(array('channel_key'=>$channel_key))->getField('channel_id');
		if (empty($channel_id)) {
			$channel_id = 2;
		}
		
		$now = date('Y-m-d H:i:s');

		$where = array();
		$where['channel_id'] = $channel_id;
		$where['cc_status'] = 0;
		$where['cc_start_time'] = array('lt', $now);
		$where['_string'] = 'cc_end_time = "000-00-00 00:00:00" or cc_end_time > "'.$now.'"';

		$exchange_codes = M('ChannelCoupon')->where($where)->limit(10)->select();

		shuffle($exchange_codes);

		$exchange_code = array_shift($exchange_codes);

		if ($channel_key == 'n9Mi2nPe') {
			$exchange_code['cc_code'] = '';
		}

		$this->assign('code', $exchange_code['cc_code']);

		$this->display();
	}

	public function fz520(){
		$this->display();
	}

	public function europeFirst($id=0){
		$information_detail = D('Information')->getInfoById($id);

		if (stripos($information_detail['information_content'], '老虎一元购') !== false) {
			$information_detail['information_content'] = substr($information_detail['information_content'], 224);
		}

		$this->assign('information_detail', $information_detail);

		$jump_data = urlencode(base64_encode(json_encode(array('lottery_id'=>6))));
		$description_text = '立即购买';
		$op_code = 10701;
		$this->assign('jump_data', $jump_data);
		$this->assign('description_text', $description_text);
		$this->assign('op_code', $op_code);

		$this->display('information');
	}

	public function recharge20(){
		$jump_data = urlencode(base64_encode(json_encode(array('money'=>20))));
		$op_code = 10702;

		$this->assign('op_code', $op_code);
		$this->assign('jump_data', $jump_data);

		$this->display();
	}

	public function getLastestAndroidApk(){
		// redirect('http://android.myapp.com/myapp/detail.htm?apkName=co.sihe.tigerlottery');return;
        $type = C('EXTEND_URL_CONFIG.ANDROID1');
        $apk_url = $this->_getApkURL($type);
        redirect($apk_url);
		//redirect('http://tclottery.oss-cn-hangzhou.aliyuncs.com/apk/tigercai_publicservice_V1.4_13.apk');
	}

    public function getLastestAndroidApkOnly(){
        $type = C('EXTEND_URL_CONFIG.ANDROID2');
        $apk_url = $this->_getApkURL($type);
        redirect($apk_url);
        //redirect('http://tclottery.oss-cn-hangzhou.aliyuncs.com/apk/tigercai_publicservice_V1.4_13.apk');
    }

    public function getLastestAndroidApk2(){
        $type = C('EXTEND_URL_CONFIG.ANDROID1');
        $apk_url = $this->_getApkURL($type);
        redirect($apk_url);
    }

	public function getiOSUrl(){
        $type = C('EXTEND_URL_CONFIG.IOS');
        $apk_url = $this->_getApkURL($type);
        redirect($apk_url);
		//redirect('https://itunes.apple.com/us/app/lao-hu-cai-piao-ti-cai-er/id1127299025?l=zh&ls=1&mt=8');
	}

	public function qrRedirect(){
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		if (stristr($user_agent, 'iphone') !== false || stristr($user_agent, 'ipad') !== false ) {
			redirect('https://itunes.apple.com/cn/app/lao-hu-cai-piao-er-chuan-yi/id1127299025?mt=8');
		} else {
			redirect('http://tclottery.oss-cn-hangzhou.aliyuncs.com/apk/tiger_blue_yidongmm_V2.0_17.apk');
		}
	}

    /**
     * 通过推广URL配置表获取apk_url
     * @param $type
     * @return mixed
     */
    private function _getApkURL($type){
        $apk_url = M('extend_url_config')->where(array('id'=>$type))->order('updatetime DESC')->getField('url');
        return $apk_url;
    }

	public function tigerAndroid(){
		$source = $_REQUEST['source'];
		$info['title'] = '激情欧洲杯，和好友一起分享';
		$info['webpage_url'] = 'http://phone.api.tigercai.com/index.php/Content/Activity/tigerAndroid';
		$info['thumb_image'] = 'http://tclottery.oss-cn-hangzhou.aliyuncs.com/app_img/icon190x190%5B1%5D.jpeg';
		$jump_data = urlencode(base64_encode(json_encode($info)));
		$op_code = 10705;
		$this->assign('jump_data', $jump_data);
		$this->assign('op_code', $op_code);
		$this->assign('src',$source);
		$this->display();
	}


	public function sendMessage(){
		exit;
		set_time_limit(0);

		$activity_message_template_id = '1';

		$data = array();

		$tels = $this->_getActivityUserTel();

		$success_count = 0;

		$faile_tels = array();

		if ($tels) {
			$tel_arr = array_chunk($tels, 500);
			foreach ($tel_arr as $tel) {
				$tel_string = implode(',', $tel);
				$res = sendMarketingMessage($tel_string, $data, $activity_message_template_id);
				// dump($res);exit;
				if (!$res || $res['success'] != 'success') {
					$faile_tels = array_merge($faile_tels, $tel);
				} else {
					$success_count += count($tel);
				}
				sleep(2);

			}

		}

		echo '累计发送'.count($tels).'条短信，成功'.$success_count.'条，失败'.count($faile_tels).'条。';
		if (count($faile_tels) > 0) {
			echo '<br/>';
			echo '失败手机号为：'.implode(',', $faile_tels);
		}

	}

	public function forTest(){
		$data = array();
		$data['20160528'] = array(
			'20160528-6-101' => array(
				'home_team' => '赞歧',
				'guest_team' => '水户',
				'schedule_status' => '1',
				'schedule_score' => '0:0',
				'schedule_half_score' => '0:0',
				'schedule_progress' => '0',
				'schedule_class' => '日职乙',
				'schedule_date' => '20160528',
				'schedule_deadline' => '23:55'
				),
			'20160528-6-102' => array(
				'home_team' => '德岛漩涡',
				'guest_team' => '北九州',
				'schedule_status' => '2',
				'schedule_score' => '1:1',
				'schedule_half_score' => '0:0',
				'schedule_progress' => '23',
				'schedule_class' => '日职乙',
				'schedule_date' => '20160528',
				'schedule_deadline' => '23:55'
				),
			'20160528-6-103' => array(
				'home_team' => '札幌',
				'guest_team' => '山口',
				'schedule_status' => '2',
				'schedule_score' => '2:1',
				'schedule_half_score' => '1:1',
				'schedule_progress' => '70',
				'schedule_class' => '日职乙',
				'schedule_date' => '20160528',
				'schedule_deadline' => '23:55'
				),
			'20160528-6-104' => array(
				'home_team' => '京都不死',
				'guest_team' => '横滨FC',
				'schedule_status' => '3',
				'schedule_score' => '2:1',
				'schedule_half_score' => '1:1',
				'schedule_progress' => '90+',
				'schedule_class' => '日职乙',
				'schedule_date' => '20160528',
				'schedule_deadline' => '23:55'
				),

			'20160528-6-105' => array(
				'home_team' => '清水鼓动',
				'guest_team' => '群马',
				'schedule_status' => '3',
				'schedule_score' => '2:1',
				'schedule_half_score' => '0:1',
				'schedule_progress' => '90+',
				'schedule_class' => '日职乙',
				'schedule_date' => '20160528',
				'schedule_deadline' => '23:55'
				)

		);

		$data['20160529'] = array(
			'20160529-6-101' => array(
				'home_team' => '横滨',
				'guest_team' => '太阳神',
				'schedule_status' => '1',
				'schedule_score' => '0:0',
				'schedule_half_score' => '0:0',
				'schedule_progress' => '0',
				'schedule_class' => '日职乙',
				'schedule_date' => '20160529',
				'schedule_deadline' => '11:58'
				),
			'20160529-6-102' => array(
				'home_team' => '福冈',
				'guest_team' => '广岛',
				'schedule_status' => '1',
				'schedule_score' => '0:0',
				'schedule_half_score' => '0:0',
				'schedule_progress' => '0',
				'schedule_class' => '日职乙',
				'schedule_date' => '20160529',
				'schedule_deadline' => '23:55'
				),
			'20160529-6-103' => array(
				'home_team' => '仙台维加',
				'guest_team' => '新泻天鹅',
				'schedule_status' => '1',
				'schedule_score' => '0:0',
				'schedule_half_score' => '0:0',
				'schedule_progress' => '0',
				'schedule_class' => '日职乙',
				'schedule_date' => '20160529',
				'schedule_deadline' => '23:55'
				),
			'20160529-6-104' => array(
				'home_team' => '首尔FC',
				'guest_team' => '全男天龙',
				'schedule_status' => '1',
				'schedule_score' => '0:0',
				'schedule_half_score' => '0:0',
				'schedule_progress' => '0',
				'schedule_class' => '日职乙',
				'schedule_date' => '20160529',
				'schedule_deadline' => '23:55'
				),

			'20160529-6-105' => array(
				'home_team' => '鹿岛鹿角',
				'guest_team' => '甲府',
				'schedule_status' => '1',
				'schedule_score' => '0:0',
				'schedule_half_score' => '0:0',
				'schedule_progress' => '0',
				'schedule_class' => '日职乙',
				'schedule_date' => '20160529',
				'schedule_deadline' => '23:55'
				)

		);

		echo $this->ajaxReturn($data);


	}

	public function test(){
		set_time_limit(0);
		
		$error = array();
		for($i=1; $i<118135; $i++){
			$error_order_id = array();
			$sql = "SELECT order_id, uid, (order_total_amount - order_coupon_consumption) as money FROM cp_order WHERE order_status = 3 and order_total_amount > order_coupon_consumption and uid = $i and follow_bet_id = 0 ";
			$orders = M('Order')->query($sql);

			if (empty($orders)) {
				continue;
			}

			$log_map = array();
			$log_map['uid'] = $i;
			$log_map['ual_type'] = 2;
			$logs = M('UserAccountLog')->where($log_map)->select();
			$logs = $this->handleLogs($logs);

			foreach ($orders as $order) {
				$log = $logs[$order['order_id']];
				$order_money = $order['money'];
				$log_decute_money = abs($log['ual_amount']);
				$log_frozen_money = abs($log['ual_frozen_amount']);
				if ($order_money != $log_decute_money && $order_money != $log_frozen_money) {
					$error_order_id[] = $order['order_id'];
				}
			}

			if (!empty($error_order_id)) {
				$error[$i] = $error_order_id;
			}

		}

		if (!empty($error)) {
			$error_serialize = serialize($error);
			file_put_contents('error.log', $error_serialize);
		}

		echo 'finish!';

		echo '<pre>';

		print_r($error);

	}


	private function handleLogs($logs){
		if (empty($logs)) {
			return array();
		}

		$res = array();
		foreach ((array)$logs as $log) {
			preg_match('/(\d+)/', $log['ual_remark'], $match);
			$res[$match[0]] = $log;
		}

		return $res;
	}

	public function lianlianNotify(){
		error_log(date('Y-m-d H:i:s').':'.print_r($_SERVER, true).PHP_EOL.print_r($_REQUEST, true).PHP_EOL.PHP_EOL, 3, './hgy0613.log');
		echo 'ok';
	}

	private function _getActivityUserTel(){
		// $tels = D('User')->getField('user_telephone', true);
		$content = file_get_contents('D:\www\tigercai\Server\web\trunk\user.txt');
		$tels = explode(PHP_EOL, $content);
		return $tels;
	}

	public function jiajiang(){
		$jump_data = urlencode(base64_encode(json_encode(array('lottery_id'=>606))));
		$description_text = '立即购买';
		$op_code = 10701;
		$this->assign('op_code', $op_code);
		$this->assign('des', $description_text);
		$this->assign('jump_data', $jump_data);
		$this->display();
	}

	public function jiajiang161017_ssq(){
		$outside = I('outside');
		if (empty($outside)) {
			$jump_data = urlencode(base64_encode(json_encode(array('lottery_id'=>1))));
			$jump_url = '/api/tiger?act=10701&em=0&data='.$jump_data;
		} else {
			$jump_url = 'javascript:void(0)';
		}

		$this->assign('jump_url', $jump_url);	
		$this->display();
	}

	public function jiajiang161017_djy(){
		$outside = I('outside');
		
		if (empty($outside)) {
			$jump_data = urlencode(base64_encode(json_encode(array('lottery_id'=>4))));
			$jump_url = '/api/tiger?act=10701&em=0&data='.$jump_data;
		} else {
			$jump_url = 'javascript:void(0)';
		}

		$this->assign('jump_url', $jump_url);
		$this->display();
	}

	public function jiajiang161017_11_5(){
		$outside = I('outside');

		if (empty($outside)) {
			$jump_data = urlencode(base64_encode(json_encode(array('lottery_id'=>8))));
			$jump_url = '/api/tiger?act=10701&em=0&data='.$jump_data;
		} else {
			$jump_url = 'javascript:void(0)';
		}

		$this->assign('jump_url', $jump_url);
		$this->display();
	}

	public function nba17(){
		$outside = I('outside');

		if (empty($outside)) {
			$jump_data = urlencode(base64_encode(json_encode(array('lottery_id'=>7))));
			$jump_url = '/api/tiger?act=10701&em=0&data='.$jump_data;
		} else {
			$jump_url = 'javascript:void(0)';
		}

		$this->assign('jump_url', $jump_url);
		$this->display();
	}

    public function jiajiang_11Gold(){
        $jump_data = urlencode(base64_encode(json_encode(array('lottery_id'=>4))));
        $jump_url = '/api/tiger?act=10701&em=0&data='.$jump_data;
        $this->assign('jump_url', $jump_url);
        $this->display();
    }

    public function new_11_5_activity(){
        $this->display();
    }
}