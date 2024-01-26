<?php
/**
 * Additional functions for adLDAP for PHP AD LDAP Authenticator for Kayako LoginShare v4
 *
 * @version 1.0.0
 */

//Get the adLDAP class
include KAYAKO_ADLDAP_PATH.'adLDAP.php';

class Kayako_LDAP extends adLDAP {
	/**
	 * Password
	 *
	 * @var string
	 */
	public $password		= '';
	/**
	 * Userinfo
	 *
	 * @var array
	 */
	public $userinfo		= array();
	/**
	 * Username
	 *
	 * @var string
	 */
	public $username		= '';

	/**
	 * Trick __get if attribute has been called aready
	 *
	 * @var bool
	 */
	private $_attribute		= false;
	/**
	 * Current attributes
	 *
	 * @var array
	 */
	private $_attributes	= array();
	/**
	 * Log file pointer
	 *
	 * @var file pointer
	 */
	private $_log			= null;

	function __get($name) {
		if ($name == 'attribute') {
			$this->_attribute = true;
			return $this;
		} else if ($this->_attribute) {
			//Fix an error with the old version
			if ($name == 'telephone') {
				$name = 'telephonenumber';
			}
			//Give back the correct telephone number
			if ($name == 'telephonenumber') {
				//If there is a telephone number send it back
				if (isset($this->userinfo[$name]) && !empty($this->userinfo[$name])) {
					return $this->_xmlEncode($this->userinfo[$name]);
				}
				if (KAYAKO_LDAP_PHONE_NUMBER) {
					//Try to get the mobile phone
					if (isset($this->userinfo['mobile']) && !empty($this->userinfo['mobile'])) {
						return $this->_xmlEncode($this->userinfo['mobile']);
					}
					//Try to get the home phone
					if (isset($this->userinfo['homephone']) && !empty($this->userinfo['homephone'])) {
						return $this->_xmlEncode($this->userinfo['homephone']);
					}
				}
			}

			return (isset($this->userinfo[$name]) && !empty($this->userinfo[$name])) ? $this->_xmlEncode($this->userinfo[$name]) : '';
		}
	}

	function __destruct() {
		//Run adLDAP's destruct
		parent::__destruct();

		if (!empty($this->_log)) {
			$this->log("----------[ Session End ]----------\n");
			//Close the log
			fclose($this->_log);
		}

		//Manually close the connections
		$this->close();
	}

	/**
	 * Displays an error XML message
	 *
	 * @param string $message
	 * @return void
	 */
	public function displayErrorXML($message = 'Invalid Username or Password') {
		$this->_displayXMLHeader();
		$message = $this->_xmlEncode($message, true);

		$out = "<loginshare>\n";
		$out .= "  <result>0</result>\n";
		$out .= "  <message>$message</message>\n";
		$out .= "</loginshare>\n";

		//Log the XML if enabled
		if (KAYAKO_LDAP_LOG_XML) {
			$this->log($out);
		}

		//Put the XML to the screen
		echo $out;

		$this->_logOutput();
	}

	/**
	 * Display a valid user's XML
	 *
	 * @param string $user_group
	 * @return void
	 */
	public function displayUserXML($user_group = 'Registered') {
		$this->_displayXMLHeader();
		$out = "<loginshare>\n";
		$out .= "  <result>1</result>\n";
		$out .= "  <user>\n";
		$out .= "          <usergroup>$user_group</usergroup>\n";
		$out .= "          <fullname>".$this->attribute->displayname."</fullname>\n";
		if (KAYAKO_LDAP_IMPORT_TITLE) {
			$out .= "          <designation>".$this->attribute->title."</designation>\n";
		} else {
			$out .= "          <designation/>\n";
		}
		if (KAYAKO_LDAP_IMPORT_DEPARTMENT) {
			if (!empty($this->attribute->department)) {
				$out .= "          <organization>".$this->attribute->department."</organization>\n";
			}
		}
		$out .= "          <emails>\n";
		$out .= "                  <email>".$this->attribute->mail."</email>\n";
		$out .= "          </emails>\n";
		$out .= "          <phone>".$this->attribute->telephonenumber."</phone>\n";
		$out .= "  </user>\n";
		$out .= "</loginshare>\n";

		//Log the XML if enabled
		if (KAYAKO_LDAP_LOG_XML) {
			$this->log($out);
		}

		//Put the XML to the screen
		echo $out;

		$this->_logOutput();
	}

