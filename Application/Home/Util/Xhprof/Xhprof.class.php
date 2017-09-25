<?php
require __DIR__ . '/xhprof_lib.php';
class Xhprof{
	private static $_xhprof_is_running = false;
	private $_config = array();
	private $_xhprof_data_obj = null;

	public function __construct(){
		$this->_config = $this->_loadXhprofConfig();
		$this->_xhprof_data_obj = $this->_getXhprofDataObject();
	}

	public function startXhprofProfiler(){
		if ($this->_checkXhprofIsTriggered()) {
			xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
		}
	}

	public function finishXhprofProfiler($p_name, $p_version_tag=''){
		if (!$this->_getXhprofRunningStatus()) {
			return false;
		}
		$xhprof_data = xhprof_disable();
		if (empty($xhprof_data)) {
			return false;
		}
		$program_name = $this->_getProgramName($p_name);
		$run_id = $this->_xhprof_data_obj->saveProfData($xhprof_data, $program_name, $p_version_tag);
		$this->_setXhprofRunningStatus(false);
		return $run_id;
	}

	public function registerShutdownFunctionForFinish($p_name, $p_version_tag=''){
		register_shutdown_function(array($this,	'finishXhprofProfiler'), $p_name, $p_version_tag);
	}

	private function _loadXhprofConfig(){
		$config = require_once __DIR__ . '/config.php';
		return $config;
	}

	private function _getXhprofDataObject(){
		$class_name = ucfirst($this->_config['db_type']) . $this->_config['data_class_suffix'];
		return new $class_name($this->_config);
	}
	
		
	private function _checkXhprofIsTriggered(){
		if ($this->_config['xhprof_switch_open'] && $this->_getRandomTriggerSignal()) {
			$this->_setXhprofRunningStatus(true);
			return true;
		}
		return false;
	}
	
	private function _getRandomTriggerSignal(){
		if (mt_rand(1, $this->_config['random_range']) == 1) {
			return true;
		}
		return false;
	}
	
	private function _setXhprofRunningStatus($p_bool){
		self::$_xhprof_is_running = $p_bool;
	}
	
	private function _getXhprofRunningStatus(){
		return self::$_xhprof_is_running;
	}

	private function _getProgramName($p_name){
		if (empty($p_name)) {
			if (defined('MODULE_NAME') && defined('ACTION_NAME')) {
				return MODULE_NAME . '_' . ACTION_NAME;
			}else{
				return 'NO_NAME';
			}
		} else {
			return $p_name;
		}
	}
}

