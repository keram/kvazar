<?php

require_once APP_DIR . '/controls/Question.php';


class QuizPresenter extends BasePresenter
{
	private $title;
	public $backlink = '';
	public $id, $question, $made_questions, $questions;
	private $run = 0;

	public function startup ()
	{
		parent::startup();
		
		$this->title = title . ' / Quiz';
		
		if ( !$this->user->isAuthenticated() ) {
			$this->flashMessage('Your must been logged.');
			$this->redirect('User:Login', $this->backlink());
			
		}
		else
		{
			$db  = dibi::getConnection();
			$src = $db->dataSource('SELECT t1.*, COUNT(t2.quiz_id) AS `made_questions` FROM quiz AS t1 LEFT JOIN `quiz_has_question` AS t2 ON t1.id = t2.quiz_id WHERE t1.datetime_start IS NOT NULL AND t1.datetime_end IS NULL GROUP BY t1.id LIMIT 1');
	
			if ( $src->count() )
			{
				$data = $src->fetch();
				$this->id = $data->id;
				$this->run = 1;
				$this->made_questions = $data->made_questions;
				$this->questions = $data->questions;
			}
		}

	}
	
	public function actionStart ($id)
	{
		if ( $id )
		{
			if ( $this->user->getIdentity()->id == 5 )
			{
				dibi::query('UPDATE `quiz` SET `datetime_start` = NOW() WHERE `id`=%i', $id);
				$this->flashMessage('Quiz started.');
				$this->redirect('Quiz:');
			}
			else
			{
				$this->flashMessage('Your don\'t  have permission for this action.');
			}
		}
		else
		{
			$this->flashMessage('Missing id.');
		}
	}

