<?php
	
	#doc
	#	classname:	Chart
	#	scope:		PUBLIC
	#
	#/doc
	
	class Chart extends NControl 
	{
		#	internal variables
		public $useAjax = false;
		public $quiz, $users, $questions;
		public $data;
		
		#	Constructor
		function __construct ( $quiz )
		{
			$this->quiz = $quiz;
			$this->initContent();
		}
		###	
		
		
		public function initContent ()
		{
			$q = dibi::query('SELECT t1.user_id, t1.quiz_id, SUM(t1.max_points) AS `max_points`, t3.nick FROM (SELECT t2.user_id, t2.question_id, MAX(points) AS max_points, t2.quiz_id FROM user_answer t2 WHERE t2.quiz_id = %i GROUP BY user_id, question_id ORDER BY time ) AS t1 INNER JOIN `user` AS t3 ON t1.user_id = t3.id GROUP BY t1.user_id ORDER BY max_points DESC', $this->quiz['id']);
			$r = $q->fetchAll();
			
			$this->data = $r;
		}

		public function getWinner($data)
		{
			$winner = null; 
	
			if ( count($data) != 0  ) 
			{
				$first = $data[0]['max_points'];
				if ( $first != 0 )
				{
					$winner = $data[0];
				}
			}
	
			return $winner;
		}


		public function render ()
		{
			$template = $this->createTemplate();
			$user =  NEnvironment::getUser();

			if ( $this->quiz['datetime_end'] && $this->quiz['datetime_end']  != "0000-00-00 00:00:00" )
			{
				$winner = $this->getWinner($this->data);
				$template->winner = $winner;
			}
			
			$template->user = $user;
			$template->data = $this->data;
			// renderf
			$template->useAjax = $this->useAjax;
			$template->setFile(dirname(__FILE__) . '/chart.phtml');
			$template->registerFilter('Nettep\Templates\NCurlyBracketsFilter::invoke');
			$template->render();
		}

	
	}
	###
	
?>