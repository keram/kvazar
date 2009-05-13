<?php

	#doc
	#	classname:	Question
	#	scope:		PUBLIC
	#
	#/doc
	
	class Question extends NControl
	{
		#	internal variables
		public $useAjax = true;
		public $id;
		public $config, $public_config;
		public $form;
		public $presenter;
		
		#	Constructor
		function __construct ( $presenter, $id = null)
		{
			parent::__construct();
			
			$this->id = $id;
			
			if ( $presenter )
			{
				$this->presenter = $presenter;
				$this->getConfig($id);
				
				if ( isset($this->config) )
				{
					$this->setPublicConfig();
					$this->createForm();
				}
			}
		}
		###
		
		public function setPublicConfig ()
		{
			$this->public_config = array( 
				"id" => $this->config['id'],
				"order" => $this->config['order'],
				"remaining_time" => $this->config['remaining_time'],
				"response_time" => $this->config['response_time'],
				"datetime_start" => $this->config['datetime_start'],
				"num_hints"	=> $this->config['num_hints'],
				"type" 	=> $this->config['type']
			);
		}
		
		public function getConfig ( $id = null )
		{
			$cache = NEnvironment::getCache();
			$title = "question-" . (( $id == null ) ? $this->presenter->quiz['made_questions'] : $id);

			if ( isset($cache[$title]) )
			{
				$this->config = $cache[$title];
				// $this->config['remaining_time'] = $this->config['response_time'] - ( $this->presenter->system_time - min( $this->presenter->system_time, $this->config['datetime_start']) );
				$this->config['remaining_time'] = $this->config['response_time'] - ( $this->presenter->system_time - $this->config['datetime_start']);
			}
			elseif ( $id != null ) 
			{
				return false;
			}
			else
			{
				try	{
					// $src_new_question = dibi::getConnection()->dataSource('SELECT t1.id, t1.response_time FROM `question` AS t1 WHERE t1.id  = 2');
					dibi::begin();
					
					$src_new_question = dibi::getConnection()->dataSource('SELECT t1.id, t1.response_time FROM `question` AS t1 WHERE t1.id NOT IN ( SELECT t3.question_id FROM `quiz_has_question` AS t3 WHERE `quiz_id` = %i ) ORDER BY RAND() ASC LIMIT 1', $this->presenter->quiz['id']);

					if ( !$src_new_question->count() )
					{
						throw new Exception("Question not found");
					}
					
					$tmp = $src_new_question->fetch();

					dibi::query('INSERT INTO `quiz_has_question` (`quiz_id`, `question_id`, `datetime_start`, `order`) VALUES ( %i, %i, NOW() + INTERVAL 3 second, %i )', $this->presenter->quiz['id'], $tmp->id, $this->presenter->quiz['made_questions']);
					// dibi::query('INSERT INTO `quiz_has_question` (`quiz_id`, `question_id`, `datetime_start`, `order`) VALUES ( %i, %i, NOW(), %i )', $this->presenter->quiz['id'], $tmp->id, $this->presenter->quiz['made_questions']);

					$src_question = dibi::getConnection()->dataSource('SELECT t1.question_id AS `id`, t1.datetime_start, 
						t2.id AS `question_id`, t2.title_sk, t2.title_en, t2.response_time, 
						t3.id AS `answer_id`, t3.correct AS `answer_correct`, t3.value AS `answer_value`, COUNT(t3.id) AS `answers_count` 
					 FROM `quiz_has_question` AS t1
						 LEFT JOIN `question` AS t2 ON t1.question_id = t2.id
						 LEFT JOIN `answer` AS t3 ON t2.id = t3.question_id
					 WHERE t1.quiz_id = %i AND t1.question_id = %i GROUP BY t3.question_id', $this->presenter->quiz['id'], $tmp->id);

					$this->bindConfig($src_question);
					dibi::commit();
				} catch ( Exception $e ) {
					dibi::rollback();
					$session = NEnvironment::getSession('question');
					if ( $this->presenter->quiz['made_questions'] == 0 || $this->presenter->quiz['made_questions'] >= $session->config['order'] )
					{
						sleep(2);
						getConfig($this->presenter->quiz['made_questions']);
					}
				}
			}
			
			return false;
		}
		
		public function bindConfig ($src)
		{
			$tmp = $src->fetchAll();
			$data = $tmp[0];
			$this->config['id'] = $data->id * 1;
			$this->config['answer_id'] 		= $data->answer_id;
			$this->config['title']['sk'] 	= $data->title_sk;
			$this->config['title']['en'] 	= $data->title_en;
			$this->config['response_time']	= $data->response_time;
			$this->config['datetime_start'] = strtotime($data->datetime_start);
			$this->config['remaining_time'] = $this->config['response_time'] - ( $this->presenter->system_time - min( $this->presenter->system_time, $this->config['datetime_start']) );
			$this->config['answers_count'] 	= $data->answers_count;
			$this->config['type'] = "simple";
			$this->config['order'] = $this->presenter->quiz['made_questions'];
			$this->config['hints'] = array();
			
			if ( $this->config['answers_count'] > 1 )
			{
				$this->config['type'] = "multi";
				$q = dibi::query('SELECT * FROM `answer` WHERE `question_id` = %i ORDER BY RAND()', $this->config['id']);
				if ( $q->count() )
				{

					$this->config['num_hints'] = $this->config['answers_count'] - 2;
					$d = $q->fetchAll();
					
					foreach( $d as $k )
					{
						$this->config['answers'][] = array('id' => $k['id'], 'value' => stripslashes($k['value']), 'correct' => $k['correct']);
						if ( $k['correct'] == 0 &&  count($this->config['hints']) < $this->config['num_hints'] )
						{
							$this->config['hints'][] = $k['id'];
						}
					}
				}
			}
			else
			{
				$this->config['answers'][] = array('id' => $data['answer_id'], 'value' => stripslashes($data['answer_value']), 'correct' => $data['answer_correct']);

				$this->config['num_hints'] = ( strlen($this->config['answers'][0]['value']) >= 2 ) ? min(strlen($this->config['answers'][0]['value']), 4) - 1 : 0;
				
				$str = $this->config['answers'][0]['value'];
				$full_str = str_split($str);
				$expl_str = $full_str;
				$chars_hint = floor( strlen($str) / ( $this->config['num_hints'] + 1));
				$visited = array();

				for ( $j=0; $j < $this->config['num_hints']; $j++ )
				{ 
					$hint_str = "";
					if ( is_array($expl_str) && count($expl_str) > $chars_hint )
					{
						// vyberiem x prvkov z pola ktore este neboli
						$rand = array_rand($expl_str, $chars_hint);
						if ( is_array($rand) )
						{
							$new_array = array_merge($rand, $visited);
						}
						else
						{
							$new_array = array_merge(array($rand), $visited);
						}
						
						for ( $i=0,$c=count($full_str); $i < $c; $i++ )
						{ 
							if ( in_array($i, $new_array) )
							{
								$hint_str .= $full_str[$i];
								$visited[] = $i;
								unset($expl_str[$i]);
							}
							else
							{
								$hint_str .= "_";
							}
						}
					}
					$this->config['hints'][] = $hint_str;
				}
			}
			
			$cache = NEnvironment::getCache();

			// + 10 sekund pre odpoved
			$cache->save('question-' . ( $this->presenter->quiz['made_questions']), $this->config, array('expire' => time() + $this->config['response_time'] + 10));
		}
	
		public function createForm ()
		{
			$form = $this->presenter->getComponent('qform');
			$form->renderer->clientScript = NULL;

			$elm = $form->getElementPrototype();
			$elm->attrs['id'] = 'qform';
			
			$title = ( $this->config['title']['sk'] && $this->config['title']['en'] ) ? $this->config['title']['sk'] . ' / ' .  $this->config['title']['en'] : ( ( $this->config['title']['sk'] ) ? $this->config['title']['sk'] : $this->config['title']['en']);
			$group = $form->addGroup(stripslashes($title));
			
			$submitted = 0;
			$user = NEnvironment::getUser();
			$user_data_src = dibi::query('SELECT * FROM user_answer WHERE `user_id` = %i AND `quiz_id` = %i AND `question_id` = %i ORDER BY `time` DESC LIMIT 1', $user->getIdentity()->id, $this->presenter->quiz['id'], $this->config['id']);
			$user_data = $user_data_src->fetch();

			if ( $this->config['answers_count'] > 1 )
			{
				$user_answers = explode(';', $user_data['value']);

				foreach( $this->config['answers'] as $answer)
				{
					$form->addCheckbox('answer' . $answer['id'], $answer['value'])->getControlPrototype()->value($answer['id']);
					
					$group->add($form['answer' . $answer['id']]);
					
					if ( $user_data )
					{
						$submitted = 1;
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
				$form->addText('useranswer', "User answer")->addRule(NForm::FILLED, 'Not filled answer.');
				$form->addText('answer', "");
				$group->add($form['useranswer']);
				$form['useranswer']->getControlPrototype()->autocomplete("off");
				// $form['useranswer']->attrs["autocomplete"] = "disabled";
				if ( $user_data )
				{
					$form['useranswer']->setValue(stripslashes($user_data->value));
				}
			}
			
			if ( $this->config['type'] == "multi" && $user_data )
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
			$session = NEnvironment::getSession('question');
			try	{
				$user = NEnvironment::getUser();
				$user_answer = false;
				$valid = 1;
				$points = 0;
					
				if ( $this->config['answers_count'] > 1 )
				{
					// $this->answers[]
					foreach( $this->config['answers'] as $answer )
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
					$session = NEnvironment::getSession('question');

					if ( $session->submitted == 1 )
					{
						$form->addError("Answer has been submited");
						$valid = 0;
					}
				}
				elseif ( $form['useranswer'] != "" )
				{
					$user_answer = $form['useranswer']->getValue();
					// todo toto este otestovat poriadne a do buducna pridat multijazycnost
					$ustr = NString::webalize($user_answer);
					$ostr = NString::webalize($this->config['answers'][0]["value"]);

					// if ( $ustr == $ostr || ( strlen($ostr) > 5 && strlen($ustr) == strlen($ostr) && $ustr[0] == $ostr[0] && levenshtein($ustr, $ostr) < 2) ) 
					// todo ci nezachovat radsej kiss? ..ano radsej kiss ako komplikovat dotazy do db 
					if ( $ustr === $ostr ) 
					{
						$points = 1;
					}
				}

				if ( $user_answer && $valid )
				{
					try	{
						dibi::query('INSERT INTO `user_answer` (`user_id`, `quiz_id`, `question_id`, `value`, `time`, `points`) VALUES ( %i, %i, %i, %s, NOW(), %i )', $user->getIdentity()->id, $this->presenter->quiz['id'], $this->config['id'], addslashes($user_answer), $points );

						if ( $this->config['type'] == "multi" )
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

				if ( !$this->presenter->isAjax() )
				{
					$this->presenter->redirect('Quiz:');
				}
			// TODO vyhod question exception
			} catch ( QuestionException $e ) {
				NDebug::dump("nieje tu vynimka nahodou?");
			}
		}
		
		
		public function render ()
		{

			$template = $this->createTemplate();
			$template->question = $this->config;
			$template->form = $this->form;
			// renderf
			$template->useAjax = $this->useAjax;
			$template->setFile(dirname(__FILE__) . '/Question.phtml');
			$template->registerFilter('Nettep\Templates\NCurlyBracketsFilter::invoke');
			$template->render();

			return false;	
		}
	}
	###

