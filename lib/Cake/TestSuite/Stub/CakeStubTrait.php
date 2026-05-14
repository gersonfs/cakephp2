<?php
App::uses('CakeStubBuilder', 'TestSuite/Stub');

trait CakeStubTrait {

	public $_cakeStubs = array();

	public function expects($matcher) {
		return new CakeStubBuilder($this);
	}

	public function _cakeSetStub($method, $stub) {
		$this->_cakeStubs[$method] = $stub;
	}

	protected function _cakeResolve($method, array $args, callable $default) {
		if (!array_key_exists($method, $this->_cakeStubs)) {
			return $default();
		}
		return CakeStubBuilder::invoke($this->_cakeStubs[$method], $args);
	}

}
