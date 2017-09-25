<?php
/**
*  定时更新彩票各彩种彩期、开奖方案
 ！注意，老虎彩票正式数据不要使用本程序，因为cp_issue部分关键字段本程序无法采集
* 
* author joey 2015-05-06
*/

class UpdateLotteryPrizeTool{

	private $_db;

	public function __construct(){
		$this->initDbIns();
	}

	public function start($pLottery='ALL'){
		$lotterys = $this->getLotteryHash();
		if ($pLottery == 'ALL') {
			$lotteryArr = $lotterys;
		} else if (array_key_exists($pLottery, $lotterys)) {
			$lotteryArr = array($lotterys[$pLottery]);
		} else {
			echo '错误访问！';
			exit;
		}

		foreach ($lotteryArr as $lotteryName) {
			$lottery  = new $lotteryName($this->_db);

			$newIssue = $lottery->analyseNewIssue();

			if (!empty($newIssue)) {
				
				$newPrize = $lottery->fetchIssuePrize($newIssue);

				$this->startTrans();

				try{					
					$issueIds = $lottery->saveIssue($newIssue);

					$lottery->savePrize($newPrize, $issueIds);
				} catch(Exception $e) {
					echo '彩种 '.$lotteryName.'采集最新采集入库失败，请排查！';
					
					$this->rollback();

					continue;
				}

				$this->commit();
			} else if ($newIssue === false) {
				echo '采集最新彩期异常，建议全量采集！';
			}
		}
	}

	private function startTrans(){
		$this->_db->startTrans();
	}

	private function commit(){
		$this->_db->commit();
	}

	private function rollback(){
		$this->_db->rollback();
	}

	private function initDbIns(){
		$this->_db = new DB();

		return $this->_db;
	}

	private function getLotteryHash(){
		return array(
			'DLT'  => 'DLTLottery',
			'SSQ'  => 'SSQLottery',
			'FCSD' => 'FCSDLottery',
			'SYXW' => 'SYXWLottery',
			'JSKS' => 'JSKSLottery'
			);
	}

}

abstract class LotteryBase{
	private $lotteryId;

	protected $_db;

	public function __construct($pDbIns){
		$this->_db = $pDbIns;
	}

	public function getLatestIssue(){
		$sql = "SELECT issue_no FROM `cp_issue_tmp` WHERE lottery_id = ".$this->getLotteryId()." ORDER BY issue_id DESC LIMIT 1";
		$issue = $this->_db->querySQL($sql);
		if (empty($issue)) {
			$issue = array();
		}

		return $issue[0];
	}

	public function analyseNewIssue(){
		$latestIssue = $this->getLatestIssue();
		$latestIssueNo = $latestIssue['issue_no'];

		$needFetchAll = true;
		for($tryTime = 1; $tryTime <= 5; $tryTime++){
			$newIssue = $this->fetchNewIssue($tryTime);
			if (array_key_exists($latestIssueNo, $newIssue)) {
				$needFetchAll = false;
				break;
			}
		}

		if ($needFetchAll) {
			return false;
		}

		$result = array();
		foreach ($newIssue as $issueNo => $issue) {
			if ($issueNo > $latestIssueNo) {
				$result[$issueNo] = $issue;
			}
		}

		return $result;
	}

	public function saveIssue($pIssue){
		$sqlArr = array();
		foreach ($pIssue as $issue) {
			$sqlArr[] .= "('{$issue['issueNo']}', ".$this->getLotteryId().", '{$issue['issuePrizeNum']}', '{$issue['openTime']}', 4, 17, 3, 
					{$issue['issue_winning_total']}, '', {$issue['issue_sell_amount']}, {$issue['issue_winnings_pool']}, 3, '{$issue['issueTestNum']}')";
		}

