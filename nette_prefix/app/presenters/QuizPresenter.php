<?php

require_once APP_DIR . '/controls/Question.php';

// 
// for ( $i=0; $i < strlen($val); $i++ )
// { 
// 	Debug::dump($val[$i]);
// }

// Debug::dump(strlen($val) . ' : ' . $r);

class QuizPresenter extends BasePresenter
{
	private $title;
	public $backlink = '';
	public $id, $question, $made_questions, $questions;
	public $quiz, $chart;
	public $datetime_start, $datetime_end;
	public $system_time, $ajax_storage;
	
	public function startup ()
	{
		parent::startup();
		$this->system_time = time();
		$this->title = title . ' / Quiz';

		if ( !$this->user->isAuthenticated() ) {
			$this->flashMessage('Your must been logged.');
			$this->redirect('User:Login', $this->backlink());
		}
		else
		{
			$db  = dibi::getConnection();
			$src = $db->dataSource('SELECT NOW() AS `system_time`, t1.*, COUNT(t2.quiz_id) AS `made_questions` FROM quiz AS t1 LEFT JOIN `quiz_has_question` AS t2 ON t1.id = t2.quiz_id WHERE t1.datetime_start != "0000-00-00 00:00:00" AND ( t1.datetime_end IS NULL OR t1.datetime_end = "0000-00-00 00:00:00") GROUP BY t1.id ORDER BY t1.id DESC LIMIT 1');
			
			if ( $src->count() )
			{
				$data = $src->fetch();
				$this->system_time 				= strtotime($data->system_time);
				$this->quiz['datetime_start'] 	= strtotime($data->datetime_start);
				$this->quiz['datetime_end'] 	= strtotime($data->datetime_end);
				$this->quiz['id'] = $data->id;
				$this->quiz['run'] = ( $this->quiz['datetime_start'] <= $this->system_time ) ? 1 : 0;
				$this->quiz['time'] = abs($this->quiz['datetime_start'] - $this->system_time);
				$this->quiz['made_questions'] = $data->made_questions;
				$this->quiz['questions'] = $data->questions;
			}
			else
			{
				$this->quiz = 0;
			}
			
			if ( $this->isAjax() )
			{
				$this->ajax_storage = $this->presenter->getAjaxDriver();
			}
		}
	}
	
	public function getQuestion ($id=null)
	{
		$q = $this->getComponent('qs', $id );
		$this->question = $q;
	}

	public function actionDefault ()
	{
		if ( $this->quiz ) 
		{
			$chart_invalidate = 1;
			if ( $this->quiz['run'] )
			{
				$this->getQuestion();

				if ( isset($this->question->config) )
				{
					$question_session = NEnvironment::getSession('question');
					if ( !$this->question->form->isSubmitted() ) {
						$t = $this->question->config['datetime_start'] - $this->system_time;
						if ( $t > 0 )
						{
							sleep($t);
						}

						if ( $this->quiz['made_questions'] == 1 )
						{ 
							$this->invalidateControl('quiz');
						}

						if ( !isset($question_session->id) || $question_session->id != $this->question->config['id'])
						{
							$this->question->invalidateControl('qst');
							$this->quiz['made_questions']++;
							$question_session->cnth = 0;
						}
					}
					else
					{
						$chart_invalidate = 0;
						if ( $question_session->id != $this->question->config['id'] )
						{
							$this->flashMessage("Question timeout!");
						}
					}
					
					$question_session->id	  = $this->question->config['id'];
					$this->template->question = $this->question;
					$this->template->hints	  = count($this->question->config['hints']);
				}
				else 
				{
					$this->flashMessage("Quiz end");
					$this->invalidateControl('quiz');
//					dibi::query('UPDATE `quiz` SET `datetime_end` = NOW() WHERE `id` = %i', $this->quiz['id']);
					$this->quiz['run'] = 0;
					$this->quiz['time'] = 0;
					$this->quiz['datetime_end'] = date("Y-m-d H:i:s", $this->system_time); // toto bude mensia odchylka ale snad nikomu nebude vadit predsa

					 // hack ale neviem uz nemam sil
					$form = $this->getComponent('qform');
				}
			}
			else // kviz este nezacal ( alebo uz skoncil )preto invalidnem cely quiz snippet, 
			{

				$this->invalidateControl('quiz');


				if ( $this->quiz['datetime_end'] != "0000-00-00 00:00:00" )
				{
					$this->newQuiz();
				}
			}
			
			// na zaver naplnim template/ajax storage datami

			$this->chart = $this->getComponent('chart');
			if ( $chart_invalidate )
			{
				$this->invalidateControl('chart');
			}

			$this->template->quiz = $this->quiz;
			$this->template->chart = $this->chart;

			if ( $this->isAjax() )
			{
				$this->ajax_storage->quiz = $this->quiz;
			}
		}
		else 
		{
			$this->newQuiz();
		}

	}

