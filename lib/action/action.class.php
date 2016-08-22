<?php

class Action
{
	public $module;
	public $controller;
	public $action;
	public $assign_data = [];
	public $is_ajax = null;

	public static function run($params = []){

		if(count($params) == 0){
			$s = isset($_GET['s']) ? trim($_GET['s']) : '';
			$s = trim($s, '/');
			if(strpos($s, "/") !== false){
				$params = explode('/', $s);
			}
			elseif($s){
				$params = [$s];
			}
		}

		if(count($params) > 0){
			foreach($params as $k=>$v){
				$params[$k] = basename($v);
			}
		}

		$action_path = ACTION_PATH;

		if(count($params) == 0){
			$module = '';
			$controller = 'index';
			$action = 'index';
			if(!is_file($action_path.'index.class.php') && is_dir($action_path.'index')){
				$module = 'index';
				$action_path = $action_path.$module.'/';
			}
		}
		else{

			if(is_file($action_path.$params[0].'.class.php')){
				$module = '';
				$controller = array_shift($params);
			}
			elseif(is_dir($action_path.$params[0])){
				$module = array_shift($params);
				$action_path = $action_path.$module.'/';

				if(count($params) > 0){
					$controller = array_shift($params);
				}
				else{
					$controller = 'index';
				}
			}
			else{
				$controller = 'index';
			}

			if(count($params) > 0){
				$action = array_shift($params);
			}
			else{
				$action = 'index';
			}

		}

		if(is_file($action_path.'app.php')){
			include $action_path.'app.php';
		}

		if(!is_file($action_path.$controller.'.class.php')){
			$o = new Action();
			$o->notfound($params);
		}

		include $action_path.$controller.'.class.php';

		$classname = ucfirst($controller).'Action';
		$o = new $classname();

		$o->module = $module;
		$o->controller = $controller;
		$o->action = $action;

		if(method_exists($o, 'appInit')) {
			$o->appInit();
		}

		if(method_exists($o, $action) && $action != 'run') {
			$o->init($params);
			$o->$action($params);
		}
		else{
			$o->notfound($params);
		}
	}

	/*
	+ 404
	*/
	public function notfound($params){
		header('Http/1.0 404 Not Found');

		$this->printContent('<h1>404 Not Found</h1>');
		exit;
	}

	/*
	+ 输出文本
	*/
	protected function printContent($string, $mobile = true){
		header('content-type:text/html; charset=utf-8');
		echo "<html>";
		if($mobile){
			echo '
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />';
		}
		echo $string;
		echo "</html>";
	}

	/*
	+ 输出Json
	*/
	protected function jsonReturn($data = []){
		header('content-type:text/html; charset=utf-8');

		if(!isset($data['success'])){
			$data['success'] = 0;
		}

		json_encode($data);
		exit;
	}

	/*
	+ 输出页面
	*/
	protected function Gui($data = [], $file = ''){
		header('content-type:text/html; charset=utf-8');

		if(!$file){
			$file = '';
			if($this->controller != 'index'){
				$file .= $this->controller.'.';
			}
			$file .= $this->action;
		}
		
		if(strpos($file, "/") ===  false){
			$file = $this->module."/".$file;
		}
		$data = array_merge($data, $this->assign_data);
		loadTpl($file, $data, $this->module);
	}
	
	/*
	+ 以文件方式输出（即下载）
	+ @param: $content 可以是文本内容也可以是二进制内容
	*/
	public function download($basename, $content=''){
		$basename = basename($basename);
		header("Content-Type:application/octet-stream");
		header("Content-Disposition: attachment; filename={$basename}");
		header("Pragma: no-cache");
		header("Expires: 0");
		if($content){
			print($content);
			exit;
		}
	}
	
	/*
	+ 检测请求的方式是否ajax
	*/
	public function isAjax(){
		if($this->is_ajax === null){
			$this->is_ajax = false;
			if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
				'xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']))
				$this->is_ajax = 'ajax';
		}
		return $this->is_ajax;
	}

	/*
	+ 模板变量赋值
	*/
	protected function assign($k, $v){
		$this->assign_data[$k] = $v;
	}

	/*
	+ 加载模块
	*/
	protected function loadModel($name){
		if(strpos($name, '/')){
			$module = '';
		}
		else{
			$module = $this->module.'/';
		}
		return loadModel($name, $module);
	}

	/*
	+ 加载工具
	*/
	protected function loadTool($name){
		return loadTool($name);
	}

	/*
	+ 重定向
	*/
	protected function redirect($url){
		header("Location:$url");
		exit;
	}
}
?>