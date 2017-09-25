<?php
class MysqlXHProfData{
	private $_db_obj = null;
	private $_run_tbname = '';
	private $_detail_tbname = '';
	private $_redis = null;
	private $_details_queue_name = '';

	public function __construct($p_config){
		try {
			$this->_db_obj = new PDO($p_config['db_dsn'], $p_config['db_user'], $p_config['db_pass'], $p_config['db_options']);
		} catch (PDOException $e) {
			die($e->getMessage());
		}
		$this->_redis = $this->_createRedisClient($p_config);
		$this->_details_queue_name = $p_config['details_queue_name'];
		$this->_run_tbname = getTableName($p_config['run_tbname_hash'], $p_config['run_default_tbname']);
		$this->_detail_tbname = getTableName($p_config['detail_tbname_hash'], $p_config['detail_default_tbname']);
	}

	public function saveProfData($p_xhprof_data, $p_name, $p_version_tag = ''){
		if (empty($p_xhprof_data) || empty($p_name)) {
			return false;
		}
		
		$run_id = $this->_getRunId($p_xhprof_data, $p_name, $p_version_tag);
		if (empty($run_id)) {
			return false;
		}
		
		$xh_result_order = 0;
		foreach ($p_xhprof_data as $function_desc => $profile_data) {
			$details_data[] = $this->_buildRunDetailData($function_desc, $profile_data, $run_id, $p_name, $xh_result_order);
			$xh_result_order++;
		}
		$this->_pushToNewDetailsQueue($details_data, $this->_detail_tbname, $run_id);
		
		return $run_id;
	}

	private function _createRedisClient($p_config){
		Predis\Autoloader::register();
		$server_config = array(
				'host' => $p_config['redis_host'],
				'port' => $p_config['redis_port'] 
		);
		return new Predis\Client($server_config);
	}

	private function _getRunId($p_xhprof_data, $p_name, $p_version_tag = ''){
		$domain = $_SERVER['HTTP_HOST'];
		$runid_cache_name = 'runid:' . $domain . ':' . $p_name . ':' . $p_version_tag;
		$run_id = $this->_redis->get($runid_cache_name);
		if (empty($run_id)) {
			$run_id = $this->_queryRunId($domain, $p_name, $p_version_tag);
			if (empty($run_id)) {
				$run_id = $this->_addRunInfo($p_xhprof_data, $p_name, $p_version_tag);
			}			
		}
		return $run_id;
	}

	private function _queryRunId($p_domain, $p_name, $p_version_tag){
		$id_field = 'run_id';
		$info['run_domain'] = $p_domain;
		$info['run_program_name'] = $p_name;
		$info['run_version_tag'] = $p_version_tag;
		$prepare_data = prepareSelectSql($info, $this->_run_tbname, $id_field);
		$pdo_statement = $this->_db_obj->prepare($prepare_data['sql']);
		$pdo_statement->execute($prepare_data['values']);
		$run_info = $pdo_statement->fetch(PDO::FETCH_ASSOC);
		return intval($run_info[$id_field]);
	}

	private function _addRunInfo($p_xhprof_data, $p_name, $p_version_tag){	
		$info['run_domain'] = $_SERVER['HTTP_HOST'];		
		$info['run_program_name'] = $p_name;
		$info['run_version_tag'] = $p_version_tag;
		
		$info['run_server_ip'] = $_SERVER["SERVER_ADDR"];
		$info['run_url'] = $_SERVER['REQUEST_URI'];
		$info['run_method'] = $_SERVER['REQUEST_METHOD'];
		$info['run_createtime'] = date('Y-m-d H:i:s');
		$run_id = $this->_add($info, $this->_run_tbname);
		return $run_id;
	}

	private function _buildRunDetailData($p_function_desc, $p_profile_data, $p_run_id, $p_name, $p_order){
		$prof_detail_data['run_id'] = $p_run_id;
		$prof_detail_data['xd_function_desc'] = $p_function_desc;
		$prof_detail_data['xd_call_times'] = $p_profile_data['ct'];
		$prof_detail_data['xd_wall_time'] = $p_profile_data['wt'];
		$prof_detail_data['xd_cpu_time'] = $p_profile_data['cpu'];
		$prof_detail_data['xd_memory_usage'] = $p_profile_data['mu'];
		$prof_detail_data['xd_peak_memory_usage'] = $p_profile_data['pmu'];
		$prof_detail_data['xd_order'] = $p_order;
		
		return $prof_detail_data;
	}

	private function _pushToNewDetailsQueue($p_data, $p_tbname, $p_run_id){
		$details_data_key = $p_tbname . ':' . $p_run_id;
		$details_data[$details_data_key] = $p_data;
		$this->_redis->lPush($this->_details_queue_name, json_encode($details_data));
	}

	private function _add($p_info, $p_tbname){
		$prepare_data = prepareInsertSql($p_info, $p_tbname);
		if (empty($prepare_data)) {
			return false;
		}
		$pdo_statement = $this->_db_obj->prepare($prepare_data['sql']);
		$pdo_statement->execute($prepare_data['values']);
		$run_id = $this->_db_obj->lastInsertId();
		return $run_id;
	}
}
