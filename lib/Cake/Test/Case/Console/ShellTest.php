<?php
/**
 * ShellTest file
 *
 * Test Case for Shell
 *
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP v 1.2.0.7726
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ShellDispatcher', 'Console');
App::uses('Shell', 'Console');
App::uses('Folder', 'Utility');
App::uses("ProgressHelper", "Console/Helper");

/**
 * ShellTestShell class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class ShellTestShell extends Shell
{

	/**
	 * name property
	 *
	 * @var name
	 */
	public $name = 'ShellTestShell';

	/**
	 * stopped property
	 *
	 * @var int
	 */
	public $stopped;

	/**
	 * testMessage property
	 *
	 * @var string
	 */
	public $testMessage = 'all your base are belong to us';

	/**
	 * stop method
	 *
	 * @param int $status
	 * @return void
	 */
	protected function _stop($status = 0)
	{
		$this->stopped = $status;
	}

	protected function _secret()
	{
	}

	//@codingStandardsIgnoreStart
	public function do_something()
	{
	}

	protected function no_access()
	{
	}

	public function log_something()
	{
		$this->log($this->testMessage);
	}

	//@codingStandardsIgnoreEnd

	public function mergeVars($properties, $class, $normalize = true)
	{
		return $this->_mergeVars($properties, $class, $normalize);
	}

	public function useLogger($enable = true)
	{
		$this->_useLogger($enable);
	}

}

/**
 * Class for testing merging vars
 *
 * @package       Cake.Test.Case.Console.Command
 */
class TestMergeShell extends Shell
{

	public $tasks = ['DbConfig', 'Fixture'];

	public $uses = ['Comment'];

}

