<?php
/**
 * AclShell Test file
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

App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('ShellDispatcher', 'Console');
App::uses('Shell', 'Console');
App::uses('AclShell', 'Console/Command');
App::uses('ComponentCollection', 'Controller');
App::uses('TestStringOutput', 'Test/Case/Console/Command');

class AclShellNoExit extends AclShell {

	public array $dispatchShellArgs = [];

	protected function _stop($status = 0) {}


	public function dispatchShell()
	{
		$this->dispatchShellArgs[] = func_get_args();
	}
}
/**
 * AclShellTest class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class AclShellTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array('core.aco', 'core.aro', 'core.aros_aco');

	/**
	 * @var \AclShellNoExit
	 */
	private $Task;

	private TestStringOutput $output;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp(): void {
		parent::setUp();
		Configure::write('Acl.database', 'test');
		Configure::write('Acl.classname', 'DbAcl');

		$this->output = new TestStringOutput();
		$this->input = new TestStringOutput();

		$this->Task = new AclShellNoExit($this->output, $this->output, $this->input);

		$collection = new ComponentCollection();
		$this->Task->Acl = new AclComponent($collection);
		$this->Task->params['datasource'] = 'test';
	}

/**
 * test that model.foreign_key output works when looking at acl rows
 *
 * @return void
 */
	public function testViewWithModelForeignKeyOutput() {
		$this->Task->command = 'view';
		$this->Task->startup();
		$data = array(
			'parent_id' => null,
			'model' => 'MyModel',
			'foreign_key' => 2,
		);
		$this->Task->Acl->Aro->create($data);
		$this->Task->Acl->Aro->save();
		$this->Task->args[0] = 'aro';

		$this->Task->view();

		$this->assertStringContainsString('[1] ROOT', $this->output->output);
		$this->assertStringContainsString('[3] Gandalf', $this->output->output);
		$this->assertStringContainsString('[5] MyModel.2', $this->output->output);
	}

/**
 * test view with an argument
 *
 * @return void
 */
	public function testViewWithArgument() {
		$this->Task->args = array('aro', 'admins');
		$this->Task->view();

		$this->assertStringContainsString('Aro tree:', $this->output->output);
		$this->assertStringContainsString('  [2] admins', $this->output->output);
		$this->assertStringContainsString('    [3] Gandalf', $this->output->output);
		$this->assertStringContainsString('    [4] Elrond', $this->output->output);
	}

/**
 * test the method that splits model.foreign key. and that it returns an array.
 *
 * @return void
 */
	public function testParsingModelAndForeignKey() {
		$result = $this->Task->parseIdentifier('Model.foreignKey');
		$expected = array('model' => 'Model', 'foreign_key' => 'foreignKey');
		$this->assertEquals($expected, $result);

		$result = $this->Task->parseIdentifier('mySuperUser');
		$this->assertEquals('mySuperUser', $result);

		$result = $this->Task->parseIdentifier('111234');
		$this->assertEquals('111234', $result);
	}

/**
 * test creating aro/aco nodes
 *
 * @return void
 */
	public function testCreate() {
		$this->Task->args = array('aro', 'root', 'User.1');

		$this->Task->create();

		$this->assertStringContainsString($this->output->styleText("<success>New Aro</success> 'User.1' created."), $this->output->output);

		$Aro = ClassRegistry::init('Aro');
		$Aro->cacheQueries = false;
		$result = $Aro->read();
		$this->assertEquals('User', $result['Aro']['model']);
		$this->assertEquals(1, $result['Aro']['foreign_key']);
		$this->assertEquals(null, $result['Aro']['parent_id']);
		$id = $result['Aro']['id'];

		$this->Task->args = array('aro', 'User.1', 'User.3');
		$this->Task->create();

		$this->assertStringContainsString($this->output->styleText("<success>New Aro</success> 'User.3' created."), $this->output->output);

		$Aro = ClassRegistry::init('Aro');
		$result = $Aro->read();
		$this->assertEquals('User', $result['Aro']['model']);
		$this->assertEquals(3, $result['Aro']['foreign_key']);
		$this->assertEquals($id, $result['Aro']['parent_id']);

		$this->Task->args = array('aro', 'root', 'somealias');
		$this->Task->create();

		$this->assertStringContainsString($this->output->styleText("<success>New Aro</success> 'somealias' created."), $this->output->output);

		$Aro = ClassRegistry::init('Aro');
		$result = $Aro->read();
		$this->assertEquals('somealias', $result['Aro']['alias']);
		$this->assertEquals(null, $result['Aro']['model']);
		$this->assertEquals(null, $result['Aro']['foreign_key']);
		$this->assertEquals(null, $result['Aro']['parent_id']);
	}

