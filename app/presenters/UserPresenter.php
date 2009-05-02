<?php

class UserPresenter extends BasePresenter
{
	private $title;
	public $backlink = '';
	
	public function startup ()
	{
		$this->title = title . ' / User';
		$user = Environment::getUser();
		parent::startup();
	}

	public function renderDefault()
	{
	}

	public function actionRegistration ()
	{
		$this->title .= ' / Registration';

		$form = new AppForm($this, 'form');
		$form->addText('nick', 'Nick:');

		$form->addText('email', 'E-mail:')
			->addRule(Form::FILLED, 'Please provide a email.');

		$form->addPassword('password', 'Password:')
			->addRule(Form::FILLED, 'Please provide a password.');

		$form->addSubmit('register', 'Register');
		$form->onSubmit[] = array($this, 'registrationFormSubmitted');

		$form->addProtection('Please submit this form again (security token has expired).');

		$this->template->form = $form;
	}

	public function actionLogout ()
	{
		$user = Environment::getUser();
		$user->signOut();
		$this->flashMessage('Your logout has been successful.');
		$this->getApplication()->restoreRequest($this->backlink);
		$this->redirect('User:');
	}
	
	public function actionLogin ()
	{
		$this->title .= ' / Login';

		$form = new AppForm($this, 'form');
		$form->addText('email', 'E-mail:')
			->addRule(Form::FILLED, 'Please provide a email.');

		$form->addPassword('password', 'Password:')
			->addRule(Form::FILLED, 'Please provide a password.');

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
				$user = Environment::getUser();
				$user->authenticate("", $pass, array('email' => $email));
				$this->flashMessage('Your login has been successful.');
				$this->getApplication()->restoreRequest($this->backlink);
				$this->redirect('User:');
			} catch (AuthenticationException $e) {
				$form->addError($e->getMessage());
			}

		} catch (FormValidationException $e) {
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
				dibi::begin('registration');
				dibi::query('INSERT INTO user (`nick`, `email`, `password`, `datetime_register`) VALUES
					(%s, %s, %s, NOW() )', $nick, $email, $pass);
				
				try {
					$user = Environment::getUser();
					$user->authenticate($nick, $pass, array('email' => $email));
					$this->flashMessage('Your registration has been successful.');
					$this->getApplication()->restoreRequest($this->backlink);
					
					dibi::commit('registration');
					$this->redirect('User:');
				} catch (AuthenticationException $e) {
					$form->addError($e->getMessage());
				}
				
				dibi::rollback('registration');
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