	public function actionDefault ()
	{
		if ( $this->run )
		{
			// pridam 1 sekundu aj ako rezervu sem
				$src_question = dibi::getConnection()->dataSource('SELECT t1.question_id AS `id`, t1.datetime_start, 
					t2.id AS `question_id`, t2.title_sk, t2.title_en, t2.response_time, 
					t3.id AS `answer_id`, t3.correct AS `answer_correct`, t3.value AS `answer_value`, COUNT(t3.id) AS `answers_count` 
				 FROM `quiz_has_question` AS t1
					 LEFT JOIN `question` AS t2 ON t1.question_id = t2.id
					 LEFT JOIN `answer` AS t3 ON t2.id = t3.question_id
				 WHERE t1.quiz_id = %i AND t2.state = "approved" AND t1.datetime_start > NOW() - INTERVAL t2.response_time second GROUP BY t3.question_id', $this->id);
				
				if ( !$src_question->count() )
				{
					if ( $this->made_questions < $this->questions )
					{
						$src_question = dibi::getConnection()->dataSource('SELECT t1.id, t1.title_sk, t1.title_en, t1.response_time, t2.id AS `answer_id`, t2.correct AS `answer_correct`, t2.value AS `answer_value`,  COUNT(t2.id) AS `answers_count` FROM `question` AS t1 LEFT JOIN `answer` AS t2 ON t1.id = t2.question_id WHERE t1.id NOT IN ( SELECT t3.question_id FROM `quiz_has_question` AS t3 ) GROUP BY t1.id ORDER BY RAND() ASC LIMIT 1');
	
						if ( $src_question->count() )
						{
							$tmp = $src_question->fetch();
							$qid = $tmp->id;
	
							try	{
								dibi::query('INSERT INTO `quiz_has_question` (`quiz_id`, `question_id`, `datetime_start`) VALUES ( %i, %i, NOW() )', $this->id, $qid);
							} catch ( DibiDriverException $e ) {
								Debug::dump("duplicate");
							}
						}
						else
						{
							$str = 'Missing questions. Have left ' . ( $this->questions - $this->made_questions ) . ' from ' . $this->questions . ' questions.';
							$this->flashMessage($str);
						}
					}
					else
					{
						$this->flashMessage("Quiz end");
						$this->invalidateControl('quiz');
					}
				}

				if ( $src_question->count() )
				{
					$this->question = new Question($this, $src_question);
					$this->addComponent($this->question, 'qs');
					$e  = $this->getComponent('qs');
	
					if ( !$e->form->isSubmitted() ) {
						$e->invalidateControl('qst');
	
						$question_session = Environment::getSession('question');
						$question_session->id = $this->question->id;
						$question_session->start = strtotime("now");
						$question_session->time	 = $this->question->time;
						$question_session->hints = $this->question->hints;
						$question_session->type	 = $this->question->type;
						$question_session->chints = array();
					}
	
					$this->template->question = $this->question;
				}
		}
		
		else
		{

			if ( $this->user->getIdentity()->id == 5 ) {
				$form = new AppForm($this, 'new');
				$form->addText('questions', 'Questions:');
				$form->addSubmit('create', 'Create');
				$form->setDefaults(array( 'questions' => 20 ));
				$form->onSubmit[] = array($this, 'newQuizFormSubmitted');
				$this->template->new_form = $form;
			}
			
			$this->flashMessage('Quiz not exists or not run.');
		}
		

		// $this->invalidateControl('round');
		// $this->invalidateControl('qst');
	}

	public function actionEnd ($id)
	{
		# code...
	}
	
	public function actionAnswer ($id)
	{
		if ( $this->isAjax() )
		{
			$ajax_storage = $this->presenter->getAjaxDriver();
		}

		try {
			if ( $id )
			{
				$question_session = Environment::getSession('question');
				
				if ( $id == $question_session->id )
				{
					$type = $question_session->type;
					
					$q = dibi::query('SELECT t1.id, t1.value FROM answer AS t1 WHERE `t1.question_id` = %i and `t1.correct` = 1', $id);

					if ( $q->count() != 0 )
					{
						if ( $type == "multi" )
						{
							$a = "";
							// TODO zistit ako efektivnejsie vyfiltrovat hodnoty z pola podla kluca
							foreach( $q->fetchAll() as $k => $v )
							{
								$a[] = $v["id"];
							}

							$ajax_storage->answer = $a;
						}
						else
						{
							$a = $q->fetch();
							$ajax_storage->answer = $a["value"];
						}
					}
				}
				else
				{
					throw new Exception("Bad question id");
				}
			}
			else
			{
				throw new Exception("Question id not passed");
			}

		} catch ( Exception $e ) {
			if ( !$this->isAjax() )
			{
				$this->flashMessage($e->getMessage());
			}
			else
			{
				$ajax_storage->error = $e->getMessage();
			}
		}
	}
	
	public function actionHint ($id)
	{
		if ( $this->isAjax() )
		{
			$ajax_storage = $this->presenter->getAjaxDriver();
		}

		try {
			if ( $id )
			{
				$question_session = Environment::getSession('question');
				if ( $id == $question_session->id && $question_session->hints != 0 )
				{
					$start 	= $question_session->start;
					$time 	= $question_session->time;
					$hints 	= $question_session->hints;
					$type 	= $question_session->type;
					$current_hints = $question_session->chints;
					
					if ( $type == "multi" )
					{
						$cnth = count($current_hints) + 1;

	 					if ( $cnth <= $hints )
	 					{
	 						$hp = round(( $time / 100 ) * ( 100 / ($hints + 1)) );
	 						$ht = $hp * $cnth;
	 
	 						// mensi hack aby sa posledny hint zobrazil minimalne 10 sek pred koncom otazky a nie skor
	 						if ( $cnth == $hints )
	 						{
	 							$ht = max($ht, $time - 10);
	 						}
	 
	 						if ( strtotime("now") - $start < $ht )
	 						{
	 							$sleep = $ht - (strtotime("now") - $start);
	 							$ajax_storage->sleep_request = "1";
	 							sleep($sleep);
	 						}
	 
	 						$q = dibi::query('SELECT t1.* FROM answer AS t1 WHERE `t1.question_id` = %i ORDER BY `t1.correct`', $id);
	 						if ( $q->count() != 0 )
	 						{
	 							// viac moznosti
	 							if ( $q->count() > 1 )
	 							{
	 								$answers = $q->fetchAll();
	 								foreach( $answers as $answer )
	 								{
										if ( !in_array($answer['id'], $current_hints ) )
										{
											$ajax_storage->hint = $answer['id'];
											$ajax_storage->hints = $hints - $cnth;
											$question_session->chints[] = $answer['id'];
											
											break;
										}
	 								}
	 							}
	 							else
	 							{
	 								// todo
	 							}
	 						}
	 					}
					}
				}
				else
				{
					throw new Exception("Bad question id");
				}
			}
			else
			{
				throw new Exception("Question id not passed");
			}
			
		} catch ( Exception $e ) {
			if ( !$this->isAjax() )
			{
				$this->flashMessage($e->getMessage());
			}
			else
			{
				$ajax_storage->error = $e->getMessage();
			}
			
		}
	}
	
	public function newQuizFormSubmitted ($form)
	{
		// TODO administracia
		try {
			if ( $form['questions']->getValue() * 1 == 0 )
			{
				 throw new NullQuestionsException();
			}
			
			$questions = $form['questions']->getValue() * 1;
			
			
			$_q_data = array( 
				'key' 	=> substr(md5(strtotime("now")), 16), 
				'admin' => $this->user->getIdentity()->id, 
				'datetime_create' => new DibiVariable('NOW()', 'sql'),
				'questions' =>  $form['questions']->getValue() * 1
			);
			
			$_q = new Quizs();
			$_q->insert($_q_data);
			$this->redirect('Quiz:');
			// TODO generovat otazky do kvizu dopredu
			// $db  = dibi::getConnection();
			// $src = $db->dataSource(sprintf('SELECT t1.id, concat(t1.title_sk, " / ", t1.title_en) AS `question`, COUNT(t2.id) AS `answers` FROM `question` AS t1 LEFT JOIN `answer` AS t2 ON t1.id = t2.question_id WHERE t1.id NOT IN ( SELECT t3.question_id FROM `quiz_has_question` AS t3 WHERE t3.open = 0 ) GROUP BY t1.id ORDER BY RAND() LIMIT %d', $questions));
			// $src = $db->dataSource('SELECT t1.*, t2.correct, t2.value, t2.id AS `answer_id` FROM `question` AS t1 LEFT JOIN `answer` AS t2 ON t1.id = t2.question_id WHERE t1.state = "approved" AND t1.id NOT IN ( SELECT t3.question_id FROM `quiz_has_question` AS t3 WHERE t3.open = 0 ) GROUP BY t1.id ORDER BY RAND() LIMIT 1');
			// Debug::dump($src->fetchAll());
		

		} catch (FormValidationException $e) {
			$form->addError($e->getMessage());
		} catch (NullQuestionsException $e) {
			$form->addError($e->getMessage());
		}		
	}

	public function beforeRender ()
	{
		$this->template->testt = strtotime("now");
		$this->template->title = $this->title;
		$this->template->user = $this->user;
	}
}


?>