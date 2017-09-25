<?php
namespace Home\Controller;
use Think\Controller;
use Home\Util\Factory;

class TestController extends Controller {
    
	private $sdk_version = 1;
	private $act = 0;
	private $header_encrypt_type = 0;
	private $header_length = 0;
	private $header_error;
	private $header_client_id = '';
	private $client_info = array();
	private $packet_body = '';
	private $response_error = 0;
	private $encrypt_type = 0;
	
	// 外网

    const SIGN_IV = 'B99ADAF9';
    const SIGN = 'B99ADAF9028095835AD0C010';
    const TOKEN = 'a4793ce1304acf28fc6f51b71f2c2574';
	/*const SIGN_IV = 'AEAC8A4D';
	const SIGN = 'AEAC8A4DDB28CF5FEA10C7BE';
	const TOKEN = 'f95e3a779685fa04d0dc8d6985c310ad';*/
	
	// 内网
// 	const SIGN_IV = '2A128423';
// 	const SIGN = '2A128423E37E0B321FB08C5C';
// 	const TOKEN = 'f056de9b7bdd122c852068f76147174d';

    public function a(){
    	$raw_json_params = 'aaa';
    	$key = md5($params->session . uniqid());
    	$redis_instance = Factory::createRedisObj();
    	echo $redis_instance->SETEX($key, 3600, $raw_json_params);
    	die();
    	$tels = array('13113957339', '18612986411', '15996330208', '15358110532', '15021221817', '13998791112', '13801188628', '18704183337', '14795618342', '13867155983', '13453677874', '13803227822', '13536434004', '15778879291', '15001239330', 
'13821239983', '15676667352', '15120900783', '15063585789', '18877170492', '18857663913', '18331625079', '15840930930', '13220056778', '18352512922', '15378150084', '13581705952', '15907888999', '13641885476', '18500936006',
'18612000068', '18827367331', '18273131334', '18674343000', '13586520131', '18170076688', '13352005957', '18509558888', '13527260707', '15300036731', '15778123257', '15070818001', '18874750667', '15997405226', '18600840465',
'18614985988', '18858072321', '13958001688', '13714315945', '13693613668', '18668080531', '18668070531', '18301957127', '15123619007', '18608313325', '13669780272', '15968921426', '18653390999', '15376788182', '18758297599',
'13509131619', '15369030562', '13337227523', '18301288298', '18651592162', '18787887470', '18609818777', '18202752073', '18668060531', '13578150277', '18298201200', '18617004291', '13985054477', '13543319700', '15002076700',
'15557157038', '18506060349', '13521257653', '13856960105', '13946112788', '15021502070', '13777975314', '18815293485', '15757158317', '15068835801', '13754322070', '15757111315', '18610291132', '18624265555', '18225513775', 
'15959284524', '13770721210', '18017880168', '13911716403', '13659635666', '18385904876', '13770788583', '13683227427', '13539831548', '13511487866', '15829103997', '13910393434', '15327317785', '15836113230', '15300146870',
'13501117727', '18612259960', '13810071236', '18612832441', '13501031931', '15327317788', '18611167183', '13476050384', '13379957979', '15377609602', '15897686477', '15851993878', '13984057688', '13395713520', '18752528529',
'13767730277', '13908062146', '13247582583');
//     	$map['user_telephone'] = array('IN',$tels);
//     	$user_list = D('User')->where($map)->select();
    	
    	$tel_set = array(
    			array('13113957339','0.00 '), array('18612986411','0.00 '), array('15996330208','0.00 '), array('15358110532','6.02 '), array('15021221817','0.00 '), array('13998791112','0.00 '), array('13801188628','0.00 '),
    			array('18704183337','0.00 '), array('14795618342','0.00 '), array('13867155983','72.00 '), array('13453677874','0.00 '), array('13803227822','0.00 '), array('13536434004','0.00 '), array('15778879291','0.00 '),
    			array('15001239330','4.00 '), array('13821239983','0.00 '), array('15676667352','1.98 '), array('15120900783','0.00 '), array('15063585789','0.00 '), array('18877170492','0.00 '), array('18857663913','1.00 '),
    			array('18331625079','0.00 '), array('15840930930','0.56 '), array('13220056778','0.00 '), array('18352512922','0.00 '), array('15378150084','1.00 '), array('13581705952','0.00 '), array('15907888999','20.26 '),
    			array('13641885476','0.00 '), array('18500936006','0.00 '), array('18612000068','0.00 '), array('18827367331','10.00 '), array('18273131334','0.00 '), array('18674343000','0.00 '), array('13586520131','0.00 '),
    			array('18170076688','0.00 '), array('13352005957','0.00 '), array('18509558888','100.00 '), array('13527260707','0.00 '), array('15300036731','0.00 '), array('15778123257','0.00 '), array('15070818001','0.00 '),
    			array('18874750667','0.00 '), array('15997405226','0.00 '), array('18600840465','0.00 '), array('18614985988','10.00 '), array('18858072321','0.00 '), array('13958001688','50.00 '), array('13714315945','1.67 '),
    			array('13693613668','100.00 '), array('18668080531','0.00 '), array('18668070531','0.00 '), array('18301957127','0.00 '), array('15123619007','0.00 '), array('18608313325','8.95 '), array('13669780272','1.29 '),
    			array('15968921426','50.00 '), array('18653390999','0.00 '), array('15376788182','0.00 '), array('18758297599','0.00 '), array('13509131619','20.00 '), array('15369030562','0.00 '), array('13337227523','0.00 '),
    			array('18301288298','0.00 '), array('18651592162','0.00 '), array('18787887470','0.54 '), array('18609818777','0.00 '), array('18202752073','0.00 '), array('18668060531','0.00 '), array('13578150277','0.00 '),
    			array('18298201200','9.90 '), array('18617004291','0.00 '), array('13985054477','0.00 '), array('13543319700','0.00 '), array('15002076700','2.00 '), array('15557157038','0.00 '), array('18506060349','0.00 '),array('13521257653','0.00 '), array('13856960105','0.00 '), array('13946112788','0.00 '), array('15021502070','0.00 '), array('13777975314','48.80 '), array('18815293485','0.00 '), array('15757158317','0.00 '),
    			array('15068835801','0.00 '), array('13754322070','0.55 '), array('15757111315','1.60 '), array('18610291132','0.00 '), array('18624265555','1.00 '), array('18225513775','0.00 '), array('15959284524','0.00 '),
    			array('13770721210','2.00 '), array('18017880168','1.62 '), array('13911716403','42.70 '), array('13659635666','10.00 '), array('18385904876','0.00 '), array('13770788583','0.00 '), array('13683227427','0.00 '),
    			array('13539831548','65.20 '), array('13511487866','2.00 '), array('15829103997','0.00 '), array('13910393434','0.00 '), array('15327317785','0.00 '), array('15836113230','0.00 '), array('15300146870','0.00 '),
    			array('13501117727','4.73 '), array('18612259960','6.33 '), array('13810071236','1.68 '), array('18612832441','9.00 '), array('13501031931','43.99 '), array('15327317788','30.00 '), array('18611167183','0.00 '),
    			array('13476050384','0.00 '), array('13379957979','0.00 '), array('15377609602','0.00 '), array('15897686477','0.00 '), array('15851993878','16.00 '), array('13984057688','0.00 '), array('13395713520','0.00 '),
    			array('18752528529','0.00 '), array('13767730277','0.00 '), array('13908062146','0.00 '), array('13247582583','0.00 ')
    	);
    	
//     	$tel_set = array(
//     			array('15980228063','1.00'), 
//     	);
    	
    	foreach($tel_set as $user_item){
    		$tel = $user_item[0];
    		$money = floatval($user_item[1]);
    		$map['user_telephone'] = $tel;
    		$user_info = D('User')->where($map)->find();
    		if($user_info){
    			print_r($user_item);
    			print_r($user_info);
    			echo "<br>";
    			if($money){
    				$this->_addAccount($user_info['uid'], $money);
    			}
    		}else{
    			$this->_addUser($tel, $money);
    		}
//     		return false;
    	}
    }
    
