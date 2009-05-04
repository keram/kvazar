<?php


class Quiz extends Control
{
	/** @var DibiDataSource */
	protected $dataSource;
	
	public $id, $question, $presenter;

	/** @var bool */
	public $useAjax = TRUE;


	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Renders quiz.
	 */
	public function render()
	{
		$template = $this->createTemplate();
		$template->question = $this->question;
		
		$dataSource = $this->dataSource;
		// render
		$template->useAjax = $this->useAjax;
		$template->setFile(dirname(__FILE__) . '/quiz.phtml');
		$template->registerFilter('Nette\Templates\CurlyBracketsFilter::invoke');
		$template->render();
	}

}
