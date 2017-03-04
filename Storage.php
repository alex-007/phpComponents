<?php
require_once('DBConnection.php');
require_once('Exceptions.php');

class Storage{
	const uidLen = 16;
	const keyNameLen = 32;
	const valueLen = 255;
	const table = 'storage';

	/**
	 * Rules for the imitation of value of the function parameters
	 * len is a maximum available len for this parameter
	 * name is a user understandable parameter name
	 * regexp is a regexp to check that the parameter contains correct characters
	 */
	static private $params = array(
		'uid' => array('len' => self::uidLen, 'name' => 'User ID', 'regexp' => '/^[\dA-Fa-f]+$/'),
		'keyName' => array('len' => self::keyNameLen, 'name' => 'Parameter', 'regexp' => '/^\w+$/'),
		'value' => array('len' => self::valueLen, 'name' => 'Value')
	);

	/**
	 * Connection to database we use to execute our queries
	 * @var DBConnection
	 */
	private $_connection = null;

	/**
	 * User id
	 * @var string
	 */
	private $_uid = false;

	/**
	 * Constructor of the class
	 * @param DBConnection $connection
	 * @param string $uid user id
	 * @param bool $autoCreate should table to be created or not if it doesn't exist
	 * @throws ParamException if connection or user id aren't initialized
	 * @throws DBException if the storage table doesn't exist and can't be created
	 */
	public function __construct(DBConnection $connection, $uid, $autoCreate = true){
		if(!$connection){
			throw new ParamException('Invalid parameter: connection must be initialized');
		}

		if(!$uid){
			throw new ParamException('Invalid parameter: user ID must be initialized');
		}

		$this->_connection = $connection;
		$this->setUID($uid);
		$this->checkTableExists($autoCreate);
	}

	/**
	 * checks if the storage table exists
	 * @param bool $autoCreate should table to be created or not if it doesn't exist
	 * @throws DBException if can't create the storage table
	 * @return boolean true if table exists false in other case
	 */
	private function checkTableExists($autoCreate){
		$sql='SELECT * FROM '.self::table.' WHERE 0';
		$res = $this->_connection->execQuery($sql, array(), false);
		if($res !== false){
			mysqli_free_result($res);
			return true;
		}

		//DB table does not exists
		if(!$autoCreate){
			throw new DBException("Storage table `".self::table."` doesn't exist");
		}
		return $this->createDbTable();
	}

