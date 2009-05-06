<?php
	
	#doc
	#	classname:	Chart
	#	scope:		PUBLIC
	#
	#/doc
	
	class Chart extends Control
	{
		#	internal variables
		public $useAjax = false;
		public $quiz_id, $users, $questions;
		
		
		#	Constructor
		function __construct ( $quiz_id )
		{
			$this->quiz_id = $quiz_id;
			// TODO napojit na model
			$this->initContent();
		}
		###	
		
		
		public function initContent ()
		{
			$tmp = dibi::test('SELECT t1.*, t2.* FROM `user` AS t1 RIGHT JOIN `user_answer` AS t2 ON t1.id = t2.user_id WHERE t2.quiz_id = %i', $this->quiz_id);
			// Debug::dump($tmp->fetchAll());
			
		}

		public function render ()
		{
			$template = $this->createTemplate();
			// renderf
			$template->useAjax = $this->useAjax;
			$template->setFile(dirname(__FILE__) . '/chart.phtml');
			$template->registerFilter('Nette\Templates\CurlyBracketsFilter::invoke');
			$template->render();
		}

	
	}
	###
	
?>