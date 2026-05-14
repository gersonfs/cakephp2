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

	public function __construct($owner) {
		$this->_owner = $owner;
	}

	public function method($name) {
		$this->_method = $name;
		return $this;
	}

	public function with(...$args) {
		return $this;
	}

	public function will($stub) {
		$this->_owner->_cakeSetStub($this->_method, $stub);
		return $this;
	}

	public function willReturn($value) {
		$this->_owner->_cakeSetStub($this->_method, $value);
		return $this;
	}

	public function willReturnCallback($callback) {
		$this->_owner->_cakeSetStub($this->_method, $callback);
		return $this;
	}

	public function willReturnArgument($index) {
		$this->_owner->_cakeSetStub($this->_method, function (...$args) use ($index) {
			return $args[$index] ?? null;
		});
		return $this;
	}

	public function willReturnSelf() {
		$this->_owner->_cakeSetStub($this->_method, $this->_owner);
		return $this;
	}

	public function willReturnMap(array $map) {
		$this->_owner->_cakeSetStub($this->_method, new ReturnValueMap($map));
		return $this;
	}

	public function willReturnOnConsecutiveCalls(...$values) {
		$this->_owner->_cakeSetStub($this->_method, new ConsecutiveCalls($values));
		return $this;
	}

	public function willThrowException(\Throwable $e) {
		$this->_owner->_cakeSetStub($this->_method, function () use ($e) {
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
