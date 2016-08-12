<?php
if(!defined('IN_KIWI')) exit('Access Denied');

define('DEGUG', true);
define('SYS_ROOT', dirname(__FILE__).'/');
define('ACTION_PATH', SYS_ROOT.'lib/action/');
define('MODEL_PATH', SYS_ROOT.'lib/model/');
define('TOOL_PATH', SYS_ROOT.'lib/tools/');
define('TEMPLATE_PATH', SYS_ROOT.'template/');
define('SITE_URL', 'http://'.$_SERVER['HTTP_HOST'].'/');
define('WEIXIN_OAUTH_CALLBACK', '');
define('WEIXIN_OAUTH_STATE', 'wx_state');

require_once SYS_ROOT."config.php";
require_once SYS_ROOT."lib/action/action.class.php";
require_once SYS_ROOT."lib/model/model.class.php";

function GET($p, $function = '', $default = ''){
	$v = isset($_GET[$p]) ? $_GET[$p] : $default;
	
	if($function){
		if( function_exists($function) ){
			$v = $function($v);
		}
		else{
			exit("Filter Function:$function Not Exists.");
		}
	}
	return $v;
}

function POST($p, $function = '', $default = ''){
	$v = isset($_POST[$p]) ? $_POST[$p] : $default;
	if($function){
		if( function_exists($function) ){
			$v = $function($v);
		}
		else{
			exit("Filter Function:$function Not Exists.");
		}
	}
	return $v;
}

function gettimestamp($day){
	return time() + $day * 86400;
}

function URL($src, $cur_module = '', $cross = false){
	if($cross){
		if(strpos($src,'/') !== false){
			$temp = explode('/', $src);
			$module = array_shift($temp);
			$src = implode('/',$temp);
		}
		else{
			$module = $src;
			$src = '';
		}
	}
	else{
		$module = $cur_module;
	}
	
	if( isset(CONFIG::$MODULE_SITES[$module]) ){
		$url = CONFIG::$MODULE_SITES[$module].$src;
	}
	else{
		$url = SITE_URL.$module.'/'.$src;
	}

	$url = trim($url);
	$url = trim($url,'/');

	return $url;
}


function getTplContent($src, $module){
	if(!is_file($src)){
		return '';
	}

	$str = file_get_contents($src);

	$str = preg_replace("/{{include (.*?)}}/ie", "getTplContent(TEMPLATE_PATH.\"$module/$1\", \"$module\")", $str);

	$str = preg_replace("/{{url (.*?)}}/i", "<?php echo URL(\"$1\",\"$module\")?>", $str);
	$str = preg_replace("/{{cross_url (.*?)}}/i", "<?php echo URL(\"$1\",\"$module\",true)?>", $str);
	
	$str = preg_replace("/{{([^\s]*?)}}/i", "<?php echo $1?>", $str);
	$str = preg_replace("/{{(.*?)}}/i", "<?php $1?>", $str);

	return $str;
}

function loadTpl($file, $data = [], $module = ''){
	$src = TEMPLATE_PATH."{$file}.html";
	$tpl = SYS_ROOT."runtime/tpl/{$file}.html.php";

	if(DEGUG == true || !file_exists($tpl) || filemtime($src) > filemtime($tpl)){
		$str = getTplContent($src, $module);
		file_put_contents($tpl, $str);
	}

	extract($data);

	include $tpl;
}

function loadModel($name, $module = ''){
	if(is_file(MODEL_PATH.$module.$name.'.class.php')){
		require_once MODEL_PATH.$module.$name.'.class.php';
		return true;
	}
}

function loadTool($name){
	if(is_file(TOOL_PATH.$name)){
		require_once TOOL_PATH.$name;
		return true;
	}
}