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
	public $system_time;
	
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
				$this->system_time = $data->system_time;
				$this->quiz['id'] = $data->id;
				$this->quiz['run'] = ( strtotime($data->datetime_start) < strtotime($this->system_time) ) ? 1 : 0;
				$this->quiz['time'] = abs(strtotime($data->datetime_start) - strtotime($this->system_time));
				$this->quiz['made_questions'] = $data->made_questions;
				$this->quiz['questions'] = $data->questions;
				$this->quiz['datetime_start'] = $data->datetime_start;
				$this->quiz['datetime_end'] = $data->datetime_end;
			}
			else
			{
				$this->quiz = 0;
			}
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

	public function getQuestion ( $id = null, $cnt = 1 )
	{
		$question = false;
		
		if ( $id == null )
		{
			$src_question = dibi::getConnection()->dataSource('SELECT t1.question_id AS `id`, t1.datetime_start, 
					t2.id AS `question_id`, t2.title_sk, t2.title_en, t2.response_time, 
					t3.id AS `answer_id`, t3.correct AS `answer_correct`, t3.value AS `answer_value`, COUNT(t3.id) AS `answers_count` 
				 FROM `quiz_has_question` AS t1
					 LEFT JOIN `question` AS t2 ON t1.question_id = t2.id
					 LEFT JOIN `answer` AS t3 ON t2.id = t3.question_id
				 WHERE t1.quiz_id = %i AND t1.datetime_start > NOW() - INTERVAL t2.response_time SECOND GROUP BY t3.question_id', $this->quiz['id']);

		}
		else
		{
			$src_question = dibi::getConnection()->dataSource('SELECT t1.question_id AS `id`, t1.datetime_start, 
					t2.id AS `question_id`, t2.title_sk, t2.title_en, t2.response_time, 
					t3.id AS `answer_id`, t3.correct AS `answer_correct`, t3.value AS `answer_value`, COUNT(t3.id) AS `answers_count` 
				 FROM `quiz_has_question` AS t1
					 LEFT JOIN `question` AS t2 ON t1.question_id = t2.id
					 LEFT JOIN `answer` AS t3 ON t2.id = t3.question_id
				 WHERE t1.quiz_id = %i AND t2.id = %i GROUP BY t3.question_id', $this->quiz['id'], $id);
		}
		
		if ( $src_question->count() == 0 && $id == null )
		{
			$cache = NEnvironment::getCache();
			$tmp_id = null;
			if ( isset($cache['new_question']) )
			{
				sleep(1);
				$tmp_id = $cache['new_question'];
			}
			else
			{
				$cache['new_question'] = null; // snad toto ako lock posluzi ok
				if ( $cnt == 1 && $this->quiz['made_questions'] < $this->quiz['questions'])
				{
					try	{
						// $src_new_question = dibi::getConnection()->dataSource('SELECT t1.id, t1.response_time FROM `question` AS t1 WHERE t1.id NOT IN ( SELECT t3.question_id FROM `quiz_has_question` AS t3 WHERE `quiz_id` = %i ) LIMIT 1', $this->quiz['id']);
						$src_new_question = dibi::getConnection()->dataSource('SELECT NOW() AS `system_time`, t1.id, t1.response_time FROM `question` AS t1 WHERE t1.id NOT IN ( SELECT t3.question_id FROM `quiz_has_question` AS t3 WHERE `quiz_id` = %i ) ORDER BY RAND() ASC LIMIT 1', $this->quiz['id']);
						// $src_question = dibi::getConnection()->dataSource('SELECT t1.id FROM `question` AS t1 WHERE t1.id = 15');
	
						if ( !$src_new_question->count() )
						{
							throw new Exception("Question not found");
						}
	
						$tmp = $src_new_question->fetch();
						$tmp_id = $tmp->id;
						$cache->save('new_question', $tmp_id, array('expire' => strtotime($tmp->system_time) + $tmp->response_time));
						dibi::query('INSERT INTO `quiz_has_question` (`quiz_id`, `question_id`, `datetime_start`) VALUES ( %i, %i, NOW() + INTERVAL 3 second )', $this->quiz['id'], $tmp_id);
					} catch ( Exception $e ) {
						$tmp_id = null;
					}
				}
			}

			// ochrana proti zacykleniu
			if ( $cnt < 5 )
			{
				$question = $this->getQuestion($tmp_id, ++$cnt);
				$this->quiz['made_questions']++;
			}
			else
			{
				$this->quiz['made_questions']--;
			}
		}
		else
		{
			$question = new Question($this, $src_question);
		}
		
		return $question;
	}

	public function actionDefault ()
	{
		if ( $this->isAjax() )
		{
			$ajax_storage = $this->presenter->getAjaxDriver();
		}
		
		if ( $this->quiz ) 
		{
			$chart_invalidate = 1;
			if ( $this->quiz['run'] )
			{
				$this->question = $this->getQuestion();
				
				if ( $this->question )
				{
					$question_session = NEnvironment::getSession('question');
					$this->addComponent($this->question, 'qs');
					$qs  = $this->getComponent('qs');

					if ( !$qs->form->isSubmitted() ) {
						$t = strtotime($this->question->datetime_start) - strtotime($this->system_time);
						if ( $t > 0 )
						{
							sleep($t);
						}
						else
						{
							// echo $this->question->datetime_start . "-t-";
						}

						if ( $this->quiz['made_questions'] == 1 )
						{ 
							$this->invalidateControl('quiz');
						}

						$qs->invalidateControl('qst');
						
						// nema sa co ked je 0 invalidovat kedze este neexistoval tento snippet
						$question_session->id	  = $this->question->id;
						$question_session->chints = array();
						$question_session->cnth   = 0;
					}
					else
					{
						$chart_invalidate = 0;
						if ( $question_session->id != $qs->id )
						{
							$this->flashMessage("Question timeout!");
						}
					}
					
					
					$this->template->question = $this->question;
				}
				else 
				{
					$this->flashMessage("Quiz end");
					$this->invalidateControl('quiz');
					dibi::query('UPDATE `quiz` SET `datetime_end` = NOW() WHERE `id` = %i', $this->quiz['id']);
					$this->quiz['run'] = 0;
					$this->quiz['time'] = 0;
					$this->quiz['datetime_end'] = date("Y-m-d H:i:s", strtotime($this->system_time)); // toto bude mensia odchylka ale snad nikomu nebude vadit predsa
					$cache = NEnvironment::getCache();
					unset($cache['new_question']);
					
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
				$ajax_storage->quiz = $this->quiz;
			}
		}
		else 
		{
			$this->newQuiz();
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
		if ( $this->isAjax() )
		{
			$ajax_storage = $this->presenter->getAjaxDriver();
		}
		
		$answer = "";

		try {

			if ( $id )
			{
				$question_session = NEnvironment::getSession('question');
				if ( $id == $question_session->id )
				{
					$this->question = $this->getQuestion($id);
					
					if ( $this->question )
					{
						$t = strtotime($this->question->datetime_start) + $this->question->response_time - strtotime($this->system_time);
						
						if ( $t > -5 ||  $t > 10 )
						{
							if ( $t > 0 )
							{
								sleep($t);
							}

							if ( $this->question->type == "multi" )
							{
								$answer = array();
								// TODO zistit ako efektivnejsie vyfiltrovat hodnoty z pola podla kluca
								foreach( $this->question->answers as $ans )
								{
									if ( $ans['correct'] )
									{
										$answer[] = $ans["id"];
									}
								}
							}
							else
							{
								$answer = $this->question->answers[0]["value"];
							}

							if ( $this->isAjax() )
							{
								$ajax_storage->answer = $answer;
							}
							
							$this->template->question = $this->question;
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
		
		$this->chart = $this->getComponent('chart');
		$this->template->chart = $this->chart;
		$this->invalidateControl('chart');
	}
	
	public function actionHint ($id)
	{
		if ( $this->isAjax() )
		{
			$ajax_storage = $this->presenter->getAjaxDriver();
		}

		try {
			$question_session = NEnvironment::getSession('question');
			$question_session->cnth++;

			if ( $id && $id == $question_session->id )
			{
				$this->question = $this->getQuestion($id);
				
				if ( $this->question && $question_session->cnth <= $this->question->hints )
				{
					$start 	= strtotime($this->question->datetime_start);
					$remaining_time = $this->question->remaining_time;
					$response_time = $this->question->response_time;
					$hints 	= $this->question->hints;
					$current_hint = $question_session->cnth;
					$remaining_hints = $hints - $current_hint;
					
					$hp = floor($response_time / ($hints + 1));
					if ( strtotime($this->system_time) < $start + ( $hp * $current_hint ) )
					{
						$sleep =  ( $start + ( $hp * $current_hint ) ) - strtotime($this->system_time);
						sleep($sleep);
					}
					
					if ( $this->question->type == "multi" )
					{
						foreach( $this->question->answers as $answer )
						{
							// pri multi posielam nespravne odpovede ako hinty!
							if ( $answer['correct'] == 0 && !in_array($answer['id'], $question_session->chints ) )
							{ 
								$ajax_storage->hint = $answer['id'];
								$question_session->chints[] = $answer['id'];

								break;
							}
						}
						
					}
					else
					{
						$str = $this->question->answers[0]['value'];
						$hint_str = "";
						$visited = $question_session->chints;
						$full_str = str_split($str);
						$expl_str = $full_str;

						$chars_hint = floor( strlen($str) / ( $hints + 1));

						$hint_str = "";
						for ( $i=0; $i < count($visited); $i++ )
						{ 
							unset($expl_str[$visited[$i]]);
						}

						if ( is_array($expl_str) && count($expl_str) > $chars_hint )
						{
							// vyberiem x prvkov z pola ktore este neboli
							$rand = array_rand($expl_str, $chars_hint);
							if ( is_array($rand) )
							{
								$new_array = array_merge($rand, $visited);
							}
							else
							{
								$new_array = array_merge(array($rand), $visited);
							}


							for ( $i=0; $i < count($full_str); $i++ )
							{ 
								if ( in_array($i, $new_array) )
								{
									$hint_str .= $full_str[$i];
								}
								else
								{
									$hint_str .= "_";
								}
							}

							$question_session->chints = $new_array;
							$ajax_storage->hint = $hint_str;
						}
					}
					
					if ( $this->isAjax() )
					{
						$ajax_storage->remaining_hints = $remaining_hints;
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
				'key' 	=> substr(md5(strtotime($this->system_time)), 16), 
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

	protected function createComponent($name)
	{
		switch ($name) {
			case 'qform':
				$form = new NAppForm($this->presenter, $name);
	
				return;

			case 'chart':
				$chart = new Chart($this->quiz);
				$this->addComponent($chart, $name);

				return;
	
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