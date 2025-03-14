<?php
/**
 * CakeLogTest file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Log
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeLog', 'Log');
App::uses('FileLog', 'Log/Engine');

/**
 * CakeLogTest class
 *
 * @package       Cake.Test.Case.Log
 */
class CakeLogTest extends CakeTestCase {

/**
 * Start test callback, clears all streams enabled.
 *
 * @return void
 */
	public function setUp(): void {
		parent::setUp();
		$streams = CakeLog::configured();
		foreach ($streams as $stream) {
			CakeLog::drop($stream);
		}
	}

/**
 * test importing loggers from app/libs and plugins.
 *
 * @return void
 */
	public function testImportingLoggers() {
		App::build(array(
			'Lib' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Lib' . DS),
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);
		CakePlugin::load('TestPlugin');

		$result = CakeLog::config('libtest', array(
			'engine' => 'TestAppLog'
		));
		$this->assertTrue($result);
		$this->assertEquals(CakeLog::configured(), array('libtest'));

		$result = CakeLog::config('plugintest', array(
			'engine' => 'TestPlugin.TestPluginLog'
		));
		$this->assertTrue($result);
		$this->assertEquals(CakeLog::configured(), array('libtest', 'plugintest'));

		CakeLog::write(LOG_INFO, 'TestPluginLog is not a BaseLog descendant');

		App::build();
		CakePlugin::unload();
	}

/**
	 * test all the errors from failed logger imports
	 *
	 * @return void
	 */
	public function testImportingLoggerFailure() {
		$this->expectException('CakeLogException');
		CakeLog::config('fail', array());
	}

/**
 * test config() with valid key name
 *
 * @return void
 */
	public function testValidKeyName() {
		CakeLog::config('valid', array('engine' => 'File'));
		$stream = CakeLog::stream('valid');
		$this->assertInstanceOf('FileLog', $stream);
		CakeLog::drop('valid');
	}

/**
 * test config() with valid key name including the deprecated Log suffix
 *
 * @return void
 */
	public function testValidKeyNameLogSuffix() {
		CakeLog::config('valid', array('engine' => 'FileLog'));
		$stream = CakeLog::stream('valid');
		$this->assertInstanceOf('FileLog', $stream);
		CakeLog::drop('valid');
	}

/**
	 * test config() with invalid key name
	 *
	 * @return void
	 */
	public function testInvalidKeyName() {
		$this->expectException('CakeLogException');
		CakeLog::config('1nv', array('engine' => 'File'));
	}

/**
	 * test that loggers have to implement the correct interface.
	 *
	 * @return void
	 */
	public function testNotImplementingInterface() {
		$this->expectException('CakeLogException');
		CakeLog::config('fail', array('engine' => 'stdClass'));
	}

/**
 * Test that CakeLog does not auto create logs when no streams are there to listen.
 *
 * @return void
 */
	public function testNoStreamListenting() {
		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}
		$res = CakeLog::write(LOG_WARNING, 'Test warning');
		$this->assertFalse($res);
		$this->assertFalse(file_exists(LOGS . 'error.log'));

		$result = CakeLog::configured();
		$this->assertEquals(array(), $result);
	}

/**
 * test configuring log streams
 *
 * @return void
 */
	public function testConfig() {
		CakeLog::config('file', array(
			'engine' => 'File',
			'path' => LOGS
		));
		$result = CakeLog::configured();
		$this->assertEquals(array('file'), $result);

		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}
		CakeLog::write(LOG_WARNING, 'Test warning');
		$this->assertTrue(file_exists(LOGS . 'error.log'));

		$result = file_get_contents(LOGS . 'error.log');
		$this->assertMatchesRegularExpression('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning/', $result);
		unlink(LOGS . 'error.log');
	}

/**
 * explicit tests for drop()
 *
 * @return void
 */
	public function testDrop() {
		CakeLog::config('file', array(
			'engine' => 'File',
			'path' => LOGS
		));
		$result = CakeLog::configured();
		$this->assertEquals(array('file'), $result);

		CakeLog::drop('file');
		$result = CakeLog::configured();
		$this->assertSame(array(), $result);
	}

/**
 * testLogFileWriting method
 *
 * @return void
 */
	public function testLogFileWriting() {
		CakeLog::config('file', array(
			'engine' => 'File',
			'path' => LOGS
		));
		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}
		$result = CakeLog::write(LOG_WARNING, 'Test warning');
		$this->assertTrue($result);
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		unlink(LOGS . 'error.log');

		CakeLog::write(LOG_WARNING, 'Test warning 1');
		CakeLog::write(LOG_WARNING, 'Test warning 2');
		$result = file_get_contents(LOGS . 'error.log');
		$this->assertMatchesRegularExpression('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning 1/', $result);
		$this->assertMatchesRegularExpression('/2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning 2$/', $result);
		unlink(LOGS . 'error.log');
	}

