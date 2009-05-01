<?php

class QuestionPresenter extends BasePresenter
{
	private $title;
	public $backlink = '';

	public function startup ()
	{
		$this->title = title . ' / Question';
		$user = Environment::getUser();
		parent::startup();
	}
	
	public function actionDefault ()
	{
		$form = new AppForm($this, 'newform');
		$form->addText('title_sk', 'Title (sk):');
		$form->addText('title_en', 'Title (en):');
		$form->addText('answer_1', 'Answer:')
			->addRule(Form::FILLED, 'Please provide correct answer.');
		// $form['correct_1']->checked = true;
		// $form['correct_1']->disabled = true;
		$form->addText('answer_2', 'Answer:');
		$form->addCheckbox('correct_2', 'Correct');
		$form->addText('answer_3', 'Answer:');
		$form->addCheckbox('correct_3', 'Correct');
		$form->addText('answer_4', 'Answer:');
		$form->addCheckbox('correct_4', 'Correct');
		$form->addText('answer_5', 'Answer:');
		$form->addCheckbox('correct_5', 'Correct');

		$form->addSubmit('create', 'Create');

		$form->setDefaults(array(
			'type' => 'simple',
			'correct_1' => '1',
		));

		$form->onSubmit[] = array($this, 'newQuestionFormSubmitted');
		$this->template->new_form = $form;
	}
	
//   `title_en` TEXT NULL ,
//   `title_sk` TEXT NULL ,
//   `datetime_create` DATETIME NOT NULL ,
//   `datetime_approved` DATETIME NULL ,
//   `state` ENUM('created', 'approved') NULL DEFAULT 'approved' ,

	public function newQuestionFormSubmitted ($form)
	{
		// todo pridaj salt, a validation
		try {
			
			if ( trim($form['title_sk']->getValue()) == "" && trim($form['title_en']->getValue()) == "" )
			{
				 throw new EmptyTitleException();
			}
			
			if ( trim($form['answer_1']->getValue()) == "" )
			{
				 throw new EmptyCorrectAnswerException();
			}
			
			
//			dibi::begin('question');
			$_q_data = array( 
				'title_sk' => addslashes(trim($form['title_sk']->getValue())), 
				'title_en' => addslashes(trim($form['title_en']->getValue())), 
				'state'    => 'approved',
				'datetime_create' => new DibiVariable('NOW()', 'sql')
			);

			$_q = new Questions();
			$_a = new Answers();
			$_q_id = $_q->insert($_q_data);
			
			$_a_data[] = array( 
				'value'		 => addslashes(trim($form['answer_1']->getValue())), 
				'correct'    => '1',
				'question_id' => $_q_id
			);
			
			for ( $i=1; $i < 5; $i++ )
			{ 
				if ( trim($form['answer_' . $i]->getValue()) != ""  )
				{
					$_a_data[] = array( 
						'value'		 => addslashes(trim($form['answer_' . $i]->getValue())), 
						'correct'    => $form['correct_' . $i]->getValue(),
						'question_id' => $_q_id
					);
				} 
				else
				{
					break;
				}
			}
			
			for ( $i=0; $i < count($_a_data); $i++ )
			{ 
				$_a->insert($_a_data[$i]);
			}
			
			
			dibi::commit('question');
			/*
			CREATE  TABLE IF NOT EXISTS `kvazar`.`answer` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `correct` TINYINT(1) NOT NULL DEFAULT 1 ,
  `value_sk` VARCHAR(128) NULL ,
  `value_en` VARCHAR(128) NULL ,
  `question_id` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`, `question_id`) ,

			
			*/
			// try {
			// 	$user = Environment::getUser();
			// 	$user->authenticate("", $pass, array('email' => $email));
			// 	$this->flashMessage('Your login has been successful.');
			// 	$this->getApplication()->restoreRequest($this->backlink);
			// 	$this->redirect('User:');
			// } catch (AuthenticationException $e) {
			// 	$form->addError($e->getMessage());
			// }

		} catch (FormValidationException $e) {
			$form->addError($e->getMessage());
		}
	}

	public function beforeRender ()
	{
		$this->template->title = $this->title;
	}
}


?>