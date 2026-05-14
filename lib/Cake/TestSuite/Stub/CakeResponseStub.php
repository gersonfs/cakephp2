<?php
App::uses('CakeResponse', 'Network');
App::uses('CakeStubTrait', 'TestSuite/Stub');

class CakeResponseStub extends CakeResponse {
	use CakeStubTrait;

	protected function _sendHeader($name, $value = null) {
		return $this->_cakeResolve('_sendHeader', array($name, $value), function () use ($name, $value) {
			return parent::_sendHeader($name, $value);
		});
	}

	protected function _sendContent($content) {
		return $this->_cakeResolve('_sendContent', array($content), function () use ($content) {
			return parent::_sendContent($content);
		});
	}

	protected function _setContentType() {
		return $this->_cakeResolve('_setContentType', array(), function () {
			return parent::_setContentType();
		});
	}

	protected function _isActive() {
		return $this->_cakeResolve('_isActive', array(), function () {
			return parent::_isActive();
		});
	}

	protected function _clearBuffer() {
		return $this->_cakeResolve('_clearBuffer', array(), function () {
			return parent::_clearBuffer();
		});
	}

	protected function _flushBuffer() {
		return $this->_cakeResolve('_flushBuffer', array(), function () {
			return parent::_flushBuffer();
		});
	}

	protected function _setCookies() {
		return $this->_cakeResolve('_setCookies', array(), function () {
			return parent::_setCookies();
		});
	}

	public function outputCompressed() {
		return $this->_cakeResolve('outputCompressed', array(), function () {
			return parent::outputCompressed();
		});
	}

	public function checkNotModified(CakeRequest $request) {
		return $this->_cakeResolve('checkNotModified', array($request), function () use ($request) {
			return parent::checkNotModified($request);
		});
	}

	public function send() {
		return $this->_cakeResolve('send', array(), function () {
			return parent::send();
		});
	}

	public function statusCode($code = null) {
		return $this->_cakeResolve('statusCode', array($code), function () use ($code) {
			return parent::statusCode($code);
		});
	}

	public function header($header = null, $value = null) {
		$args = func_get_args();
		return $this->_cakeResolve('header', $args, function () use ($args) {
			return parent::header(...$args);
		});
	}

	public function notModified() {
		return $this->_cakeResolve('notModified', array(), function () {
			return parent::notModified();
		});
	}

	public function httpCodes($code = null) {
		return $this->_cakeResolve('httpCodes', array($code), function () use ($code) {
			return parent::httpCodes($code);
		});
	}

	public function type($contentType = null) {
		return $this->_cakeResolve('type', array($contentType), function () use ($contentType) {
			return parent::type($contentType);
		});
	}

	public function download($filename) {
		return $this->_cakeResolve('download', array($filename), function () use ($filename) {
			return parent::download($filename);
		});
	}

	public function charset($charset = null) {
		return $this->_cakeResolve('charset', array($charset), function () use ($charset) {
			return parent::charset($charset);
		});
	}

}
