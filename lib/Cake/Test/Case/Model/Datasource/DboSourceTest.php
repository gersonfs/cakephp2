<?php
/**
 * DboSourceTest file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Datasource
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('DataSource', 'Model/Datasource');
App::uses('DboSource', 'Model/Datasource');
App::uses('DboTestSource', 'Model/Datasource');
App::uses('DboSecondTestSource', 'Model/Datasource');
App::uses('MockDataSource', 'Model/Datasource');
App::uses('PDOStatementFake', 'Test/Case/Util');

require_once dirname(dirname(__FILE__)) . DS . 'models.php';

/**
 * MockPDO
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class MockPDO extends PDO {

/**
 * Constructor.
 */
	public function __construct() {
	}

	/**
	 * @return false|string|void
	 */
	public function quote($string, $type = PDO::PARAM_INT)
	{
		return parent::quote($string, $type);
	}

	/**
	 * @return false|int|void
	 */
	public function exec($statement)
	{
		return parent::exec($statement);
	}

	/**
	 * @return false|string|void
	 */
	public function lastInsertId($name = null)
	{
		return parent::lastInsertId($name);
	}

	/**
	 * @return false|\PDOStatement|void
	 */
	public function prepare($query, $options = [])
	{
		return parent::prepare($query, $options);
	}

	/**
	 * @return false|\PDOStatement|void
	 */
	public function query(string $statement, ?int $mode = PDO::ATTR_DEFAULT_FETCH_MODE, $arg3 = null, ...$fechModeArgs)
	{
		return parent::query($statement, $mode, $arg3, $fechModeArgs);
	}

}

