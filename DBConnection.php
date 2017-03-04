<?php
require_once('Exceptions.php');

class DBConnection{
	private $_handler = null;

	/**
	 * Constructor of the class
	 * @param string $host
	 * @param string $user
	 * @param string $password
	 * @param string $database 
	 * @throws DBException if connection to DB is failed
	 */
	public function __construct($host, $user, $password, $database){
		$this->_handler = mysqli_connect($host, $user, $password, $database);
		if(mysqli_connect_errno()) {
			throw new DBException("Connect failed: ".mysqli_connect_error());
		}
	}

	public function  __destruct() {
		mysqli_close($this->_handler);
	}

	/**
	 * Function that safely executes the query
	 * @param string $query Parametrized execution of query string
	 * @param array $params array of parameters for the query like array('name' => 10)
	 * @param boolean $checkResult if true and query execution fails then function
	 *  will generate Exception
	 * @return mixed the same as for mysqli_query
	 * @throws DBException if execution fails
	 * @throws ParamException if there is wrong parameters number or names of parameters
	 * @example The following example shows how to use this method
	 * <code>
	 * $sql = "SELECT * FROM table WHERE uid = :uid AND Name IN (:name1, :name2)";
	 * $params = array("uid" => 1, "name1" => "AAA", "name2" => "BBB");
	 * $connection->execQuery($query, $params);
	 * </code>
	 * where $connection is already initialized DBConnection object
	 */
	public function execQuery($query, $params = array(), $checkResult = true){
		if(preg_match_all('/:(\w+)/',$query,$matches)){
			$matches = $matches[1];
			$matches = array_unique($matches);
			if(count($matches) != count($params)){
				throw new ParamException("Invalid parameter number: number of bound variables does not match number of tokens");
			}

			foreach($matches as $match){
				if(!isset($params[$match])){
					throw new ParamException("Invalid parameter: parameter $match is undefined");
				}
				
				$tmp = 'NULL';
				if($params[$match] !== null){
					$tmp = "'".mysqli_real_escape_string($this->_handler, $params[$match])."'";
				}
				$query = str_replace(":$match", $tmp, $query);
			}
		}

		$res = mysqli_query($this->_handler, $query);
		if($checkResult && ($res === false)){
			throw new DBException("Query execution error: ".mysqli_error($this->_handler));
		}
		return $res;
	}
}
