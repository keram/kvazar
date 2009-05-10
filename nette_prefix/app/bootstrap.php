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

require LIBS_DIR . '/Nettep/loader.php';

//require dirname(__FILE__) . '/../../../Nette/loader.php';



// Step 2: Configure environment a
// 2a) enable Nette\Debug for better exception and error visualisation
NDebug::enable();

NDebug::enable(NDebug::DEVELOPMENT);
NEnvironment::setMode(NEnvironment::DEVELOPMENT);

// 2b) load configuration from config.ini file
NEnvironment::loadConfig();

require_once LIBS_DIR . '/dibi/dibi.php';
dibi::connect(NEnvironment::getConfig('database'));

$site = NEnvironment::getConfig('site');
foreach($site as $key=>$val)
{
	define($key, $val);
}

// 2c) check if directory /app/temp is writable
if (@file_put_contents(NEnvironment::expand('%tempDir%/_check'), '') === FALSE) {
	throw new Exception("Make directory '" . NEnvironment::getVariable('tempDir') . "' writable!");
}

// 2d) enable RobotLoader - this allows load all classes automatically
$loader = new NRobotLoader();
$loader->addDirectory(APP_DIR);
$loader->addDirectory(LIBS_DIR);
$loader->register();

// 2e) setup sessions
$session = NEnvironment::getSession();
$session->setSavePath(APP_DIR . '/sessions/');



// Step 3: Configure application
// 3a) get and setup a front controller
$application = NEnvironment::getApplication();
$application->errorPresenter = 'Error';
//$application->catchExceptions = TRUE;


NEnvironment::getServiceLocator()->addService("Users", 'Nette\Security\IAuthenticator');
// NEnvironment::getServiceLocator()->addService("Users", 'IAuthenticator');
// NEnvironment::getServiceLocator()->addService("Acl", 'Nettep\Security\IAuthorizator');
// NEnvironment::getServiceLocator()->addService("Acl", 'Nettep\Security\IAuthorizator');

// Step 4: Setup application router
$router = $application->getRouter();

$router[] = new NRoute('index.php', array(
	'presenter' => 'Homepage',
	'action' => 'default',
), NRoute::ONE_WAY);


$router[] = new NRoute('<presenter>/<action>/<id>', array(
	'presenter' => 'Homepage',
	'action' => 'default',
	'id' => NULL,
));

// Step 5: Run the application!
$application->run();