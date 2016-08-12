<?php
if(!defined('IN_KIWI')) exit('Access Denied');
require_once SYS_ROOT."lib/model/db.class.php";

class Model extends db{

	protected $table;
	
	public function __construct(){
		$this->table = strtolower(get_class($this));
		$this->table = rtrim($this->table, 'model');

		$this->connect([
			'hostname'=> DB_HOST,
			'hostport'=> DB_PORT,
			'username'=> DB_USER,
			'password'=> DB_PASS,
			'database'=> DB_NAME
		]);
	}


	/**
	 +----------------------------------------------------------
	 * 插入记录
	 +----------------------------------------------------------
	 * @param mixed $values 必须
	 +----------------------------------------------------------
	 * @return int
	 +----------------------------------------------------------
	*/
	public function create($values){
		if(empty($values)){
			return false;
		}

		$sentence = 'insert into '.$this->table;
		if(is_array($values)){
			$fsql = '(';
			$vsql = ' values(';
			foreach($values as $k=>$v){
				if(is_scalar($v)){
					$this->parseValue($v);
					$fsql .= $k.',';
					$vsql .= $v.',';
				}
			}
			$fsql = trim($fsql, ',');
			$vsql = trim($vsql, ',');
			$fsql .= ')';
			$vsql .= ')';
			$sentence .= $fsql.$vsql;
		}
		else{
			$sentence .= $values;
		}
		$this->query($sentence);

		return $this->affected_rows();
	}


	/**
	 +----------------------------------------------------------
	 * 删除记录
	 +----------------------------------------------------------
	 * @param string $condition 必须
	 +----------------------------------------------------------
	 * @return int
	 +----------------------------------------------------------
	*/
	public function delete($condition){
		$this->query("delete from {$this->table} where $condition");

		return $this->affected_rows();
	}

	/**
	 +----------------------------------------------------------
	 * 查询单条数据
	 +----------------------------------------------------------
	 * @param string $condition 必须
	 +----------------------------------------------------------
	 * @return array data
	 +----------------------------------------------------------
	*/
	public function select($condition, $field = '*'){
		$this->query("select $field from {$this->table} where $condition");

		return $this->fetch_assoc();
	}

	/**
	 +----------------------------------------------------------
	 * 查询多条数据
	 +----------------------------------------------------------
	 * @param string $condition 必须
	 +----------------------------------------------------------
	 * @return array data
	 +----------------------------------------------------------
	*/
	public function selectAll($condition, $field = '*'){
		$this->query("select $field from {$this->table} where $condition");

		return $this->fetch_assoc_all();
	}

	/**
	 +----------------------------------------------------------
	 * 更新数据
	 +----------------------------------------------------------
	 * @param mixed $values 必须
	 +----------------------------------------------------------
	 * @param string $condition 必须
	 +----------------------------------------------------------
	 * @return int
	 +----------------------------------------------------------
	*/
	public function update($values, $condition){
		if(empty($values) || empty($condition)){
			return false;
		}

		$sentence = 'update '.$this->table.' set ';

		if(is_array($values)){
			$vsql = '';
			foreach($values as $k=>$v){
				$this->parseValue($v);
				$vsql .= $k."=".$v.",";
			}
			$vsql = substr($vsql, 0, -1);
			$sentence .= $vsql;
		}
		else{
			$sentence .= $values;
		}

		$sentence .= " where $condition";
		$this->query($sentence);

		return $this->affected_rows();
	}
	
	/**
	 +----------------------------------------------------------
	 * value分析
	 +----------------------------------------------------------
	 * @param mixed $value 必须
	 +----------------------------------------------------------
	 * @return string
	 +----------------------------------------------------------
	*/
	protected function parseValue(&$value) {
		if(is_string($value)) {
			$value = '\''.$this->real_escape_string($value).'\'';
		}
		elseif(isset($value[1]) && strtoupper($value[1]) === 'EXP'){
			$value   =  $this->real_escape_string($value[0]);
		}
		elseif(is_null($value)){
			$value   =  'null';
		}
		return $value;
	}

}
?>