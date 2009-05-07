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
	public $quiz;
	public $test;
	public $datetime_start, $datetime_end;
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
				$this->quiz['id'] = $data->id;
				$this->quiz['run'] = ( strtotime($data->datetime_start)  < time() ) ? 1 : 0;
				$this->quiz['time'] = abs(strtotime($data->datetime_start) - time());
				$this->quiz['made_questions'] = $data->made_questions;
				$this->quiz['questions'] = $data->questions;
				$this->quiz['datetime_start'] = $data->datetime_start;
				$this->quiz['datetime_end'] = $data->datetime_end;
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

	public function actionDefault ()
	{
		if ( $this->isAjax() )
		{
			$ajax_storage = $this->presenter->getAjaxDriver();
		}

		if ( $this->quiz ) 
		{
			if ( $this->quiz['run'] )
			{
				// pridam 1 sekundu aj ako rezervu sem
				$src_question = dibi::getConnection()->dataSource('SELECT t1.question_id AS `id`, t1.datetime_start, 
					t2.id AS `question_id`, t2.title_sk, t2.title_en, t2.response_time, 
					t3.id AS `answer_id`, t3.correct AS `answer_correct`, t3.value AS `answer_value`, COUNT(t3.id) AS `answers_count` 
				 FROM `quiz_has_question` AS t1
					 LEFT JOIN `question` AS t2 ON t1.question_id = t2.id
					 LEFT JOIN `answer` AS t3 ON t2.id = t3.question_id
				 WHERE t1.quiz_id = %i AND t2.state = "approved" AND t1.datetime_start > NOW() - INTERVAL t2.response_time second GROUP BY t3.question_id', $this->quiz['id']);
	
				if ( !$src_question->count() )
				{
					if ( $this->quiz['made_questions'] < $this->quiz['questions'] )
					{
						// $src_question = dibi::getConnection()->dataSource('SELECT t1.id, t1.title_sk, t1.title_en, t1.response_time, t2.id AS `answer_id`, t2.correct AS `answer_correct`, t2.value AS `answer_value`,  COUNT(t2.id) AS `answers_count` FROM `question` AS t1 LEFT JOIN `answer` AS t2 ON t1.id = t2.question_id WHERE t1.id NOT IN ( SELECT t3.question_id FROM `quiz_has_question` AS t3 ) GROUP BY t1.id ORDER BY RAND() ASC LIMIT 1');
						$src_question = dibi::getConnection()->dataSource('SELECT t1.id, t1.title_sk, t1.title_en, t1.response_time, t2.id AS `answer_id`, t2.correct AS `answer_correct`, t2.value AS `answer_value`,  COUNT(t2.id) AS `answers_count` FROM `question` AS t1 LEFT JOIN `answer` AS t2 ON t1.id = t2.question_id WHERE t1.id NOT IN ( SELECT t3.question_id FROM `quiz_has_question` AS t3 WHERE `quiz_id` = %i ) GROUP BY t1.id ORDER BY RAND() ASC LIMIT 1', $this->quiz['id']);
						// dibi::test('SELECT t1.id, t1.title_sk, t1.title_en, t1.response_time, t2.id AS `answer_id`, t2.correct AS `answer_correct`, t2.value AS `answer_value`,  COUNT(t2.id) AS `answers_count` FROM `question` AS t1 LEFT JOIN `answer` AS t2 ON t1.id = t2.question_id WHERE t1.id NOT IN ( SELECT t3.question_id FROM `quiz_has_question` AS t3 WHERE `quiz_id` != %i ) GROUP BY t1.id ORDER BY RAND() ASC LIMIT 1', $this->quiz['id']);
	
						if ( $src_question->count() )
						{
							$tmp = $src_question->fetch();
							$qid = $tmp->id;
	
							try	{
								dibi::query('INSERT INTO `quiz_has_question` (`quiz_id`, `question_id`, `datetime_start`) VALUES ( %i, %i, NOW() )', $this->quiz['id'], $qid);
							} catch ( DibiDriverException $e ) {
								$this->flashMessage($e->getMessage);
							}
						}
						else
						{
							$str = 'Missing questions. Have left ' . ( $this->quiz['questions'] - $this->quiz['made_questions'] ) . ' from ' . $this->quiz['questions'] . ' questions.';
							$this->flashMessage($str);
						}
					}
					else
					{
						$this->flashMessage("Quiz end");
						$this->invalidateControl('quiz');
						// debug dibi::query('UPDATE `quiz` SET `datetime_end` = NOW() WHERE `id` = %i', $this->quiz['id']);
					}
				}
	
				if ( $src_question->count() )
				{
					// kviz prave zacal tak invalidnem cely quiz aby som nahral prvu otazku a dalsi bordel
					if ( $this->quiz['made_questions'] == 0 )
					{
						$this->invalidateControl('quiz');
					}
					
					$this->question = new Question($this, $src_question);
					$this->addComponent($this->question, 'qs');
					$e  = $this->getComponent('qs');

					if ( !$e->form->isSubmitted() ) {
						// nema sa co ked je 0 invalidovat kedze este neexistoval tento snippet
						if ( $this->quiz['made_questions'] > 0 ) {
							$e->invalidateControl('qst');
						}

						$question_session = Environment::getSession('question');
						$question_session->id = $this->question->id;
						$question_session->start = strtotime("now");
						$question_session->time	 = $this->question->time;
						$question_session->hints = $this->question->hints;
						$question_session->type	 = $this->question->type;
						$question_session->chints = array();
						$question_session->cnth = 0;

					}

					$this->template->question = $this->question;
				}
			}
			else // kviz este nezacal preto invalidnem cely quiz snippet
			{
				$this->invalidateControl('quiz');
			}
			
			// na zaver naplnim template/ajax storage datami
			$this->template->quiz = $this->quiz;

			if ( $this->isAjax() )
			{
				$ajax_storage->quiz = $this->quiz;
			}
		}
		else 
		{
			
			// kviz nebezi, ak mam na to prava zobrazim formular na vytvorenie kvizu
			if ( $this->user->getIdentity()->id == 5 ) {
				$form = new AppForm($this, 'new');
				$form->addText('questions', 'Questions:');
				$form->addSubmit('create', 'Create');
				$form->setDefaults(array( 'questions' => 20 ));
				$form->onSubmit[] = array($this, 'newQuizFormSubmitted');
				$this->template->new_form = $form;
			}
		}
		
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
	 					}
					}
					else
					{
						$cnth = $question_session->cnth;

						if ( $cnth <= $hints )
						{
							$q = dibi::query('SELECT t1.* FROM answer AS t1 WHERE `t1.question_id` = %i ORDER BY `t1.correct`', $id);
							$data = $q->fetch();

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

							$str = $data->value;
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
								$ajax_storage->hints = $hints - $cnth;
								$question_session->cnth++;
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
			
			$this->flashMessage('Quiz created.');
			
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

	protected function createComponent($name)
	{
		switch ($name) {
			case 'qform':
				$form = new AppForm($this->presenter, $name);
				// $this->addComponent($form, $name);
	
				return;
	
			default:
				parent::createComponent($name);
			
				return;
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