		$sql = "INSERT INTO `cp_issue_tmp`(issue_no, lottery_id, issue_prize_number, issue_prize_time, issue_proxy_status,
				issue_task_status, issue_is_current, issue_winning_total, issue_slogon, 
				issue_sell_amount,issue_winnings_pool,issue_prize_status,issue_test_number) VALUES".implode(',', $sqlArr);

		$result = $this->_db->execSQL($sql);

		if (!$result) {
			echo $sql;
			throw new Exception('数据库操作错误,错误信息：'.mysql_error());
		}

		$issueIds = array();
		$sql = "SELECT issue_id, issue_no FROM `cp_issue_tmp` WHERE lottery_id = ".$this->getLotteryId();
		$result = $this->_db->querySql($sql);
		foreach ($result as $row) {
			$issueIds[$row['issue_no']] = $row['issue_id'];
		}

		return $issueIds;
	}

	public function savePrize($pPrize, $pIssueIds){
		foreach ($pPrize as $prize) {

			$sqlArr = array();	
			foreach($prize as $row){
				$sqlArr[] = "({$pIssueIds[$row['issue_no']]}, '{$row['ws_bonus_name']}', {$row['ws_winning_num']}, {$row['ws_bonus_money']}, {$row['ws_bonus_level']}, '{$row['ws_create_time']}')";
			}

			$sql = "INSERT INTO `cp_winnings_scheme_tmp`(issue_id, ws_bonus_name, ws_winning_num, ws_bonus_money, ws_bonus_level, ws_create_time) VALUES".implode(',', $sqlArr);

			$result = $this->_db->execSQL($sql);

			if (!$result) {
				echo $sql;
				throw new Exception('数据库操作错误,错误信息：'.mysql_error());
			}
		
		}

		return true;
	}

	abstract protected function getLotteryId();

	abstract protected function fetchNewIssue($pTyeTime);

	abstract public function fetchIssuePrize(&$pIssue);
}

class DLTLottery extends LotteryBase{
	protected function getLotteryId(){
		return 3;
	}

	protected function fetchNewIssue($pTryTime){
		if ($pTryTime == 1) {
			$api = 'http://www.500wan.com/static/info/kaijiang/xml/dlt/list5.xml';
		} else {
			$api = 'http://www.500wan.com/static/info/kaijiang/xml/dlt/list.xml';
		}

		$resp = Common::requestXMLApi($api);
		if (empty($resp)) {
			return array();
		}

		$issue = array();
		foreach ($resp['row'] as $row) {		
			$issue[$row['@attributes']['expect']] = array(
				'issueNo' => $row['@attributes']['expect'],
				'issuePrizeNum' => str_replace('|', '#', $row['@attributes']['opencode']),
				'openTime' => $row['@attributes']['opentime']
				);
		}

		ksort($issue);

		return $issue;
	}

