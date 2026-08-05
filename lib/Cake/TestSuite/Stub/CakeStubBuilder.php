<?php
/**
 * Backwards-compatibility builder for the
 * $stub->expects(...)->method('X')->will/willReturn(...) chain when the
 * mocked class collides with PHPUnit 12's Method trait (e.g. CakeRequest).
 */

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\MockObject\Rule\AnyInvokedCount;
use PHPUnit\Framework\MockObject\Rule\InvokedAtLeastCount;
use PHPUnit\Framework\MockObject\Rule\InvokedAtLeastOnce;
use PHPUnit\Framework\MockObject\Rule\InvokedAtMostCount;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
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

/**
 * The invocation matcher passed to expects(), used to verify the call count.
 *
 * @var mixed
 */
	protected $_matcher = null;

/**
 * Arguments declared through with(). Null means with() was never called.
 *
 * @var array|null
 */
	protected $_with = null;

/**
 * Value of the owner's invocation counter when this expectation was declared.
 * Earlier calls are not attributed to it.
 *
 * @var int
 */
	protected $_startSequence = 0;

	public function __construct($owner, $matcher = null, $startSequence = 0) {
		$this->_owner = $owner;
		$this->_matcher = $matcher;
		$this->_startSequence = $startSequence;
		if (is_object($matcher) && method_exists($matcher, 'index')) {
			$this->_index = $matcher->index();
		}
	}

/**
 * @return int
 */
	public function _cakeStartSequence() {
		return $this->_startSequence;
	}

	public function method($name) {
		$this->_method = $name;
		return $this;
	}

	public function with(...$args) {
		$this->_with = $args;
		return $this;
	}

	public function withAnyParameters() {
		$this->_with = null;
		return $this;
	}

/**
 * Name of the method this expectation applies to, or null when method() was
 * never called.
 *
 * @return string|null
 */
	public function _cakeMethodName() {
		return $this->_method;
	}

/**
 * Whether this expectation was created from an at($n) matcher.
 *
 * @return bool
 */
	public function _cakeIsIndexed() {
		return $this->_index !== null;
	}

