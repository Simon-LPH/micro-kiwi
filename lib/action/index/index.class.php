<?php

class IndexAction extends AppAction{

	private $oUser;
	private $wx_checked = false;

	public function init($params){
	}

	public function index($params){

		$this->Gui([
			'user_name' => 'Simon'
		]);
	}

	public function show(){
		$this->redirect('http://d.eqxiu.com/s/xRoJM9HT');
	}

}

?>