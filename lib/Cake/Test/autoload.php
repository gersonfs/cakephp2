<?php

if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('ROOT')) {
	define('ROOT', dirname(__FILE__, 4));
}

if (!defined('WEBROOT_DIR')) {
	define('WEBROOT_DIR', 'webroot');
}


/**
 * The actual directory name for the "app".
 */
if (!defined('APP_DIR')) {
	define('APP_DIR', 'app');
}

/**
 * Config Directory
 */
if (!defined('CONFIG')) {
	define('CONFIG', ROOT . DS . APP_DIR . DS . 'Config' . DS);
}

if (!defined('WWW_ROOT')) {
	define('WWW_ROOT', ROOT . DS . APP_DIR . DS . WEBROOT_DIR . DS);
}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';
restore_error_handler();
require_once 'vendors/autoload.php';

App::uses('CakeTestCase', 'TestSuite');
App::uses('CakeTestModel', 'TestSuite/Fixture');
App::uses('CakeFixtureManager', 'TestSuite/Fixture');

// The framework's own test suite drops/recreates fixture tables across
// many test classes, so we opt-out of the cross-instance fixture cache
// that is enabled by default for application test suites.
CakeFixtureManager::$cacheInstances = false;
