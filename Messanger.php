<?php
require_once('Exceptions.php');

/**
 * Class that implements sending messages
 */
class Messanger{

	/**
	 * Maximum length of not encoded message to send
	 */
	const maxMessLength = 255;

	/**
	 * Maximum length of module name
	 */
	const maxModuleLength = 64;

	/**
	 * Regular expression for validating module name
	 */
	const MODULE_REGEXP = "/^\w+$/";

	/**
	 * URL of the sender script
	 * @var string
	 */
	private $_sender = null;

	/**
	 * Jabber account of the bot
	 * @var string
	 */
	private $_from = null;

	/**
	 * Jabber account(s) of the destination of the user(s)
	 * @var string
	 */
	private $_to = null;

	/**
	 * Constructor of the class
	 * @param string $sender url of the sender script
	 * @param string $from habber account of bot that sends messages
	 * @throws ParamException if the parameters are incorrect
	 */
	public function __construct($sender, $from){
		$this->validateSender($sender);
		$this->_sender = $sender;

		$this->setFrom($from);
	}

	/**
	 * Sets jabber account to the bot
	 * @param string $value
	 * @return Messanger link to the current object
	 * @throws ParamException if the parameters are incorrect
	 */
	public function setFrom($value){
		$this->validateEmail($value);
		$this->_from = $value;
		return $this;
	}

	/**
	 * Returns current bot jabber account
	 * @return string
	 */
	public function getFrom(){
		return $this->_from;
	}

	/**
	 * Sets the jabber account(s) of the destination user(s)
	 * if there are several jabber accounts they should be separated by comma (,)
	 * @param string $value
	 * @return Messanger link to the current object
	 * @throws ParamException if the parameters are incorrect
	 */
	public function setTo($value){
		if(!is_string($value)){
			throw new ParamException("Invalid parameter: `to` should be a string");
		}

		$values = explode(',', $value);
		foreach($values as &$to){
			$to = trim($to);
			$this->validateEmail($to);
		}
		
		$this->_to = join(",",$values);
		return $this;
	}

	/**
	 * Returns current jabber account(s) of the destination user(s)
	 * @return string
	 */
	public function getTo(){
		return $this->_to;
	}

	/**
	 * Sends message to the users that saved in the
	 * @param string $message
	 * @param string $module module name
	 * @return boolean|string true if all is OK alse it will sends the error from
	 * the sender script
	 * @throws ParamException if the parameters are incorrect
	 */
	public function sendMessage($message, $module = false){
		if(!isset($this->_to)){
			throw new ParamException("Parameter `to` should be initialized first");
		}
		
		$this->validateMessage($message);
		if(!empty($module)){
			$this->validateModule($module);
		}

		$delimiter = strpos($this->_sender, "?")?'&':'?';

		$url = $this->_sender.$delimiter."bot=".$this->_from."&msg=".base64_encode($message).
					(!empty($module)?"&source=$module":"")."&to=".$this->_to;

		$res = file_get_contents($url);

		return empty($res)?true:$res;
	}

	/**
	 * Validates the URL to the sender script
	 * @param string $sender 
	 * @return boolean true if the URL is correct
	 * @throws ParamException if the URL is incorrect
	 */
	private function validateSender($sender){
		if(!is_string($sender) || empty($sender)){
			throw new ParamException("Invalid parameter: sender should be not empty string");
		}

		if((strpos($sender, "http://") !== 0) || !filter_var($sender, FILTER_VALIDATE_URL)){
			throw new ParamException("Invalid parameter: incorrect sender url");
		}

		return true;
	}

	/**
	 * Validates the email/jabber account
	 * @param string $email
	 * @return boolean true if email is OK
	 * @throws ParamException if the email is incorrect
	 */
	private function validateEmail($email) {
		if(!is_string($email) || empty($email)){
			throw new ParamException("Invalid parameter: email should be not empty string");
		}

		if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
			throw new ParamException("Invalid parameter: Incorrect email");
		}

		return true;
	}

	/**
	 * Validates the message
	 * @param string $message
	 * @return boolean true if message is OK 
	 * @throws ParamException if the message is incorrect
	 */
	private function validateMessage($message){
		if(!is_string($message)){
			throw new ParamException("Invalid parameter: message should be a string");
		}

		if(strlen($message) > self::maxMessLength){
			throw new ParamException("Invalid parameter: message should be not more then "
							.self::maxMessLength." characters");
		}

		return true;
	}
	
	/**
	 * Validates the module name
	 * @param string $module
	 * @return boolean true if module name is OK
	 * @throws ParamException if the message is incorrect
	 */
	private function validateModule($module){
		if(!is_string($module)){
			throw new ParamException("Invalid parameter: module name should be a string");
		}

		if(strlen($module) > self::maxModuleLength){
			throw new ParamException("Invalid parameter: module name should be not more then "
							.self::maxMmoduleLength." characters");
		}

		if(!preg_match(self::MODULE_REGEXP, $module)){
			throw new ParamException("Invalid parameter: module name is not correct");
		}

		return true;
	}
}
