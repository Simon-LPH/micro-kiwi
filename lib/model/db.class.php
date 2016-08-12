<?php
if(!defined('IN_KIWI')) exit('Access Denied');

class db
{
	protected $pconnect = false;
	private $connected;
	private $_linkID;
	private $queryID = null;
	private $num_count = 0;
	private $query_times = 0;
	private $error;
	private $errno = 0;
	private $trans_running = false;

	/**
     +----------------------------------------------------------
	 + 新建一个数据库连接
	 + @param mixed $config 数据库服务器配置信息
	 + @param int $linkNum 连接序号
     +----------------------------------------------------------
	*/
	public function connect($config) {
		if(! $this->connected ){
			if(empty($config) || !is_array($config)){
				exit('Database connect error:config is empty.');
			}
			// 处理不带端口号的socket连接情况
			$host = $config['hostname'].(!empty($config['hostport']) && $config['hostport']!=3306?":{$config['hostport']}":'');
			if($this->pconnect){
				$this->_linkID = mysql_pconnect( $host, $config['username'], $config['password']);
			}
			else{
				$this->_linkID = mysql_connect( $host, $config['username'], $config['password']);
			}
			if(!$this->_linkID || (!empty($config['database']) && !$this->select_db($config['database'])) ) {
				$this->errorlog("Cannot connect to mysql!".$this->error(), __FILE__.' on line '.__LINE__);
				exit("Datebase connect error.\r\n");
			}
			mysql_query("SET NAMES 'utf8'", $this->_linkID);
			//设置 sql_model
			mysql_query("SET sql_mode=''", $this->_linkID);
			// 标记连接成功
			$this->connected = 1;
		}
		return $this->_linkID;
	}
	
	/*
	 + 同服务器上的数据库切换
	*/
	public function select_db($dbname){
		return mysql_select_db($dbname, $this->_linkID);
	}
	
	/*
	 + 执行sql语句
	*/
	public function query($query, $ignore_err=false){
		if(!empty($query)){
			$this->queryID = mysql_query($query, $this->_linkID);
			if(empty($this->queryID)) $this->errorlog($this->error(), '("'.$query.'")'.__FILE__.' on line '.__LINE__, $ignore_err);
			$this->query_times++;
			$this->num_count = 0;
		}
		return $this->queryID;
	}
	
	/*
	 + query执行次数
	*/
	public function sql_runtimes(){
		return $this->query_times;
	}
	
	/*
	 + 返回更新的行数
	*/
	public function affected_rows(){
		return mysql_affected_rows($this->_linkID);
	}
	
	/*
	 + 最后插入行的主键
	*/
	public function insert_id(){
		return mysql_insert_id();
	}
	
	/*
	 + 特殊字符过滤
	*/
	public function real_escape_string($string){
		$string = mysql_real_escape_string($string);
		return $string;
	}
	
	/*
	 + 返回单行结果
	*/
	public function fetch_row(){
		if(is_resource($this->queryID)){
			return mysql_fetch_row($this->queryID);
		}
		return null;
	}
	
	/*
	 + 返回所有结果集
	*/
	public function fetch_row_all(){
		$rows=array();
		if(is_resource($this->queryID)){
			while($arr=mysql_fetch_row($this->queryID)){
				$rows[] = $arr;
			}
		}
		return $rows;
	}
	
	/*
	 + 返回单行结果
	*/
	public function fetch_assoc(){
		if(is_resource($this->queryID)){
			return mysql_fetch_assoc($this->queryID);
		}
		return null;
	}
	
	/*
	 + 返回所有结果集
	*/
	public function fetch_assoc_all(){
		$rows=array();
		if(is_resource($this->queryID)){
			while($arr=mysql_fetch_assoc($this->queryID)){
				$rows[] = $arr;
			}
		}
		return $rows;
	}
	
	/*
	 + 返回单行结果
	*/
	public function fetch_array(){
		if(is_resource($this->queryID)){
			return mysql_fetch_array($this->queryID);
		}
		return false;
	}
	
	/*
	 + 返回所有结果集
	*/
	public function fetch_array_all(){
		$rows=array();
		if(is_resource($this->queryID)){
			while($arr=mysql_fetch_array($this->queryID)){
				$rows[] = $arr;
			}
		}
		return $rows;
	}
	
