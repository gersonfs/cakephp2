<?php
App::uses('CakeStubBuilder', 'TestSuite/Stub');

trait CakeStubTrait {

	public $_cakeStubs = array();

	public $_cakeMockedMethods = array();

	public function expects($matcher) {
		return new CakeStubBuilder($this);
	}

	public function _cakeSetStub($method, $stub) {
		$this->_cakeStubs[$method] = $stub;
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
		if (array_key_exists($method, $this->_cakeStubs)) {
			return CakeStubBuilder::invoke($this->_cakeStubs[$method], $args);
		}
		if (in_array($method, $this->_cakeMockedMethods, true)) {
			return null;
		}
		return $default();
	}

}