/**
 * test selective logging by level/type
 *
 * @return void
 */
	public function testSelectiveLoggingByLevel() {
		if (file_exists(LOGS . 'spam.log')) {
			unlink(LOGS . 'spam.log');
		}
		if (file_exists(LOGS . 'eggs.log')) {
			unlink(LOGS . 'eggs.log');
		}
		CakeLog::config('spam', array(
			'engine' => 'File',
			'types' => 'debug',
			'file' => 'spam',
		));
		CakeLog::config('eggs', array(
			'engine' => 'File',
			'types' => array('eggs', 'debug', 'error', 'warning'),
			'file' => 'eggs',
		));

		$testMessage = 'selective logging';
		CakeLog::write(LOG_WARNING, $testMessage);

		$this->assertTrue(file_exists(LOGS . 'eggs.log'));
		$this->assertFalse(file_exists(LOGS . 'spam.log'));

		CakeLog::write(LOG_DEBUG, $testMessage);
		$this->assertTrue(file_exists(LOGS . 'spam.log'));

		$contents = file_get_contents(LOGS . 'spam.log');
		$this->assertStringContainsString('Debug: ' . $testMessage, $contents);
		$contents = file_get_contents(LOGS . 'eggs.log');
		$this->assertStringContainsString('Debug: ' . $testMessage, $contents);

		if (file_exists(LOGS . 'spam.log')) {
			unlink(LOGS . 'spam.log');
		}
		if (file_exists(LOGS . 'eggs.log')) {
			unlink(LOGS . 'eggs.log');
		}
	}

/**
	 * test enable
	 *
	 * @return void
	 */
	public function testStreamEnable() {
		$this->expectException('CakeLogException');
		CakeLog::config('spam', array(
			'engine' => 'File',
			'file' => 'spam',
			));
		$this->assertTrue(CakeLog::enabled('spam'));
		CakeLog::drop('spam');
		CakeLog::enable('bogus_stream');
	}

/**
	 * test disable
	 *
	 * @return void
	 */
	public function testStreamDisable() {
		$this->expectException('CakeLogException');
		CakeLog::config('spam', array(
			'engine' => 'File',
			'file' => 'spam',
			));
		$this->assertTrue(CakeLog::enabled('spam'));
		CakeLog::disable('spam');
		$this->assertFalse(CakeLog::enabled('spam'));
		CakeLog::drop('spam');
		CakeLog::enable('bogus_stream');
	}

/**
	 * test enabled() invalid stream
	 *
	 * @return void
	 */
	public function testStreamEnabledInvalid() {
		$this->expectException('CakeLogException');
		CakeLog::enabled('bogus_stream');
	}

/**
	 * test disable invalid stream
	 *
	 * @return void
	 */
	public function testStreamDisableInvalid() {
		$this->expectException('CakeLogException');
		CakeLog::disable('bogus_stream');
	}

/**
 * resets log config
 *
 * @return void
 */
	protected function _resetLogConfig() {
		CakeLog::config('debug', array(
			'engine' => 'File',
			'types' => array('notice', 'info', 'debug'),
			'file' => 'debug',
		));
		CakeLog::config('error', array(
			'engine' => 'File',
			'types' => array('warning', 'error', 'critical', 'alert', 'emergency'),
			'file' => 'error',
		));
	}

/**
 * delete logs
 *
 * @return void
 */
	protected function _deleteLogs() {
		if (file_exists(LOGS . 'shops.log')) {
			unlink(LOGS . 'shops.log');
		}
		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}
		if (file_exists(LOGS . 'debug.log')) {
			unlink(LOGS . 'debug.log');
		}
		if (file_exists(LOGS . 'bogus.log')) {
			unlink(LOGS . 'bogus.log');
		}
		if (file_exists(LOGS . 'spam.log')) {
			unlink(LOGS . 'spam.log');
		}
		if (file_exists(LOGS . 'eggs.log')) {
			unlink(LOGS . 'eggs.log');
		}
	}

