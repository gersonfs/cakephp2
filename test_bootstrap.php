<?php
if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}

$dispatcher = 'lib' . DS . 'Cake' . DS . 'Console' . DS . 'ShellDispatcher.php';
$found = false;
$paths = explode(PATH_SEPARATOR, ini_get('include_path'));

foreach ($paths as $path) {
	if (file_exists($path . DS . $dispatcher)) {
		$found = $path;
		break;
	}
}

if (!$found) {
	$rootInstall = dirname(dirname(dirname(__FILE__))) . DS . $dispatcher;
	$composerInstall = dirname(dirname(__FILE__)) . DS . $dispatcher;

	if (file_exists($composerInstall)) {
		include $composerInstall;
	} elseif (file_exists($rootInstall)) {
		include $rootInstall;
	} else {
		trigger_error('Could not locate CakePHP core files.', E_USER_ERROR);
	}
	unset($rootInstall, $composerInstall);

} else {
	include $found . DS . $dispatcher;
}

unset($paths, $path, $found, $dispatcher);

class ShellDispatcher2 extends ShellDispatcher
{
	protected function _initEnvironment()
	{
		$this->_bootstrap();
	}
}
new ShellDispatcher2();
require_once 'lib/Cake/TestSuite/CakeTestCase.php';
error_reporting(0);