    private function _addUser($tel,$user_account){
    	$channel_info['app_package']= '';
    	$channel_info['app_channel_id']='sihecp';
    	$channel_info['app_os'] = 2;
    	$uid = D('User')->register($tel, $tel.'1', array(), $channel_info);
    	if(!$uid){
    		echo $tel.'==='.$user_account.'<br>';
    		return false;
    	}else{
    		$addUserAccount = D('UserAccount')->addUserAccount($uid);
    		if(!$addUserAccount){
    			echo $tel.'==='.$user_account.'<br>';
    			 return false;
    		}
    	}
    	
    	if($user_account){
    		$add = $this->_addAccount($uid, $user_account);
    		echo $tel.'==='.$user_account.'<br>';
    		return false;
    	}
    	 
    }
    
    private function _addAccount($uid, $recharge_amount){
    	$new_recharge_data = $this->_addManualRechargeRecord($uid, $recharge_amount, 6, '', '四合彩票余额转移');
    	if ($new_recharge_data) {
    		$update_balance = D('UserAccount')->where('uid='.$uid)->setInc('user_account_balance', $recharge_amount);
    		if ($update_balance) {
    			$update_recharge_static = D('UserAccount')->where('uid='.$uid)->setInc('user_account_recharge_amount', $recharge_amount);
    	
    			if ($update_recharge_static) {
    				$user_account = D('UserAccount')->getUserAccount($uid);
    				$add_account_log = $this->_addRechargeAccountLog($new_recharge_data, $user_account, 1);
    	
    				if ($add_account_log) {
    					$add_success = true;
    				}
    			}
    		}
    	}
    	return $add_success;
    }
    
    private function _addRechargeAccountLog($recharge_data, $user_account_data, $operator=0){
    	$account_log = array();
    	$account_log['uid'] 				= $recharge_data['uid'];
    	$account_log['ual_type'] 			= 1;
    	$account_log['ual_amount'] 			= $recharge_data['recharge_amount'];
    	$account_log['ual_frozen_amount'] 	= 0;
    	$account_log['ual_balance'] 		= $user_account_data['user_account_balance'];
    	$account_log['ual_frozen_balance'] 	= $user_account_data['user_account_frozen_balance'];
    	$account_log['operator_id'] 		= $operator;
    	$account_log['ual_create_time'] 	= getCurrentTime();
    	$account_log['ual_remark'] 			= $recharge_data['recharge_id'];
    
    	return D('UserAccountLog')->add($account_log);
    }
    
