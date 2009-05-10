<?php

class UserPresenter extends BasePresenter
{
	private $title;
	public $backlink = '';
	
	public function startup ()
	{
		$this->title = title . ' / User';
		$user = NEnvironment::getUser();
		parent::startup();
	}

	public function renderDefault()
	{
	}

	public function actionRegistration ()
	{
		$this->title .= ' / Registration';

		$form = new NAppForm($this, 'form');
		$form->addText('nick', 'Nick:');

		$form->addText('email', 'E-mail:')
			->addRule(NForm::FILLED, 'Please provide a email.');

		$form->addPassword('password', 'Password:')
			->addRule(NForm::FILLED, 'Please provide a password.');

		$form->addSubmit('register', 'Register');
		$form->onSubmit[] = array($this, 'registrationFormSubmitted');

		$form->addProtection('Please submit this form again (security token has expired).');

		$this->template->form = $form;
	}

	public function actionLogout ()
	{
		if ( $this->user->isAuthenticated() )
		{
			dibi::query('DELETE  FROM logged WHERE `user_id` = %i', $this->user->getIDentity()->id);
			$this->flashMessage('Your logout has been successful.');
			$this->user->signOut();
		}

		$this->redirect('Homepage:');
	}
	
	public function actionLogin ($backlink)
	{
		$this->title .= ' / Login';

		$form = new NAppForm($this, 'form');
		$form->addText('email', 'E-mail:')
			->addRule(NForm::FILLED, 'Please provide a email.');

		$form->addPassword('password', 'Password:')
			->addRule(NForm::FILLED, 'Please provide a password.');

		$form->addSubmit('login', 'Login');
		$form->onSubmit[] = array($this, 'loginFormSubmitted');

		$form->addProtection('Please submit this form again (security token has expired).');
		
		$this->template->form = $form;
	}

	public function loginFormSubmitted ($form)
	{
		// todo pridaj salt, a validation
		try {
			$email	= addslashes($form['email']->getValue());
			$pass 	= sha1($form['password']->getValue());

			try {
				$this->user->authenticate("", $pass, array('email' => $email));
				if ( $this->user->isAuthenticated() )
				{
					dibi::query('DELETE FROM logged WHERE `user_id` = %i', $this->user->getIdentity()->id);
					dibi::query('INSERT INTO logged (`user_id`, `datetime_logged`, `datetime_last_action`) VALUES
								(%i, NOW(), NOW() )', $this->user->getIdentity()->id);

					$this->flashMessage('Your login has been successful.');
					$this->redirect('Quiz:');
				}
			} catch (NAuthenticationException $e) {
				$form->addError($e->getMessage());
			}

			// dibi::query('DELETE FROM logged WHERE `datetime_last_action` < NOW() - INTERVAL 15 MINUTE');

		} catch (NFormValidationException $e) {
			$form->addError($e->getMessage());
		}
	}

	public function registrationFormSubmitted ($form)
	{
		// todo pridaj salt, a validation
		try {
			$nick	= addslashes($form['nick']->getValue());
			$email	= addslashes($form['email']->getValue());
			$pass 	= sha1($form['password']->getValue());
			
			try	{
				dibi::begin();
				dibi::query('INSERT INTO user (`nick`, `email`, `password`, `datetime_register`) VALUES
					(%s, %s, %s, NOW() )', $nick, $email, $pass);

				dibi::query('INSERT INTO logged (`user_id`, `datetime_logged`, `datetime_last_action`) VALUES
							(%i, NOW(), NOW() )', dibi::getInsertId());
				
				try {
					$user = NEnvironment::getUser();
					$user->authenticate($nick, $pass, array('email' => $email));
					$this->flashMessage('Your registration has been successful.');
					$this->getApplication()->restoreRequest($this->backlink);
					
					dibi::commit();
					$this->redirect('Quiz:');
				} catch (NAuthenticationException $e) {
					$form->addError($e->getMessage());
					dibi::rollback();
				}
				
			} catch (DibiDriverException $e) {
				if ( $e->getCode() == 1062 )
				{
					$form->addError('User exists');
				}
				else
				{
					$form->addError($e->getMessage());
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