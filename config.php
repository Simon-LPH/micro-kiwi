<?php

class CONFIG {
	public static $MODULE_SITES = [];
}

/*
数据库配置
*/
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'DB_USER');
define('DB_PASS', 'DB_PASS');
define('DB_NAME', 'DB_NAME');

/*
+ 设置独立模块域名
*/
CONFIG::$MODULE_SITES = [
	'index' => 'http://'.$_SERVER['HTTP_HOST'].'/index/'
];