	public function fetchIssuePrize(&$pIssue){
		$api = 'http://www.500wan.com/static/info/kaijiang/xml/dlt/{$issueNo}.xml';
		
		foreach ($pIssue as $issueNo => $issue) {
			$resp = Common::requestXMLApi(str_replace('{$issueNo}', $issue['issueNo'], $api));

			$prize = array(
				1 => array(
					'issue_no' => $issue['issueNo'],
					'ws_bonus_name' => '一等奖，基本',
					'ws_winning_num' => intval($resp['BaseNum1']),
					'ws_bonus_money' => intval(str_replace(',', '', $resp['BaseMoney1'])),
					'ws_bonus_level' => 1,
					'ws_create_time' => date('Y-m-d H:i:s')
					),
				2 => array(
					'issue_no' => $issue['issueNo'],
					'ws_bonus_name' => '一等奖，追加',
					'ws_winning_num' => intval($resp['AdditionNum1']),
					'ws_bonus_money' => intval(str_replace(',', '', $resp['AdditionMoney1'])),
					'ws_bonus_level' => 2,
					'ws_create_time' => date('Y-m-d H:i:s')
					),
				3 => array(
					'issue_no' => $issue['issueNo'],
					'ws_bonus_name' => '二等奖，基本',
					'ws_winning_num' => intval($resp['BaseNum2']),
					'ws_bonus_money' => intval(str_replace(',', '', $resp['BaseMoney2'])),
					'ws_bonus_level' => 3,
					'ws_create_time' => date('Y-m-d H:i:s')
					),
				4 => array(
					'issue_no' => $issue['issueNo'],
					'ws_bonus_name' => '二等奖，追加',
					'ws_winning_num' => intval($resp['AdditionNum2']),
					'ws_bonus_money' => intval(str_replace(',', '', $resp['AdditionMoney2'])),
					'ws_bonus_level' => 4,
					'ws_create_time' => date('Y-m-d H:i:s')
					),
				5 => array(
					'issue_no' => $issue['issueNo'],
					'ws_bonus_name' => '三等奖，基本',
					'ws_winning_num' => intval($resp['BaseNum3']),
					'ws_bonus_money' => intval(str_replace(',', '', $resp['BaseMoney3'])),
					'ws_bonus_level' => 5,
					'ws_create_time' => date('Y-m-d H:i:s')
					),
				6 => array(
					'issue_no' => $issue['issueNo'],
					'ws_bonus_name' => '三等奖，追加',
					'ws_winning_num' => intval($resp['AdditionNum3']),
					'ws_bonus_money' => intval(str_replace(',', '', $resp['AdditionMoney3'])),
					'ws_bonus_level' => 6,
					'ws_create_time' => date('Y-m-d H:i:s')
					),
				7 => array(
					'issue_no' => $issue['issueNo'],
					'ws_bonus_name' => '四等奖，基本',
					'ws_winning_num' => intval($resp['BaseNum4']),
					'ws_bonus_money' => intval(str_replace(',', '', $resp['BaseMoney4'])),
					'ws_bonus_level' => 7,
					'ws_create_time' => date('Y-m-d H:i:s')
					),
				8 => array(
					'issue_no' => $issue['issueNo'],
					'ws_bonus_name' => '四等奖，追加',
					'ws_winning_num' => intval($resp['AdditionNum4']),
					'ws_bonus_money' => intval(str_replace(',', '', $resp['AdditionMoney4'])),
					'ws_bonus_level' => 8,
					'ws_create_time' => date('Y-m-d H:i:s')
					),
				9 => array(
					'issue_no' => $issue['issueNo'],
					'ws_bonus_name' => '五等奖，基本',
					'ws_winning_num' => intval($resp['BaseNum5']),
					'ws_bonus_money' => intval(str_replace(',', '', $resp['BaseMoney5'])),
					'ws_bonus_level' => 9,
					'ws_create_time' => date('Y-m-d H:i:s')
					),
				10 => array(
					'issue_no' => $issue['issueNo'],
					'ws_bonus_name' => '五等奖，追加',
					'ws_winning_num' => intval($resp['AdditionNum5']),
					'ws_bonus_money' => intval(str_replace(',', '', $resp['AdditionMoney5'])),
					'ws_bonus_level' => 10,
					'ws_create_time' => date('Y-m-d H:i:s')
					),
				11 => array(
					'issue_no' => $issue['issueNo'],
					'ws_bonus_name' => '六等奖，基本',
					'ws_winning_num' => intval($resp['BaseNum6']),
					'ws_bonus_money' => intval(str_replace(',', '', $resp['BaseMoney6'])),
					'ws_bonus_level' => 11,
					'ws_create_time' => date('Y-m-d H:i:s')
					),
				12 => array(
					'issue_no' => $issue['issueNo'],
					'ws_bonus_name' => '六等奖，追加',
					'ws_winning_num' => intval($resp['AdditionNum6']),
					'ws_bonus_money' => intval(str_replace(',', '', $resp['AdditionMoney6'])),
					'ws_bonus_level' => 12,
					'ws_create_time' => date('Y-m-d H:i:s')
					)
			);
				
			$prizeArr[] = $prize;

			$pIssue[$issueNo]['issue_sell_amount'] = intval(str_replace(',', '', $resp['TotalMoney']));
			$pIssue[$issueNo]['issue_winnings_pool'] = intval(str_replace(',', '', $resp['CCMoney']));
			$pIssue[$issueNo]['issue_winning_total'] = ($prize[1]['ws_winning_num']*$prize[1]['ws_bonus_money'] + $prize[2]['ws_winning_num']*$prize[2]['ws_bonus_money'] + $prize[3]['ws_winning_num']*$prize[3]['ws_bonus_money'] + $prize[4]['ws_winning_num']*$prize[4]['ws_bonus_money'] + $prize[5]['ws_winning_num']*$prize[5]['ws_bonus_money'] + $prize[6]['ws_winning_num']*$prize[6]['ws_bonus_money'] + $prize[7]['ws_winning_num']*$prize[7]['ws_bonus_money'] +$prize[8]['ws_winning_num']*$prize[8]['ws_bonus_money'] + $prize[9]['ws_winning_num']*$prize[9]['ws_bonus_money'] + $prize[10]['ws_winning_num']*$prize[10]['ws_bonus_money'] + $prize[11]['ws_winning_num']*$prize[11]['ws_bonus_money'] + $prize[12]['ws_winning_num']*$prize[12]['ws_bonus_money']);
		}

		return $prizeArr;
	}


}

