<?php
/**
 * ModelReadTest file
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
 * @package       Cake.Test.Case.Model
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

require_once dirname(__FILE__) . DS . 'ModelTestBase.php';

/**
 * ModelReadTest
 *
 * @package       Cake.Test.Case.Model
 */
class ModelReadTest extends BaseModelTest {

/**
 * testExists function
 * @return void
 */
	public function testExists() {
		$this->loadFixtures('User');
		$TestModel = new User();

		$this->assertTrue($TestModel->exists(1));

		$TestModel->id = 2;
		$this->assertTrue($TestModel->exists());

		$TestModel->delete();
		$this->assertFalse($TestModel->exists());

		$this->assertFalse($TestModel->exists(2));
	}

/**
 * testFetchingNonUniqueFKJoinTableRecords()
 *
 * Tests if the results are properly returned in the case there are non-unique FK's
 * in the join table but another fields value is different. For example:
 * something_id | something_else_id | doomed = 1
 * something_id | something_else_id | doomed = 0
 * Should return both records and not just one.
 *
 * @return void
 */
	public function testFetchingNonUniqueFKJoinTableRecords() {
		$this->loadFixtures('Something', 'SomethingElse', 'JoinThing');
		$Something = new Something();

		$joinThingData = array(
			'JoinThing' => array(
				'something_id' => 1,
				'something_else_id' => 2,
				'doomed' => '0',
				'created' => '2007-03-18 10:39:23',
				'updated' => '2007-03-18 10:41:31'
			)
		);

		$Something->JoinThing->create($joinThingData);
		$Something->JoinThing->save();

		$result = $Something->JoinThing->find('all', array('conditions' => array('something_else_id' => 2)));

		$this->assertEquals(true, $result[0]['JoinThing']['doomed']);
		$this->assertEquals(false, $result[1]['JoinThing']['doomed']);

		$result = $Something->find('first');

		$this->assertEquals(2, count($result['SomethingElse']));

		$doomed = Hash::extract($result['SomethingElse'], '{n}.JoinThing.doomed');
		$this->assertTrue(in_array(true, $doomed));
		$this->assertTrue(in_array(false, $doomed));
	}

/**
 * Test IN operator
 *
 * @return void
 */
	public function testInOperator() {
		$this->loadFixtures('Product');
		$Product = new Product();
		$expected = array(
			array(
				'Product' => array(
					'id' => 1,
					'name' => "Park's Great Hits",
					'type' => 'Music',
					'price' => 19
				)
			)
		);

		$result = $Product->find('all', array('conditions' => array('Product.id IN' => array(1))));
		$this->assertEquals($expected, $result);

		$expected = array(
			array(
				'Product' => array(
					'id' => 2,
					'name' => "Silly Puddy",
					'type' => 'Toy',
					'price' => 3
				)
			),
			array(
				'Product' => array(
					'id' => 3,
					'name' => "Playstation",
					'type' => 'Toy',
					'price' => 89
				)
			),
			array(
				'Product' => array(
					'id' => 4,
					'name' => "Men's T-Shirt",
					'type' => 'Clothing',
					'price' => 32
				)
			),
			array(
				'Product' => array(
					'id' => 5,
					'name' => "Blouse",
					'type' => 'Clothing',
					'price' => 34
				)
			),
			array(
				'Product' => array(
					'id' => 6,
					'name' => "Electronica 2002",
					'type' => 'Music',
					'price' => 4
				)
			),
			array(
				'Product' => array(
					'id' => 7,
					'name' => "Country Tunes",
					'type' => 'Music',
					'price' => 21
				)
			),
			array(
				'Product' => array(
					'id' => 8,
					'name' => "Watermelon",
					'type' => 'Food',
					'price' => 9
				)
			)
		);
		$result = $Product->find('all', array('conditions' => array('Product.id NOT IN' => array(1))));
		$this->assertEquals($expected, $result);

		$expected = array(
			array(
				'Product' => array(
					'id' => 1,
					'name' => "Park's Great Hits",
					'type' => 'Music',
					'price' => 19
				)
			),
			array(
				'Product' => array(
					'id' => 2,
					'name' => "Silly Puddy",
					'type' => 'Toy',
					'price' => 3
				)
			),
		);

		$result = $Product->find('all', array('conditions' => array('Product.id IN' => array(1, 2))));
		$this->assertEquals($expected, $result);
	}

