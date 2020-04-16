<?php
/**
 * des 加密
 * 易城对接使用 
 * @author chao
 *
 */
class desEncry
{
	private $key;
	private $iv; //偏移量

	public function __construct($key, $iv=0) {
		$this->key = $key;
		if( $iv == 0 ) {
			$this->iv = $key; 
		} else {
			$this->iv = $iv; 
		}
	}
	/**
	 * 加密
	 */
	public function encrypt($str) {
		$size = mcrypt_get_block_size ( MCRYPT_DES, MCRYPT_MODE_ECB );
		$str = $this->pkcs5Pad ( $str, $size );
		$td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
		@mcrypt_generic_init($td, $this->key, $this->iv);
		$data = bin2hex(mcrypt_generic($td, $str));
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return $data;
	}
	/**
	 * 解密
	 */
	public function decrypt($str) {
		$td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
		mcrypt_generic_init($td, $this->key, $this->iv);
		$strBan = $this->hex2bin(strtolower($str));
		$ret = mdecrypt_generic($td, $strBan);

		$ret = $this->pkcs5Unpad($ret);
	
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return $ret;
	}

	private function hex2bin($hexData) {
		$binData = "";
		for($i = 0; $i < strlen ( $hexData ); $i += 2) {
			$binData .= chr ( hexdec ( substr ( $hexData, $i, 2 ) ) );
		}
		return $binData;
	}

	private function pkcs5Pad($text, $blocksize) {
		$pad = $blocksize - (strlen ( $text ) % $blocksize);
		return $text . str_repeat ( chr ( $pad ), $pad );
	}

	private function pkcs5Unpad($text) {
		$pad = ord ( $text {strlen ( $text ) - 1} );
		if ($pad > strlen ( $text ))
			return false;
		if (strspn ( $text, chr ( $pad ), strlen ( $text ) - $pad ) != $pad)
			return false;
		return substr ( $text, 0, - 1 * $pad );
	}

}