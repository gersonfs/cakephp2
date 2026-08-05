<?php
App::uses('CakeStubBuilder', 'TestSuite/Stub');

trait CakeStubTrait {

	public $_cakeStubs = array();

	public $_cakeSeqStubs = array();

	public $_cakeSeqCounter = array();

	public $_cakeMockedMethods = array();

/**
 * Whether every intercepted method is doubled, i.e. getMock() was called
 * without a method list.
 *
 * @var bool
 */
	public $_cakeDoubleAll = false;

/**
 * Expectations declared through expects(), verified by _cakeVerify().
 *
 * @var array
 */
	public $_cakeExpectations = array();

/**
 * Every intercepted call, in object-wide order, as method/args pairs. The
 * ordering matters: at($n) indexes invocations of the whole object, not of a
 * single method, which is the semantics the legacy matcher had.
 *
 * @var array
 */
	public $_cakeInvocations = array();

/**
 * Monotonic invocation counter used to ignore calls that happened before an
 * expectation was declared.
 *
 * @var int
 */
	public $_cakeSequence = 0;

	public function expects($matcher) {
		// PHPUnit only counts invocations made after the matcher is registered.
		// Calls made earlier — most notably the ones the constructor itself
		// makes, such as CakeRequest::_processPost() probing is('put') — must
		// not be attributed to this expectation.
		$builder = new CakeStubBuilder($this, $matcher, $this->_cakeSequence);
		$this->_cakeExpectations[] = $builder;
		return $builder;
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
		// getMock($class) without a method list doubles every method, so no
		// call may reach the real implementation. Without this the stub runs
		// the parent for anything it intercepts: CakeResponse::send() then
		// actually echoes the response body during tests.
		$this->_cakeDoubleAll = empty($methods);
	}

/**
 * Verifies every expectation declared through expects() against the calls that
 * were actually intercepted. Called by CakeTestCase once the test body has
 * completed, mirroring PHPUnit's verifyMockObjects().
 *
 * Expectations on methods this stub does not intercept are skipped: the call
 * never reaches _cakeResolve(), so there is nothing to count and failing would
 * be a false positive.
 *
 * @return void
 */
	public function _cakeVerify() {
		$strict = array();
		foreach ($this->_cakeExpectations as $expectation) {
			$method = $expectation->_cakeMethodName();
			if ($method === null || $expectation->_cakeIsIndexed() ||
				!$expectation->_cakeHasArgumentExpectation()
			) {
				continue;
			}
			$strict[$method] = isset($strict[$method]) ? $strict[$method] + 1 : 1;
		}

		foreach ($this->_cakeExpectations as $expectation) {
			$method = $expectation->_cakeMethodName();
			if ($method === null || !$this->_cakeCanObserve($method)) {
				continue;
			}
			if ($expectation->_cakeIsIndexed()) {
				$expectation->_cakeVerifyIndexed(
					$this->_cakeDoubledInvocationsSince($expectation->_cakeStartSequence())
				);
				continue;
			}
			$expectation->_cakeVerify(
				$this->_cakeInvocationsSince($method, $expectation->_cakeStartSequence()),
				empty($strict[$method]) || $strict[$method] === 1
			);
		}
	}

/**
 * Arguments of the calls to $method made from $sequence onwards.
 *
 * @param string $method Method name.
 * @param int $sequence Invocation counter value the expectation was declared at.
 * @return array List of argument arrays.
 */
	protected function _cakeInvocationsSince($method, $sequence) {
		$args = array();
		foreach (array_slice($this->_cakeInvocations, $sequence) as $invocation) {
			if ($invocation['method'] === $method) {
				$args[] = $invocation['args'];
			}
		}
		return $args;
	}

/**
 * The invocation sitting at object-wide offset $index counted from $sequence,
 * or null when the object was not called that many times.
 *
 * @param int $sequence Invocation counter value the expectation was declared at.
 * @param int $index Offset requested through at($n).
 * @return array|null
 */
	protected function _cakeDoubledInvocationsSince($sequence) {
		// A real mock only routes the methods getMock() was asked to double
		// through its invocation handler, so only those advance the at() index.
		// This stub intercepts every method it overrides, hence the filter.
		$relevant = array();
		foreach (array_slice($this->_cakeInvocations, $sequence) as $invocation) {
			if (empty($this->_cakeMockedMethods) ||
				in_array($invocation['method'], $this->_cakeMockedMethods, true)
			) {
				$relevant[] = $invocation;
			}
		}
		return $relevant;
	}

/**
 * Whether calls to $method are routed through _cakeResolve(). Only methods
 * declared by a stub class (which is what `use CakeStubTrait` marks) are.
 *
 * @param string $method Method name.
 * @return bool
 */
	protected function _cakeCanObserve($method) {
		if (!method_exists($this, $method)) {
			return false;
		}
		$declaringClass = (new ReflectionMethod($this, $method))->getDeclaringClass();
		return in_array('CakeStubTrait', $declaringClass->getTraitNames(), true);
	}

	protected function _cakeResolve($method, array $args, callable $default) {
		$this->_cakeInvocations[] = array('method' => $method, 'args' => $args);
		$this->_cakeSequence++;

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
		if ($this->_cakeDoubleAll || in_array($method, $this->_cakeMockedMethods, true)) {
			return null;
		}
		return $default();
	}

}
