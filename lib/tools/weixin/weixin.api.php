<?php
if(!defined('IN_KIWI')) exit('Access Denied');

class WeixinConn 
{
	
	private $data=array();
	private $toUserName;
	private $fromUserName;
	private $createTime;
	private $msgType;
	private $msgId;
	private $appid;
	private $appsecret;
	private $access_token;
	private $access_token_expires;
	public $errcode=0;
	public $errmsg;
	
	public function __construct($appid='', $appsecret=''){
		$this->appid = $appid;
		$this->appsecret = $appsecret;
	}
	
	/*
	+ 验证签名
	*/
	public function checkSignature($token){
		$signature = isset($_GET["signature"]) ? $_GET["signature"]:'';
		$timestamp = isset($_GET["timestamp"]) ? $_GET["timestamp"]:'';
		$nonce =  isset($_GET["nonce"]) ? $_GET["nonce"]:'';	

		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
	
	/*
	+ 解析接收到的xml文本
	*/
	public function recieveXml($xmlstring){
		$data = array();
		$oXml = new SimpleXMLElement($xmlstring);
		if(!$oXml)
			return false;
		foreach($oXml->children() as $name=>$child){
			$data[$name] = $child;
		}
		if(isset($data['MsgType'])){
			$this->data = $data;
			$this->toUserName = $data['ToUserName'];
			$this->fromUserName = $data['FromUserName'];
			$this->creatTime = $data['CreateTime'];
			$this->msgType = $data['MsgType'];
			if(isset($data['MsgId'])) $this->msgId = $data['MsgId'];
		}
		return $data;
	}
	
	/*
	+ 生成回复信息的xml文本
	*/
	public function responseXml($data, $print = false){
		if(!isset($data['MsgType']))
			return null;
		$string = '<xml>';
		$string .= $this->createXml($data);
		$string .= '</xml>';
		if($print) echo $string;
		return $string;
	}
	
	/*
	+ 生成Xml文本内容
	*/
	protected function createXml($data){
		$string = '';
		foreach($data as $name=>$value){
			if(is_numeric($name)){
				$string .= "<item>";
			}
			else{
				$string .= "<".$name.">";
			}
			
			if(is_array($value)){
				$string .= $this->createXml($value);
			}
			elseif(is_numeric($value)){
				$string .= $value;
			}
			else{
				$string .= "<![CDATA[".$value."]]>";
			}
			
			if(is_numeric($name)){
				$string .= "</item>";
			}
			else{
				$string .= "</".$name.">";
			}
		}
		return $string;
	}
	
	public function msgType($strval = true){
		if($strval)
			return strval($this->msgType);
		else
			return $this->msgType;
	}
	
	public function msgId(){
		return floor(floatval($this->msgId));
	}
	
	public function createTime(){
		return intval($this->createTime);
	}
	
	public function toUserName($strval = true){
		if($strval)
			return strval($this->toUserName);
		else
			return $this->toUserName;
	}
	
	public function fromUserName($strval = true){
		if($strval)
			return strval($this->fromUserName);
		else
			return $this->fromUserName;
	}
	
	public function accessToken(){
		return $this->access_token;
	}
	
	public function accessTokenExpires(){
		return $this->access_token_expires;
	}
	
	/*
	+ 从服务器获取新的Access Token
	*/
	public function refreshAccessToken(){
		if(!$this->appid || !$this->appsecret) return false;
		$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->appsecret;
		$content = $this->request($url,'',true);
		$json = @json_decode($content, true);
		if(!empty($json['access_token'])){
			$this->access_token = $json['access_token'];
			$this->access_token_expires = time() + intval($json['expires_in']*0.8);
		}
		else{
			$this->errcode = $json['errcode'];
			$this->errmsg = $json['errmsg'];
		}
		return $json;
	}
	
	/*
	+ 从服务器获取使用oAuth的Access Token
	*/
	public function getOauthAccessToken($code){
		if(!$this->appid || !$this->appsecret) return false;

		$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->appid.'&secret='.$this->appsecret.'&code='.$code.'&grant_type=authorization_code';
		$content = $this->request($url,'',true);
		$json = @json_decode($content, true);
		if(empty($json['access_token'])){
			$this->errcode = $json['errcode'];
			$this->errmsg = $json['errmsg'];
		}
		return $json;
	}
	
	/*
	+ 创建自定义菜单
	*/
	public function createCustomMenu($access_token, $data){
		$json = $this->getService('menu/create', $access_token, $data);
		return isset($json['errcode']) && $json['errcode']==0;
	}
	
	/*
	+ 查询自定义菜单
	*/
	public function getCustomMenu($access_token){
		return $this->getService('menu/get', $access_token);
	}
	
	/*
	+ 删除自定义菜单
	*/
	public function deleteCustomMenu($access_token){
		$json = $this->getService('menu/delete', $access_token);
		return isset($json['errcode']) && $json['errcode']==0;
	}
	
	/*
	+ 获取用户基本信息
	*/
	public function getUserInfo($access_token, $openid){
		$url = "http://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
		$rs = $this->request($url);
		if(!empty($rs)){
			$rs = @json_decode($rs, true);
			if(isset($rs['errcode'])){
				$this->errcode = $rs['errcode'];
				$this->errmsg = $rs['errmsg'];
			}
		}
		return $rs;
	}

	/*
	+ 通过oauth获取用户基本信息
	*/
	public function getUserInfoAuth($access_token, $openid){
		$url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
		$rs = $this->request($url,'',true);
		if(!empty($rs)){
			$rs = @json_decode($rs, true);
			if(isset($rs['errcode'])){
				$this->errcode = $rs['errcode'];
				$this->errmsg = $rs['errmsg'];
			}
		}
		return $rs;
	}
	
	/*
	+ 获取jsapi ticket
	*/
	public function getJsapiTicket($access_token){
		return $this->getService('ticket/getticket', $access_token.'&type=jsapi');
	}
	
	//主动发送文本消息
	public function sendTextMsg($touser, $content, $access_token){
		$postdata = array("touser"=>$touser,"msgtype"=>"text","text"=>array("content"=>urlencode($content)));
		$postdata = urldecode(json_encode($postdata));
		$json = $this->getService('message/custom/send', $access_token, $postdata);
		return isset($json['errcode']) && $json['errcode']==0;
	}
	
	//主动发送图文消息
	public function sendNewsMsg($touser, $content, $access_token){
		$postdata = array("touser"=>$touser,"msgtype"=>"news","news"=>array("articles"=>$content));
		$postdata = urldecode(json_encode($postdata));
		$json = $this->getService('message/custom/send', $access_token, $postdata);
		return isset($json['errcode']) && $json['errcode']==0;
	}
	
	private function getService($group_action, $access_token, $postdata=''){
		if(!$group_action || !$access_token) return false;
		$url = 'https://api.weixin.qq.com/cgi-bin/'.$group_action.'?access_token='.$access_token;
		$content = $this->request($url, $postdata, true);
		$json = !empty($content) ? @json_decode($content, true):array();
		if(isset($json['errcode'])){
			$this->errcode = $json['errcode'];
			$this->errmsg = $json['errmsg'];
		}
		return $json;
	}
	
	private function request($url, $postdata='', $https=false){
		$header=array();
		$header[]='Accept: */*';
		$header[]='User-Agent:Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36';
		$header[]='Connection: Keep-Alive';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if($https){
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 25);
		if(!empty($postdata)){
			curl_setopt($ch, CURLOPT_POST, 1); // 发送一个常规的Post请求
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata); // Post提交的数据包
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$contents = curl_exec($ch);
		curl_close($ch);
		return $contents;
	}
}
?>