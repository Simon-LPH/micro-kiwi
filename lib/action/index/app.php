<?php
class AppAction extends Action{

	public function appInit(){
		$this->setPageTitle('Micro-kiwi');

	}

	public function setPageTitle($text){
		$this->assign('page_title', $text);
	}

	public function setPageKeyword($text){
		$this->assign('page_keyword', $text);
	}

	public function setPageDescription($text){
		$this->assign('page_description', $text);
	}

	public function setPageAuthor($text){
		$this->assign('page_author', $text);
	}
}
