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
		public $id, $title, $answers, $answers_count, $scope, $used, $time;
		public $form, $presenter;
		private $quiz;
		
		#	Constructor
		function __construct ($presenter, $quiz, $src )
		{
			$this->presenter = $presenter;
			
			parent::__construct();
			$this->quiz = $quiz;
			$this->bindData($src);
			$this->createForm();
		}
		###	
		
		public function bindData ($src)
		{
			$tmp = $src->fetchAll();
			$data = $tmp[0];
			$this->id = $data->id;
			$this->title['sk'] = $data->title_sk;
			$this->title['en'] = $data->title_en;
			$this->time = $data->time;
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
			$form = new AppForm($this->quiz, 'question');
			
			$title = ( $this->title['sk'] && $this->title['en'] ) ? $this->title['sk'] . ' / ' .  $this->title['en'] : ( ( $this->title['sk'] ) ? $this->title['sk'] : $this->title['en']);
			$group = $form->addGroup($title);
	
			if ( $this->answers_count > 1 )
			{
				foreach( $this->answers as $answer)
				{
					$form->addCheckbox('answer' . $answer['id'], $answer['value']);
					$group->add($form['answer' . $answer['id']]);

					if ( $answer['correct'] )
					{
					}
				}
			}
			else
			{
				$form->addText('answer' . $answer['id']);
				$group->add($form['answer' . $answer['id']]);
			}

			$form->addSubmit('send', 'Send');
			$form->onSubmit[] = array($this, 'questionFormSubmitted');
			$this->form = $form;
		}
		
		public function questionFormSubmitted ($form)
		{
			if ( $this->answers_count > 1 )
			{
				foreach( $this->answers as $answer)
				{
					if ( $answer['correct'] )
					{
						// $form['answer' . $answer['id']];
						// Debug::dump($answer['correct']);
						// $form['answer' . $answer['id']]->checked = true;
						
					}
	
					// $form['answer' . $answer['id']]->setDisabled();

				}
				
				$form->offsetUnset('send');
				$form->addSubmit('next', 'Wait')->setDisabled();
			}
			else
			{
				
				// $group->add($form['answer' . $answer['id']]);
			}
			
			
		}
		
		public function render ()
		{
			$template = $this->createTemplate();
			$template->question = $this;
			$template->form = $this->form;
			// render
			$template->useAjax = $this->useAjax;
			$template->setFile(dirname(__FILE__) . '/question.phtml');
			$template->registerFilter('Nette\Templates\CurlyBracketsFilter::invoke');
			$template->render();
		}
	}
	###

?>