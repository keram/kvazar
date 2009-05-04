<?php


class Quiz extends Control
{
	/** @var DibiDataSource */
	protected $dataSource;
	
	public $id;

	/** @var bool */
	public $useAjax = TRUE;


	public function __construct()
	{
		parent::__construct();
	}

	public function bindDataTable(DibiDataSource $dataSource)
	{
		$this->dataSource = $dataSource;
	}
	
	private function getQuestion ()
	{
		$question = false;
		$src = dibi::getConnection()->dataSource('SELECT t1.id, t1.title_sk, t1.title_en, t2.id AS `answer_id`, t2.correct AS `answer_correct`, t2.value AS `answer_value`,  COUNT(t2.id) AS `answers_count` FROM `question` AS t1 LEFT JOIN `answer` AS t2 ON t1.id = t2.question_id WHERE t1.id NOT IN ( SELECT t3.question_id FROM `quiz_has_question` AS t3 WHERE t3.open = 0 ) GROUP BY t1.id ORDER BY id ASC LIMIT 1');
		// $src = dibi::getConnection()->dataSource('SELECT t1.id, t1.title_sk, t1.title_en, t2.id AS `answer_id`, t2.correct AS `answer_correct`, t2.value AS `answer_value`,  COUNT(t2.id) AS `answers_count` FROM `question` AS t1 LEFT JOIN `answer` AS t2 ON t1.id = t2.question_id WHERE t1.id NOT IN ( SELECT t3.question_id FROM `quiz_has_question` AS t3 WHERE t3.open = 0 ) GROUP BY t1.id ORDER BY RAND() ASC LIMIT 1');
		// $src = dibi::getConnection()->dataSource('SELECT t1.id, concat(t1.title_sk, " / ", t1.title_en) AS `question`, t2.id AS answer_id, t2.correct, t2.value,  COUNT(t2.id) AS `answers` FROM `question` AS t1 LEFT JOIN `answer` AS t2 ON t1.id = t2.question_id WHERE t1.id NOT IN ( SELECT t3.question_id FROM `quiz_has_question` AS t3 WHERE t3.open = 0 ) GROUP BY t1.id ORDER BY RAND() LIMIT 1');
		if ( $src->count() )
		{
			// todo fix
			require_once (dirname(__FILE__) . '/Question.php');
			$question = new Question($src);

		}
		
		return $question;
	}
	
	private function getRanks ()
	{
		# code...
	}

	/**
	 * Renders table grid.
	 */
	public function renderQuestion()
	{
		$dataSource = $this->dataSource;
		// render
		$template->useAjax = $this->useAjax;
		$template->setFile(dirname(__FILE__) . '/question.phtml');
		$template->registerFilter('Nette\Templates\CurlyBracketsFilter::invoke');
		$template->render();
	}
	
	
	/**
	 * Renders quiz.
	 */
	public function render()
	{
		$template = $this->createTemplate();
		$question = $this->getQuestion();
		$template->question = $question;
		
		$dataSource = $this->dataSource;
		// render
		$template->useAjax = $this->useAjax;
		$template->setFile(dirname(__FILE__) . '/quiz.phtml');
		$template->registerFilter('Nette\Templates\CurlyBracketsFilter::invoke');
		$template->render();
	}

}