/**
 * test backward compatible scoped logging
 *
 * @return void
 */
	public function testScopedLoggingBC() {
		$this->_resetLogConfig();

		CakeLog::config('shops', array(
			'engine' => 'File',
			'types' => array('info', 'notice', 'warning'),
			'scopes' => array('transactions', 'orders'),
			'file' => 'shops',
		));
		$this->_deleteLogs();

		CakeLog::write('info', 'info message');
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertTrue(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		CakeLog::write('transactions', 'transaction message');
		$this->assertTrue(file_exists(LOGS . 'shops.log'));
		$this->assertFalse(file_exists(LOGS . 'transactions.log'));
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		CakeLog::write('error', 'error message');
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->assertFalse(file_exists(LOGS . 'shops.log'));

		$this->_deleteLogs();

		CakeLog::write('orders', 'order message');
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->assertFalse(file_exists(LOGS . 'orders.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));

		$this->_deleteLogs();

		CakeLog::write('warning', 'warning message');
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		CakeLog::drop('shops');
	}

/**
 * Test that scopes are exclusive and don't bleed.
 *
 * @return void
 */
	public function testScopedLoggingExclusive() {
		$this->_deleteLogs();

		CakeLog::config('shops', array(
			'engine' => 'File',
			'types' => array('info', 'notice', 'warning'),
			'scopes' => array('transactions', 'orders'),
			'file' => 'shops.log',
		));
		CakeLog::config('eggs', array(
			'engine' => 'File',
			'types' => array('info', 'notice', 'warning'),
			'scopes' => array('eggs'),
			'file' => 'eggs.log',
		));

		CakeLog::write('info', 'transactions message', 'transactions');
		$this->assertFalse(file_exists(LOGS . 'eggs.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));

		$this->_deleteLogs();

		CakeLog::write('info', 'eggs message', 'eggs');
		$this->assertTrue(file_exists(LOGS . 'eggs.log'));
		$this->assertFalse(file_exists(LOGS . 'shops.log'));
	}

/**
 * test scoped logging
 *
 * @return void
 */
	public function testScopedLogging() {
		$this->_resetLogConfig();
		$this->_deleteLogs();

		CakeLog::config('string-scope', array(
			'engine' => 'File',
			'types' => array('info', 'notice', 'warning'),
			'scopes' => 'string-scope',
			'file' => 'string-scope.log'
		));
		CakeLog::write('info', 'info message', 'string-scope');
		$this->assertTrue(file_exists(LOGS . 'string-scope.log'));

		CakeLog::drop('string-scope');

		CakeLog::config('shops', array(
			'engine' => 'File',
			'types' => array('info', 'notice', 'warning'),
			'scopes' => array('transactions', 'orders'),
			'file' => 'shops.log',
		));

		CakeLog::write('info', 'info message', 'transactions');
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));
		$this->assertTrue(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		CakeLog::write('transactions', 'transaction message', 'orders');
		$this->assertTrue(file_exists(LOGS . 'shops.log'));
		$this->assertFalse(file_exists(LOGS . 'transactions.log'));
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		CakeLog::write('error', 'error message', 'orders');
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->assertFalse(file_exists(LOGS . 'shops.log'));

		$this->_deleteLogs();

		CakeLog::write('orders', 'order message', 'transactions');
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->assertFalse(file_exists(LOGS . 'orders.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));

		$this->_deleteLogs();

		CakeLog::write('warning', 'warning message', 'orders');
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		CakeLog::drop('shops');
	}

/**
 * test bogus type and scope
 *
 * @return void
 */
	public function testBogusTypeAndScope() {
		$this->_resetLogConfig();
		$this->_deleteLogs();

		CakeLog::config('file', array(
			'engine' => 'File',
			'path' => LOGS
		));

		CakeLog::write('bogus', 'bogus message');
		$this->assertTrue(file_exists(LOGS . 'bogus.log'));
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		CakeLog::write('bogus', 'bogus message', 'bogus');
		$this->assertTrue(file_exists(LOGS . 'bogus.log'));
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		CakeLog::write('error', 'bogus message', 'bogus');
		$this->assertFalse(file_exists(LOGS . 'bogus.log'));
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();
	}

/**
 * test scoped logging with convenience methods
 *
 * @return void
 */
	public function testConvenienceScopedLogging() {
		if (file_exists(LOGS . 'shops.log')) {
			unlink(LOGS . 'shops.log');
		}
		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}
		if (file_exists(LOGS . 'debug.log')) {
			unlink(LOGS . 'debug.log');
		}

		$this->_resetLogConfig();
		CakeLog::config('shops', array(
			'engine' => 'File',
			'types' => array('info', 'debug', 'notice', 'warning'),
			'scopes' => array('transactions', 'orders'),
			'file' => 'shops',
		));

		CakeLog::info('info message', 'transactions');
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));
		$this->assertTrue(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		CakeLog::error('error message', 'orders');
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->assertFalse(file_exists(LOGS . 'shops.log'));

		$this->_deleteLogs();

		CakeLog::warning('warning message', 'orders');
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		CakeLog::drop('shops');
	}

/**
 * test convenience methods
 *
 * @return void
 */
	public function testConvenienceMethods() {
		$this->_deleteLogs();

		CakeLog::config('debug', array(
			'engine' => 'File',
			'types' => array('notice', 'info', 'debug'),
			'file' => 'debug',
		));
		CakeLog::config('error', array(
			'engine' => 'File',
			'types' => array('emergency', 'alert', 'critical', 'error', 'warning'),
			'file' => 'error',
		));

		$testMessage = 'emergency message';
		CakeLog::emergency($testMessage);
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertMatchesRegularExpression('/(Emergency|Critical): ' . $testMessage . '/', $contents);
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		$testMessage = 'alert message';
		CakeLog::alert($testMessage);
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertMatchesRegularExpression('/(Alert|Critical): ' . $testMessage . '/', $contents);
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		$testMessage = 'critical message';
		CakeLog::critical($testMessage);
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertStringContainsString('Critical: ' . $testMessage, $contents);
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		$testMessage = 'error message';
		CakeLog::error($testMessage);
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertStringContainsString('Error: ' . $testMessage, $contents);
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		$testMessage = 'warning message';
		CakeLog::warning($testMessage);
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertStringContainsString('Warning: ' . $testMessage, $contents);
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		$testMessage = 'notice message';
		CakeLog::notice($testMessage);
		$contents = file_get_contents(LOGS . 'debug.log');
		$this->assertMatchesRegularExpression('/(Notice|Debug): ' . $testMessage . '/', $contents);
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->_deleteLogs();

		$testMessage = 'info message';
		CakeLog::info($testMessage);
		$contents = file_get_contents(LOGS . 'debug.log');
		$this->assertMatchesRegularExpression('/(Info|Debug): ' . $testMessage . '/', $contents);
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->_deleteLogs();

		$testMessage = 'debug message';
		CakeLog::debug($testMessage);
		$contents = file_get_contents(LOGS . 'debug.log');
		$this->assertStringContainsString('Debug: ' . $testMessage, $contents);
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->_deleteLogs();
	}

/**
 * test levels customization
 *
 * @return void
 */
	public function testLevelCustomization() {
		$this->skipIf(DIRECTORY_SEPARATOR === '\\', 'Log level tests not supported on Windows.');

		$levels = CakeLog::defaultLevels();
		$this->assertNotEmpty($levels);
		$result = array_keys($levels);
		$this->assertEquals(array(0, 1, 2, 3, 4, 5, 6, 7), $result);

		$levels = CakeLog::levels(array('foo', 'bar'));
		CakeLog::defaultLevels();
		$this->assertEquals('foo', $levels[8]);
		$this->assertEquals('bar', $levels[9]);

		$levels = CakeLog::levels(array(11 => 'spam', 'bar' => 'eggs'));
		CakeLog::defaultLevels();
		$this->assertEquals('spam', $levels[8]);
		$this->assertEquals('eggs', $levels[9]);

		$levels = CakeLog::levels(array(11 => 'spam', 'bar' => 'eggs'), false);
		CakeLog::defaultLevels();
		$this->assertEquals(array('spam', 'eggs'), $levels);

		$levels = CakeLog::levels(array('ham', 9 => 'spam', '12' => 'fam'), false);
		CakeLog::defaultLevels();
		$this->assertEquals(array('ham', 'spam', 'fam'), $levels);
	}

/**
 * Test writing log files with custom levels
 *
 * @return void
 */
	public function testCustomLevelWrites() {
		$this->_deleteLogs();
		$this->_resetLogConfig();

		CakeLog::levels(array('spam', 'eggs'));

		$testMessage = 'error message';
		CakeLog::write('error', $testMessage);
		CakeLog::defaultLevels();
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertStringContainsString('Error: ' . $testMessage, $contents);

		CakeLog::config('spam', array(
			'engine' => 'File',
			'file' => 'spam.log',
			'types' => 'spam',
			));
		CakeLog::config('eggs', array(
			'engine' => 'File',
			'file' => 'eggs.log',
			'types' => array('spam', 'eggs'),
			));

		$testMessage = 'spam message';
		CakeLog::write('spam', $testMessage);
		CakeLog::defaultLevels();
		$this->assertTrue(file_exists(LOGS . 'spam.log'));
		$this->assertTrue(file_exists(LOGS . 'eggs.log'));
		$contents = file_get_contents(LOGS . 'spam.log');
		$this->assertStringContainsString('Spam: ' . $testMessage, $contents);

		$testMessage = 'egg message';
		CakeLog::write('eggs', $testMessage);
		CakeLog::defaultLevels();
		$contents = file_get_contents(LOGS . 'spam.log');
		$this->assertStringNotContainsString('Eggs: ' . $testMessage, $contents);
		$contents = file_get_contents(LOGS . 'eggs.log');
		$this->assertStringContainsString('Eggs: ' . $testMessage, $contents);

		CakeLog::drop('spam');
		CakeLog::drop('eggs');

		$this->_deleteLogs();
	}

}
