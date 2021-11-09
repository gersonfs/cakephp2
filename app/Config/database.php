<?php

class DATABASE_CONFIG
{
	private $identities = array(
		'mysql' => array(
			'datasource' => 'Database/Mysql',
			'host' => '127.0.0.1',
			'login' => 'root',
		),
		'sqlite' => array(
			'datasource' => 'Database/Sqlite',
			'database' => array(
				'default' => ':memory:',
				'test' => ':memory:',
				'test2' => '/tmp/cakephp_test2.db',
				'test_database_three' => '/tmp/cakephp_test3.db',
			),
		),
	);
	public $default = array(
		'persistent' => false,
		'host' => '',
		'login' => 'root',
		'password' => 'root',
		'database' => 'cakephp_test',
		'prefix' => '',
	);
	public $test = array(
		'persistent' => false,
		'host' => '',
		'login' => 'root',
		'password' => 'root',
		'database' => 'cakephp_test',
		'prefix' => '',
	);
	public $test2 = array(
		'persistent' => false,
		'host' => '',
		'login' => 'root',
		'password' => 'root',
		'database' => 'cakephp_test2',
		'prefix' => '',
	);
	public $test_database_three = array(
		'persistent' => false,
		'host' => '',
		'login' => 'root',
		'password' => 'root',
		'database' => 'cakephp_test3',
		'prefix' => '',
	);

	public function __construct()
	{
		$db = 'mysql';
		if (!empty($_SERVER['DB'])) {
			$db = $_SERVER['DB'];
		}
		foreach (array('default', 'test', 'test2', 'test_database_three') as $source) {
			$config = array_merge($this->{$source}, $this->identities[$db]);
			if (is_array($config['database'])) {
				$config['database'] = $config['database'][$source];
			}
			if (!empty($config['schema']) && is_array($config['schema'])) {
				$config['schema'] = $config['schema'][$source];
			}
			$this->{$source} = $config;
		}
	}
}
