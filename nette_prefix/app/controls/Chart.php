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
		
		
		#	Constructor
		function __construct ( $quiz )
		{
			$this->quiz = $quiz;
		}
		###	
		
		
		public function initContent ()
		{
			$q = dibi::query('SELECT t1.*, SUM(t1.points) AS `sum`, t2.nick FROM `user_answer` AS t1 INNER JOIN `user` AS t2 ON t1.user_id = t2.id WHERE `t1.quiz_id` = %i GROUP BY t1.user_id ORDER BY `sum` DESC', $this->quiz['id']);
			$r = $q->fetchAll();

			
			return $r;
		}

		public function getWinner($data)
		{
			$array = array();
			$winner = null; 
	
			if ( count($data) != 0  ) 
			{
				$prev = $data[0]['sum'];
				if ( $prev != 0 )
				{
					foreach( $data as $winner )
					{
						if ( $prev == $winner['sum'] )
						{
							$array[] = $winner;
						}
					}
	
	
					if ( count($array) > 1 )
					{
						$ids = array();
						foreach( $array as $winner )
						{
							$ids[] = $winner['user_id'];
						}
	
						$q = dibi::query('SELECT SUM(`t1.time`) AS `sum_time`, `t1.user_id`, t2.* FROM user_answer AS t1 INNER JOIN `user` AS t2 ON `t1.user_id` = `t2.id`  WHERE points != 0 AND `user_id` IN ( ' . implode(", ", $ids) . ') GROUP BY `user_id` ORDER BY `sum_time` ASC LIMIT 1' ); 
						$winner = $q->fetch();
					}
					else
					{
						$winner = $data[0];
					}
				}
			}
	
			return $winner;
		}


		public function render ()
		{
			$template = $this->createTemplate();
			$user =  NEnvironment::getUser();
			$data = $this->initContent();
			if ( $this->quiz['datetime_end'] && $this->quiz['datetime_end']  != "0000-00-00 00:00:00" )
			{
				$winner = $this->getWinner($data);
				$template->winner = $winner;
			}
			
			$template->user = $user;
			$template->data = $data;
			// renderf
			$template->useAjax = $this->useAjax;
			$template->setFile(dirname(__FILE__) . '/chart.phtml');
			$template->registerFilter('Nettep\Templates\NCurlyBracketsFilter::invoke');
			$template->render();
		}

	
	}
	###
	
?>