	public function skipIfIsStrictGroupBy() {
		$isOnlyFullGroupBy = false;
		if ($this->db instanceof Mysql) {
			$sqlMode = $this->db->query('SELECT @@sql_mode AS sql_mode;');
			if (strpos($sqlMode[0][0]['sql_mode'], 'ONLY_FULL_GROUP_BY') > -1) {
				$isOnlyFullGroupBy = true;
			}
		}
		$isStrictGroupBy = $isOnlyFullGroupBy ||
			$this->db instanceof Postgres ||
			$this->db instanceof Sqlite ||
			$this->db instanceof Oracle ||
			$this->db instanceof Sqlserver;
		$message = 'Postgres, Oracle, SQLite, SQL Server and MySQL in ONLY_FULL_GROUP_BY ' .
			'mode have strict GROUP BY and are incompatible with this test.';
		$this->skipIf($isStrictGroupBy, $message);
	}

/**
 * testGroupByAndOrder method
 *
 * This test will never pass with Postgres or Oracle as all fields in a select must be
 * part of an aggregate function or in the GROUP BY statement.
 *
 * @return void
 */
	public function testGroupByAndOrder() {
		$this->skipIfIsStrictGroupBy();
		$this->loadFixtures('Project', 'Thread', 'Message');
		$Thread = new Thread();
		$result = $Thread->find('all', array(
			'group' => 'Thread.project_id',
			'order' => 'Thread.id ASC',
		));
		$expected = array(
			array(
				'Thread' => array(
					'id' => 1,
					'project_id' => 1,
					'name' => 'Project 1, Thread 1',
				),
				'Project' => array(
					'id' => 1,
					'name' => 'Project 1',
				),
				'Message' => array(
					array(
						'id' => 1,
						'thread_id' => 1,
						'name' => 'Thread 1, Message 1',
					),
				),
			),
			array (
				'Thread' => array(
					'id' => 3,
					'project_id' => 2,
					'name' => 'Project 2, Thread 1',
				),
				'Project' => array(
					'id' => 2,
					'name' => 'Project 2',
				),
				'Message' => array(
					array(
						'id' => 3,
						'thread_id' => 3,
						'name' => 'Thread 3, Message 1',
					),
				),
			),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testGroupBy method
 *
 * These tests will never pass with Postgres or Oracle as all fields in a select must be
 * part of an aggregate function or in the GROUP BY statement.
 *
 * @return void
 */
	public function testGroupBy() {
		$this->skipIfIsStrictGroupBy();

		$this->loadFixtures('Project', 'Product', 'Thread', 'Message', 'Bid');
		$Thread = new Thread();
		$Product = new Product();

		$rows = $Thread->find('all', array(
			'group' => 'Thread.project_id',
			'fields' => array('Thread.project_id', 'COUNT(*) AS total')
		));
		$result = array();
		foreach ($rows as $row) {
			$result[$row['Thread']['project_id']] = $row[0]['total'];
		}
		$expected = array(
			1 => 2,
			2 => 1
		);
		$this->assertEquals($expected, $result);

		$rows = $Thread->find('all', array(
			'group' => 'Thread.project_id',
			'fields' => array('Thread.project_id', 'COUNT(*) AS total'),
			'order' => 'Thread.project_id'
		));
		$result = array();
		foreach ($rows as $row) {
			$result[$row['Thread']['project_id']] = $row[0]['total'];
		}
		$expected = array(
			1 => 2,
			2 => 1
		);
		$this->assertEquals($expected, $result);

		$result = $Thread->find('all', array(
			'conditions' => array('Thread.project_id' => 1),
			'group' => 'Thread.project_id'
		));
		$expected = array(
			array(
				'Thread' => array(
					'id' => 1,
					'project_id' => 1,
					'name' => 'Project 1, Thread 1'
				),
				'Project' => array(
					'id' => 1,
					'name' => 'Project 1'
				),
				'Message' => array(
					array(
						'id' => 1,
						'thread_id' => 1,
						'name' => 'Thread 1, Message 1'
		))));
		$this->assertEquals($expected, $result);

		$result = $Thread->find('all', array(
			'conditions' => array('Thread.project_id' => 1),
			'group' => 'Thread.project_id, Project.id'
		));
		$this->assertEquals($expected, $result);

		$result = $Thread->find('all', array(
			'conditions' => array('Thread.project_id' => 1),
			'group' => 'project_id'
		));
		$this->assertEquals($expected, $result);

		$result = $Thread->find('all', array(
			'conditions' => array('Thread.project_id' => 1),
			'group' => array('project_id')
		));
		$this->assertEquals($expected, $result);

		$result = $Thread->find('all', array(
			'conditions' => array('Thread.project_id' => 1),
			'group' => array('project_id', 'Project.id')
		));
		$this->assertEquals($expected, $result);

		$result = $Thread->find('all', array(
			'conditions' => array('Thread.project_id' => 1),
			'group' => array('Thread.project_id', 'Project.id')
		));
		$this->assertEquals($expected, $result);

		$expected = array(
			array('Product' => array('type' => 'Clothing'), array('price' => 32)),
			array('Product' => array('type' => 'Food'), array('price' => 9)),
			array('Product' => array('type' => 'Music'), array('price' => 4)),
			array('Product' => array('type' => 'Toy'), array('price' => 3))
		);
		$result = $Product->find('all', array(
			'fields' => array('Product.type', 'MIN(Product.price) as price'),
			'group' => 'Product.type',
			'order' => 'Product.type ASC'
			));
		$this->assertEquals($expected, $result);

		$result = $Product->find('all', array(
			'fields' => array('Product.type', 'MIN(Product.price) as price'),
			'group' => array('Product.type'),
			'order' => 'Product.type ASC'));
		$this->assertEquals($expected, $result);
	}

/**
 * Test find method with having clause
 *
 * @return void
 */
	public function testHaving() {
		$this->loadFixtures('Comment');

		$Comment = ClassRegistry::init('Comment');
		$comments = $Comment->find('all', array(
			'fields' => array('user_id', 'COUNT(*) AS count'),
			'group' => array('user_id'),
			'having' => array('COUNT(*) >' => 1),
			'order' => array('COUNT(*)' => 'DESC'),
			'recursive' => -1,
		));

		$results = Hash::combine($comments, '{n}.Comment.user_id', '{n}.0.count');

		$expected = array(
			1 => 3,
			2 => 2,
		);

		$this->assertEquals($expected, $results);
	}

/**
 * testOldQuery method
 *
 * @return void
 */
	public function testOldQuery() {
		$this->loadFixtures('Article', 'User', 'Tag', 'ArticlesTag', 'Comment', 'Attachment');
		$Article = new Article();

		$query = 'SELECT title FROM ';
		$query .= $this->db->fullTableName('articles');
		$query .= ' WHERE ' . $this->db->fullTableName('articles') . '.id IN (1,2)';

		$results = $Article->query($query);
		$this->assertTrue(is_array($results));
		$this->assertEquals(2, count($results));

		$query = 'SELECT title, body FROM ';
		$query .= $this->db->fullTableName('articles');
		$query .= ' WHERE ' . $this->db->fullTableName('articles') . '.id = 1';

		$results = $Article->query($query, false);
		$this->assertFalse($this->db->getQueryCache($query));
		$this->assertTrue(is_array($results));

		$query = 'SELECT title, id FROM ';
		$query .= $this->db->fullTableName('articles');
		$query .= ' WHERE ' . $this->db->fullTableName('articles');
		$query .= '.published = ' . $this->db->value('Y');

		$results = $Article->query($query, true);
		$result = $this->db->getQueryCache($query);
		$this->assertFalse(empty($result));
		$this->assertTrue(is_array($results));
	}

/**
 * testPreparedQuery method
 *
 * @return void
 */
	public function testPreparedQuery() {
		$this->loadFixtures('Article', 'User', 'Tag', 'ArticlesTag');
		$Article = new Article();

		$query = 'SELECT title, published FROM ';
		$query .= $this->db->fullTableName('articles');
		$query .= ' WHERE ' . $this->db->fullTableName('articles');
		$query .= '.id = ? AND ' . $this->db->fullTableName('articles') . '.published = ?';

		$params = array(1, 'Y');
		$result = $Article->query($query, $params, true);
		$expected = array(
			'0' => array(
				$this->db->fullTableName('articles', false, false) => array(
					'title' => 'First Article', 'published' => 'Y')
		));

		if (isset($result[0][0])) {
			$expected[0][0] = $expected[0][$this->db->fullTableName('articles', false, false)];
			unset($expected[0][$this->db->fullTableName('articles', false, false)]);
		}

		$this->assertEquals($expected, $result);
		$result = $this->db->getQueryCache($query, $params);
		$this->assertFalse(empty($result));

		$query = 'SELECT id, created FROM ';
		$query .= $this->db->fullTableName('articles');
		$query .= '  WHERE ' . $this->db->fullTableName('articles') . '.title = ?';

		$params = array('First Article');
		$result = $Article->query($query, $params, false);
		$this->assertTrue(is_array($result));
		$this->assertTrue(
			isset($result[0][$this->db->fullTableName('articles', false, false)]) ||
			isset($result[0][0])
		);
		$result = $this->db->getQueryCache($query, $params);
		$this->assertTrue(empty($result));

		$query = 'SELECT title FROM ';
		$query .= $this->db->fullTableName('articles');
		$query .= ' WHERE ' . $this->db->fullTableName('articles') . '.title LIKE ?';

		$params = array('%First%');
		$result = $Article->query($query, $params, true);
		$this->assertTrue(is_array($result));
		$this->assertTrue(
			isset($result[0][$this->db->fullTableName('articles', false, false)]['title']) ||
			isset($result[0][0]['title'])
		);

		//related to ticket #5035
		$query = 'SELECT title FROM ';
		$query .= $this->db->fullTableName('articles') . ' WHERE title = ? AND published = ?';
		$params = array('First? Article', 'Y');
		$Article->query($query, $params, true);

		$result = $this->db->getQueryCache($query, $params);
		$this->assertFalse($result === false);
	}

/**
	 * testParameterMismatch method
	 *
	 * @return void
	 */
	public function testParameterMismatch() {
		$this->expectException(\PDOException::class);
		$this->skipIf($this->db instanceof Sqlite, 'Sqlite does not accept real prepared statements, no way to check this');
		$this->loadFixtures('Article', 'User', 'Tag', 'ArticlesTag');
		$Article = new Article();

		$query = 'SELECT * FROM ' . $this->db->fullTableName('articles');
		$query .= ' WHERE ' . $this->db->fullTableName('articles');
		$query .= '.published = ? AND ' . $this->db->fullTableName('articles') . '.user_id = ?';
		$params = array('Y');

		$Article->query($query, $params);
	}

/**
	 * testVeryStrangeUseCase method
	 *
	 * @return void
	 */
	public function testVeryStrangeUseCase() {
		$this->expectException(\PDOException::class);
		$this->loadFixtures('Article', 'User', 'Tag', 'ArticlesTag');
		$Article = new Article();

		$query = 'SELECT * FROM ? WHERE ? = ? AND ? = ?';
		$param = array(
			$this->db->fullTableName('articles'),
			$this->db->fullTableName('articles') . '.user_id', '3',
			$this->db->fullTableName('articles') . '.published', 'Y'
		);

		$Article->query($query, $param);
	}

/**
 * testRecursiveUnbind method
 *
 * @return void
 */
	public function testRecursiveUnbind() {
		$this->skipIf($this->db instanceof Sqlserver, 'The test of testRecursiveUnbind test is not compatible with SQL Server, because it check for time columns.');

		$this->loadFixtures('Apple', 'Sample');
		$TestModel = new Apple();
		$TestModel->recursive = 2;

		$result = $TestModel->find('all');
		$expected = array(
			array(
				'Apple' => array(
					'id' => 1,
					'apple_id' => 2,
					'color' => 'Red 1',
					'name' => 'Red Apple 1',
					'created' => '2006-11-22 10:38:58',
					'date' => '1951-01-04',
					'modified' => '2006-12-01 13:31:26',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 2,
					'apple_id' => 1,
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 1,
						'apple_id' => 2,
						'color' => 'Red 1',
						'name' => 'Red Apple 1',
						'created' => '2006-11-22 10:38:58',
						'date' => '1951-01-04',
						'modified' => '2006-12-01 13:31:26',
						'mytime' => '22:57:17'
					),
					'Sample' => array(
						'id' => 2,
						'apple_id' => 2,
						'name' => 'sample2'
					),
					'Child' => array(
						array(
							'id' => 1,
							'apple_id' => 2,
							'color' => 'Red 1',
							'name' => 'Red Apple 1',
							'created' => '2006-11-22 10:38:58',
							'date' => '1951-01-04',
							'modified' => '2006-12-01 13:31:26',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 3,
							'apple_id' => 2,
							'color' => 'blue green',
							'name' => 'green blue',
							'created' => '2006-12-25 05:13:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:24',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 4,
							'apple_id' => 2,
							'color' => 'Blue Green',
							'name' => 'Test Name',
							'created' => '2006-12-25 05:23:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:36',
							'mytime' => '22:57:17'
					))),
					'Sample' => array(
						'id' => '',
						'apple_id' => '',
						'name' => ''
					),
					'Child' => array(
						array(
							'id' => 2,
							'apple_id' => 1,
							'color' => 'Bright Red 1',
							'name' => 'Bright Red Apple',
							'created' => '2006-11-22 10:43:13',
							'date' => '2014-01-01',
							'modified' => '2006-11-30 18:38:10',
							'mytime' => '22:57:17',
							'Parent' => array(
								'id' => 1,
								'apple_id' => 2,
								'color' => 'Red 1',
								'name' => 'Red Apple 1',
								'created' => '2006-11-22 10:38:58',
								'date' => '1951-01-04',
								'modified' => '2006-12-01 13:31:26',
								'mytime' => '22:57:17'
							),
							'Sample' => array(
								'id' => 2,
								'apple_id' => 2,
								'name' => 'sample2'
							),
							'Child' => array(
								array(
									'id' => 1,
									'apple_id' => 2,
									'color' => 'Red 1',
									'name' => 'Red Apple 1',
									'created' => '2006-11-22 10:38:58',
									'date' => '1951-01-04',
									'modified' => '2006-12-01 13:31:26',
									'mytime' => '22:57:17'
								),
								array(
									'id' => 3,
									'apple_id' => 2,
									'color' => 'blue green',
									'name' => 'green blue',
									'created' => '2006-12-25 05:13:36',
									'date' => '2006-12-25',
									'modified' => '2006-12-25 05:23:24',
									'mytime' => '22:57:17'
								),
								array(
									'id' => 4,
									'apple_id' => 2,
									'color' => 'Blue Green',
									'name' => 'Test Name',
									'created' => '2006-12-25 05:23:36',
									'date' => '2006-12-25',
									'modified' => '2006-12-25 05:23:36',
									'mytime' => '22:57:17'
			))))),
			array(
				'Apple' => array(
					'id' => 2,
					'apple_id' => 1,
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
						'id' => 1,
						'apple_id' => 2,
						'color' => 'Red 1',
						'name' => 'Red Apple 1',
						'created' => '2006-11-22 10:38:58',
						'date' => '1951-01-04',
						'modified' => '2006-12-01 13:31:26',
						'mytime' => '22:57:17',
						'Parent' => array(
							'id' => 2,
							'apple_id' => 1,
							'color' => 'Bright Red 1',
							'name' => 'Bright Red Apple',
							'created' => '2006-11-22 10:43:13',
							'date' => '2014-01-01',
							'modified' => '2006-11-30 18:38:10',
							'mytime' => '22:57:17'
						),
						'Sample' => array(),
						'Child' => array(
							array(
								'id' => 2,
								'apple_id' => 1,
								'color' => 'Bright Red 1',
								'name' => 'Bright Red Apple',
								'created' => '2006-11-22 10:43:13',
								'date' => '2014-01-01',
								'modified' => '2006-11-30 18:38:10',
								'mytime' => '22:57:17'
					))),
					'Sample' => array(
						'id' => 2,
						'apple_id' => 2,
						'name' => 'sample2',
						'Apple' => array(
							'id' => 2,
							'apple_id' => 1,
							'color' => 'Bright Red 1',
							'name' => 'Bright Red Apple',
							'created' => '2006-11-22 10:43:13',
							'date' => '2014-01-01',
							'modified' => '2006-11-30 18:38:10',
							'mytime' => '22:57:17'
					)),
					'Child' => array(
						array(
							'id' => 1,
							'apple_id' => 2,
							'color' => 'Red 1',
							'name' => 'Red Apple 1',
							'created' => '2006-11-22 10:38:58',
							'date' => '1951-01-04',
							'modified' => '2006-12-01 13:31:26',
							'mytime' => '22:57:17',
							'Parent' => array(
								'id' => 2,
								'apple_id' => 1,
								'color' => 'Bright Red 1',
								'name' => 'Bright Red Apple',
								'created' => '2006-11-22 10:43:13',
								'date' => '2014-01-01',
								'modified' => '2006-11-30 18:38:10',
								'mytime' => '22:57:17'
							),
							'Sample' => array(),
							'Child' => array(
								array(
									'id' => 2,
									'apple_id' => 1,
									'color' => 'Bright Red 1',
									'name' => 'Bright Red Apple',
									'created' => '2006-11-22 10:43:13',
									'date' => '2014-01-01',
									'modified' => '2006-11-30 18:38:10',
									'mytime' => '22:57:17'
						))),
						array(
							'id' => 3,
							'apple_id' => 2,
							'color' => 'blue green',
							'name' => 'green blue',
							'created' => '2006-12-25 05:13:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:24',
							'mytime' => '22:57:17',
							'Parent' => array(
								'id' => 2,
								'apple_id' => 1,
								'color' => 'Bright Red 1',
								'name' => 'Bright Red Apple',
								'created' => '2006-11-22 10:43:13',
								'date' => '2014-01-01',
								'modified' => '2006-11-30 18:38:10',
								'mytime' => '22:57:17'
							),
							'Sample' => array(
								'id' => 1,
								'apple_id' => 3,
								'name' => 'sample1'
						)),
						array(
							'id' => 4,
							'apple_id' => 2,
							'color' => 'Blue Green',
							'name' => 'Test Name',
							'created' => '2006-12-25 05:23:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:36',
							'mytime' => '22:57:17',
							'Parent' => array(
								'id' => 2,
								'apple_id' => 1,
								'color' => 'Bright Red 1',
								'name' => 'Bright Red Apple',
								'created' => '2006-11-22 10:43:13',
								'date' => '2014-01-01',
								'modified' => '2006-11-30 18:38:10',
								'mytime' => '22:57:17'
							),
							'Sample' => array(
								'id' => 3,
								'apple_id' => 4,
								'name' => 'sample3'
							),
							'Child' => array(
								array(
									'id' => 6,
									'apple_id' => 4,
									'color' => 'My new appleOrange',
									'name' => 'My new apple',
									'created' => '2006-12-25 05:29:39',
									'date' => '2006-12-25',
									'modified' => '2006-12-25 05:29:39',
									'mytime' => '22:57:17'
			))))),
			array(
				'Apple' => array(
					'id' => 3,
					'apple_id' => 2,
					'color' => 'blue green',
					'name' => 'green blue',
					'created' => '2006-12-25 05:13:36',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:23:24',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 2,
					'apple_id' => 1,
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 1,
						'apple_id' => 2,
						'color' => 'Red 1',
						'name' => 'Red Apple 1',
						'created' => '2006-11-22 10:38:58',
						'date' => '1951-01-04',
						'modified' => '2006-12-01 13:31:26',
						'mytime' => '22:57:17'
					),
					'Sample' => array(
						'id' => 2,
						'apple_id' => 2,
						'name' => 'sample2'
					),
					'Child' => array(
						array(
							'id' => 1,
							'apple_id' => 2,
							'color' => 'Red 1',
							'name' => 'Red Apple 1',
							'created' => '2006-11-22 10:38:58',
							'date' => '1951-01-04',
							'modified' => '2006-12-01 13:31:26',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 3,
							'apple_id' => 2,
							'color' => 'blue green',
							'name' => 'green blue',
							'created' => '2006-12-25 05:13:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:24',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 4,
							'apple_id' => 2,
							'color' => 'Blue Green',
							'name' => 'Test Name',
							'created' => '2006-12-25 05:23:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:36',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => 1,
					'apple_id' => 3,
					'name' => 'sample1',
					'Apple' => array(
						'id' => 3,
						'apple_id' => 2,
						'color' => 'blue green',
						'name' => 'green blue',
						'created' => '2006-12-25 05:13:36',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:23:24',
						'mytime' => '22:57:17'
				)),
				'Child' => array()
			),
			array(
				'Apple' => array(
					'id' => 4,
					'apple_id' => 2,
					'color' => 'Blue Green',
					'name' => 'Test Name',
					'created' => '2006-12-25 05:23:36',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:23:36',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 2,
					'apple_id' => 1,
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 1,
						'apple_id' => 2,
						'color' => 'Red 1',
						'name' => 'Red Apple 1',
						'created' => '2006-11-22 10:38:58',
						'date' => '1951-01-04',
						'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
						'Child' => array(
							array(
								'id' => 1,
								'apple_id' => 2,
								'color' => 'Red 1',
								'name' => 'Red Apple 1',
								'created' => '2006-11-22 10:38:58',
								'date' => '1951-01-04',
								'modified' => '2006-12-01 13:31:26',
								'mytime' => '22:57:17'
							),
							array(
								'id' => 3,
								'apple_id' => 2,
								'color' => 'blue green',
								'name' => 'green blue',
								'created' => '2006-12-25 05:13:36',
								'date' => '2006-12-25',
								'modified' => '2006-12-25 05:23:24',
								'mytime' => '22:57:17'
							),
							array(
								'id' => 4,
								'apple_id' => 2,
								'color' => 'Blue Green',
								'name' => 'Test Name',
								'created' => '2006-12-25 05:23:36',
								'date' => '2006-12-25',
								'modified' => '2006-12-25 05:23:36',
								'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => 3,
					'apple_id' => 4,
					'name' => 'sample3',
					'Apple' => array(
						'id' => 4,
						'apple_id' => 2,
						'color' => 'Blue Green',
						'name' => 'Test Name',
						'created' => '2006-12-25 05:23:36',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:23:36',
						'mytime' => '22:57:17'
				)),
				'Child' => array(
					array(
						'id' => 6,
						'apple_id' => 4,
						'color' => 'My new appleOrange',
						'name' => 'My new apple',
						'created' => '2006-12-25 05:29:39',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:29:39',
						'mytime' => '22:57:17',
						'Parent' => array(
							'id' => 4,
							'apple_id' => 2,
							'color' => 'Blue Green',
							'name' => 'Test Name',
							'created' => '2006-12-25 05:23:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:36',
							'mytime' => '22:57:17'
						),
						'Sample' => array(),
						'Child' => array(
							array(
								'id' => 7,
								'apple_id' => 6,
								'color' => 'Some wierd color',
								'name' => 'Some odd color',
								'created' => '2006-12-25 05:34:21',
								'date' => '2006-12-25',
								'modified' => '2006-12-25 05:34:21',
								'mytime' => '22:57:17'
			))))),
			array(
				'Apple' => array(
					'id' => 5,
					'apple_id' => 5,
					'color' => 'Green',
					'name' => 'Blue Green',
					'created' => '2006-12-25 05:24:06',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:16',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 5,
					'apple_id' => 5,
					'color' => 'Green',
					'name' => 'Blue Green',
					'created' => '2006-12-25 05:24:06',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:16',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 5,
						'apple_id' => 5,
						'color' => 'Green',
						'name' => 'Blue Green',
						'created' => '2006-12-25 05:24:06',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:29:16',
						'mytime' => '22:57:17'
					),
					'Sample' => array(
						'id' => 4,
						'apple_id' => 5,
						'name' => 'sample4'
					),
					'Child' => array(
						array(
							'id' => 5,
							'apple_id' => 5,
							'color' => 'Green',
							'name' => 'Blue Green',
							'created' => '2006-12-25 05:24:06',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:29:16',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => 4,
					'apple_id' => 5,
					'name' => 'sample4',
					'Apple' => array(
						'id' => 5,
						'apple_id' => 5,
						'color' => 'Green',
						'name' => 'Blue Green',
						'created' => '2006-12-25 05:24:06',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:29:16',
						'mytime' => '22:57:17'
					)),
					'Child' => array(
						array(
							'id' => 5,
							'apple_id' => 5,
							'color' => 'Green',
							'name' => 'Blue Green',
							'created' => '2006-12-25 05:24:06',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:29:16',
							'mytime' => '22:57:17',
							'Parent' => array(
								'id' => 5,
								'apple_id' => 5,
								'color' => 'Green',
								'name' => 'Blue Green',
								'created' => '2006-12-25 05:24:06',
								'date' => '2006-12-25',
								'modified' => '2006-12-25 05:29:16',
								'mytime' => '22:57:17'
							),
							'Sample' => array(
								'id' => 4,
								'apple_id' => 5,
								'name' => 'sample4'
							),
							'Child' => array(
								array(
									'id' => 5,
									'apple_id' => 5,
									'color' => 'Green',
									'name' => 'Blue Green',
									'created' => '2006-12-25 05:24:06',
									'date' => '2006-12-25',
									'modified' => '2006-12-25 05:29:16',
									'mytime' => '22:57:17'
			))))),
			array(
				'Apple' => array(
					'id' => 6,
					'apple_id' => 4,
					'color' => 'My new appleOrange',
					'name' => 'My new apple',
					'created' => '2006-12-25 05:29:39',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:39',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 4,
					'apple_id' => 2,
					'color' => 'Blue Green',
					'name' => 'Test Name',
					'created' => '2006-12-25 05:23:36',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:23:36',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 2,
						'apple_id' => 1,
						'color' => 'Bright Red 1',
						'name' => 'Bright Red Apple',
						'created' => '2006-11-22 10:43:13',
						'date' => '2014-01-01',
						'modified' => '2006-11-30 18:38:10',
						'mytime' => '22:57:17'
					),
					'Sample' => array(
						'id' => 3,
						'apple_id' => 4,
						'name' => 'sample3'
					),
					'Child' => array(
						array(
							'id' => 6,
							'apple_id' => 4,
							'color' => 'My new appleOrange',
							'name' => 'My new apple',
							'created' => '2006-12-25 05:29:39',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:29:39',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => '',
					'apple_id' => '',
					'name' => ''
				),
				'Child' => array(
					array(
						'id' => 7,
						'apple_id' => 6,
						'color' => 'Some wierd color',
						'name' => 'Some odd color',
						'created' => '2006-12-25 05:34:21',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:34:21',
						'mytime' => '22:57:17',
						'Parent' => array(
							'id' => 6,
							'apple_id' => 4,
							'color' => 'My new appleOrange',
							'name' => 'My new apple',
							'created' => '2006-12-25 05:29:39',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:29:39',
							'mytime' => '22:57:17'
						),
						'Sample' => array()
			))),
			array(
				'Apple' => array(
					'id' => 7,
					'apple_id' => 6,
					'color' =>
					'Some wierd color',
					'name' => 'Some odd color',
					'created' => '2006-12-25 05:34:21',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:34:21',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 6,
					'apple_id' => 4,
					'color' => 'My new appleOrange',
					'name' => 'My new apple',
					'created' => '2006-12-25 05:29:39',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:39',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 4,
						'apple_id' => 2,
						'color' => 'Blue Green',
						'name' => 'Test Name',
						'created' => '2006-12-25 05:23:36',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:23:36',
						'mytime' => '22:57:17'
					),
					'Sample' => array(),
					'Child' => array(
						array(
							'id' => 7,
							'apple_id' => 6,
							'color' => 'Some wierd color',
							'name' => 'Some odd color',
							'created' => '2006-12-25 05:34:21',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:34:21',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => '',
					'apple_id' => '',
					'name' => ''
				),
				'Child' => array()));
		$this->assertEquals($expected, $result);

		$result = $TestModel->Parent->unbindModel(array('hasOne' => array('Sample')));
		$this->assertTrue($result);

		$result = $TestModel->find('all');
		$expected = array(
			array(
				'Apple' => array(
					'id' => 1,
					'apple_id' => 2,
					'color' => 'Red 1',
					'name' => 'Red Apple 1',
					'created' => '2006-11-22 10:38:58',
					'date' => '1951-01-04',
					'modified' => '2006-12-01 13:31:26',
					'mytime' => '22:57:17'),
					'Parent' => array(
						'id' => 2,
						'apple_id' => 1,
						'color' => 'Bright Red 1',
						'name' => 'Bright Red Apple',
						'created' => '2006-11-22 10:43:13',
						'date' => '2014-01-01',
						'modified' => '2006-11-30 18:38:10',
						'mytime' => '22:57:17',
						'Parent' => array(
							'id' => 1,
							'apple_id' => 2,
							'color' => 'Red 1',
							'name' => 'Red Apple 1',
							'created' => '2006-11-22 10:38:58',
							'date' => '1951-01-04',
							'modified' => '2006-12-01 13:31:26',
							'mytime' => '22:57:17'
						),
						'Child' => array(
							array(
								'id' => 1,
								'apple_id' => 2,
								'color' => 'Red 1',
								'name' => 'Red Apple 1',
								'created' => '2006-11-22 10:38:58',
								'date' => '1951-01-04',
								'modified' => '2006-12-01 13:31:26',
								'mytime' => '22:57:17'
							),
							array(
								'id' => 3,
								'apple_id' => 2,
								'color' => 'blue green',
								'name' => 'green blue',
								'created' => '2006-12-25 05:13:36',
								'date' => '2006-12-25',
								'modified' => '2006-12-25 05:23:24',
								'mytime' => '22:57:17'
							),
							array(
								'id' => 4,
								'apple_id' => 2,
								'color' => 'Blue Green',
								'name' => 'Test Name',
								'created' => '2006-12-25 05:23:36',
								'date' => '2006-12-25',
								'modified' => '2006-12-25 05:23:36',
								'mytime' => '22:57:17'
					))),
					'Sample' => array(
						'id' => '',
						'apple_id' => '',
						'name' => ''
					),
					'Child' => array(
						array(
							'id' => 2,
							'apple_id' => 1,
							'color' => 'Bright Red 1',
							'name' => 'Bright Red Apple',
							'created' => '2006-11-22 10:43:13',
							'date' => '2014-01-01',
							'modified' => '2006-11-30 18:38:10',
							'mytime' => '22:57:17',
							'Parent' => array(
								'id' => 1,
								'apple_id' => 2,
								'color' => 'Red 1',
								'name' => 'Red Apple 1',
								'created' => '2006-11-22 10:38:58',
								'date' => '1951-01-04',
								'modified' => '2006-12-01 13:31:26',
								'mytime' => '22:57:17'
							),
							'Sample' => array(
								'id' => 2,
								'apple_id' => 2,
								'name' => 'sample2'
							),
							'Child' => array(
								array(
									'id' => 1,
									'apple_id' => 2,
									'color' => 'Red 1',
									'name' => 'Red Apple 1',
									'created' => '2006-11-22 10:38:58',
									'date' => '1951-01-04',
									'modified' => '2006-12-01 13:31:26',
									'mytime' => '22:57:17'
								),
								array(
									'id' => 3,
									'apple_id' => 2,
									'color' => 'blue green',
									'name' => 'green blue',
									'created' => '2006-12-25 05:13:36',
									'date' => '2006-12-25',
									'modified' => '2006-12-25 05:23:24',
									'mytime' => '22:57:17'
								),
								array(
									'id' => 4,
									'apple_id' => 2,
									'color' => 'Blue Green',
									'name' => 'Test Name',
									'created' => '2006-12-25 05:23:36',
									'date' => '2006-12-25',
									'modified' => '2006-12-25 05:23:36',
									'mytime' => '22:57:17'
			))))),
			array(
				'Apple' => array(
					'id' => 2,
					'apple_id' => 1,
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 1,
					'apple_id' => 2,
					'color' => 'Red 1',
					'name' => 'Red Apple 1',
					'created' => '2006-11-22 10:38:58',
					'date' => '1951-01-04',
					'modified' => '2006-12-01 13:31:26',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 2,
						'apple_id' => 1,
						'color' => 'Bright Red 1',
						'name' => 'Bright Red Apple',
						'created' => '2006-11-22 10:43:13',
						'date' => '2014-01-01',
						'modified' => '2006-11-30 18:38:10',
						'mytime' => '22:57:17'
					),
					'Child' => array(
						array(
							'id' => 2,
							'apple_id' => 1,
							'color' => 'Bright Red 1',
							'name' => 'Bright Red Apple',
							'created' => '2006-11-22 10:43:13',
							'date' => '2014-01-01',
							'modified' => '2006-11-30 18:38:10',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => 2,
					'apple_id' => 2,
					'name' => 'sample2',
					'Apple' => array(
						'id' => 2,
						'apple_id' => 1,
						'color' => 'Bright Red 1',
						'name' => 'Bright Red Apple',
						'created' => '2006-11-22 10:43:13',
						'date' => '2014-01-01',
						'modified' => '2006-11-30 18:38:10',
						'mytime' => '22:57:17'
				)),
				'Child' => array(
					array(
						'id' => 1,
						'apple_id' => 2,
						'color' => 'Red 1',
						'name' => 'Red Apple 1',
						'created' => '2006-11-22 10:38:58',
						'date' => '1951-01-04',
						'modified' => '2006-12-01 13:31:26',
						'mytime' => '22:57:17',
						'Parent' => array(
							'id' => 2,
							'apple_id' => 1,
							'color' => 'Bright Red 1',
							'name' => 'Bright Red Apple',
							'created' => '2006-11-22 10:43:13',
							'date' => '2014-01-01',
							'modified' => '2006-11-30 18:38:10',
							'mytime' => '22:57:17'
						),
						'Sample' => array(),
						'Child' => array(
							array(
								'id' => 2,
								'apple_id' => 1,
								'color' => 'Bright Red 1',
								'name' => 'Bright Red Apple',
								'created' => '2006-11-22 10:43:13',
								'date' => '2014-01-01', 'modified' =>
								'2006-11-30 18:38:10',
								'mytime' => '22:57:17'
					))),
					array(
						'id' => 3,
						'apple_id' => 2,
						'color' => 'blue green',
						'name' => 'green blue',
						'created' => '2006-12-25 05:13:36',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:23:24',
						'mytime' => '22:57:17',
						'Parent' => array(
							'id' => 2,
							'apple_id' => 1,
							'color' => 'Bright Red 1',
							'name' => 'Bright Red Apple',
							'created' => '2006-11-22 10:43:13',
							'date' => '2014-01-01',
							'modified' => '2006-11-30 18:38:10',
							'mytime' => '22:57:17'
						),
						'Sample' => array(
							'id' => 1,
							'apple_id' => 3,
							'name' => 'sample1'
					)),
					array(
						'id' => 4,
						'apple_id' => 2,
						'color' => 'Blue Green',
						'name' => 'Test Name',
						'created' => '2006-12-25 05:23:36',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:23:36',
						'mytime' => '22:57:17',
						'Parent' => array(
							'id' => 2,
							'apple_id' => 1,
							'color' => 'Bright Red 1',
							'name' => 'Bright Red Apple',
							'created' => '2006-11-22 10:43:13',
							'date' => '2014-01-01',
							'modified' => '2006-11-30 18:38:10',
							'mytime' => '22:57:17'
						),
						'Sample' => array(
							'id' => 3,
							'apple_id' => 4,
							'name' => 'sample3'
						),
						'Child' => array(
							array(
								'id' => 6,
								'apple_id' => 4,
								'color' => 'My new appleOrange',
								'name' => 'My new apple',
								'created' => '2006-12-25 05:29:39',
								'date' => '2006-12-25',
								'modified' => '2006-12-25 05:29:39',
								'mytime' => '22:57:17'
			))))),
			array(
				'Apple' => array(
					'id' => 3,
					'apple_id' => 2,
					'color' => 'blue green',
					'name' => 'green blue',
					'created' => '2006-12-25 05:13:36',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:23:24',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 2,
					'apple_id' => 1,
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 1,
						'apple_id' => 2,
						'color' => 'Red 1',
						'name' => 'Red Apple 1',
						'created' => '2006-11-22 10:38:58',
						'date' => '1951-01-04',
						'modified' => '2006-12-01 13:31:26',
						'mytime' => '22:57:17'
					),
					'Child' => array(
						array(
							'id' => 1,
							'apple_id' => 2,
							'color' => 'Red 1',
							'name' => 'Red Apple 1',
							'created' => '2006-11-22 10:38:58',
							'date' => '1951-01-04',
							'modified' => '2006-12-01 13:31:26',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 3,
							'apple_id' => 2,
							'color' => 'blue green',
							'name' => 'green blue',
							'created' => '2006-12-25 05:13:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:24',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 4,
							'apple_id' => 2,
							'color' => 'Blue Green',
							'name' => 'Test Name',
							'created' => '2006-12-25 05:23:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:36',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => 1,
					'apple_id' => 3,
					'name' => 'sample1',
					'Apple' => array(
						'id' => 3,
						'apple_id' => 2,
						'color' => 'blue green',
						'name' => 'green blue',
						'created' => '2006-12-25 05:13:36',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:23:24',
						'mytime' => '22:57:17'
				)),
				'Child' => array()
			),
			array(
				'Apple' => array(
					'id' => 4,
					'apple_id' => 2,
					'color' => 'Blue Green',
					'name' => 'Test Name',
					'created' => '2006-12-25 05:23:36',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:23:36',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 2,
					'apple_id' => 1,
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 1,
						'apple_id' => 2,
						'color' => 'Red 1',
						'name' => 'Red Apple 1',
						'created' => '2006-11-22 10:38:58',
						'date' => '1951-01-04',
						'modified' => '2006-12-01 13:31:26',
						'mytime' => '22:57:17'
					),
					'Child' => array(
						array(
							'id' => 1,
							'apple_id' => 2,
							'color' => 'Red 1',
							'name' => 'Red Apple 1',
							'created' => '2006-11-22 10:38:58',
							'date' => '1951-01-04',
							'modified' => '2006-12-01 13:31:26',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 3,
							'apple_id' => 2,
							'color' => 'blue green',
							'name' => 'green blue',
							'created' => '2006-12-25 05:13:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:24',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 4,
							'apple_id' => 2,
							'color' => 'Blue Green',
							'name' => 'Test Name',
							'created' => '2006-12-25 05:23:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:36',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => 3,
					'apple_id' => 4,
					'name' => 'sample3',
					'Apple' => array(
						'id' => 4,
						'apple_id' => 2,
						'color' => 'Blue Green',
						'name' => 'Test Name',
						'created' => '2006-12-25 05:23:36',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:23:36',
						'mytime' => '22:57:17'
				)),
				'Child' => array(
					array(
						'id' => 6,
						'apple_id' => 4,
						'color' => 'My new appleOrange',
						'name' => 'My new apple',
						'created' => '2006-12-25 05:29:39',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:29:39',
						'mytime' => '22:57:17',
						'Parent' => array(
							'id' => 4,
							'apple_id' => 2,
							'color' => 'Blue Green',
							'name' => 'Test Name',
							'created' => '2006-12-25 05:23:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:36',
							'mytime' => '22:57:17'
						),
						'Sample' => array(),
							'Child' => array(
								array(
									'id' => 7,
									'apple_id' => 6,
									'color' => 'Some wierd color',
									'name' => 'Some odd color',
									'created' => '2006-12-25 05:34:21',
									'date' => '2006-12-25',
									'modified' => '2006-12-25 05:34:21',
									'mytime' => '22:57:17'
			))))),
			array(
				'Apple' => array(
					'id' => 5,
					'apple_id' => 5,
					'color' => 'Green',
					'name' => 'Blue Green',
					'created' => '2006-12-25 05:24:06',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:16',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 5,
					'apple_id' => 5,
					'color' => 'Green',
					'name' => 'Blue Green',
					'created' => '2006-12-25 05:24:06',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:16',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 5,
						'apple_id' => 5,
						'color' => 'Green',
						'name' => 'Blue Green',
						'created' => '2006-12-25 05:24:06',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:29:16',
						'mytime' => '22:57:17'
					),
					'Child' => array(
						array(
							'id' => 5,
							'apple_id' => 5,
							'color' => 'Green',
							'name' => 'Blue Green',
							'created' => '2006-12-25 05:24:06',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:29:16',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => 4,
					'apple_id' => 5,
					'name' => 'sample4',
					'Apple' => array(
						'id' => 5,
						'apple_id' => 5,
						'color' => 'Green',
						'name' => 'Blue Green',
						'created' => '2006-12-25 05:24:06',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:29:16',
						'mytime' => '22:57:17'
				)),
				'Child' => array(
					array(
						'id' => 5,
						'apple_id' => 5,
						'color' => 'Green',
						'name' => 'Blue Green',
						'created' => '2006-12-25 05:24:06',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:29:16',
						'mytime' => '22:57:17',
						'Parent' => array(
							'id' => 5,
							'apple_id' => 5,
							'color' => 'Green',
							'name' => 'Blue Green',
							'created' => '2006-12-25 05:24:06',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:29:16',
							'mytime' => '22:57:17'
						),
						'Sample' => array(
							'id' => 4,
							'apple_id' => 5,
							'name' => 'sample4'
						),
						'Child' => array(
							array(
								'id' => 5,
								'apple_id' => 5,
								'color' => 'Green',
								'name' => 'Blue Green',
								'created' => '2006-12-25 05:24:06',
								'date' => '2006-12-25',
								'modified' => '2006-12-25 05:29:16',
								'mytime' => '22:57:17'
			))))),
			array(
				'Apple' => array(
					'id' => 6,
					'apple_id' => 4,
					'color' => 'My new appleOrange',
					'name' => 'My new apple',
					'created' => '2006-12-25 05:29:39',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:39',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 4,
					'apple_id' => 2,
					'color' => 'Blue Green',
					'name' => 'Test Name',
					'created' => '2006-12-25 05:23:36',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:23:36',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 2,
						'apple_id' => 1,
						'color' => 'Bright Red 1',
						'name' => 'Bright Red Apple',
						'created' => '2006-11-22 10:43:13',
						'date' => '2014-01-01',
						'modified' => '2006-11-30 18:38:10',
						'mytime' => '22:57:17'
					),
					'Child' => array(
						array(
							'id' => 6,
							'apple_id' => 4,
							'color' => 'My new appleOrange',
							'name' => 'My new apple',
							'created' => '2006-12-25 05:29:39',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:29:39',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => '',
					'apple_id' => '',
					'name' => ''
				),
				'Child' => array(
					array(
						'id' => 7,
						'apple_id' => 6,
						'color' => 'Some wierd color',
						'name' => 'Some odd color',
						'created' => '2006-12-25 05:34:21',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:34:21',
						'mytime' => '22:57:17',
						'Parent' => array(
							'id' => 6,
							'apple_id' => 4,
							'color' => 'My new appleOrange',
							'name' => 'My new apple',
							'created' => '2006-12-25 05:29:39',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:29:39',
							'mytime' => '22:57:17'
						),
						'Sample' => array()
			))),
			array(
				'Apple' => array(
					'id' => 7,
					'apple_id' => 6,
					'color' => 'Some wierd color',
					'name' => 'Some odd color',
					'created' => '2006-12-25 05:34:21',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:34:21',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 6,
					'apple_id' => 4,
					'color' => 'My new appleOrange',
					'name' => 'My new apple',
					'created' => '2006-12-25 05:29:39',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:39',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 4,
						'apple_id' => 2,
						'color' => 'Blue Green',
						'name' => 'Test Name',
						'created' => '2006-12-25 05:23:36',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:23:36',
						'mytime' => '22:57:17'
					),
					'Child' => array(
						array(
							'id' => 7,
							'apple_id' => 6,
							'color' => 'Some wierd color',
							'name' => 'Some odd color',
							'created' => '2006-12-25 05:34:21',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:34:21',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => '',
					'apple_id' => '',
					'name' => ''
				),
				'Child' => array()
		));

		$this->assertEquals($expected, $result);

		$result = $TestModel->Parent->unbindModel(array('hasOne' => array('Sample')));
		$this->assertTrue($result);

		$result = $TestModel->unbindModel(array('hasMany' => array('Child')));
		$this->assertTrue($result);

		$result = $TestModel->find('all');
		$expected = array(
			array(
				'Apple' => array(
					'id' => 1,
					'apple_id' => 2,
					'color' => 'Red 1',
					'name' => 'Red Apple 1',
					'created' => '2006-11-22 10:38:58',
					'date' => '1951-01-04',
					'modified' => '2006-12-01 13:31:26',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 2,
					'apple_id' => 1,
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 1,
						'apple_id' => 2,
						'color' => 'Red 1',
						'name' => 'Red Apple 1',
						'created' => '2006-11-22 10:38:58',
						'date' => '1951-01-04',
						'modified' => '2006-12-01 13:31:26',
						'mytime' => '22:57:17'
					),
					'Child' => array(
						array(
							'id' => 1,
							'apple_id' => 2,
							'color' => 'Red 1',
							'name' => 'Red Apple 1',
							'created' => '2006-11-22 10:38:58',
							'date' => '1951-01-04',
							'modified' => '2006-12-01 13:31:26',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 3,
							'apple_id' => 2,
							'color' => 'blue green',
							'name' => 'green blue',
							'created' => '2006-12-25 05:13:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:24',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 4,
							'apple_id' => 2,
							'color' => 'Blue Green',
							'name' => 'Test Name',
							'created' => '2006-12-25 05:23:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:36',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => '',
					'apple_id' => '',
					'name' => ''
			)),
			array(
				'Apple' => array(
					'id' => 2,
					'apple_id' => 1,
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 1,
					'apple_id' => 2,
					'color' => 'Red 1',
					'name' => 'Red Apple 1',
					'created' => '2006-11-22 10:38:58',
					'date' => '1951-01-04',
					'modified' => '2006-12-01 13:31:26',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 2,
						'apple_id' => 1,
						'color' => 'Bright Red 1',
						'name' => 'Bright Red Apple',
						'created' => '2006-11-22 10:43:13',
						'date' => '2014-01-01',
						'modified' => '2006-11-30 18:38:10',
						'mytime' => '22:57:17'
					),
					'Child' => array(
						array(
							'id' => 2,
							'apple_id' => 1,
							'color' => 'Bright Red 1',
							'name' => 'Bright Red Apple',
							'created' => '2006-11-22 10:43:13',
							'date' => '2014-01-01',
							'modified' => '2006-11-30 18:38:10',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => 2,
					'apple_id' => 2,
					'name' => 'sample2',
					'Apple' => array(
						'id' => 2,
						'apple_id' => 1,
						'color' => 'Bright Red 1',
						'name' => 'Bright Red Apple',
						'created' => '2006-11-22 10:43:13',
						'date' => '2014-01-01',
						'modified' => '2006-11-30 18:38:10',
						'mytime' => '22:57:17'
			))),
			array(
				'Apple' => array(
				'id' => 3,
				'apple_id' => 2,
				'color' => 'blue green',
				'name' => 'green blue',
				'created' => '2006-12-25 05:13:36',
				'date' => '2006-12-25',
				'modified' => '2006-12-25 05:23:24',
				'mytime' => '22:57:17'
			),
			'Parent' => array(
				'id' => 2,
				'apple_id' => 1,
				'color' => 'Bright Red 1',
				'name' => 'Bright Red Apple',
				'created' => '2006-11-22 10:43:13',
				'date' => '2014-01-01',
				'modified' => '2006-11-30 18:38:10',
				'mytime' => '22:57:17',
				'Parent' => array(
					'id' => 1,
					'apple_id' => 2,
					'color' => 'Red 1',
					'name' => 'Red Apple 1',
					'created' => '2006-11-22 10:38:58',
					'date' => '1951-01-04',
					'modified' => '2006-12-01 13:31:26',
					'mytime' => '22:57:17'
				),
				'Child' => array(
					array(
						'id' => 1,
						'apple_id' => 2,
						'color' => 'Red 1',
						'name' => 'Red Apple 1',
						'created' => '2006-11-22 10:38:58',
						'date' => '1951-01-04',
						'modified' => '2006-12-01 13:31:26',
						'mytime' => '22:57:17'
					),
					array(
						'id' => 3,
						'apple_id' => 2,
						'color' => 'blue green',
						'name' => 'green blue',
						'created' => '2006-12-25 05:13:36',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:23:24',
						'mytime' => '22:57:17'
					),
					array(
						'id' => 4,
						'apple_id' => 2,
						'color' => 'Blue Green',
						'name' => 'Test Name',
						'created' => '2006-12-25 05:23:36',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:23:36',
						'mytime' => '22:57:17'
			))),
			'Sample' => array(
				'id' => 1,
				'apple_id' => 3,
				'name' => 'sample1',
				'Apple' => array(
					'id' => 3,
					'apple_id' => 2,
					'color' => 'blue green',
					'name' => 'green blue',
					'created' => '2006-12-25 05:13:36',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:23:24',
					'mytime' => '22:57:17'
		))),
		array(
			'Apple' => array(
				'id' => 4,
				'apple_id' => 2,
				'color' => 'Blue Green',
				'name' => 'Test Name',
				'created' => '2006-12-25 05:23:36',
				'date' => '2006-12-25',
				'modified' => '2006-12-25 05:23:36',
				'mytime' => '22:57:17'
			),
			'Parent' => array(
				'id' => 2,
				'apple_id' => 1,
				'color' => 'Bright Red 1',
				'name' => 'Bright Red Apple',
				'created' => '2006-11-22 10:43:13',
				'date' => '2014-01-01',
				'modified' => '2006-11-30 18:38:10',
				'mytime' => '22:57:17',
				'Parent' => array(
					'id' => 1,
					'apple_id' => 2,
					'color' => 'Red 1',
					'name' => 'Red Apple 1',
					'created' => '2006-11-22 10:38:58',
					'date' => '1951-01-04',
					'modified' => '2006-12-01 13:31:26',
					'mytime' => '22:57:17'
				),
				'Child' => array(
					array(
						'id' => 1,
						'apple_id' => 2,
						'color' => 'Red 1',
						'name' => 'Red Apple 1',
						'created' => '2006-11-22 10:38:58',
						'date' => '1951-01-04',
						'modified' => '2006-12-01 13:31:26',
						'mytime' => '22:57:17'
					),
					array(
						'id' => 3,
						'apple_id' => 2,
						'color' => 'blue green',
						'name' => 'green blue',
						'created' => '2006-12-25 05:13:36',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:23:24',
						'mytime' => '22:57:17'
					),
					array(
						'id' => 4,
						'apple_id' => 2,
						'color' => 'Blue Green',
						'name' => 'Test Name',
						'created' => '2006-12-25 05:23:36',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:23:36',
						'mytime' => '22:57:17'
			))),
			'Sample' => array(
				'id' => 3,
				'apple_id' => 4,
				'name' => 'sample3',
				'Apple' => array(
					'id' => 4,
					'apple_id' => 2,
					'color' => 'Blue Green',
					'name' => 'Test Name',
					'created' => '2006-12-25 05:23:36',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:23:36',
					'mytime' => '22:57:17'
		))),
		array(
			'Apple' => array(
				'id' => 5,
				'apple_id' => 5,
				'color' => 'Green',
				'name' => 'Blue Green',
				'created' => '2006-12-25 05:24:06',
				'date' => '2006-12-25',
				'modified' => '2006-12-25 05:29:16',
				'mytime' => '22:57:17'
			),
			'Parent' => array(
				'id' => 5,
				'apple_id' => 5,
				'color' => 'Green',
				'name' => 'Blue Green',
				'created' => '2006-12-25 05:24:06',
				'date' => '2006-12-25',
				'modified' => '2006-12-25 05:29:16',
				'mytime' => '22:57:17',
				'Parent' => array(
					'id' => 5,
					'apple_id' => 5,
					'color' => 'Green',
					'name' => 'Blue Green',
					'created' => '2006-12-25 05:24:06',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:16',
					'mytime' => '22:57:17'
				),
				'Child' => array(
					array(
						'id' => 5,
						'apple_id' => 5,
						'color' => 'Green',
						'name' => 'Blue Green',
						'created' => '2006-12-25 05:24:06',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:29:16',
						'mytime' => '22:57:17'
			))),
			'Sample' => array(
				'id' => 4,
				'apple_id' => 5,
				'name' => 'sample4',
				'Apple' => array(
					'id' => 5,
					'apple_id' => 5,
					'color' => 'Green',
					'name' => 'Blue Green',
					'created' => '2006-12-25 05:24:06',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:16',
					'mytime' => '22:57:17'
		))),
		array(
			'Apple' => array(
				'id' => 6,
				'apple_id' => 4,
				'color' => 'My new appleOrange',
				'name' => 'My new apple',
				'created' => '2006-12-25 05:29:39',
				'date' => '2006-12-25',
				'modified' => '2006-12-25 05:29:39',
				'mytime' => '22:57:17'
			),
			'Parent' => array(
				'id' => 4,
				'apple_id' => 2,
				'color' => 'Blue Green',
				'name' => 'Test Name',
				'created' => '2006-12-25 05:23:36',
				'date' => '2006-12-25',
				'modified' => '2006-12-25 05:23:36',
				'mytime' => '22:57:17',
				'Parent' => array(
					'id' => 2,
					'apple_id' => 1,
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17'
				),
				'Child' => array(
					array(
						'id' => 6,
						'apple_id' => 4,
						'color' => 'My new appleOrange',
						'name' => 'My new apple',
						'created' => '2006-12-25 05:29:39',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:29:39',
						'mytime' => '22:57:17'
			))),
			'Sample' => array(
				'id' => '',
				'apple_id' => '',
				'name' => ''
		)),
		array(
			'Apple' => array(
				'id' => 7,
				'apple_id' => 6,
				'color' => 'Some wierd color',
				'name' => 'Some odd color',
				'created' => '2006-12-25 05:34:21',
				'date' => '2006-12-25',
				'modified' => '2006-12-25 05:34:21',
				'mytime' => '22:57:17'
			),
			'Parent' => array(
				'id' => 6,
				'apple_id' => 4,
				'color' => 'My new appleOrange',
				'name' => 'My new apple',
				'created' => '2006-12-25 05:29:39',
				'date' => '2006-12-25',
				'modified' => '2006-12-25 05:29:39',
				'mytime' => '22:57:17',
				'Parent' => array(
					'id' => 4,
					'apple_id' => 2,
					'color' => 'Blue Green',
					'name' => 'Test Name',
					'created' => '2006-12-25 05:23:36',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:23:36',
					'mytime' => '22:57:17'
				),
				'Child' => array(
					array(
						'id' => 7,
						'apple_id' => 6,
						'color' => 'Some wierd color',
						'name' => 'Some odd color',
						'created' => '2006-12-25 05:34:21',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:34:21',
						'mytime' => '22:57:17'
			))),
			'Sample' => array(
				'id' => '',
				'apple_id' => '',
				'name' => ''
		)));

		$this->assertEquals($expected, $result);

		$result = $TestModel->unbindModel(array('hasMany' => 'Child'));
		$this->assertTrue($result);

		$result = $TestModel->Sample->unbindModel(array('belongsTo' => 'Apple'));
		$this->assertTrue($result);

		$result = $TestModel->find('all');
		$expected = array(
			array(
				'Apple' => array(
					'id' => 1,
					'apple_id' => 2,
					'color' => 'Red 1',
					'name' => 'Red Apple 1',
					'created' => '2006-11-22 10:38:58',
					'date' => '1951-01-04',
					'modified' => '2006-12-01 13:31:26',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 2,
					'apple_id' => 1,
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 1,
						'apple_id' => 2,
						'color' => 'Red 1',
						'name' => 'Red Apple 1',
						'created' => '2006-11-22 10:38:58',
						'date' => '1951-01-04',
						'modified' => '2006-12-01 13:31:26',
						'mytime' => '22:57:17'
					),
					'Sample' => array(
						'id' => 2,
						'apple_id' => 2,
						'name' => 'sample2'
					),
					'Child' => array(
						array(
							'id' => 1,
							'apple_id' => 2,
							'color' => 'Red 1',
							'name' => 'Red Apple 1',
							'created' => '2006-11-22 10:38:58',
							'date' => '1951-01-04',
							'modified' => '2006-12-01 13:31:26',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 3,
							'apple_id' => 2,
							'color' => 'blue green',
							'name' => 'green blue',
							'created' => '2006-12-25 05:13:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:24',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 4,
							'apple_id' => 2,
							'color' => 'Blue Green',
							'name' => 'Test Name',
							'created' => '2006-12-25 05:23:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:36',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => '',
					'apple_id' => '',
					'name' => ''
			)),
			array(
				'Apple' => array(
					'id' => 2,
					'apple_id' => 1,
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 1,
					'apple_id' => 2,
					'color' => 'Red 1',
					'name' => 'Red Apple 1',
					'created' => '2006-11-22 10:38:58',
					'date' => '1951-01-04',
					'modified' => '2006-12-01 13:31:26',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 2,
						'apple_id' => 1,
						'color' => 'Bright Red 1',
						'name' => 'Bright Red Apple',
						'created' => '2006-11-22 10:43:13',
						'date' => '2014-01-01',
						'modified' => '2006-11-30 18:38:10',
						'mytime' => '22:57:17'
					),
					'Sample' => array(),
					'Child' => array(
						array(
							'id' => 2,
							'apple_id' => 1,
							'color' => 'Bright Red 1',
							'name' => 'Bright Red Apple',
							'created' => '2006-11-22 10:43:13',
							'date' => '2014-01-01',
							'modified' => '2006-11-30 18:38:10',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => 2,
					'apple_id' => 2,
					'name' => 'sample2'
			)),
			array(
				'Apple' => array(
					'id' => 3,
					'apple_id' => 2,
					'color' => 'blue green',
					'name' => 'green blue',
					'created' => '2006-12-25 05:13:36',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:23:24',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 2,
					'apple_id' => 1,
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 1,
						'apple_id' => 2,
						'color' => 'Red 1',
						'name' => 'Red Apple 1',
						'created' => '2006-11-22 10:38:58',
						'date' => '1951-01-04',
						'modified' => '2006-12-01 13:31:26',
						'mytime' => '22:57:17'
					),
					'Sample' => array(
						'id' => 2,
						'apple_id' => 2,
						'name' => 'sample2'
					),
					'Child' => array(
						array(
							'id' => 1,
							'apple_id' => 2,
							'color' => 'Red 1',
							'name' => 'Red Apple 1',
							'created' => '2006-11-22 10:38:58',
							'date' => '1951-01-04',
							'modified' => '2006-12-01 13:31:26',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 3,
							'apple_id' => 2,
							'color' => 'blue green',
							'name' => 'green blue',
							'created' => '2006-12-25 05:13:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:24',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 4,
							'apple_id' => 2,
							'color' => 'Blue Green',
							'name' => 'Test Name',
							'created' => '2006-12-25 05:23:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:36',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => 1,
					'apple_id' => 3,
					'name' => 'sample1'
			)),
			array(
				'Apple' => array(
					'id' => 4,
					'apple_id' => 2,
					'color' => 'Blue Green',
					'name' => 'Test Name',
					'created' => '2006-12-25 05:23:36',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:23:36',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 2,
					'apple_id' => 1,
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 1,
						'apple_id' => 2,
						'color' => 'Red 1',
						'name' => 'Red Apple 1',
						'created' => '2006-11-22 10:38:58',
						'date' => '1951-01-04',
						'modified' => '2006-12-01 13:31:26',
						'mytime' => '22:57:17'
					),
					'Sample' => array(
						'id' => 2,
						'apple_id' => 2,
						'name' => 'sample2'
					),
					'Child' => array(
						array(
							'id' => 1,
							'apple_id' => 2,
							'color' => 'Red 1',
							'name' => 'Red Apple 1',
							'created' => '2006-11-22 10:38:58',
							'date' => '1951-01-04',
							'modified' => '2006-12-01 13:31:26',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 3,
							'apple_id' => 2,
							'color' => 'blue green',
							'name' => 'green blue',
							'created' => '2006-12-25 05:13:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:24',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 4,
							'apple_id' => 2,
							'color' => 'Blue Green',
							'name' => 'Test Name',
							'created' => '2006-12-25 05:23:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:36',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => 3,
					'apple_id' => 4,
					'name' => 'sample3'
			)),
			array(
				'Apple' => array(
					'id' => 5,
					'apple_id' => 5,
					'color' => 'Green',
					'name' => 'Blue Green',
					'created' => '2006-12-25 05:24:06',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:16',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 5,
					'apple_id' => 5,
					'color' => 'Green',
					'name' => 'Blue Green',
					'created' => '2006-12-25 05:24:06',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:16',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 5,
						'apple_id' => 5,
						'color' => 'Green',
						'name' => 'Blue Green',
						'created' => '2006-12-25 05:24:06',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:29:16',
						'mytime' => '22:57:17'
					),
					'Sample' => array(
						'id' => 4,
						'apple_id' => 5,
						'name' => 'sample4'
					),
					'Child' => array(
						array(
							'id' => 5,
							'apple_id' => 5,
							'color' => 'Green',
							'name' => 'Blue Green',
							'created' => '2006-12-25 05:24:06',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:29:16',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => 4,
					'apple_id' => 5,
					'name' => 'sample4'
			)),
			array(
				'Apple' => array(
					'id' => 6,
					'apple_id' => 4,
					'color' => 'My new appleOrange',
					'name' => 'My new apple',
					'created' => '2006-12-25 05:29:39',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:39',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 4,
					'apple_id' => 2,
					'color' => 'Blue Green',
					'name' => 'Test Name',
					'created' => '2006-12-25 05:23:36',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:23:36',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 2,
						'apple_id' => 1,
						'color' => 'Bright Red 1',
						'name' => 'Bright Red Apple',
						'created' => '2006-11-22 10:43:13',
						'date' => '2014-01-01',
						'modified' => '2006-11-30 18:38:10',
						'mytime' => '22:57:17'
					),
					'Sample' => array(
						'id' => 3,
						'apple_id' => 4,
						'name' => 'sample3'
					),
					'Child' => array(
						array(
							'id' => 6,
							'apple_id' => 4,
							'color' => 'My new appleOrange',
							'name' => 'My new apple',
							'created' => '2006-12-25 05:29:39',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:29:39',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => '',
					'apple_id' => '',
					'name' => ''
			)),
			array(
				'Apple' => array(
					'id' => 7,
					'apple_id' => 6,
					'color' => 'Some wierd color',
					'name' => 'Some odd color',
					'created' => '2006-12-25 05:34:21',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:34:21',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 6,
					'apple_id' => 4,
					'color' => 'My new appleOrange',
					'name' => 'My new apple',
					'created' => '2006-12-25 05:29:39',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:39',
					'mytime' => '22:57:17',
					'Parent' => array(
						'id' => 4,
						'apple_id' => 2,
						'color' => 'Blue Green',
						'name' => 'Test Name',
						'created' => '2006-12-25 05:23:36',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:23:36',
						'mytime' => '22:57:17'
					),
					'Sample' => array(),
					'Child' => array(
						array(
							'id' => 7,
							'apple_id' => 6,
							'color' => 'Some wierd color',
							'name' => 'Some odd color',
							'created' => '2006-12-25 05:34:21',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:34:21',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => '',
					'apple_id' => '',
					'name' => ''
		)));
		$this->assertEquals($expected, $result);

		$result = $TestModel->Parent->unbindModel(array('belongsTo' => array('Parent')));
		$this->assertTrue($result);

		$result = $TestModel->unbindModel(array('hasMany' => array('Child')));
		$this->assertTrue($result);

		$result = $TestModel->find('all');
		$expected = array(
			array(
				'Apple' => array(
					'id' => 1,
					'apple_id' => 2,
					'color' => 'Red 1',
					'name' => 'Red Apple 1',
					'created' => '2006-11-22 10:38:58',
					'date' => '1951-01-04',
					'modified' => '2006-12-01 13:31:26',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 2,
					'apple_id' => 1,
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17',
					'Sample' => array(
						'id' => 2,
						'apple_id' => 2,
						'name' => 'sample2'
					),
					'Child' => array(
						array(
							'id' => 1,
							'apple_id' => 2,
							'color' => 'Red 1',
							'name' => 'Red Apple 1',
							'created' => '2006-11-22 10:38:58',
							'date' => '1951-01-04',
							'modified' => '2006-12-01 13:31:26',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 3,
							'apple_id' => 2,
							'color' => 'blue green',
							'name' => 'green blue',
							'created' => '2006-12-25 05:13:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:24',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 4,
							'apple_id' => 2,
							'color' => 'Blue Green',
							'name' => 'Test Name',
							'created' => '2006-12-25 05:23:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:36',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => '',
					'apple_id' => '',
					'name' => ''
			)),
			array(
				'Apple' => array(
					'id' => 2,
					'apple_id' => 1,
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 1,
					'apple_id' => 2,
					'color' => 'Red 1',
					'name' => 'Red Apple 1',
					'created' => '2006-11-22 10:38:58',
					'date' => '1951-01-04',
					'modified' => '2006-12-01 13:31:26',
					'mytime' => '22:57:17',
					'Sample' => array(),
						'Child' => array(
							array(
								'id' => 2,
								'apple_id' => 1,
								'color' => 'Bright Red 1',
								'name' => 'Bright Red Apple',
								'created' => '2006-11-22 10:43:13',
								'date' => '2014-01-01',
								'modified' => '2006-11-30 18:38:10',
								'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => 2,
					'apple_id' => 2,
					'name' => 'sample2',
					'Apple' => array(
						'id' => 2,
						'apple_id' => 1,
						'color' => 'Bright Red 1',
						'name' => 'Bright Red Apple',
						'created' => '2006-11-22 10:43:13',
						'date' => '2014-01-01',
						'modified' => '2006-11-30 18:38:10',
						'mytime' => '22:57:17'
			))),
			array(
				'Apple' => array(
					'id' => 3,
					'apple_id' => 2,
					'color' => 'blue green',
					'name' => 'green blue',
					'created' => '2006-12-25 05:13:36',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:23:24',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 2,
					'apple_id' => 1,
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17',
					'Sample' => array(
						'id' => 2,
						'apple_id' => 2,
						'name' => 'sample2'
					),
					'Child' => array(
						array(
							'id' => 1,
							'apple_id' => 2,
							'color' => 'Red 1',
							'name' => 'Red Apple 1',
							'created' => '2006-11-22 10:38:58',
							'date' => '1951-01-04',
							'modified' => '2006-12-01 13:31:26',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 3,
							'apple_id' => 2,
							'color' => 'blue green',
							'name' => 'green blue',
							'created' => '2006-12-25 05:13:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:24',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 4,
							'apple_id' => 2,
							'color' => 'Blue Green',
							'name' => 'Test Name',
							'created' => '2006-12-25 05:23:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:36',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => 1,
					'apple_id' => 3,
					'name' => 'sample1',
					'Apple' => array(
						'id' => 3,
						'apple_id' => 2,
						'color' => 'blue green',
						'name' => 'green blue',
						'created' => '2006-12-25 05:13:36',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:23:24',
						'mytime' => '22:57:17'
			))),
			array(
				'Apple' => array(
					'id' => 4,
					'apple_id' => 2,
					'color' => 'Blue Green',
					'name' => 'Test Name',
					'created' => '2006-12-25 05:23:36',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:23:36',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 2,
					'apple_id' => 1,
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17',
					'Sample' => array(
						'id' => 2,
						'apple_id' => 2,
						'name' => 'sample2'
					),
					'Child' => array(
						array(
							'id' => 1,
							'apple_id' => 2,
							'color' => 'Red 1',
							'name' => 'Red Apple 1',
							'created' => '2006-11-22 10:38:58',
							'date' => '1951-01-04',
							'modified' => '2006-12-01 13:31:26',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 3,
							'apple_id' => 2,
							'color' => 'blue green',
							'name' => 'green blue',
							'created' => '2006-12-25 05:13:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:24',
							'mytime' => '22:57:17'
						),
						array(
							'id' => 4,
							'apple_id' => 2,
							'color' => 'Blue Green',
							'name' => 'Test Name',
							'created' => '2006-12-25 05:23:36',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:23:36',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => 3,
					'apple_id' => 4,
					'name' => 'sample3',
					'Apple' => array(
						'id' => 4,
						'apple_id' => 2,
						'color' => 'Blue Green',
						'name' => 'Test Name',
						'created' => '2006-12-25 05:23:36',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:23:36',
						'mytime' => '22:57:17'
			))),
			array(
				'Apple' => array(
					'id' => 5,
					'apple_id' => 5,
					'color' => 'Green',
					'name' => 'Blue Green',
					'created' => '2006-12-25 05:24:06',
					'date' => '2006-12-25',
					'modified' =>
					'2006-12-25 05:29:16',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 5,
					'apple_id' => 5,
					'color' => 'Green',
					'name' => 'Blue Green',
					'created' => '2006-12-25 05:24:06',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:16',
					'mytime' => '22:57:17',
					'Sample' => array(
						'id' => 4,
						'apple_id' => 5,
						'name' => 'sample4'
					),
					'Child' => array(
						array(
							'id' => 5,
							'apple_id' => 5,
							'color' => 'Green',
							'name' => 'Blue Green',
							'created' => '2006-12-25 05:24:06',
							'date' => '2006-12-25',
							'modified' => '2006-12-25 05:29:16',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => 4,
					'apple_id' => 5,
					'name' => 'sample4',
					'Apple' => array(
						'id' => 5,
						'apple_id' => 5,
						'color' => 'Green',
						'name' => 'Blue Green',
						'created' => '2006-12-25 05:24:06',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:29:16',
						'mytime' => '22:57:17'
			))),
			array(
				'Apple' => array(
					'id' => 6,
					'apple_id' => 4,
					'color' => 'My new appleOrange',
					'name' => 'My new apple',
					'created' => '2006-12-25 05:29:39',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:39',
					'mytime' => '22:57:17'),
					'Parent' => array(
						'id' => 4,
						'apple_id' => 2,
						'color' => 'Blue Green',
						'name' => 'Test Name',
						'created' => '2006-12-25 05:23:36',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:23:36',
						'mytime' => '22:57:17',
						'Sample' => array(
							'id' => 3,
							'apple_id' => 4,
							'name' => 'sample3'
						),
						'Child' => array(
							array(
								'id' => 6,
								'apple_id' => 4,
								'color' => 'My new appleOrange',
								'name' => 'My new apple',
								'created' => '2006-12-25 05:29:39',
								'date' => '2006-12-25',
								'modified' => '2006-12-25 05:29:39',
								'mytime' => '22:57:17'
					))),
					'Sample' => array(
						'id' => '',
						'apple_id' => '',
						'name' => ''
			)),
			array(
				'Apple' => array(
					'id' => 7,
					'apple_id' => 6,
					'color' => 'Some wierd color',
					'name' => 'Some odd color',
					'created' => '2006-12-25 05:34:21',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:34:21',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => 6,
					'apple_id' => 4,
					'color' => 'My new appleOrange',
					'name' => 'My new apple',
					'created' => '2006-12-25 05:29:39',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:39',
					'mytime' => '22:57:17',
					'Sample' => array(),
					'Child' => array(
						array(
							'id' => 7,
							'apple_id' => 6,
							'color' => 'Some wierd color',
							'name' => 'Some odd color',
							'created' => '2006-12-25 05:34:21',
							'date' => '2006-12-25', 'modified' =>
							'2006-12-25 05:34:21',
							'mytime' => '22:57:17'
				))),
				'Sample' => array(
					'id' => '',
					'apple_id' => '',
					'name' => ''
		)));
		$this->assertEquals($expected, $result);
	}

/**
 * testSelfAssociationAfterFind method
 *
 * @return void
 */
	public function testSelfAssociationAfterFind() {
		$this->loadFixtures('Apple', 'Sample');
		$afterFindModel = new NodeAfterFind();
		$afterFindModel->recursive = 3;
		$afterFindData = $afterFindModel->find('all');

		$duplicateModel = new NodeAfterFind();
		$duplicateModel->recursive = 3;

		$noAfterFindModel = new NodeNoAfterFind();
		$noAfterFindModel->recursive = 3;
		$noAfterFindData = $noAfterFindModel->find('all');

		$this->assertFalse($afterFindModel == $noAfterFindModel);
		$this->assertEquals($afterFindData, $noAfterFindData);
	}

/**
 * Test that afterFind can completely unset data.
 *
 * @return void
 */
	public function testAfterFindUnset() {
		$this->loadFixtures('Article', 'Comment', 'User');
		$model = new CustomArticle();
		$model->bindModel(array(
			'hasMany' => array(
				'ModifiedComment' => array(
					'className' => 'ModifiedComment',
					'foreignKey' => 'article_id',
				)
			)
		));
		$model->ModifiedComment->remove = true;
		$result = $model->find('all');
		$this->assertTrue(
			empty($result[0]['ModifiedComment']),
			'Zeroith row should be removed by afterFind'
		);
	}

/**
 * testFindThreadedNoParent method
 *
 * @return void
 */
	public function testFindThreadedNoParent() {
		$this->loadFixtures('Apple', 'Sample');
		$Apple = new Apple();
		$result = $Apple->find('threaded');
		$result = Hash::extract($result, '{n}.children');
		$expected = array(array(), array(), array(), array(), array(), array(), array());
		$this->assertEquals($expected, $result);
	}

/**
 * testFindThreaded method
 *
 * @return void
 */
	public function testFindThreaded() {
		$this->loadFixtures('Person');
		$Model = new Person();
		$Model->recursive = -1;
		$result = $Model->find('threaded');
		$result = Hash::extract($result, '{n}.children');
		$expected = array(array(), array(), array(), array(), array(), array(), array());
		$this->assertEquals($expected, $result);

		$result = $Model->find('threaded', array('parent' => 'mother_id'));
		$expected = array(
			array(
				'Person' => array(
					'id' => '4',
					'name' => 'mother - grand mother',
					'mother_id' => '0',
					'father_id' => '0'
				),
				'children' => array(
					array(
						'Person' => array(
							'id' => '2',
							'name' => 'mother',
							'mother_id' => '4',
							'father_id' => '5'
						),
						'children' => array(
							array(
								'Person' => array(
									'id' => '1',
									'name' => 'person',
									'mother_id' => '2',
									'father_id' => '3'
								),
								'children' => array()
							)
						)
					)
				)
			),
			array(
				'Person' => array(
					'id' => '5',
					'name' => 'mother - grand father',
					'mother_id' => '0',
					'father_id' => '0'
				),
				'children' => array()
			),
			array(
				'Person' => array(
					'id' => '6',
					'name' => 'father - grand mother',
					'mother_id' => '0',
					'father_id' => '0'
				),
				'children' => array(
					array(
						'Person' => array(
							'id' => '3',
							'name' => 'father',
							'mother_id' => '6',
							'father_id' => '7'
						),
						'children' => array()
					)
				)
			),
			array(
				'Person' => array(
					'id' => '7',
					'name' => 'father - grand father',
					'mother_id' => '0',
					'father_id' => '0'
				),
				'children' => array()
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testFindAllThreaded method
 *
 * @return void
 */
	public function testFindAllThreaded() {
		$this->loadFixtures('Category');
		$TestModel = new Category();

		$result = $TestModel->find('threaded');
		$expected = array(
			array(
				'Category' => array(
					'id' => '1',
					'parent_id' => '0',
					'name' => 'Category 1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => '2',
							'parent_id' => '1',
							'name' => 'Category 1.1',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31'
						),
						'children' => array(
							array('Category' => array(
								'id' => '7',
								'parent_id' => '2',
								'name' => 'Category 1.1.1',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31'),
								'children' => array()),
							array('Category' => array(
								'id' => '8',
								'parent_id' => '2',
								'name' => 'Category 1.1.2',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31'),
								'children' => array()))
					),
					array(
						'Category' => array(
							'id' => '3',
							'parent_id' => '1',
							'name' => 'Category 1.2',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31'
						),
						'children' => array()
					)
				)
			),
			array(
				'Category' => array(
					'id' => '4',
					'parent_id' => '0',
					'name' => 'Category 2',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'children' => array()
			),
			array(
				'Category' => array(
					'id' => '5',
					'parent_id' => '0',
					'name' => 'Category 3',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => '6',
							'parent_id' => '5',
							'name' => 'Category 3.1',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31'
						),
						'children' => array()
					)
				)
			)
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('threaded', array(
			'conditions' => array('Category.name LIKE' => 'Category 1%')
		));

		$expected = array(
			array(
				'Category' => array(
					'id' => '1',
					'parent_id' => '0',
					'name' => 'Category 1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => '2',
							'parent_id' => '1',
							'name' => 'Category 1.1',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31'
						),
						'children' => array(
							array('Category' => array(
								'id' => '7',
								'parent_id' => '2',
								'name' => 'Category 1.1.1',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31'),
								'children' => array()),
							array('Category' => array(
								'id' => '8',
								'parent_id' => '2',
								'name' => 'Category 1.1.2',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31'),
								'children' => array()))
					),
					array(
						'Category' => array(
							'id' => '3',
							'parent_id' => '1',
							'name' => 'Category 1.2',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31'
						),
						'children' => array()
					)
				)
			)
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('threaded', array(
			'fields' => 'id, parent_id, name'
		));

		$expected = array(
			array(
				'Category' => array(
					'id' => '1',
					'parent_id' => '0',
					'name' => 'Category 1'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => '2',
							'parent_id' => '1',
							'name' => 'Category 1.1'
						),
						'children' => array(
							array('Category' => array(
								'id' => '7',
								'parent_id' => '2',
								'name' => 'Category 1.1.1'),
								'children' => array()),
							array('Category' => array(
								'id' => '8',
								'parent_id' => '2',
								'name' => 'Category 1.1.2'),
								'children' => array()))
					),
					array(
						'Category' => array(
							'id' => '3',
							'parent_id' => '1',
							'name' => 'Category 1.2'
						),
						'children' => array()
					)
				)
			),
			array(
				'Category' => array(
					'id' => '4',
					'parent_id' => '0',
					'name' => 'Category 2'
				),
				'children' => array()
			),
			array(
				'Category' => array(
					'id' => '5',
					'parent_id' => '0',
					'name' => 'Category 3'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => '6',
							'parent_id' => '5',
							'name' => 'Category 3.1'
						),
						'children' => array()
					)
				)
			)
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('threaded', array('order' => 'id DESC'));

		$expected = array(
			array(
				'Category' => array(
					'id' => 5,
					'parent_id' => 0,
					'name' => 'Category 3',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => 6,
							'parent_id' => 5,
							'name' => 'Category 3.1',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31'
						),
						'children' => array()
					)
				)
			),
			array(
				'Category' => array(
					'id' => 4,
					'parent_id' => 0,
					'name' => 'Category 2',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'children' => array()
			),
			array(
				'Category' => array(
					'id' => 1,
					'parent_id' => 0,
					'name' => 'Category 1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => 3,
							'parent_id' => 1,
							'name' => 'Category 1.2',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31'
						),
						'children' => array()
					),
					array(
						'Category' => array(
							'id' => 2,
							'parent_id' => 1,
							'name' => 'Category 1.1',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31'
						),
						'children' => array(
							array('Category' => array(
								'id' => '8',
								'parent_id' => '2',
								'name' => 'Category 1.1.2',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31'),
								'children' => array()),
							array('Category' => array(
								'id' => '7',
								'parent_id' => '2',
								'name' => 'Category 1.1.1',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31'),
								'children' => array()))
					)
				)
			)
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('threaded', array(
			'conditions' => array('Category.name LIKE' => 'Category 3%')
		));
		$expected = array(
			array(
				'Category' => array(
					'id' => '5',
					'parent_id' => '0',
					'name' => 'Category 3',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => '6',
							'parent_id' => '5',
							'name' => 'Category 3.1',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31'
						),
						'children' => array()
					)
				)
			)
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('threaded', array(
			'conditions' => array('Category.name LIKE' => 'Category 1.1%')
		));
		$expected = array(
				array('Category' =>
					array(
						'id' => '2',
						'parent_id' => '1',
						'name' => 'Category 1.1',
						'created' => '2007-03-18 15:30:23',
						'updated' => '2007-03-18 15:32:31'),
						'children' => array(
							array('Category' => array(
								'id' => '7',
								'parent_id' => '2',
								'name' => 'Category 1.1.1',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31'),
								'children' => array()),
							array('Category' => array(
								'id' => '8',
								'parent_id' => '2',
								'name' => 'Category 1.1.2',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31'),
								'children' => array()))));
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('threaded', array(
			'fields' => 'id, parent_id, name',
			'conditions' => array('Category.id !=' => 2)
		));
		$expected = array(
			array(
				'Category' => array(
					'id' => '1',
					'parent_id' => '0',
					'name' => 'Category 1'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => '3',
							'parent_id' => '1',
							'name' => 'Category 1.2'
						),
						'children' => array()
					)
				)
			),
			array(
				'Category' => array(
					'id' => '4',
					'parent_id' => '0',
					'name' => 'Category 2'
				),
				'children' => array()
			),
			array(
				'Category' => array(
					'id' => '5',
					'parent_id' => '0',
					'name' => 'Category 3'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => '6',
							'parent_id' => '5',
							'name' => 'Category 3.1'
						),
						'children' => array()
					)
				)
			)
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all', array(
			'fields' => 'id, name, parent_id',
			'conditions' => array('Category.id !=' => 1)
		));
		$expected = array(
			array('Category' => array(
				'id' => '2',
				'name' => 'Category 1.1',
				'parent_id' => '1'
			)),
			array('Category' => array(
				'id' => '3',
				'name' => 'Category 1.2',
				'parent_id' => '1'
			)),
			array('Category' => array(
				'id' => '4',
				'name' => 'Category 2',
				'parent_id' => '0'
			)),
			array('Category' => array(
				'id' => '5',
				'name' => 'Category 3',
				'parent_id' => '0'
			)),
			array('Category' => array(
				'id' => '6',
				'name' => 'Category 3.1',
				'parent_id' => '5'
			)),
			array('Category' => array(
				'id' => '7',
				'name' => 'Category 1.1.1',
				'parent_id' => '2'
			)),
			array('Category' => array(
				'id' => '8',
				'name' => 'Category 1.1.2',
				'parent_id' => '2'
		)));
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('threaded', array(
			'fields' => 'id, parent_id, name',
			'conditions' => array('Category.id !=' => 1)
		));
		$expected = array(
			array(
				'Category' => array(
					'id' => '2',
					'parent_id' => '1',
					'name' => 'Category 1.1'
				),
				'children' => array(
					array('Category' => array(
						'id' => '7',
						'parent_id' => '2',
						'name' => 'Category 1.1.1'),
						'children' => array()),
					array('Category' => array(
						'id' => '8',
						'parent_id' => '2',
						'name' => 'Category 1.1.2'),
						'children' => array()))
			),
			array(
				'Category' => array(
					'id' => '3',
					'parent_id' => '1',
					'name' => 'Category 1.2'
				),
				'children' => array()
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test find('neighbors')
 *
 * @return void
 */
	public function testFindNeighbors() {
		$this->loadFixtures('User', 'Article', 'Comment', 'Tag', 'ArticlesTag', 'Attachment');
		$TestModel = new Article();

		$TestModel->id = 1;
		$result = $TestModel->find('neighbors', array('fields' => array('id')));

		$this->assertNull($result['prev']);
		$this->assertEquals(array('id' => 2), $result['next']['Article']);
		$this->assertEquals(2, count($result['next']['Comment']));
		$this->assertEquals(2, count($result['next']['Tag']));

		$TestModel->id = 2;
		$TestModel->recursive = 0;
		$result = $TestModel->find('neighbors', array(
			'fields' => array('id')
		));

		$expected = array(
			'prev' => array(
				'Article' => array(
					'id' => 1
			)),
			'next' => array(
				'Article' => array(
					'id' => 3
		)));
		$this->assertEquals($expected, $result);

		$TestModel->id = 3;
		$TestModel->recursive = 1;
		$result = $TestModel->find('neighbors', array('fields' => array('id')));

		$this->assertNull($result['next']);
		$this->assertEquals(array('id' => 2), $result['prev']['Article']);
		$this->assertEquals(2, count($result['prev']['Comment']));
		$this->assertEquals(2, count($result['prev']['Tag']));

		$TestModel->id = 1;
		$result = $TestModel->find('neighbors', array('recursive' => -1));
		$expected = array(
			'prev' => null,
			'next' => array(
				'Article' => array(
					'id' => 2,
					'user_id' => 3,
					'title' => 'Second Article',
					'body' => 'Second Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				)
			)
		);
		$this->assertEquals($expected, $result);

		$TestModel->id = 2;
		$result = $TestModel->find('neighbors', array('recursive' => -1));
		$expected = array(
			'prev' => array(
				'Article' => array(
					'id' => 1,
					'user_id' => 1,
					'title' => 'First Article',
					'body' => 'First Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				)
			),
			'next' => array(
				'Article' => array(
					'id' => 3,
					'user_id' => 1,
					'title' => 'Third Article',
					'body' => 'Third Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
				)
			)
		);
		$this->assertEquals($expected, $result);

		$TestModel->id = 3;
		$result = $TestModel->find('neighbors', array('recursive' => -1));
		$expected = array(
			'prev' => array(
				'Article' => array(
					'id' => 2,
					'user_id' => 3,
					'title' => 'Second Article',
					'body' => 'Second Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				)
			),
			'next' => null
		);
		$this->assertEquals($expected, $result);

		$TestModel->recursive = 0;
		$TestModel->id = 1;
		$one = $TestModel->read();
		$TestModel->id = 2;
		$two = $TestModel->read();
		$TestModel->id = 3;
		$three = $TestModel->read();

		$TestModel->id = 1;
		$result = $TestModel->find('neighbors');
		$expected = array('prev' => null, 'next' => $two);
		$this->assertEquals($expected, $result);

		$TestModel->id = 2;
		$result = $TestModel->find('neighbors');
		$expected = array('prev' => $one, 'next' => $three);
		$this->assertEquals($expected, $result);

		$TestModel->id = 3;
		$result = $TestModel->find('neighbors');
		$expected = array('prev' => $two, 'next' => null);
		$this->assertEquals($expected, $result);

		$TestModel->recursive = 2;
		$TestModel->id = 1;
		$one = $TestModel->read();
		$TestModel->id = 2;
		$two = $TestModel->read();
		$TestModel->id = 3;
		$three = $TestModel->read();

		$TestModel->id = 1;
		$result = $TestModel->find('neighbors', array('recursive' => 2));
		$expected = array('prev' => null, 'next' => $two);
		$this->assertEquals($expected, $result);

		$TestModel->id = 2;
		$result = $TestModel->find('neighbors', array('recursive' => 2));
		$expected = array('prev' => $one, 'next' => $three);
		$this->assertEquals($expected, $result);

		$TestModel->id = 3;
		$result = $TestModel->find('neighbors', array('recursive' => 2));
		$expected = array('prev' => $two, 'next' => null);
		$this->assertEquals($expected, $result);
	}

/**
 * Test find(neighbors) with missing fields so no neighbors are found.
 *
 * @return void
 */
	public function testFindNeighborsNoPrev() {
		$this->loadFixtures('User', 'Article', 'Comment', 'Tag', 'ArticlesTag', 'Attachment');
		$Article = new Article();

		$result = $Article->find('neighbors', array(
			'field' => 'Article.title',
			'value' => 'Second Article',
			'fields' => array('id'),
			'conditions' => array(
				'Article.title LIKE' => '%Article%'
			),
			'recursive' => 0,
		));
		$expected = array(
			'prev' => null,
			'next' => null
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testFindCombinedRelations method
 *
 * @return void
 */
	public function testFindCombinedRelations() {
		$this->skipIf($this->db instanceof Sqlserver, 'The test of testRecursiveUnbind test is not compatible with SQL Server, because it check for time columns.');

		$this->loadFixtures('Apple', 'Sample');
		$TestModel = new Apple();

		$result = $TestModel->find('all');

		$expected = array(
			array(
				'Apple' => array(
					'id' => '1',
					'apple_id' => '2',
					'color' => 'Red 1',
					'name' => 'Red Apple 1',
					'created' => '2006-11-22 10:38:58',
					'date' => '1951-01-04',
					'modified' => '2006-12-01 13:31:26',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => '2',
					'apple_id' => '1',
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17'
				),
				'Sample' => array(
					'id' => null,
					'apple_id' => null,
					'name' => null
				),
				'Child' => array(
					array(
						'id' => '2',
						'apple_id' => '1',
						'color' => 'Bright Red 1',
						'name' => 'Bright Red Apple',
						'created' => '2006-11-22 10:43:13',
						'date' => '2014-01-01',
						'modified' => '2006-11-30 18:38:10',
						'mytime' => '22:57:17'
			))),
			array(
				'Apple' => array(
					'id' => '2',
					'apple_id' => '1',
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => '1',
					'apple_id' => '2',
					'color' => 'Red 1',
					'name' => 'Red Apple 1',
					'created' => '2006-11-22 10:38:58',
					'date' => '1951-01-04',
					'modified' => '2006-12-01 13:31:26',
					'mytime' => '22:57:17'
				),
				'Sample' => array(
					'id' => '2',
					'apple_id' => '2',
					'name' => 'sample2'
				),
				'Child' => array(
					array(
						'id' => '1',
						'apple_id' => '2',
						'color' => 'Red 1',
						'name' => 'Red Apple 1',
						'created' => '2006-11-22 10:38:58',
						'date' => '1951-01-04',
						'modified' => '2006-12-01 13:31:26',
						'mytime' => '22:57:17'
					),
					array(
						'id' => '3',
						'apple_id' => '2',
						'color' => 'blue green',
						'name' => 'green blue',
						'created' => '2006-12-25 05:13:36',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:23:24',
						'mytime' => '22:57:17'
					),
					array(
						'id' => '4',
						'apple_id' => '2',
						'color' => 'Blue Green',
						'name' => 'Test Name',
						'created' => '2006-12-25 05:23:36',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:23:36',
						'mytime' => '22:57:17'
			))),
			array(
				'Apple' => array(
					'id' => '3',
					'apple_id' => '2',
					'color' => 'blue green',
					'name' => 'green blue',
					'created' => '2006-12-25 05:13:36',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:23:24',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => '2',
					'apple_id' => '1',
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17'
				),
				'Sample' => array(
					'id' => '1',
					'apple_id' => '3',
					'name' => 'sample1'
				),
				'Child' => array()
			),
			array(
				'Apple' => array(
					'id' => '4',
					'apple_id' => '2',
					'color' => 'Blue Green',
					'name' => 'Test Name',
					'created' => '2006-12-25 05:23:36',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:23:36',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => '2',
					'apple_id' => '1',
					'color' => 'Bright Red 1',
					'name' => 'Bright Red Apple',
					'created' => '2006-11-22 10:43:13',
					'date' => '2014-01-01',
					'modified' => '2006-11-30 18:38:10',
					'mytime' => '22:57:17'
				),
				'Sample' => array(
					'id' => '3',
					'apple_id' => '4',
					'name' => 'sample3'
				),
				'Child' => array(
					array(
						'id' => '6',
						'apple_id' => '4',
						'color' => 'My new appleOrange',
						'name' => 'My new apple',
						'created' => '2006-12-25 05:29:39',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:29:39',
						'mytime' => '22:57:17'
			))),
			array(
				'Apple' => array(
					'id' => '5',
					'apple_id' => '5',
					'color' => 'Green',
					'name' => 'Blue Green',
					'created' => '2006-12-25 05:24:06',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:16',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => '5',
					'apple_id' => '5',
					'color' => 'Green',
					'name' => 'Blue Green',
					'created' => '2006-12-25 05:24:06',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:16',
					'mytime' => '22:57:17'
				),
				'Sample' => array(
					'id' => '4',
					'apple_id' => '5',
					'name' => 'sample4'
				),
				'Child' => array(
					array(
						'id' => '5',
						'apple_id' => '5',
						'color' => 'Green',
						'name' => 'Blue Green',
						'created' => '2006-12-25 05:24:06',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:29:16',
						'mytime' => '22:57:17'
			))),
			array(
				'Apple' => array(
					'id' => '6',
					'apple_id' => '4',
					'color' => 'My new appleOrange',
					'name' => 'My new apple',
					'created' => '2006-12-25 05:29:39',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:39',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => '4',
					'apple_id' => '2',
					'color' => 'Blue Green',
					'name' => 'Test Name',
					'created' => '2006-12-25 05:23:36',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:23:36',
					'mytime' => '22:57:17'
				),
				'Sample' => array(
					'id' => null,
					'apple_id' => null,
					'name' => null
				),
				'Child' => array(
					array(
						'id' => '7',
						'apple_id' => '6',
						'color' => 'Some wierd color',
						'name' => 'Some odd color',
						'created' => '2006-12-25 05:34:21',
						'date' => '2006-12-25',
						'modified' => '2006-12-25 05:34:21',
						'mytime' => '22:57:17'
			))),
			array(
				'Apple' => array(
					'id' => '7',
					'apple_id' => '6',
					'color' => 'Some wierd color',
					'name' => 'Some odd color',
					'created' => '2006-12-25 05:34:21',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:34:21',
					'mytime' => '22:57:17'
				),
				'Parent' => array(
					'id' => '6',
					'apple_id' => '4',
					'color' => 'My new appleOrange',
					'name' => 'My new apple',
					'created' => '2006-12-25 05:29:39',
					'date' => '2006-12-25',
					'modified' => '2006-12-25 05:29:39',
					'mytime' => '22:57:17'
				),
				'Sample' => array(
					'id' => null,
					'apple_id' => null,
					'name' => null
				),
				'Child' => array()
		));
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveEmpty method
 *
 * @return void
 */
	public function testSaveEmpty() {
		$this->loadFixtures('Thread');
		$TestModel = new Thread();
		$data = array();
		$expected = $TestModel->save($data);
		$this->assertFalse($expected);
	}

/**
 * testFindAllWithConditionInChildQuery
 *
 * @return void
 */
	public function testFindAllWithConditionInChildQuery() {
		$this->loadFixtures('Basket', 'FilmFile');

		$TestModel = new Basket();
		$recursive = 3;
		$result = $TestModel->find('all', compact('recursive'));

		$expected = array(
			array(
				'Basket' => array(
					'id' => 1,
					'type' => 'nonfile',
					'name' => 'basket1',
					'object_id' => 1,
					'user_id' => 1,
				),
				'FilmFile' => array(
					'id' => '',
					'name' => '',
				)
			),
			array(
				'Basket' => array(
					'id' => 2,
					'type' => 'file',
					'name' => 'basket2',
					'object_id' => 2,
					'user_id' => 1,
				),
				'FilmFile' => array(
					'id' => 2,
					'name' => 'two',
				)
			),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testFindAllWithConditionsHavingMixedDataTypes method
 *
 * @return void
 */
	public function testFindAllWithConditionsHavingMixedDataTypes() {
		$this->loadFixtures('Article', 'User', 'Tag', 'ArticlesTag');
		$TestModel = new Article();
		$expected = array(
			array(
				'Article' => array(
					'id' => 1,
					'user_id' => 1,
					'title' => 'First Article',
					'body' => 'First Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				)
			),
			array(
				'Article' => array(
					'id' => 2,
					'user_id' => 3,
					'title' => 'Second Article',
					'body' => 'Second Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				)
			)
		);
		$conditions = array('id' => array('1', 2));
		$recursive = -1;
		$order = 'Article.id ASC';
		$result = $TestModel->find('all', compact('conditions', 'recursive', 'order'));
		$this->assertEquals($expected, $result);

		$this->skipIf($this->db instanceof Postgres, 'The rest of testFindAllWithConditionsHavingMixedDataTypes test is not compatible with Postgres.');

		$conditions = array('id' => array('1', 2, '3.0'));
		$order = 'Article.id ASC';
		$result = $TestModel->find('all', compact('recursive', 'conditions', 'order'));
		$expected = array(
			array(
				'Article' => array(
					'id' => 1,
					'user_id' => 1,
					'title' => 'First Article',
					'body' => 'First Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				)
			),
			array(
				'Article' => array(
					'id' => 2,
					'user_id' => 3,
					'title' => 'Second Article',
					'body' => 'Second Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				)
			),
			array(
				'Article' => array(
					'id' => 3,
					'user_id' => 1,
					'title' => 'Third Article',
					'body' => 'Third Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
				)
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testBindUnbind method
 *
 * @return void
 */
	public function testBindUnbind() {
		$this->loadFixtures(
			'User',
			'Comment',
			'FeatureSet',
			'DeviceType',
			'DeviceTypeCategory',
			'ExteriorTypeCategory',
			'Device',
			'Document',
			'DocumentDirectory'
		);
		$TestModel = new User();

		$result = $TestModel->hasMany;
		$expected = array();
		$this->assertEquals($expected, $result);

		$result = $TestModel->bindModel(array('hasMany' => array('Comment')));
		$this->assertTrue($result);

		$result = $TestModel->find('all', array(
			'fields' => 'User.id, User.user',
			'order' => array('User.id' => 'ASC'),
		));
		$expected = array(
			array(
				'User' => array(
					'id' => '1',
					'user' => 'mariano'
				),
				'Comment' => array(
					array(
						'id' => '3',
						'article_id' => '1',
						'user_id' => '1',
						'comment' => 'Third Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:49:23',
						'updated' => '2007-03-18 10:51:31'
					),
					array(
						'id' => '4',
						'article_id' => '1',
						'user_id' => '1',
						'comment' => 'Fourth Comment for First Article',
						'published' => 'N',
						'created' => '2007-03-18 10:51:23',
						'updated' => '2007-03-18 10:53:31'
					),
					array(
						'id' => '5',
						'article_id' => '2',
						'user_id' => '1',
						'comment' => 'First Comment for Second Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:53:23',
						'updated' => '2007-03-18 10:55:31'
			))),
			array(
				'User' => array(
					'id' => '2',
					'user' => 'nate'
				),
				'Comment' => array(
					array(
						'id' => '1',
						'article_id' => '1',
						'user_id' => '2',
						'comment' => 'First Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:45:23',
						'updated' => '2007-03-18 10:47:31'
					),
					array(
						'id' => '6',
						'article_id' => '2',
						'user_id' => '2',
						'comment' => 'Second Comment for Second Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:55:23',
						'updated' => '2007-03-18 10:57:31'
			))),
			array(
				'User' => array(
					'id' => '3',
					'user' => 'larry'
				),
				'Comment' => array()
			),
			array(
				'User' => array(
					'id' => '4',
					'user' => 'garrett'
				),
				'Comment' => array(
					array(
						'id' => '2',
						'article_id' => '1',
						'user_id' => '4',
						'comment' => 'Second Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:47:23',
						'updated' => '2007-03-18 10:49:31'
		))));

		$this->assertEquals($expected, $result);

		$TestModel->resetAssociations();
		$result = $TestModel->hasMany;
		$this->assertSame(array(), $result);

		$result = $TestModel->bindModel(array('hasMany' => array('Comment')), false);
		$this->assertTrue($result);

		$result = $TestModel->find('all', array(
			'fields' => 'User.id, User.user',
			'order' => array('User.id' => 'ASC'),
		));

		$expected = array(
			array(
				'User' => array(
					'id' => '1',
					'user' => 'mariano'
				),
				'Comment' => array(
					array(
						'id' => '3',
						'article_id' => '1',
						'user_id' => '1',
						'comment' => 'Third Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:49:23',
						'updated' => '2007-03-18 10:51:31'
					),
					array(
						'id' => '4',
						'article_id' => '1',
						'user_id' => '1',
						'comment' => 'Fourth Comment for First Article',
						'published' => 'N',
						'created' => '2007-03-18 10:51:23',
						'updated' => '2007-03-18 10:53:31'
					),
					array(
						'id' => '5',
						'article_id' => '2',
						'user_id' => '1',
						'comment' => 'First Comment for Second Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:53:23',
						'updated' => '2007-03-18 10:55:31'
			))),
			array(
				'User' => array(
					'id' => '2',
					'user' => 'nate'
				),
				'Comment' => array(
					array(
						'id' => '1',
						'article_id' => '1',
						'user_id' => '2',
						'comment' => 'First Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:45:23',
						'updated' => '2007-03-18 10:47:31'
					),
					array(
						'id' => '6',
						'article_id' => '2',
						'user_id' => '2',
						'comment' => 'Second Comment for Second Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:55:23',
						'updated' => '2007-03-18 10:57:31'
			))),
			array(
				'User' => array(
					'id' => '3',
					'user' => 'larry'
				),
				'Comment' => array()
			),
			array(
				'User' => array(
					'id' => '4',
					'user' => 'garrett'
				),
				'Comment' => array(
					array(
						'id' => '2',
						'article_id' => '1',
						'user_id' => '4',
						'comment' => 'Second Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:47:23',
						'updated' => '2007-03-18 10:49:31'
		))));

		$this->assertEquals($expected, $result);

		$result = $TestModel->hasMany;
		$expected = array(
			'Comment' => array(
				'className' => 'Comment',
				'foreignKey' => 'user_id',
				'conditions' => null,
				'fields' => null,
				'order' => null,
				'limit' => null,
				'offset' => null,
				'dependent' => null,
				'exclusive' => null,
				'finderQuery' => null,
				'counterQuery' => null
		));
		$this->assertEquals($expected, $result);

		$result = $TestModel->unbindModel(array('hasMany' => array('Comment')));
		$this->assertTrue($result);

		$result = $TestModel->hasMany;
		$expected = array();
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all', array(
			'fields' => 'User.id, User.user',
			'order' => array('User.id' => 'ASC'),
		));
		$expected = array(
			array('User' => array('id' => '1', 'user' => 'mariano')),
			array('User' => array('id' => '2', 'user' => 'nate')),
			array('User' => array('id' => '3', 'user' => 'larry')),
			array('User' => array('id' => '4', 'user' => 'garrett')));
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all', array(
			'fields' => 'User.id, User.user',
			'order' => array('User.id' => 'ASC'),
		));
		$expected = array(
			array(
				'User' => array(
					'id' => '1',
					'user' => 'mariano'
				),
				'Comment' => array(
					array(
						'id' => '3',
						'article_id' => '1',
						'user_id' => '1',
						'comment' => 'Third Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:49:23',
						'updated' => '2007-03-18 10:51:31'
					),
					array(
						'id' => '4',
						'article_id' => '1',
						'user_id' => '1',
						'comment' => 'Fourth Comment for First Article',
						'published' => 'N',
						'created' => '2007-03-18 10:51:23',
						'updated' => '2007-03-18 10:53:31'
					),
					array(
						'id' => '5',
						'article_id' => '2',
						'user_id' => '1',
						'comment' => 'First Comment for Second Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:53:23',
						'updated' => '2007-03-18 10:55:31'
			))),
			array(
				'User' => array(
					'id' => '2',
					'user' => 'nate'
				),
				'Comment' => array(
					array(
						'id' => '1',
						'article_id' => '1',
						'user_id' => '2',
						'comment' => 'First Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:45:23',
						'updated' => '2007-03-18 10:47:31'
					),
					array(
						'id' => '6',
						'article_id' => '2',
						'user_id' => '2',
						'comment' => 'Second Comment for Second Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:55:23',
						'updated' => '2007-03-18 10:57:31'
			))),
			array(
				'User' => array(
					'id' => '3',
					'user' => 'larry'
				),
				'Comment' => array()
			),
			array(
				'User' => array(
					'id' => '4',
					'user' => 'garrett'
				),
				'Comment' => array(
					array(
						'id' => '2',
						'article_id' => '1',
						'user_id' => '4',
						'comment' =>
						'Second Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:47:23',
						'updated' => '2007-03-18 10:49:31'
		))));
		$this->assertEquals($expected, $result);

		$result = $TestModel->unbindModel(array('hasMany' => array('Comment')), false);
		$this->assertTrue($result);

		$result = $TestModel->find('all', array(
			'fields' => 'User.id, User.user',
			'order' => array('User.id' => 'ASC'),
		));
		$expected = array(
			array('User' => array('id' => '1', 'user' => 'mariano')),
			array('User' => array('id' => '2', 'user' => 'nate')),
			array('User' => array('id' => '3', 'user' => 'larry')),
			array('User' => array('id' => '4', 'user' => 'garrett')));
		$this->assertEquals($expected, $result);

		$result = $TestModel->hasMany;
		$expected = array();
		$this->assertEquals($expected, $result);

		$result = $TestModel->bindModel(array('hasMany' => array(
			'Comment' => array('className' => 'Comment', 'conditions' => 'Comment.published = \'Y\'')
		)));
		$this->assertTrue($result);

		$result = $TestModel->find('all', array(
			'fields' => 'User.id, User.user',
			'order' => array('User.id' => 'ASC'),
		));
		$expected = array(
			array(
				'User' => array(
					'id' => '1',
					'user' => 'mariano'
				),
				'Comment' => array(
					array(
						'id' => '3',
						'article_id' => '1',
						'user_id' => '1',
						'comment' => 'Third Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:49:23',
						'updated' => '2007-03-18 10:51:31'
					),
					array(
						'id' => '5',
						'article_id' => '2',
						'user_id' => '1',
						'comment' => 'First Comment for Second Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:53:23',
						'updated' => '2007-03-18 10:55:31'
			))),
			array(
				'User' => array(
					'id' => '2',
					'user' => 'nate'
				),
				'Comment' => array(
					array(
						'id' => '1',
						'article_id' => '1',
						'user_id' => '2',
						'comment' => 'First Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:45:23',
						'updated' => '2007-03-18 10:47:31'
					),
					array(
						'id' => '6',
						'article_id' => '2',
						'user_id' => '2',
						'comment' => 'Second Comment for Second Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:55:23',
						'updated' => '2007-03-18 10:57:31'
			))),
			array(
				'User' => array(
					'id' => '3',
					'user' => 'larry'
				),
				'Comment' => array()
			),
			array(
				'User' => array(
					'id' => '4',
					'user' => 'garrett'
				),
				'Comment' => array(
					array(
						'id' => '2',
						'article_id' => '1',
						'user_id' => '4',
						'comment' => 'Second Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:47:23',
						'updated' => '2007-03-18 10:49:31'
		))));

		$this->assertEquals($expected, $result);

		$TestModel2 = new DeviceType();

		$expected = array(
			'className' => 'FeatureSet',
			'foreignKey' => 'feature_set_id',
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'counterCache' => ''
		);
		$this->assertEquals($expected, $TestModel2->belongsTo['FeatureSet']);

		$TestModel2->bindModel(array(
			'belongsTo' => array(
				'FeatureSet' => array(
					'className' => 'FeatureSet',
					'conditions' => array('active' => true)
				)
			)
		));
		$expected['conditions'] = array('active' => true);
		$this->assertEquals($expected, $TestModel2->belongsTo['FeatureSet']);

		$TestModel2->bindModel(array(
			'belongsTo' => array(
				'FeatureSet' => array(
					'className' => 'FeatureSet',
					'foreignKey' => false,
					'conditions' => array('Feature.name' => 'DeviceType.name')
				)
			)
		));
		$expected['conditions'] = array('Feature.name' => 'DeviceType.name');
		$expected['foreignKey'] = false;
		$this->assertEquals($expected, $TestModel2->belongsTo['FeatureSet']);

		$TestModel2->bindModel(array(
			'hasMany' => array(
				'NewFeatureSet' => array(
					'className' => 'FeatureSet',
					'conditions' => array('active' => true)
				)
			)
		));

		$expected = array(
			'className' => 'FeatureSet',
			'conditions' => array('active' => true),
			'foreignKey' => 'device_type_id',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'dependent' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		);
		$this->assertEquals($expected, $TestModel2->hasMany['NewFeatureSet']);
		$this->assertTrue(is_object($TestModel2->NewFeatureSet));
	}

/**
 * testBindMultipleTimes method
 *
 * @return void
 */
	public function testBindMultipleTimes() {
		$this->loadFixtures('User', 'Comment', 'Article', 'Tag', 'ArticlesTag');
		$TestModel = new User();

		$result = $TestModel->hasMany;
		$expected = array();
		$this->assertEquals($expected, $result);

		$result = $TestModel->bindModel(array(
			'hasMany' => array(
				'Items' => array('className' => 'Comment')
		)));
		$this->assertTrue($result);

		$result = $TestModel->find('all', array(
			'fields' => 'User.id, User.user'
		));

		$expected = array(
			array(
				'User' => array(
					'id' => '1',
					'user' => 'mariano'
				),
				'Items' => array(
					array(
						'id' => '3',
						'article_id' => '1',
						'user_id' => '1',
						'comment' => 'Third Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:49:23',
						'updated' => '2007-03-18 10:51:31'
					),
					array(
						'id' => '4',
						'article_id' => '1',
						'user_id' => '1',
						'comment' => 'Fourth Comment for First Article',
						'published' => 'N',
						'created' => '2007-03-18 10:51:23',
						'updated' => '2007-03-18 10:53:31'
					),
					array(
						'id' => '5',
						'article_id' => '2',
						'user_id' => '1',
						'comment' => 'First Comment for Second Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:53:23',
						'updated' => '2007-03-18 10:55:31'
			))),
			array(
				'User' => array(
					'id' => '2',
					'user' => 'nate'
				),
				'Items' => array(
					array(
						'id' => '1',
						'article_id' => '1',
						'user_id' => '2',
						'comment' => 'First Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:45:23',
						'updated' => '2007-03-18 10:47:31'
					),
					array(
						'id' => '6',
						'article_id' => '2',
						'user_id' => '2',
						'comment' => 'Second Comment for Second Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:55:23',
						'updated' => '2007-03-18 10:57:31'
			))),
			array(
				'User' => array(
					'id' => '3',
					'user' => 'larry'
				),
				'Items' => array()
			),
			array(
				'User' => array(
					'id' => '4',
					'user' => 'garrett'
				),
					'Items' => array(
						array(
							'id' => '2',
							'article_id' => '1',
							'user_id' => '4',
							'comment' => 'Second Comment for First Article',
							'published' => 'Y',
							'created' => '2007-03-18 10:47:23',
							'updated' => '2007-03-18 10:49:31'
		))));
		$this->assertEquals($expected, $result);

		$result = $TestModel->bindModel(array(
			'hasMany' => array(
				'Items' => array('className' => 'Article')
		)));
		$this->assertTrue($result);

		$result = $TestModel->find('all', array(
			'fields' => 'User.id, User.user'
		));
		$expected = array(
			array(
				'User' => array(
					'id' => '1',
					'user' => 'mariano'
				),
				'Items' => array(
					array(
						'id' => 1,
						'user_id' => 1,
						'title' => 'First Article',
						'body' => 'First Article Body',
						'published' => 'Y',
						'created' => '2007-03-18 10:39:23',
						'updated' => '2007-03-18 10:41:31'
					),
					array(
						'id' => 3,
						'user_id' => 1,
						'title' => 'Third Article',
						'body' => 'Third Article Body',
						'published' => 'Y',
						'created' => '2007-03-18 10:43:23',
						'updated' => '2007-03-18 10:45:31'
			))),
			array(
				'User' => array(
					'id' => '2',
					'user' => 'nate'
				),
				'Items' => array()
			),
			array(
				'User' => array(
					'id' => '3',
					'user' => 'larry'
				),
				'Items' => array(
					array(
						'id' => 2,
						'user_id' => 3,
						'title' => 'Second Article',
						'body' => 'Second Article Body',
						'published' => 'Y',
						'created' => '2007-03-18 10:41:23',
						'updated' => '2007-03-18 10:43:31'
			))),
			array(
				'User' => array(
					'id' => '4',
					'user' => 'garrett'
				),
				'Items' => array()
		));

		$this->assertEquals($expected, $result);
	}

/**
 * test that multiple reset = true calls to bindModel() result in the original associations.
 *
 * @return void
 */
	public function testBindModelMultipleTimesResetCorrectly() {
		$this->loadFixtures('User', 'Comment', 'Article');
		$TestModel = new User();

		$TestModel->bindModel(array('hasMany' => array('Comment')));
		$TestModel->bindModel(array('hasMany' => array('Comment')));
		$TestModel->resetAssociations();

		$this->assertFalse(isset($TestModel->hasMany['Comment']), 'Association left behind');
	}

/**
 * testBindMultipleTimes method with different reset settings
 *
 * @return void
 */
	public function testBindMultipleTimesWithDifferentResetSettings() {
		$this->loadFixtures('User', 'Comment', 'Article');
		$TestModel = new User();

		$result = $TestModel->hasMany;
		$expected = array();
		$this->assertEquals($expected, $result);

		$result = $TestModel->bindModel(array(
			'hasMany' => array('Comment')
		));
		$this->assertTrue($result);
		$result = $TestModel->bindModel(
			array('hasMany' => array('Article')),
			false
		);
		$this->assertTrue($result);

		$result = array_keys($TestModel->hasMany);
		$expected = array('Comment', 'Article');
		$this->assertEquals($expected, $result);

		$TestModel->resetAssociations();

		$result = array_keys($TestModel->hasMany);
		$expected = array('Article');
		$this->assertEquals($expected, $result);
	}

/**
 * test that bindModel behaves with Custom primary Key associations
 *
 * @return void
 */
	public function testBindWithCustomPrimaryKey() {
		$this->loadFixtures('Story', 'StoriesTag', 'Tag');
		$Model = ClassRegistry::init('StoriesTag');
		$Model->bindModel(array(
			'belongsTo' => array(
				'Tag' => array(
					'className' => 'Tag',
					'foreignKey' => 'story'
		))));

		$result = $Model->find('all');
		$this->assertFalse(empty($result));
	}

/**
 * test that calling unbindModel() with reset == true multiple times
 * leaves associations in the correct state.
 *
 * @return void
 */
	public function testUnbindMultipleTimesResetCorrectly() {
		$this->loadFixtures('User', 'Comment', 'Article');
		$TestModel = new Article10();

		$TestModel->unbindModel(array('hasMany' => array('Comment')));
		$TestModel->unbindModel(array('hasMany' => array('Comment')));
		$TestModel->resetAssociations();

		$this->assertTrue(isset($TestModel->hasMany['Comment']), 'Association permanently removed');
	}

/**
 * testBindMultipleTimes method with different reset settings
 *
 * @return void
 */
	public function testUnBindMultipleTimesWithDifferentResetSettings() {
		$this->loadFixtures('User', 'Comment', 'Article');
		$TestModel = new Comment();

		$result = array_keys($TestModel->belongsTo);
		$expected = array('Article', 'User');
		$this->assertEquals($expected, $result);

		$result = $TestModel->unbindModel(array(
			'belongsTo' => array('User')
		));
		$this->assertTrue($result);
		$result = $TestModel->unbindModel(
			array('belongsTo' => array('Article')),
			false
		);
		$this->assertTrue($result);

		$result = array_keys($TestModel->belongsTo);
		$expected = array();
		$this->assertEquals($expected, $result);

		$TestModel->resetAssociations();

		$result = array_keys($TestModel->belongsTo);
		$expected = array('User');
		$this->assertEquals($expected, $result);
	}

/**
 * testAssociationAfterFind method
 *
 * @return void
 */
	public function testAssociationAfterFind() {
		$this->loadFixtures('Post', 'Author', 'Comment');
		$TestModel = new Post();
		$result = $TestModel->find('all', array(
			'order' => array('Post.id' => 'ASC')
		));
		$expected = array(
			array(
				'Post' => array(
					'id' => '1',
					'author_id' => '1',
					'title' => 'First Post',
					'body' => 'First Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'Author' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31',
					'test' => 'working'
			)),
			array(
				'Post' => array(
					'id' => '2',
					'author_id' => '3',
					'title' => 'Second Post',
					'body' => 'Second Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				),
				'Author' => array(
					'id' => '3',
					'user' => 'larry',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23',
					'updated' => '2007-03-17 01:22:31',
					'test' => 'working'
			)),
			array(
				'Post' => array(
					'id' => '3',
					'author_id' => '1',
					'title' => 'Third Post',
					'body' => 'Third Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
				),
				'Author' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31',
					'test' => 'working'
		)));
		$this->assertEquals($expected, $result);
		unset($TestModel);

		$Author = new Author();
		$Author->Post->bindModel(array(
			'hasMany' => array(
				'Comment' => array(
					'className' => 'ModifiedComment',
					'foreignKey' => 'article_id',
				)
		)));
		$result = $Author->find('all', array(
			'conditions' => array('Author.id' => 1),
			'order' => array('Author.id' => 'ASC'),
			'recursive' => 2
		));
		$expected = array(
			'id' => 1,
			'article_id' => 1,
			'user_id' => 2,
			'comment' => 'First Comment for First Article',
			'published' => 'Y',
			'created' => '2007-03-18 10:45:23',
			'updated' => '2007-03-18 10:47:31',
			'callback' => 'Fire'
		);
		$this->assertEquals($expected, $result[0]['Post'][0]['Comment'][0]);
	}

/**
 * testDeeperAssociationAfterFind method
 *
 * @return void
 */
	public function testDeeperAssociationAfterFind() {
		$this->loadFixtures('Post', 'Author', 'Comment', 'Attachment', 'Article');

		$Post = new Post();
		$Post->bindModel(array(
			'hasMany' => array(
				'Comment' => array(
					'className' => 'ModifiedComment',
					'foreignKey' => 'article_id',
				)
		)));
		$Post->Comment->bindModel(array(
			'hasOne' => array(
				'Attachment' => array(
					'className' => 'ModifiedAttachment',
				)
		)));

		$result = $Post->find('first', array(
			'conditions' => array('Post.id' => 2),
			'recursive' => 2
		));
		$this->assertTrue(isset($result['Comment'][0]['callback']));
		$this->assertEquals('Fire', $result['Comment'][0]['callback']);
		$this->assertTrue(isset($result['Comment'][0]['Attachment']['callback']));
		$this->assertEquals('Fired', $result['Comment'][0]['Attachment']['callback']);
	}

/**
 * Tests that callbacks can be properly disabled
 *
 * @return void
 */
	public function testCallbackDisabling() {
		$this->loadFixtures('Author');
		$TestModel = new ModifiedAuthor();

		$result = Hash::extract($TestModel->find('all'), '{n}.Author.user');
		$expected = array('mariano (CakePHP)', 'nate (CakePHP)', 'larry (CakePHP)', 'garrett (CakePHP)');
		$this->assertEquals($expected, $result);

		$result = Hash::extract($TestModel->find('all', array('callbacks' => 'after')), '{n}.Author.user');
		$expected = array('mariano (CakePHP)', 'nate (CakePHP)', 'larry (CakePHP)', 'garrett (CakePHP)');
		$this->assertEquals($expected, $result);

		$result = Hash::extract($TestModel->find('all', array('callbacks' => 'before')), '{n}.Author.user');
		$expected = array('mariano', 'nate', 'larry', 'garrett');
		$this->assertEquals($expected, $result);

		$result = Hash::extract($TestModel->find('all', array('callbacks' => false)), '{n}.Author.user');
		$expected = array('mariano', 'nate', 'larry', 'garrett');
		$this->assertEquals($expected, $result);
	}

/**
 * testAssociationAfterFindCallbacksDisabled method
 *
 * @return void
 */
	public function testAssociationAfterFindCalbacksDisabled() {
		$this->loadFixtures('Post', 'Author', 'Comment');
		$TestModel = new Post();
		$result = $TestModel->find('all', array(
			'callbacks' => false,
			'order' => array('Post.id' => 'ASC'),
		));
		$expected = array(
			array(
				'Post' => array(
					'id' => '1',
					'author_id' => '1',
					'title' => 'First Post',
					'body' => 'First Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'Author' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
			)),
			array(
				'Post' => array(
					'id' => '2',
					'author_id' => '3',
					'title' => 'Second Post',
					'body' => 'Second Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				),
				'Author' => array(
					'id' => '3',
					'user' => 'larry',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23',
					'updated' => '2007-03-17 01:22:31'
			)),
			array(
				'Post' => array(
					'id' => '3',
					'author_id' => '1',
					'title' => 'Third Post',
					'body' => 'Third Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
				),
				'Author' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
		)));
		$this->assertEquals($expected, $result);
		unset($TestModel);

		$Author = new Author();
		$Author->Post->bindModel(array(
			'hasMany' => array(
				'Comment' => array(
					'className' => 'ModifiedComment',
					'foreignKey' => 'article_id',
				)
		)));
		$result = $Author->find('all', array(
			'conditions' => array('Author.id' => 1),
			'recursive' => 2,
			'order' => array('Author.id' => 'ASC'),
			'callbacks' => false
		));
		$expected = array(
			'id' => 1,
			'article_id' => 1,
			'user_id' => 2,
			'comment' => 'First Comment for First Article',
			'published' => 'Y',
			'created' => '2007-03-18 10:45:23',
			'updated' => '2007-03-18 10:47:31'
		);
		$this->assertEquals($expected, $result[0]['Post'][0]['Comment'][0]);
	}

/**
 * Tests that the database configuration assigned to the model can be changed using
 * (before|after)Find callbacks
 *
 * @return void
 */
	public function testCallbackSourceChange() {
		$this->loadFixtures('Post');
		$TestModel = new Post();
		$this->assertEquals(3, count($TestModel->find('all')));
	}

/**
	 * testCallbackSourceChangeUnknownDatasource method
	 *
	 * @return void
	 */
	public function testCallbackSourceChangeUnknownDatasource() {
		$this->expectException('MissingDatasourceConfigException');
		$this->loadFixtures('Post', 'Author');
		$TestModel = new Post();
		$this->assertFalse($TestModel->find('all', array('connection' => 'foo')));
	}

/**
 * testMultipleBelongsToWithSameClass method
 *
 * @return void
 */
	public function testMultipleBelongsToWithSameClass() {
		$this->loadFixtures(
			'DeviceType',
			'DeviceTypeCategory',
			'FeatureSet',
			'ExteriorTypeCategory',
			'Document',
			'Device',
			'DocumentDirectory'
		);

		$DeviceType = new DeviceType();

		$DeviceType->recursive = 2;
		$result = $DeviceType->read(null, 1);

		$expected = array(
			'DeviceType' => array(
				'id' => 1,
				'device_type_category_id' => 1,
				'feature_set_id' => 1,
				'exterior_type_category_id' => 1,
				'image_id' => 1,
				'extra1_id' => 1,
				'extra2_id' => 1,
				'name' => 'DeviceType 1',
				'order' => 0
			),
			'Image' => array(
				'id' => 1,
				'document_directory_id' => 1,
				'name' => 'Document 1',
				'DocumentDirectory' => array(
					'id' => 1,
					'name' => 'DocumentDirectory 1'
			)),
			'Extra1' => array(
				'id' => 1,
				'document_directory_id' => 1,
				'name' => 'Document 1',
				'DocumentDirectory' => array(
					'id' => 1,
					'name' => 'DocumentDirectory 1'
			)),
			'Extra2' => array(
				'id' => 1,
				'document_directory_id' => 1,
				'name' => 'Document 1',
				'DocumentDirectory' => array(
					'id' => 1,
					'name' => 'DocumentDirectory 1'
			)),
			'DeviceTypeCategory' => array(
				'id' => 1,
				'name' => 'DeviceTypeCategory 1'
			),
			'FeatureSet' => array(
				'id' => 1,
				'name' => 'FeatureSet 1'
			),
			'ExteriorTypeCategory' => array(
				'id' => 1,
				'image_id' => 1,
				'name' => 'ExteriorTypeCategory 1',
				'Image' => array(
					'id' => 1,
					'device_type_id' => 1,
					'name' => 'Device 1',
					'typ' => 1
			)),
			'Device' => array(
				array(
					'id' => 1,
					'device_type_id' => 1,
					'name' => 'Device 1',
					'typ' => 1
				),
				array(
					'id' => 2,
					'device_type_id' => 1,
					'name' => 'Device 2',
					'typ' => 1
				),
				array(
					'id' => 3,
					'device_type_id' => 1,
					'name' => 'Device 3',
					'typ' => 2
		)));

		$this->assertEquals($expected, $result);
	}

/**
 * testHabtmRecursiveBelongsTo method
 *
 * @return void
 */
	public function testHabtmRecursiveBelongsTo() {
		$this->loadFixtures('Portfolio', 'Item', 'ItemsPortfolio', 'Syfile', 'Image');
		$Portfolio = new Portfolio();

		$result = $Portfolio->find('first', array('conditions' => array('id' => 2), 'recursive' => 3));
		$expected = array(
			'Portfolio' => array(
				'id' => 2,
				'seller_id' => 1,
				'name' => 'Portfolio 2'
			),
			'Item' => array(
				array(
					'id' => 2,
					'syfile_id' => 2,
					'published' => false,
					'name' => 'Item 2',
					'ItemsPortfolio' => array(
						'id' => 2,
						'item_id' => 2,
						'portfolio_id' => 2
					),
					'Syfile' => array(
						'id' => 2,
						'image_id' => 2,
						'name' => 'Syfile 2',
						'item_count' => null,
						'Image' => array(
							'id' => 2,
							'name' => 'Image 2'
						)
				)),
				array(
					'id' => 6,
					'syfile_id' => 6,
					'published' => false,
					'name' => 'Item 6',
					'ItemsPortfolio' => array(
						'id' => 6,
						'item_id' => 6,
						'portfolio_id' => 2
					),
					'Syfile' => array(
						'id' => 6,
						'image_id' => null,
						'name' => 'Syfile 6',
						'item_count' => null,
						'Image' => array()
		))));

		$this->assertEquals($expected, $result);
	}

/**
 * testNonNumericHabtmJoinKey method
 *
 * @return void
 */
	public function testNonNumericHabtmJoinKey() {
		$this->loadFixtures('Post', 'Tag', 'PostsTag', 'Author');
		$Post = new Post();
		$Post->bindModel(array(
			'hasAndBelongsToMany' => array('Tag')
		));
		$Post->Tag->primaryKey = 'tag';

		$result = $Post->find('all', array(
			'order' => 'Post.id ASC',
		));
		$expected = array(
			array(
				'Post' => array(
					'id' => '1',
					'author_id' => '1',
					'title' => 'First Post',
					'body' => 'First Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'Author' => array(
					'id' => 1,
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31',
					'test' => 'working'
				),
				'Tag' => array(
					array(
						'id' => '1',
						'tag' => 'tag1',
						'created' => '2007-03-18 12:22:23',
						'updated' => '2007-03-18 12:24:31'
					),
					array(
						'id' => '2',
						'tag' => 'tag2',
						'created' => '2007-03-18 12:24:23',
						'updated' => '2007-03-18 12:26:31'
			))),
			array(
				'Post' => array(
					'id' => '2',
					'author_id' => '3',
					'title' => 'Second Post',
					'body' => 'Second Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				),
				'Author' => array(
					'id' => 3,
					'user' => 'larry',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23',
					'updated' => '2007-03-17 01:22:31',
					'test' => 'working'
				),
				'Tag' => array(
					array(
						'id' => '1',
						'tag' => 'tag1',
						'created' => '2007-03-18 12:22:23',
						'updated' => '2007-03-18 12:24:31'
						),
					array(
						'id' => '3',
						'tag' => 'tag3',
						'created' => '2007-03-18 12:26:23',
						'updated' => '2007-03-18 12:28:31'
			))),
			array(
				'Post' => array(
					'id' => '3',
					'author_id' => '1',
					'title' => 'Third Post',
					'body' => 'Third Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
				),
				'Author' => array(
					'id' => 1,
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31',
					'test' => 'working'
				),
				'Tag' => array()
		));
		$this->assertEquals($expected, $result);
	}

/**
 * testHabtmFinderQuery method
 *
 * @return void
 */
	public function testHabtmFinderQuery() {
		$this->loadFixtures('Article', 'Tag', 'ArticlesTag');
		$Article = new Article();

		$sql = $this->db->buildStatement(
			array(
				'fields' => $this->db->fields($Article->Tag, null, array(
					'Tag.id', 'Tag.tag', 'ArticlesTag.article_id', 'ArticlesTag.tag_id'
				)),
				'table' => $this->db->fullTableName('tags'),
				'alias' => 'Tag',
				'limit' => null,
				'offset' => null,
				'group' => null,
				'joins' => array(array(
					'alias' => 'ArticlesTag',
					'table' => 'articles_tags',
					'conditions' => array(
						array("ArticlesTag.article_id" => '{$__cakeID__$}'),
						array("ArticlesTag.tag_id" => $this->db->identifier('Tag.id'))
					)
				)),
				'conditions' => array(),
				'order' => null
			),
			$Article
		);

		$Article->hasAndBelongsToMany['Tag']['finderQuery'] = $sql;
		$result = $Article->find('first');
		$expected = array(
			array(
				'id' => '1',
				'tag' => 'tag1'
			),
			array(
				'id' => '2',
				'tag' => 'tag2'
		));

		$this->assertEquals($expected, $result['Tag']);
	}

/**
 * testHabtmLimitOptimization method
 *
 * @return void
 */
	public function testHabtmLimitOptimization() {
		$this->loadFixtures('Article', 'User', 'Comment', 'Tag', 'ArticlesTag');
		$TestModel = new Article();

		$TestModel->hasAndBelongsToMany['Tag']['limit'] = 2;
		$result = $TestModel->read(null, 2);
		$expected = array(
			'Article' => array(
				'id' => '2',
				'user_id' => '3',
				'title' => 'Second Article',
				'body' => 'Second Article Body',
				'published' => 'Y',
				'created' => '2007-03-18 10:41:23',
				'updated' => '2007-03-18 10:43:31'
			),
			'User' => array(
				'id' => '3',
				'user' => 'larry',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				'created' => '2007-03-17 01:20:23',
				'updated' => '2007-03-17 01:22:31'
			),
			'Comment' => array(
				array(
					'id' => '5',
					'article_id' => '2',
					'user_id' => '1',
					'comment' => 'First Comment for Second Article',
					'published' => 'Y',
					'created' => '2007-03-18 10:53:23',
					'updated' => '2007-03-18 10:55:31'
				),
				array(
					'id' => '6',
					'article_id' => '2',
					'user_id' => '2',
					'comment' => 'Second Comment for Second Article',
					'published' => 'Y',
					'created' => '2007-03-18 10:55:23',
					'updated' => '2007-03-18 10:57:31'
			)),
			'Tag' => array(
				array(
					'id' => '1',
					'tag' => 'tag1',
					'created' => '2007-03-18 12:22:23',
					'updated' => '2007-03-18 12:24:31'
				),
				array(
					'id' => '3',
					'tag' => 'tag3',
					'created' => '2007-03-18 12:26:23',
					'updated' => '2007-03-18 12:28:31'
		)));

		$this->assertEquals($expected, $result);

		$TestModel->hasAndBelongsToMany['Tag']['limit'] = 1;
		$result = $TestModel->read(null, 2);
		unset($expected['Tag'][1]);

		$this->assertEquals($expected, $result);
	}

/**
 * testHasManyLimitOptimization method
 *
 * @return void
 */
	public function testHasManyLimitOptimization() {
		$this->loadFixtures('Project', 'Thread', 'Message', 'Bid');
		$Project = new Project();
		$Project->recursive = 3;

		$result = $Project->find('all', array(
			'order' => 'Project.id ASC',
		));
		$expected = array(
			array(
				'Project' => array(
					'id' => 1,
					'name' => 'Project 1'
				),
				'Thread' => array(
					array(
						'id' => 1,
						'project_id' => 1,
						'name' => 'Project 1, Thread 1',
						'Project' => array(
							'id' => 1,
							'name' => 'Project 1',
							'Thread' => array(
								array(
									'id' => 1,
									'project_id' => 1,
									'name' => 'Project 1, Thread 1'
								),
								array(
									'id' => 2,
									'project_id' => 1,
									'name' => 'Project 1, Thread 2'
						))),
						'Message' => array(
							array(
								'id' => 1,
								'thread_id' => 1,
								'name' => 'Thread 1, Message 1',
								'Bid' => array(
									'id' => 1,
									'message_id' => 1,
									'name' => 'Bid 1.1'
					)))),
					array(
						'id' => 2,
						'project_id' => 1,
						'name' => 'Project 1, Thread 2',
						'Project' => array(
							'id' => 1,
							'name' => 'Project 1',
							'Thread' => array(
								array(
									'id' => 1,
									'project_id' => 1,
									'name' => 'Project 1, Thread 1'
								),
								array(
									'id' => 2,
									'project_id' => 1,
									'name' => 'Project 1, Thread 2'
						))),
						'Message' => array(
							array(
								'id' => 2,
								'thread_id' => 2,
								'name' => 'Thread 2, Message 1',
								'Bid' => array(
									'id' => 4,
									'message_id' => 2,
									'name' => 'Bid 2.1'
			)))))),
			array(
				'Project' => array(
					'id' => 2,
					'name' => 'Project 2'
				),
				'Thread' => array(
					array(
						'id' => 3,
						'project_id' => 2,
						'name' => 'Project 2, Thread 1',
						'Project' => array(
							'id' => 2,
							'name' => 'Project 2',
							'Thread' => array(
								array(
									'id' => 3,
									'project_id' => 2,
									'name' => 'Project 2, Thread 1'
						))),
						'Message' => array(
							array(
								'id' => 3,
								'thread_id' => 3,
								'name' => 'Thread 3, Message 1',
								'Bid' => array(
									'id' => 3,
									'message_id' => 3,
									'name' => 'Bid 3.1'
			)))))),
			array(
				'Project' => array(
					'id' => 3,
					'name' => 'Project 3'
				),
				'Thread' => array()
		));

		$this->assertEquals($expected, $result);
	}

/**
 * testFindAllRecursiveSelfJoin method
 *
 * @return void
 */
	public function testFindAllRecursiveSelfJoin() {
		$this->loadFixtures('Home', 'AnotherArticle', 'Advertisement');
		$TestModel = new Home();
		$TestModel->recursive = 2;

		$result = $TestModel->find('all', array(
			'order' => 'Home.id ASC',
		));
		$expected = array(
			array(
				'Home' => array(
					'id' => '1',
					'another_article_id' => '1',
					'advertisement_id' => '1',
					'title' => 'First Home',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'AnotherArticle' => array(
					'id' => '1',
					'title' => 'First Article',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31',
					'Home' => array(
						array(
							'id' => '1',
							'another_article_id' => '1',
							'advertisement_id' => '1',
							'title' => 'First Home',
							'created' => '2007-03-18 10:39:23',
							'updated' => '2007-03-18 10:41:31'
				))),
				'Advertisement' => array(
					'id' => '1',
					'title' => 'First Ad',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31',
					'Home' => array(
						array(
							'id' => '1',
							'another_article_id' => '1',
							'advertisement_id' => '1',
							'title' => 'First Home',
							'created' => '2007-03-18 10:39:23',
							'updated' => '2007-03-18 10:41:31'
						),
						array(
							'id' => '2',
							'another_article_id' => '3',
							'advertisement_id' => '1',
							'title' => 'Second Home',
							'created' => '2007-03-18 10:41:23',
							'updated' => '2007-03-18 10:43:31'
			)))),
			array(
				'Home' => array(
					'id' => '2',
					'another_article_id' => '3',
					'advertisement_id' => '1',
					'title' => 'Second Home',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				),
				'AnotherArticle' => array(
					'id' => '3',
					'title' => 'Third Article',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31',
					'Home' => array(
						array(
							'id' => '2',
							'another_article_id' => '3',
							'advertisement_id' => '1',
							'title' => 'Second Home',
							'created' => '2007-03-18 10:41:23',
							'updated' => '2007-03-18 10:43:31'
				))),
				'Advertisement' => array(
					'id' => '1',
					'title' => 'First Ad',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31',
					'Home' => array(
						array(
							'id' => '1',
							'another_article_id' => '1',
							'advertisement_id' => '1',
							'title' => 'First Home',
							'created' => '2007-03-18 10:39:23',
							'updated' => '2007-03-18 10:41:31'
						),
						array(
							'id' => '2',
							'another_article_id' => '3',
							'advertisement_id' => '1',
							'title' => 'Second Home',
							'created' => '2007-03-18 10:41:23',
							'updated' => '2007-03-18 10:43:31'
		)))));

		$this->assertEquals($expected, $result);
	}

/**
 * testFindAllRecursiveWithHabtm method
 *
 * @return void
 */
	public function testFindAllRecursiveWithHabtm() {
		$this->loadFixtures(
			'MyCategoriesMyUsers',
			'MyCategoriesMyProducts',
			'MyCategory',
			'MyUser',
			'MyProduct'
		);

		$MyUser = new MyUser();
		$MyUser->recursive = 2;

		$result = $MyUser->find('all', array(
			'order' => 'MyUser.id ASC'
		));
		$expected = array(
			array(
				'MyUser' => array('id' => '1', 'firstname' => 'userA'),
				'MyCategory' => array(
					array(
						'id' => '1',
						'name' => 'A',
						'MyProduct' => array(
							array(
								'id' => '1',
								'name' => 'book'
					))),
					array(
						'id' => '3',
						'name' => 'C',
						'MyProduct' => array(
							array(
								'id' => '2',
								'name' => 'computer'
			))))),
			array(
				'MyUser' => array(
					'id' => '2',
					'firstname' => 'userB'
				),
				'MyCategory' => array(
					array(
						'id' => '1',
						'name' => 'A',
						'MyProduct' => array(
							array(
								'id' => '1',
								'name' => 'book'
					))),
					array(
						'id' => '2',
						'name' => 'B',
						'MyProduct' => array(
							array(
								'id' => '1',
								'name' => 'book'
							),
							array(
								'id' => '2',
								'name' => 'computer'
		))))));

		$this->assertEquals($expected, $result);
	}

/**
 * testReadFakeThread method
 *
 * @return void
 */
	public function testReadFakeThread() {
		$this->loadFixtures('CategoryThread');
		$TestModel = new CategoryThread();

		$fullDebug = $this->db->fullDebug;
		$this->db->fullDebug = true;
		$TestModel->recursive = 6;
		$TestModel->id = 7;
		$result = $TestModel->read();
		$expected = array(
			'CategoryThread' => array(
				'id' => 7,
				'parent_id' => 6,
				'name' => 'Category 2.1',
				'created' => '2007-03-18 15:30:23',
				'updated' => '2007-03-18 15:32:31'
			),
			'ParentCategory' => array(
				'id' => 6,
				'parent_id' => 5,
				'name' => 'Category 2',
				'created' => '2007-03-18 15:30:23',
				'updated' => '2007-03-18 15:32:31',
				'ParentCategory' => array(
					'id' => 5,
					'parent_id' => 4,
					'name' => 'Category 1.1.1.1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array(
						'id' => 4,
						'parent_id' => 3,
						'name' => 'Category 1.1.2',
						'created' => '2007-03-18 15:30:23',
						'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array(
							'id' => 3,
							'parent_id' => 2,
							'name' => 'Category 1.1.1',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31',
							'ParentCategory' => array(
								'id' => 2,
								'parent_id' => 1,
								'name' => 'Category 1.1',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31',
								'ParentCategory' => array(
									'id' => 1,
									'parent_id' => 0,
									'name' => 'Category 1',
									'created' => '2007-03-18 15:30:23',
									'updated' => '2007-03-18 15:32:31'
		)))))));

		$this->db->fullDebug = $fullDebug;
		$this->assertEquals($expected, $result);
	}

/**
 * testFindFakeThread method
 *
 * @return void
 */
	public function testFindFakeThread() {
		$this->loadFixtures('CategoryThread');
		$TestModel = new CategoryThread();

		$fullDebug = $this->db->fullDebug;
		$this->db->fullDebug = true;
		$TestModel->recursive = 6;
		$result = $TestModel->find('first', array('conditions' => array('CategoryThread.id' => 7)));

		$expected = array(
			'CategoryThread' => array(
				'id' => 7,
				'parent_id' => 6,
				'name' => 'Category 2.1',
				'created' => '2007-03-18 15:30:23',
				'updated' => '2007-03-18 15:32:31'
			),
			'ParentCategory' => array(
				'id' => 6,
				'parent_id' => 5,
				'name' => 'Category 2',
				'created' => '2007-03-18 15:30:23',
				'updated' => '2007-03-18 15:32:31',
				'ParentCategory' => array(
					'id' => 5,
					'parent_id' => 4,
					'name' => 'Category 1.1.1.1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array(
						'id' => 4,
						'parent_id' => 3,
						'name' => 'Category 1.1.2',
						'created' => '2007-03-18 15:30:23',
						'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array(
							'id' => 3,
							'parent_id' => 2,
							'name' => 'Category 1.1.1',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31',
							'ParentCategory' => array(
								'id' => 2,
								'parent_id' => 1,
								'name' => 'Category 1.1',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31',
								'ParentCategory' => array(
									'id' => 1,
									'parent_id' => 0,
									'name' => 'Category 1',
									'created' => '2007-03-18 15:30:23',
									'updated' => '2007-03-18 15:32:31'
		)))))));

		$this->db->fullDebug = $fullDebug;
		$this->assertEquals($expected, $result);
	}

/**
 * testFindAllFakeThread method
 *
 * @return void
 */
	public function testFindAllFakeThread() {
		$this->loadFixtures('CategoryThread');
		$TestModel = new CategoryThread();

		$fullDebug = $this->db->fullDebug;
		$this->db->fullDebug = true;
		$TestModel->recursive = 6;
		$result = $TestModel->find('all');
		$expected = array(
			array(
				'CategoryThread' => array(
				'id' => 1,
				'parent_id' => 0,
				'name' => 'Category 1',
				'created' => '2007-03-18 15:30:23',
				'updated' => '2007-03-18 15:32:31'
				),
				'ParentCategory' => array(
					'id' => null,
					'parent_id' => null,
					'name' => null,
					'created' => null,
					'updated' => null,
					'ParentCategory' => array()
			)),
			array(
				'CategoryThread' => array(
					'id' => 2,
					'parent_id' => 1,
					'name' => 'Category 1.1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'ParentCategory' => array(
					'id' => 1,
					'parent_id' => 0,
					'name' => 'Category 1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array()
				)),
			array(
				'CategoryThread' => array(
					'id' => 3,
					'parent_id' => 2,
					'name' => 'Category 1.1.1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'ParentCategory' => array(
					'id' => 2,
					'parent_id' => 1,
					'name' => 'Category 1.1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array(
						'id' => 1,
						'parent_id' => 0,
						'name' => 'Category 1',
						'created' => '2007-03-18 15:30:23',
						'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array()
			))),
			array(
				'CategoryThread' => array(
					'id' => 4,
					'parent_id' => 3,
					'name' => 'Category 1.1.2',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'ParentCategory' => array(
					'id' => 3,
					'parent_id' => 2,
					'name' => 'Category 1.1.1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array(
						'id' => 2,
						'parent_id' => 1,
						'name' => 'Category 1.1',
						'created' => '2007-03-18 15:30:23',
						'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array(
							'id' => 1,
							'parent_id' => 0,
							'name' => 'Category 1',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31',
							'ParentCategory' => array()
			)))),
			array(
				'CategoryThread' => array(
					'id' => 5,
					'parent_id' => 4,
					'name' => 'Category 1.1.1.1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'ParentCategory' => array(
					'id' => 4,
					'parent_id' => 3,
					'name' => 'Category 1.1.2',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array(
						'id' => 3,
						'parent_id' => 2,
						'name' => 'Category 1.1.1',
						'created' => '2007-03-18 15:30:23',
						'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array(
							'id' => 2,
							'parent_id' => 1,
							'name' => 'Category 1.1',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31',
							'ParentCategory' => array(
								'id' => 1,
								'parent_id' => 0,
								'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31',
								'ParentCategory' => array()
			))))),
			array(
				'CategoryThread' => array(
					'id' => 6,
					'parent_id' => 5,
					'name' => 'Category 2',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'ParentCategory' => array(
					'id' => 5,
					'parent_id' => 4,
					'name' => 'Category 1.1.1.1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array(
						'id' => 4,
						'parent_id' => 3,
						'name' => 'Category 1.1.2',
						'created' => '2007-03-18 15:30:23',
						'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array(
							'id' => 3,
							'parent_id' => 2,
							'name' => 'Category 1.1.1',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31',
							'ParentCategory' => array(
								'id' => 2,
								'parent_id' => 1,
								'name' => 'Category 1.1',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31',
								'ParentCategory' => array(
									'id' => 1,
									'parent_id' => 0,
									'name' => 'Category 1',
									'created' => '2007-03-18 15:30:23',
									'updated' => '2007-03-18 15:32:31',
									'ParentCategory' => array()
			)))))),
			array(
				'CategoryThread' => array(
					'id' => 7,
					'parent_id' => 6,
					'name' => 'Category 2.1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'ParentCategory' => array(
					'id' => 6,
					'parent_id' => 5,
					'name' => 'Category 2',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array(
						'id' => 5,
						'parent_id' => 4,
						'name' => 'Category 1.1.1.1',
						'created' => '2007-03-18 15:30:23',
						'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array(
							'id' => 4,
							'parent_id' => 3,
							'name' => 'Category 1.1.2',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31',
							'ParentCategory' => array(
								'id' => 3,
								'parent_id' => 2,
								'name' => 'Category 1.1.1',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31',
							'ParentCategory' => array(
								'id' => 2,
								'parent_id' => 1,
								'name' => 'Category 1.1',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31',
								'ParentCategory' => array(
									'id' => 1,
									'parent_id' => 0,
									'name' => 'Category 1',
									'created' => '2007-03-18 15:30:23',
									'updated' => '2007-03-18 15:32:31'
		))))))));

		$this->db->fullDebug = $fullDebug;
		$this->assertEquals($expected, $result);
	}

/**
 * testConditionalNumerics method
 *
 * @return void
 */
	public function testConditionalNumerics() {
		$this->loadFixtures('NumericArticle');
		$NumericArticle = new NumericArticle();
		$data = array('conditions' => array('title' => '12345abcde'));
		$result = $NumericArticle->find('first', $data);
		$this->assertTrue(!empty($result));

		$data = array('conditions' => array('title' => '12345'));
		$result = $NumericArticle->find('first', $data);
		$this->assertTrue(empty($result));
	}

/**
 * test buildQuery()
 *
 * @return void
 */
	public function testBuildQuery() {
		$this->loadFixtures('User');
		$TestModel = new User();
		$TestModel->cacheQueries = false;
		$TestModel->order = null;
		$expected = array(
			'conditions' => array(
				'user' => 'larry'
			),
			'fields' => null,
			'joins' => array(),
			'limit' => null,
			'offset' => null,
			'order' => array(),
			'page' => 1,
			'group' => null,
			'callbacks' => true,
			'returnQuery' => true
		);
		$result = $TestModel->buildQuery('all', array('returnQuery' => true, 'conditions' => array('user' => 'larry')));
		$this->assertEquals($expected, $result);
	}

	public function testBuildQueryAllI18nConditions() {
		$this->skipIf(!$this->db instanceof Mysql, 'This test is only compatible with Mysql.');
		$dbName = $this->db->config['database'];

		$this->loadFixtures('TranslateArticle', 'TranslatedArticle', 'User');
		$TestModel = new TranslatedArticle();
		$TestModel->cacheQueries = false;
		$TestModel->locale = 'eng';
		$expected = array(
			'conditions' => array(
				'NOT' => array('I18n__title.content' => ''),
			),
			'fields' => null,
			'joins' => array(
				array(
					'type' => 'INNER',
					'alias' => 'I18n__title',
					'table' => (object)array(
						'tablePrefix' => '',
						'table' => 'article_i18n',
						'schemaName' => $dbName
					),
					'conditions' => array(
						'TranslatedArticle.id' => (object)array(
							'type' => 'identifier',
							'value' => 'I18n__title.foreign_key',
						),
						'I18n__title.model' => 'TranslatedArticle',
						'I18n__title.field' => 'title',
						'I18n__title.locale' => 'eng',
					),
				),
				array(
					'type' => 'INNER',
					'alias' => 'I18n__body',
					'table' => (object)array(
						'tablePrefix' => '',
						'table' => 'article_i18n',
						'schemaName' => $dbName
					),
					'conditions' => array(
						'TranslatedArticle.id' => (object)array(
							'type' => 'identifier',
							'value' => 'I18n__body.foreign_key',
						),
						'I18n__body.model' => 'TranslatedArticle',
						'I18n__body.field' => 'body',
						'I18n__body.locale' => 'eng',
					),
				),
			),
			'limit' => 2,
			'offset' => null,
			'order' => array(
				'TranslatedArticle.id' => 'ASC',
			),
			'page' => 1,
			'group' => null,
			'callbacks' => true,
			'recursive' => 0,
		);
		$query = array(
			'recursive' => 0,
			'conditions' => array(
				'NOT' => array('I18n__title.content' => ''),
			),
			'limit' => 2,
		);
		$result = $TestModel->buildQuery('all', $query);
		$this->assertEquals($expected, $result);
	}

	public function testBuildQueryCountI18nConditions() {
		$this->skipIf(!$this->db instanceof Mysql, 'This test is only compatible with Mysql.');
		$dbName = $this->db->config['database'];
		$this->loadFixtures('TranslateArticle', 'TranslatedArticle', 'User');
		$TestModel = new TranslatedArticle();
		$TestModel->cacheQueries = false;
		$TestModel->locale = 'eng';
		$expected = array(
			'conditions' => array(
				'NOT' => array('I18n__title.content' => ''),
			),
			'fields' => 'COUNT(DISTINCT(`TranslatedArticle`.`id`)) AS count',
			'joins' => array(
				array(
					'type' => 'INNER',
					'alias' => 'TranslateArticleModel',
					'table' => (object)array(
						'tablePrefix' => '',
						'table' => 'article_i18n',
						'schemaName' => $dbName
					),
					'conditions' => array(
						'`TranslatedArticle`.`id`' => (object)array(
							'type' => 'identifier',
							'value' => '`TranslateArticleModel`.`foreign_key`',
						),
						'`TranslateArticleModel`.`model`' => 'TranslatedArticle',
						'`TranslateArticleModel`.`locale`' => 'eng',
					),
				),
				array(
					'type' => 'INNER',
					'alias' => 'I18n__title',
					'table' => (object)array(
						'tablePrefix' => '',
						'table' => 'article_i18n',
						'schemaName' => $dbName
					),
					'conditions' => array(
						'TranslatedArticle.id' => (object)array(
							'type' => 'identifier',
							'value' => 'I18n__title.foreign_key',
						),
						'I18n__title.model' => 'TranslatedArticle',
						'I18n__title.field' => 'title',
						'I18n__title.locale' => 'eng',
					),
				),
			),
			'limit' => 2,
			'offset' => null,
			'order' => array(
				0 => false,
			),
			'page' => 1,
			'group' => null,
			'callbacks' => true,
			'recursive' => 0,
		);
		$query = array(
			'recursive' => 0,
			'conditions' => array(
				'NOT' => array('I18n__title.content' => ''),
			),
			'limit' => 2,
		);
		$result = $TestModel->buildQuery('count', $query);
		$this->assertEquals($expected, $result);
	}

/**
 * test find('all') method
 *
 * @return void
 */
	public function testFindAll() {
		$this->loadFixtures('User');
		$TestModel = new User();
		$TestModel->cacheQueries = false;

		$result = $TestModel->find('all');
		$expected = array(
			array(
				'User' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
			)),
			array(
				'User' => array(
					'id' => '2',
					'user' => 'nate',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23',
					'updated' => '2007-03-17 01:20:31'
			)),
			array(
				'User' => array(
					'id' => '3',
					'user' => 'larry',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23',
					'updated' => '2007-03-17 01:22:31'
			)),
			array(
				'User' => array(
					'id' => '4',
					'user' => 'garrett',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23',
					'updated' => '2007-03-17 01:24:31'
		)));
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all', array('conditions' => 'User.id > 2'));
		$expected = array(
			array(
				'User' => array(
					'id' => '3',
					'user' => 'larry',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23',
					'updated' => '2007-03-17 01:22:31'
			)),
			array(
				'User' => array(
					'id' => '4',
					'user' => 'garrett',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23',
					'updated' => '2007-03-17 01:24:31'
		)));
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all', array(
			'conditions' => array('User.id !=' => '0', 'User.user LIKE' => '%arr%')
		));
		$expected = array(
			array(
				'User' => array(
					'id' => '3',
					'user' => 'larry',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23',
					'updated' => '2007-03-17 01:22:31'
			)),
			array(
				'User' => array(
					'id' => '4',
					'user' => 'garrett',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23',
					'updated' => '2007-03-17 01:24:31'
		)));
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all', array('conditions' => array('User.id' => '0')));
		$expected = array();
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all', array(
			'conditions' => array('or' => array('User.id' => '0', 'User.user LIKE' => '%a%')
		)));

		$expected = array(
			array(
				'User' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
			)),
			array(
				'User' => array(
					'id' => '2',
					'user' => 'nate',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23',
					'updated' => '2007-03-17 01:20:31'
			)),
			array(
				'User' => array(
					'id' => '3',
					'user' => 'larry',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23',
					'updated' => '2007-03-17 01:22:31'
			)),
			array(
				'User' => array(
					'id' => '4',
					'user' => 'garrett',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23',
					'updated' => '2007-03-17 01:24:31'
		)));
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all', array('fields' => 'User.id, User.user'));
		$expected = array(
				array('User' => array('id' => '1', 'user' => 'mariano')),
				array('User' => array('id' => '2', 'user' => 'nate')),
				array('User' => array('id' => '3', 'user' => 'larry')),
				array('User' => array('id' => '4', 'user' => 'garrett')));
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all', array('fields' => 'User.user', 'order' => 'User.user ASC'));
		$expected = array(
				array('User' => array('user' => 'garrett')),
				array('User' => array('user' => 'larry')),
				array('User' => array('user' => 'mariano')),
				array('User' => array('user' => 'nate')));
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all', array('fields' => 'User.user', 'order' => 'User.user DESC'));
		$expected = array(
				array('User' => array('user' => 'nate')),
				array('User' => array('user' => 'mariano')),
				array('User' => array('user' => 'larry')),
				array('User' => array('user' => 'garrett')));
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all', array('limit' => 3, 'page' => 1));

		$expected = array(
			array(
				'User' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
			)),
			array(
				'User' => array(
					'id' => '2',
					'user' => 'nate',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23',
					'updated' => '2007-03-17 01:20:31'
			)),
			array(
				'User' => array(
					'id' => '3',
					'user' => 'larry',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23',
					'updated' => '2007-03-17 01:22:31'
		)));
		$this->assertEquals($expected, $result);

		$ids = array(4 => 1, 5 => 3);
		$result = $TestModel->find('all', array(
			'conditions' => array('User.id' => $ids),
			'order' => 'User.id'
		));
		$expected = array(
			array(
				'User' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
			)),
			array(
				'User' => array(
					'id' => '3',
					'user' => 'larry',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23',
					'updated' => '2007-03-17 01:22:31'
		)));
		$this->assertEquals($expected, $result);

		// These tests are expected to fail on SQL Server since the LIMIT/OFFSET
		// hack can't handle small record counts.
		if (!($this->db instanceof Sqlserver)) {
			$result = $TestModel->find('all', array('limit' => 3, 'page' => 2));
			$expected = array(
				array(
					'User' => array(
						'id' => '4',
						'user' => 'garrett',
						'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:22:23',
						'updated' => '2007-03-17 01:24:31'
			)));
			$this->assertEquals($expected, $result);

			$result = $TestModel->find('all', array('limit' => 3, 'page' => 3));
			$expected = array();
			$this->assertEquals($expected, $result);
		}
	}

/**
 * Test that find() with array conditions works when there is only one element.
 *
 * @return void
 */
	public function testFindAllArrayConditions() {
		$this->loadFixtures('User');
		$TestModel = new User();
		$TestModel->cacheQueries = false;

		$result = $TestModel->find('all', array(
			'conditions' => array('User.id' => array(3)),
		));
		$expected = array(
			array(
				'User' => array(
					'id' => '3',
					'user' => 'larry',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23',
					'updated' => '2007-03-17 01:22:31'
			))
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all', array(
			'conditions' => array('User.user' => array('larry')),
		));
		$this->assertEquals($expected, $result);
	}

	public function testFindAllI18nConditions() {
		$this->loadFixtures('TranslateArticle', 'TranslatedArticle', 'User');
		$TestModel = new TranslatedArticle();
		$TestModel->cacheQueries = false;
		$TestModel->locale = 'eng';
		$options = array(
			'recursive' => 0,
			'conditions' => array(
				'NOT' => array('I18n__title.content' => ''),
			),
			'limit' => 2,
		);
		$result = $TestModel->find('all', $options);
		$expected = array(
			array(
				'TranslatedArticle' => array(
					'id' => '1',
					'user_id' => '1',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31',
					'locale' => 'eng',
					'title' => 'Title (eng) #1',
					'body' => 'Body (eng) #1',
				),
				'User' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31',
				),
			),
			array(
				'TranslatedArticle' => array(
					'id' => '2',
					'user_id' => '3',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31',
					'locale' => 'eng',
					'title' => 'Title (eng) #2',
					'body' => 'Body (eng) #2',
				),
				'User' => array(
					'id' => '3',
					'user' => 'larry',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23',
					'updated' => '2007-03-17 01:22:31',
				),
			),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test find('list') method
 *
 * @return void
 */
	public function testFindList() {
		$this->loadFixtures('Article', 'Apple', 'Post', 'Author', 'User', 'Comment');

		$TestModel = new Article();
		$TestModel->displayField = 'title';

		$result = $TestModel->find('list', array(
			'order' => 'Article.title ASC'
		));

		$expected = array(
			1 => 'First Article',
			2 => 'Second Article',
			3 => 'Third Article'
		);
		$this->assertEquals($expected, $result);

		$db = ConnectionManager::getDataSource('test');
		if ($db instanceof Mysql) {
			$result = $TestModel->find('list', array(
				'order' => array('FIELD(Article.id, 3, 2) ASC', 'Article.title ASC')
			));
			$expected = array(
				1 => 'First Article',
				3 => 'Third Article',
				2 => 'Second Article'
			);
			$this->assertEquals($expected, $result);
		}

		$result = Hash::combine(
			$TestModel->find('all', array(
				'order' => 'Article.title ASC',
				'fields' => array('id', 'title')
			)),
			'{n}.Article.id', '{n}.Article.title'
		);
		$expected = array(
			1 => 'First Article',
			2 => 'Second Article',
			3 => 'Third Article'
		);
		$this->assertEquals($expected, $result);

		$result = Hash::combine(
			$TestModel->find('all', array(
				'order' => 'Article.title ASC'
			)),
			'{n}.Article.id', '{n}.Article'
		);
		$expected = array(
			1 => array(
				'id' => 1,
				'user_id' => 1,
				'title' => 'First Article',
				'body' => 'First Article Body',
				'published' => 'Y',
				'created' => '2007-03-18 10:39:23',
				'updated' => '2007-03-18 10:41:31'
			),
			2 => array(
				'id' => 2,
				'user_id' => 3,
				'title' => 'Second Article',
				'body' => 'Second Article Body',
				'published' => 'Y',
				'created' => '2007-03-18 10:41:23',
				'updated' => '2007-03-18 10:43:31'
			),
			3 => array(
				'id' => 3,
				'user_id' => 1,
				'title' => 'Third Article',
				'body' => 'Third Article Body',
				'published' => 'Y',
				'created' => '2007-03-18 10:43:23',
				'updated' => '2007-03-18 10:45:31'
		));

		$this->assertEquals($expected, $result);

		$result = Hash::combine(
			$TestModel->find('all', array(
				'order' => 'Article.title ASC'
			)),
			'{n}.Article.id', '{n}.Article', '{n}.Article.user_id'
		);
		$expected = array(
			1 => array(
				1 => array(
					'id' => 1,
					'user_id' => 1,
					'title' => 'First Article',
					'body' => 'First Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				3 => array(
					'id' => 3,
					'user_id' => 1,
					'title' => 'Third Article',
					'body' => 'Third Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
				)),
			3 => array(
				2 => array(
					'id' => 2,
					'user_id' => 3,
					'title' => 'Second Article',
					'body' => 'Second Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
		)));

		$this->assertEquals($expected, $result);

		$result = Hash::combine(
			$TestModel->find('all', array(
				'order' => 'Article.title ASC',
				'fields' => array('id', 'title', 'user_id')
			)),
			'{n}.Article.id', '{n}.Article.title', '{n}.Article.user_id'
		);

		$expected = array(
			1 => array(
				1 => 'First Article',
				3 => 'Third Article'
			),
			3 => array(
				2 => 'Second Article'
		));
		$this->assertEquals($expected, $result);

		$TestModel = new Apple();
		$expected = array(
			1 => 'Red Apple 1',
			2 => 'Bright Red Apple',
			3 => 'green blue',
			4 => 'Test Name',
			5 => 'Blue Green',
			6 => 'My new apple',
			7 => 'Some odd color'
		);

		$this->assertEquals($expected, $TestModel->find('list'));
		$this->assertEquals($expected, $TestModel->Parent->find('list'));

		$TestModel = new Post();
		$result = $TestModel->find('list', array(
			'fields' => 'Post.title'
		));
		$expected = array(
			1 => 'First Post',
			2 => 'Second Post',
			3 => 'Third Post'
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('list', array(
			'fields' => 'title'
		));
		$expected = array(
			1 => 'First Post',
			2 => 'Second Post',
			3 => 'Third Post'
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('list', array(
			'fields' => array('title', 'id')
		));
		$expected = array(
			'First Post' => '1',
			'Second Post' => '2',
			'Third Post' => '3'
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('list', array(
			'fields' => array('title', 'id', 'created')
		));
		$expected = array(
			'2007-03-18 10:39:23' => array(
				'First Post' => '1'
			),
			'2007-03-18 10:41:23' => array(
				'Second Post' => '2'
			),
			'2007-03-18 10:43:23' => array(
				'Third Post' => '3'
			),
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('list', array(
			'fields' => array('Post.body')
		));
		$expected = array(
			1 => 'First Post Body',
			2 => 'Second Post Body',
			3 => 'Third Post Body'
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('list', array(
			'fields' => array('Post.title', 'Post.body')
		));
		$expected = array(
			'First Post' => 'First Post Body',
			'Second Post' => 'Second Post Body',
			'Third Post' => 'Third Post Body'
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('list', array(
			'fields' => array('Post.id', 'Post.title', 'Author.user'),
			'recursive' => 1
		));
		$expected = array(
			'mariano' => array(
				1 => 'First Post',
				3 => 'Third Post'
			),
			'larry' => array(
				2 => 'Second Post'
		));
		$this->assertEquals($expected, $result);

		$TestModel = new User();
		$result = $TestModel->find('list', array(
			'fields' => array('User.user', 'User.password')
		));
		$expected = array(
			'mariano' => '5f4dcc3b5aa765d61d8327deb882cf99',
			'nate' => '5f4dcc3b5aa765d61d8327deb882cf99',
			'larry' => '5f4dcc3b5aa765d61d8327deb882cf99',
			'garrett' => '5f4dcc3b5aa765d61d8327deb882cf99'
		);
		$this->assertEquals($expected, $result);

		$TestModel = new ModifiedAuthor();
		$result = $TestModel->find('list', array(
			'fields' => array('Author.id', 'Author.user')
		));
		$expected = array(
			1 => 'mariano (CakePHP)',
			2 => 'nate (CakePHP)',
			3 => 'larry (CakePHP)',
			4 => 'garrett (CakePHP)'
		);
		$this->assertEquals($expected, $result);

		$TestModel = new Article();
		$TestModel->displayField = 'title';
		$result = $TestModel->find('list', array(
			'conditions' => array('User.user' => 'mariano'),
			'recursive' => 0
		));
		$expected = array(
			1 => 'First Article',
			3 => 'Third Article'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test find('list') method
 *
 * @return void
 */
	public function testFindListZeroValue() {
		$this->loadFixtures('Article');

		$model = new Article();
		$model->displayField = 'title';
		$model->save(array(
			'title' => 'Zeroth Article',
			'user_id' => 0,
			'published' => 'Y'
		));

		$result = $model->find('list', array(
			'fields' => array('title', 'user_id')
		));
		$expected = array(
			'Zeroth Article' => 0,
			'First Article' => 1,
			'Second Article' => 3,
			'Third Article' => 1,
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that find(list) works with array conditions that have only one element.
 *
 * @return void
 */
	public function testFindListArrayCondition() {
		$this->loadFixtures('User');
		$TestModel = new User();
		$TestModel->cacheQueries = false;

		$result = $TestModel->find('list', array(
			'fields' => array('id', 'user'),
			'conditions' => array('User.id' => array(3)),
		));
		$expected = array(
			3 => 'larry'
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('list', array(
			'fields' => array('id', 'user'),
			'conditions' => array('User.user' => array('larry')),
		));
		$this->assertEquals($expected, $result);
	}

/**
 * testFindField method
 *
 * @return void
 */
	public function testFindField() {
		$this->loadFixtures('User');
		$TestModel = new User();

		$TestModel->id = 1;
		$result = $TestModel->field('user');
		$this->assertEquals('mariano', $result);

		$result = $TestModel->field('User.user');
		$this->assertEquals('mariano', $result);

		$TestModel->id = false;
		$result = $TestModel->field('user', array(
			'user' => 'mariano'
		));
		$this->assertEquals('mariano', $result);
		$TestModel->order = null;
		$result = $TestModel->field('COUNT(*) AS count', true);
		$this->assertEquals(4, $result);

		$result = $TestModel->field('COUNT(*)', true);
		$this->assertEquals(4, $result);

		$TestModel->id = null;
		$result = $TestModel->field('user', array(
			'user' => 'mariano'
		));
		$this->assertEquals('mariano', $result);
	}

/**
 * testFindUnique method
 *
 * @return void
 */
	public function testFindUnique() {
		$this->loadFixtures('User');
		$TestModel = new User();

		$this->assertFalse($TestModel->isUnique(array(
			'user' => 'nate'
		)));
		$TestModel->id = 2;
		$this->assertTrue($TestModel->isUnique(array(
			'user' => 'nate'
		)));
		$this->assertFalse($TestModel->isUnique(array(
			'user' => 'nate',
			'password' => '5f4dcc3b5aa765d61d8327deb882cf99'
		)));
	}

/**
 * test find('count') method
 *
 * @return void
 */
	public function testFindCount() {
		$this->loadFixtures('User', 'Article', 'Comment', 'Tag', 'ArticlesTag');

		$TestModel = new User();
		$this->db->getLog(false, true);
		$result = $TestModel->find('count');
		$this->assertEquals(4, $result);

		$this->db->getLog(false, true);
		$fullDebug = $this->db->fullDebug;
		$this->db->fullDebug = true;
		$TestModel->order = 'User.id';
		$result = $TestModel->find('count');
		$this->db->fullDebug = $fullDebug;
		$this->assertEquals(4, $result);

		$log = $this->db->getLog();
		$this->assertTrue(isset($log['log'][0]['query']));
		$this->assertDoesNotMatchRegularExpression('/ORDER\s+BY/', $log['log'][0]['query']);

		$Article = new Article();
		$Article->order = null;
		$Article->recursive = -1;
		$expected = count($Article->find('all', array(
			'fields' => array('Article.user_id'),
			'group' => 'Article.user_id')
		));
		$result = $Article->find('count', array('group' => array('Article.user_id')));
		$this->assertEquals($expected, $result);

		$expected = count($Article->find('all', array(
			'fields' => array('Article.user_id'),
			'conditions' => array('Article.user_id' => 1),
			'group' => 'Article.user_id')
		));
		$result = $Article->find('count', array(
			'conditions' => array('Article.user_id' => 1),
			'group' => array('Article.user_id'),
		));
		$this->assertEquals($expected, $result);
	}

	public function testFindCountI18nConditions() {
		$this->loadFixtures('TranslateArticle', 'TranslatedArticle', 'User');
		$TestModel = new TranslatedArticle();
		$TestModel->cacheQueries = false;
		$TestModel->locale = 'eng';
		$options = array(
			'recursive' => 0,
			'conditions' => array(
				'NOT' => array('I18n__title.content' => ''),
			),
			'limit' => 2,
		);
		$result = $TestModel->find('count', $options);
		$this->assertEquals(3, $result);
	}

/**
 * Test that find('first') does not use the id set to the object.
 *
 * @return void
 */
	public function testFindFirstNoIdUsed() {
		$this->loadFixtures('Project');

		$Project = new Project();
		$Project->id = 3;
		$result = $Project->find('first');

		$this->assertEquals('Project 1', $result['Project']['name'], 'Wrong record retrieved');
	}

/**
 * test find with COUNT(DISTINCT field)
 *
 * @return void
 */
	public function testFindCountDistinct() {
		$this->skipIf($this->db instanceof Sqlite, 'SELECT COUNT(DISTINCT field) is not compatible with SQLite.');
		$this->skipIf($this->db instanceof Sqlserver, 'This test is not compatible with SQL Server.');

		$this->loadFixtures('Project', 'Thread');
		$TestModel = new Project();
		$TestModel->create(array('name' => 'project')) && $TestModel->save();
		$TestModel->create(array('name' => 'project')) && $TestModel->save();
		$TestModel->create(array('name' => 'project')) && $TestModel->save();

		$result = $TestModel->find('count', array('fields' => 'DISTINCT name'));
		$this->assertEquals(4, $result);
	}

/**
 * Test find(count) with DboSource::expression
 *
 * @return void
 */
	public function testFindCountWithDbExpressions() {
		$this->skipIf($this->db instanceof Postgres, 'testFindCountWithDbExpressions is not compatible with Postgres.');

		$this->loadFixtures('Project', 'Thread');
		$db = ConnectionManager::getDataSource('test');
		$TestModel = new Project();

		$result = $TestModel->find('count', array('conditions' => array(
			$db->expression('Project.name = \'Project 3\'')
		)));
		$this->assertEquals(1, $result);

		$result = $TestModel->find('count', array('conditions' => array(
			'Project.name' => $db->expression('\'Project 3\'')
		)));
		$this->assertEquals(1, $result);
	}

/**
 * Test 'order' with DboSource::expression
 */
	public function testOrderWithDbExpressions() {
		$this->loadFixtures('User');

		$User = new User();

		$results = $User->find('all', array(
			'fields' => array('id'),
			'recursive' => -1,
			'order' => $this->db->expression('CASE id WHEN 4 THEN 0 ELSE id END'),
		));

		$expected = array(
			array(
				'User' => array('id' => 4),
			),
			array(
				'User' => array('id' => 1),
			),
			array(
				'User' => array('id' => 2),
			),
			array(
				'User' => array('id' => 3),
			),
		);

		$this->assertEquals($expected, $results);
	}

/**
 * testFindMagic method
 *
 * @return void
 */
	public function testFindMagic() {
		$this->loadFixtures('User', 'Comment', 'Article');
		$TestModel = new User();

		$result = $TestModel->findByUser('mariano');
		$expected = array(
			'User' => array(
				'id' => '1',
				'user' => 'mariano',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				'created' => '2007-03-17 01:16:23',
				'updated' => '2007-03-17 01:18:31'
		));
		$this->assertEquals($expected, $result);

		$result = $TestModel->findByPassword('5f4dcc3b5aa765d61d8327deb882cf99');
		$expected = array('User' => array(
			'id' => '1',
			'user' => 'mariano',
			'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
			'created' => '2007-03-17 01:16:23',
			'updated' => '2007-03-17 01:18:31'
		));
		$this->assertEquals($expected, $result);

		$Comment = new Comment();
		$Comment->recursive = -1;
		$results = $Comment->findAllByUserId(1);
		$expected = array(
			array(
				'Comment' => array(
					'id' => 3,
					'article_id' => 1,
					'user_id' => 1,
					'comment' => 'Third Comment for First Article',
					'published' => 'Y',
					'created' => '2007-03-18 10:49:23',
					'updated' => '2007-03-18 10:51:31'
				)
			),
			array(
				'Comment' => array(
					'id' => 4,
					'article_id' => 1,
					'user_id' => 1,
					'comment' => 'Fourth Comment for First Article',
					'published' => 'N',
					'created' => '2007-03-18 10:51:23',
					'updated' => '2007-03-18 10:53:31'
				)
			),
			array(
				'Comment' => array(
					'id' => 5,
					'article_id' => 2,
					'user_id' => 1,
					'comment' => 'First Comment for Second Article',
					'published' => 'Y',
					'created' => '2007-03-18 10:53:23',
					'updated' => '2007-03-18 10:55:31'
				)
			)
		);
		$this->assertEquals($expected, $results);

		$results = $Comment->findAllByUserIdAndPublished(1, 'Y');
		$expected = array(
			array(
				'Comment' => array(
					'id' => 3,
					'article_id' => 1,
					'user_id' => 1,
					'comment' => 'Third Comment for First Article',
					'published' => 'Y',
					'created' => '2007-03-18 10:49:23',
					'updated' => '2007-03-18 10:51:31'
				)
			),
			array(
				'Comment' => array(
					'id' => 5,
					'article_id' => 2,
					'user_id' => 1,
					'comment' => 'First Comment for Second Article',
					'published' => 'Y',
					'created' => '2007-03-18 10:53:23',
					'updated' => '2007-03-18 10:55:31'
				)
			)
		);
		$this->assertEquals($expected, $results);

		$Article = new CustomArticle();
		$Article->recursive = -1;
		$results = $Article->findListByUserId(1);
		$expected = array(
			1 => 'First Article',
			3 => 'Third Article'
		);
		$this->assertEquals($expected, $results);

		$results = $Article->findPublishedByUserId(1);
		$expected = array(
			array(
				'CustomArticle' => array(
					'id' => 1,
					'user_id' => 1,
					'title' => 'First Article',
					'body' => 'First Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				)
			),
			array(
				'CustomArticle' => array(
					'id' => 3,
					'user_id' => 1,
					'title' => 'Third Article',
					'body' => 'Third Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
				)
			)
		);
		$this->assertEquals($expected, $results);

		$results = $Article->findUnPublishedByUserId(1);
		$expected = array();
		$this->assertEquals($expected, $results);
	}

/**
 * testRead method
 *
 * @return void
 */
	public function testRead() {
		$this->loadFixtures('User', 'Article');
		$TestModel = new User();

		$result = $TestModel->read();
		$this->assertFalse($result);

		$TestModel->id = 2;
		$result = $TestModel->read();
		$expected = array(
			'User' => array(
				'id' => '2',
				'user' => 'nate',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				'created' => '2007-03-17 01:18:23',
				'updated' => '2007-03-17 01:20:31'
		));
		$this->assertEquals($expected, $result);

		$result = $TestModel->read(null, 2);
		$expected = array(
			'User' => array(
				'id' => '2',
				'user' => 'nate',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				'created' => '2007-03-17 01:18:23',
				'updated' => '2007-03-17 01:20:31'
		));
		$this->assertEquals($expected, $result);

		$TestModel->id = 2;
		$result = $TestModel->read(array('id', 'user'));
		$expected = array('User' => array('id' => '2', 'user' => 'nate'));
		$this->assertEquals($expected, $result);

		$result = $TestModel->read('id, user', 2);
		$expected = array(
			'User' => array(
				'id' => '2',
				'user' => 'nate'
		));
		$this->assertEquals($expected, $result);

		$result = $TestModel->bindModel(array('hasMany' => array('Article')));
		$this->assertTrue($result);

		$TestModel->id = 1;
		$result = $TestModel->read('id, user');
		$expected = array(
			'User' => array(
				'id' => '1',
				'user' => 'mariano'
			),
			'Article' => array(
				array(
					'id' => '1',
					'user_id' => '1',
					'title' => 'First Article',
					'body' => 'First Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				array(
					'id' => '3',
					'user_id' => '1',
					'title' => 'Third Article',
					'body' => 'Third Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
		)));
		$this->assertEquals($expected, $result);
	}

/**
 * testRecursiveRead method
 *
 * @return void
 */
	public function testRecursiveRead() {
		$this->loadFixtures(
			'User',
			'Article',
			'Comment',
			'Tag',
			'ArticlesTag',
			'Featured',
			'ArticleFeatured'
		);
		$TestModel = new User();

		$result = $TestModel->bindModel(array('hasMany' => array('Article')), false);
		$this->assertTrue($result);

		$TestModel->recursive = 0;
		$result = $TestModel->read('id, user', 1);
		$expected = array(
			'User' => array('id' => '1', 'user' => 'mariano'),
		);
		$this->assertEquals($expected, $result);

		$TestModel->recursive = 1;
		$result = $TestModel->read('id, user', 1);
		$expected = array(
			'User' => array(
				'id' => '1',
				'user' => 'mariano'
			),
			'Article' => array(
				array(
					'id' => '1',
					'user_id' => '1',
					'title' => 'First Article',
					'body' => 'First Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				array(
					'id' => '3',
					'user_id' => '1',
					'title' => 'Third Article',
					'body' => 'Third Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
		)));
		$this->assertEquals($expected, $result);

		$TestModel->recursive = 2;
		$result = $TestModel->read('id, user', 3);
		$expected = array(
			'User' => array(
				'id' => '3',
				'user' => 'larry'
			),
			'Article' => array(
				array(
					'id' => '2',
					'user_id' => '3',
					'title' => 'Second Article',
					'body' => 'Second Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31',
					'User' => array(
						'id' => '3',
						'user' => 'larry',
						'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:20:23',
						'updated' => '2007-03-17 01:22:31'
					),
					'Comment' => array(
						array(
							'id' => '5',
							'article_id' => '2',
							'user_id' => '1',
							'comment' => 'First Comment for Second Article',
							'published' => 'Y',
							'created' => '2007-03-18 10:53:23',
							'updated' => '2007-03-18 10:55:31'
						),
						array(
							'id' => '6',
							'article_id' => '2',
							'user_id' => '2',
							'comment' => 'Second Comment for Second Article',
							'published' => 'Y',
							'created' => '2007-03-18 10:55:23',
							'updated' => '2007-03-18 10:57:31'
					)),
					'Tag' => array(
						array(
							'id' => '1',
							'tag' => 'tag1',
							'created' => '2007-03-18 12:22:23',
							'updated' => '2007-03-18 12:24:31'
						),
						array(
							'id' => '3',
							'tag' => 'tag3',
							'created' => '2007-03-18 12:26:23',
							'updated' => '2007-03-18 12:28:31'
		)))));
		$this->assertEquals($expected, $result);
	}

	public function testRecursiveFindAll() {
		$this->loadFixtures(
			'User',
			'Article',
			'Comment',
			'Tag',
			'ArticlesTag',
			'Attachment',
			'ArticleFeatured',
			'ArticleFeaturedsTags',
			'Featured',
			'Category'
		);
		$TestModel = new Article();

		$result = $TestModel->find('all', array('conditions' => array('Article.user_id' => 1)));
		$expected = array(
			array(
				'Article' => array(
					'id' => '1',
					'user_id' => '1',
					'title' => 'First Article',
					'body' => 'First Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'User' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array(
					array(
						'id' => '1',
						'article_id' => '1',
						'user_id' => '2',
						'comment' => 'First Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:45:23',
						'updated' => '2007-03-18 10:47:31'
					),
					array(
						'id' => '2',
						'article_id' => '1',
						'user_id' => '4',
						'comment' => 'Second Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:47:23',
						'updated' => '2007-03-18 10:49:31'
					),
					array(
						'id' => '3',
						'article_id' => '1',
						'user_id' => '1',
						'comment' => 'Third Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:49:23',
						'updated' => '2007-03-18 10:51:31'
					),
					array(
						'id' => '4',
						'article_id' => '1',
						'user_id' => '1',
						'comment' => 'Fourth Comment for First Article',
						'published' => 'N',
						'created' => '2007-03-18 10:51:23',
						'updated' => '2007-03-18 10:53:31'
					)
				),
				'Tag' => array(
					array(
						'id' => '1',
						'tag' => 'tag1',
						'created' => '2007-03-18 12:22:23',
						'updated' => '2007-03-18 12:24:31'
					),
					array(
						'id' => '2',
						'tag' => 'tag2',
						'created' => '2007-03-18 12:24:23',
						'updated' => '2007-03-18 12:26:31'
			))),
			array(
				'Article' => array(
					'id' => '3',
					'user_id' => '1',
					'title' => 'Third Article',
					'body' => 'Third Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array(),
				'Tag' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all', array(
			'conditions' => array('Article.user_id' => 3),
			'limit' => 1,
			'recursive' => 2
		));

		$expected = array(
			array(
				'Article' => array(
					'id' => '2',
					'user_id' => '3',
					'title' => 'Second Article',
					'body' => 'Second Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				),
				'User' => array(
					'id' => '3',
					'user' => 'larry',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23',
					'updated' => '2007-03-17 01:22:31'
				),
				'Comment' => array(
					array(
						'id' => '5',
						'article_id' => '2',
						'user_id' => '1',
						'comment' => 'First Comment for Second Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:53:23',
						'updated' => '2007-03-18 10:55:31',
						'Article' => array(
							'id' => '2',
							'user_id' => '3',
							'title' => 'Second Article',
							'body' => 'Second Article Body',
							'published' => 'Y',
							'created' => '2007-03-18 10:41:23',
							'updated' => '2007-03-18 10:43:31'
						),
						'User' => array(
							'id' => '1',
							'user' => 'mariano',
							'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
							'created' => '2007-03-17 01:16:23',
							'updated' => '2007-03-17 01:18:31'
						),
						'Attachment' => array(
							'id' => '1',
							'comment_id' => 5,
							'attachment' => 'attachment.zip',
							'created' => '2007-03-18 10:51:23',
							'updated' => '2007-03-18 10:53:31'
						)
					),
					array(
						'id' => '6',
						'article_id' => '2',
						'user_id' => '2',
						'comment' => 'Second Comment for Second Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:55:23',
						'updated' => '2007-03-18 10:57:31',
						'Article' => array(
							'id' => '2',
							'user_id' => '3',
							'title' => 'Second Article',
							'body' => 'Second Article Body',
							'published' => 'Y',
							'created' => '2007-03-18 10:41:23',
							'updated' => '2007-03-18 10:43:31'
						),
						'User' => array(
							'id' => '2',
							'user' => 'nate',
							'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
							'created' => '2007-03-17 01:18:23',
							'updated' => '2007-03-17 01:20:31'
						),
						'Attachment' => array()
					)
				),
				'Tag' => array(
					array(
						'id' => '1',
						'tag' => 'tag1',
						'created' => '2007-03-18 12:22:23',
						'updated' => '2007-03-18 12:24:31'
					),
					array(
						'id' => '3',
						'tag' => 'tag3',
						'created' => '2007-03-18 12:26:23',
						'updated' => '2007-03-18 12:28:31'
		))));

		$this->assertEquals($expected, $result);

		$Featured = new Featured();

		$Featured->recursive = 2;
		$Featured->bindModel(array(
			'belongsTo' => array(
				'ArticleFeatured' => array(
					'conditions' => "ArticleFeatured.published = 'Y'",
					'fields' => 'id, title, user_id, published'
				)
			)
		));

		$Featured->ArticleFeatured->unbindModel(array(
			'hasMany' => array('Attachment', 'Comment'),
			'hasAndBelongsToMany' => array('Tag'))
		);

		$orderBy = 'ArticleFeatured.id ASC';
		$result = $Featured->find('all', array(
			'order' => $orderBy, 'limit' => 3
		));

		$expected = array(
			array(
				'Featured' => array(
					'id' => '1',
					'article_featured_id' => '1',
					'category_id' => '1',
					'published_date' => '2007-03-31 10:39:23',
					'end_date' => '2007-05-15 10:39:23',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'ArticleFeatured' => array(
					'id' => '1',
					'title' => 'First Article',
					'user_id' => '1',
					'published' => 'Y',
					'User' => array(
						'id' => '1',
						'user' => 'mariano',
						'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:16:23',
						'updated' => '2007-03-17 01:18:31'
					),
					'Category' => array(),
					'Featured' => array(
						'id' => '1',
						'article_featured_id' => '1',
						'category_id' => '1',
						'published_date' => '2007-03-31 10:39:23',
						'end_date' => '2007-05-15 10:39:23',
						'created' => '2007-03-18 10:39:23',
						'updated' => '2007-03-18 10:41:31'
				)),
				'Category' => array(
					'id' => '1',
					'parent_id' => '0',
					'name' => 'Category 1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				)),
			array(
				'Featured' => array(
					'id' => '2',
					'article_featured_id' => '2',
					'category_id' => '1',
					'published_date' => '2007-03-31 10:39:23',
					'end_date' => '2007-05-15 10:39:23',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'ArticleFeatured' => array(
					'id' => '2',
					'title' => 'Second Article',
					'user_id' => '3',
					'published' => 'Y',
					'User' => array(
						'id' => '3',
						'user' => 'larry',
						'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:20:23',
						'updated' => '2007-03-17 01:22:31'
					),
					'Category' => array(),
					'Featured' => array(
						'id' => '2',
						'article_featured_id' => '2',
						'category_id' => '1',
						'published_date' => '2007-03-31 10:39:23',
						'end_date' => '2007-05-15 10:39:23',
						'created' => '2007-03-18 10:39:23',
						'updated' => '2007-03-18 10:41:31'
				)),
				'Category' => array(
					'id' => '1',
					'parent_id' => '0',
					'name' => 'Category 1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
		)));
		$this->assertEquals($expected, $result);
	}

/**
 * testRecursiveFindAllWithLimit method
 *
 * @return void
 */
	public function testRecursiveFindAllWithLimit() {
		$this->loadFixtures('Article', 'User', 'Tag', 'ArticlesTag', 'Comment', 'Attachment');
		$TestModel = new Article();

		$TestModel->hasMany['Comment']['limit'] = 2;

		$result = $TestModel->find('all', array(
			'conditions' => array('Article.user_id' => 1)
		));
		$expected = array(
			array(
				'Article' => array(
					'id' => '1',
					'user_id' => '1',
					'title' => 'First Article',
					'body' => 'First Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'User' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array(
					array(
						'id' => '1',
						'article_id' => '1',
						'user_id' => '2',
						'comment' => 'First Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:45:23',
						'updated' => '2007-03-18 10:47:31'
					),
					array(
						'id' => '2',
						'article_id' => '1',
						'user_id' => '4',
						'comment' => 'Second Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:47:23',
						'updated' => '2007-03-18 10:49:31'
					),
				),
				'Tag' => array(
					array(
						'id' => '1',
						'tag' => 'tag1',
						'created' => '2007-03-18 12:22:23',
						'updated' => '2007-03-18 12:24:31'
					),
					array(
						'id' => '2',
						'tag' => 'tag2',
						'created' => '2007-03-18 12:24:23',
						'updated' => '2007-03-18 12:26:31'
			))),
			array(
				'Article' => array(
					'id' => '3',
					'user_id' => '1',
					'title' => 'Third Article',
					'body' => 'Third Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array(),
				'Tag' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$TestModel->hasMany['Comment']['limit'] = 1;

		$result = $TestModel->find('all', array(
			'conditions' => array('Article.user_id' => 3),
			'limit' => 1,
			'recursive' => 2
		));
		$expected = array(
			array(
				'Article' => array(
					'id' => '2',
					'user_id' => '3',
					'title' => 'Second Article',
					'body' => 'Second Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				),
				'User' => array(
					'id' => '3',
					'user' => 'larry',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23',
					'updated' => '2007-03-17 01:22:31'
				),
				'Comment' => array(
					array(
						'id' => '5',
						'article_id' => '2',
						'user_id' => '1',
						'comment' => 'First Comment for Second Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:53:23',
						'updated' => '2007-03-18 10:55:31',
						'Article' => array(
							'id' => '2',
							'user_id' => '3',
							'title' => 'Second Article',
							'body' => 'Second Article Body',
							'published' => 'Y',
							'created' => '2007-03-18 10:41:23',
							'updated' => '2007-03-18 10:43:31'
						),
						'User' => array(
							'id' => '1',
							'user' => 'mariano',
							'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
							'created' => '2007-03-17 01:16:23',
							'updated' => '2007-03-17 01:18:31'
						),
						'Attachment' => array(
							'id' => '1',
							'comment_id' => 5,
							'attachment' => 'attachment.zip',
							'created' => '2007-03-18 10:51:23',
							'updated' => '2007-03-18 10:53:31'
						)
					)
				),
				'Tag' => array(
					array(
						'id' => '1',
						'tag' => 'tag1',
						'created' => '2007-03-18 12:22:23',
						'updated' => '2007-03-18 12:24:31'
					),
					array(
						'id' => '3',
						'tag' => 'tag3',
						'created' => '2007-03-18 12:26:23',
						'updated' => '2007-03-18 12:28:31'
					)
				)
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Testing availability of $this->findQueryType in Model callbacks
 *
 * @return void
 */
	public function testFindQueryTypeInCallbacks() {
		$this->loadFixtures('Comment');
		$Comment = new AgainModifiedComment();
		$comments = $Comment->find('all');
		$this->assertEquals('all', $comments[0]['Comment']['querytype']);
		$comments = $Comment->find('first');
		$this->assertEquals('first', $comments['Comment']['querytype']);
	}

/**
 * testVirtualFields()
 *
 * Test correct fetching of virtual fields
 * currently is not possible to do Relation.virtualField
 *
 * @return void
 */
	public function testVirtualFields() {
		$this->loadFixtures('Post', 'Author');
		$Post = ClassRegistry::init('Post');
		$Post->virtualFields = array('two' => "1 + 1");
		$result = $Post->find('first');
		$this->assertEquals(2, $result['Post']['two']);

		// SQL Server does not support operators in expressions
		if (!($this->db instanceof Sqlserver)) {
			$Post->Author->virtualFields = array('false' => '1 = 2');
			$result = $Post->find('first');
			$this->assertEquals(2, $result['Post']['two']);
			$this->assertFalse((bool)$result['Author']['false']);
		}

		$result = $Post->find('first', array('fields' => array('author_id')));
		$this->assertFalse(isset($result['Post']['two']));
		$this->assertFalse(isset($result['Author']['false']));

		$result = $Post->find('first', array('fields' => array('author_id', 'two')));
		$this->assertEquals(2, $result['Post']['two']);
		$this->assertFalse(isset($result['Author']['false']));

		$result = $Post->find('first', array('fields' => array('two')));
		$this->assertEquals(2, $result['Post']['two']);

		$Post->id = 1;
		$result = $Post->field('two');
		$this->assertEquals(2, $result);

		$result = $Post->find('first', array(
			'conditions' => array('two' => 2),
			'limit' => 1
		));
		$this->assertEquals(2, $result['Post']['two']);

		$result = $Post->find('first', array(
			'conditions' => array('two <' => 3),
			'limit' => 1
		));
		$this->assertEquals(2, $result['Post']['two']);

		$result = $Post->find('first', array(
			'conditions' => array('NOT' => array('two >' => 3)),
			'limit' => 1
		));
		$this->assertEquals(2, $result['Post']['two']);

		$dbo = $Post->getDataSource();
		$Post->virtualFields = array('other_field' => 'Post.id + 1');
		$result = $Post->find('first', array(
			'conditions' => array('other_field' => 3),
			'limit' => 1
		));
		$this->assertEquals(2, $result['Post']['id']);
		$Post->order = null;

		$Post->virtualFields = array('other_field' => 'Post.id + 1');
		$result = $Post->find('all', array(
			'fields' => array($dbo->calculate($Post, 'max', array('other_field')))
		));
		$this->assertEquals(4, $result[0][0]['other_field']);

		ClassRegistry::flush();
		$Writing = ClassRegistry::init(array('class' => 'Post', 'alias' => 'Writing'));
		$Writing->virtualFields = array('two' => "1 + 1");
		$result = $Writing->find('first');
		$this->assertEquals(2, $result['Writing']['two']);

		$Post->create();
		$Post->virtualFields = array('other_field' => 'COUNT(Post.id) + 1');
		$result = $Post->field('other_field');
		$this->assertEquals(4, $result);
	}

/**
 * Test virtualfields that contain subqueries get correctly
 * quoted allowing reserved words to be used.
 *
 * @return void
 */
	public function testVirtualFieldSubqueryReservedWords() {
		$this->loadFixtures('User');
		$user = ClassRegistry::init('User');
		$user->cacheMethods = false;
		$ds = $user->getDataSource();

		$sub = $ds->buildStatement(
			array(
				'fields' => array('Table.user'),
				'table' => $ds->fullTableName($user),
				'alias' => 'Table',
				'limit' => 1,
				'conditions' => array(
					"Table.id > 1"
				)
			),
			$user
		);
		$user->virtualFields = array(
			'sub_test' => $sub
		);

		$result = $user->find('first');
		$this->assertNotEmpty($result);
	}

/**
 * testVirtualFieldsOrder()
 *
 * Test correct order on virtual fields
 *
 * @return void
 */
	public function testVirtualFieldsOrder() {
		$this->loadFixtures('Post', 'Author');
		$Post = ClassRegistry::init('Post');
		$Post->virtualFields = array('other_field' => '10 - Post.id');
		$result = $Post->find('list', array('order' => array('Post.other_field' => 'ASC')));
		$expected = array(
			'3' => 'Third Post',
			'2' => 'Second Post',
			'1' => 'First Post'
		);
		$this->assertEquals($expected, $result);

		$result = $Post->find('list', array('order' => array('Post.other_field' => 'DESC')));
		$expected = array(
			'1' => 'First Post',
			'2' => 'Second Post',
			'3' => 'Third Post'
		);
		$this->assertEquals($expected, $result);

		$Post->Author->virtualFields = array('joined' => 'Post.id * Author.id');
		$result = $Post->find('all', array(
			'order' => array('Post.id' => 'ASC')
		));
		$result = Hash::extract($result, '{n}.Author.joined');
		$expected = array(1, 6, 3);
		$this->assertEquals($expected, $result);

		$result = $Post->find('all', array('order' => array('Author.joined' => 'ASC')));
		$result = Hash::extract($result, '{n}.Author.joined');
		$expected = array(1, 3, 6);
		$this->assertEquals($expected, $result);

		$result = $Post->find('all', array('order' => array('Author.joined' => 'DESC')));
		$result = Hash::extract($result, '{n}.Author.joined');
		$expected = array(6, 3, 1);
		$this->assertEquals($expected, $result);
	}

	public function testVirtualFieldsMysqlGroup() {
		$this->skipIf(!($this->db instanceof Mysql), 'The rest of virtualFields test only compatible with Mysql.');
		$this->skipIfIsStrictGroupBy();
		$this->loadFixtures('Post');
		$Post = ClassRegistry::init('Post');
		$Post->create();
		$Post->virtualFields = array(
			'low_title' => 'lower(Post.title)',
			'unique_test_field' => 'COUNT(Post.id)',
		);
		$expectation = array(
			'Post' => array(
				'low_title' => 'first post',
				'unique_test_field' => 1,
			),
		);
		$result = $Post->find('first', array(
			'fields' => array_keys($Post->virtualFields),
			'group' => array('low_title'),
		));
		$this->assertEquals($expectation, $result);
	}

/**
 * testVirtualFieldsMysql()
 *
 * Test correct fetching of virtual fields
 * currently is not possible to do Relation.virtualField
 *
 * @return void
 */
	public function testVirtualFieldsMysql() {
		$this->skipIf(!($this->db instanceof Mysql), 'The rest of virtualFields test only compatible with Mysql.');
		$this->loadFixtures('Author');
		$Author = ClassRegistry::init('Author');
		$Author->virtualFields = array(
			'full_name' => 'CONCAT(Author.user, " ", Author.id)'
		);
		$result = $Author->find('first', array(
			'conditions' => array('Author.user' => 'mariano'),
			'fields' => array('Author.password', 'Author.full_name'),
			'recursive' => -1
		));
		$this->assertTrue(isset($result['Author']['full_name']));
		$result = $Author->find('first', array(
			'conditions' => array('Author.user' => 'mariano'),
			'fields' => array('Author.full_name', 'Author.password'),
			'recursive' => -1
		));
		$this->assertTrue(isset($result['Author']['full_name']));
	}

/**
 * test that virtual fields work when they don't contain functions.
 *
 * @return void
 */
	public function testVirtualFieldAsAString() {
		$this->loadFixtures('Post', 'Author');
		$Post = new Post();
		$Post->virtualFields = array(
			'writer' => 'Author.user'
		);
		$result = $Post->find('first');
		$this->assertTrue(isset($result['Post']['writer']), 'virtual field not fetched %s');
	}

/**
 * test that isVirtualField will accept both aliased and non aliased fieldnames
 *
 * @return void
 */
	public function testIsVirtualField() {
		$this->loadFixtures('Post');
		$Post = ClassRegistry::init('Post');
		$Post->virtualFields = array('other_field' => 'COUNT(Post.id) + 1');

		$this->assertTrue($Post->isVirtualField('other_field'));
		$this->assertTrue($Post->isVirtualField('Post.other_field'));
		$this->assertFalse($Post->isVirtualField('Comment.other_field'), 'Other models should not match.');
		$this->assertFalse($Post->isVirtualField('id'));
		$this->assertFalse($Post->isVirtualField('Post.id'));
		$this->assertFalse($Post->isVirtualField(array()));
	}

/**
 * test that getting virtual fields works with and without model alias attached
 *
 * @return void
 */
	public function testGetVirtualField() {
		$this->loadFixtures('Post');
		$Post = ClassRegistry::init('Post');
		$Post->virtualFields = array('other_field' => 'COUNT(Post.id) + 1');

		$this->assertEquals($Post->getVirtualField('other_field'), $Post->virtualFields['other_field']);
		$this->assertEquals($Post->getVirtualField('Post.other_field'), $Post->virtualFields['other_field']);
	}

/**
 * test that checks for error when NOT condition passed in key and a 1 element array value
 *
 * @return void
 */
	public function testNotInArrayWithOneValue() {
		$this->loadFixtures('Article');
		$Article = new Article();
		$Article->recursive = -1;

		$result = $Article->find(
			'all',
			array(
				'conditions' => array(
					'Article.id NOT' => array(1)
				)
			)
		);
		$this->assertTrue(is_array($result) && !empty($result));
	}

/**
 * test to assert that != in key together with a single element array will work
 *
 * @return void
 */
	public function testNotEqualsInArrayWithOneValue() {
		$this->loadFixtures('Article');
		$Article = new Article();
		$Article->recursive = -1;

		$result = $Article->find(
			'all',
			array(
				'conditions' => array(
					'Article.id !=' => array(1)
				)
			)
		);
		$this->assertTrue(is_array($result) && !empty($result));
	}

/**
 * test custom find method
 *
 * @return void
 */
	public function testfindCustom() {
		$this->loadFixtures('Article');
		$Article = new CustomArticle();
		$data = array('user_id' => 3, 'title' => 'Fourth Article', 'body' => 'Article Body, unpublished', 'published' => 'N');
		$Article->create($data);
		$Article->save(null, false);
		$this->assertEquals(4, $Article->id);

		$result = $Article->find('published');
		$this->assertEquals(3, count($result));

		$result = $Article->find('unPublished');
		$this->assertEquals(1, count($result));
	}

/**
 * test after find callback on related model
 *
 * @return void
 */
	public function testRelatedAfterFindCallback() {
		$this->loadFixtures('Something', 'SomethingElse', 'JoinThing');
		$Something = new Something();

		$Something->bindModel(array(
			'hasMany' => array(
				'HasMany' => array(
					'className' => 'JoinThing',
					'foreignKey' => 'something_id'
				)
			),
			'hasOne' => array(
				'HasOne' => array(
					'className' => 'JoinThing',
					'foreignKey' => 'something_id'
				)
			)
		));

		$results = $Something->find('all');

		$expected = array(
			array(
				'Something' => array(
					'id' => '1',
					'title' => 'First Post',
					'body' => 'First Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'HasOne' => array(
					'id' => '1',
					'something_id' => '1',
					'something_else_id' => '2',
					'doomed' => true,
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31',
					'afterFind' => 'Successfully added by AfterFind'
				),
				'HasMany' => array(
					array(
						'id' => '1',
						'something_id' => '1',
						'something_else_id' => '2',
						'doomed' => true,
						'created' => '2007-03-18 10:39:23',
						'updated' => '2007-03-18 10:41:31',
						'afterFind' => 'Successfully added by AfterFind'
					)
				),
				'SomethingElse' => array(
					array(
						'id' => '2',
						'title' => 'Second Post',
						'body' => 'Second Post Body',
						'published' => 'Y',
						'created' => '2007-03-18 10:41:23',
						'updated' => '2007-03-18 10:43:31',
						'afterFind' => 'Successfully added by AfterFind',
						'JoinThing' => array(
							'doomed' => true,
							'something_id' => '1',
							'something_else_id' => '2',
							'afterFind' => 'Successfully added by AfterFind'
						)
					)
				)
			),
			array(
				'Something' => array(
					'id' => '2',
					'title' => 'Second Post',
					'body' => 'Second Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				),
				'HasOne' => array(
					'id' => '2',
					'something_id' => '2',
					'something_else_id' => '3',
					'doomed' => false,
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31',
					'afterFind' => 'Successfully added by AfterFind'
				),
				'HasMany' => array(
					array(
						'id' => '2',
						'something_id' => '2',
						'something_else_id' => '3',
						'doomed' => false,
						'created' => '2007-03-18 10:41:23',
						'updated' => '2007-03-18 10:43:31',
						'afterFind' => 'Successfully added by AfterFind'
					)
				),
				'SomethingElse' => array(
					array(
						'id' => '3',
						'title' => 'Third Post',
						'body' => 'Third Post Body',
						'published' => 'Y',
						'created' => '2007-03-18 10:43:23',
						'updated' => '2007-03-18 10:45:31',
						'afterFind' => 'Successfully added by AfterFind',
						'JoinThing' => array(
							'doomed' => false,
							'something_id' => '2',
							'something_else_id' => '3',
							'afterFind' => 'Successfully added by AfterFind'
						)
					)
				)
			),
			array(
				'Something' => array(
					'id' => '3',
					'title' => 'Third Post',
					'body' => 'Third Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
				),
				'HasOne' => array(
					'id' => '3',
					'something_id' => '3',
					'something_else_id' => '1',
					'doomed' => true,
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31',
					'afterFind' => 'Successfully added by AfterFind'
				),
				'HasMany' => array(
					array(
						'id' => '3',
						'something_id' => '3',
						'something_else_id' => '1',
						'doomed' => true,
						'created' => '2007-03-18 10:43:23',
						'updated' => '2007-03-18 10:45:31',
						'afterFind' => 'Successfully added by AfterFind'
					)
				),
				'SomethingElse' => array(
					array(
						'id' => '1',
						'title' => 'First Post',
						'body' => 'First Post Body',
						'published' => 'Y',
						'created' => '2007-03-18 10:39:23',
						'updated' => '2007-03-18 10:41:31',
						'afterFind' => 'Successfully added by AfterFind',
						'JoinThing' => array(
							'doomed' => true,
							'something_id' => '3',
							'something_else_id' => '1',
							'afterFind' => 'Successfully added by AfterFind'
						)
					)
				)
			)
		);
		$this->assertEquals($expected, $results, 'Model related with has* afterFind callback fails');

		$JoinThing = new JoinThing();
		$JoinThing->unbindModel(array(
			'belongsTo' => array(
				'Something'
			)
		));
		$results = $JoinThing->find('all');

		$expected = array(
			array(
				'JoinThing' => array(
					'id' => '1',
					'something_id' => '1',
					'something_else_id' => '2',
					'doomed' => true,
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31',
					'afterFind' => 'Successfully added by AfterFind'
				),
				'SomethingElse' => array(
					'id' => '2',
					'title' => 'Second Post',
					'body' => 'Second Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31',
					'afterFind' => 'Successfully added by AfterFind'
				)
			),
			array(
				'JoinThing' => array(
					'id' => '2',
					'something_id' => '2',
					'something_else_id' => '3',
					'doomed' => false,
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31',
					'afterFind' => 'Successfully added by AfterFind'
				),
				'SomethingElse' => array(
					'id' => '3',
					'title' => 'Third Post',
					'body' => 'Third Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31',
					'afterFind' => 'Successfully added by AfterFind'
				)
			),
			array(
				'JoinThing' => array(
					'id' => '3',
					'something_id' => '3',
					'something_else_id' => '1',
					'doomed' => true,
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31',
					'afterFind' => 'Successfully added by AfterFind'
				),
				'SomethingElse' => array(
					'id' => '1',
					'title' => 'First Post',
					'body' => 'First Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31',
					'afterFind' => 'Successfully added by AfterFind'
				)
			)
		);
		$this->assertEquals($expected, $results, 'Model related with belongsTo afterFind callback fails');
	}

/**
 * Pull out the username from a result set.
 *
 * @param array $result The results.
 * @return string The username.
 */
	public static function extractUserNameFromQueryResult(array $result) {
		return isset($result[0][0]) ? $result[0][0]['user'] : $result[0]['u']['user'];
	}

/**
 * Test that query() doesn't override the 2nd argument with a default.
 *
 * @return void
 */
	public function testQueryRespectsCacheQueriesAsSecondArgument() {
		$model = new User();
		$model->save(array('user' => 'Chuck'));
		$userTableName = $this->db->fullTableName('users');

		$getUserNameFromDb = function ($cacheArgument) use ($model, $userTableName) {
			$query = sprintf('SELECT u.user FROM %s u WHERE id=%d', $userTableName, $model->id);
			$users = $model->query($query, $cacheArgument);
			return ModelReadTest::extractUserNameFromQueryResult($users);
		};

		$model->cacheQueries = true;
		$this->assertSame('Chuck', $getUserNameFromDb(true));
		$this->assertSame('Chuck', $getUserNameFromDb(false));

		$model->updateAll(array('User.user' => "'Sylvester'"), array('User.id' => $model->id));
		$model->cacheQueries = false;
		$this->assertSame('Chuck', $getUserNameFromDb(true));
		$this->assertSame('Sylvester', $getUserNameFromDb(false));
	}

/**
 * Test that query() doesn't override the cache param in the 3nd argument
 * with a default.
 *
 * @return void
 */
	public function testQueryRespectsCacheQueriesAsThirdArgument() {
		$model = new User();
		$model->save(array('user' => 'Chuck'));
		$userTableName = $this->db->fullTableName('users');

		$getUserNameFromDb = function ($cacheArgument) use ($model, $userTableName) {
			$query = sprintf('SELECT u.user FROM %s u WHERE id=?', $userTableName);
			$users = $model->query($query, array($model->id), $cacheArgument);
			return ModelReadTest::extractUserNameFromQueryResult($users);
		};

		$model->cacheQueries = true;
		$this->assertSame('Chuck', $getUserNameFromDb(true));
		$this->assertSame('Chuck', $getUserNameFromDb(false));

		$model->updateAll(array('User.user' => "'Sylvester'"), array('User.id' => $model->id));
		$model->cacheQueries = false;
		$this->assertSame('Chuck', $getUserNameFromDb(true));
		$this->assertSame('Sylvester', $getUserNameFromDb(false));
	}

/**
 * Test that query() uses the cacheQueries property when there is one argument.
 *
 * @return void
 */
	public function testQueryTakesModelCacheQueriesValueAsDefaultForOneArgument() {
		$model = new User();
		$model->save(array('user' => 'Chuck'));
		$userTableName = $this->db->fullTableName('users');

		$getUserNameFromDb = function () use ($model, $userTableName) {
			$query = sprintf('SELECT u.user FROM %s u WHERE id=%d', $userTableName, $model->id);
			$users = $model->query($query);
			return ModelReadTest::extractUserNameFromQueryResult($users);
		};

		$model->cacheQueries = true;
		$this->assertSame('Chuck', $getUserNameFromDb());
		$model->updateAll(array('User.user' => "'Sylvester'"), array('User.id' => $model->id));

		$this->assertSame('Chuck', $getUserNameFromDb());
		$model->cacheQueries = false;
		$this->assertSame('Sylvester', $getUserNameFromDb());
	}

/**
 * Test that query() uses the cacheQueries property when there are two arguments.
 *
 * @return void
 */
	public function testQueryTakesModelCacheQueriesValueAsDefaultForTwoArguments() {
		$model = new User();
		$model->save(array('user' => 'Chuck'));
		$userTableName = $this->db->fullTableName('users');

		$getUserNameFromDb = function () use ($model, $userTableName) {
			$query = sprintf('SELECT u.user FROM %s u WHERE id=?', $userTableName);
			$users = $model->query($query, array($model->id));
			return ModelReadTest::extractUserNameFromQueryResult($users);
		};

		$model->cacheQueries = true;
		$this->assertSame('Chuck', $getUserNameFromDb());

		$model->updateAll(array('User.user' => "'Sylvester'"), array('User.id' => $model->id));
		$this->assertSame('Chuck', $getUserNameFromDb());

		$model->cacheQueries = false;
		$this->assertSame('Sylvester', $getUserNameFromDb());
	}
}
