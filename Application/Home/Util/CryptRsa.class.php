<?php

class CryptRsa {
	/**
	 * private key
	 */
	private $_privKey;

	/**
	 * public key
	 */
	private $_pubKey;

	/**
	 * the keys saving path
	 */
	private $_keyPath;

	/**
	 * the construtor,the param $path is the keys saving path
	 */
	public function __construct($path) {
		if(empty($path) || !is_dir($path)) {
			error_log(date('Y-m-d H:i:s').':RSA KEY ERROR, ERROR PATH '.$path.PHP_EOL, 3, './RSA.ERROR');
			throw new Exception('Must set the keys save path');
		}

		$this->_keyPath = $path;
	}

	/**
	 * create the key pair,save the key to $this->_keyPath
	 */
	public function createKey($len = 512) {
		$config = array('digest_alg' => 'sha1',
                   'private_key_type' => OPENSSL_KEYTYPE_RSA,
                   'private_key_bits' => $len,
                   //"config" => "E:/xampp/apache/bin/openssl.cnf"
               );
			
		$r = openssl_pkey_new($config);
		openssl_pkey_export($r, $privKey, null, $config);
		file_put_contents($this->_keyPath . DIRECTORY_SEPARATOR . 'priv.key', $privKey);
		$this->_privKey = openssl_pkey_get_private($privKey);

		$rp = openssl_pkey_get_details($r);
		$pubKey = $rp['key'];
		file_put_contents($this->_keyPath . DIRECTORY_SEPARATOR .  'pub.key', $pubKey);
		$this->_pubKey = openssl_pkey_get_public($pubKey);
	}

	/**
	 * setup the private key
	 */
	public function setupPrivKey() {
		if(is_resource($this->_privKey)){
			return true;
		}
		$file = $this->_keyPath . DIRECTORY_SEPARATOR . 'priv.key';
		$prk = file_get_contents($file);
		$this->_privKey = openssl_pkey_get_private($prk);
		return true;
	}

	/**
	 * setup the public key
	 */
	public function setupPubKey() {
		if(is_resource($this->_pubKey)){
			return true;
		}
		$file = $this->_keyPath . DIRECTORY_SEPARATOR .  'pub.key';
		$puk = file_get_contents($file);
		$this->_pubKey = openssl_pkey_get_public($puk);
		return true;
	}

	/**
	 * encrypt with the private key
	 */
	public function privEncrypt($data) {
		if(!is_string($data)){
			return null;
		}

		$this->setupPrivKey();

		$r = openssl_private_encrypt($data, $encrypted, $this->_privKey);
		if($r){
			return base64_encode($encrypted);
		}
		return null;
	}

	/**
	 * decrypt with the private key
	 */
	public function privDecrypt($encrypted) {
		if(!is_string($encrypted)){
			return null;
		}

		$this->setupPrivKey();

		$encrypted = base64_decode($encrypted);

		$r = openssl_private_decrypt($encrypted, $decrypted, $this->_privKey);
		if($r){
			return $decrypted;
		}
		return null;
	}

	/**
	 * encrypt with public key
	 */
	public function pubEncrypt($data) {
		if(!is_string($data)){
			return null;
		}

		$this->setupPubKey();

		$r = openssl_public_encrypt($data, $encrypted, $this->_pubKey);
		if($r){
			return base64_encode($encrypted);
		}
		return null;
	}

	/**
	 * decrypt with the public key
	 */
	public function pubDecrypt($crypted) {
		if(!is_string($crypted)){
			return null;
		}

		$this->setupPubKey();

		$crypted = base64_decode($crypted);

		$r = openssl_public_decrypt($crypted, $decrypted, $this->_pubKey);
		if($r){
			return $decrypted;
		}
		return null;
	}
	

    /**
    * 获取$modulus
    */
    public function getModulus()
    {
        $cmd = 'openssl rsa -in '. $this->_keyPath .'priv.key -noout -modulus';
		$modulus = exec($cmd,$res,$ret);
		$mod     = explode('=', $modulus);
        return empty($mod[1])?'':$mod[1];
    }

	public function __destruct() {
		if(is_resource($this->_privKey)){
			@fclose($this->_privKey);
		}
		if(is_resource($this->_pubKey)){
			@fclose($this->_pubKey);
		}
	}

}