/**
 * TestAppleTask class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class TestAppleTask extends Shell
{
}

/**
 * TestBananaTask class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class TestBananaTask extends Shell
{
}

/**
 * ShellTest class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class ShellTest extends CakeTestCase
{

	/**
	 * Fixtures used in this test case
	 *
	 * @var array
	 */
	public $fixtures = [
		'core.post', 'core.comment', 'core.article', 'core.user',
		'core.tag', 'core.articles_tag', 'core.attachment',
	];

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void
	{
		parent::setUp();

		$output = $this->getMock('ConsoleOutput', [], [], '', false);
		$error = $this->getMock('ConsoleOutput', [], [], '', false);
		$in = $this->getMock('ConsoleInput', [], [], '', false);
		$this->Shell = new ShellTestShell($output, $error, $in);

		if (is_dir(TMP . 'shell_test')) {
			$Folder = new Folder(TMP . 'shell_test');
			$Folder->delete();
		}
	}

	/**
	 * testConstruct method
	 *
	 * @return void
	 */
	public function testConstruct()
	{
		$this->assertEquals('ShellTestShell', $this->Shell->name);
		$this->assertInstanceOf('ConsoleInput', $this->Shell->stdin);
		$this->assertInstanceOf('ConsoleOutput', $this->Shell->stdout);
		$this->assertInstanceOf('ConsoleOutput', $this->Shell->stderr);
	}

	/**
	 * test merging vars
	 *
	 * @return void
	 */
	public function testMergeVars()
	{
		$this->Shell->tasks = ['DbConfig' => ['one', 'two']];
		$this->Shell->uses = ['Posts'];
		$this->Shell->mergeVars(['tasks'], 'TestMergeShell');
		$this->Shell->mergeVars(['uses'], 'TestMergeShell', false);

		$expected = ['DbConfig' => null, 'Fixture' => null, 'DbConfig' => ['one', 'two']];
		$this->assertEquals($expected, $this->Shell->tasks);

		$expected = ['Fixture' => null, 'DbConfig' => ['one', 'two']];
		$this->assertEquals($expected, Hash::normalize($this->Shell->tasks), 'Normalized results are wrong.');
		$this->assertEquals(['Comment', 'Posts'], $this->Shell->uses, 'Merged models are wrong.');
	}

	/**
	 * testInitialize method
	 *
	 * @return void
	 */
	public function testInitialize()
	{
		App::build([
			'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS],
			'Model' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS],
		], App::RESET);

		CakePlugin::load('TestPlugin');
		$this->Shell->tasks = ['DbConfig' => ['one', 'two']];
		$this->Shell->uses = ['TestPlugin.TestPluginPost'];
		$this->Shell->initialize();

		$this->assertTrue(isset($this->Shell->TestPluginPost));
		$this->assertInstanceOf('TestPluginPost', $this->Shell->TestPluginPost);
		$this->assertEquals('TestPluginPost', $this->Shell->modelClass);
		CakePlugin::unload('TestPlugin');

		$this->Shell->uses = ['Comment'];
		$this->Shell->initialize();
		$this->assertTrue(isset($this->Shell->Comment));
		$this->assertInstanceOf('Comment', $this->Shell->Comment);
		$this->assertEquals('Comment', $this->Shell->modelClass);
		$this->assertInstanceOf('DbConfigTask', $this->Shell->DbConfig);

		App::build();
	}

	/**
	 * testLoadModel method
	 *
	 * @return void
	 */
	public function testLoadModel()
	{
		App::build([
			'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS],
			'Model' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS],
		], App::RESET);

		$Shell = new TestMergeShell();
		$this->assertEquals('Comment', $Shell->Comment->alias);
		$this->assertInstanceOf('Comment', $Shell->Comment);
		$this->assertEquals('Comment', $Shell->modelClass);

		CakePlugin::load('TestPlugin');
		$this->Shell->loadModel('TestPlugin.TestPluginPost');
		$this->assertTrue(isset($this->Shell->TestPluginPost));
		$this->assertInstanceOf('TestPluginPost', $this->Shell->TestPluginPost);
		$this->assertEquals('TestPluginPost', $this->Shell->modelClass);
		CakePlugin::unload('TestPlugin');

		App::build();
	}

	/**
	 * testIn method
	 *
	 * @return void
	 */
	public function testIn()
	{
		$this->Shell->stdin
			->method('read')
			->willReturnOnConsecutiveCalls('n', 'Y', 'y', 'y', 'y', '0');

		$result = $this->Shell->in('Just a test?', ['y', 'n'], 'n');
		$this->assertEquals('n', $result);

		$result = $this->Shell->in('Just a test?', ['y', 'n'], 'n');
		$this->assertEquals('Y', $result);

		$result = $this->Shell->in('Just a test?', 'y,n', 'n');
		$this->assertEquals('y', $result);

		$result = $this->Shell->in('Just a test?', 'y/n', 'n');
		$this->assertEquals('y', $result);

		$result = $this->Shell->in('Just a test?', 'y', 'y');
		$this->assertEquals('y', $result);

		$result = $this->Shell->in('Just a test?', [0, 1, 2], '0');
		$this->assertEquals('0', $result);
	}

	/**
	 * Test in() when not interactive.
	 *
	 * @return void
	 */
	public function testInNonInteractive()
	{
		$this->Shell->interactive = false;

		$result = $this->Shell->in('Just a test?', 'y/n', 'n');
		$this->assertEquals('n', $result);
	}

	/**
	 * testOut method
	 *
	 * @return void
	 */
	public function testOut()
	{
		$this->Shell->stdout
			->expects($this->exactly(4))
			->method('write')
			->withConsecutive(
				["Just a test", 1],
				[['Just', 'a', 'test'], 1],
				[['Just', 'a', 'test'], 2],
				['', 1]
			);

		$this->Shell->out('Just a test');

		$this->Shell->out(['Just', 'a', 'test']);

		$this->Shell->out(['Just', 'a', 'test'], 2);

		$this->Shell->out();
	}

	/**
	 * test that verbose and quiet output levels work
	 *
	 * @return void
	 */
	public function testVerboseOutput()
	{
		$this->Shell->stdout
			->expects($this->exactly(3))
			->method('write')
			->withConsecutive(
				['Verbose', 1],
				['Normal', 1],
				['Quiet', 1]
			);

		$this->Shell->params['verbose'] = true;
		$this->Shell->params['quiet'] = false;

		$this->Shell->out('Verbose', 1, Shell::VERBOSE);
		$this->Shell->out('Normal', 1, Shell::NORMAL);
		$this->Shell->out('Quiet', 1, Shell::QUIET);
	}

	/**
	 * test that verbose and quiet output levels work
	 *
	 * @return void
	 */
	public function testQuietOutput()
	{
		$this->Shell->stdout->expects($this->once())->method('write')
			->with('Quiet', 1);

		$this->Shell->params['verbose'] = false;
		$this->Shell->params['quiet'] = true;

		$this->Shell->out('Verbose', 1, Shell::VERBOSE);
		$this->Shell->out('Normal', 1, Shell::NORMAL);
		$this->Shell->out('Quiet', 1, Shell::QUIET);
	}

