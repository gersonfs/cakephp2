<?php
/**
 * Adapter to expose a CakeSessionHandlerInterface as a native
 * \SessionHandlerInterface so it can be registered with the
 * non-deprecated single-argument form of session_set_save_handler().
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

App::uses('CakeSessionHandlerInterface', 'Model/Datasource/Session');

class CakeSessionHandlerAdapter implements CakeSessionHandlerInterface, \SessionHandlerInterface {

/**
 * Wrapped legacy handler.
 *
 * @var CakeSessionHandlerInterface
 */
	protected $_handler;

	public function __construct(CakeSessionHandlerInterface $handler) {
		$this->_handler = $handler;
	}

	#[\ReturnTypeWillChange]
	public function open($savePath = null, $name = null) {
		return (bool)$this->_handler->open();
	}

	#[\ReturnTypeWillChange]
	public function close() {
		return (bool)$this->_handler->close();
	}

	#[\ReturnTypeWillChange]
	public function read($id) {
		$value = $this->_handler->read($id);
		return $value === false ? '' : (string)$value;
	}

	#[\ReturnTypeWillChange]
	public function write($id, $data) {
		return (bool)$this->_handler->write($id, $data);
	}

	#[\ReturnTypeWillChange]
	public function destroy($id) {
		return (bool)$this->_handler->destroy($id);
	}

	#[\ReturnTypeWillChange]
	public function gc($expires = null) {
		$this->_handler->gc($expires);
		return 0;
	}

}
