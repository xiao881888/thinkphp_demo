<?php
namespace Home\Controller;
use Think\Controller;
use Home\Util\XML2Array;

class PaddingDataController extends Controller {
	
	public function dlt() {
		$xml_string = $this->request_by_curl("http://www.500wan.com/static/info/kaijiang/xml/dlt/13047.xml");
		$array = $this->_xmlToArray($xml_string);
		$data = $array['xml'];
		
		dump($data);
		exit;
		
		$add = array(
			'issue_no' => $data['PeriodicalNO'],
			'lottery_id' => 3,
			'issue_prize_number' => $data['ForeResult'].'#'.$data['BackResult'],
			'issue_prize_time' => $data['ResultTime'],
			'issue_proxy_status' => 4,
			'issue_task_status' => 17,
			'issue_is_current' => 3,
			'issue_winning_total' => $data['TotalMoney'],
			'issue_sell_amount' => $data['SEXETotalMoney'],
			'issue_winnings_pool' => $data['CCMoney'],
			'issue_prize_status' => 3,
		);
		
		$result = D('Issue')->add($add);
		dump($result);
	}
	
	private function _xmlToArray($xml_string) {
		import('@.Util.XML2Array');
		return XML2Array::createArray($xml_string);
	}
	
	private	function request_by_curl($remote_server) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $remote_server);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "");
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	
	
}

?>