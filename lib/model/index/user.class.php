<?php

class UserModel extends Model{

	private $current_uid;
	private $current_info = [];

	public function create($user_name, $openid, $gender, $headimg, $result){

		return parent::create([
			'user_name'=>$user_name,
			'openid'=>$openid,
			'gender'=>$gender,
			'headimg'=>$headimg,
			'result'=>$result
		]);
	}
	
	/*
	+ 登录检测
	*/
	public function loginCheck($user_name, $pwd){

	}

	public function id(){
		return $this->current_uid;
	}

	public function info(){
		return $this->current_info;
	}

}
?>