class SSQLottery extends LotteryBase{
	protected function getLotteryId(){
		return 1;
	}

	protected function fetchNewIssue($pTryTime){
		if ($pTryTime == 1) {
			$api = 'http://www.500wan.com/static/info/kaijiang/xml/ssq/list5.xml';
		} else {
			$api = 'http://www.500wan.com/static/info/kaijiang/xml/ssq/list.xml';
		}

		$resp = Common::requestXMLApi($api);
		if (empty($resp)) {
			return array();
		}

		$issue = array();
		foreach ($resp['row'] as $row) {		
			$issue[$row['@attributes']['expect']] = array(
				'issueNo' => $row['@attributes']['expect'],
				'issuePrizeNum' => str_replace('|', '#', $row['@attributes']['opencode']),
				'openTime' => $row['@attributes']['opentime']
				);
		}

		ksort($issue);

		return $issue;
	}

	public function fetchIssuePrize(&$pIssue){
		$api = 'http://www.500wan.com/static/info/kaijiang/xml/ssq/{$issueNo}.xml';
		
		foreach ($pIssue as $issueNo => $issue) {
			$resp = Common::requestXMLApi(str_replace('{$issueNo}', $issue['issueNo'], $api));

			$prize = array(
				1 => array(
					'issue_no' => $issue['issueNo'],
					'ws_bonus_name' => '一等奖',
					'ws_winning_num' => intval($resp['Num1']),
					'ws_bonus_money' => intval(str_replace(',', '', $resp['Money1'])),
					'ws_bonus_level' => 1,
					'ws_create_time' => date('Y-m-d H:i:s')
					),
				2 => array(
					'issue_no' => $issue['issueNo'],
					'ws_bonus_name' => '二等奖',
					'ws_winning_num' => intval($resp['Num2']),
					'ws_bonus_money' => intval(str_replace(',', '', $resp['Money2'])),
					'ws_bonus_level' => 2,
					'ws_create_time' => date('Y-m-d H:i:s')
					),
				3 => array(
					'issue_no' => $issue['issueNo'],
					'ws_bonus_name' => '三等奖',
					'ws_winning_num' => intval($resp['Num3']),
					'ws_bonus_money' => intval(str_replace(',', '', $resp['Money3'])),
					'ws_bonus_level' => 3,
					'ws_create_time' => date('Y-m-d H:i:s')
					),
				4 => array(
					'issue_no' => $issue['issueNo'],
					'ws_bonus_name' => '四等奖',
					'ws_winning_num' => intval($resp['Num4']),
					'ws_bonus_money' => intval(str_replace(',', '', $resp['Money4'])),
					'ws_bonus_level' => 4,
					'ws_create_time' => date('Y-m-d H:i:s')
					),
				5 => array(
					'issue_no' => $issue['issueNo'],
					'ws_bonus_name' => '五等奖',
					'ws_winning_num' => intval($resp['Num5']),
					'ws_bonus_money' => intval(str_replace(',', '', $resp['Money5'])),
					'ws_bonus_level' => 5,
					'ws_create_time' => date('Y-m-d H:i:s')
					),
				6 => array(
					'issue_no' => $issue['issueNo'],
					'ws_bonus_name' => '六等奖',
					'ws_winning_num' => intval($resp['Num6']),
					'ws_bonus_money' => intval(str_replace(',', '', $resp['Money6'])),
					'ws_bonus_level' => 6,
					'ws_create_time' => date('Y-m-d H:i:s')
					)
			);
				
			$prizeArr[] = $prize;

			$pIssue[$issueNo]['issue_sell_amount'] = intval(str_replace(',', '', $resp['TotalMoney']));
			$pIssue[$issueNo]['issue_winnings_pool'] = intval(str_replace(',', '', $resp['CCMoney']));
			$pIssue[$issueNo]['issue_winning_total'] = ($prize[1]['ws_winning_num']*$prize[1]['ws_bonus_money'] + $prize[2]['ws_winning_num']*$prize[2]['ws_bonus_money'] + $prize[3]['ws_winning_num']*$prize[3]['ws_bonus_money'] + $prize[4]['ws_winning_num']*$prize[4]['ws_bonus_money'] + $prize[5]['ws_winning_num']*$prize[5]['ws_bonus_money'] + $prize[6]['ws_winning_num']*$prize[6]['ws_bonus_money'] + $prize[7]['ws_winning_num']*$prize[7]['ws_bonus_money'] +$prize[8]['ws_winning_num']*$prize[8]['ws_bonus_money'] + $prize[9]['ws_winning_num']*$prize[9]['ws_bonus_money'] + $prize[10]['ws_winning_num']*$prize[10]['ws_bonus_money'] + $prize[11]['ws_winning_num']*$prize[11]['ws_bonus_money'] + $prize[12]['ws_winning_num']*$prize[12]['ws_bonus_money']);
		}

		return $prizeArr;
	}

}

