<?php
if(!defined('IN_KIWI')) exit('Access Denied');

class WXOAuth
{

	/*
	+ 获取微信验证URL
	*/
	public static function getAuthURL($redirect, $force = false){
		if($force){
			return SITE_URL."oauth&force=1&re=".urlencode($redirect);
		}
		else{
			return SITE_URL."oauth&re=".urlencode($redirect);
		}
	}
	
	/*
	+ 验证跳转微信链接
	*/
	public static function getRedirectUrl($redirect = ''){
		
		$callback_url = WEIXIN_OAUTH_CALLBACK.$redirect;
		
		//微信oauth验证
		$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".APP_ID.
			"&redirect_uri=".urlencode($callback_url).
			"&response_type=code&scope=snsapi_userinfo&state=".WEIXIN_OAUTH_STATE."#wechat_redirect";
		return $url;
	}
}
?>