<?php
/**
 * Console Logging
 *
 * CakePHP(tm) :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       Cake.Log.Engine
 * @since         CakePHP(tm) v 2.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('BaseLog', 'Log/Engine');
App::uses('ConsoleOutput', 'Console');

/**
 * Console logging. Writes logs to console output.
 *
 * @package       Cake.Log.Engine
 */
class ConsoleLog extends BaseLog {

/**
 * Output stream
 *
 * @var ConsoleOutput
 */
	protected $_output = null;

/**
 * Constructs a new Console Logger.
 *
 * Config
 *
 * - `types` string or array, levels the engine is interested in
 * - `scopes` string or array, scopes the engine is interested in
 * - `stream` the path to save logs on.
 * - `outputAs` integer or ConsoleOutput::[RAW|PLAIN|COLOR]
 *
 * @param array $config Options for the FileLog, see above.
 * @throws CakeLogException
 */
	public function __construct($config = array()) {
		parent::__construct($config);
		$config = Hash::merge(array(
			'stream' => 'php://stderr',
			'types' => null,
			'scopes' => array(),
			'outputAs' => null,
			), $this->_config);
		$config = $this->config($config);
		if ($config['stream'] instanceof ConsoleOutput) {
			$this->_output = $config['stream'];
		} elseif (is_string($config['stream'])) {
			$this->_output = new ConsoleOutput($config['stream']);
		} else {
			throw new CakeLogException('`stream` not a ConsoleOutput nor string');
		}
		if ($config['outputAs'] === null) {
			$config['outputAs'] = static::_defaultOutputAs($config['stream']);
			$this->_config['outputAs'] = $config['outputAs'];
		}
		$this->_output->outputAs($config['outputAs']);
	}

/**
 * Decides whether output should be colourised.
 *
 * The check has to look at the stream that was actually configured: testing
 * STDERR unconditionally means a configuration such as
 * `'stream' => LOGS . 'cli.log'` gets ANSI escape sequences written into the
 * log file whenever the process happens to run on a terminal.
 *
 * @param string|ConsoleOutput $stream The configured stream.
 * @return string One of the ConsoleOutput output modes.
 */
	protected static function _defaultOutputAs($stream) {
		$consoleStreams = array('php://stderr', 'php://stdout', 'php://output');
		if (!is_string($stream) || !in_array($stream, $consoleStreams, true)) {
			return ConsoleOutput::PLAIN;
		}
		if (DS === '\\' && !(bool)env('ANSICON') && env('ConEmuANSI') !== 'ON') {
			return ConsoleOutput::PLAIN;
		}
		if (!function_exists('posix_isatty')) {
			return ConsoleOutput::PLAIN;
		}
		$handle = null;
		if ($stream === 'php://stdout' && defined('STDOUT')) {
			$handle = STDOUT;
		} elseif (defined('STDERR')) {
			$handle = STDERR;
		}
		if ($handle === null || !posix_isatty($handle)) {
			return ConsoleOutput::PLAIN;
		}
		return ConsoleOutput::COLOR;
	}

/**
 * Implements writing to console.
 *
 * @param string $type The type of log you are making.
 * @param string $message The message you want to log.
 * @return bool success of write.
 */
	public function write($type, $message) {
		$output = date('Y-m-d H:i:s') . ' ' . ucfirst($type) . ': ' . $message . "\n";
		return $this->_output->write(sprintf('<%s>%s</%s>', $type, $output, $type), false);
	}

}