/**
 * MockDataSource
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class MockDataSource extends DataSource {
}

/**
 * DboTestSource
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class DboTestSource extends DboSource {

	public $nestedSupport = false;

	public function connect($config = array()) {
		$this->connected = true;
	}

	public function mergeAssociation(&$data, &$merge, $association, $type, $selfJoin = false) {
		return parent::_mergeAssociation($data, $merge, $association, $type, $selfJoin);
	}

	public function setConfig($config = array()) {
		$this->config = $config;
	}

	public function setConnection($conn) {
		$this->_connection = $conn;
	}

	public function nestedTransactionSupported() {
		return $this->useNestedTransactions && $this->nestedSupport;
	}

}

/**
 * DboSecondTestSource
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class DboSecondTestSource extends DboSource {

	public $startQuote = '_';

	public $endQuote = '_';

	public function connect($config = array()) {
		$this->connected = true;
	}

	public function mergeAssociation(&$data, &$merge, $association, $type, $selfJoin = false) {
		return parent::_mergeAssociation($data, $merge, $association, $type, $selfJoin);
	}

	public function setConfig($config = array()) {
		$this->config = $config;
	}

	public function setConnection($conn) {
		$this->_connection = $conn;
	}

}

/**
 * DboThirdTestSource
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class DboThirdTestSource extends DboSource {

	public function connect($config = array()) {
		$this->connected = true;
	}

	public function cacheMethodHasher($value) {
		return hash('sha1', $value);
	}

}

/**
 * DboFourthTestSource
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class DboFourthTestSource extends DboSource {

	public function connect($config = array()) {
		$this->connected = true;
	}

	public function cacheMethodFilter($method, $key, $value) {
		if ($method === 'name') {
			if ($value === '`menus`') {
				return false;
			} elseif ($key === '1fca740733997f1ebbedacfc7678592a') {
				return false;
			}
		} elseif ($method === 'fields') {
			$endsWithName = preg_grep('/`name`$/', $value);

			return count($endsWithName) === 0;
		}

		return true;
	}

}

/**
 * DboSourceTest class
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class DboSourceTest extends CakeTestCase {

/**
 * autoFixtures property
 *
 * @var bool
 */
	public $autoFixtures = false;

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array(
		'core.apple', 'core.article', 'core.articles_tag', 'core.attachment', 'core.comment',
		'core.sample', 'core.tag', 'core.user', 'core.post', 'core.author', 'core.data_test'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp(): void {
		parent::setUp();

		$this->testDb = new DboTestSource();
		$this->testDb->cacheSources = false;
		$this->testDb->startQuote = '`';
		$this->testDb->endQuote = '`';

		$this->Model = new TestModel();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown(): void {
		parent::tearDown();
		unset($this->Model);
	}

/**
 * test that booleans and null make logical condition strings.
 *
 * @return void
 */
	public function testBooleanNullConditionsParsing() {
		$result = $this->testDb->conditions(true);
		$this->assertEquals(' WHERE 1 = 1', $result, 'true conditions failed %s');

		$result = $this->testDb->conditions(false);
		$this->assertEquals(' WHERE 0 = 1', $result, 'false conditions failed %s');

		$result = $this->testDb->conditions(null);
		$this->assertEquals(' WHERE 1 = 1', $result, 'null conditions failed %s');

		$result = $this->testDb->conditions(array());
		$this->assertEquals(' WHERE 1 = 1', $result, 'array() conditions failed %s');

		$result = $this->testDb->conditions('');
		$this->assertEquals(' WHERE 1 = 1', $result, '"" conditions failed %s');

		$result = $this->testDb->conditions(' ', '"  " conditions failed %s');
		$this->assertEquals(' WHERE 1 = 1', $result);
	}

/**
 * test that booleans work on empty set.
 *
 * @return void
 */
	public function testBooleanEmptyConditionsParsing() {
		$result = $this->testDb->conditions(array('OR' => array()));
		$this->assertEquals(' WHERE  1 = 1', $result, 'empty conditions failed');

		$result = $this->testDb->conditions(array('OR' => array('OR' => array())));
		$this->assertEquals(' WHERE  1 = 1', $result, 'nested empty conditions failed');
	}

/**
 * test that SQL JSON operators can be used.
 *
 * @return void
 */
	public function testColumnHyphenOperator() {
		//PostgreSQL style
		$result = $this->testDb->conditions(array('Foo.bar->>\'fieldName\'' => 42));
		$this->assertEquals(' WHERE `Foo`.`bar`->>\'fieldName\' = 42', $result, 'SQL JSON operator failed');
		$result = $this->testDb->conditions(array('Foo.bar->\'fieldName\'' => 42));
		$this->assertEquals(' WHERE `Foo`.`bar`->\'fieldName\' = 42', $result, 'SQL JSON operator failed');

		// MYSQL style
		$result = $this->testDb->conditions(array('Foo.bar->>\'$.fieldName\'' => 42));
		$this->assertEquals(' WHERE `Foo`.`bar`->>\'$.fieldName\' = 42', $result, 'SQL JSON operator failed');

		//Without defining table name.
		$result = $this->testDb->conditions(array('bar->>\'$.fieldName\'' => 42));
		$this->assertEquals(' WHERE `bar`->>\'$.fieldName\' = 42', $result, 'SQL JSON operator failed');
	}

/**
 * test that order() will accept objects made from DboSource::expression
 *
 * @return void
 */
	public function testOrderWithExpression() {
		$expression = $this->testDb->expression("CASE Sample.id WHEN 1 THEN 'Id One' ELSE 'Other Id' END AS case_col");
		$result = $this->testDb->order($expression);
		$expected = " ORDER BY CASE Sample.id WHEN 1 THEN 'Id One' ELSE 'Other Id' END AS case_col";
		$this->assertEquals($expected, $result);
	}

/**
 * testMergeAssociations method
 *
 * @return void
 */
	public function testMergeAssociations() {
		$data = array('Article2' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article',
				'body' => 'First Article Body', 'published' => 'Y',
				'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
		));
		$merge = array('Topic' => array(array(
			'id' => '1', 'topic' => 'Topic', 'created' => '2007-03-17 01:16:23',
			'updated' => '2007-03-17 01:18:31'
		)));
		$expected = array(
			'Article2' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article',
				'body' => 'First Article Body', 'published' => 'Y',
				'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'Topic' => array(
				'id' => '1', 'topic' => 'Topic', 'created' => '2007-03-17 01:16:23',
				'updated' => '2007-03-17 01:18:31'
			)
		);
		$this->testDb->mergeAssociation($data, $merge, 'Topic', 'hasOne');
		$this->assertEquals($expected, $data);

		$data = array('Article2' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article',
				'body' => 'First Article Body', 'published' => 'Y',
				'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
		));
		$merge = array('User2' => array(array(
			'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
			'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
		)));

		$expected = array(
			'Article2' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article',
				'body' => 'First Article Body', 'published' => 'Y',
				'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'User2' => array(
				'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
			)
		);
		$this->testDb->mergeAssociation($data, $merge, 'User2', 'belongsTo');
		$this->assertEquals($expected, $data);

		$data = array(
			'Article2' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)
		);
		$merge = array(array('Comment' => false));
		$expected = array(
			'Article2' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'Comment' => array()
		);
		$this->testDb->mergeAssociation($data, $merge, 'Comment', 'hasMany');
		$this->assertEquals($expected, $data);

		$data = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)
		);
		$merge = array(
			array(
				'Comment' => array(
					'id' => '1', 'comment' => 'Comment 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			),
			array(
				'Comment' => array(
					'id' => '2', 'comment' => 'Comment 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			)
		);
		$expected = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'Comment' => array(
				array(
					'id' => '1', 'comment' => 'Comment 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				array(
					'id' => '2', 'comment' => 'Comment 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			)
		);
		$this->testDb->mergeAssociation($data, $merge, 'Comment', 'hasMany');
		$this->assertEquals($expected, $data);

		$data = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)
		);
		$merge = array(
			array(
				'Comment' => array(
					'id' => '1', 'comment' => 'Comment 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'User2' => array(
					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			),
			array(
				'Comment' => array(
					'id' => '2', 'comment' => 'Comment 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'User2' => array(
					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			)
		);
		$expected = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'Comment' => array(
				array(
					'id' => '1', 'comment' => 'Comment 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'User2' => array(
						'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
					)
				),
				array(
					'id' => '2', 'comment' => 'Comment 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'User2' => array(
						'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
					)
				)
			)
		);
		$this->testDb->mergeAssociation($data, $merge, 'Comment', 'hasMany');
		$this->assertEquals($expected, $data);

		$data = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)
		);
		$merge = array(
			array(
				'Comment' => array(
					'id' => '1', 'comment' => 'Comment 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'User2' => array(
					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Tag' => array(
					array('id' => 1, 'tag' => 'Tag 1'),
					array('id' => 2, 'tag' => 'Tag 2')
				)
			),
			array(
				'Comment' => array(
					'id' => '2', 'comment' => 'Comment 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'User2' => array(
					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Tag' => array()
			)
		);
		$expected = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'Comment' => array(
				array(
					'id' => '1', 'comment' => 'Comment 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'User2' => array(
						'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
					),
					'Tag' => array(
						array('id' => 1, 'tag' => 'Tag 1'),
						array('id' => 2, 'tag' => 'Tag 2')
					)
				),
				array(
					'id' => '2', 'comment' => 'Comment 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'User2' => array(
						'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
					),
					'Tag' => array()
				)
			)
		);
		$this->testDb->mergeAssociation($data, $merge, 'Comment', 'hasMany');
		$this->assertEquals($expected, $data);

		$data = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)
		);
		$merge = array(
			array(
				'Tag' => array(
					'id' => '1', 'tag' => 'Tag 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			),
			array(
				'Tag' => array(
					'id' => '2', 'tag' => 'Tag 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			),
			array(
				'Tag' => array(
					'id' => '3', 'tag' => 'Tag 3', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			)
		);
		$expected = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'Tag' => array(
				array(
					'id' => '1', 'tag' => 'Tag 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				array(
					'id' => '2', 'tag' => 'Tag 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				array(
					'id' => '3', 'tag' => 'Tag 3', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			)
		);
		$this->testDb->mergeAssociation($data, $merge, 'Tag', 'hasAndBelongsToMany');
		$this->assertEquals($expected, $data);

		$data = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)
		);
		$merge = array(
			array(
				'Tag' => array(
					'id' => '1', 'tag' => 'Tag 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			),
			array(
				'Tag' => array(
					'id' => '2', 'tag' => 'Tag 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			),
			array(
				'Tag' => array(
					'id' => '3', 'tag' => 'Tag 3', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			)
		);
		$expected = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'Tag' => array('id' => '1', 'tag' => 'Tag 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31')
		);
		$this->testDb->mergeAssociation($data, $merge, 'Tag', 'hasOne');
		$this->assertEquals($expected, $data);
	}

/**
 * testMagicMethodQuerying method
 *
 * @return void
 */
	public function testMagicMethodQuerying() {
		$result = $this->db->query('findByFieldName', array('value'), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_name' => 'value'),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEquals($expected, $result);

		$result = $this->db->query('findByFindBy', array('value'), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.find_by' => 'value'),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEquals($expected, $result);

		$result = $this->db->query('findAllByFieldName', array('value'), $this->Model);
		$expected = array('all', array(
			'conditions' => array('TestModel.field_name' => 'value'),
			'fields' => null, 'order' => null, 'limit' => null,
			'page' => null, 'recursive' => null
		));
		$this->assertEquals($expected, $result);

		$result = $this->db->query('findAllById', array('a'), $this->Model);
		$expected = array('all', array(
			'conditions' => array('TestModel.id' => 'a'),
			'fields' => null, 'order' => null, 'limit' => null,
			'page' => null, 'recursive' => null
		));
		$this->assertEquals($expected, $result);

		$result = $this->db->query('findByFieldName', array(array('value1', 'value2', 'value3')), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_name' => array('value1', 'value2', 'value3')),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEquals($expected, $result);

		$result = $this->db->query('findByFieldName', array(null), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_name' => null),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEquals($expected, $result);

		$result = $this->db->query('findByFieldName', array('= a'), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_name' => '= a'),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEquals($expected, $result);

		$result = $this->db->query('findByFieldName', array(), $this->Model);
		$expected = false;
		$this->assertEquals($expected, $result);

		// findBy<X>And<Y>
		$result = $this->db->query('findByFieldXAndFieldY', array('x', 'y'), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_x' => 'x', 'TestModel.field_y' => 'y'),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEquals($expected, $result);

		// findBy<X>Or<Y>
		$result = $this->db->query('findByFieldXOrFieldY', array('x', 'y'), $this->Model);
		$expected = array('first', array(
			'conditions' => array('OR' => array('TestModel.field_x' => 'x', 'TestModel.field_y' => 'y')),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEquals($expected, $result);

		// findMyFancySearchBy<X>
		$result = $this->db->query('findMyFancySearchByFieldX', array('x'), $this->Model);
		$expected = array('myFancySearch', array(
			'conditions' => array('TestModel.field_x' => 'x'),
			'fields' => null, 'order' => null, 'limit' => null,
			'page' => null, 'recursive' => null
		));
		$this->assertEquals($expected, $result);

		// findFirstBy<X>
		$result = $this->db->query('findFirstByFieldX', array('x'), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_x' => 'x'),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEquals($expected, $result);

		// findBy<X> with optional parameters
		$result = $this->db->query('findByFieldX', array('x', 'y', 'priority', -1), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_x' => 'x'),
			'fields' => 'y', 'order' => 'priority', 'recursive' => -1
		));
		$this->assertEquals($expected, $result);

		// findBy<X>And<Y> with optional parameters
		$result = $this->db->query('findByFieldXAndFieldY', array('x', 'y', 'z', 'priority', -1), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_x' => 'x', 'TestModel.field_y' => 'y'),
			'fields' => 'z', 'order' => 'priority', 'recursive' => -1
		));
		$this->assertEquals($expected, $result);

		// findAllBy<X> with optional parameters
		$result = $this->db->query('findAllByFieldX', array('x', 'y', 'priority', 10, 2, -1), $this->Model);
		$expected = array('all', array(
			'conditions' => array('TestModel.field_x' => 'x'),
			'fields' => 'y', 'order' => 'priority', 'limit' => 10,
			'page' => 2, 'recursive' => -1
		));
		$this->assertEquals($expected, $result);

		// findAllBy<X>And<Y> with optional parameters
		$result = $this->db->query('findAllByFieldXAndFieldY', array('x', 'y', 'z', 'priority', 10, 2, -1), $this->Model);
		$expected = array('all', array(
			'conditions' => array('TestModel.field_x' => 'x', 'TestModel.field_y' => 'y'),
			'fields' => 'z', 'order' => 'priority', 'limit' => 10,
			'page' => 2, 'recursive' => -1
		));
		$this->assertEquals($expected, $result);
	}

/**
	 * @return void
	 */
	public function testDirectCallThrowsException() {
		$this->expectException(\PDOException::class);
		$this->db->query('directCall', array(), $this->Model);
	}

/**
 * testValue method
 *
 * @return void
 */
	public function testValue() {
		if ($this->db instanceof Sqlserver) {
			$this->markTestSkipped('Cannot run this test with SqlServer');
		}
		$result = $this->db->value('{$__cakeForeignKey__$}');
		$this->assertEquals('{$__cakeForeignKey__$}', $result);

		$result = $this->db->value(array('first', 2, 'third'));
		$expected = array('\'first\'', 2, '\'third\'');
		$this->assertEquals($expected, $result);
	}

/**
 * Tests if the connection can be re-established and that the new (optional) config is set.
 *
 * @return void
 */
	public function testReconnect() {
		$this->testDb->reconnect(array('prefix' => 'foo'));
		$this->assertTrue($this->testDb->connected);
		$this->assertEquals('foo', $this->testDb->config['prefix']);
	}

/**
 * testName method
 *
 * @return void
 */
	public function testName() {
		$result = $this->testDb->name('name');
		$expected = '`name`';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name(array('name', 'Model.*'));
		$expected = array('`name`', '`Model`.*');
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('MTD()');
		$expected = 'MTD()';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('(sm)');
		$expected = '(sm)';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('name AS x');
		$expected = '`name` AS `x`';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('Model.name AS x');
		$expected = '`Model`.`name` AS `x`';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('Function(Something.foo)');
		$expected = 'Function(`Something`.`foo`)';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('Function(SubFunction(Something.foo))');
		$expected = 'Function(SubFunction(`Something`.`foo`))';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('Function(Something.foo) AS x');
		$expected = 'Function(`Something`.`foo`) AS `x`';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('I18n__title__pt-br.locale');
		$expected = '`I18n__title__pt-br`.`locale`';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('name-with-minus');
		$expected = '`name-with-minus`';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name(array('my-name', 'Foo-Model.*'));
		$expected = array('`my-name`', '`Foo-Model`.*');
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name(array('Team.P%', 'Team.G/G'));
		$expected = array('`Team`.`P%`', '`Team`.`G/G`');
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('Model.name as y');
		$expected = '`Model`.`name` AS `y`';
		$this->assertEquals($expected, $result);
	}

/**
 * test that cacheMethod works as expected
 *
 * @return void
 */
	public function testCacheMethod() {
		$this->testDb->cacheMethods = true;
		$result = $this->testDb->cacheMethod('name', 'some-key', 'stuff');
		$this->assertEquals('stuff', $result);

		$result = $this->testDb->cacheMethod('name', 'some-key');
		$this->assertEquals('stuff', $result);

		$result = $this->testDb->cacheMethod('conditions', 'some-key');
		$this->assertNull($result);

		$result = $this->testDb->cacheMethod('name', 'other-key');
		$this->assertNull($result);

		$this->testDb->cacheMethods = false;
		$result = $this->testDb->cacheMethod('name', 'some-key', 'stuff');
		$this->assertEquals('stuff', $result);

		$result = $this->testDb->cacheMethod('name', 'some-key');
		$this->assertNull($result);
	}

/**
 * Test that cacheMethodFilter does not filter by default.
 *
 * @return void
 */
	public function testCacheMethodFilter() {
		$method = 'name';
		$key = '49d9207adfce6df1dd3ee8c30c434414';
		$value = '`menus`';
		$actual = $this->testDb->cacheMethodFilter($method, $key, $value);

		$this->assertTrue($actual);

		$method = 'fields';
		$key = '2b57253ab1fffb3e95fa4f95299220b1';
		$value = array("`Menu`.`id`", "`Menu`.`name`");
		$actual = $this->testDb->cacheMethodFilter($method, $key, $value);

		$this->assertTrue($actual);

		$method = 'non-existing';
		$key = '';
		$value = '``';
		$actual = $this->testDb->cacheMethodFilter($method, $key, $value);

		$this->assertTrue($actual);
	}

/**
 * Test that cacheMethodFilter can be overridden to do actual filtering.
 *
 * @return void
 */
	public function testCacheMethodFilterOverridden() {
		$testDb = new DboFourthTestSource();

		$method = 'name';
		$key = '49d9207adfce6df1dd3ee8c30c434414';
		$value = '`menus`';
		$actual = $testDb->cacheMethodFilter($method, $key, $value);

		$this->assertFalse($actual);

		$method = 'name';
		$key = '1fca740733997f1ebbedacfc7678592a';
		$value = '`Menu`.`id`';
		$actual = $testDb->cacheMethodFilter($method, $key, $value);

		$this->assertFalse($actual);

		$method = 'fields';
		$key = '2b57253ab1fffb3e95fa4f95299220b1';
		$value = array("`Menu`.`id`", "`Menu`.`name`");
		$actual = $testDb->cacheMethodFilter($method, $key, $value);

		$this->assertFalse($actual);

		$method = 'name';
		$key = 'd2bc458620afb092c61ab4383b7475e0';
		$value = '`Menu`';
		$actual = $testDb->cacheMethodFilter($method, $key, $value);

		$this->assertTrue($actual);

		$method = 'non-existing';
		$key = '';
		$value = '``';
		$actual = $testDb->cacheMethodFilter($method, $key, $value);

		$this->assertTrue($actual);
	}

/**
 * Test that cacheMethodHasher uses md5 by default.
 *
 * @return void
 */
	public function testCacheMethodHasher() {
		$name = 'Model.fieldlbqndkezcoapfgirmjsh';
		$actual = $this->testDb->cacheMethodHasher($name);
		$expected = '4a45dc9ed52f98c393d04ac424ee5078';

		$this->assertEquals($expected, $actual);
	}

/**
 * Test that cacheMethodHasher can be overridden to use a different hashing algorithm.
 *
 * @return void
 */
	public function testCacheMethodHasherOverridden() {
		$testDb = new DboThirdTestSource();

		$name = 'Model.fieldlbqndkezcoapfgirmjsh';
		$actual = $testDb->cacheMethodHasher($name);
		$expected = 'beb8b6469359285b7c2865dce0ef743feb16cb71';

		$this->assertEquals($expected, $actual);
	}

/**
 * Test that rare collisions do not happen with method caching
 *
 * @return void
 */
	public function testNameMethodCacheCollisions() {
		$this->testDb->cacheMethods = true;
		$this->testDb->flushMethodCache();
		$this->testDb->name('Model.fieldlbqndkezcoapfgirmjsh');
		$result = $this->testDb->name('Model.fieldkhdfjmelarbqnzsogcpi');
		$expected = '`Model`.`fieldkhdfjmelarbqnzsogcpi`';
		$this->assertEquals($expected, $result);
	}

/**
 * Test that flushMethodCache works as expected
 *
 * @return void
 */
	public function testFlushMethodCache() {
		$this->testDb->cacheMethods = true;
		$this->testDb->cacheMethod('name', 'some-key', 'stuff');

		Cache::write('method_cache', DboTestSource::$methodCache, '_cake_core_');

		$this->testDb->flushMethodCache();
		$result = $this->testDb->cacheMethod('name', 'some-key');
		$this->assertNull($result);
	}

/**
 * testLog method
 *
 * @outputBuffering enabled
 * @return void
 */
	public function testLog() {
		$this->testDb->logQuery('Query 1');
		$this->testDb->logQuery('Query 2');

		$log = $this->testDb->getLog(false, false);
		$result = Hash::extract($log['log'], '{n}.query');
		$expected = array('Query 1', 'Query 2');
		$this->assertEquals($expected, $result);

		$oldDebug = Configure::read('debug');
		Configure::write('debug', 2);
		ob_start();
		$this->testDb->showLog();
		$contents = ob_get_clean();

		$this->assertMatchesRegularExpression('/Query 1/s', $contents);
		$this->assertMatchesRegularExpression('/Query 2/s', $contents);

		ob_start();
		$this->testDb->showLog(true);
		$contents = ob_get_clean();

		$this->assertMatchesRegularExpression('/Query 1/s', $contents);
		$this->assertMatchesRegularExpression('/Query 2/s', $contents);

		Configure::write('debug', $oldDebug);
	}

/**
 * test getting the query log as an array.
 *
 * @return void
 */
	public function testGetLog() {
		$this->testDb->logQuery('Query 1');
		$this->testDb->logQuery('Query 2');

		$log = $this->testDb->getLog();
		$expected = array('query' => 'Query 1', 'params' => array(), 'affected' => '', 'numRows' => '', 'took' => '');

		$this->assertEquals($expected, $log['log'][0]);
		$expected = array('query' => 'Query 2', 'params' => array(), 'affected' => '', 'numRows' => '', 'took' => '');
		$this->assertEquals($expected, $log['log'][1]);
		$expected = array('query' => 'Error 1', 'affected' => '', 'numRows' => '', 'took' => '');
	}

/**
 * test getting the query log as an array, setting bind params.
 *
 * @return void
 */
	public function testGetLogParams() {
		$this->testDb->logQuery('Query 1', array(1, 2, 'abc'));
		$this->testDb->logQuery('Query 2', array('field1' => 1, 'field2' => 'abc'));

		$log = $this->testDb->getLog();
		$expected = array('query' => 'Query 1', 'params' => array(1, 2, 'abc'), 'affected' => '', 'numRows' => '', 'took' => '');
		$this->assertEquals($expected, $log['log'][0]);
		$expected = array('query' => 'Query 2', 'params' => array('field1' => 1, 'field2' => 'abc'), 'affected' => '', 'numRows' => '', 'took' => '');
		$this->assertEquals($expected, $log['log'][1]);
	}

/**
 * test that query() returns boolean values from operations like CREATE TABLE
 *
 * @return void
 */
	public function testFetchAllBooleanReturns() {
		$name = $this->db->fullTableName('test_query');
		$query = "CREATE TABLE {$name} (name varchar(10));";
		$result = $this->db->query($query);
		$this->assertTrue($result, 'Query did not return a boolean');

		$query = "DROP TABLE {$name};";
		$result = $this->db->query($query);
		$this->assertTrue($result, 'Query did not return a boolean');
	}

/**
 * Test NOT NULL on ENUM data type with empty string as a value
 *
 * @return void
 */
	public function testNotNullOnEnum() {
		if (!$this->db instanceof Mysql) {
			$this->markTestSkipped('This test can only run on MySQL');
		}
		$name = $this->db->fullTableName('enum_tests');

		$query = "DROP TABLE IF EXISTS {$name};";
		$result = $this->db->query($query);
		$this->assertTrue($result);

		$query = "CREATE TABLE {$name} (mood ENUM('','happy','sad','ok') NOT NULL);";
		$result = $this->db->query($query);
		$this->assertTrue($result);

		$EnumTest = ClassRegistry::init('EnumTest');
		$enumResult = $EnumTest->save(array('mood' => ''));

		$query = "DROP TABLE {$name};";
		$result = $this->db->query($query);
		$this->assertTrue($result);

		$this->assertEquals(array(
			'EnumTest' => array(
				'mood' => '',
				'id' => '0'
			)
		), $enumResult);
	}

/**
 * Test for MySQL enum datatype for a list of Integer stored as String
 *
 * @return void
 */
	public function testIntValueAsStringOnEnum() {
		if (!$this->db instanceof Mysql) {
			$this->markTestSkipped('This test can only run on MySQL');
		}
		$name = $this->db->fullTableName('enum_faya_tests');

		$query = "DROP TABLE IF EXISTS {$name};";
		$result = $this->db->query($query);
		$this->assertTrue($result);

		$query = "CREATE TABLE {$name} (faya enum('10','20','30','40') NOT NULL);";
		$result = $this->db->query($query);
		$this->assertTrue($result);

		$EnumFayaTest = ClassRegistry::init('EnumFayaTest');
		$enumResult = $EnumFayaTest->save(array('faya' => '10'));

		$query = "DROP TABLE {$name};";
		$result = $this->db->query($query);
		$this->assertTrue($result);

		$this->assertEquals(array(
			'EnumFayaTest' => array(
				'faya' => '10',
				'id' => '0'
			)
		), $enumResult);
	}

/**
 * test order to generate query order clause for virtual fields
 *
 * @return void
 */
	public function testVirtualFieldsInOrder() {
		$Article = ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'this_moment' => 'NOW()',
			'two' => '1 + 1',
		);
		$order = array('two', 'this_moment');
		$result = $this->db->order($order, 'ASC', $Article);
		$expected = ' ORDER BY (1 + 1) ASC, (NOW()) ASC';
		$this->assertEquals($expected, $result);

		$order = array('Article.two', 'Article.this_moment');
		$result = $this->db->order($order, 'ASC', $Article);
		$expected = ' ORDER BY (1 + 1) ASC, (NOW()) ASC';
		$this->assertEquals($expected, $result);
	}

/**
 * test the permutations of fullTableName()
 *
 * @return void
 */
	public function testFullTablePermutations() {
		$Article = ClassRegistry::init('Article');
		$result = $this->testDb->fullTableName($Article, false, false);
		$this->assertEquals('articles', $result);

		$Article->tablePrefix = 'tbl_';
		$result = $this->testDb->fullTableName($Article, false, false);
		$this->assertEquals('tbl_articles', $result);

		$Article->useTable = $Article->table = 'with spaces';
		$Article->tablePrefix = '';
		$result = $this->testDb->fullTableName($Article, true, false);
		$this->assertEquals('`with spaces`', $result);

		$this->loadFixtures('Article');
		$Article->useTable = $Article->table = 'articles';
		$Article->setDataSource('test');
		$testdb = $Article->getDataSource();
		$result = $testdb->fullTableName($Article, false, true);
		$this->assertEquals($testdb->getSchemaName() . '.articles', $result);

		// tests for empty schemaName
		$noschema = ConnectionManager::create('noschema', array(
			'datasource' => 'DboTestSource'
			));
		$Article->setDataSource('noschema');
		$Article->schemaName = null;
		$result = $noschema->fullTableName($Article, false, true);
		$this->assertEquals('articles', $result);

		$this->testDb->config['prefix'] = 't_';
		$result = $this->testDb->fullTableName('post_tag', false, false);
		$this->assertEquals('t_post_tag', $result);
	}

/**
 * test that read() only calls queryAssociation on db objects when the method is defined.
 *
 * @return void
 */
	public function testReadOnlyCallingQueryAssociationWhenDefined() {
		$this->loadFixtures('Article', 'User', 'ArticlesTag', 'Tag');
		ConnectionManager::create('test_no_queryAssociation', array(
			'datasource' => 'MockDataSource'
		));
		$Article = ClassRegistry::init('Article');
		$Article->Comment->useDbConfig = 'test_no_queryAssociation';
		$result = $Article->find('all');
		$this->assertTrue(is_array($result));
	}

/**
 * test that queryAssociation() reuse already joined data for 'belongsTo' and 'hasOne' associations
 * instead of running unneeded queries for each record
 *
 * @return void
 */
	public function testQueryAssociationUnneededQueries() {
		$this->loadFixtures('Article', 'User', 'Comment', 'Attachment', 'Tag', 'ArticlesTag');
		$Comment = ClassRegistry::init('Comment');

		$fullDebug = $this->db->fullDebug;
		$this->db->fullDebug = true;

		$Comment->find('all', array('recursive' => 2)); // ensure Model descriptions are saved
		$this->db->getLog();

		// case: Comment belongsTo User and Article
		$Comment->unbindModel(array(
			'hasOne' => array('Attachment')
		));
		$Comment->Article->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment'),
			'hasAndBelongsToMany' => array('Tag')
		));
		$Comment->find('all', array('recursive' => 2));
		$log = $this->db->getLog();
		$this->assertEquals(1, count($log['log']));

		// case: Comment belongsTo Article, Article belongsTo User
		$Comment->unbindModel(array(
			'belongsTo' => array('User'),
			'hasOne' => array('Attachment')
		));
		$Comment->Article->unbindModel(array(
			'hasMany' => array('Comment'),
			'hasAndBelongsToMany' => array('Tag'),
		));
		$Comment->find('all', array('recursive' => 2));
		$log = $this->db->getLog();
		$this->assertEquals(7, count($log['log']));

		// case: Comment hasOne Attachment
		$Comment->unbindModel(array(
			'belongsTo' => array('Article', 'User'),
		));
		$Comment->Attachment->unbindModel(array(
			'belongsTo' => array('Comment'),
		));
		$Comment->find('all', array('recursive' => 2));
		$log = $this->db->getLog();
		$this->assertEquals(1, count($log['log']));

		$this->db->fullDebug = $fullDebug;
	}

/**
 * Tests that generation association queries without LinkModel still works.
 * Mainly BC.
 *
 * @return void
 */
	public function testGenerateAssociationQuery() {
		$this->loadFixtures('Article');
		$Article = ClassRegistry::init('Article');

		$queryData = array(
			'conditions' => array(
				'Article.id' => 1
			),
			'fields' => array(
				'Article.id',
				'Article.title',
			),
			'joins' => array(),
			'limit' => 2,
			'offset' => 2,
			'order' => array('title'),
			'page' => 2,
			'group' => null,
			'callbacks' => 1
		);

		$result = $this->db->generateAssociationQuery($Article, null, null, null, null, $queryData, false);
		$this->assertStringContainsString('SELECT', $result);
		$this->assertStringContainsString('FROM', $result);
		$this->assertStringContainsString('WHERE', $result);
		$this->assertStringContainsString('ORDER', $result);
	}

/**
 * test that fields() is using methodCache()
 *
 * @return void
 */
	public function testFieldsUsingMethodCache() {
		$this->testDb->cacheMethods = false;
		DboTestSource::$methodCache = array();

		$Article = ClassRegistry::init('Article');
		$this->testDb->fields($Article, null, array('title', 'body', 'published'));
		$this->assertTrue(empty(DboTestSource::$methodCache['fields']), 'Cache not empty');
	}

/**
 * test that fields() method cache detects datasource changes
 *
 * @return void
 */
	public function testFieldsCacheKeyWithDatasourceChange() {
		ConnectionManager::create('firstschema', array(
			'datasource' => 'DboTestSource'
		));
		ConnectionManager::create('secondschema', array(
			'datasource' => 'DboSecondTestSource'
		));
		Cache::delete('method_cache', '_cake_core_');
		DboTestSource::$methodCache = array();
		$Article = ClassRegistry::init('Article');

		$Article->setDataSource('firstschema');
		$ds = $Article->getDataSource();
		$ds->cacheMethods = true;
		$first = $ds->fields($Article, null, array('title', 'body', 'published'));

		$Article->setDataSource('secondschema');
		$ds = $Article->getDataSource();
		$ds->cacheMethods = true;
		$second = $ds->fields($Article, null, array('title', 'body', 'published'));

		$this->assertNotEquals($first, $second);
		$this->assertEquals(2, count(DboTestSource::$methodCache['fields']));
	}

/**
 * test that fields() method cache detects schema name changes
 *
 * @return void
 */
	public function testFieldsCacheKeyWithSchemanameChange() {
		if ($this->db instanceof Postgres || $this->db instanceof Sqlserver) {
			$this->markTestSkipped('Cannot run this test with SqlServer or Postgres');
		}
		Cache::delete('method_cache', '_cake_core_');
		DboSource::$methodCache = array();
		$Article = ClassRegistry::init('Article');

		$ds = $Article->getDataSource();
		$ds->cacheMethods = true;
		$first = $ds->fields($Article);

		$Article->schemaName = 'secondSchema';
		$ds = $Article->getDataSource();
		$ds->cacheMethods = true;
		$second = $ds->fields($Article);

		$this->assertEquals(2, count(DboSource::$methodCache['fields']));
	}

/**
 * Test that group works without a model
 *
 * @return void
 */
	public function testGroupNoModel() {
		$result = $this->db->group('created');
		$this->assertEquals(' GROUP BY created', $result);
	}

/**
 * Test having method
 *
 * @return void
 */
	public function testHaving() {
		$this->loadFixtures('User');

		$result = $this->testDb->having(array('COUNT(*) >' => 0));
		$this->assertEquals(' HAVING COUNT(*) > 0', $result);

		$User = ClassRegistry::init('User');
		$result = $this->testDb->having('COUNT(User.id) > 0', true, $User);
		$this->assertEquals(' HAVING COUNT(`User`.`id`) > 0', $result);
	}

/**
 * Test getLockingHint method
 *
 * @return void
 */
	public function testGetLockingHint() {
		$this->assertEquals(' FOR UPDATE', $this->testDb->getLockingHint(true));
		$this->assertNull($this->testDb->getLockingHint(false));
		$this->assertNull($this->testDb->getLockingHint(null));
	}

/**
 * Test getting the last error.
 *
 * @return void
 */
	public function testLastError() {
		$class = $this->isPHP81() ? 'PDOStatementFake' : 'PDOStatement';
		$stmt = $this->getMock($class);
		$stmt->expects($this->any())
			->method('errorInfo')
			->will($this->returnValue(array('', 'something', 'bad')));

		$result = $this->db->lastError($stmt);
		$expected = 'something: bad';
		$this->assertEquals($expected, $result);
	}

/**
 * Tests that transaction commands are logged
 *
 * @return void
 */
	public function testTransactionLogging() {
		$conn = $this->getMock('MockPDO');
		$db = new DboTestSource();
		$db->setConnection($conn);
		$conn->expects($this->exactly(2))->method('beginTransaction')
			->will($this->returnValue(true));
		$conn->expects($this->once())->method('inTransaction')->will($this->returnValue(true));
		$conn->expects($this->once())->method('commit')->will($this->returnValue(true));
		$conn->expects($this->once())->method('rollback')->will($this->returnValue(true));

		$db->begin();
		$log = $db->getLog();
		$expected = array('query' => 'BEGIN', 'params' => array(), 'affected' => '', 'numRows' => '', 'took' => '');
		$this->assertEquals($expected, $log['log'][0]);

		$db->commit();
		$expected = array('query' => 'COMMIT', 'params' => array(), 'affected' => '', 'numRows' => '', 'took' => '');
		$log = $db->getLog();
		$this->assertEquals($expected, $log['log'][0]);

		$db->begin();
		$expected = array('query' => 'BEGIN', 'params' => array(), 'affected' => '', 'numRows' => '', 'took' => '');
		$log = $db->getLog();
		$this->assertEquals($expected, $log['log'][0]);

		$db->rollback();
		$expected = array('query' => 'ROLLBACK', 'params' => array(), 'affected' => '', 'numRows' => '', 'took' => '');
		$log = $db->getLog();
		$this->assertEquals($expected, $log['log'][0]);
	}

/**
 * Test nested transaction calls
 *
 * @return void
 */
	public function testTransactionNested() {
		$conn = $this->getMock('MockPDO');
		$db = new DboTestSource();
		$db->setConnection($conn);
		$db->useNestedTransactions = true;
		$db->nestedSupport = true;

		$conn->expects($this->at(0))->method('beginTransaction')->will($this->returnValue(true));
		$conn->expects($this->at(1))->method('exec')->with($this->equalTo('SAVEPOINT LEVEL1'))->will($this->returnValue(true));
		$conn->expects($this->at(2))->method('exec')->with($this->equalTo('RELEASE SAVEPOINT LEVEL1'))->will($this->returnValue(true));
		$conn->expects($this->at(3))->method('exec')->with($this->equalTo('SAVEPOINT LEVEL1'))->will($this->returnValue(true));
		$conn->expects($this->at(4))->method('exec')->with($this->equalTo('ROLLBACK TO SAVEPOINT LEVEL1'))->will($this->returnValue(true));
		$conn->expects($this->at(5))->method('commit')->will($this->returnValue(true));

		$this->_runTransactions($db);
	}

/**
 * Test nested transaction calls without support
 *
 * @return void
 */
	public function testTransactionNestedWithoutSupport() {
		$conn = $this->getMock('MockPDO');
		$db = new DboTestSource();
		$db->setConnection($conn);
		$db->useNestedTransactions = true;
		$db->nestedSupport = false;

		$conn->expects($this->once())->method('beginTransaction')->will($this->returnValue(true));
		$conn->expects($this->never())->method('exec');
		$conn->expects($this->once())->method('inTransaction')->will($this->returnValue(true));
		$conn->expects($this->once())->method('commit')->will($this->returnValue(true));

		$this->_runTransactions($db);
	}

/**
 * Test nested transaction disabled
 *
 * @return void
 */
	public function testTransactionNestedDisabled() {
		$conn = $this->getMock('MockPDO');
		$db = new DboTestSource();
		$db->setConnection($conn);
		$db->useNestedTransactions = false;
		$db->nestedSupport = true;

		$conn->expects($this->once())->method('beginTransaction')->will($this->returnValue(true));
		$conn->expects($this->never())->method('exec');
		$conn->expects($this->once())->method('inTransaction')->will($this->returnValue(true));
		$conn->expects($this->once())->method('commit')->will($this->returnValue(true));

		$this->_runTransactions($db);
	}

/**
 * Nested transaction calls
 *
 * @param DboTestSource $db
 * @return void
 */
	protected function _runTransactions($db) {
		$db->begin();
		$db->begin();
		$db->commit();
		$db->begin();
		$db->rollback();
		$db->commit();
	}

/**
 * Test build statement with some fields missing
 *
 * @return void
 */
	public function testBuildStatementDefaults() {
		$conn = $this->getMock('MockPDO', array('quote'));
		$conn->expects($this->any())
			->method('quote')
			->will($this->returnArgument(0));
		$db = new DboTestSource();
		$db->setConnection($conn);

		$subQuery = $db->buildStatement(
			array(
				'fields' => array('DISTINCT(AssetsTag.asset_id)'),
				'table' => 'assets_tags',
				'alias' => 'AssetsTag',
				'conditions' => array('Tag.name' => 'foo bar'),
				'limit' => null,
				'group' => 'AssetsTag.asset_id'
			),
			$this->Model
		);
		$expected = 'SELECT DISTINCT(AssetsTag.asset_id) FROM assets_tags AS AssetsTag   WHERE Tag.name = foo bar  GROUP BY AssetsTag.asset_id';
		$this->assertEquals($expected, $subQuery);
	}

/**
 * Test build statement with having option
 *
 * @return void
 */
	public function testBuildStatementWithHaving() {
		$conn = $this->getMock('MockPDO', array('quote'));
		$conn->expects($this->any())
			->method('quote')
			->will($this->returnArgument(0));
		$db = new DboTestSource();
		$db->setConnection($conn);

		$sql = $db->buildStatement(
			array(
				'fields' => array('user_id', 'COUNT(*) AS count'),
				'table' => 'articles',
				'alias' => 'Article',
				'group' => 'user_id',
				'order' => array('COUNT(*)' => 'DESC'),
				'limit' => 5,
				'having' => array('COUNT(*) >' => 10),
			),
			$this->Model
		);
		$expected = 'SELECT user_id, COUNT(*) AS count FROM articles AS Article   WHERE 1 = 1  GROUP BY user_id  HAVING COUNT(*) > 10  ORDER BY COUNT(*) DESC  LIMIT 5';
		$this->assertEquals($expected, $sql);
	}

/**
 * Test build statement with lock option
 *
 * @return void
 */
	public function testBuildStatementWithLockingHint() {
		$conn = $this->getMock('MockPDO', array('quote'));
		$conn->expects($this->any())
			->method('quote')
			->will($this->returnArgument(0));
		$db = new DboTestSource();
		$db->setConnection($conn);

		$sql = $db->buildStatement(
			array(
				'fields' => array('id'),
				'table' => 'users',
				'alias' => 'User',
				'order' => array('id'),
				'limit' => 1,
				'lock' => true,
			),
			$this->Model
		);
		$expected = 'SELECT id FROM users AS User   WHERE 1 = 1   ORDER BY id ASC  LIMIT 1  FOR UPDATE';
		$this->assertEquals($expected, $sql);
	}

/**
 * data provider for testBuildJoinStatement
 *
 * @return array
 */
	public static function joinStatements() {
		return array(
			array(array(
				'type' => 'CROSS',
				'alias' => 'PostsTag',
				'table' => 'posts_tags',
				'conditions' => array('1 = 1')
			), 'CROSS JOIN cakephp.posts_tags AS PostsTag'),
			array(array(
				'type' => 'LEFT',
				'alias' => 'PostsTag',
				'table' => 'posts_tags',
			), 'LEFT JOIN cakephp.posts_tags AS PostsTag'),
			array(array(
				'type' => 'LEFT',
				'alias' => 'PostsTag',
				'table' => 'posts_tags',
				'conditions' => array('PostsTag.post_id = Post.id')
			), 'LEFT JOIN cakephp.posts_tags AS PostsTag ON (PostsTag.post_id = Post.id)'),
			array(array(
				'type' => 'LEFT',
				'alias' => 'Stock',
				'table' => '(SELECT Stock.article_id, sum(quantite) quantite FROM stocks AS Stock GROUP BY Stock.article_id)',
				'conditions' => 'Stock.article_id = Article.id'
			), 'LEFT JOIN (SELECT Stock.article_id, sum(quantite) quantite FROM stocks AS Stock GROUP BY Stock.article_id) AS Stock ON (Stock.article_id = Article.id)')
		);
	}

/**
 * Test buildJoinStatement()
 * ensure that schemaName is not added when table value is a subquery
 *
 * @dataProvider joinStatements
 * @return void
 */
	public function testBuildJoinStatement($join, $expected) {
		$db = $this->getMock('DboTestSource', array('getSchemaName'));
		$db->expects($this->any())
			->method('getSchemaName')
			->will($this->returnValue('cakephp'));
		$result = $db->buildJoinStatement($join);
		$this->assertEquals($expected, $result);
	}

/**
 * data provider for testBuildJoinStatementWithTablePrefix
 *
 * @return array
 */
	public static function joinStatementsWithPrefix($schema) {
		return array(
			array(array(
				'type' => 'LEFT',
				'alias' => 'PostsTag',
				'table' => 'posts_tags',
				'conditions' => array('PostsTag.post_id = Post.id')
			), 'LEFT JOIN pre_posts_tags AS PostsTag ON (PostsTag.post_id = Post.id)'),
				array(array(
					'type' => 'LEFT',
					'alias' => 'Stock',
					'table' => '(SELECT Stock.article_id, sum(quantite) quantite FROM stocks AS Stock GROUP BY Stock.article_id)',
					'conditions' => 'Stock.article_id = Article.id'
				), 'LEFT JOIN (SELECT Stock.article_id, sum(quantite) quantite FROM stocks AS Stock GROUP BY Stock.article_id) AS Stock ON (Stock.article_id = Article.id)')
			);
	}

/**
 * Test buildJoinStatement()
 * ensure that prefix is not added when table value is a subquery
 *
 * @dataProvider joinStatementsWithPrefix
 * @return void
 */
	public function testBuildJoinStatementWithTablePrefix($join, $expected) {
		$db = new DboTestSource();
		$db->config['prefix'] = 'pre_';
		$result = $db->buildJoinStatement($join);
		$this->assertEquals($expected, $result);
	}

/**
 * Test conditionKeysToString()
 *
 * @return void
 */
	public function testConditionKeysToString() {
		$Article = ClassRegistry::init('Article');
		$conn = $this->getMock('MockPDO', array('quote'));
		$db = new DboTestSource();
		$db->setConnection($conn);

		$conn->expects($this->at(0))
			->method('quote')
			->will($this->returnValue('just text'));

		$conditions = array('Article.name' => 'just text');
		$result = $db->conditionKeysToString($conditions, true, $Article);
		$expected = "Article.name = just text";
		$this->assertEquals($expected, $result[0]);

		$conn->expects($this->at(0))
			->method('quote')
			->will($this->returnValue('just text'));
		$conn->expects($this->at(1))
			->method('quote')
			->will($this->returnValue('other text'));

		$conditions = array('Article.name' => array('just text', 'other text'));
		$result = $db->conditionKeysToString($conditions, true, $Article);
		$expected = "Article.name IN (just text, other text)";
		$this->assertEquals($expected, $result[0]);
	}

/**
 * Test conditionKeysToString() with virtual field
 *
 * @return void
 */
	public function testConditionKeysToStringVirtualFieldExpression() {
		$Article = ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'extra' => $Article->getDataSource()->expression('something virtual')
		);
		$conn = $this->getMock('MockPDO', array('quote'));
		$db = new DboTestSource();
		$db->setConnection($conn);

		$conn->expects($this->at(0))
			->method('quote')
			->will($this->returnValue('just text'));

		$conditions = array('Article.extra' => 'just text');
		$result = $db->conditionKeysToString($conditions, true, $Article);
		$expected = "(" . $Article->virtualFields['extra']->value . ") = just text";
		$this->assertEquals($expected, $result[0]);

		$conn->expects($this->at(0))
			->method('quote')
			->will($this->returnValue('just text'));
		$conn->expects($this->at(1))
			->method('quote')
			->will($this->returnValue('other text'));

		$conditions = array('Article.extra' => array('just text', 'other text'));
		$result = $db->conditionKeysToString($conditions, true, $Article);
		$expected = "(" . $Article->virtualFields['extra']->value . ") IN (just text, other text)";
		$this->assertEquals($expected, $result[0]);
	}

/**
 * Test conditionKeysToString() with virtual field
 *
 * @return void
 */
	public function testConditionKeysToStringVirtualField() {
		$Article = ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'extra' => 'something virtual'
		);
		$conn = $this->getMock('MockPDO', array('quote'));
		$db = new DboTestSource();
		$db->setConnection($conn);

		$conn->expects($this->at(0))
			->method('quote')
			->will($this->returnValue('just text'));

		$conditions = array('Article.extra' => 'just text');
		$result = $db->conditionKeysToString($conditions, true, $Article);
		$expected = "(" . $Article->virtualFields['extra'] . ") = just text";
		$this->assertEquals($expected, $result[0]);

		$conn->expects($this->at(0))
			->method('quote')
			->will($this->returnValue('just text'));
		$conn->expects($this->at(1))
			->method('quote')
			->will($this->returnValue('other text'));

		$conditions = array('Article.extra' => array('just text', 'other text'));
		$result = $db->conditionKeysToString($conditions, true, $Article);
		$expected = "(" . $Article->virtualFields['extra'] . ") IN (just text, other text)";
		$this->assertEquals($expected, $result[0]);
	}

/**
 * Test the limit function.
 *
 * @return void
 */
	public function testLimit() {
		$db = new DboTestSource();

		$result = $db->limit('0');
		$this->assertNull($result);

		$result = $db->limit('10');
		$this->assertEquals(' LIMIT 10', $result);

		$result = $db->limit('FARTS', 'BOOGERS');
		$this->assertEquals(' LIMIT 0, 0', $result);

		$result = $db->limit(20, 10);
		$this->assertEquals(' LIMIT 10, 20', $result);

		$result = $db->limit(10, 300000000000000000000000000000);
		$scientificNotation = sprintf('%.1E', 300000000000000000000000000000);
		$this->assertStringNotContainsString($scientificNotation, $result);
	}

/**
 * Test insertMulti with id position.
 *
 * @return void
 */
	public function testInsertMultiId() {
		$this->loadFixtures('Article');
		$Article = ClassRegistry::init('Article');
		$db = $Article->getDatasource();
		$datetime = date('Y-m-d H:i:s');
		$data = array(
			array(
				'user_id' => 1,
				'title' => 'test',
				'body' => 'test',
				'published' => 'N',
				'created' => $datetime,
				'updated' => $datetime,
				'id' => 100,
			),
			array(
				'user_id' => 1,
				'title' => 'test 101',
				'body' => 'test 101',
				'published' => 'N',
				'created' => $datetime,
				'updated' => $datetime,
				'id' => 101,
			)
		);
		$result = $db->insertMulti('articles', array_keys($data[0]), $data);
		$this->assertTrue($result, 'Data was saved');

		$data = array(
			array(
				'id' => 102,
				'user_id' => 1,
				'title' => 'test',
				'body' => 'test',
				'published' => 'N',
				'created' => $datetime,
				'updated' => $datetime,
			),
			array(
				'id' => 103,
				'user_id' => 1,
				'title' => 'test 101',
				'body' => 'test 101',
				'published' => 'N',
				'created' => $datetime,
				'updated' => $datetime,
			)
		);

		$result = $db->insertMulti('articles', array_keys($data[0]), $data);
		$this->assertTrue($result, 'Data was saved');
	}

/**
 * Test defaultConditions()
 *
 * @return void
 */
	public function testDefaultConditions() {
		$this->loadFixtures('Article');
		$Article = ClassRegistry::init('Article');
		$db = $Article->getDataSource();

		// Creates a default set of conditions from the model if $conditions is null/empty.
		$Article->id = 1;
		$result = $db->defaultConditions($Article, null);
		$this->assertEquals(array('Article.id' => 1), $result);

		// $useAlias == false
		$Article->id = 1;
		$result = $db->defaultConditions($Article, null, false);
		$this->assertEquals(array($db->fullTableName($Article, false) . '.id' => 1), $result);

		// If conditions are supplied then they will be returned.
		$Article->id = 1;
		$result = $db->defaultConditions($Article, array('Article.title' => 'First article'));
		$this->assertEquals(array('Article.title' => 'First article'), $result);

		// If a model doesn't exist and no conditions were provided either null or false will be returned based on what was input.
		$Article->id = 1000000;
		$result = $db->defaultConditions($Article, null);
		$this->assertNull($result);

		$Article->id = 1000000;
		$result = $db->defaultConditions($Article, false);
		$this->assertFalse($result);

		// Safe update mode
		$Article->id = 1000000;
		$Article->__safeUpdateMode = true;
		$result = $db->defaultConditions($Article, null);
		$this->assertFalse($result);
	}

/**
 * Test that count how many times afterFind is called
 *
 * @return void
 */
	public function testCountAfterFindCalls() {
		$this->loadFixtures('Article', 'User', 'Comment', 'Attachment', 'Tag', 'ArticlesTag');

		// Use alias to make testing "primary = true" easy
		$Primary = $this->getMock('Comment', array('afterFind'), array(array('alias' => 'Primary')), '', true);

		$Article = $this->getMock('Article', array('afterFind'), array(), '', true);
		$User = $this->getMock('User', array('afterFind'), array(), '', true);
		$Comment = $this->getMock('Comment', array('afterFind'), array(), '', true);
		$Tag = $this->getMock('Tag', array('afterFind'), array(), '', true);
		$Attachment = $this->getMock('Attachment', array('afterFind'), array(), '', true);

		$Primary->Article = $Article;
		$Primary->Article->User = $User;
		$Primary->Article->Tag = $Tag;
		$Primary->Article->Comment = $Comment;
		$Primary->Attachment = $Attachment;
		$Primary->Attachment->Comment = $Comment;
		$Primary->User = $User;

		// primary = true
		$Primary->expects($this->once())
			->method('afterFind')->with($this->anything(), $this->isTrue())->will($this->returnArgument(0));

		// primary = false
		$Article->expects($this->once()) // Primary belongs to 1 Article
			->method('afterFind')->with($this->anything(), $this->isFalse())->will($this->returnArgument(0));
		$User->expects($this->exactly(2)) // Article belongs to 1 User and Primary belongs to 1 User
			->method('afterFind')->with($this->anything(), $this->isFalse())->will($this->returnArgument(0));
		$Tag->expects($this->exactly(2)) // Article has 2 Tags
			->method('afterFind')->with($this->anything(), $this->isFalse())->will($this->returnArgument(0));
		$Comment->expects($this->exactly(3)) // Article has 2 Comments and Attachment belongs to 1 Comment
			->method('afterFind')->with($this->anything(), $this->isFalse())->will($this->returnArgument(0));
		$Attachment->expects($this->once()) // Primary has 1 Attachment
			->method('afterFind')->with($this->anything(), $this->isFalse())->will($this->returnArgument(0));

		$result = $Primary->find('first', array('conditions' => array('Primary.id' => 5), 'recursive' => 2));
		$this->assertCount(2, $result['Article']['Tag']);
		$this->assertCount(2, $result['Article']['Comment']);

		// hasMany special case
		// Both User and Article has many Comments
		$User = $this->getMock('User', array('afterFind'), array(), '', true);
		$Article = $this->getMock('Article', array('afterFind'), array(), '', true);
		$Comment = $this->getMock('Comment', array('afterFind'), array(), '', true);

		$User->bindModel(array('hasMany' => array('Comment', 'Article')));
		$Article->unbindModel(array('belongsTo' => array('User'), 'hasAndBelongsToMany' => array('Tag')));
		$Comment->unbindModel(array('belongsTo' => array('User', 'Article'), 'hasOne' => 'Attachment'));

		$User->Comment = $Comment;
		$User->Article = $Article;
		$User->Article->Comment = $Comment;

		// primary = true
		$User->expects($this->once())
			->method('afterFind')->with($this->anything(), $this->isTrue())->will($this->returnArgument(0));

		$Article->expects($this->exactly(2)) // User has 2 Articles
			->method('afterFind')->with($this->anything(), $this->isFalse())->will($this->returnArgument(0));

		$Comment->expects($this->exactly(7)) // User1 has 3 Comments, Article[id=1] has 4 Comments and Article[id=3] has 0 Comments
			->method('afterFind')->with($this->anything(), $this->isFalse())->will($this->returnArgument(0));

		$result = $User->find('first', array('conditions' => array('User.id' => 1), 'recursive' => 2));
		$this->assertCount(3, $result['Comment']);
		$this->assertCount(2, $result['Article']);
		$this->assertCount(4, $result['Article'][0]['Comment']);
		$this->assertCount(0, $result['Article'][1]['Comment']);
	}

/**
 * Test format of $results in afterFind
 *
 * @return void
 */
	public function testUseConsistentAfterFind() {
		$this->loadFixtures('Author', 'Post');

		$expected = array(
			'Author' => array(
				'id' => '1',
				'user' => 'mariano',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				'created' => '2007-03-17 01:16:23',
				'updated' => '2007-03-17 01:18:31',
				'test' => 'working',
			),
			'Post' => array(
				array(
					'id' => '1',
					'author_id' => '1',
					'title' => 'First Post',
					'body' => 'First Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31',
				),
				array(
					'id' => '3',
					'author_id' => '1',
					'title' => 'Third Post',
					'body' => 'Third Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31',
				),
			),
		);

		$Author = new Author();
		$Post = $this->getMock('Post', array('afterFind'), array(), '', true);
		$Post->expects($this->at(0))->method('afterFind')->with(array(array('Post' => $expected['Post'][0])), $this->isFalse())->will($this->returnArgument(0));
		$Post->expects($this->at(1))->method('afterFind')->with(array(array('Post' => $expected['Post'][1])), $this->isFalse())->will($this->returnArgument(0));

		$Author->bindModel(array('hasMany' => array('Post' => array('limit' => 2, 'order' => 'Post.id'))));
		$Author->Post = $Post;

		$result = $Author->find('first', array('conditions' => array('Author.id' => 1), 'recursive' => 1));
		$this->assertEquals($expected, $result);

		// Backward compatiblity
		$Author = new Author();
		$Post = $this->getMock('Post', array('afterFind'), array(), '', true);
		$Post->expects($this->once())->method('afterFind')->with($expected['Post'], $this->isFalse())->will($this->returnArgument(0));
		$Post->useConsistentAfterFind = false;

		$Author->bindModel(array('hasMany' => array('Post' => array('limit' => 2, 'order' => 'Post.id'))));
		$Author->Post = $Post;

		$result = $Author->find('first', array('conditions' => array('Author.id' => 1), 'recursive' => 1));
		$this->assertEquals($expected, $result);
	}

/**
 * Test that afterFind is called correctly for 'joins'
 *
 * @return void
 */
	public function testJoinsAfterFind() {
		$this->loadFixtures('Article', 'User');

		$User = new User();
		$User->bindModel(array('hasOne' => array('Article')));

		$Article = $this->getMock('Article', array('afterFind'), array(), '', true);
		$Article->expects($this->once())
			->method('afterFind')
			->with(
				array(
					0 => array(
						'Article' => array(
							'id' => '1',
							'user_id' => '1',
							'title' => 'First Article',
							'body' => 'First Article Body',
							'published' => 'Y',
							'created' => '2007-03-18 10:39:23',
							'updated' => '2007-03-18 10:41:31'
						)
					)
				),
				$this->isFalse()
			)
			->will($this->returnArgument(0));

		$User->Article = $Article;
		$User->find('first', array(
			'fields' => array(
				'Article.id',
				'Article.user_id',
				'Article.title',
				'Article.body',
				'Article.published',
				'Article.created',
				'Article.updated'
			),
			'conditions' => array('User.id' => 1),
			'recursive' => -1,
			'joins' => array(
				array(
					'table' => 'articles',
					'alias' => 'Article',
					'type' => 'LEFT',
					'conditions' => array(
						'Article.user_id = User.id'
					),
				)
			),
			'order' => array('Article.id')
		));
	}

/**
 * Test that afterFind is called correctly for 'hasOne' association.
 *
 * @return void
 */
	public function testHasOneAfterFind() {
		$this->loadFixtures('Article', 'User', 'Comment');

		$User = new User();
		$User->bindModel(array('hasOne' => array('Article')));

		$Article = $this->getMock('Article', array('afterFind'), array(), '', true);
		$Article->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment'),
			'hasAndBelongsToMany' => array('Tag')
		));
		$Article->bindModel(array(
			'hasOne' => array('Comment'),
		));
		$Article->expects($this->once())
			->method('afterFind')
			->with(
				$this->equalTo(
					array(
						0 => array(
							'Article' => array(
								'id' => '1',
								'user_id' => '1',
								'title' => 'First Article',
								'body' => 'First Article Body',
								'published' => 'Y',
								'created' => '2007-03-18 10:39:23',
								'updated' => '2007-03-18 10:41:31',
								'Comment' => array(
									'id' => '1',
									'article_id' => '1',
									'user_id' => '2',
									'comment' => 'First Comment for First Article',
									'published' => 'Y',
									'created' => '2007-03-18 10:45:23',
									'updated' => '2007-03-18 10:47:31',
								)
							)
						)
					)
				),
				$this->isFalse()
			)
			->will($this->returnArgument(0));

		$User->Article = $Article;
		$User->find('first', array('conditions' => array('User.id' => 1), 'recursive' => 2));
	}

/**
 * Test that flushQueryCache works as expected
 *
 * @return void
 */
	public function testFlushQueryCache() {
		$this->db->flushQueryCache();
		$this->db->query('SELECT 1');
		$this->db->query('SELECT 1');
		$this->db->query('SELECT 2');
		$this->assertCount(2, $this->db->getQueryCacheForTests());

		$this->db->flushQueryCache();
		$this->assertCount(0, $this->db->getQueryCacheForTests());
	}

/**
 * Test length parsing.
 *
 * @return void
 */
	public function testLength() {
		$result = $this->db->length('varchar(255)');
		$this->assertEquals(255, $result);

		$result = $this->db->length('integer(11)');
		$this->assertEquals(11, $result);

		$result = $this->db->length('integer unsigned');
		$this->assertNull($result);

		$result = $this->db->length('integer(11) unsigned');
		$this->assertEquals(11, $result);

		$result = $this->db->length('integer(11) zerofill');
		$this->assertEquals(11, $result);

		$result = $this->db->length('decimal(20,3)');
		$this->assertEquals('20,3', $result);
	}

/**
 * Test length parsing of enum column.
 *
 * @return void
 */
	public function testLengthEnum() {
		$result = $this->db->length('enum("one", "longer")');
		$this->assertNull($result);

		$result = $this->db->length("enum('One Value','ANOTHER ... VALUE ...')");
		$this->assertNull($result);
	}

/**
 * Test find with locking hint
 */
	public function testFindWithLockingHint() {
		$db = $this->getMock('DboTestSource', array('connect', '_execute', 'execute', 'describ'));

		$Test = $this->getMock('Test', array('getDataSource'));
		$Test->expects($this->any())
			->method('getDataSource')
			->will($this->returnValue($db));

		$expected = 'SELECT Test.id FROM tests AS Test   WHERE id = 1   ORDER BY Test.id ASC  LIMIT 1  FOR UPDATE';

		$db->expects($this->once())
			->method('execute')
			->with($expected);

		$Test->find('first', array(
			'recursive' => -1,
			'fields' => array('id'),
			'conditions' => array('id' => 1),
			'lock' => true,
		));
	}
}
