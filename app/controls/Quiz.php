<?php


require_once (dirname(__FILE__) . '/Question.php');

class Quiz extends Control
{
	/** @var DibiDataSource */
	protected $dataSource;
	
	public $id, $question, $presenter;

	/** @var bool */
	public $useAjax = TRUE;


	public function __construct($presenter)
	{
		$this->presenter = $presenter;
		parent::__construct();
	}

	public function bindDataTable(DibiDataSource $dataSource)
	{
		$data = $dataSource->fetchAll();
		$this->id = $data[0]->id;
		$src = dibi::getConnection()->dataSource('SELECT t1.question_id AS `id`, t1.open, t1.datetime_start, t1.time, t1.quiz_id AS `questions_count`, 
							t2.id AS `question_id`, t2.title_sk, t2.title_en, 
							t3.id AS `answer_id`, t3.correct AS `answer_correct`, t3.value AS `answer_value`, COUNT(t3.id) AS `answers_count` 
						 FROM `quiz_has_question` AS t1
							 LEFT JOIN `question` AS t2 ON t1.question_id = t2.id
							 LEFT JOIN `answer` AS t3 ON t2.id = t3.question_id
						 WHERE t1.quiz_id = %i GROUP BY t3.question_id', $this->id);
		if ( !$src->count() )
		{
			$src = dibi::getConnection()->dataSource('SELECT t1.id, t1.title_sk, t1.title_en, t2.id AS `answer_id`, t2.correct AS `answer_correct`, t2.value AS `answer_value`,  COUNT(t2.id) AS `answers_count` FROM `question` AS t1 LEFT JOIN `answer` AS t2 ON t1.id = t2.question_id WHERE t1.id NOT IN ( SELECT t3.question_id FROM `quiz_has_question` AS t3 WHERE t3.open = 0 ) GROUP BY t1.id ORDER BY id ASC LIMIT 1');
			if ( $src->count() )
			{
				$tmp = $src->fetchAll();
				$qid = $tmp[0]->id;
				dibi::query('INSERT INTO `quiz_has_question` (`quiz_id`, `question_id`, `datetime_start`) VALUES ( %i, %i, NOW() )', $this->id, $qid);
			}
			
		}
		if ( $src->count() )
		{
			$tmp = $src->fetchAll();
			$this->question = new Question($this->presenter, $this, $src);
		}
		
		
	}
	
	private function getRanks ()
	{
		# code...
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
