<?php

class QuestionPresenter extends BasePresenter
{
	private $title;
	public $backlink = '';
	private $model;
	static $scopes = array('general', 'art', 'sport', 'science', 'history', 'geography', 'society', 'logic', 'health');
	static $types = array('simple', 'multi');

	public function startup ()
	{
		parent::startup();

		$this->title = title . ' / Question';

		if ( !$this->user->isAuthenticated() ) {
			$this->flashMessage('Your must been logged.');
			$this->redirect('User:Login');
		}
		
		$this->model = new Questions();
	}
	
	public function actionDefault ()
	{
		$form = new NAppForm($this, 'newform');
		$form->addText('title_sk', 'Title (sk):');
		$form->addText('title_en', 'Title (en):');
		$form->addText('answer_1_sk', 'Answer (sk) (correct):');
		$form->addText('answer_1_en', '(en)');
		// $form['correct_1']->checked = true;
		// $form['correct_1']->disabled = true;
		$form->addText('answer_2_sk', 'Answer 2:');
		$form->addText('answer_2_en', '(en)');
		$form->addCheckbox('correct_2', 'Correct');
		$form->addText('answer_3_sk', 'Answer 3:');
		$form->addText('answer_3_en', '(en)');
		$form->addCheckbox('correct_3', 'Correct');
		$form->addText('answer_4_sk', 'Answer 4:');
		$form->addText('answer_4_en', '(en)');
		$form->addCheckbox('correct_4', 'Correct');
		$form->addText('answer_5_sk', 'Answer 5:');
		$form->addText('answer_5_en', '(en)');
		$form->addCheckbox('correct_5', 'Correct');
		$form->addRadioList('type', 'Typ', self::$types);
		$form->addMultiSelect('scope', 'Scope: ', self::$scopes);

		$form->addText('attachment_name', 'Attachment name');
		// $form->addText('attachment_title', 'Attachment title'); TODO 
		$form->addTextArea('attachment_value', 'Value:');
		$form->addSelect('attachment_type', 'Typ att.:', array("img" => "Image", "link" => "link to page", "mp3" => "mp3", 'youtube' => "video from youtube"));
		// $form->addTextArea('attachment_params', 'Att. parameters:'); TODO
		// $form->addFile('attachment_file', 'Att. file:');  TODO

		$form->addSubmit('create', 'Create');
		$form->setDefaults(array(
			'type' => '0',
			'correct_1' => '1',
		));

		$form->onSubmit[] = array($this, 'newQuestionFormSubmitted');
		$this->template->new_form = $form;
		
		$db  = dibi::getConnection();
		$src = $db->dataSource('SELECT t1.id, CONCAT(t1.title_sk, " / ", t1.title_en) AS `question`, t1.scope, COUNT(t2.id) AS `answers`, COUNT(t3.id) AS `attachments` FROM `question` AS t1 LEFT JOIN `answer` AS t2 ON t1.id = t2.question_id LEFT JOIN `question_attachment` AS t3 ON t1.id = t3.question_id GROUP BY t1.id');
		
		$dataGrid = new QuestionDataGrid;
		$dataGrid->bindDataTable($src);
		$this->addComponent($dataGrid, 'dg');
		$this->template->dataGrid = $dataGrid;
	}
	
	public function actionDetail ($id)
	{
		NDebug::dump($id);
	}
	
	public function newQuestionFormSubmitted ($form)
	{
		// TODO administracia
		try {
			
			if ( trim($form['title_sk']->getValue()) == "" && trim($form['title_en']->getValue()) == "" )
			{
				 throw new EmptyTitleException();
			}
			
			if ( trim($form['answer_1_en']->getValue()) == "" && trim($form['answer_1_sk']->getValue()) == "" )
			{
				 throw new EmptyCorrectAnswerException();
			}
			
			if ( trim($form['attachment_name']->getValue()) != "" && trim($form['attachment_value']->getValue()) == "" 
			 	|| trim($form['attachment_name']->getValue()) == "" && trim($form['attachment_value']->getValue()) != "" )
			{
				 throw new BadAttachmentFillException();
			}
			
			$trans_state = true;
			$b = dibi::begin();
			
			$_q_data = array( 
				'title_sk' 	=> addslashes(trim($form['title_sk']->getValue())), 
				'title_en' 	=> addslashes(trim($form['title_en']->getValue())), 
				'state'   	=> 'approved',
				'scope'   	=> implode(",", array_intersect_key(self::$scopes, array_flip($form['scope']->getValue()))),
				'type'		=> self::$types[$form['type']->getValue()],
				'datetime_create' => new DibiVariable('NOW()', 'sql'),
				'datetime_approved' => new DibiVariable('NOW()', 'sql')
			);
			
			$_q_id = $this->model->insert($_q_data);
			
			if ( $_q_id )
			{
				$_a_data[] = array( 
					'value_en' => addslashes(trim($form['answer_1_en']->getValue())), 
					'value_sk' => addslashes(trim($form['answer_1_sk']->getValue())), 
					'correct'    => '1',
					'question_id' => $_q_id
				);
				
				for ( $i=2; $i <= 5; $i++ )
				{
					if ( trim($form['answer_' . $i . '_en']->getValue()) != "" ||  trim($form['answer_' . $i . '_sk']->getValue()) != ""  )
					{
						$_a_data[] = array( 
							'value_en'	 => addslashes(trim($form['answer_' . $i . '_en']->getValue())), 
							'value_sk'	 => addslashes(trim($form['answer_' . $i . '_sk']->getValue())), 
							'correct'    => $form['correct_' . $i]->getValue(),
							'question_id' => $_q_id
						);
					} 
					else
					{
						break;
					}
				}
				
				if ( trim($form['attachment_name']->getValue()) != "" && trim($form['attachment_value']->getValue()) != "" )
				{
					$_att_data = array(
						'name'		 => addslashes(trim($form['attachment_name']->getValue())), 
						'value'		 => addslashes(trim($form['attachment_value']->getValue())), 
						'type'		 => addslashes(trim($form['attachment_type']->getValue())), 
						'question_id' => $_q_id
					);
					
					$trans_state  = $this->model->insert_attachment($_att_data);
				}
				
				if ( $trans_state )
				{
					// zameisam poradie aby sa nedala podla id v db urcit spravna odpoved
					srand((float)microtime() * 1000000);
					shuffle($_a_data);
					
					for ( $i=0; $i < count($_a_data); $i++ )
					{
						if ( !$this->model->insert_answer($_a_data[$i]) )
						{ // TODO prerobit model na jeden insert
							$trans_state = false;
						}
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
		} catch (NFormValidationException $e) {
			$form->addError($e->getMessage());
		}
	}
	
	public function beforeRender ()
	{
		$this->template->title = $this->title;
	}
}


?>