<?php

require_once APP_DIR . '/controls/Question.php';


class QuizPresenter extends BasePresenter
{
	private $title;
	public $backlink = '';
	public $id, $question;


	public function startup ()
	{
		parent::startup();

		$this->title = title . ' / Quiz';
		
		if ( !$this->user->isAuthenticated() ) {
			$this->flashMessage('Your must been logged.');
			$this->redirect('User:Login', $this->backlink());
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
		$db  = dibi::getConnection();
		$src = $db->dataSource('SELECT * FROM quiz AS t1 WHERE t1.datetime_start IS NOT NULL AND t1.datetime_end IS NULL ORDER BY id LIMIT 1');

		if ( $src->count() )
		{
			$data = $src->fetchAll();
			$this->id = $data[0]->id;
			$src_question = dibi::getConnection()->dataSource('SELECT t1.question_id AS `id`, t1.open, t1.datetime_start, t1.time, t1.quiz_id AS `questions_count`, 
								t2.id AS `question_id`, t2.title_sk, t2.title_en, 
								t3.id AS `answer_id`, t3.correct AS `answer_correct`, t3.value AS `answer_value`, COUNT(t3.id) AS `answers_count` 
							 FROM `quiz_has_question` AS t1
								 LEFT JOIN `question` AS t2 ON t1.question_id = t2.id
								 LEFT JOIN `answer` AS t3 ON t2.id = t3.question_id
							 WHERE t1.quiz_id = %i AND t1.datetime_start > NOW() - INTERVAL t1.time second GROUP BY t3.question_id', $this->id);
							 
			if ( !$src_question->count() )
			{
				$src_question = dibi::getConnection()->dataSource('SELECT t1.id, t1.title_sk, t1.title_en, t2.id AS `answer_id`, t2.correct AS `answer_correct`, t2.value AS `answer_value`,  COUNT(t2.id) AS `answers_count` FROM `question` AS t1 LEFT JOIN `answer` AS t2 ON t1.id = t2.question_id WHERE t1.id NOT IN ( SELECT t3.question_id FROM `quiz_has_question` AS t3 ) GROUP BY t1.id ORDER BY RAND() ASC LIMIT 1');
				if ( $src_question->count() )
				{
					$tmp = $src_question->fetchAll();
					$qid = $tmp[0]->id;
					
					// dibi::test('INSERT INTO `quiz_has_question` (`quiz_id`, `question_id`, `datetime_start`) VALUES ( %i, %i, NOW() )', $this->id, $qid);
					// dibi::query('INSERT INTO `quiz_has_question` (`quiz_id`, `question_id`, `datetime_start`) VALUES ( %i, %i, NOW() )', $this->id, $qid);
					// dibi::query('INSERT INTO `quiz_has_question` (`quiz_id`, `question_id`, `datetime_start`, `time`) VALUES ( %i, %i, NOW(), 10 )', $this->id, $qid);
				}
				else
				{
					
				}
			}
			if ( $src_question->count() )
			{
				$tmp = $src_question->fetchAll();
				$this->question = new Question($this, $src_question);
				$this->addComponent($this->question, 'question');
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
		$this->template->title = $this->title;
		$this->template->user = $this->user;
	}
}


?>