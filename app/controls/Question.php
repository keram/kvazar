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
		
		public $id, $title, $answers, $answers_count, $scope, $used;
		
		#	Constructor
		function __construct ( $src )
		{
			$this->bindData($src);
		}
		###	
		
		public function bindData ($src)
		{
			$tmp = $src->fetchAll();
			$data = $tmp[0];
			$this->id = $data->id;
			$this->title['sk'] = $data->title_sk;
			$this->title['en'] = $data->title_en;
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
		
		public function render ()
		{
			$template = $this->createTemplate();
			$template->question = $this;
			// render
			$template->useAjax = $this->useAjax;
			$template->setFile(dirname(__FILE__) . '/question.phtml');
			$template->registerFilter('Nette\Templates\CurlyBracketsFilter::invoke');
			$template->render();
		}
	}
	###

?>