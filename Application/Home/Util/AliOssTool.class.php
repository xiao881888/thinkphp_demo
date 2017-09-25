<?php

namespace Home\Util;
class AliOssTool {
	/**
	* 上传本地文件到云服务器上
	* @param string $pFile {$bucket}:{$}
	* @param string $pDestiFile
	**
	*/
    static public function uploadFile($pFile, $pDestiFile){
        $config = C('UPLOAD_IMG_OSS.driverConfig');
        $bucket = $config['Bucket'];
		if (strpos($pDestiFile, ':') > 0) {
            $destiFile = substr($pDestiFile, strpos($pDestiFile, ':')+1);
		} else {
			//$bucket = ALI_BUCKET;
			$destiFile = $pDestiFile;
		}
		if (empty($bucket)) {
			return false;
		}
        vendor('AliyunOSS.sdk');
		$ossSdkService = new \ALIOSS($config['AccessKeyId'],$config['AccessKeySecret'],$config['Endpoint']);
		$ossSdkService->set_debug_mode(true);
		$response = $ossSdkService->upload_file_by_file($bucket,$destiFile,$pFile);

		if (is_object($response)) {
			if ($response->status == 200) {
				$header = $response->header;
				return $header['_info']['url'];
			}
		}

		return false;
	}

	static public function createDir($pDir){
		if (strpos($pDir, ':') > 0) {
			$bucket = substr($pDir, 0, strpos($pDir, ':'));
			$dir = substr($pDir, strpos($pDir, ':')+1);
		} else {
			$bucket = ALI_BUCKET;
			$dir = $pDir;
		}

		if (empty($bucket)) {
			return false;
		}
        vendor('AliyunOSS.sdk');
		$ossSdkService = new \ALIOSS();
		$ossSdkService->set_debug_mode(false);

		$response = $ossSdkService->create_object_dir($bucket, $dir);
	}
}