class FCSDLottery extends LotteryBase{
	protected function getLotteryId(){
		return 2;
	}

	protected function fetchNewIssue($pTryTime){
		if ($pTryTime == 1) {
			$api = 'http://www.500wan.com/static/info/kaijiang/xml/sd/list5.xml';
		} else {
			$api = 'http://www.500wan.com/static/info/kaijiang/xml/sd/list.xml';
		}

		$resp = Common::requestXMLApi($api);
		if (empty($resp)) {
			return array();
		}

		$issue = array();
		foreach ($resp['row'] as $row) {		
			$issue[$row['@attributes']['expect']] = array(
				'issueNo' => $row['@attributes']['expect'],
				'issuePrizeNum' => str_replace(',', '#', $row['@attributes']['opencode']),
				'issueTestNum' => str_replace(',', '#', $row['@attributes']['trycode']),
				'openTime' => $row['@attributes']['opentime']
				);
		}

		ksort($issue);

		return $issue;
	}

	public function fetchIssuePrize(&$pIssue){
		$api = 'http://caipiao.163.com/award/3d/{$issueNo}.html';

		$prizeArr = array();
		foreach ($pIssue as $issueNo => $issue) {
			$resp = Common::requestHTMLApi(str_replace('{$issueNo}', $issueNo, $api));

			$issueSellAmount = preg_match('/id="sale">(\d+)<\/span>/', $resp, $match1);
			$zhixuan3PrizeNum = preg_match('/id="award1">(\d+)<\/span>/', $resp, $match2);
			$zuxuan3PrizenNum = preg_match('/id="award2">(\d+)<\/span>/', $resp, $match3);
			$zuxuan6PrizeNum = preg_match('/id="award3">(\d+)<\/span>/', $resp, $match4);
			if ($issueSellAmount == 0 || $zhixuan3PrizeNum == 0 || $zuxuan3PrizenNum == 0 || $zuxuan6PrizeNum == 0) {
				throw new Exception('采集福彩3D开奖方案失败，彩期'.$issueId);
			}

			$prize = array(
				1 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '直选3',
					'ws_winning_num' => intval($match2[1]),
					'ws_bonus_money' => 1040,
					'ws_bonus_level' => 1,
					'ws_create_time' => date('Y-m-d H:i:s')
					),
				2 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '组选3',
					'ws_winning_num' => intval($match3[1]),
					'ws_bonus_money' => 346,
					'ws_bonus_level' => 2,
					'ws_create_time' => date('Y-m-d H:i:s')
					),
				3 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '组选6',
					'ws_winning_num' => intval($match4[1]),
					'ws_bonus_money' => 173,
					'ws_bonus_level' => 3,
					'ws_create_time' => date('Y-m-d H:i:s')
					)
			);

			$prizeArr[] = $prize;

			$pIssue[$issueNo]['issue_sell_amount'] = $match1[1];
			$pIssue[$issueNo]['issue_winnings_pool'] = 0;
			$pIssue[$issueNo]['issue_winning_total'] = 0;
		}

		return $prizeArr;
	}

}

