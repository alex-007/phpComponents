<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../Profiler.php';

/**
 * Test class for Profiler.
 */
class ProfilerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var Profiler
	 */
	protected $profiler;

	protected $pdo;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		$dsn = 'mysql:dbname=test;host=127.0.0.1';
		$user = 'root';
		$pass = '';

		$this->pdo = new PDO($dsn,$user,$pass);
		$this->profiler = new Profiler($this->pdo, 0, 0);
	}

	/**
	 * @covers Profiler::AllProfiles
	 */
	public function testAllProfiles() {
		$sql = 'SELECT * FROM test';
		$res = $this->pdo->query($sql);

		$ret = $this->profiler->AllProfiles();
		$this->assertEquals($sql, $ret[0]['Query']);
	}

	/**
	 * @covers Profiler::QueryProfile
	 * @depends testAllProfiles
	 */
	public function testQueryProfile() {
		$sql = 'SELECT * FROM test';
		$res = $this->pdo->query($sql);
		$ret = $this->profiler->QueryProfile(1);
		$this->assertEquals('starting', $ret[0]['Status']);
	}
}
