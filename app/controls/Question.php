<?php

	#doc
	#	classname:	Question
	#	scope:		PUBLIC
	#
	#/doc
	
	class Question extends Control
	{
		#	internal variables
		public $useAjax = true;
		public $id, $title, $answers, $answers_count, $scope, $used, $response_time, $remaining_time, $datetime_start, $answer_id;
		public $hints, $type;
		public $form;
		public $presenter;
		
		#	Constructor
		function __construct ( $presenter, $src )
		{
			$this->presenter = $presenter;
			
			parent::__construct();
			$this->bindData($src);
			$this->createForm();
			if ( $this->form->isSubmitted() )
			{
			 	// $this->validateAnswer();
			// 	Debug::dump("jurko");
			}
		}
		###	
		
		public function getType ()
		{
			$type = "simple";
			
			if ( $this->answers_count > 1 )
			{
				$type = "multi";
			}
			
			return $type;
		}
		
		public function bindData ($src)
		{
			$tmp = $src->fetchAll();
			$data = $tmp[0];
			$this->id = $data->id;
			$this->answer_id 	= $data->answer_id;
			$this->title['sk'] 	= $data->title_sk;
			$this->title['en'] 	= $data->title_en;
			$this->response_time = $data->response_time;
			$now = date("Y-m-d H:i:s", time());
			$this->datetime_start = isset($data->datetime_start) ? $data->datetime_start : $now;
			$this->remaining_time = $this->response_time - ( strtotime($now) - min( strtotime($now), strtotime($this->datetime_start)) );
			$this->answers_count = $data->answers_count;
			$this->type = $this->getType();
			
			if ( $this->answers_count > 1 )
			{
				$q = dibi::query('SELECT * FROM `answer` WHERE `question_id` = %i ORDER BY RAND()', $this->id);
				if ( $q->count() )
				{
					$d = $q->fetchAll();
					foreach( $d as $k )
					{
						$this->answers[] = array('id' => $k['id'], 'value' => $k['value'], 'correct' => $k['correct']);
					}
				}
			}
			else
			{
				$this->answers[] = array('id' => $data['answer_id'], 'value' => stripslashes($data['answer_value']), 'correct' => $data['answer_correct']);
			}
			
			$this->hints = $this->getNumberHints();
			
			if ( $this->presenter->isAjax() )
			{
				$ajax_storage = $this->presenter->getAjaxDriver();
				$ajax_storage->question = array(
					"id" 	=> $this->id,
					"remaining_time" => $this->remaining_time,
					"hints"	=> $this->hints,
					"type" 	=> $this->type
				);
			}
		}
		
		public function getNumberHints ()
		{
			$hints = 0;
			
			if ( $this->answers_count > 1 )
			{
				$hints = $this->answers_count - 2;
			}
			else
			{
				$hints = ( strlen($this->answers[0]['value']) >= 2 ) ? min(strlen($this->answers[0]['value']), 4) - 1 : 0;
			}
			
			return $hints;
		}

		public function createForm ()
		{
			$form = $this->presenter->getComponent('qform');
			// $form = new AppForm($this->presenter, 'qform');
			$form->renderer->clientScript = NULL;
			$elm = $form->getElementPrototype();
			$elm->attrs['id'] = 'qform';
			$title = ( $this->title['sk'] && $this->title['en'] ) ? $this->title['sk'] . ' / ' .  $this->title['en'] : ( ( $this->title['sk'] ) ? $this->title['sk'] : $this->title['en']);
			$group = $form->addGroup($title);
			
			$user = Environment::getUser();
			$user_data_src = dibi::query('SELECT * FROM user_answer WHERE `user_id` = %i AND `quiz_id` = %i AND `question_id` = %i ORDER BY `time` DESC LIMIT 1', $user->getIdentity()->id, $this->presenter->quiz['id'], $this->id);
			$user_data = $user_data_src->fetch();

			$question_session = Environment::getSession('question');
			$question_session->submitted = 0;
			
			if ( $this->answers_count > 1 )
			{
				if ( $user_data )
				{
					$question_session->submitted = 1;
				}
				
				$user_answers = explode(';', $user_data['value']);

				foreach( $this->answers as $answer)
				{
					$form->addCheckbox('answer' . $answer['id'], $answer['value'])->getControlPrototype()->value($answer['id']);
					
					$group->add($form['answer' . $answer['id']]);
					if ( $user_data )
					{
						$form['answer' . $answer['id']]->setDisabled();
						if ( in_array($answer['id'], $user_answers) )
						{
							$form['answer' . $answer['id']]->setValue(1);
						}
					}
				}
			}
			else
			{
				$form->addText('useranswer', "User answer")->addRule(Form::FILLED, 'Not filled answer.');
				$form->addText('answer', "");
				$group->add($form['useranswer']);

				if ( $user_data )
				{
					$form['useranswer']->setValue(stripslashes($user_data->value));
				}
			}
			
			if ( $this->type == "multi" && $user_data )
			{
				$form->addSubmit('next', 'Wait')->setDisabled();
			}
			else
			{
				$form->addSubmit('send', 'Send');
			}

			$form->onSubmit[] = array($this, 'questionFormSubmitted');
			$this->form = $form;
		}
		
		public function questionFormSubmitted ($form)
		{
			$question_session = Environment::getSession('question');
			try	{
				if ( $this->id == $question_session->id && strtotime($this->datetime_start) + $this->response_time >= strtotime("now") )
				{
					$user = Environment::getUser();
					$user_answer = false;
					$valid = 1;
					$points = 0;
					

					if ( $this->answers_count > 1 )
					{
						// $this->answers[]
						foreach( $this->answers as $answer)
						{
							if ( $form['answer' . $answer['id']]->value || isset($_REQUEST['answer' . $answer['id']]) )
							{
								$user_answer .= $answer['id'] . ';';
								if ( $answer['correct'] )
								{
									$points++;
								}
								else
								{
									$points--;
								}
							}
						}
						
						$points = max(0, $points);
						
						$user_answer = substr($user_answer, 0, -1);
	
						$question_session = Environment::getSession('question');
						if ( $question_session->submitted == 1 )
						{
							$form->addError("Answer has been submited");
							$valid = 0;
						}
					}
					elseif ( $form['useranswer'] != "" )
					{
						$user_answer = $form['useranswer']->getValue();
						// todo toto este otestovat poriadne a do buducna pridat multijazycnost
						$ustr = String::webalize($user_answer);
						$ostr = String::webalize($this->answers[0]["value"]);
						Debug::dump($ustr . ' -- ' . $ostr);
						if ( $ustr == $ostr || ( $ostr > 5 && $ustr[0] == $ostr[0] && levenshtein($ustr, $ostr) < 2 )  )
						{	 // aby sme zamedzily zbytocnym preklepom a zaroven Dawkins bude niekto iny ako Hawkins
							$points = 1;
						}
					}
					
					if ( $user_answer && $valid )
					{
						try	{
							dibi::query('INSERT INTO `user_answer` (`user_id`, `quiz_id`, `question_id`, `value`, `time`, `points`) VALUES ( %i, %i, %i, %s, NOW(), %i )', $user->getIdentity()->id, $this->presenter->quiz['id'], $this->id, addslashes($user_answer), $points );
	
							if ( $this->type == "multi" )
							{
								$form->offsetUnset('send');
								foreach( $form->getControls() as $elm )
								{
									$elm->setDisabled();
								}
	
								$form->addSubmit('next', 'Wait')->setDisabled();
							}

						} catch (DibiDriverException $e) {
							if ( $e->getCode() == 1062 )
							{
								$form->addError("Answer has been submitted");
							}
							else
							{
								$form->addError($e->getMessage());
							}
						} 
					}
					
					$this->presenter->redirect('Quiz:');
				}
				else
				{
					$form->addError("Bad question or time is out.");
				}
			// TODO vyhod question exception
			} catch ( QuestionException $e ) {
				Debug::dump("nieje tu vynimka nahodou?");
			}
		}
		
		
		public function render ()
		{
			$template = $this->createTemplate();
			$template->question = $this;
			$template->form = $this->form;
			// renderf
			$template->useAjax = $this->useAjax;
			$template->setFile(dirname(__FILE__) . '/question.phtml');
			$template->registerFilter('Nette\Templates\CurlyBracketsFilter::invoke');
			$template->render();
		}
	}
	###

