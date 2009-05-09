<?php

class QuestionPresenter extends BasePresenter
{
	private $title;
	public $backlink = '';

	public function startup ()
	{
		parent::startup();

		$this->title = title . ' / Question';

		if ( !$this->user->isAuthenticated() ) {
			$this->flashMessage('Your must been logged.');
			$this->redirect('User:Login');
		}

	}
	
	public function actionDefault ()
	{
		$form = new AppForm($this, 'newform');
		$form->addText('title_sk', 'Title (sk):');
		$form->addText('title_en', 'Title (en):');
		$form->addText('answer_1', 'Answer (correct):')
			->addRule(Form::FILLED, 'Please provide correct answer.');
		// $form['correct_1']->checked = true;
		// $form['correct_1']->disabled = true;
		$form->addText('answer_2', 'Answer 2:');
		$form->addCheckbox('correct_2', 'Correct');
		$form->addText('answer_3', 'Answer 3:');
		$form->addCheckbox('correct_3', 'Correct');
		$form->addText('answer_4', 'Answer 4:');
		$form->addCheckbox('correct_4', 'Correct');
		$form->addText('answer_5', 'Answer 5:');
		$form->addCheckbox('correct_5', 'Correct');

		$form->addSubmit('create', 'Create');

		$form->setDefaults(array(
			'type' => 'simple',
			'correct_1' => '1',
		));

		$form->onSubmit[] = array($this, 'newQuestionFormSubmitted');
		$this->template->new_form = $form;
		
		$db  = dibi::getConnection();
		$src = $db->dataSource('SELECT t1.id, concat(t1.title_sk, " / ", t1.title_en) AS `question`, COUNT(t2.id) AS `answers` FROM `question` AS t1 LEFT JOIN `answer` AS t2 ON t1.id = t2.question_id GROUP BY t1.id');
		// $src = $db->dataSource('SELECT t1.*, COUNT(t2.id) AS `answers` FROM `question` AS t1 LEFT JOIN `answer` AS t2 ON t1.id = t2.question_id GROUP BY t1.id');
		$dataGrid = new DataGrid;
		$dataGrid->bindDataTable($src);
		$this->addComponent($dataGrid, 'dg');
		$this->template->dataGrid = $dataGrid;
	}
	
	public function newQuestionFormSubmitted ($form)
	{
		// TODO administracia
		try {
			
			if ( trim($form['title_sk']->getValue()) == "" && trim($form['title_en']->getValue()) == "" )
			{
				 throw new EmptyTitleException();
			}
			
			if ( trim($form['answer_1']->getValue()) == "" )
			{
				 throw new EmptyCorrectAnswerException();
			}
			
			
			$b = dibi::begin();
			$_q_data = array( 
				'title_sk' => addslashes(trim($form['title_sk']->getValue())), 
				'title_en' => addslashes(trim($form['title_en']->getValue())), 
				'state'    => 'approved',
				'datetime_create' => new DibiVariable('NOW()', 'sql'),
				'datetime_approved' => new DibiVariable('NOW()', 'sql')
			);

			$_q = new Questions();
			$_a = new Answers();
			$trans_state = true;
			$_q_id = $_q->insert($_q_data);
			
			if ( $_q_id )
			{
				$_a_data[] = array( 
					'value'		 => addslashes(trim($form['answer_1']->getValue())), 
					'correct'    => '1',
					'question_id' => $_q_id
				);
	
				for ( $i=2; $i < 5; $i++ )
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
				
				// zameisam poradie aby sa nedala podla id v db urcit spravna odpoved
				srand((float)microtime() * 1000000);
				shuffle($_a_data);
				
				for ( $i=0; $i < count($_a_data); $i++ )
				{ 

					if ( !$_a->insert($_a_data[$i]) )
					{
						$trans_state = false;
					}
					
				}
				
				if ( $trans_state )
				{
					dibi::commit();
					$this->flashMessage('Your question has been successful added.');
					$this->redirect('Question:');
				}
				else
				{
					dibi::rollback();
					$this->flashMessage("Your question hasn't been successful added.");
				}
			}
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