/**
 * Test overwriting.
 *
 * @return void
 */
	public function testOverwrite() {
		$number = strlen('Some text I want to overwrite');

		$this->Shell->stdout->expects($this->exactly(5))
			->method('write')
			->willReturnOnConsecutiveCalls($number, null, 9, null, null)
			->withConsecutive(
				['Some <info>text</info> I want to overwrite', 0],
				[str_repeat("\x08", $number), 0],
				['Less text', 0],
				[str_repeat(' ', $number - 9), 0],
				["\n", 0] // Adicionando a chamada extra com '\n'
			);


		$this->Shell->out('Some <info>text</info> I want to overwrite', 0);
		$this->Shell->overwrite('Less text');

	}

	/**
	 * testErr method
	 *
	 * @return void
	 */
	public function testErr()
	{
		$this->Shell->stderr
			->expects($this->exactly(4))
			->method('write')
			->withConsecutive(
				["Just a test", 1],
				[["Just", "a", "test"], 1],
				[["Just", "a", "test"], 2],
				["", 1]
			);

		$this->Shell->err('Just a test');
		$this->Shell->err(["Just", "a", "test"]);
		$this->Shell->err(["Just", "a", "test"], 2);
		$this->Shell->err();
	}

	/**
	 * testNl
	 *
	 * @return void
	 */
	public function testNl()
	{
		$newLine = "\n";
		if (DS === '\\') {
			$newLine = "\r\n";
		}
		$this->assertEquals($this->Shell->nl(), $newLine);
		$this->assertEquals($this->Shell->nl(true), $newLine);
		$this->assertEquals("", $this->Shell->nl(false));
		$this->assertEquals($this->Shell->nl(2), $newLine . $newLine);
		$this->assertEquals($this->Shell->nl(1), $newLine);
	}

	/**
	 * testHr
	 *
	 * @return void
	 */
	public function testHr()
	{
		$bar = '---------------------------------------------------------------';

		$this->Shell->stdout->expects($this->exactly(9))
			->method('write')
			->withConsecutive(
				['', 0],
				[$bar, 1],
				['', 0],
				["", true],
				[$bar, 1],
				["", true],
				["", 2],
				[$bar, 1],
				["", 2]
			);

		$this->Shell->hr();

		$this->Shell->hr(true);

		$this->Shell->hr(2);
	}

	/**
	 * testError
	 *
	 * @return void
	 */
	public function testError()
	{
		$this->Shell->stderr->expects($this->exactly(3))
			->method('write')
			->withConsecutive(
				["<error>Error:</error> Foo Not Found", 1],
				["<error>Error:</error> Foo Not Found", 1],
				["Searched all...", 1]
			);


		$this->Shell->error('Foo Not Found');
		$this->assertSame($this->Shell->stopped, 1);

		$this->Shell->stopped = null;

		$this->Shell->error('Foo Not Found', 'Searched all...');
		$this->assertSame($this->Shell->stopped, 1);
	}

	/**
	 * testLoadTasks method
	 *
	 * @return void
	 */
	public function testLoadTasks()
	{
		$this->assertTrue($this->Shell->loadTasks());

		$this->Shell->tasks = null;
		$this->assertTrue($this->Shell->loadTasks());

		$this->Shell->tasks = false;
		$this->assertTrue($this->Shell->loadTasks());

		$this->Shell->tasks = true;
		$this->assertTrue($this->Shell->loadTasks());

		$this->Shell->tasks = [];
		$this->assertTrue($this->Shell->loadTasks());

		$this->Shell->tasks = ['TestApple'];
		$this->assertTrue($this->Shell->loadTasks());
		$this->assertInstanceOf('TestAppleTask', $this->Shell->TestApple);

		$this->Shell->tasks = 'TestBanana';
		$this->assertTrue($this->Shell->loadTasks());
		$this->assertInstanceOf('TestAppleTask', $this->Shell->TestApple);
		$this->assertInstanceOf('TestBananaTask', $this->Shell->TestBanana);

		unset($this->Shell->ShellTestApple, $this->Shell->TestBanana);

		$this->Shell->tasks = ['TestApple', 'TestBanana'];
		$this->assertTrue($this->Shell->loadTasks());
		$this->assertInstanceOf('TestAppleTask', $this->Shell->TestApple);
		$this->assertInstanceOf('TestBananaTask', $this->Shell->TestBanana);
	}

	/**
	 * test that __get() makes args and params references
	 *
	 * @return void
	 */
	public function testMagicGetArgAndParamReferences()
	{
		$this->Shell->tasks = ['TestApple'];
		$this->Shell->args = ['one'];
		$this->Shell->params = ['help' => false];
		$this->Shell->loadTasks();
		$result = $this->Shell->TestApple;

		$this->Shell->args = ['one', 'two'];

		$this->assertSame($this->Shell->args, $result->args);
		$this->assertSame($this->Shell->params, $result->params);
	}

	/**
	 * testShortPath method
	 *
	 * @return void
	 */
	public function testShortPath()
	{
		$path = $expected = DS . 'tmp' . DS . 'ab' . DS . 'cd';
		$this->assertEquals($expected, $this->Shell->shortPath($path));

		$path = $expected = DS . 'tmp' . DS . 'ab' . DS . 'cd' . DS;
		$this->assertEquals($expected, $this->Shell->shortPath($path));

		$path = $expected = DS . 'tmp' . DS . 'ab' . DS . 'index.php';
		$this->assertEquals($expected, $this->Shell->shortPath($path));

		$path = DS . 'tmp' . DS . 'ab' . DS . DS . 'cd';
		$expected = DS . 'tmp' . DS . 'ab' . DS . 'cd';
		$this->assertEquals($expected, $this->Shell->shortPath($path));

		$path = 'tmp' . DS . 'ab';
		$expected = 'tmp' . DS . 'ab';
		$this->assertEquals($expected, $this->Shell->shortPath($path));

		$path = 'tmp' . DS . 'ab';
		$expected = 'tmp' . DS . 'ab';
		$this->assertEquals($expected, $this->Shell->shortPath($path));

		$path = APP;
		$expected = DS . basename(APP) . DS;
		$this->assertEquals($expected, $this->Shell->shortPath($path));

		$path = APP . 'index.php';
		$expected = DS . basename(APP) . DS . 'index.php';
		$this->assertEquals($expected, $this->Shell->shortPath($path));
	}

	/**
	 * testCreateFile method
	 *
	 * @return void
	 */
	public function testCreateFileNonInteractive()
	{
		$eol = PHP_EOL;

		$path = TMP . 'shell_test';
		$file = $path . DS . 'file1.php';

		new Folder($path, true);

		$this->Shell->interactive = false;

		$contents = "<?php{$eol}echo 'test';${eol}\$te = 'st';{$eol}";
		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($file));
		$this->assertEquals(file_get_contents($file), $contents);

		$contents = "<?php\necho 'another test';\n\$te = 'st';\n";
		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($file));
		$this->assertTextEquals(file_get_contents($file), $contents);
	}

	/**
	 * test createFile when the shell is interactive.
	 *
	 * @return void
	 */
	public function testCreateFileInteractive()
	{
		$eol = PHP_EOL;

		$path = TMP . 'shell_test';
		$file = $path . DS . 'file1.php';
		new Folder($path, true);

		$this->Shell->interactive = true;

		$this->Shell->stdin->expects($this->exactly(2))
			->method('read')
			->willReturnOnConsecutiveCalls('n', 'y');

		$contents = "<?php{$eol}echo 'yet another test';{$eol}\$te = 'st';{$eol}";
		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($file));
		$this->assertEquals(file_get_contents($file), $contents);

		// no overwrite
		$contents = 'new contents';
		$result = $this->Shell->createFile($file, $contents);
		$this->assertFalse($result);
		$this->assertTrue(file_exists($file));
		$this->assertNotEquals($contents, file_get_contents($file));

		// overwrite
		$contents = 'more new contents';
		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($file));
		$this->assertEquals($contents, file_get_contents($file));
	}

	/**
	 * Test that you can't create files that aren't writable.
	 *
	 * @return void
	 */
	public function testCreateFileNoPermissions()
	{
		$this->skipIf(DIRECTORY_SEPARATOR === '\\', 'Cant perform operations using permissions on Windows.');

		$path = TMP . 'shell_test';
		$file = $path . DS . 'no_perms';

		if (!is_dir($path)) {
			mkdir($path);
		}
		chmod($path, 0444);

		$this->Shell->createFile($file, 'testing');
		$this->assertFalse(file_exists($file));

		chmod($path, 0744);
		rmdir($path);
	}

	/**
	 * test hasTask method
	 *
	 * @return void
	 */
	public function testHasTask()
	{
		$this->Shell->tasks = ['Extract', 'DbConfig'];
		$this->Shell->loadTasks();

		$this->assertTrue($this->Shell->hasTask('extract'));
		$this->assertTrue($this->Shell->hasTask('Extract'));
		$this->assertFalse($this->Shell->hasTask('random'));

		$this->assertTrue($this->Shell->hasTask('db_config'));
		$this->assertTrue($this->Shell->hasTask('DbConfig'));
	}

	/**
	 * test the hasMethod
	 *
	 * @return void
	 */
	public function testHasMethod()
	{
		$this->assertTrue($this->Shell->hasMethod('do_something'));
		$this->assertFalse($this->Shell->hasMethod('hr'), 'hr is callable');
		$this->assertFalse($this->Shell->hasMethod('_secret'), '_secret is callable');
		$this->assertFalse($this->Shell->hasMethod('no_access'), 'no_access is callable');
	}

	/**
	 * test run command calling main.
	 *
	 * @return void
	 */
	public function testRunCommandMain()
	{
		$Mock = $this->getMock('Shell', ['main', 'startup'], [], '', false);

		$Mock->expects($this->once())->method('main')->will($this->returnValue(true));
		$result = $Mock->runCommand(null, []);
		$this->assertTrue($result);
	}

	/**
	 * test run command calling a legit method.
	 *
	 * @return void
	 */
	public function testRunCommandWithMethod()
	{
		$Mock = $this->getMock('Shell', ['hit_me', 'startup'], [], '', false);

		$Mock->expects($this->once())->method('hit_me')->will($this->returnValue(true));
		$result = $Mock->runCommand('hit_me', []);
		$this->assertTrue($result);
	}

	/**
	 * test run command causing exception on Shell method.
	 *
	 * @return void
	 */
	public function testRunCommandBaseclassMethod()
	{
		$Mock = $this->getMock('Shell', ['startup', 'getOptionParser', 'out'], [], '', false);
		$Parser = $this->getMock('ConsoleOptionParser', [], [], '', false);
		$Parser->expects($this->once())->method('help');
		$Mock->expects($this->once())->method('getOptionParser')
			->will($this->returnValue($Parser));
		$Mock->expects($this->once())->method('out');
		$Mock->runCommand('hr', []);
	}

	/**
	 * test run command causing exception on Shell method.
	 *
	 * @return void
	 */
	public function testRunCommandMissingMethod()
	{
		$Mock = $this->getMock('Shell', ['startup', 'getOptionParser', 'out'], [], '', false);
		$Parser = $this->getMock('ConsoleOptionParser', [], [], '', false);
		$Parser->expects($this->once())->method('help');
		$Mock->expects($this->once())->method('getOptionParser')
			->will($this->returnValue($Parser));
		$Mock->expects($this->once())->method('out');
		$result = $Mock->runCommand('idontexist', []);
		$this->assertFalse($result);
	}

	/**
	 * test unknown option causes display of error and help.
	 *
	 * @return void
	 */
	public function testRunCommandUnknownOption()
	{
		$output = $this->getMock('ConsoleOutput', [], [], '', false);
		$error = $this->getMock('ConsoleOutput', [], [], '', false);
		$in = $this->getMock('ConsoleInput', [], [], '', false);

		$Parser = $this->getMock('ConsoleOptionParser', [], [], '', false);
		$Parser->expects($this->once())->method('parse')
			->with(['--unknown'])
			->will($this->throwException(new ConsoleException('Unknown option `unknown`')));
		$Parser->expects($this->once())->method('help');

		$Shell = $this->getMock('ShellTestShell', ['getOptionParser'], [$output, $error, $in]);

		$Shell->expects($this->once())->method('getOptionParser')
			->will($this->returnValue($Parser));
		$Shell->stderr->expects($this->once())->method('write');
		$Shell->stdout->expects($this->once())->method('write');

		$Shell->runCommand('do_something', ['do_something', '--unknown']);
	}

	/**
	 * test that a --help causes help to show.
	 *
	 * @return void
	 */
	public function testRunCommandTriggeringHelp()
	{
		$Parser = $this->getMock('ConsoleOptionParser', [], [], '', false);
		$Parser->expects($this->once())->method('parse')
			->with(['--help'])
			->will($this->returnValue([['help' => true], []]));
		$Parser->expects($this->once())->method('help');

		$Shell = $this->getMock('Shell', ['getOptionParser', 'out', 'startup', '_welcome'], [], '', false);
		$Shell->expects($this->once())->method('getOptionParser')
			->will($this->returnValue($Parser));
		$Shell->expects($this->once())->method('out');

		$Shell->runCommand(null, ['--help']);
	}

	/**
	 * test that runCommand will call runCommand on the task.
	 *
	 * @return void
	 */
	public function testRunCommandHittingTask()
	{
		$Shell = $this->getMock('Shell', ['hasTask', 'startup'], [], '', false);
		$task = $this->getMock('Shell', ['execute', 'runCommand'], [], '', false);
		$task->expects($this->any())
			->method('runCommand')
			->with('execute', ['one', 'value']);

		$Shell->expects($this->once())->method('startup');
		$Shell->expects($this->any())
			->method('hasTask')
			->will($this->returnValue(true));

		$Shell->RunCommand = $task;

		$Shell->runCommand('run_command', ['run_command', 'one', 'value']);
	}

	/**
	 * test wrapBlock wrapping text.
	 *
	 * @return void
	 */
	public function testWrapText()
	{
		$text = 'This is the song that never ends. This is the song that never ends. This is the song that never ends.';
		$result = $this->Shell->wrapText($text, 33);
		$expected = <<<TEXT
This is the song that never ends.
This is the song that never ends.
This is the song that never ends.
TEXT;
		$this->assertTextEquals($expected, $result, 'Text not wrapped.');

		$result = $this->Shell->wrapText($text, ['indent' => '  ', 'width' => 33]);
		$expected = <<<TEXT
  This is the song that never ends.
  This is the song that never ends.
  This is the song that never ends.
TEXT;
		$this->assertTextEquals($expected, $result, 'Text not wrapped.');
	}

	/**
	 * Testing camel cased naming of tasks
	 *
	 * @return void
	 */
	public function testShellNaming()
	{
		$this->Shell->tasks = ['TestApple'];
		$this->Shell->loadTasks();
		$expected = 'TestApple';
		$this->assertEquals($expected, $this->Shell->TestApple->name);
	}

	/**
	 * Test reading params
	 *
	 * @dataProvider paramReadingDataProvider
	 */
	public function testParamReading($toRead, $expected)
	{
		$this->Shell->params = [
			'key' => 'value',
			'help' => false,
			'emptykey' => '',
			'truthy' => true,
		];
		$this->assertSame($expected, $this->Shell->param($toRead));
	}

	/**
	 * Data provider for testing reading values with Shell::param()
	 *
	 * @return array
	 */
	public function paramReadingDataProvider()
	{
		return [
			[
				'key',
				'value',
			],
			[
				'help',
				false,
			],
			[
				'emptykey',
				'',
			],
			[
				'truthy',
				true,
			],
			[
				'does_not_exist',
				null,
			],
		];
	}

	/**
	 * Test that option parsers are created with the correct name/command.
	 *
	 * @return void
	 */
	public function testGetOptionParser()
	{
		$this->Shell->name = 'test';
		$this->Shell->plugin = 'plugin';
		$parser = $this->Shell->getOptionParser();

		$this->assertEquals('plugin.test', $parser->command());
	}

	/**
	 * Test file and console and logging
	 *
	 * @return void
	 */
	public function testFileAndConsoleLogging()
	{
		CakeLog::disable('stdout');
		CakeLog::disable('stderr');
		// file logging
		$this->Shell->log_something();
		$this->assertTrue(file_exists(LOGS . 'error.log'));

		unlink(LOGS . 'error.log');
		$this->assertFalse(file_exists(LOGS . 'error.log'));

		// both file and console logging
		require_once CORE_TEST_CASES . DS . 'Log' . DS . 'Engine' . DS . 'ConsoleLogTest.php';
		$mock = $this->getMock('ConsoleLog', ['write'], [
			['types' => 'error'],
		]);
		TestCakeLog::config('console', [
			'engine' => 'Console',
			'stream' => 'php://stderr',
		]);
		TestCakeLog::replace('console', $mock);
		$mock->expects($this->once())
			->method('write')
			->with('error', $this->Shell->testMessage);
		$this->Shell->log_something();
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertStringContainsString($this->Shell->testMessage, $contents);

		CakeLog::enable('stdout');
		CakeLog::enable('stderr');
	}

	/**
	 * Tests that _useLogger works properly
	 *
	 * @return void
	 */
	public function testProtectedUseLogger()
	{
		CakeLog::drop('stdout');
		CakeLog::drop('stderr');
		$this->Shell->useLogger(true);
		$this->assertNotEmpty(CakeLog::stream('stdout'));
		$this->assertNotEmpty(CakeLog::stream('stderr'));
		$this->Shell->useLogger(false);
		$this->assertFalse(CakeLog::stream('stdout'));
		$this->assertFalse(CakeLog::stream('stderr'));
	}

	/**
	 * Test file and console and logging quiet output
	 *
	 * @return void
	 */
	public function testQuietLog()
	{
		$output = $this->getMock('ConsoleOutput', [], [], '', false);
		$error = $this->getMock('ConsoleOutput', [], [], '', false);
		$in = $this->getMock('ConsoleInput', [], [], '', false);
		$this->Shell = $this->getMock('ShellTestShell', ['_useLogger'], [$output, $error, $in]);
		$this->Shell->expects($this->once())->method('_useLogger')->with(false);
		$this->Shell->runCommand('foo', ['--quiet']);
	}

	/**
	 * Test getting an instance of a helper
	 *
	 * @return void
	 */
	public function testGetInstanceOfHelper()
	{
		$actual = $this->Shell->helper("progress");
		$this->assertInstanceOf("ProgressShellHelper", $actual);
	}

	/**
	 * Test getting an invalid helper
	 *
	 * @return void
	 */
	public function testGetInvalidHelper()
	{
		$this->expectException(\RunTimeException::class);
		$this->Shell->helper("tomato");
	}

	/**
	 * Test that shell loggers do not get overridden in constructor if already configured
	 *
	 * @return void
	 */
	public function testShellLoggersDoNotGetOverridden()
	{
		$shell = $this->getMock(
			"Shell", [
			"_loggerIsConfigured",
			"_configureStdOutLogger",
			"_configureStdErrLogger",
		],
			[],
			"",
			false
		);
		$shell->expects($this->exactly(2))
			->method("_loggerIsConfigured")
			->will($this->returnValue(true));
		$shell->expects($this->never())
			->method("_configureStdOutLogger");
		$shell->expects($this->never())
			->method("_configureStdErrLogger");
		$shell->__construct();
	}
}
