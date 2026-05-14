<?php
App::uses('CakeRequest', 'Network');
App::uses('CakeStubTrait', 'TestSuite/Stub');

class CakeRequestStub extends CakeRequest {
	use CakeStubTrait;

	public function _readInput() {
		return $this->_cakeResolve('_readInput', array(), function () {
			return parent::_readInput();
		});
	}

	public function referer($base = false) {
		return $this->_cakeResolve('referer', array($base), function () use ($base) {
			return parent::referer($base);
		});
	}

	public function is($type) {
		$args = func_get_args();
		return $this->_cakeResolve('is', $args, function () use ($args) {
			return parent::is(...$args);
		});
	}

	public function here($base = true) {
		return $this->_cakeResolve('here', array($base), function () use ($base) {
			return parent::here($base);
		});
	}

}