class SYXWLottery extends LotteryBase{
	protected function getLotteryId(){
		return 4;
	}

	protected function fetchNewIssue($pTryTime){
		$issue = array();

		$api = 'http://caipiao.163.com/award/gd11xuan5/{$date}.html';
		for($i = 0; $i < $pTryTime ;$i++){
			$resp = Common::requestHTMLApi(str_replace('{$date}', date('Ymd', strtotime('-'.$i.'days')), $api));

			if (empty($resp)) {
				throw new Exception('采集11选5彩期异常！');
			}

			preg_match_all('/<td\s+class="start"\s+data-period="(\d+?)"\s+data-award="(.+?)">/', $resp, $match);
			foreach ($match[1] as $key => $issueId) {
				$issue[$issueId] = array(
					'issueNo' => $issueId,
					'issuePrizeNum' => preg_replace('/\s/', ',', $match[2][$key]),
					'openTime' => date('Y-m-d 00:00:00', strtotime('-'.$i.'days')),
					'issue_sell_amount' => 0,
					'issue_winnings_pool' => 0,
					'issue_winning_total' => 0
					);
			}
		}

		ksort($issue);

		return $issue;
	}

	public function fetchIssuePrize(&$pIssue){
		$prizeArr = array();
		foreach ($pIssue as $issueNo => $issue) {
			$prize = array(
				1 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '任选二',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 6,
					'ws_bonus_level' => 1,
					'ws_create_time' => date('Y-m-d H:i:s')
				),
				2 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '任选三',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 19,
					'ws_bonus_level' => 2,
					'ws_create_time' => date('Y-m-d H:i:s')
				),
				3 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '任选四',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 78,
					'ws_bonus_level' => 3,
					'ws_create_time' => date('Y-m-d H:i:s')
				),
				4 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '任选五',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 540,
					'ws_bonus_level' => 4,
					'ws_create_time' => date('Y-m-d H:i:s')
				),
				5 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '任选六',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 90,
					'ws_bonus_level' => 5,
					'ws_create_time' => date('Y-m-d H:i:s')
				),
				6 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '任选七',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 26,
					'ws_bonus_level' => 6,
					'ws_create_time' => date('Y-m-d H:i:s')
				),
				7 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '任选八',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 9,
					'ws_bonus_level' => 7,
					'ws_create_time' => date('Y-m-d H:i:s')
				),
				8 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '前一直选',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 13,
					'ws_bonus_level' => 8,
					'ws_create_time' => date('Y-m-d H:i:s')
				),
				9 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '前二直选',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 130,
					'ws_bonus_level' => 9,
					'ws_create_time' => date('Y-m-d H:i:s')
				),
				10 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '前二组选',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 65,
					'ws_bonus_level' => 10,
					'ws_create_time' => date('Y-m-d H:i:s')
				),
				11 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '前三直选',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 1170,
					'ws_bonus_level' => 11,
					'ws_create_time' => date('Y-m-d H:i:s')
				),
				12 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '前三组选',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 195,
					'ws_bonus_level' => 12,
					'ws_create_time' => date('Y-m-d H:i:s')
				)
			);

			$prizeArr[] = $prize;
		}

		return $prizeArr;
	}
}

