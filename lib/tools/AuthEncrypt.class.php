<?php
if(!defined('IN_KIWI')) exit('Access Denied');

class AuthEncrypt
{
	private static $EX_KEY = 'd7b39fd5fbbfa932c3cdbb2bdf0372e5';
	
	//一般权限签名
	public static function normal_auth($str){
		if(empty($str)) return '';
		$str=self::hmac_md5(self::$EX_KEY, $str);
		return $str;
	}
	
	public static function hmac_md5($key,$data){
		return self::ph_hash_hmac('md5', $data, $key);
	}
	
	public static function hmac_sha1($key,$data){
		return self::ph_hash_hmac('sha1', $data, $key);
	}
	
	public static function ph_hash_hmac($hashfunc, $data, $key){
		if(function_exists('hash_hmac')){
			return hash_hmac($hashfunc, $data, $key);
		}
		else{
			$b = 64; // byte length for sha1
			if (strlen($key) > $b) {
				$key = pack("H*",$hashfunc($key));
			}
			$key = str_pad($key, $b, chr(0x00));
			$ipad = str_pad('', $b, chr(0x36));
			$opad = str_pad('', $b, chr(0x5c));
			$k_ipad = $key ^ $ipad;
			$k_opad = $key ^ $opad;
			return $hashfunc($k_opad . pack("H*",$hashfunc($k_ipad . $data)));
		}
	}
	
	public static function shortCode($hex){
		$base32 = array (
			'a','b','c','d','e','f','g','h',
			'i','j','k','l','m','n','o','p',
			'q','r','s','t','u','v','w','x',
			'y','z','0','1','2','3','4','5',
			'6','7','8','9','A','B','C','D',
			'E','F','G','H','I','J','K','L',
			'M','N','O','P','Q','R','S','T',
			'U','V','W','X','Y','Z'
		);
		$hexLen = strlen($hex);
		$subHexLen = $hexLen / 8;
		$output = array();

		for($i = 0; $i < $subHexLen; $i++) {
			$subHex = substr ($hex, $i * 8, 8);
			$int = 0x3FFFFFFF & (1 * ('0x'.$subHex));
			$out = '';
			for ($j = 0; $j < 6; $j++) {
				$val = 0x0000003D & $int;
				$out .= $base32[$val];
				$int = $int >> 5;
			}
			$output[] = $out;
		}
		return $output;
	}
	
	public static function authEncode($string, $dkey='', $urlencode = false){
		return self::auth_code($string, 'ENCODE', $dkey.self::$EX_KEY, $urlencode);
	}
	
	public static function authDecode($string, $dkey='', $urlencode = false){
		return self::auth_code($string, 'DECODE', $dkey.self::$EX_KEY);
	}
	
	protected static function auth_code($string, $operation='DECODE', $dkey='', $urlencode=false){
		if(empty($string)) return '';
		$string = strval($string);
		$enkeylen=3;	//$enkeylen<=16
		$authkey = substr(md5($dkey),0,6);
		$result="";
		if($operation === 'ENCODE'){
			$time = microtime();
			$time = md5($time);
			$enkey1 = substr($time, 0, $enkeylen);
			$enkey2 = substr($time, -$enkeylen);
		}
		else{
			$string = base64_decode($string);
			$enkey1 = substr($string, 0, $enkeylen);
			$enkey2 = substr($string, -$enkeylen);
			$string = substr($string, $enkeylen, -$enkeylen);
		}
		$enkey = $enkey1.$enkey2;
		
		$sl_str = strlen($string);
		$sl_ek = strlen($enkey);
		$sl_ak = strlen($authkey);
		
		for($i=0; $i < $sl_str; $i++){
			$j = $i % $sl_ek;
			if($authkey){
				$k = $i % $sl_ak;
				$result .= chr(ord($string[$i])^ ord($enkey[$j])^ ord($authkey[$k]));
			}
			else{
				$result .= chr(ord($string[$i])^ ord($enkey[$j]));
			}
		}
		if($operation==='ENCODE'){
			$result = str_replace('=','',base64_encode($enkey1.$result.$enkey2));
			if($urlencode)
				$result = urlencode($result);
		}
		return $result;
	}
	
/**
* 公钥加密
*
* @param string 明文
* @param string 证书文件（.crt）
* @return string 密文（base64编码）
*/
	public static function publickey_encodeing($sourcestr, $fileName){
		$key_content = file_get_contents($fileName);
		$pubkeyid = openssl_get_publickey($key_content);
		if (openssl_public_encrypt($sourcestr, $crypttext, $pubkeyid)){
			return base64_encode("" . $crypttext);
		}
		return False;
	}
/**
* 私钥解密
*
* @param string 密文（base64编码）
* @param string 密钥文件（.pem）
* @param string 密文是否来源于JS的RSA加密
* @return string 明文
*/
	public static function privatekey_decodeing($crypttext, $fileName, $fromjs = FALSE){
		$key_content = file_get_contents($fileName);
		$prikeyid = openssl_get_privatekey($key_content);
		$crypttext = base64_decode($crypttext);
		$padding = $fromjs ? OPENSSL_NO_PADDING : OPENSSL_PKCS1_PADDING;
		if (openssl_private_decrypt($crypttext, $sourcestr, $prikeyid, $padding)){
			return $fromjs ? rtrim(strrev($sourcestr), "/0") : "".$sourcestr;
		}
		return FALSE;
	}
	
}
?>