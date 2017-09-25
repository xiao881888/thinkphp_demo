<?php
require __DIR__ . '/xhprof_functions.php';
require __DIR__ . '/MysqlXhprofData.php';
if (!class_exists('Predis\Autoloader', false)) {
	require __DIR__ . '/Predis/Autoloader.php';
}