	public function actionStart ($id, $sec = 60)
	{
		if ( $id )
		{
			if ( $this->user->getIdentity()->id == 5 )
			{
				dibi::query('UPDATE `quiz` SET `datetime_start` = NOW() + INTERVAL %i SECOND WHERE `id`=%i AND `datetime_start` IS NULL', $sec, $id);
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
	
	public function newQuiz ()
	{
		if ( $this->user->getIdentity()->id == 5 ) {
			$q = dibi::query('SELECT * FROM quiz WHERE datetime_start IS NULL OR datetime_start = "0000-00-00 00:00:00" LIMIT 1');
			$f = $q->fetch();

			if ( !$f )
			{
				$form = new NAppForm($this, 'new');
				$form->addText('questions', 'Questions:');
				$form->addSubmit('create', 'Create');
				$form->setDefaults(array( 'questions' => 20 ));
				$form->onSubmit[] = array($this, 'newQuizFormSubmitted');
				$this->template->new_form = $form;
				
			}
			else
			{
				$form = new NAppForm($this, 'start');
				$form->setAction($this->link('start', $f['id'] ));
				$form->setMethod('get');
				$form->addText('sec', 'Seconds:')->setValue(60);
				$form->addSubmit('start', 'Start');
				$form->setDefaults(array( 'questions' => 20 ));
				$form->onSubmit[] = array($this, 'newQuizFormSubmitted');
				$this->template->start_form = $form;

			}
		}
	}
	
	public function actionEnd ($id)
	{
		
	}
	
	public function actionAnswer ($id)
	{
		$answer = "";

		try {
			if ( $id )
			{
				$question_session = NEnvironment::getSession('question');
				if ( $id == $question_session->id )
				{
					$this->getQuestion($id);
					
					if ( $this->question )
					{
						$t = $this->question->config['datetime_start'] + $this->question->config['response_time'] - $this->system_time;
						
						if ( $t > -5 ||  $t > 10 )
						{
							if ( $t > 0 )
							{
								sleep($t);
							}
							
							if ( $this->question->config['type'] == "multi" )
							{
								$answer = array();
								// TODO zistit ako efektivnejsie vyfiltrovat hodnoty z pola podla kluca
								foreach( $this->question->config['answers'] as $ans )
								{
									if ( $ans['correct'] )
									{
										$answer[] = $ans["id"];
									}
								}
							}
							else
							{
								$answer = $this->question->config['answers'][0]["value"];
							}

							if ( $this->isAjax() )
							{
								$this->ajax_storage->answer = $answer;
							}
							
							$this->template->question = $this->question->config;
							$this->template->answer = $answer;
						}
						else
						{
							throw new Exception("Question time out.");
						}
					}
					else
					{
						throw new Exception("Question not found.");
					}
				}
				else
				{
					NDebug::firelog($question_session->id);
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
				$this->ajax_storage->error = $e->getMessage();
			}
		}
		
		$this->chart = $this->getComponent('chart');
		$this->template->chart = $this->chart;
		$this->invalidateControl('chart');
	}

	public function actionHint ($id)
	{
		try {
			$question_session = NEnvironment::getSession('question');

			if ( $id && $id == $question_session->id )
			{
				$this->getQuestion($id);
				
				if ( $this->question && $question_session->cnth <= count($this->question->config['hints']) )
				{
					$start 	= $this->question->config['datetime_start'];
					$remaining_time = $this->question->config['remaining_time'];
					$response_time = $this->question->config['response_time'];
					$hp = floor($response_time / ($this->question->config['num_hints'] + 1));
					$range 	= range(0, $response_time, $hp);
					$ch	= $this->system_time - $start;
					$i = $question_session->cnth;
					if ( $this->isAjax() )
					{
						if ( $this->system_time < $start + ( $hp * $i ) )
						{
							$sleep = ($start + $hp * $i) - $this->system_time;
							sleep($sleep);
						}
 						$this->ajax_storage->hint = $this->question->config['hints'][$i];
						$this->ajax_storage->remaining_num_hints = $this->question->config['num_hints'] - $i - 1;

					}
					$question_session->cnth++;
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
			
		} catch ( Exception $e ) { // -t-
			if ( !$this->isAjax() )
			{
				$this->flashMessage($e->getMessage());
			}
			else
			{
				$this->ajax_storage->error = $e->getMessage();
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
				'key' 	=> substr(md5($this->system_time), 16), 
				'admin' => $this->user->getIdentity()->id, 
				'datetime_create' => new DibiVariable('NOW()', 'sql'),
				'questions' =>  $form['questions']->getValue() * 1
			);
			
			$_q = new Quizs();
			$_q->insert($_q_data);
			
			$this->flashMessage('Quiz created.');
			
			$this->redirect('Quiz:');
			// TODO generovat otazky do kvizu dopredu
			// $db  = dibi::getConnection();
			// $src = $db->dataSource(sprintf('SELECT t1.id, concat(t1.title_sk, " / ", t1.title_en) AS `question`, COUNT(t2.id) AS `answers` FROM `question` AS t1 LEFT JOIN `answer` AS t2 ON t1.id = t2.question_id WHERE t1.id NOT IN ( SELECT t3.question_id FROM `quiz_has_question` AS t3 WHERE t3.open = 0 ) GROUP BY t1.id ORDER BY RAND() LIMIT %d', $questions));
			// $src = $db->dataSource('SELECT t1.*, t2.correct, t2.value, t2.id AS `answer_id` FROM `question` AS t1 LEFT JOIN `answer` AS t2 ON t1.id = t2.question_id WHERE t1.state = "approved" AND t1.id NOT IN ( SELECT t3.question_id FROM `quiz_has_question` AS t3 WHERE t3.open = 0 ) GROUP BY t1.id ORDER BY RAND() LIMIT 1');
			// Debug::dump($src->fetchAll());
		

		} catch (NFormValidationException $e) {
			$form->addError($e->getMessage());
		} catch (NullQuestionsException $e) {
			$form->addError($e->getMessage());
		}
	}

	protected function createComponent($name, $id=null)
	{
		switch ($name) {
			case 'qform':
				$form = new NAppForm($this->presenter, 'qform');
	
				return;

			case 'chart':
				$chart = new Chart($this->quiz);
				$this->addComponent($chart, 'chart');

				return;
			
			case 'qs':
				$question = new Question($this, $id);
				$this->addComponent($question, 'qs');

			break;
			
			default:
				parent::createComponent($name);
			
				return;
		}
	}

	public function beforeRender ()
	{
		$this->template->title = $this->title;
		$this->template->user = $this->user;
	}
}


?>