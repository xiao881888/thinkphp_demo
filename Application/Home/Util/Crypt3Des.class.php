<?php
/**
*
* PHP版3DES加解密类
*
* 可与java的3DES(DESede)加密方式兼容
*
*/

class Crypt3Des {

	private $key;
	private $iv; //like java: private static byte[] myIV = { 50, 51, 52, 53, 54, 55, 56, 57 };

	public function __set($property_name, $value) {
		$this->$property_name = $value;
	}

	//加密
	public function encrypt($input) {
		$input = $this->PaddingPKCS7( $input );
		//$key = base64_decode($this->key);
		$key = $this->key;
		$td = mcrypt_module_open( MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');

		//使用MCRYPT_3DES算法,cbc模式
		mcrypt_generic_init($td, $key, $this->iv);

		//初始处理
		$data = mcrypt_generic($td, $input);

		//加密
		mcrypt_generic_deinit($td);

		//结束
		mcrypt_module_close($td);
		//$data = $this->removeBR(base64_encode($data));
		$data = $this->removeBR($data);
		return $data;

	}

	//解密
	public function decrypt($encrypted) {
		//$encrypted = base64_decode($encrypted);
		//$key = base64_decode($this->key);
		$key = $this->key;
		$td = mcrypt_module_open( MCRYPT_3DES,'',MCRYPT_MODE_CBC,'');

		//使用MCRYPT_3DES算法,cbc模式
		mcrypt_generic_init($td, $key, $this->iv);

		//初始处理
		$decrypted = mdecrypt_generic($td, $encrypted);

		//解密
		mcrypt_generic_deinit($td);

		//结束
		mcrypt_module_close($td);
		$decrypted = $this->UnPaddingPKCS7($decrypted);
		return $decrypted;

	}

	//填充密码，PKCS7填充
	private function PaddingPKCS7($data) {
		$block_size = mcrypt_get_block_size('tripledes', 'cbc');
		$padding_char = $block_size - (strlen($data) % $block_size);
		$data .= str_repeat(chr($padding_char), $padding_char);
		return $data;
	}

	//删除填充符
	private function UnPaddingPKCS7($text) {
			$pad = ord($text{strlen($text) - 1});
			if ($pad > strlen($text)) {
				return false;
			}
			if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
				return false;
			}
			return substr($text, 0, - 1 * $pad);
		}

	//删除回车和换行
	public function removeBR( $str ) {
		$len = strlen( $str );
		$newstr = "";
		$str = str_split($str);
		for ($i = 0; $i < $len; $i++ ) {
			if ($str[$i] != '\n' and $str[$i] != '\r') {
				$newstr .= $str[$i];
			}
		}

		return $newstr;
	}

}
