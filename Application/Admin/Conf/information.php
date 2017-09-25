<?php
/**
 * @date 2015-4-27
 * @author tww <merry2014@vip.qq.com>
 */
if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
	return array(
		'CURL_INFORMATION_URL'  => 'http://news-collect.tigercai.com/api/lottory/provider_index.php'
	);
}elseif( get_cfg_var('PROJECT_RUN_MODE') == 'TEST' ){
	return array(
		'CURL_INFORMATION_URL'  => 'http://news-collect.tigercai.com/api/lottory/provider_index.php'
	);
}else {
	return array(
		'CURL_INFORMATION_URL'  => 'http://192.168.1.159/collect_api/api/lottory/provider_index.php'
	);
}


