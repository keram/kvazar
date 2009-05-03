<?php

/**
 * My Application
 *
 * @copyright  Copyright (c) 2009 John Doe
 * @package    MyApplication
 * @version    $Id: BasePresenter.php 182 2008-12-31 00:28:33Z david@grudl.com $
 */



/**
 * Base class for all application presenters.
 *
 * @author     John Doe
 * @package    MyApplication
 */
abstract class BasePresenter extends Presenter
{
	public $user;

	public function startup ()
	{
		$this->user = Environment::getUser();
		if ( $this->user->isAuthenticated() ) {
			dibi::query('UPDATE `logged` SET `datetime_last_action` = NOW() WHERE user_id = %i', $this->user->getIdentity()->id);
			
			// $logged_users = dibi::query('SELECT t1.*, t2.* FROM `user` AS t1 LEFT JOIN `logged` AS t2 ON t1.id = t2.user_id WHERE t2.datetime_last_action > NOW() - INTERVAL 15 MINUTE');
			$db  = dibi::getConnection();
			$src = $db->dataSource('SELECT t1.nick, t1.email, t2.datetime_last_action FROM `user` AS t1 LEFT JOIN `logged` AS t2 ON t1.id = t2.user_id WHERE t2.datetime_last_action > NOW() - INTERVAL 15 MINUTE');
			$logged_users = new LoggedUsers;
			$logged_users->bindDataTable($src);
			$this->addComponent($logged_users, 'lu');
			$this->template->logged_users = $logged_users;
		}
		
		$this->template->user = $this->user;
	}

	/**
	 * @return ITemplate
	 */
	protected function createTemplate()
	{
		$template = parent::createTemplate();
		$template->registerFilter('Nette\Templates\CurlyBracketsFilter::invoke');
		return $template;
	}

}