/**
 * Whether with() declared argument expectations.
 *
 * @return bool
 */
	public function _cakeHasArgumentExpectation() {
		return $this->_with !== null;
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

/**
 * Verifies this expectation against the invocations actually recorded for the
 * method. Mirrors what PHPUnit's Matcher::verify() does for real mock objects:
 * the invocation count is checked against the matcher, and — unless the
 * matcher is any()/never()/atMost() — the arguments are checked too.
 *
 * @param array $invocations List of argument arrays, in invocation order.
 * @param bool $strictArguments When true every invocation must match the
 *   declared arguments; when false (several expectations share the method) a
 *   single matching invocation is enough.
 * @return void
 */
	public function _cakeVerify(array $invocations, $strictArguments = true) {
		$count = count($invocations);

		list($min, $max) = $this->_cakeExpectedRange();

		if ($min !== null && $min === $max) {
			Assert::assertSame(
				$min,
				$count,
				sprintf(
					'Method %s() was expected to be called %d time(s), actually called %d time(s).',
					$this->_method,
					$min,
					$count
				)
			);
		} else {
			if ($min !== null) {
				Assert::assertGreaterThanOrEqual(
					$min,
					$count,
					sprintf(
						'Method %s() was expected to be called at least %d time(s), actually called %d time(s).',
						$this->_method,
						$min,
						$count
					)
				);
			}
			if ($max !== null) {
				Assert::assertLessThanOrEqual(
					$max,
					$count,
					sprintf(
						'Method %s() was expected to be called at most %d time(s), actually called %d time(s).',
						$this->_method,
						$max,
						$count
					)
				);
			}
		}

		// PHPUnit skips the parameter rule for any(), never() and atMost().
		if ($this->_with === null || $count === 0 || $max === 0 ||
			$this->_matcher instanceof AnyInvokedCount ||
			$this->_matcher instanceof InvokedAtMostCount
		) {
			return;
		}

		if ($strictArguments) {
			foreach ($invocations as $i => $args) {
				$this->_cakeAssertArguments($args, sprintf('%s() invocation #%d', $this->_method, $i));
			}
			return;
		}

		foreach ($invocations as $args) {
			if ($this->_cakeArgumentsMatch($args)) {
				return;
			}
		}
		Assert::fail(sprintf(
			'No invocation of %s() matched the arguments declared with with().',
			$this->_method
		));
	}

/**
 * The at($n) offset this expectation was declared with.
 *
 * @return int|null
 */
	public function _cakeIndex() {
		return $this->_index;
	}

/**
 * Verifies an at($n) expectation, reproducing the semantics of the matcher
 * PHPUnit removed.
 *
 * The index addresses invocations of the whole object rather than of a single
 * method, because PHPUnit evaluates the invocation rule before the method name
 * rule. Consequently the legacy verify() only asserted that the object had been
 * invoked at least $n + 1 times; the arguments were checked in invoked(), which
 * ran only when the method name lined up as well. Both halves are reproduced
 * here — being stricter would fail tests that upstream CakePHP shipped green.
 *
 * @param array $invocations Method/args pairs recorded since this expectation
 *   was declared, restricted to the doubled methods.
 * @return void
 */
	public function _cakeVerifyIndexed(array $invocations) {
		Assert::assertGreaterThan(
			$this->_index,
			count($invocations),
			sprintf(
				'The expected invocation at index %d (%s()) was never matched: the object was called %d time(s).',
				$this->_index,
				$this->_method,
				count($invocations)
			)
		);
		$invocation = $invocations[$this->_index];
		if ($this->_with === null || $invocation['method'] !== $this->_method) {
			return;
		}
		$this->_cakeAssertArguments(
			$invocation['args'],
			sprintf('%s() invocation at index %d', $this->_method, $this->_index)
		);
	}

/**
 * Translates an invocation matcher into an inclusive [min, max] call-count
 * range. A null bound means "unconstrained".
 *
 * @return array
 */
	protected function _cakeExpectedRange() {
		$matcher = $this->_matcher;
		if ($matcher instanceof InvokedCount) {
			$count = $this->_cakeReadMatcherCount($matcher, 'expectedCount');
			return array($count, $count);
		}
		if ($matcher instanceof InvokedAtLeastOnce) {
			return array(1, null);
		}
		if ($matcher instanceof InvokedAtLeastCount) {
			return array($this->_cakeReadMatcherCount($matcher, 'requiredInvocations'), null);
		}
		if ($matcher instanceof InvokedAtMostCount) {
			return array(null, $this->_cakeReadMatcherCount($matcher, 'allowedInvocations'));
		}
		return array(null, null);
	}

/**
 * Reads the expected count out of a PHPUnit matcher. The property is private
 * and there is no public accessor, so reflection is the only option.
 *
 * @param object $matcher The matcher to read.
 * @param string $property Property holding the count.
 * @return int|null
 */
	protected function _cakeReadMatcherCount($matcher, $property) {
		$reflection = new \ReflectionClass($matcher);
		if (!$reflection->hasProperty($property)) {
			return null;
		}
		return (int)$reflection->getProperty($property)->getValue($matcher);
	}

/**
 * @param array $args Actual arguments of one invocation.
 * @param string $context Human readable description used in failures.
 * @return void
 */
	protected function _cakeAssertArguments(array $args, $context) {
		foreach ($this->_with as $i => $expected) {
			$actual = array_key_exists($i, $args) ? $args[$i] : null;
			$constraint = $expected instanceof Constraint ? $expected : new IsEqual($expected);
			Assert::assertThat(
				$actual,
				$constraint,
				sprintf('%s: argument #%d does not match the expectation declared with with().', $context, $i)
			);
		}
	}

/**
 * Non-throwing variant of _cakeAssertArguments().
 *
 * @param array $args Actual arguments of one invocation.
 * @return bool
 */
	protected function _cakeArgumentsMatch(array $args) {
		foreach ($this->_with as $i => $expected) {
			$actual = array_key_exists($i, $args) ? $args[$i] : null;
			$constraint = $expected instanceof Constraint ? $expected : new IsEqual($expected);
			if (!$constraint->evaluate($actual, '', true)) {
				return false;
			}
		}
		return true;
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