    private function _addManualRechargeRecord($uid, $recharge_amount, $recharge_channel_id, $bank_deal_no, $recharge_remark){
    	$recharge_data = array();
    	$recharge_data['uid'] 					= $uid;
    	$recharge_data['recharge_channel_id'] 	= $recharge_channel_id;
    	$recharge_data['recharge_create_time'] 	= getCurrentTime();
    	$recharge_data['recharge_receive_time'] = getCurrentTime();
    	$recharge_data['recharge_status'] 		= 1;
    	$recharge_data['recharge_amount'] 		= $recharge_amount;
    	$recharge_data['recharge_operator_id'] 	= 1;
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

    //10311
    public function test10311(){
        $this->act = 10311;
        $data = array(
        );
        $public_para = $this->getPublicParameters();
        $request_body = array_merge($data, $public_para);
        $header = $this->_request($request_body);
        $this->_dumpHeader($header);
    }


    public function test10312(){
        $this->act = 10312;
        $data = array(
            'lottery_id'=>$_REQUEST['lottery_id'],
        );
        $public_para = $this->getPublicParameters();
        $request_body = array_merge($data, $public_para);
        $header = $this->_request($request_body);
        $this->_dumpHeader($header);
    }

    public function test10301(){
        $this->act = 10301;
        $data = array(
        );
        $public_para = $this->getPublicParameters();
        $request_body = array_merge($data, $public_para);
        $header = $this->_request($request_body);
        $this->_dumpHeader($header);
    }

    public function test11002(){
        $this->act = 11002;
        $data = array(
        );
        $public_para = $this->getPublicParameters();
        $request_body = array_merge($data, $public_para);
        $header = $this->_request($request_body);
        $this->_dumpHeader($header);
    }

    public function test11003(){
        $this->act = 11003;
        $data = array(
            'project_id' => I('id'),
        );
        $public_para = $this->getPublicParameters();
        $request_body = array_merge($data, $public_para);
        $header = $this->_request($request_body);
        $this->_dumpHeader($header);
    }


    public function test10110(){
        $this->act = 10110;
        $data = array(
        );
        $public_para = $this->getPublicParameters();
        $request_body = array_merge($data, $public_para);
        $header = $this->_request($request_body);
        $this->_dumpHeader($header);
    }

    public function test10109(){
        $this->act = 10109;
        $data = array(
        );
        $public_para = $this->getPublicParameters();
        $request_body = array_merge($data, $public_para);
        $header = $this->_request($request_body);
        $this->_dumpHeader($header);
    }

    public function test10306(){
        $this->act = 10306;
        $this->sdk_version = 7;
        $this->encrypt_type = 131;

        $data = array(
            'lottery_id'=>$_REQUEST['lottery_id'],
            'play_type' => $_REQUEST['play_type'],
        );

        $public_para = $this->getPublicParameters();
        $request_body = array_merge($data, $public_para);
        $header = $this->_request($request_body);
        $this->_dumpHeader($header);
    }

    public function test10707(){
        $this->act = 10707;
        $this->sdk_version = 8;
        $this->encrypt_type = 131;
        $issue_no = (int)I('no');
        $data = array(
            'follow_detail'=>array(
                array(
                    'issue_no'=>$issue_no,
                    'multiple' =>1,
                    'total_amount' =>2,
                ),
                array(
                    'issue_no'=>$issue_no+1,
                    'multiple' =>1,
                    'total_amount' =>2,
                ),
                array(
                    'issue_no'=>$issue_no+2,
                    'multiple' =>2,
                    'total_amount' =>4,
                ),
                array(
                    'issue_no'=>$issue_no+3,
                    'multiple' =>4,
                    'total_amount' =>8,
                ),
                array(
                    'issue_no'=>$issue_no+4,
                    'multiple' =>7,
                    'total_amount' =>14,
                ),
                5=>array(
                    'issue_no'=>$issue_no+5,
                    'multiple' =>12,
                    'total_amount' =>24,
                ),
                array(
                    'issue_no'=>$issue_no+6,
                    'multiple' =>21,
                    'total_amount' =>42,
                ),
                array(
                    'issue_no'=>$issue_no+7,
                    'multiple' =>37,
                    'total_amount' =>74,
                ),
                array(
                    'issue_no'=>$issue_no+8,
                    'multiple' =>65,
                    'total_amount' =>130,
                ),
                array(
                    'issue_no'=>$issue_no+9,
                    'multiple' =>115,
                    'total_amount' =>230,
                ),
            ),
            'follow_times' => 0,
            'lottery_id' => 4,
            'is_win_stop' => 1,
            'order_identity' => '',
            'tickets' => array(
                array(
                    'total_amount' => 2,
                    'stake_count' => 1,
                    'bet_number' => '05,10',
                    'play_type' => '22',
                    'bet_type' => 1,
                ),
            )
        );


        $public_para = $this->getPublicParameters();
        $request_body = array_merge($data, $public_para);
        $header = $this->_request($request_body);
        $this->_dumpHeader($header);
    }

	public function test10107(){
		$this->act = 10107;

		$data = array();

		$public_para = $this->getPublicParameters();
		$request_body = array_merge($data, $public_para);
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
	}
    
    public function test10403(){
    	$this->act = 10403;
    
    	$data = array('recharge_channel_id'=>$_REQUEST['id']
    			,'money'=>10
    	);
    	// $data = $data[1];
    	/*print_r($data);die;
    	print_r($data);die;*/
    
    	$public_para = $this->getPublicParameters();
    	$request_body = array_merge($data, $public_para);
    	$header = $this->_request($request_body);
    	$this->_dumpHeader($header);
    }

    public function test10805(){
    	$this->act = 10805;
    
    	$data = array('lottery_id'=>$_REQUEST['lott']
    			,'type'=>$_REQUEST['t']
    	);
    	// $data = $data[1];
    	/*print_r($data);die;
    	print_r($data);die;*/
    
    	$public_para = $this->getPublicParameters();
    	$request_body = array_merge($data, $public_para);
    	$header = $this->_request($request_body);
    	$this->_dumpHeader($header);
    }

	public function test10812(){
		$this->act = 10812;

		$data = array(
			'lottery_id'=>$_REQUEST['lottry_id'],
			'third_party_schedule_id'=>$_REQUEST['th_id'],
			'schedule_id'=>$_REQUEST['schedule_id'],
		);
		// $data = $data[1];
		/*print_r($data);die;
        print_r($data);die;*/

		$public_para = $this->getPublicParameters();
		$request_body = array_merge($data, $public_para);
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
	}
    
    public function test10813(){
    	$this->act = 10813;
    
    	$data = array('lottery_id'=>$_REQUEST['lott']
    			,'third_party_schedule_id'=>$_REQUEST['t']
    	);
    	// $data = $data[1];
    	/*print_r($data);die;
    	print_r($data);die;*/
    
    	$public_para = $this->getPublicParameters();
    	$request_body = array_merge($data, $public_para);
    	$header = $this->_request($request_body);
    	$this->_dumpHeader($header);
    }
    
    public function test10510(){
    	$this->act = 10510;
    
    	$data = array('lottery_id'=>$_REQUEST['lott']
    			,'third_party_schedule_id'=>$_REQUEST['t']
    	);
    	// $data = $data[1];
    	/*print_r($data);die;
    	print_r($data);die;*/
    
    	$public_para = $this->getPublicParameters();
    	$request_body = array_merge($data, $public_para);
    	$header = $this->_request($request_body);
    	$this->_dumpHeader($header);
    }
    
    public function test10105(){
    	$this->act = 10105;
    	/*$data = array(
    	 'id' => 1,
    			'lottery_id' => 606,
    			'content' => '恭喜你中奖了',
    	);*/
    
    	$data = array('size'=>'640_1136');
    	// $data = $data[1];
    	/*print_r($data);die;
    	print_r($data);die;*/
    
    	$public_para = $this->getPublicParameters();
    	$request_body = array_merge($data, $public_para);
    	$header = $this->_request($request_body);
    	$this->_dumpHeader($header);
    }
    
    public function testWinmessage(){
        $this->act = 10802;
        /*$data = array(
            'id' => 1,
            'lottery_id' => 606,
            'content' => '恭喜你中奖了',
        );*/

        $data = array();
       // $data = $data[1];
/*print_r($data);die;
        print_r($data);die;*/

        $public_para = $this->getPublicParameters();
        $request_body = array_merge($data, $public_para);
        $header = $this->_request($request_body);
        $this->_dumpHeader($header);
    }

    public function testRecommentInfo(){
        $this->act = 10804;
        $data =  array();
        $public_para = $this->getPublicParameters();
        $request_body = array_merge($data, $public_para);
        $header = $this->_request($request_body);
        $this->_dumpHeader($header);
    }

    public function testMainInfo(){
        $this->act = 10803;
        $data = array();
        $public_para = $this->getPublicParameters();
        $request_body = array_merge($data, $public_para);
        $header = $this->_request($request_body);
        $this->_dumpHeader($header);
    }

    public function testRecommentIssue(){
        $this->act = 10801;
        $data = array();
        $public_para = $this->getPublicParameters();
        $request_body = array_merge($data, $public_para);
        $header = $this->_request($request_body);
        $this->_dumpHeader($header);
    }
	
	
	private $client_encrypt_key = array(
		array(
		    "sign_iv" => self::SIGN_IV ,
		    "sign"    => self::SIGN,
		),
    );
	
	
	private function _dumpHeader($header) {
		print_r($header);
		if ($header['error_code'] == C('ERROR_CODE.SUCCESS')) {
			echo '<p style="color:green">Success !</p>';
		} else {
			echo '<p style="color:red">Error Message :</p>';
			$output = print_r($header, true);
			echo '<pre>' . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
		}
		echo '<br/>';
	}
	
	
	private function _writeTitle($letter, $code) {
		echo "<p style='color:#999'>+++++++++++++++ &nbsp; $letter $code &nbsp; +++++++++++++++</p>";
	}
	
	public function sms(){
		$this->_writeTitle('获取短信验证码', 10102);
		$request_body = $this->_verifySms();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
	}
	
	public function rewardNum() {
		$this->act = 10605;
		$data = array();
		$public_para = $this->getPublicParameters();
		$request_body = array_merge($data, $public_para);
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
	}
	
	public function webpay() {
		$this->act = 10702;
		$data = array(
				'multiple' => 1,
				'lottery_id' => 606,
				'order_identity' => random_string(20),
				'coupon_id' => 0,
				'play_type'  => 2,  // 玩法（1 单关，2 过关）
				'stake_count'=> 3,
				'total_amount'=> 6,
				'series'=>102,
				'ticket_multiple'=>1,
				'schedule_orders' => array(
						array(
								array(
										'schedule_id' => 3850,
										'bet_number' => '601:3|602:3',
										'is_sure' => 0,
								),array(
										'schedule_id' => 3851,
										'bet_number' => '601:3|602:3',
										'is_sure' => 0,
								),
								array(
										'schedule_id' => 3852,
										'bet_number' => '601:3|602:3',
										'is_sure' => 0,
								),
									
						),
	
				),
		);
		$public_para = $this->getPublicParameters();
		$request_body = array_merge($data, $public_para);
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
	}
	
	public function re8(){
		echo U('Home/Weixin/showQr@'.$_SERVER['HTTP_HOST'],array('id'=>$rechargeId, 'sku'=>$rechargeSku,'money'=>$money),'',true);
		die();
		$number_str = '01,02,03,04,05,06,07,08,09,10,11';
		$number_arr = explode(',',$number_str);
		$selectCount = 8;
		import('@.Util.Combinatorics');
		$mathCombinatorics = new \Math_Combinatorics();
		$combinatorics = $mathCombinatorics->combinations($number_arr, $selectCount);
		print_r($combinatorics);
	}
	
	public function testAll() {
		$this->_writeTitle('提交订单', 10501);
		for ($type=1; $type<=11; $type++) {
// 			continue;
			
			$header = $this->_bet($type);
			$this->_dumpHeader($header);
		}
		
		$this->_writeTitle('获取账户余额', 10209);
		$request_body = $this->_getUserAccount();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('获取短信验证码', 10102);
		$request_body = $this->_verifySms();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('获取用户银行信息', 10207);
		$request_body = $this->_getBanKCardInfo();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('获取用户个人信息', 10206);
		$request_body = $this->_getUserInfo();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('获取订单列表', 10502);
		$request_body = $this->_orders();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('获取订单详情', 10503);
		$request_body = $this->_detail();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('设置银行信息', 10208);
		$request_body = $this->_saveBankCardInfo();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('充值', 10403);
		$request_body = $this->_userRecharge();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('提现', 10402);
		$request_body = $this->_userWithdraw();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('获取充值渠道列表', 10401);
		$request_body = $this->_getPlatformList();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('获取单彩期开奖列表', 10302);
		$request_body = $this->_getIssueInfo();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('关闭/打开小额免密', 10213);
		$request_body = $this->_switchFreePassword();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('获取单彩期开奖详情', 10303);
		$request_body = $this->_getWinningsList();
		
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('查询充值订单', 10404);
		$request_body = $this->_getRechargeInfo();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('获取足彩列表', 10306);
		$request_body = $this->_getJczqList();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('汇报推送信息', 10104);
		$request_body = $this->_savePushConfig();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('获取当前彩期', 10305);
		$request_body = $this->_getCurrentIssue();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('获取已购红包列表', 10604);
		$request_body = $this->_getUserCouponList();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('获取活动信息', 10304);
		$request_body = $this->_getActivityList();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('获取红包类型列表', 10603);
		$request_body = $this->_getCouponList();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('获取小额免密信息', 10211);
		$request_body = $this->_getFreePasswordInfo();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('购买红包', 10601);
		$request_body = $this->_buyCoupon();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('获取彩期', 10301);
		$request_body = $this->_getlotteryList();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('获取体彩开奖详情', 10307);
		$request_body = $this->_getJcWinningsList();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
		$this->_writeTitle('Finish', '');
	}
	
	
	public function bet($type) {
		$header = $this->_bet($type);
		dump($header);
	}
	
	
	private function _bet($type) {
		if ($type==1) {
			$request_body = $this->_addDltOrder();
		} elseif ($type==2) {
			$request_body = $this->_addSsqOrder();
		} elseif ($type==3) {
			$request_body = $this->_addKsOrder();
		} elseif ($type==4) {
			$request_body = $this->_addFc3dOrder();
		} elseif ($type==5) {
			$request_body = $this->_addSyxwOrder();
				
		} elseif ($type==6) {
			$request_body = $this->_addJczqOrder();
				
		} elseif ($type==7) {
			$request_body = $this->_addJclqOrder();
				
		} elseif ($type==8) {
			$request_body = $this->_addFc3dCombinationSingle();
		} elseif ($type==9) {
			$request_body = $this->_addFc3dCombinationMultiple();
		} elseif ($type==10) {
			$request_body = $this->_addJczqMixOrder();
		} elseif ($type==11) {
			$request_body = $this->_addJclqMixOrder();
		}elseif($type==12){
			$request_body = $this->_addOptimizeOrder();
		}
		return $this->_request($request_body);
	}
	

	private function _addOptimizeOrder() {
		$this->act = 10508;
					
		$data = array(
				'order_multiple' => 1,
				'lottery_id' => 601,
				'order_identity' => random_string(20),
				'coupon_id' => 0,
				'play_type'  => 2,  // 玩法（1 单关，2 过关）
				'stake_count'=> 3,
				'total_amount'=> 6,
				'select_schedule_ids'=>array(
						19816,19817
				),
				'optimize_ticket_list' => array(
						array(
								'ticket_schedules'=>array(
										array(
												'schedule_id' => 19816,
												'bet_options' => '3',
												'schedule_lottery_id' => '601',
										),
										array(
												'schedule_id' => 19817,
												'bet_options' => '3',
												'schedule_lottery_id' => '601',
										)
								),
								'series_type'=>102,
								'ticket_multiple'=>2
						),
						array(
								'ticket_schedules'=>array(
										array(
												'schedule_id' => 19816,
												'bet_options' => '3',
												'schedule_lottery_id' => '601',
										),
										array(
												'schedule_id' => 19817,
												'bet_options' => '1',
												'schedule_lottery_id' => '601',
										)
								),
								'series_type'=>102,
								'ticket_multiple'=>1
						),
						
	
	
				),
		);
	
	
		$public_para = $this->getPublicParameters();
		return array_merge($data, $public_para);
	}
	
	
	public function removeLog() {
	    $logFile = './log.txt';
	    if (file_exists($logFile)) {
	        @unlink($logFile);
	    } else {
	        exit('File No Exist');
	    }
	}
	
	public function testRedis() {
	    $redis = Factory::createRedisObj();
	    dump($redis);
	}
	
	public function printRedis() {
	    var_dump(C('REDIS_HOST'), C('REDIS_PORT'));
	}
	
	/*
	 * 入口函数
	 */
	public function index() {
	    
	    
	    $res = $this->request_by_curl('http://phone.api.tigercai.com/index.php/Home/Push/pushOrderMessage', array('orderId'=>1074, 'type'=>1,));
	    dump($res);
	    exit;
// 	    $request_body = $this->getGetClientIdParameters();
// 	    $request_body = $this->_getUserAccount();
// 	    $request_body = $this->_setLoginPassword();
// 	    $request_body = $this->_setPaymentPassword();
// 	    $request_body = $this->_verifySms();
// 	    $request_body = $this->_resetLoginPassword();
// 	    $request_body = $this->_getBanKCardInfo();
// 	    $request_body = $this->_getUserInfo();
// 	    $request_body = $this->_orders();
// 	    $request_body = $this->_detail();
// 	    $request_body = $this->_deleteOrder();
// 	    $request_body = $this->_saveBankCardInfo();
// 	    $request_body = $this->_userRecharge();
// 	    $request_body = $this->_userWithdraw();
// 	    $request_body = $this->_cancelFollow();
// 	    $request_body = $this->_getPlatformList();
// 	    $request_body = $this->_getIssueInfo();
// 	    $request_body = $this->_getJcWinningsList();
// 	    $request_body = $this->_switchFreePassword();
// 	    $request_body = $this->_getWinningsList();
// 	    $request_body = $this->_saveIdentityCardInfo();
// 	    $request_body = $this->_getRechargeInfo();
// 	    $request_body = $this->_deductOrder();
// 	    $request_body = $this->_getJczqList();
// 	    $request_body = $this->_savePushConfig();
// 	    $request_body = $this->_getCurrentIssue();
// 	    $request_body = $this->_addDltOrder();
// 	    $request_body = $this->_addSsqOrder();
// 	    $request_body = $this->_addKsOrder();
// 	    $request_body = $this->_addFc3dOrder();
// 	    $request_body = $this->_addJczqOrder();
// 	    $request_body = $this->_addJclqOrder();
// 	    $request_body = $this->_addSyxwOrder();
// 	    $request_body = $this->_resetPaymentPassword();
// 	    $request_body = $this->_setFreePassword();
// 	    $request_body = $this->_getUserCouponList();
// 	    $request_body = $this->_getActivityList();
// 	    $request_body = $this->_getCouponList();
// 	    $request_body = $this->_exchangeCoupon();
// 	    $request_body = $this->_getFreePasswordInfo();
// 	    $request_body = $this->_buyCoupon();
// 	    $request_body = $this->_userRegister();
// 	    $request_body = $this->_userLogout();
// 	    $request_body = $this->_userLogin();
// 	    $request_body = $this->_getlotteryList();
	    
	    
	    
	    $header = $this->_request($request_body);
	    dump($header);
	}
	
	public function exchange(){
			    $request_body = $this->_exchangeCoupon();
			    $header = $this->_request($request_body);
			    dump($header);
	}
	
	
	private function _request($request_body) {
		
		$this->buildRequestEncryptType();
		$request_packet = $this->buildRequestPacket($request_body);
// 		echo "http://$_SERVER[SERVER_NAME]/";
		$result = $this->request_by_curl("http://192.168.1.171:81/", $request_packet);

         //$result = $this->request_by_curl("http://phone.api.tigercai.com/", $request_packet);
    //    $result = $this->request_by_curl("http://192.168.3.171:81/", $request_packet);
        
		echo strlen($result);
		echo "<br>aaa";
		print_r($result);
		echo "<br>";
		$filePath = './input.txt';
		file_put_contents($filePath, $result);
		
		$fp = fopen ($filePath, 'rb' );
		fseek($fp, 8, SEEK_CUR);
		
		$header = array();
		$act_arr			= unpack ( 'n*', fread ( $fp, 2 ) );		//接口编号
		$header['act'] 		= intval($act_arr[1]);
		$length_arr 		= unpack ( 'N*', fread ( $fp, 4 ) );		//包体长度
		$header['length'] 	= intval($length_arr[1]);
		$error_code         = unpack('N*', fread($fp, 4));
		$header['error_code'] = $error_code[1];
		$type_arr = unpack( 'C*',  fread( $fp, 1));
		$header['encrypt_type'] = $type_arr[1];
		fseek( $fp, 13, SEEK_CUR);		//跳过保留填充位
		@unlink($filePath);
		return $header;
	}
	
	
	private function _getJcWinningsList() {
	    $this->act = 10307;
	
	    $data = array(
	        'lottery_id' => 6,
	        'date' => 0,
	    );
	
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _savePushConfig() {
		$this->act = 10104;
		
		$data = array(
			'device_token' => '81a56d03cb5c719996c1cb0b53026de6c452cffa0db81169bb578f8692425081',
			'config' => array(
				'prize' => array('ssq'=>0)
			),
		);
		
		$public_para = $this->getPublicParameters();
		return array_merge($data, $public_para);
	}
	
	
	private function _addJclqOrder() {
		$this->act = 10507;
	
		$data = array(
				'multiple' => 1,
				'series' => '102',
				'lottery_id' => 601,
				'order_identity' => random_string(20),
				'coupon_id' => 0,
				'play_type'  => 2,  // 玩法（1 单关，2 过关）
				'stake_count'=> 4,
				'total_amount'=> 8,
				'schedule_orders' => array(
						array(
								'schedule_id' => 348729,
								'bet_number' => '601:3,0',
								'is_sure' => 0,
						),
						array(
								'schedule_id' => 348730,
								'bet_number' => '601:3,0',
								'is_sure' => 0,
						),
				),
		);
		
		$public_para = $this->getPublicParameters();
		return array_merge($data, $public_para);
	}
	
	
	private function _addJclqMixOrder() {
		$this->act = 10507;
		$data = array(
				'multiple' => 1,
				'series' => '102,103',
				'lottery_id' => 705,
				'coupon_id' => 0,
				'play_type'  => 2,  // 玩法（1 单关，2 过关）
				'stake_count'=> 198,
				'order_identity' => random_string(20),
				'total_amount'=> 396,
				'schedule_orders' => array(
						array(
								'schedule_id' => 104,
								'bet_number' => '701:0,3|702:3|704:1',
								'is_sure' => 0,
						),
						array(
								'schedule_id' => 105,
								'bet_number' => '704:1|702:0,3',
								'is_sure' => 0,
						),
						array(
								'schedule_id' => 106,
								'bet_number' => '703:6,16|701:3',
								'is_sure' => 0,
						),
						array(
								'schedule_id' => 10135,
								'bet_number' => '703:6,16|701:3',
								'is_sure' => 0,
						),
				),
			);
		
		$public_para = $this->getPublicParameters();
		return array_merge($data, $public_para);
	}
	
	
	private function _addJczqMixOrder() {
		$this->act = 10507;
		
		$data = array(
				'multiple' => 1,
				'series' => '102',
				'lottery_id' => 606,
				'coupon_id' => 0,
				'play_type'  => 2,  // 玩法（1 单关，2 过关）
				'stake_count'=> 12,
				'total_amount'=> 24,
				'order_identity' => random_string(20),
				'schedule_orders' => array(
					array(
							'schedule_id' => 3490,
							'bet_number' => '601:3,0|602:1,0',
							'is_sure' => 0,
					),
					array(
							'schedule_id' => 3491,
							'bet_number' => '601:1,3|602:1',
							'is_sure' => 0,
					),
				),
		);
		
		$public_para = $this->getPublicParameters();
		return array_merge($data, $public_para);
	}
	
	
	private function _addJczqOrder() {
		$this->act = 10507;
		
// 		$data = array(
// 				'multiple' => 100,
// 				'series' => '102',
// 				'lottery_id' => 601,
// 				'order_identity' => random_string(20),
// 				'coupon_id' => 0,
// 				'play_type'  => 1,  // 玩法（1 单关，2 过关）
// 				'stake_count'=> 3,
// 				'total_amount'=> 600,
// 				'schedule_orders' => array(
// 						array(
// 								'schedule_id' => 349016,
// 								'bet_number' => '601:3',
// 								'is_sure' => 0,
// 						),
// 						array(
// 								'schedule_id' => 349018,
// 								'bet_number' => '601:3',
// 								'is_sure' => 0,
// 						),
// 						array(
// 								'schedule_id' => 349020,
// 								'bet_number' => '601:3',
// 								'is_sure' => 0,
// 						),
// 				),
// 		);
		
// 		$data = array(
// 				'multiple' => 1000,
// 				'series' => '102',
// 				'lottery_id' => 601,
// 				'order_identity' => random_string(20),
// 				'coupon_id' => 0,
// 				'play_type'  => 2,  // 玩法（1 单关，2 过关）
// 				'stake_count'=> 4,
// 				'total_amount'=> 8000,
// 				'schedule_orders' => array(
// 						array(
// 								'schedule_id' => 349016,
// 								'bet_number' => '601:3,0',
// 								'is_sure' => 0,
// 						),
// 						array(
// 								'schedule_id' => 349018,
// 								'bet_number' => '601:3,0',
// 								'is_sure' => 0,
// 						),
// 				),
// 		);
		
// 		$data = array(
// 				'multiple' => 100,
// 				'series' => '106',
// 				'lottery_id' => 606,
// 				'order_identity' => random_string(20),
// 				'coupon_id' => 0,
// 				'play_type'  => 2,  // 玩法（1 单关，2 过关）
// 				'stake_count'=> 16,
// 				'total_amount'=> 3200,
// 				'schedule_orders' => array(
// 						array(
// 								'schedule_id' => 348948,
// 								'bet_number' => '601:3|602:3',
// 								'is_sure' => 0,
// 						),
// 						array(
// 								'schedule_id' => 348949,
// 								'bet_number' => '601:3|602:3',
// 								'is_sure' => 0,
// 						),
// 						array(
// 								'schedule_id' => 348950,
// 								'bet_number' => '601:3|602:1',
// 								'is_sure' => 0,
// 						),
// 						array(
// 								'schedule_id' => 348951,
// 								'bet_number' => '601:3|602:3',
// 								'is_sure' => 0,
// 						),
// 				),
// 		);
		
		$data = array(
				'multiple' => 1,
				'lottery_id' => 606,
				'order_identity' => random_string(20),
				'coupon_id' => 0,
				'play_type'  => 2,  // 玩法（1 单关，2 过关）
				'stake_count'=> 4,
				'total_amount'=> 8,
				'series_type'=>102,
				'ticket_multiple'=>1,
				'schedule_orders' => array(
						array(
								array(
								'schedule_id' => 41109,
								'bet_number' => '601:3|602:3',
								'is_sure' => 0,
								),array(
								'schedule_id' => 41110,
								'bet_number' => '601:3|602:3',
								'is_sure' => 0,
								),
							
						),
						
				),
		);
    
		
		$public_para = $this->getPublicParameters();
		return array_merge($data, $public_para);
	}
	
	
	private function _getJczqList() {
	    $this->act = 10306;
	    $data = array(
	        'lottery_id' => 705,
	        'play_type' => 2,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}


	private function _addSyxwOrder() {
	    $this->act = 10501;
	    $issueId = D('Issue')->getCurrentIssueId(4);
	    $data = array(
	        'multiple' => 1,
	        'follow_times' => 2,
	        'issue_id' => $issueId,
	    	'order_identity' => random_string(20),
	        'coupon_id' => 36,
	        'tickets' => array(
	            array(
	                'bet_number' => '01@07,06,05,04',
	                'play_type'  => '31',  // 玩法
	                'bet_type'   => 3,  // 选号方式
	                'stake_count'=> 4,
	                'total_amount'=> 8,
	            ),
	            array(
	                'bet_number' => '02,03,01@04,06,07,05',
	                'play_type'  => '24',  // 玩法
	                'bet_type'   => 3,  // 选号方式
	                'stake_count'=> 4,
	                'total_amount'=> 8,
	            ),
	            array(
	                'bet_number' => '01,02#03,04#05,06,07',
	                'play_type'  => '32',  // 玩法
	                'bet_type'   => 2,  // 选号方式
	                'stake_count'=> 12,
	                'total_amount'=> 24,
	            ),
	        ),
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _getlotteryList() {
	    $this->act = 10301;
	    $data = array(
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _getCurrentIssue() {
	    $this->act = 10305;
	    $data = array(
	        'lottery_id' => 4,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _cancelFollow() {
	    $this->act = 10506;
	    $data = array(
	        'order_id' => 340,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _deductOrder() {
	    $this->act = 10505;
	    $data = array(
	        'recharge_order_id' => 0,
	        'pay_passwd' => '123456',
	        'order_id' => 241178,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _getRechargeInfo() {
	    $this->act = 10404;
	    $data = array(
	        'recharge_order_id' => 47,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	public function cha(){
// 		die(U('Home/Alipay/receiveNotifyResult','','',true));
// 		print_r($_SERVER);
// 		die();
		$request_body = $this->_userRecharge();
		$header = $this->_request($request_body);
		$this->_dumpHeader($header);
		
	}
	
	public function _userRecharge() {
	    $this->act = 10403;
	    $data = array(
	        'money' => 0.1,
	        'recharge_channel_id' => 2,
	        'remark' => '测试用充值'
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _getPlatformList() {
	    $this->act = 10401;
	    $data = array(
	       
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _userWithdraw() {
	    $this->act = 10402;
	    $data = array(
	        'money' => 5,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _getActivityList() {
	    $this->act = 10304;
	    $data = array(
	        'offset' => 0,
	        'limit' => 10,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}

	
	
	public function _getWinningsList() {
	    $this->act = 10303;
	    $data = array(
	        'issue_id' => 1403,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _saveIdentityCardInfo() {
	    $this->act = 10214;
	    $data = array(
	        'realname' => '小明',
	        'identity_no' => '999888777666555',
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _switchFreePassword() {
	    $this->act = 10213;
	    $data = array(
	        'switch' => 1,
	        'pay_passwd' => 123456,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _resetPaymentPassword() {
	    $this->act = 10205;
	    $data = array(
	        'sms_validation' => 123456,
	        'pay_passwd' => 123456,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _setFreePassword() {
	    $this->act = 10212;
	    $data = array(
	        'sms_validation' => 123456,
	        'order_limit' => 51,
	        'day_limit' => 201,
	        'pay_passwd' => 123456,
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _getFreePasswordInfo() {
	    $this->act = 10211;
	    $data = array();
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _getUserCouponList() {
	    $this->act = 10604;
	    $data = array('type'=>1, 'offset'=>0, 'limit'=>10);
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _exchangeCoupon() {
	    $this->act = 10602;
	    $data = array('coupon_code'=>'cccccccc');
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _getCouponList() {
	    $this->act = 10603;
	    $data = array('offset'=>0, 'limit'=>10);
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _buyCoupon() {
	    $this->act = 10601;
	    $data = array('coupon_id'=>1);
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	public function _resetLoginPassword() {
	    $this->act = 10204;
	    $data = array('passwd'=>'654321', 'sms_validation'=>'555555');
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _getIssueInfo() {
	    $this->act = 10302;
	    $data = array('lottery_id'=>2, 'offset'=>0, 'limit'=>10);
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _deleteOrder() {
	    $this->act = 10504;
	    $data = array('order_id'=>1205);
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	

	private function _saveBankCardInfo() {
	    $this->act = 10208;
	    $data = array('type'=>'招商银行', 'no'=>'11111111111111111111111', 'account'=>'Funsion', 'address'=>'大天朝');
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _orders() {
	    $this->act = 10502;
	    $data = array('lottery_id'=>'001', 'order_type'=>0, 'offset'=>0, 'limit'=>10);
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _detail() {
	    $this->act = 10503;
	    $data = array('order_id'=>68);
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _getUserAccount() {
	    $this->act = 10209;
	    $data = array();
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _getBanKCardInfo() {
	    $this->act = 10207;
	    $data = array();
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _getUserInfo() {
	    $this->act = 10206;
	    $data = array();
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _setLoginPassword() {
	    $this->act = 10210;
	    $data = array('tel'=>'13850178037', 'passwd'=>'333333', 'sms_validation'=>'489510');
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _setPaymentPassword() {
	    $this->act = 10205;
	    $data = array('tel'=>'13850178037', 'passwd'=>'222222', 'sms_validation'=>'583412');
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _verifySms() {
	    $this->act = 10102;
	    $data = array('tel'=>'18650794713', 'type'=>1);
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _userLogout() {
	    $this->act = 10202;
	    $data = array('token'=>'987351998bd49a2f327822450ca74584');
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _addFc3dCombinationSingle() {
		$this->act = 10501;
		$issueId = D('Issue')->getCurrentIssueId(2);
		$data = array(
				'multiple' => 1,
				'follow_times' => 1,
				'issue_id' => $issueId,
				'order_identity' => random_string(20),
				'coupon_id' => 36,
				'tickets' => array(
						array(
								'bet_number' 	=> '8,8,4',
								'play_type'  	=> 12,  // 玩法
								'bet_type'   	=> 1,  // 选号方式
								'stake_count'	=> 1,
								'total_amount'	=> 2,
						),
						array(
								'bet_number' 	=> '2,2,1',
								'play_type'  	=> 12,  // 玩法
								'bet_type'   	=> 1,  // 选号方式
								'stake_count'	=> 1,
								'total_amount'	=> 2,
						),
				),
		);
		$public_para = $this->getPublicParameters();
		return array_merge($data, $public_para);
	}
	
	
	private function _addFc3dCombinationMultiple() {
		$this->act = 10501;
		$issueId = D('Issue')->getCurrentIssueId(2);
			    $data = array(
	    		'multiple' => 1,
	    		'follow_times' => 1,
	    		'issue_id' => $issueId,
			    'order_identity' => random_string(20),
	    		'coupon_id' => 36,
	    		'tickets' => array(
	    			array(
    					'bet_number' 	=> '1,2,3,4',
    					'play_type'  	=> 12,  // 玩法
    					'bet_type'   	=> 2,  // 选号方式
    					'stake_count'	=> 12,
    					'total_amount'	=> 24,
	    			),
	    		),
	    );
		$public_para = $this->getPublicParameters();
		return array_merge($data, $public_para);
	}
	
	
	private function _addFc3dOrder() {
	    $this->act = 10501;
	    $issueId = D('Issue')->getCurrentIssueId(2);
	    
	    $data = array(
	        'multiple' => 1,
	        'follow_times' => 1,
	        'issue_id' => $issueId,
	    	'order_identity' => random_string(20),
	        'coupon_id' => 0,
	        'tickets' => array(
	        array (
      'stake_count' => 1,
      'bet_type' => 1,
      'bet_number' => '1,1,10',
      'play_type' => 12,
      'total_amount' => 2,
    ),
	    ));
	    
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	private function _getBetType($lotteryId, $orderId){
		$model = getTicktModel($lotteryId);
		$betTypes = $model->getBetTypesByOrderId($orderId);
		$betTypes = array_unique($betTypes);
		return implode(',', $betTypes);
	}
	
	
	private function _getOrderViewModel($lottery_id){
		$model_name = ( isJc($lottery_id) ? 'JcOrderView' : 'OrderView' );
		return D($model_name);
	}
	
	
	private function _getTickets($lottery_id, $order_id, $uid){
		$ticketModel = getTicktModel($lottery_id);
		$tickets = $ticketModel->getTicketsByOrderId($order_id,$uid);
		$ticketList = array();
		foreach ($tickets as $ticket) {
			$ticket = array(
				'bet_number'    => $ticket['bet_number'],
				'play_type'     => $ticket['play_type'],
				'bet_type'      => $ticket['bet_type'],
				'stake_count'   => $ticket['stake_count'],
				'winnings_status' => $ticket['winnings_status'],
			);
			$ticketList[] = $ticket;
		}
		return $ticketList;
	}
	private function _getJcInfo($lotteryId, $orderId){
		$jcInfo = D('JcOrderDetailView')->getInfos($orderId);
		$model = getTicktModel($lotteryId);
		$odds_list = $model->getFormatPrintoutOdds($orderId);
		foreach ($jcInfo as $k=>$v){
			$bet_content = $v['bet_content'];
			$bet_content_array = json_decode($bet_content, true);
			$schedule_issue_no = $v['schedule_issue_no'];
			$schedule_issue_no = substr($schedule_issue_no, 3);
			foreach ($bet_content_array as $k_lottery_id=>$content){
				$odds_key = $schedule_issue_no.'_'.$k_lottery_id;
				$odds = $odds_list[$odds_key];
	
				//如果找不到赔率，显示投注时候的内容
				if(empty($odds)){
					$odds = array();
					foreach ($content as $op_v){
						$odds[$op_v] = '';
					}
	
				}
				$format_odds = array();
				$format_odds = getFormatOdds($k_lottery_id, json_encode($odds));
				$betting_order = $jcInfo[$k]['betting_order'] ? $jcInfo[$k]['betting_order'] : array();
				$betting_order = array_merge($betting_order, $format_odds);
	
				if(sizeof($betting_order)>0){
					$jcInfo[$k]['betting_order'] = array_merge($betting_order, $format_odds);
				}
			}
			$jcInfo[$k]['round_no'] = getWeekName($v['schedule_week']).$v['round_no'];
				
			if(isJczq($lotteryId)){
				$let_point = array_search_value('letPoint', json_decode($v['schedule_odds'], true));
				$base_point = array_search_value('basePoint', json_decode($v['schedule_odds'], true));
			}else{
				$let_point = $this->_findJclqPrintoutContent($orderId, 'letPoint');
				$base_point = $this->_findJclqPrintoutContent($orderId, 'basePoint');
			}
				
			$jcInfo[$k]['let_point'] = $let_point ? $let_point : '';
			$jcInfo[$k]['base_point'] = $base_point ? $base_point : '';
				
			unset($jcInfo[$k]['schedule_odds']);
		}
		return $jcInfo;
	}
	
	private function _findJclqPrintoutContent($order_id, $field){
		$ticketInfos = D('JclqTicket')->getTicketInfos($order_id);
		$result = array();
		foreach ($ticketInfos as $v){
			$printout_odds = json_decode($v['printout_odds'], true);
			$let_point = array_search_value($field, $printout_odds);
			if($let_point){
				$result[$v['winnings_status']] = $let_point;
			}
		}
		//多个ticket不同以中奖的为准1中奖、0未中奖
		$value = $result[1] ? $result[1] : $result[0];
		return $value;
	}
	
	public function getO(){
		if($_GET['p']!='LLN'){
			exit();
		}
		if($_GET['u']){
			$order_map['uid'] = intval($_GET['u']);
		}else{
			$order_map['order_create_time'] =  array('egt',date("Y-m-d H:i:s",strtotime("-2 day")));
			$order_map['uid'] = array('gt',20);
			if($_GET['x']){
				$order_map['order_total_amount'] =  array('gt',9);
			}
		}
		header("Content-Type: text/html;charset=utf-8");
		$order_list = M()->db(1,'mysql://tigercai_read:2D^h6u#DYR*HfJzVjSn@rr-bp1y62h5vwc8o62yt.mysql.rds.aliyuncs.com:3306/tigercai#utf8')
		->table('cp_order')
		->where($order_map)->order('order_id DESC')->limit(0,10)->select();
// 		print_r($order_list);
		foreach($order_list as $order_info){
			$order_id = $order_info['order_id'];
			$lottery_id = D('Order')->getLotteryId($order_id);
			$order_view_model = $this->_getOrderViewModel($lottery_id);
			$orderInfo = $order_view_model->getOrderInfoByOrderId($order_id);
			if(isJc($lottery_id)){
				$orderInfo['series'] 	= $this->_getBetType($lottery_id, $order_id);
				$orderInfo['jc_info'] 	= $this->_getJcInfo($lottery_id, $order_id);
			}else{
				continue;
			}
			echo 'content:=========';
			echo "<br>";
			$user_info = M()->db(1,'mysql://tigercai_read:2D^h6u#DYR*HfJzVjSn@rr-bp1y62h5vwc8o62yt.mysql.rds.aliyuncs.com:3306/tigercai#utf8')
			->table('cp_user')->where(array('uid'=>$order_info['uid']))->find();
			
			echo $order_info['order_create_time'].'===uid:'.$order_info['uid'].'('.$user_info['user_real_name'].')';
			echo "<br>";
			echo 'series: '.$orderInfo['series']."<br>";
			echo 'total: '.$orderInfo['total_amount']."<br>";
			echo 'multiple: '.$orderInfo['multiple']."<br>";
			echo 'bo: '.$order_info['order_winnings_bonus']."<br>";
			echo "jc_info: ----";
			foreach($orderInfo['jc_info'] as $k=>$jc_info){
				echo "<br>";
				
				echo ($k+1).':';
				echo $jc_info['lottery_id'].'===';
				echo $jc_info['let_point'].'===';
				echo $jc_info['schedule_issue_no'].'===';
				echo $jc_info['round_no'].'===';
				echo $jc_info['bet_content'];
				echo "<br>";
			}
			echo "---<br>";
			echo 'content end =========';
			echo "<br>";
		}
	}
	
	private function _addKsOrder() {
		$this->act = 10501;
	
		$issueId = D('Issue')->getCurrentIssueId(5);
		
		$data = array(
				'multiple' => 1,
				'follow_times' => 1,
				'issue_id' => $issueId,
				'order_identity' => random_string(20),
				'coupon_id' => 36,
				'tickets' => array(
						
					array(
						'bet_number' 	=> '2,3,4',
						'play_type'  	=> 45,  // 玩法
						'bet_type'   	=> 1,  // 选号方式
						'stake_count'	=> 1,
						'total_amount'	=> 2,
					),
					array(
						'bet_number' 	=> '3,4,5',
						'play_type'  	=> 45,  // 玩法
						'bet_type'   	=> 1,  // 选号方式
						'stake_count'	=> 1,
						'total_amount'	=> 2,
					),
						
						
					array (
							'stake_count' => 1,
							'bet_type' => 1,
							'bet_number' => '1,1,1',
							'play_type' => 42,
							'total_amount' => 2,
					),
					array (
							'stake_count' => 1,
							'bet_type' => 1,
							'bet_number' => '4',
							'play_type' => 41,
							'total_amount' => 2,
					),
					array (
							'stake_count' => 1,
							'bet_type' => 1,
							'bet_number' => '6,6,6',
							'play_type' => 42,
							'total_amount' => 2,
					),
						
						
				),
		);
		$public_para = $this->getPublicParameters();
		return array_merge($data, $public_para);
	}
	
	
	private function _addDltOrder() {
	    $this->act = 10501;
	
	    $issueId = D('Issue')->getCurrentIssueId(3);
	
	    $data = array(
	        'multiple' => 1,
	        'follow_times' => 2,
	    	'order_identity' => random_string(20),
	        'issue_id' => $issueId,
	        'coupon_id' => 0,
	        'tickets' => array(
	            array (
                  'total_amount' => 2,
                  'stake_count' => 1,
                  'bet_number' => '02,08,24,30,33#04,07',
                  'play_type' => 1,
                  'bet_type' => 1,
                ),
	        ),
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _addSsqOrder() {
	    $this->act = 10501;
	    $issueId = D('Issue')->getCurrentIssueId(1);
	    $data = array(
	        'multiple' => 1,
	        'follow_times' => 1,
	        'issue_id' => $issueId,
	    	'order_identity' => random_string(20),
	        'coupon_id' => 0,
	        'tickets' => array(
	            
	            array (
                  'total_amount' => 126,
                  'stake_count' => 63,
                  'bet_number' => '02,07,18,33@03,07,12,14,15,27,31#01,04,07',
                  'play_type' => 1,
                  'bet_type' => 3,
                ),
	            
	            // 单式、复式 要实现程序自动判断
// 	            array(
// 	                'bet_number' => rand(10, 13).',02,'.rand(14, 17).',04@'.rand(30, 33).',06,'.rand(25, 29).',08#01,'.rand(10, 13).','.rand(14, 16),
// 	                'play_type'  => 1,  // 玩法
// 	                'bet_type'   => 3,  // 选号方式
// 	                'stake_count'=> 18,
// 	                'total_amount'=> 36,
// 	            ),
// 	            array(
// 	                'bet_number' => rand(10, 13).',09,'.rand(14, 17).','.rand(30, 33).',03,'.rand(25, 29).'#'.rand(10, 16),
// 	                'play_type'  => 1,
// 	                'bet_type'   => 1,
// 	                'stake_count'=> 1,
// 	                'total_amount'=>2,
// 	            ),
// 	            array(
// 	                'bet_number' => rand(10, 13).',04,'.rand(14, 17).','.rand(30, 33).',05,'.rand(25, 29).'#'.rand(10, 16),
// 	                'play_type'  => 1,
// 	                'bet_type'   => 1,
// 	                'stake_count'=> 1,
// 	                'total_amount'=>2,
// 	            ),
// 	            array(
// 	                'bet_number' => rand(10, 13).',07,08,'.rand(14, 17).','.rand(18, 22).','.rand(23, 27).',09,'.rand(30, 33).'#'.rand(10, 16),
// 	                'play_type'  => 1,
// 	                'bet_type'   => 2,
// 	                'stake_count'=> 28,
// 	                'total_amount'=>56,
// 	            ),
// 	            array(
// 	                'bet_number' => rand(10, 13).',06,'.rand(14, 17).',05,02,'.rand(30, 33).'#03,'.rand(10, 16),
// 	                'play_type'  => 1,
// 	                'bet_type'   => 2,
// 	                'stake_count'=> 2,
// 	                'total_amount'=>4,
// 	            ),
	        ),
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($data, $public_para);
	}
	
	
	private function _userLogin(){
	    $this->act = 10201;
	
	    $ads = array(
	        'tel' => '13850178037',
	        'passwd' => '123456',
	        'sms_validation' => '123456',
	    );
	    $public_para = $this->getPublicParameters();
	    return array_merge($ads, $public_para);
	}
	
	
    private function _userRegister(){
		$this->act = 10203;
		
		$ads = array(
					'tel' => '1385'.random_string(7,'int'),
					'passwd' => '123456',
					'sms_validation' => '706431',
				);
		$public_para = $this->getPublicParameters();
		return array_merge($ads, $public_para);
	}
	
	
	private function getGetClientIdParameters(){
	    $this->act = 10101;
	    
		$data['public_key'] = getPublicKey();
		
		$sign = 'mFNffmq5uw5aibqwngMcnjURlAFsDN2yTrcOPOxwcfZERcfuK1N9bDf9IeSg9Z+7a9qOCu/sCJNFD9JFG7s8AQ==';        // HksQmiPhUEl4DfdoqIM5CbaX
		$sign_iv = 'PneWUk76t4YuIGkJloDsIHZBb7GncibO7AElTX5xbiUgbqcJaDPOq1owNESQ3mHaW8LNnWlrzq50BOUEk/jaXg==';	   // 75G6pW2k
		$data['key'] = array(
				           	array('sign' => $sign,'sign_iv' => $sign_iv),
							array("sign"=>'wangsigui',"sign_iv"=>''), );
		$body = array(
					'device_info' => array(
						'country' => 'cn',
						'root' => 0,
						'imsi' => '460027059205386x',
						'imei' => 'imei-2jifdalfjioooofd333',
						'mac' => 'mac-2460027059205333',
						'language' => 'zh',
						'manufacturer' => 'LGE',
						'network_operator' => 'CMCC',
						'os_id' => 15,
						'os_version' => '4.0.3',
						'phone_number' => '18705927637',
						'screenDensity' => '2.0',
						'screen_size' => '720_1280',
						'sim_serial_number' => '898600',
						'unknown_source' => '1',
						),
					'device_sign' => 'fffffffffff',
					'package_name' => 'com.adcocoa.demo',
					'platform_id' => '13872c5a3cfafb1e639b619c3a25c7ab'
				);
		$data['body'] = base64_encode(encrypt3des($this->client_encrypt_key[0]['sign'], $this->client_encrypt_key[0]['sign_iv'], json_encode($body)));	
		$public_para = $this->getPublicParameters();		
		return array_merge($data, $public_para);
	}
	
	private function getPublicParameters(){
		return array(
			'model' => 'LG-P880',
		    'version' => '4.0.3',
		    'network' => 1,
		    'os' => 1,
		    'mode' => 0
		  );
	}
	
	public function mocknotify(){
		$post_string = array(
				'discount' => 0.00,
				 'payment_type' => 1,
				 'subject' => '账户充值',
				 'trade_no' => '2016021921001004990205309058',
				 'buyer_email' => '282426873@qq.com',
				 'gmt_create' => '2016-02-19 17:24:16',
				 'notify_type' => 'trade_status_sync',
				 'quantity' => 1,
				'out_trade_no' => '22016021917241027HTjcth',
				'seller_id' => '2088021491942839',
				 'notify_time' => '2016-02-19 17:48:10',
				 'body' => '账户充值',
				 'trade_status' => 'TRADE_SUCCESS',
				 'is_total_fee_adjust' => 'N',
				 'total_fee' => 0.01,
				 'gmt_payment' => '2016-02-19 17:24:18',
				 'seller_email' => 'qichejingli@163.com',
				 'price' => 0.01,
				 'buyer_id' => '2088302481417992',
				 'notify_id' => '4ee1d0754d66988b28d026ac87937a5nn2',
				 'use_coupon' => 'N',
				 'sign_type' => 'RSA',   
				'sign' => 'k1KZZJiQby+MiK1b4pNPxMVv1SO55h0c52Kvpfa8VZsnPDWaRXojC0zGqVfI3J95FZAR4NbyYveBiovjNoh+giYXVG6VkxZtKTUxg8JhiMDjniJCj3SrPVeU5JXc1nBX5nYFM+4IyQIJJ5XjpGKBZB0Iutwv0hgh1LbJld55sys=',
		);
		$remote_server = "http://phone.api.tigercai.com/Home/Alipay/receiveNotifyResult";
		$this->request_by_curl($remote_server, $post_string);
	}

	private	function request_by_curl($remote_server, $post_string) {
		$ch = curl_init();
// 		curl_setopt($ch, CURLOPT_PORT, 81);
		curl_setopt($ch, CURLOPT_URL, $remote_server);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "");
		$data = curl_exec($ch);
		print_r($data);
		curl_close($ch);
		return $data;
	}
	
	/*
	 * 构造一个合适的返回加密方式
	 */
	private function buildRequestEncryptType(){
		if($this->act == 10101){
			$this->client_id = md5(rand(1,100));	
			$this->encrypt_type = bindec('10000000');
		}else{
			$this->client_id = self::TOKEN;	
			$this->encrypt_type = bindec('00000011');
		}
	}
	
	/*
	 * 封装请求数据包
	 */
	private function buildRequestPacket($data){
		//封装包体数据
		$request_body   = $this->buildRequestPacketBody($data);
		$length = empty($request_body) ? 0 : strlen($request_body);

		$request_header = $this->buildRequestPacketHeader($length);
		return $request_header.$request_body;
	}

	/*
	 * 封装请求数据包头
	 */
	private function buildRequestPacketHeader($length) {
		Load('extend');
		$header = pack ( 'a8', random_string(4) );
		$header .= pack ( 'n', $this->sdk_version );
		$header .= pack ( 'n', $this->act );
		$header .= pack ( 'N', $length );
		$header .= pack ( 'a32', $this->client_id );
		$header .= pack ( 'C', $this->encrypt_type );
		$header .= pack ( 'a15', random_string(15) );
		return $header;
	}

	/*
	 * 封装请求数据包体
	 */
	private function buildRequestPacketBody($body) {
		if (empty($body))return '';
		$body  = json_encode($body);
		
		$encrypt_array = DecToBinArray($this->encrypt_type);
		if($encrypt_array[7] == 1){			
			$client_encrypt_key = $this->client_encrypt_key;
			if($encrypt_array[5] == 1){//aes加密
				$body = encryptAes($client_encrypt_key[1]['sign'], $body);
			}elseif($encrypt_array[6] == 1){//3des加密
				$body = encrypt3des($client_encrypt_key[0]['sign'], $client_encrypt_key[0]['sign_iv'], $body);
			}
		}
		if($encrypt_array[0] == 1){
			$body = gzencode($body, 3);
		}
		$body = base64_encode($body);
		return $body;
	}
    
}
