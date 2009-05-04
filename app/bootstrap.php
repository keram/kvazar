<?php

/**
 * My Application bootstrap file.
 *
 * @copyright  Copyright (c) 2009 John Doe
 * @package    MyApplication
 * @version    $Id: bootstrap.php 273 2009-04-15 02:05:53Z david@grudl.com $
 */



// Step 1: Load Nette Framework
// this allows load Nette Framework classes automatically so that
// you don't have to litter your code with 'require' statements

require LIBS_DIR . '/Nette/loader.php';
//require dirname(__FILE__) . '/../../../Nette/loader.php';



// Step 2: Configure environment
// 2a) enable Nette\Debug for better exception and error visualisation
Debug::enable();

// 2b) load configuration from config.ini file
Environment::loadConfig();

require_once LIBS_DIR . '/dibi/dibi.php';
dibi::connect(Environment::getConfig('database'));

$site = Environment::getConfig('site');
foreach($site as $key=>$val)
{
	define($key, $val);
}

// 2c) check if directory /app/temp is writable
if (@file_put_contents(Environment::expand('%tempDir%/_check'), '') === FALSE) {
	throw new Exception("Make directory '" . Environment::getVariable('tempDir') . "' writable!");
}

// 2d) enable RobotLoader - this allows load all classes automatically
$loader = new RobotLoader();
$loader->addDirectory(APP_DIR);
$loader->addDirectory(LIBS_DIR);
$loader->register();

// 2e) setup sessions
$session = Environment::getSession();
$session->setSavePath(APP_DIR . '/sessions/');



// Step 3: Configure application
// 3a) get and setup a front controller
$application = Environment::getApplication();
$application->errorPresenter = 'Error';
//$application->catchExceptions = TRUE;


$acl = new Permission();
$acl->addRole('guest');
$acl->addRole('user', 'guest');
$acl->addRole('admin', 'user');

$acl->addResource('quiz');
$acl->addResource('question');
$acl->addResource('homepage');
$acl->addResource('user');

$acl->allow('guest', 'homepage');
$acl->allow('guest', 'user');
$acl->allow('user', 'quiz');
$acl->allow('admin', 'question');



// Step 4: Setup application router
$router = $application->getRouter();

$router[] = new Route('index.php', array(
	'presenter' => 'Homepage',
	'action' => 'default',
), Route::ONE_WAY);


$router[] = new Route('<presenter>/<action>/<id>', array(
	'presenter' => 'Homepage',
	'action' => 'default',
	'id' => NULL,
));

// Step 5: Run the application!
$application->run();