/**
 * test the delete method with different node types.
 *
 * @return void
 */
	public function testDelete() {
		$this->Task->args = array('aro', 'AuthUser.1');
		$this->Task->delete();
		$this->assertStringContainsString($this->output->styleText("<success>Aro deleted.</success>"), $this->output->output);

		$Aro = ClassRegistry::init('Aro');
		$result = $Aro->findById(3);
		$this->assertSame(array(), $result);
	}

/**
 * test setParent method.
 *
 * @return void
 */
	public function testSetParent() {
		$this->Task->args = array('aro', 'AuthUser.2', 'root');
		$this->Task->setParent();

		$Aro = ClassRegistry::init('Aro');
		$result = $Aro->read(null, 4);
		$this->assertEquals(null, $result['Aro']['parent_id']);
	}

/**
 * test grant
 *
 * @return void
 */
	public function testGrant() {
		$this->Task->args = array('AuthUser.2', 'ROOT/Controller1', 'create');
		$this->Task->grant();

		$this->assertStringContainsString('granted', $this->output->output);
		$node = $this->Task->Acl->Aro->node(array('model' => 'AuthUser', 'foreign_key' => 2));
		$node = $this->Task->Acl->Aro->read(null, $node[0]['Aro']['id']);

		$this->assertFalse(empty($node['Aco'][0]));
		$this->assertEquals(1, $node['Aco'][0]['Permission']['_create']);
	}

/**
 * test deny
 *
 * @return void
 */
	public function testDeny() {
		$this->Task->args = array('AuthUser.2', 'ROOT/Controller1', 'create');
		$this->Task->deny();

		$this->assertStringContainsString('Permission denied', $this->output->output);

		$node = $this->Task->Acl->Aro->node(array('model' => 'AuthUser', 'foreign_key' => 2));
		$node = $this->Task->Acl->Aro->read(null, $node[0]['Aro']['id']);
		$this->assertFalse(empty($node['Aco'][0]));
		$this->assertEquals(-1, $node['Aco'][0]['Permission']['_create']);
	}

/**
 * test checking allowed and denied perms
 *
 * @return void
 */
	public function testCheck() {

		$this->Task->args = array('AuthUser.2', 'ROOT/Controller1', '*');
		$this->Task->check();
		$this->assertStringContainsString('not allowed', $this->output->output);

		$this->output->output = '';
		$this->Task->args = array('AuthUser.2', 'ROOT/Controller1', 'create');
		$this->Task->grant();
		$this->assertStringContainsString('granted', $this->output->output);

		$this->output->output = '';
		$this->Task->args = array('AuthUser.2', 'ROOT/Controller1', 'create');
		$this->Task->check();
		$this->assertMatchesRegularExpression('/is.*allowed/', $this->output->output);

		$this->output->output = '';
		$this->Task->args = array('AuthUser.2', 'ROOT/Controller1', 'delete');
		$this->Task->check();
		$this->assertMatchesRegularExpression('/not.*allowed/', $this->output->output);
	}

/**
 * test inherit and that it 0's the permission fields.
 *
 * @return void
 */
	public function testInherit() {

		$this->Task->args = array('AuthUser.2', 'ROOT/Controller1', 'create');
		$this->Task->grant();
		$this->assertMatchesRegularExpression('/Permission .*granted/', $this->output->output);

		$this->output->output = '';
		$this->Task->args = array('AuthUser.2', 'ROOT/Controller1', 'all');
		$this->Task->inherit();
		$this->assertMatchesRegularExpression('/Permission .*inherited/', $this->output->output);

		$node = $this->Task->Acl->Aro->node(array('model' => 'AuthUser', 'foreign_key' => 2));
		$node = $this->Task->Acl->Aro->read(null, $node[0]['Aro']['id']);
		$this->assertFalse(empty($node['Aco'][0]));
		$this->assertEquals(0, $node['Aco'][0]['Permission']['_create']);
	}

/**
 * test getting the path for an aro/aco
 *
 * @return void
 */
	public function testGetPath() {
		$this->Task->args = array('aro', 'AuthUser.2');
		$node = $this->Task->Acl->Aro->node(array('model' => 'AuthUser', 'foreign_key' => 2));
		$first = $node[0]['Aro']['id'];
		$second = $node[1]['Aro']['id'];
		$last = $node[2]['Aro']['id'];
		$this->Task->getPath();
		$linhas = explode("\n", $this->output->output);
		$this->assertEquals('[' . $last . '] ROOT', $linhas[2]);
		$this->assertEquals('  [' . $second . '] admins', $linhas[3]);
		$this->assertEquals('    [' . $first . '] Elrond', $linhas[4]);
	}

	public function testInitDb() {
		$this->Task->initdb();
		$this->assertEquals('schema create DbAcl', $this->Task->dispatchShellArgs[0][0]);
	}
}