class JSKSLottery extends LotteryBase{
	protected function getLotteryId(){
		return 5;
	}

	protected function fetchNewIssue($pTryTime){
		$issue = array();

		$api = 'http://caipiao.163.com/award/jskuai3/?gameEn=oldkuai3&date={$date}';
		for($i = 0; $i < $pTryTime ;$i++){
			$resp = Common::requestHTMLApi(str_replace('{$date}', date('Ymd', strtotime('-'.$i.'days')), $api));

			if (empty($resp)) {
				throw new Exception('采集江苏快3彩期异常！');
			}

			preg_match_all('/<td\s+class="start"\s+data-win-number=\'(.+?)\'\s+data-period="(\d+)">/', $resp, $match);
			foreach ($match[2] as $key => $issueId) {
				$issue[$issueId] = array(
					'issueNo' => $issueId,
					'issuePrizeNum' => preg_replace('/\s/', ',', $match[1][$key]),
					'openTime' => date('Y-m-d 00:00:00', strtotime('-'.$i.'days')),
					'issue_sell_amount' => 0,
					'issue_winnings_pool' => 0,
					'issue_winning_total' => 0
					);
			}
		}

		ksort($issue);

		return $issue;
	}

	public function fetchIssuePrize(&$pIssue){
		$prizeArr = array();

		foreach ($pIssue as $issueNo => $issue) {
			$prize = array(
				1 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '和值',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 240,
					'ws_bonus_level' => 1,
					'ws_create_time' => date('Y-m-d H:i:s')
				),
				2 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '三同号通选',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 40,
					'ws_bonus_level' => 2,
					'ws_create_time' => date('Y-m-d H:i:s')
				),
				3 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '三同号单选',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 240,
					'ws_bonus_level' => 3,
					'ws_create_time' => date('Y-m-d H:i:s')
				),
				4 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '三不同号',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 40,
					'ws_bonus_level' => 4,
					'ws_create_time' => date('Y-m-d H:i:s')
				),
				5 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '三连号通选',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 10,
					'ws_bonus_level' => 5,
					'ws_create_time' => date('Y-m-d H:i:s')
				),
				6 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '二同号复选',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 15,
					'ws_bonus_level' => 6,
					'ws_create_time' => date('Y-m-d H:i:s')
				),
				7 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '二同号单选',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 80,
					'ws_bonus_level' => 7,
					'ws_create_time' => date('Y-m-d H:i:s')
				),
				8 => array(
					'issue_no' => $issueNo,
					'ws_bonus_name' => '二不同号',
					'ws_winning_num' => 0,
					'ws_bonus_money' => 8,
					'ws_bonus_level' => 8,
					'ws_create_time' => date('Y-m-d H:i:s')
				),	
			);

			$prizeArr[] = $prize;
		}

		return $prizeArr;
	}
}



