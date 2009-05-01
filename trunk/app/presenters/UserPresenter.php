<?php

class UserPresenter extends BasePresenter
{
	private $title;
	public $backlink = '';
	
	public function startup ()
	{
		$this->title = title . ' / User';
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
	
	public function registrationFormSubmitted ($form)
	{
		// todo pridaj salt, a validation
		try {
			$nick	= addSlashes($form['nick']->getValue());
			$email	= addSlashes($form['email']->getValue());
			$pass 	= sha1($form['password']->getValue());
			
			try	{
				dibi::query('INSERT INTO user (`nick`, `email`, `password`, `datetime_register`) VALUES
					(%s, %s, %s, NOW() )', $nick, $email, $pass);
				
				try {
					$user = Environment::getUser();
					$user->authenticate($email, $pass);
					$this->getApplication()->restoreRequest($this->backlink);
					$this->redirect('Homepage:');
				} catch (AuthenticationException $e) {
					$form->addError($e->getMessage());
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
			// 
			// $this->getApplication()->restoreRequest($this->backlink);
			// $this->redirect('Dashboard:');

		} catch (FormValidationException $e) {
			$form->addError($e->getMessage());
		}
	}

	public function actionLogin ()
	{
		$this->title .= ' / Login';
	}
	
	public function beforeRender ()
	{
		$this->template->title = $this->title;
	}
}


?>