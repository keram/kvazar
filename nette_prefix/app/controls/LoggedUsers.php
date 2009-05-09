<?php

class LoggedUsers extends Control
{
	/** @var bool */
	public $useAjax = TRUE;

	/** @var DibiDataSource */
	protected $dataSource;


	public function __construct()
	{
		parent::__construct();
	}

	public function bindDataTable(DibiDataSource $dataSource)
	{
		$this->dataSource = $dataSource;
	}


	/**
	 * Renders table grid.
	 */
	public function render()
	{
		// todo je to zvrhle 
		$user = Environment::getUser();
		if ( $user->isAuthenticated() ) {
			$dataSource = $this->dataSource;
			// render
			$template = $this->createTemplate();
			$template->rows    = $dataSource->getIterator();
			$template->columns = $dataSource->getResult()->getColumnNames();
			$template->useAjax = $this->useAjax;
	
			$template->setFile(dirname(__FILE__) . '/LoggedUsers.phtml');
			$template->registerFilter('Nette\Templates\CurlyBracketsFilter::invoke');
			$template->render();
		}
	}

}
