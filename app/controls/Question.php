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
		public $id, $title, $answers, $answers_count, $scope, $used, $time, $datetime_start, $answer_id;
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
		
		public function handleDefault ()
		{
			Debug::dump("handluje nieco tato fnc?");
		}
		
		public function bindData ($src)
		{
			$tmp = $src->fetchAll();
			$data = $tmp[0];
			$this->id = $data->id;
			$this->answer_id = $data->answer_id;
			$this->title['sk'] = $data->title_sk;
			$this->title['en'] = $data->title_en;
			$this->time = isset($data->time) ? $data->time : 30;
			$this->datetime_start = isset($data->datetime_start) ? $data->datetime_start : null;
			$this->answers_count = $data->answers_count;
			if ( $this->answers_count > 1 )
			{
				$q = dibi::query('SELECT * FROM `answer` WHERE `question_id` = %i', $this->id);

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
				$this->answers[] = array('id' => $data['answer_id'], 'value' => $data['answer_value'], 'correct' => $data['answer_correct']);
			}
			
		}
		

		public function createForm ()
		{
			$form = new AppForm($this->presenter, 'qform');
			$form->renderer->clientScript = NULL;
			$elm = $form->getElementPrototype();
			$elm->attrs['id'] = 'qform';
			
			// $name = $form['name']->getControlPrototype();
			
			$title = ( $this->title['sk'] && $this->title['en'] ) ? $this->title['sk'] . ' / ' .  $this->title['en'] : ( ( $this->title['sk'] ) ? $this->title['sk'] : $this->title['en']);
			$group = $form->addGroup($title);

			$user = Environment::getUser();
			$user_data_src = dibi::query('SELECT * FROM user_answer WHERE `user_id` = %i AND `quiz_id` = %i AND `question_id` = %i', $user->getIdentity()->id, $this->presenter->id, $this->id);
			$user_data = $user_data_src->fetch();
			
			if ( $this->answers_count > 1 )
			{
				$user_answers = explode(';', $user_data['value']);
				foreach( $this->answers as $answer)
				{
					$form->addCheckbox('answer' . $answer['id'], $answer['value']);
					$group->add($form['answer' . $answer['id']]);
					if ( $user_data_src->count() == 1)
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
				$form->addText('answer' . $this->answer_id, "User answer")->addRule(Form::FILLED, 'Not filled answer.');
				$form->addText('correctAnswer', "Correct answer")->setDisabled();
				$group->add($form['answer' . $this->answer_id]);
				if ( $user_data_src->count() == 1)
				{
					$form['answer' . $this->answer_id]->setDisabled();
					$form['answer' . $this->answer_id]->setValue(stripslashes($user_data->value));
				}

			}

			$form->addHidden('quid')->setValue($this->id);
			if ( $user_data_src->count() == 1)
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
			try	{
				if ( $this->id == $form['quid']->getValue() && strtotime($this->datetime_start) + $this->time >= strtotime("now") )
				{
					$user = Environment::getUser();
					$user_answer = false;
	
					if ( $this->answers_count > 1 )
					{
						foreach( $this->answers as $answer)
						{
							if ( $form['answer' . $answer['id']]->value || isset($_REQUEST['answer' . $answer['id']]) )
							{
								$user_answer .= $answer['id'] . ';';
							}
	
						}

						$user_answer = substr($user_answer, 0, -1);
					}
					elseif ( $form['answer' . $this->answer_id]->getValue() != "" )
					{
						$user_answer = $form['answer' . $this->answer_id]->getValue();
					}

					if ( $user_answer )
					{
						try	{
							dibi::query('INSERT INTO `user_answer` (`user_id`, `quiz_id`, `question_id`, `value`, `time`) VALUES ( %i, %i, %i, %s, NOW() )', $user->getIdentity()->id, $this->presenter->id, $this->id, addslashes($user_answer) );
							$this->presenter->redirect('Quiz:');
						} catch (DibiDriverException $e) {
							if ( $e->getCode() == 1062 )
							{
								$form->addError("Answer has been submited");
							}
							else
							{
								$form->addError($e->getMessage());
							}
						}
					}

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

