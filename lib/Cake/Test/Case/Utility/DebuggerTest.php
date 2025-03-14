<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Debugger', 'Utility');

/**
 * DebuggerTestCaseDebugger class
 *
 * @package       Cake.Test.Case.Utility
 */
class DebuggerTestCaseDebugger extends Debugger {
}

/**
 * DebuggerTest class
 *
 * !!! Be careful with changing code below as it may
 * !!! change line numbers which are used in the tests
 *
 * @package       Cake.Test.Case.Utility
 */
class DebuggerTest extends CakeTestCase {

	protected $_restoreError = false;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp(): void {
		parent::setUp();
		Configure::write('debug', 2);
		Configure::write('log', false);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown(): void {
		parent::tearDown();
		Configure::write('log', true);
		if ($this->_restoreError) {
			restore_error_handler();
		}
	}

/**
 * testDocRef method
 *
 * @return void
 */
	public function testDocRef() {
		ini_set('docref_root', '');
		$this->assertEquals(ini_get('docref_root'), '');
		new Debugger();
		$this->assertEquals(ini_get('docref_root'), 'http://php.net/');
	}

/**
 * test Excerpt writing
 *
 * @return void
 */
	public function testExcerpt() {
		$result = Debugger::excerpt(__FILE__, __LINE__, 2);
		$this->assertTrue(is_array($result));
		$this->assertEquals(5, count($result));
		$this->assertMatchesRegularExpression('/function(.+)testExcerpt/', $result[1]);

		$result = Debugger::excerpt(__FILE__, 2, 2);
		$this->assertTrue(is_array($result));
		$this->assertEquals(4, count($result));

		$pattern = '/<code>.*?<span style\="color\: \#\d+">.*?&lt;\?php/';
		if (version_compare(PHP_VERSION, '8.3.0') >= 0) {
			$pattern = '/<code style\="color\: \#\d+">.*?<span style\="color\: \#.{6}">.*?&lt;\?php/';
		}
		$this->assertMatchesRegularExpression($pattern, $result[0]);

		$result = Debugger::excerpt(__FILE__, 11, 2);
		$this->assertEquals(5, count($result));

		$pattern = '/<span style\="color\: \#\d{6}">\*<\/span>/';
		$this->assertMatchesRegularExpression($pattern, $result[0]);

		$return = Debugger::excerpt('[internal]', 2, 2);
		$this->assertTrue(empty($return));
	}

/**
 * testOutput method
 *
 * @return void
 */
	public function testOutput() {
		set_error_handler('Debugger::showError');
		$this->_restoreError = true;

		$result = Debugger::output(false);
		$this->assertEquals('', $result);
		$out .= '';
		$result = Debugger::output(true);

		$type = $this->isPHP7() ? 'Notice' : 'Warning';
		$this->assertEquals($type, $result[0]['error']);
		if ($this->isPHP8()) {
			$this->assertMatchesRegularExpression('/Undefined variable \$out/', $result[0]['description']);
		}

		if ($this->isPHP7()) {
			$this->assertMatchesRegularExpression('/Undefined variable\:\s+out/', $result[0]['description']);
		}

		$this->assertMatchesRegularExpression('/DebuggerTest::testOutput/i', $result[0]['trace']);

		ob_start();
		Debugger::output('txt');
		$other .= '';
		$result = ob_get_clean();

		if ($this->isPHP7()) {
			$this->assertMatchesRegularExpression('/Undefined variable:\s+other/', $result);
		}

		if ($this->isPHP8()) {
			$this->assertMatchesRegularExpression('/Undefined variable \$other/', $result);
		}

		$this->assertMatchesRegularExpression('/Trace:/', $result);
		$this->assertMatchesRegularExpression('/DebuggerTest::testOutput/i', $result);

		ob_start();
		Debugger::output('html');
		$wrong .= '';
		$result = ob_get_clean();
		$this->assertMatchesRegularExpression('/<pre class="cake-error">.+<\/pre>/', $result);
		if ($this->isPHP7()) {
			$this->assertMatchesRegularExpression('/<b>Notice<\/b>/', $result);
			$this->assertMatchesRegularExpression('/variable:\s+wrong/', $result);
		}

		if ($this->isPHP8()) {
			$this->assertMatchesRegularExpression('/<b>Warning<\/b>/', $result);
			$this->assertMatchesRegularExpression('/variable \$wrong/', $result);
		}


		ob_start();
		Debugger::output('js');
		$buzz .= '';
		$result = explode('</a>', ob_get_clean());
		$tags = array(
			'pre' => array('class' => 'cake-error'),
			'a' => array(
				'href' => "javascript:void(0);",
				'onclick' => "preg:/document\.getElementById\('cakeErr[a-z0-9]+\-trace'\)\.style\.display = " .
					"\(document\.getElementById\('cakeErr[a-z0-9]+\-trace'\)\.style\.display == 'none'" .
					" \? '' \: 'none'\);/"
			),
			'b' => array(), 'Notice', '/b', ' (8)',
		);

		if ($this->isPHP8()) {
			$tags = array(
				'pre' => array('class' => 'cake-error'),
				'a' => array(
					'href' => "javascript:void(0);",
					'onclick' => "preg:/document\.getElementById\('cakeErr[a-z0-9]+\-trace'\)\.style\.display = " .
						"\(document\.getElementById\('cakeErr[a-z0-9]+\-trace'\)\.style\.display == 'none'" .
						" \? '' \: 'none'\);/"
				),
				'b' => array(), 'Warning', '/b', ' (2)',
			);
		}
		$this->assertTags($result[0], $tags);

		$pattern = '/Undefined variable:\s+buzz/';
		if ($this->isPHP8()) {
			$pattern = '/Undefined variable \$buzz/';
		}
		$this->assertMatchesRegularExpression($pattern, $result[1]);
		$this->assertMatchesRegularExpression('/<a[^>]+>Code/', $result[1]);
		$this->assertMatchesRegularExpression('/<a[^>]+>Context/', $result[2]);
		if (!$this->isPHP8()) {
			$this->assertStringContainsString('$wrong = &#039;&#039;', $result[3], 'Context should be HTML escaped.');
		}
		restore_error_handler();
	}

/**
 * test encodes error messages
 *
 * @return void
 */
	public function testOutputEncodeDescription() {
		set_error_handler('Debugger::showError');
		$this->_restoreError = true;

		ob_start();
		$a = array();
		$b = $a['<script>alert(1)</script>'];
		$result = ob_get_clean();

		$this->assertStringNotContainsString('<script>alert(1)', $result);
		$this->assertStringContainsString('&lt;script&gt;alert(1)', $result);
		restore_error_handler();
	}

/**
 * Tests that changes in output formats using Debugger::output() change the templates used.
 *
 * @return void
 */
	public function testChangeOutputFormats() {
		set_error_handler('Debugger::showError');
		$this->_restoreError = true;

		Debugger::output('js', array(
			'traceLine' => '{:reference} - <a href="txmt://open?url=file://{:file}' .
				'&line={:line}">{:path}</a>, line {:line}'
		));
		$result = Debugger::trace();
		$this->assertMatchesRegularExpression('/' . preg_quote('txmt://open?url=file://', '/') . '(\/|[A-Z]:\\\\)' . '/', $result);

		Debugger::output('xml', array(
			'error' => '<error><code>{:code}</code><file>{:file}</file><line>{:line}</line>' .
				'{:description}</error>',
			'context' => "<context>{:context}</context>",
			'trace' => "<stack>{:trace}</stack>",
		));
		Debugger::output('xml');

		ob_start();
		$foo .= '';
		$result = ob_get_clean();

		$data = array(
			'error' => array(),
			'code' => array(), '8', '/code',
			'file' => array(), 'preg:/[^<]+/', '/file',
			'line' => array(), '' . ((int)__LINE__ - 7), '/line',
			'preg:/Undefined variable:\s+foo/',
			'/error'
		);

		if ($this->isPHP8()) {
			$data = array(
				'error' => array(),
				'code' => array(), '2', '/code',
				'file' => array(), 'preg:/[^<]+/', '/file',
				'line' => array(), '' . ((int)__LINE__ - 17), '/line',
				'preg:/Undefined variable \$foo/',
				'/error'
			);
		}
		$this->assertTags($result, $data, true);
		restore_error_handler();
	}

/**
 * Test that outputAs works.
 *
 * @return void
 */
	public function testOutputAs() {
		Debugger::outputAs('html');
		$this->assertEquals('html', Debugger::outputAs());
	}

/**
	 * Test that choosing a non-existent format causes an exception
	 *
	 * @return void
	 */
	public function testOutputAsException() {
		$this->expectException('CakeException');
		Debugger::outputAs('Invalid junk');
	}

/**
 * Tests that changes in output formats using Debugger::output() change the templates used.
 *
 * @return void
 */
	public function testAddFormat() {
		set_error_handler('Debugger::showError');
		$this->_restoreError = true;

		Debugger::addFormat('js', array(
			'traceLine' => '{:reference} - <a href="txmt://open?url=file://{:file}' .
				'&line={:line}">{:path}</a>, line {:line}'
		));
		Debugger::outputAs('js');

		$result = Debugger::trace();
		$this->assertMatchesRegularExpression('/' . preg_quote('txmt://open?url=file://', '/') . '(\/|[A-Z]:\\\\)' . '/', $result);

		Debugger::addFormat('xml', array(
			'error' => '<error><code>{:code}</code><file>{:file}</file><line>{:line}</line>' .
				'{:description}</error>',
		));
		Debugger::outputAs('xml');

		ob_start();
		$foo .= '';
		$result = ob_get_clean();

		$data = array(
			'<error',
			'<code', '8', '/code',
			'<file', 'preg:/[^<]+/', '/file',
			'<line', '' . ((int)__LINE__ - 7), '/line',
			'preg:/Undefined variable:\s+foo/',
			'/error'
		);
		if ($this->isPHP8()) {
			$data = array(
				'<error',
				'<code', '2', '/code',
				'<file', 'preg:/[^<]+/', '/file',
				'<line', '' . ((int)__LINE__ - 16), '/line',
				'preg:/Undefined variable \$foo/',
				'/error'
			);
		}
		$this->assertTags($result, $data, true);
		restore_error_handler();
	}

/**
 * Test adding a format that is handled by a callback.
 *
 * @return void
 */
	public function testAddFormatCallback() {
		set_error_handler('Debugger::showError');
		$this->_restoreError = true;

		Debugger::addFormat('callback', array('callback' => array($this, 'customFormat')));
		Debugger::outputAs('callback');

		ob_start();
		$foo .= '';
		$result = ob_get_clean();
		$type = $this->isPHP7() ? 'Notice' : 'Warning';
		$this->assertStringContainsString($type . ': I eated an error', $result);
		$this->assertStringContainsString('DebuggerTest.php', $result);
		restore_error_handler();
	}

/**
 * Test method for testing addFormat with callbacks.
 *
 * @return void
 */
	public function customFormat($error, $strings) {
		return $error['error'] . ': I eated an error ' . $error['file'];
	}

/**
 * testTrimPath method
 *
 * @return void
 */
	public function testTrimPath() {
		$this->assertEquals('APP' . DS, Debugger::trimPath(APP));
		$this->assertEquals('CORE', Debugger::trimPath(CAKE_CORE_INCLUDE_PATH));
		$this->assertEquals('ROOT', Debugger::trimPath(ROOT));
		$this->assertEquals('CORE' . DS . 'Cake' . DS, Debugger::trimPath(CAKE));
		$this->assertEquals('Some/Other/Path', Debugger::trimPath('Some/Other/Path'));
	}

/**
 * testExportVar method
 *
 * @return void
 */
	public function testExportVar() {
		App::uses('Controller', 'Controller');
		$Controller = new Controller();
		$Controller->helpers = array('Html', 'Form');
		$View = new View($Controller);
		$View->int = 2;
		$View->float = 1.333;

		$result = Debugger::exportVar($View);
		$expected = <<<TEXT
object(View) {
	Helpers => object(HelperCollection) {}
	Blocks => object(ViewBlock) {}
	plugin => null
	name => ''
	passedArgs => array()
	helpers => array(
		(int) 0 => 'Html',
		(int) 1 => 'Form'
	)
	viewPath => ''
	viewVars => array()
	view => null
	layout => 'default'
	layoutPath => null
	autoLayout => true
	ext => '.ctp'
	subDir => null
	theme => null
	cacheAction => false
	validationErrors => array()
	hasRendered => false
	uuids => array()
	request => object(CakeRequest) {}
	response => object(CakeResponse) {}
	elementCache => 'default'
	elementCacheSettings => array()
	Html => object(HtmlHelper) {}
	Form => object(FormHelper) {}
	int => (int) 2
	float => (float) 1.333

TEXT;
		if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
			$expected .= <<<TEXT
	[protected] _passedVars => array(
		(int) 0 => 'viewVars',
		(int) 1 => 'autoLayout',
		(int) 2 => 'ext',
		(int) 3 => 'helpers',
		(int) 4 => 'view',
		(int) 5 => 'layout',
		(int) 6 => 'name',
		(int) 7 => 'theme',
		(int) 8 => 'layoutPath',
		(int) 9 => 'viewPath',
		(int) 10 => 'request',
		(int) 11 => 'plugin',
		(int) 12 => 'passedArgs',
		(int) 13 => 'cacheAction'
	)
	[protected] _scripts => array()
	[protected] _paths => array()
	[protected] _pathsForPlugin => array()
	[protected] _parents => array()
	[protected] _current => null
	[protected] _currentType => ''
	[protected] _stack => array()
	[protected] _eventManager => object(CakeEventManager) {}
	[protected] _eventManagerConfigured => false

TEXT;
		}
		$expected .= <<<TEXT
}
TEXT;

		$this->assertTextEquals($expected, $result);

		$data = array(
			1 => 'Index one',
			5 => 'Index five'
		);
		$result = Debugger::exportVar($data);
		$expected = <<<TEXT
array(
	(int) 1 => 'Index one',
	(int) 5 => 'Index five'
)
TEXT;
		$this->assertTextEquals($expected, $result);

		$data = array(
			'key' => array(
				'value'
			)
		);
		$result = Debugger::exportVar($data, 1);
		$expected = <<<TEXT
array(
	'key' => array(
		[maximum depth reached]
	)
)
TEXT;
		$this->assertTextEquals($expected, $result);

		$data = false;
		$result = Debugger::exportVar($data);
		$expected = <<<TEXT
false
TEXT;
		$this->assertTextEquals($expected, $result);

		$file = fopen('php://output', 'w');
		fclose($file);
		$result = Debugger::exportVar($file);
		$this->assertTextEquals('unknown', $result);
	}

/**
 * Test exporting various kinds of false.
 *
 * @return void
 */
	public function testExportVarZero() {
		$data = array(
			'nothing' => '',
			'null' => null,
			'false' => false,
			'szero' => '0',
			'zero' => 0
		);
		$result = Debugger::exportVar($data);
		$expected = <<<TEXT
array(
	'nothing' => '',
	'null' => null,
	'false' => false,
	'szero' => '0',
	'zero' => (int) 0
)
TEXT;
		$this->assertTextEquals($expected, $result);
	}

/**
 * testLog method
 *
 * @return void
 */
	public function testLog() {
		if (file_exists(LOGS . 'debug.log')) {
			unlink(LOGS . 'debug.log');
		}
		CakeLog::config('file', array('engine' => 'File', 'path' => TMP . 'logs' . DS));

		CakeLog::drop('stderr');
		Debugger::log('cool');
		$result = file_get_contents(LOGS . 'debug.log');
		$this->assertStringContainsString('DebuggerTest::testLog', $result);
		$this->assertStringContainsString("'cool'", $result);

		unlink(LOGS . 'debug.log');

		Debugger::log(array('whatever', 'here'));
		$result = file_get_contents(LOGS . 'debug.log');
		$this->assertStringContainsString('DebuggerTest::testLog', $result);
		$this->assertStringContainsString('[main]', $result);
		$this->assertStringContainsString('array', $result);
		$this->assertStringContainsString("'whatever',", $result);
		$this->assertStringContainsString("'here'", $result);
	}

/**
 * test log() depth
 *
 * @return void
 */
	public function testLogDepth() {
		if (file_exists(LOGS . 'debug.log')) {
			unlink(LOGS . 'debug.log');
		}
		CakeLog::config('file', array('engine' => 'File', 'path' => TMP . 'logs' . DS));

		$val = array(
			'test' => array('key' => 'val')
		);
		Debugger::log($val, LOG_DEBUG, 0);
		$result = file_get_contents(LOGS . 'debug.log');
		$this->assertStringContainsString('DebuggerTest::testLog', $result);
		$this->assertStringNotContainsString("/'val'/", $result);

		unlink(LOGS . 'debug.log');
	}

/**
 * testDump method
 *
 * @return void
 */
	public function testDump() {
		$var = array('People' => array(
			array(
				'name' => 'joeseph',
				'coat' => 'technicolor',
				'hair_color' => 'brown'
			),
			array(
				'name' => 'Shaft',
				'coat' => 'black',
				'hair' => 'black'
			)
		));
		ob_start();
		Debugger::dump($var);
		$result = ob_get_clean();

		$open = PHP_SAPI === 'cli' ? "\n" : '<pre>';
		$close = PHP_SAPI === 'cli' ? "\n" : '</pre>';
		$expected = <<<TEXT
{$open}array(
	'People' => array(
		(int) 0 => array(
			'name' => 'joeseph',
			'coat' => 'technicolor',
			'hair_color' => 'brown'
		),
		(int) 1 => array(
			'name' => 'Shaft',
			'coat' => 'black',
			'hair' => 'black'
		)
	)
){$close}
TEXT;
		$this->assertTextEquals($expected, $result);

		ob_start();
		Debugger::dump($var, 1);
		$result = ob_get_clean();

		$open = PHP_SAPI === 'cli' ? "\n" : '<pre>';
		$close = PHP_SAPI === 'cli' ? "\n" : '</pre>';
		$expected = <<<TEXT
{$open}array(
	'People' => array(
		[maximum depth reached]
	)
){$close}
TEXT;
		$this->assertTextEquals($expected, $result);
	}

/**
 * test getInstance.
 *
 * @return void
 */
	public function testGetInstance() {
		$result = Debugger::getInstance();
		$this->assertInstanceOf('Debugger', $result);

		$result = Debugger::getInstance('DebuggerTestCaseDebugger');
		$this->assertInstanceOf('DebuggerTestCaseDebugger', $result);

		$result = Debugger::getInstance();
		$this->assertInstanceOf('DebuggerTestCaseDebugger', $result);

		$result = Debugger::getInstance('Debugger');
		$this->assertInstanceOf('Debugger', $result);
	}

/**
 * testNoDbCredentials
 *
 * If a connection error occurs, the config variable is passed through exportVar
 * *** our database login credentials such that they are never visible
 *
 * @return void
 */
	public function testNoDbCredentials() {
		$config = array(
			'datasource' => 'mysql',
			'persistent' => false,
			'host' => 'void.cakephp.org',
			'login' => 'cakephp-user',
			'password' => 'cakephp-password',
			'database' => 'cakephp-database',
			'prefix' => ''
		);

		$output = Debugger::exportVar($config);

		$expectedArray = array(
			'datasource' => 'mysql',
			'persistent' => false,
			'host' => '*****',
			'login' => '*****',
			'password' => '*****',
			'database' => '*****',
			'prefix' => ''
		);
		$expected = Debugger::exportVar($expectedArray);

		$this->assertEquals($expected, $output);
	}

/**
 * Test that exportVar() doesn't loop through recursive structures.
 *
 * @return void
 */
	public function testExportVarRecursion() {
		$this->skipIf($this->isPHP81(), 'Escapando teste PHP 8.1');
		$output = Debugger::exportVar($GLOBALS);
		$this->assertStringContainsString("'GLOBALS' => [recursion]", $output);
	}

/**
 * test trace exclude
 *
 * @return void
 */
	public function testTraceExclude() {
		$result = Debugger::trace();
		$this->assertMatchesRegularExpression('/^DebuggerTest::testTraceExclude/', $result);

		$result = Debugger::trace(array(
			'exclude' => array('DebuggerTest::testTraceExclude')
		));
		$this->assertDoesNotMatchRegularExpression('/^DebuggerTest::testTraceExclude/', $result);
	}
}
