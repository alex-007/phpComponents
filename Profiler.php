<?php
require_once('Exceptions.php');

/**
 * Class that shows profiling informaion for queryes
 * initialization of the class should be before executing the queries
 */
class Profiler{
	/**
	 * minimal duration time for showing profiled query (in msec)
	 * @var float
	 */
	private $minQuery;

	/**
	 * minimal duration time for showing details of the profiled query (in msec)
	 * @var float
	 */
	private $minDetail;

	private $pdo = null;

	/**
	 * Constructor of the class
	 * @param PDO $pdo
	 * @param float $minQuery minimal duration time for showing profiled query (in msec)
	 * Default 30 msec
	 * @param float $minDetail minimal duration time for showing profiled query (in msec)
	 * Default 1 msec
	 * throws ParamException
	 */
	public function __construct(PDO $pdo, $minQuery = 30, $minDetail = 1){
		if($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) != 'mysql'){
			throw new ParamException("This class can work only with mysql");
		}
		$this->pdo = $pdo;
		$this->pdo->exec("set profiling_history_size=100;");
		$this->pdo->exec("set profiling=1;");
		$this->setMinQuery($minQuery);
		$this->setMinDetail($minDetail);
	}

	/**
	 * @param float $value
	 * @return Profile
	 * throws ParamException
	 */
	public function setMinQuery($value){
		if(!is_numeric($value)){
			throw new ParamException('The value should be float');
		}
		$this->minQuery = $value;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getMinQuery(){
		return $this->minQuery;
	}

	/**
	 * @param float $value
	 * @return Profile
	 * throws ParamException
	 */
	public function setMinDetail($value){
		if(!is_numeric($value)){
			throw new ParamException('The value should be float');
		}
		$this->minDetail = $value;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getMinDetail(){
		return $this->minDetail;
	}

	/**
	 * Returns information about all profiled queries if execution time was
	 * more then $minQuery
	 * @return array
	 */
	public function AllProfiles(){
		$ret = array();
		$sql = "show profiles;";
		$res = $this->pdo->query($sql);

		foreach($res as $row){
			if($row['Duration'] * 1000 > $this->minQuery){
				$ret[] = $row;
			}
		}
		return $ret;
	}

	/**
	 * Returns details for profiled query number $n
	 * All details with execution time lesser then $minDetail won't be in result
	 * @param integer $n
	 * @return array
	 */
	public function QueryProfile($n){
		$n = intval($n);
		$ret = array();
		$sql = "show profile for query ".$n;
		$res = $this->pdo->query($sql);
		foreach($res as $row){
			if($row['Duration'] * 1000 > $this->minDetail){
				$ret[] = $row;
			}
		}
		return $ret;
	}
}
