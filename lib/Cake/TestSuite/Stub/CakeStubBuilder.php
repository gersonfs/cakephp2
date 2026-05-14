<?php
/**
 * Backwards-compatibility builder for the
 * $stub->expects(...)->method('X')->will/willReturn(...) chain when the
 * mocked class collides with PHPUnit 12's Method trait (e.g. CakeRequest).
 */

use PHPUnit\Framework\MockObject\Stub\ConsecutiveCalls;
use PHPUnit\Framework\MockObject\Stub\ReturnArgument;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use PHPUnit\Framework\MockObject\Stub\ReturnStub;
use PHPUnit\Framework\MockObject\Stub\ReturnValueMap;
use PHPUnit\Framework\MockObject\Stub\Stub as StubInterface;

class CakeStubBuilder {

	protected $_owner;

	protected $_method;

	protected $_index = null;

	public function __construct($owner, $matcher = null) {
		$this->_owner = $owner;
		if (is_object($matcher) && method_exists($matcher, 'index')) {
			$this->_index = $matcher->index();
		}
	}

	public function method($name) {
		$this->_method = $name;
		return $this;
	}

	public function with(...$args) {
		return $this;
	}

/**
 * Stores a stub for the current method, either as the default stub or, when
 * the builder was created from an `at($n)` matcher, as the stub for the n-th
 * invocation.
 *
 * @param mixed $stub Stub or return value.
 * @return void
 */
	protected function _store($stub) {
		if ($this->_index !== null) {
			$this->_owner->_cakeSetSeqStub($this->_method, $this->_index, $stub);
			return;
		}
		$this->_owner->_cakeSetStub($this->_method, $stub);
	}

	public function will($stub) {
		$this->_store($stub);
		return $this;
	}

	public function willReturn($value) {
		$this->_store($value);
		return $this;
	}

	public function willReturnCallback($callback) {
		$this->_store($callback);
		return $this;
	}

	public function willReturnArgument($index) {
		$this->_store(function (...$args) use ($index) {
			return $args[$index] ?? null;
		});
		return $this;
	}

	public function willReturnSelf() {
		$this->_store($this->_owner);
		return $this;
	}

	public function willReturnMap(array $map) {
		$this->_store(new ReturnValueMap($map));
		return $this;
	}

	public function willReturnOnConsecutiveCalls(...$values) {
		$this->_store(new ConsecutiveCalls($values));
		return $this;
	}

	public function willThrowException(\Throwable $e) {
		$this->_store(function () use ($e) {
			throw $e;
		});
		return $this;
	}

	public static function invoke($stub, array $args) {
		if ($stub instanceof ReturnStub) {
			return (new \ReflectionProperty($stub, 'value'))->getValue($stub);
		}
		if ($stub instanceof ReturnCallback) {
			$cb = (new \ReflectionProperty($stub, 'callback'))->getValue($stub);
			return $cb(...$args);
		}
		if ($stub instanceof ReturnArgument) {
			$idx = (new \ReflectionProperty($stub, 'argumentIndex'))->getValue($stub);
			return $args[$idx] ?? null;
		}
		if ($stub instanceof ConsecutiveCalls) {
			$ref = new \ReflectionProperty($stub, 'stack');
			$stack = $ref->getValue($stub);
			$next = array_shift($stack);
			$ref->setValue($stub, $stack);
			return self::invoke($next, $args);
		}
		if ($stub instanceof ReturnValueMap) {
			$ref = new \ReflectionProperty($stub, 'valueMap');
			$map = $ref->getValue($stub);
			$count = count($args);
			foreach ($map as $row) {
				if (array_slice($row, 0, $count) === $args) {
					return $row[$count] ?? null;
				}
			}
			return null;
		}
		if ($stub instanceof StubInterface) {
			$ref = new \ReflectionClass($stub);
			foreach (['value', 'callback', 'argumentIndex'] as $p) {
				if ($ref->hasProperty($p)) {
					return $ref->getProperty($p)->getValue($stub);
				}
			}
			return null;
		}
		if (is_callable($stub) && !is_string($stub) && !is_array($stub)) {
			return $stub(...$args);
		}
		return $stub;
	}

}
