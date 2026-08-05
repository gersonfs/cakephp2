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

// Share loaded fixtures across CakeFixtureManager instances.
//
// PHPUnit builds one CakeTestCase instance per test method, and each one used
// to get its own manager with an empty $_loaded. Every fixture therefore took
// the slow path in _setupTable(): listSources() + DROP + CREATE on setUp and a
// TRUNCATE on tearDown, instead of the single TRUNCATE upstream did once the
// table existed. Measured on the full suite: 6m07 without the cache, 1m37 with
// it, both green.
//
// The cache was previously off because a table dropped by one test class would
// leave a stale entry behind; _setupTable() guards against that by confirming
// the table still exists before truncating.
CakeFixtureManager::$cacheInstances = true;

// CakeTestRunner used to drop the fixture tables when the run ended. It was
// deleted in the PHPUnit 12 migration, leaving CakeFixtureManager::shutDown()
// with no caller and the test schema full of leftover tables.
register_shutdown_function(function () {
	$manager = new CakeFixtureManager();
	$manager->shutDown();
});
