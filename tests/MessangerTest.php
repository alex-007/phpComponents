<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../Messanger.php';

/**
 * Test class for Messanger.
 * Generated by PHPUnit on 2010-02-14 at 16:06:01.
 */
class MessangerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var Messanger
	 */
	protected $messanger;

	protected function setUp() {
		$this->messanger = new Messanger("http://localhost/1.php","bot@example.com");
	}

	/**
	 * @expectedException ParamException
	 */
	public function testSetSenderException(){
		new Messanger("aaa","bot@example.com");
	}

	/**
	 * @expectedException ParamException
	 */
	public function testSetFromException(){
		$this->messanger->setFrom("Not an Email");
	}

	/**
	 * @expectedException ParamException
	 */
	public function testSetToException(){
		$this->messanger->setTo("Not an Email");
	}

	/**
	 * @expectedException ParamException
	 */
	public function testSetToException2(){
		$this->messanger->setTo("email1@example.com email2@example.com");
	}

	/**
	 * @expectedException ParamException
	 */
	public function testSendMessageException(){
		$this->messanger->setTo('user1@example.com, user2@example.com')->sendMessage('some message', 'testModule wrong');
	}

	/**
	 * @expectedException ParamException
	 */
	public function testSendMessageException2(){
		$message = str_repeat('a', 256);
		$this->messanger->setTo('user1@example.com')->sendMessage($message, 'testModule');
	}

	/**
	 * @covers Messanger::getFrom
	 */
	public function testGetFrom() {
		$this->assertEquals('bot@example.com', $this->messanger->getFrom());
	}

	/**
	 * @covers Messanger::setFrom
	 * @covers Messanger::getFrom
	 */
	public function testSetFrom() {
		$this->assertEquals($this->messanger, $this->messanger->setFrom('bot1@example.com'));
		$this->assertEquals('bot1@example.com', $this->messanger->getFrom());
	}

	/**
	 * @covers Messanger::setTo
	 * @covers Messanger::getTo
	 */
	public function testSetTo() {
		$this->assertEquals($this->messanger, $this->messanger->setTo('user@example.com'));
		$this->assertEquals('user@example.com', $this->messanger->getTo());

		$this->assertEquals($this->messanger, $this->messanger->setTo('user1@example.com, user2@example.com'));
		$this->assertEquals('user1@example.com,user2@example.com', $this->messanger->getTo());
	}

	/**
	 * @covers Messanger::sendMessage
	 * @depends testSetTo
	 */
	public function testSendMessage() {
		$this->assertTrue($this->messanger->setTo('user1@example.com, user2@example.com')->sendMessage('some message', 'testModule'));
	}
}