class DB {

	private $_config = array(
		'HOST' 		=> 'fzhcwlkjyxgs.mysql.rds.aliyuncs.com',
		'PORT'		=> 3306,
		'DBNAME'	=> 'tigercai',
		'DBUSER'	=> 'tigercai_server',
		'DBPWD'		=> 'e4huY8J7e4'
		);

	// private $_config = array(
	// 	'HOST' 		=> '192.168.1.172',
	// 	'PORT'		=> 3306,
	// 	'DBNAME'	=> 'lottery_alpha',
	// 	'DBUSER'	=> 'root',
	// 	'DBPWD'		=> '123456'
	// 	);


	private $_link;

	public function __construct(){
		
		$this->_link = $this->_connect();
		
		return $this;
	}

	public function _connect(){
		
		static $_conn = null;
		
		if (is_null($_conn)) {
		 	
		 	$_conn = mysql_connect($this->_config['HOST'], $this->_config['DBUSER'], $this->_config['DBPWD'], true, 131072);
		 	if (!$_conn) {
		 		throw new Exception("MYSQL connect faile,error message ".mysql_error());
		 	}
		 	
		 	mysql_select_db($this->_config['DBNAME'], $_conn);

		 	mysql_query("SET NAMES 'utf8'", $_conn);
		 }

		 return $_conn;
	}

	public function execSQL($sql){
		
		$query = mysql_query($sql, $this->_link);
		
		return $query;
	}

	public function querySQL($sql){

		$query = mysql_query($sql, $this->_link);

		if (!$query) {
			return false;
		}

		$rest = array();

		while ($row = mysql_fetch_assoc($query)) {
			
			$rest[] = $row;
		}

		return $rest;
	}

	public function startTrans(){

		mysql_query('START TRANSACTION', $this->_link);
	}

	public function commit(){

		mysql_query('COMMIT', $this->_link);
	}

	public function rollback(){

		mysql_query('ROLLBACK', $this->_link);
	}

	public function close(){

		if ($this->_link) {
			mysql_close($this->_link);
		}

		$this->_link = null;
	}

	public function __destruct(){

		$this->close();
	}
}

class Common {

	static public function requestXMLApi($pUrl){
		$i = 3;
		while ($i > 0) {
			try {
				$resp = file_get_contents($pUrl);
			} catch (Exception $e) {
				$i--;
				sleep(1);
				continue;
			}
			break;
		}

		if (!empty($resp)) {
			$resp = simplexml_load_string($resp);
			$resp = Common::xml2Arr($resp);
			return $resp;
		} else {
			return array();
		}
	}

	static public function requestHTMLApi($pUrl){
		$i = 3;
		while ($i > 0) {
			try {
				$resp = file_get_contents($pUrl);
			} catch (Exception $e) {
				$i--;
				sleep(1);
				continue;
			}
			break;
		}
		
		return $resp;
	}

	static public function xml2Arr($pXml){

		$arr = json_decode(json_encode($pXml), true);

		foreach (array_slice($arr, 0) as $key => $value) {
			if(is_array($value)) {
				$arr[$key] = Common::xml2Arr($value);
			}
		}

		return $arr;
	}

	static public function dump(){
		
		$argc = func_num_args();
		if ($argc) {
			
			echo '<pre>';	
			for ($i=0; $i < $argc; $i++) { 
				var_dump(func_get_arg($i));	
			}
		}
	}
}


error_reporting(E_ALL ^ E_NOTICE);
$startTime = time();
echo 'start:'.date('Y-m-d H:i:s', $startTime).PHP_EOL;

$tool = new UpdateLotteryPrizeTool();
$tool->start('ALL');
$finishTime = time();
echo 'finish:'.date('Y-m-d H:i:s', $finishTime).PHP_EOL;
echo 'take '.($finishTime - $startTime).' seconds'.PHP_EOL;