	/**
	 * creates the storage table
	 * @throws DBException if can't create the storage table
	 * @return boolean true if the table was created
	 */
	private function createDbTable(){
		$sql = "CREATE TABLE `".self::table."` (
  `uid` char(".self::uidLen.") NOT NULL,
  `key_name` varchar(".self::keyNameLen.") NOT NULL,
  `value` varchar(".self::valueLen.") NOT NULL DEFAULT '',
  `created` TIMESTAMP,
  PRIMARY KEY (`uid`,`key_name`),
  KEY `uid` (`uid`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		$res = $this->_connection->execQuery($sql);
		return true;
	}

	/**
	 * Set the user id
	 * @param string $uid user id
	 * @throws ParamException if length of user id is wrong
	 */
	public function setUID($uid){
		$this->checkStringParam($uid, 'uid', "setUID");
		
		$this->_uid = $uid;
		return true;
	}

	/**
	 * @return string user id saved in the class
	 */
	public function getUID(){
		return $this->_uid;
	}

	/**
	 * Tries to get the value of the saved parameter for the user that is difined by $_uid
	 * @param string $key parameter name
	 * @return mixed false if there is no this parameter or string with value of this parameter
	 * @throws ParamException if length of parameter name is wrong
	 * @throws DBException if the query to get parameter can't be executed
	 */
	public function getParam($key){
		$this->checkStringParam($key, 'keyName', "getParam");

		$sql = 'SELECT value FROM '.self::table.' WHERE uid=:uid AND key_name=:key';
		$res = $this->_connection->execQuery($sql, array('uid' => $this->_uid, 'key' => $key));

		$value = ($row = mysqli_fetch_assoc($res)) ? $row['value'] : false;
		mysqli_free_result($res);
		return $value;
	}

	/**
	 * Saves the value of the parameter of user. If value exists, it will be rewritten
	 * @param string $key parameter name
	 * @param string $value value that whould be saved
	 * @return boolean false on failure, true in other case
	 * @throws ParamException if length of the parameter name or the value is wrong
	 * @throws DBException if the query to set parameter can't be executed
	 */
	public function setParam($key, $value){
		$this->checkStringParam($key, 'keyName', "getParam");
		$this->checkStringParam($value, 'value', "setParam");

		$sql = 'INSERT INTO '.self::table.'(uid, key_name, value)VALUES(:uid, :key, :value) ON DUPLICATE KEY UPDATE value=VALUES(value)';
		return $this->_connection->execQuery($sql, array('uid' => $this->_uid, 'key' => $key, 'value' => $value));
	}

	/**
	 * Removes the all parameters of the current user
	 * @return boolean false on failure, true in other case
	 * @throws DBException if the query to delete parameters can't be executed
	 */
	public function deleteAllParams(){
		$sql = 'DELETE FROM '.self::table.' WHERE uid=:uid';
		return $this->_connection->execQuery($sql, array('uid' => $this->_uid));
	}

	/**
	 * Removes the parameter
	 * @param string $key Name of the key to romove
	 * @return boolean false on failure, true in other case
	 * @throws ParamException if the parameter name is wrong
	 * @throws DBException if the query to delete parameter can't be executed
	 */
	public function deleteParam($key){
		$this->checkStringParam($key, 'keyName', "deleteParam");

		$sql = 'DELETE FROM '.self::table.' WHERE uid=:uid AND key_name=:key';
		return $this->_connection->execQuery($sql, array('uid' => $this->_uid, 'key' => $key));
	}

	/**
	 * Removes the parameters
	 * @param array $keys The array of key name to remove
	 * @return boolean false on failure, true in other case
	 * @throws ParamException if length of the parameter name is wrong
	 * @throws DBException if the query to delete parameters can't be executed
	 */
	public function deleteParams($keys){
		if(!is_array($keys) || empty($keys)){
			throw new ParamException("Invalid parameter: type of keys should be an array and not empty");
		}

		$sql = 'DELETE FROM '.self::table.' WHERE uid=:uid AND key_name IN(';
		$params = array('uid' => $this->_uid);
		$i = 0;
		foreach($keys as $key){
			$this->checkStringParam($key, 'keyName', "deleteParams");
			$sql .= ":key$i,";
			$params["key$i"] = $key;
			$i++;
		}
		$sql = substr($sql, 0, -1).")";
		return $this->_connection->execQuery($sql, $params);
	}

	/**
	 * Removes expired parameters
	 * @param integer $time unix time shows when parameters is expired
	 * @return boolean false on failure, true in other case
	 * @throws ParamException if type of $time is wrong
	 * @throws DBException if the query to delete parameters can't be executed
	 */
	public function deleteParamsByExpired($time){
		if(!is_int($time)){
			throw new ParamException("Invalid parameter: time should be integer");
		}

		$sql = 'DELETE FROM '.self::table.' WHERE uid=:uid AND UNIX_TIMESTAMP(created) < :expired';
		$params = array('uid' => $this->_uid, 'expired' => $time);

		return $this->_connection->execQuery($sql, $params);
	}

	/**
	 * Checks the limitation of parameter $param by using the rule $name
	 * @param string $param
	 * @param string $name Rule name for parameter
	 * @param string $method Name of method that call this ckeck
	 * @throws ParamException if the rule is wrong
	 */
	private function checkStringParam($param, $name, $method) {
		$rule = self::$params[$name];
		if(!is_string($param)){
			throw new ParamException("Invalid parameter in the method '$method': {$rule['name']} has wrong type");
		}

		if(strlen($param) > $rule['len']){
			throw new ParamException("Invalid parameter in the method '$method': {$rule['name']} should be not more then {$rule['len']} characters");
		}

		if(isset($rule['regexp']) && !preg_match($rule['regexp'], $param)){
			throw new ParamException("Invalid parameter in the method '$method': {$rule['name']} contains wrong characters");
		}
		return true;
	}
}
