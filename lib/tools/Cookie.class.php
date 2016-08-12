<?php
if(!defined('IN_KIWI')) exit('Access Denied');

class Cookie
{
	//对 cookie 值是否采用随机码对称加密
	private static $auth_code = true;
	
	//对称加密附加键值，在启用 auth_code 的情况下，修改会造成客户端原有cookie失效					
	const COOKIE_AUTH_CODE_KEY = 'gwef23Wf8^jEla5_';
	
	/*
	 + cookie 写入
	*/
	public static function write($key, $value, $expires=0, $path='', $domain=''){
		$config = Base::Conf();
		
		if(empty($path)){
			$path = $config['COOKIEPATH'];
		}
		if(empty($domain)){
			$domain = $config['COOKIEDOMAIN'];
		}
		if(!empty($value)){
			//序列化使其支持数组值
			$value = serialize($value);
			if(self::$auth_code){
				Base::_load_class("common/AuthEncrypt");
				$value = AuthEncrypt::authEncode($value, self::COOKIE_AUTH_CODE_KEY);
			}
		}
		else{
			$value='';
		}
		return setcookie($config['COOKIEPRE'].$key, $value, $expires, $path, $domain);
	}
	
	/*
	 + cookie 读取
	*/
	public static function read($key){
		$cookiepre = Base::Conf('COOKIEPRE');
		$key = $cookiepre.$key;
		$cookie=isset($_COOKIE[$key]) ? $_COOKIE[$key]:'';
		if(empty($cookie)) return '';
		if(self::$auth_code){
			Base::_load_class("common/AuthEncrypt");
			$cookie = AuthEncrypt::authDecode($cookie, self::COOKIE_AUTH_CODE_KEY);
		}
		//反序列化恢复原值结构
		$cookie = unserialize($cookie);
		return $cookie;
	}
	
	/*
	 + cookie 删除
	*/
	public static function delete($key, $path='', $domain=''){
		return self::write($key, '', time()-3600, $path, $domain);
	}
	
	/*
	 + cookie 键检查
	*/
	public static function key_exists($key){
		$cookiepre = Base::Conf('COOKIEPRE');
		return isset($_COOKIE[$cookiepre.$key]);
	}
}
?>