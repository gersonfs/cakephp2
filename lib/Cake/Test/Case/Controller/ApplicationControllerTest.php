<?php
App::uses('AppController', 'Controller');
App::uses('ControllerTestCase', 'TestSuite');
App::uses('CakeSession', 'Model/Datasource');

/**
 * TransSessionIdController class for testing session.use_trans_sid=1.
 *
 * @package       Cake.Test.Case.Controller
 */
class TransSessionIdController extends AppController {

/**
 * Constructor.
 *
 * @param CakeRequest $request Request object for this controller.
 * @param CakeResponse $response Response object for this controller.
 */
	public function __construct($request = null, $response = null) {
		parent::__construct($request, $response);
		$ini = Configure::read('Session.ini');
		$ini['session.use_cookies'] = 0;
		$ini['session.use_only_cookies'] = 0;
		$ini['session.use_trans_sid'] = 1;
		Configure::write('Session.ini', $ini);
	}

/**
 * For testing redirect URL with session.use_trans_sid=1.
 *
 * @return CakeResponse|null
 */
	public function next() {
		$sessionName = session_name();
		$sessionId = $this->Session->id();
		return $this->redirect(array(
			'controller' => 'trans_session_id',
			'action' => 'next_step',
			'?' => array(
				$sessionName => $sessionId,
			),
		));
	}

}

/**
 * ApplicationControllerTest class for testing controllers by using ControllerTestCase.
 *
 * ApplicationControllerTest extends ControllerTestCase in contrast
 * with ControllerTest that extends CakeTestCase.
 *
 * @package       Cake.Test.Case.Controller
 */
class ApplicationControllerTest extends ControllerTestCase {

/**
 * setupDown method
 *
 * @return void
 */
	public function setUp(): void {
		CakeSession::destroy();
		parent::setUp();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown(): void {
		CakeSession::destroy();
		parent::tearDown();
	}

/**
 * Tests the redirect and session config with use_trans_sid=1.
 *
 * @return void
 */
	public function testRedirect() {
		$sessionId = 'o7k64tlhil9pakp89j6d8ovlqk';
		$levelBefore = ob_get_level();
		$this->testAction('/trans_session_id/next?CAKEPHP=' . $sessionId);
		while (ob_get_level() > $levelBefore) {
			ob_end_clean();
		}
		$this->assertStringContainsString('/trans_session_id/next_step?CAKEPHP=' . $sessionId, $this->headers['Location']);
		$actualConfig = Configure::read('Session');
		$this->assertEquals('CAKEPHP', $actualConfig['cookie']);
		$this->assertEquals(240, $actualConfig['timeout']);
		$this->assertEquals('php', $actualConfig['defaults']);
		$this->assertEquals(1, $actualConfig['ini']['session.use_trans_sid']);
		$this->assertEquals(0, $actualConfig['ini']['session.use_cookies']);
		$this->assertEquals(0, $actualConfig['ini']['session.use_only_cookies']);
	}

}
