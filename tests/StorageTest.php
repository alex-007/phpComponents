<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../Storage.php';

class StorageTest extends PHPUnit_Framework_TestCase {
	/**
	 * @var Storage
	 */
	protected $storage;

	protected function setUp() {
		$connection = new DBConnection("localhost", "root", "", "test");
		$this->storage = new Storage($connection, "001AAFF");
	}

	/**
	 * @expectedException ParamException
	 */
	public function testSetUIDException(){
		$this->storage->setUID('Unlegal Chars');
	}

	/**
	 * @expectedException ParamException
	 */
	public function testSetUIDException2(){
		$this->storage->setUID('Aaaaaaffffff123456');
	}

	/**
	 * @covers Storage::getUID
	 */
	public function testGetUID(){
		$this->assertEquals('001AAFF', $this->storage->getUID());
	}

	/**
	 * @covers Storage::setUID
	 * @covers Storage::getUID
	 */
	public function testSetUID() {
		$user = $this->storage->getUID();
		$this->storage->setUID('FFBB11');
		$this->assertEquals('FFBB11', $this->storage->getUID());
		$this->storage->setUID($user);
	}

	/**
	 * @expectedException ParamException
	 */
	public function testSetParamException(){
		$this->storage->setParam('&!2cxzc', "value1");
	}

	/**
	 * @expectedException ParamException
	 */
	public function testSetParamException2(){
		$value = array();
		$this->storage->setParam('param', $value);
	}

	/**
	 * @expectedException ParamException
	 */
	public function testSetParamException3(){
		$value = str_repeat('a', 256);
		$this->storage->setParam('param', $value);
	}

	/**
	 * @covers Storage::setParam
	 */
	public function testSetParamAllChars(){
		$value = '';
		for($i = 0; $i<255; $i++){
			$value .= chr($i);
		}
		$this->assertTrue($this->storage->setParam("param0", $value));
	}

	/**
	 * @covers Storage::setParam
	 */
	public function testSetParam(){
		$this->assertTrue($this->storage->setParam("param1", "value1"));
	}
	/**
	 * @covers Storage::getParam
	 * @depends testSetParam
	 */
	public function testGetParam(){
		$this->assertFalse($this->storage->getParam("falseParam"));
		$this->assertEquals("value1", $this->storage->getParam("param1"));
	}

	/**
	 * @covers Storage::deleteParam
	 * @covers Storage::getParam
	 * @depends testSetParam
	 */
	public function testDeleteParam() {
		$this->assertTrue($this->storage->deleteParam("param1"));
		$this->assertFalse($this->storage->getParam("param1"));
	}

	/**
	 * @covers Storage::deleteParams
	 * @covers Storage::getParam
	 * @covers Storage::setParam
	 */
	public function testDeleteParams() {
		$this->storage->setParam("param2", "value2");
		$this->storage->setParam("param3", "value3");
		$this->assertEquals("value2", $this->storage->getParam("param2"));
		$this->assertEquals("value3", $this->storage->getParam("param3"));

		$this->assertTrue($this->storage->deleteParams(array("param2", "param3")));
		$this->assertFalse($this->storage->getParam("param2"));
		$this->assertFalse($this->storage->getParam("param3"));
	}

	/**
	 * @covers Storage::deleteAllParams
	 * @covers Storage::getParam
	 * @covers Storage::setParam
	 */
	public function testDeleteAllParams(){
		$this->storage->setParam("param4", "value4");
		$this->storage->setParam("param5", "value5");
		$this->assertEquals("value4", $this->storage->getParam("param4"));
		$this->assertEquals("value5", $this->storage->getParam("param5"));
		$this->assertTrue($this->storage->deleteAllParams());
		$this->assertFalse($this->storage->getParam("param4"));
		$this->assertFalse($this->storage->getParam("param5"));
	}

	/**
	 * @covers Storage::deleteParamsByExpired
	 * @covers Storage::getParam
	 * @covers Storage::setParam
	 * @covers Storage::setUID
	 * @covers Storage::getUID
	 */
	public function testDeleteParamsByExpired(){
		$this->storage->setParam("param6", "value6");
		sleep(5);
		$this->storage->setParam("param7", "value7");

		$this->assertTrue($this->storage->deleteParamsByExpired(time()-10));
		$this->assertEquals("value6", $this->storage->getParam("param6"));
		$this->assertEquals("value7", $this->storage->getParam("param7"));

		$this->assertTrue($this->storage->deleteParamsByExpired(time()-4));
		$this->assertFalse($this->storage->getParam("param6"));
		$this->assertEquals("value7", $this->storage->getParam("param7"));

		sleep(5);
		$this->assertTrue($this->storage->deleteParamsByExpired(time()-4));
		$this->assertFalse($this->storage->getParam("param7"));
	}
}
