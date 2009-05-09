<?php

/**
 * My Application
 *
 * @copyright  Copyright (c) 2009 John Doe
 * @package    MyApplication
 * @version    $Id: HomepagePresenter.php 182 2008-12-31 00:28:33Z david@grudl.com $
 */



/**
 * Homepage presenter.
 *
 * @author     John Doe
 * @package    MyApplication
 */
class HomepagePresenter extends BasePresenter
{

	public function renderDefault()
	{
		$this->template->title = title;
	}

	public function handleLoggedUsers ()
	{
		$this->getComponent('lu')->invalidateControl('logged_users');
	}

}