	/**
	 * Display a valid staff's XML
	 *
	 * @param string $team
	 * @return void
	 */
	public function displayStaffXML($team) {
		$this->_displayXMLHeader();
		$out = "<loginshare>\n";
		$out .= "  <result>1</result>\n";
		$out .= "  <staff>\n";
		$out .= "          <team>$team</team>\n";
		$out .= "          <firstname>".$this->attribute->givenname."</firstname>\n";
		$out .= "          <lastname>".$this->attribute->sn."</lastname>\n";
		if (KAYAKO_LDAP_IMPORT_TITLE) {
			$out .= "          <designation>".$this->attribute->title."</designation>\n";
		} else {
			$out .= "          <designation/>\n";
		}
		$out .= "          <email>".$this->attribute->mail."</email>\n";
		$out .= "          <mobilenumber>".$this->attribute->mobile."</mobilenumber>\n";
		$out .= "          <signature></signature>\n";
		$out .= "  </staff>\n";
		$out .= "</loginshare>\n";

		//Log the XML if enabled
		if (KAYAKO_LDAP_LOG_XML) {
			$this->log($out);
		}

		//Put the XML to the screen
		echo $out;

		$this->_logOutput();
	}

	/**
	 * Get a user's IP address
	 *
	 * @return string
	 */
	public function getIP() {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$_SERVER['HTTP_CLIENT_IP'];
		}  else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			return $_SERVER['REMOTE_ADDR'];
		}
	}

	/**
	 * Get user's password
	 *
	 * @return string
	 */
	public function getPassword() {
		//If we already know the password send it back
		if (!empty($this->password)) {
			return $this->password;
		}

		//In test mode
		if (KAYAKO_LDAP_TEST) {
			if (!defined('KAYAKO_LDAP_PASSWORD') || KAYAKO_LDAP_PASSWORD == '') {
				trigger_error('When in test mode a password is required', E_USER_ERROR);
			}
			$this->password = KAYAKO_LDAP_PASSWORD;
		} else {
			$this->password = (isset($_POST['password'])) ? $_POST['password'] : '';
		}

		return $this->password;
	}

	/**
	 * Get user information
	 *
	 * @param array $attributes
	 * @return array
	 */
	public function getUser($attributes = array()) {
		if (empty($attributes)) {
			/**
			 * AD attributes to get
			 * For more see:
			 * http://msdn.microsoft.com/en-us/library/windows/desktop/ms675090%28v=vs.85%29.aspx
			 * You want the Ldap-Display-Name
			 */
			$this->_attributes =  array(
				'displayname',
				'title',
				'mail',
				'telephonenumber',

				//Optional
				'department',
				'company',
				'mobile',
				'homephone',
			);
		} else {
			$this->_attributes = $attributes;
		}
		$this->_setUserinfo($this->user()->info($this->getUsername(), $this->_attributes));
		$mail = $this->attribute->mail;
		if (empty($mail)) {
			$this->log('User does not have a email address in AD');
			$this->displayErrorXML('User does not have a email address in AD');
			die();
		}

		return $this->userinfo;
	}

	/**
	 * Get user name
	 *
	 * @return string
	 */
	public function getUsername() {
		//If we already have a user name send it back
		if (!empty($this->username)) {
			return $this->username;
		}

		//In test mode
		if (KAYAKO_LDAP_TEST) {
			if (!defined('KAYAKO_LDAP_USERNAME') || KAYAKO_LDAP_USERNAME == '') {
				trigger_error('When in test mode a username is required', E_USER_ERROR);
			}
			$this->username = KAYAKO_LDAP_USERNAME;
		} else {
			if (isset($_POST['username']) && !empty($_POST['username'])) {
				//If someone used their email address remove it just give the name a try
				if (KAYAKO_LDAP_STRIP_EMAIL && ($pos = strpos($_POST['username'], '@')) !== false) {
					$this->username = substr($_POST['username'], 0, $pos);
				} else {
					$this->username = $_POST['username'];
				}
			} else {
				$this->username = '';
			}
		}

		return $this->username;
	}

	/**
	 * Get staff information
	 *
	 * @param array $attributes
	 * @return array
	 */
	public function getStaff($attributes = array()) {
		if (empty($attributes)) {
			/**
			 * AD attributes to get
			 * For more see:
			 * http://msdn.microsoft.com/en-us/library/windows/desktop/ms675090%28v=vs.85%29.aspx
			 * You want the Ldap-Display-Name
			 */
			$this->_attributes =  array(
				'givenname',
				'sn',
				'mail',
				'mobile',
				'title',
				'telephonenumber',
			);
		} else {
			$this->_attributes = $attributes;
		}
		$this->_setUserinfo($this->user()->info($this->getUsername(), $this->_attributes));

		$mail = $this->attribute->mail;
		if (empty($mail)) {
			$this->log('User does not have a email address in AD');
			$this->displayErrorXML('User does not have a email address in AD');
			die();
		}

		return $this->userinfo;
	}

	/**
	 * Send a string of data to the log file
	 *
	 * @param $string
	 * @return void
	 */
	public function log($string) {
		//If we are not logging we shouldn't continue
		if (!KAYAKO_LDAP_LOG) {
			return;
		}

		//Make sure the log file is open
		if (!$this->_openLog()) {
			return;
		}

		$out = date('[m-d-y - H:i]').' '.$string."\n";
		@fwrite($this->_log, $out);
	}


	/**
	 * Display the common XML header
	 *
	 * @return void
	 */
	private function _displayXMLHeader() {
		header('content-type: text/xml; charset=utf-8');
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	}

	/**
	 * Fix the bug in the admin for the staff where the mobile number cannot use anything but numbers
	 * @param $number
	 * @return mixed
	 * @todo  Left out because Kayako should fix the issue
	 */
	private function _fixPhoneNumber($number) {
		return str_replace(array('-', ' ', '(', ')', '.'), '', $number);
	}

	/**
	 * Log screen output
	 */
	private function _logOutput() {
		//Log the screen output if enabled
		if (KAYAKO_LDAP_LOG_OUTPUT) {
			//Try to get the contents that were sent to the screen
			if (($output = @ob_get_contents()) !== false) {
				//Log them
				$this->log($output);
				//Put them to the screen
				@ob_flush();
			} else {
				$this->log('Could not capture output');
			}
		}
	}

	/**
	 * Attempt to open the log file
	 *
	 * @return bool
	 */
	private function _openLog() {
		//If there is already a log open we shouldn't continue
		if (!empty($this->_log)) {
			return true;
		}

		//Make sure that the log directory is writable
		if (!is_writable(KAYAKO_LDAP_PATH.'log'.DIRECTORY_SEPARATOR)) {
			$this->displayErrorXML('Log directory ('.KAYAKO_LDAP_PATH.'log'.DIRECTORY_SEPARATOR.') is not writable!');
			die();
		}

		//Open or create a new log.txt file
		if (($this->_log = fopen(KAYAKO_LDAP_PATH.'log'.DIRECTORY_SEPARATOR.'log.txt', 'a')) === false) {
			return false;
		}

		return true;
	}

	/**
	 * Setup a clean _userinfo var
	 *
	 * @param $userinfo
	 * @return void
	 */
	private function _setUserinfo($userinfo) {
		if (empty($userinfo) || !is_array($userinfo) || !isset($userinfo[0])) {
			$this->userinfo = array();
			return;
		}

		foreach ($userinfo[0] as $k => $v) {
			if (is_int($k)) {
				continue;
			}
			if (is_array($v) || !isset($v[0])) {
				$this->userinfo[$k] = $v[0];
			} else {
				$this->userinfo[$k] = '';
			}
		}
	}

	/**
	 * Fix string for XML
	 *
	 * @param $string
	 * @param bool $encode
	 * @return string
	 */
	private function _xmlEncode($string, $encode = false) {
		if (empty($string)) {
			return $string;
		}

		//If encode is true then try to re-encode the text to UTF8
		if ($encode) {
			if (extension_loaded('mbstring') && function_exists('mb_convert_encoding')) {
				$string = mb_convert_encoding($string, 'UTF-8');
			} else if(function_exists('utf8_encode')){
				$string = utf8_encode($string);
			}

			$string = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
		}

		//Fix any possible HTML special characters, using XML1 encoding if possible
		if (defined('ENT_XML1')) {
			$string = htmlspecialchars($string, ENT_XML1, 'UTF-8');
		} else {
			$string = htmlspecialchars($string, ENT_COMPAT, 'UTF-8');
		}


		return trim($string);
	}
}
