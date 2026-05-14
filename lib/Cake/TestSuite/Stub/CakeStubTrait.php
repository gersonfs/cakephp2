<?php
App::uses('CakeStubBuilder', 'TestSuite/Stub');

trait CakeStubTrait {

	public $_cakeStubs = array();

	public $_cakeSeqStubs = array();

	public $_cakeSeqCounter = array();

	public $_cakeMockedMethods = array();

	public function expects($matcher) {
		return new CakeStubBuilder($this, $matcher);
	}

	public function _cakeSetStub($method, $stub) {
		$this->_cakeStubs[$method] = $stub;
	}

/**
 * Registers a stub for a specific invocation index of a method, used to
 * emulate the `expects($this->at($n))` pattern.
 *
 * @param string $method Method name.
 * @param int $index Zero-based invocation index.
 * @param mixed $stub Stub/return value.
 * @return void
 */
	public function _cakeSetSeqStub($method, $index, $stub) {
		$this->_cakeSeqStubs[$method][$index] = $stub;
	}

/**
 * Records which methods were explicitly requested to be mocked via
 * `getMock($class, $methods)`. Those methods must behave like real PHPUnit
 * doubles: when no return value was configured they return null instead of
 * invoking the parent implementation.
 *
 * @param array $methods Method names passed to getMock().
 * @return void
 */
	public function _cakeSetMockedMethods(array $methods) {
		$this->_cakeMockedMethods = $methods;
	}

	protected function _cakeResolve($method, array $args, callable $default) {
		if (isset($this->_cakeSeqStubs[$method])) {
			$index = isset($this->_cakeSeqCounter[$method]) ? $this->_cakeSeqCounter[$method] : 0;
			$this->_cakeSeqCounter[$method] = $index + 1;
			if (array_key_exists($index, $this->_cakeSeqStubs[$method])) {
				return CakeStubBuilder::invoke($this->_cakeSeqStubs[$method][$index], $args);
			}
		}
		if (array_key_exists($method, $this->_cakeStubs)) {
			return CakeStubBuilder::invoke($this->_cakeStubs[$method], $args);
		}
		if (in_array($method, $this->_cakeMockedMethods, true)) {
			return null;
		}
		return $default();
	}

}