	/*
	 + 返回字段数量
	*/
	public function fileds_num(){
		if(!empty($this->queryID)){
			return mysql_num_fields($this->queryID);
		}
		return 0;
	}
	
	/*
	 + 返回字段名称
	*/
	public function fileds_name($i){
		if(!empty($this->queryID)){
			return mysql_field_name($this->queryID, $i);
		}
		return false;
	}
	
	/*
	 + 返回结果总数
	*/
	public function get_num_count(){
		if(empty($this->num_count)){
			if($myrow = mysql_num_rows($this->queryID))
				$this->num_count = $myrow;
		}
		return $this->num_count;
	}
	
	/*
	 + 计算当前结果分页页数
	*/
	public function get_page_count($pagelen, $numcount=''){
		if($pagelen){
			if(empty($numcount)){
				$numcount = $this->get_num_count();
			}
			$pages = ceil($numcount/$pagelen);
			return $pages;
		}
		return false;
	}
	
	/*
	 + 根据字段及索引取出单个结果
	*/
	public function result($inx, $field){
		if($this->get_num_count()){
			$result = mysql_result($this->queryID, $inx, $field);
			$result!==false or $this->errorlog('Get result error.', __FILE__.' on line '.__LINE__);
			return $result;
		}
		return false;
	}
	
	/*
	 + 取出结果的第一行
	*/
	public function firstrow($field){
		return $this->result(0,$field);
	}
	
	/*
	 + 取出结果的最后一行
	*/
	public function lastrow($field){
		$num_count = $this->get_num_count();
		return $this->result(($num_count)-1,$field);
	}
	
	/*
	 + 错误记录
	*/
	public function errorlog($message, $fileline='', $ignore_err=false){
		if(!$ignore_err && !$this->trans_running){
			$this->errno();
			if(!$this->errno) $this->errno=-1;
			$this->close();
		}
	}
	
	/*
	 + 返回当前的错误代码
	*/
	public function errno($force=false){
		if(!$this->errno || $force){
			$this->errno = mysql_errno();
		}
		return $this->errno;
	}
	
	/*
	 + 返回当前的错误信息
	*/
	public function error($force=false){
		if(!$this->error || $force){
			$this->error = !empty($this->_linkID) ? mysql_errno().':'.mysql_error($this->_linkID) : mysql_errno().':'.mysql_error();
		}
		return $this->error;
	}
	
	/*
	 + 设定是否自动提交
	*/
	public function autocommit($auto=true){
		if($auto){
			$this->query('set autocommit=1');
		}
		else{
			$this->query('set autocommit=0');
		}
		return $this->errno==0;
	}
	
	/*
	 + 开始事务
	*/
	public function startTrans(){
		if($this->trans_running) return true;
		$this->initConnect(true);
		$this->trans_running=true;
		return $this->autocommit(false);
	}
	
	/*
	 + 停止事务
	*/
	public function stopTrans(){
		if(!$this->trans_running) return true;
		$this->masterConnect = null;
		$this->trans_running=false;
		return $this->autocommit(true);
	}
	
	/*
	 + 非自动提交状态下的回滚操作
	*/
	public function rollback(){
		if($this->trans_running==false) return false;
		$this->query('ROLLBACK');
		$this->stopTrans();
		return $this->errno==0;
	}
	
	/*
	 + 非自动提交状态下的提交操作
	*/
	public function commit(){
		if($this->trans_running==false) return false;
		$this->query('COMMIT');
		$this->stopTrans();
		return $this->errno==0;
	}
	
	/*
	 + 自动检测当失败时回滚
	*/
	public function roll_back_when_failed(){
		if($this->affected_rows()<=0){
			$this->rollback();
			return $this->errno ? $this->errno : -1;
		}
		return 0;
	}
	
	/*
	 + 释放查询结果集资源
	*/
	public function free(){
		if(is_resource($this->queryID)){
			mysql_free_result($this->queryID);
			$this->queryID = null;
		}
	}
	
	/*
	 + 关闭当前连接
	*/
	public function close(){
		if($this->connected && $this->_linkID){
			mysql_close($this->_linkID);
			$this->_linkID = null;
		}
	}

}
?>