<?php
/**
 * CommandListShellTest file
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
 * @since         CakePHP v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CommandListShell', 'Console/Command');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('Shell', 'Console');
App::uses('CommandTask', 'Console/Command/Task');
App::uses('TestStringOutput', 'Test/Case/Console/Command');

/**
 * CommandListShellTest
 *
 * @package       Cake.Test.Case.Console.Command
 */
class CommandListShellTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp(): void {
		parent::setUp();
		App::build(array(
			'Plugin' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS
			),
			'Console/Command' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'Console' . DS . 'Command' . DS
			)
		), App::RESET);
		CakePlugin::load(array('TestPlugin', 'TestPluginTwo'));

		$out = new TestStringOutput();
		$in = $this->getMockBuilder('ConsoleInput')->getMock();

		$this->Shell = $this->getMockBuilder('CommandListShell')
			->onlyMethods(array('in', '_stop', 'clear'))->setConstructorArgs(array($out, $out, $in))
		->getMock();

		$this->Shell->Command = $this->getMockBuilder('CommandTask')
			->onlyMethods(array('in', '_stop', 'clear'))
			->setConstructorArgs(array($out, $out, $in))->getMock();
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown(): void {
		parent::tearDown();
		unset($this->Shell);
		CakePlugin::unload();
	}

/**
 * test that main finds core shells.
 *
 * @return void
 */
	public function testMain() {
		$this->Shell->main();
		$output = $this->Shell->stdout->output;

		$expected = "/\[.*TestPlugin.*\] example/";
		$this->assertMatchesRegularExpression($expected, $output);

		$expected = "/\[.*TestPluginTwo.*\] example, welcome/";
		$this->assertMatchesRegularExpression($expected, $output);

		$expected = "/\[.*CORE.*\] acl, api, bake, command_list, completion, console, i18n, schema, server, test, testsuite, upgrade/";
		$this->assertMatchesRegularExpression($expected, $output);

		$expected = "/\[.*app.*\] sample/";
		$this->assertMatchesRegularExpression($expected, $output);
	}

/**
 * test xml output.
 *
 * @return void
 */
	public function testMainXml() {
		$this->Shell->params['xml'] = true;
		$this->Shell->main();

		$output = $this->Shell->stdout->output;

		$find = '<shell name="sample" call_as="sample" provider="app" help="sample -h"/>';
		$this->assertStringContainsString($find, $output);

		$find = '<shell name="bake" call_as="bake" provider="CORE" help="bake -h"/>';
		$this->assertStringContainsString($find, $output);

		$find = '<shell name="welcome" call_as="TestPluginTwo.welcome" provider="TestPluginTwo" help="TestPluginTwo.welcome -h"/>';
		$this->assertStringContainsString($find, $output);
	}
}
