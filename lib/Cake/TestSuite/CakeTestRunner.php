<?php
/**
 * TestRunner for CakePHP Test suite.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestResult;

if (!class_exists('PHPUnit_TextUI_TestRunner')) {
	//require_once 'PHPUnit/TextUI/TestRunner.php';
}
if (class_exists('SebastianBergmann\CodeCoverage\CodeCoverage')) {
	class_alias('SebastianBergmann\CodeCoverage\CodeCoverage', 'PHP_CodeCoverage');
	class_alias('SebastianBergmann\CodeCoverage\Report\Text', 'PHP_CodeCoverage_Report_Text');
	class_alias('SebastianBergmann\CodeCoverage\Report\PHP', 'PHP_CodeCoverage_Report_PHP');
	class_alias('SebastianBergmann\CodeCoverage\Report\Clover', 'PHP_CodeCoverage_Report_Clover');
	class_alias('SebastianBergmann\CodeCoverage\Report\Html\Facade', 'PHP_CodeCoverage_Report_HTML');
	class_alias('SebastianBergmann\CodeCoverage\Exception', 'PHP_CodeCoverage_Exception');
}

App::uses('CakeFixtureManager', 'TestSuite/Fixture');

/**
 * A custom test runner for CakePHP's use of PHPUnit.
 *
 * @package       Cake.TestSuite
 */
class CakeTestRunner {

/**
 * Lets us pass in some options needed for CakePHP's webrunner.
 *
 * @param mixed $loader The test suite loader
 * @param array $params list of options to be used for this run
 */

	private \PHPUnit\TextUI\TestRunner $runner;

	public function __construct($loader, $params) {
		$this->_params = $params;
		$this->runner = new \PHPUnit\TextUI\TestRunner($loader);
	}

/**
 * Actually run a suite of tests. Cake initializes fixtures here using the chosen fixture manager
 *
 * @param \PHPUnit\Framework\Test $suite The test suite to run
 * @param array $arguments The CLI arguments
 * @param bool $exit Exits by default or returns the results
 * This argument is ignored if >PHPUnit5.2.0
 * @return void
 */
	public function doRun(\PHPUnit\Framework\Test $suite, array $arguments = array(), bool $exit = true): TestResult {
		if (isset($arguments['printer'])) {
			static::$versionStringPrinted = true;
		}

		$fixture = $this->_getFixtureManager($arguments);
		$iterator = $suite->getIterator();
		if ($iterator instanceof RecursiveIterator) {
			$iterator = new RecursiveIteratorIterator($iterator);
		}
		foreach ($iterator as $test) {
			if ($test instanceof CakeTestCase) {
				$fixture->fixturize($test);
				$test->fixtureManager = $fixture;
			}
		}

		$return = $this->runner->doRun($suite, $arguments, [], $exit);
		$fixture->shutdown();
		return $return;
	}

// @codingStandardsIgnoreStart PHPUnit overrides don't match CakePHP
/**
 * Create the test result and splice on our code coverage reports.
 *
 * @return \PHPUnit\Framework\TestResult
 */
	protected function createTestResult(): TestResult {
		$result = new \PHPUnit\Framework\TestResult;
		if (!empty($this->_params['codeCoverage'])) {
			if (method_exists($result, 'collectCodeCoverageInformation')) {
				$result->collectCodeCoverageInformation(true);
			}
			if (method_exists($result, 'setCodeCoverage')) {
				$result->setCodeCoverage(new PHP_CodeCoverage());
			}
		}
		return $result;
	}
// @codingStandardsIgnoreEnd

/**
 * Get the fixture manager class specified or use the default one.
 *
 * @param array $arguments The CLI arguments.
 * @return mixed instance of a fixture manager.
 * @throws RuntimeException When fixture manager class cannot be loaded.
 */
	protected function _getFixtureManager($arguments) {
		if (!empty($arguments['fixtureManager'])) {
			App::uses($arguments['fixtureManager'], 'TestSuite');
			if (class_exists($arguments['fixtureManager'])) {
				return new $arguments['fixtureManager'];
			}
			throw new RuntimeException(__d('cake_dev', 'Could not find fixture manager %s.', $arguments['fixtureManager']));
		}
		App::uses('AppFixtureManager', 'TestSuite');
		if (class_exists('AppFixtureManager')) {
			return new AppFixtureManager();
		}
		return new CakeFixtureManager();
	}

	public function getTest(string $suiteClassName, array $data = [], $suffixes = '') : ?Test
	{
		$suiteClassFile = $this->_resolveTestFile($suiteClassName, $data);
		return $this->runner->getTest($suiteClassName, $suiteClassFile, $suffixes);
	}

	/**
	 * Convert path fragments used by CakePHP's test runner to absolute paths that can be fed to PHPUnit.
	 *
	 * @param string $filePath The file path to load.
	 * @param string $params Additional parameters.
	 * @return string Converted path fragments.
	 */
	protected function _resolveTestFile($filePath, $params) {
		$basePath = $this->_basePath($params) . DS . $filePath;
		$ending = 'Test.php';
		return (strpos($basePath, $ending) === (strlen($basePath) - strlen($ending))) ? $basePath : $basePath . $ending;
	}

	/**
	 * Generates the base path to a set of tests based on the parameters.
	 *
	 * @param array $params The path parameters.
	 * @return string The base path.
	 */
	protected static function _basePath($params) {
		$result = null;
		if (!empty($params['core'])) {
			$result = CORE_TEST_CASES;
		} elseif (!empty($params['plugin'])) {
			if (!CakePlugin::loaded($params['plugin'])) {
				try {
					CakePlugin::load($params['plugin']);
					$result = CakePlugin::path($params['plugin']) . 'Test' . DS . 'Case';
				} catch (MissingPluginException $e) {
				}
			} else {
				$result = CakePlugin::path($params['plugin']) . 'Test' . DS . 'Case';
			}
		} elseif (!empty($params['app'])) {
			$result = APP_TEST_CASES;
		}
		return $result;
	}

}
