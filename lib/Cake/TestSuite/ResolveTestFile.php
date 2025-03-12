<?php

App::uses('CakePlugin', 'Core');
class ResolveTestFile
{
	/**
	 * Convert path fragments used by CakePHP's test runner to absolute paths that can be fed to PHPUnit.
	 *
	 * @param string $filePath The file path to load.
	 * @param string $params Additional parameters.
	 * @return string Converted path fragments.
	 */
	public function resolveTestFile($filePath, $params)
	{
		$basePath = $this->basePath($params) . DS . $filePath;
		$ending = 'Test.php';
		return (strpos($basePath, $ending) === (strlen($basePath) - strlen($ending))) ? $basePath : $basePath . $ending;
	}

	/**
	 * Generates the base path to a set of tests based on the parameters.
	 *
	 * @param array $params The path parameters.
	 * @return string The base path.
	 */
	protected function basePath($params) {
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
