<?php
/**
 * Adapter turning the legacy six-callback session handler configuration into a
 * SessionHandlerInterface.
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
 * @package       Cake.Model.Datasource.Session
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Wraps the `Session.handler` callback array documented by CakePHP 2.
 *
 * Passing those callbacks straight to session_set_save_handler() is deprecated
 * as of PHP 8.4, so they are adapted to the object form PHP expects.
 *
 * @package       Cake.Model.Datasource.Session
 */
class CakeSessionHandlerCallbacks implements SessionHandlerInterface {

/**
 * open, close, read, write, destroy and gc, in that order.
 *
 * @var array
 */
	protected $_callbacks = array();

/**
 * @param array $callbacks The six session callbacks, in the order
 *   session_set_save_handler() expects them.
 */
	public function __construct(array $callbacks) {
		$this->_callbacks = array_values($callbacks);
	}

/**
 * @param string $savePath Session save path.
 * @param string $name Session name.
 * @return bool
 */
	#[\ReturnTypeWillChange]
	public function open($savePath, $name) {
		return (bool)call_user_func($this->_callbacks[0], $savePath, $name);
	}

/**
 * @return bool
 */
	#[\ReturnTypeWillChange]
	public function close() {
		return (bool)call_user_func($this->_callbacks[1]);
	}

/**
 * @param string $id Session id.
 * @return string
 */
	#[\ReturnTypeWillChange]
	public function read($id) {
		$data = call_user_func($this->_callbacks[2], $id);
		return $data === false || $data === null ? '' : (string)$data;
	}

/**
 * @param string $id Session id.
 * @param string $data Session data.
 * @return bool
 */
	#[\ReturnTypeWillChange]
	public function write($id, $data) {
		return (bool)call_user_func($this->_callbacks[3], $id, $data);
	}

/**
 * @param string $id Session id.
 * @return bool
 */
	#[\ReturnTypeWillChange]
	public function destroy($id) {
		return (bool)call_user_func($this->_callbacks[4], $id);
	}

/**
 * @param int $maxlifetime Sessions older than this are removed.
 * @return int|false
 */
	#[\ReturnTypeWillChange]
	public function gc($maxlifetime) {
		$result = call_user_func($this->_callbacks[5], $maxlifetime);
		if ($result === false) {
			return false;
		}
		return is_int($result) ? $result : 0;
	}

}
