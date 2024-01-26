<?php
/**
 * Helper functions for PHP AD LDAP Authenticator for Kayako LoginShare v4
 *
 * @version 1.0.0
 */


/**
 * Tries to create the adLDAP class and authenticate to it
 *
 * @param string $ldap_account_suffix
 * @param string $ldap_base_dn
 * @param string $ldap_domain_controllers
 * @return bool
 * @throws Exception
 */
function create_adldap($ldap_account_suffix, $ldap_base_dn, $ldap_domain_controllers) {
	global $adldap, $use_adldap_options, $adldap_options;

	try {
		$options = array(
			'account_suffix'		=> $ldap_account_suffix,
			'base_dn'				=> $ldap_base_dn,
			'domain_controllers'	=> $ldap_domain_controllers,
		);

		if ($use_adldap_options) {
			if ($adldap_options['admin_user_name'] === '') {
				$adldap_options['admin_user_name'] = NULL;
				$adldap_options['admin_password'] = NULL;
			}
			$options = array_merge($options, $adldap_options);
		}

		//Try to setup a new instance
		$adldap = new Kayako_LDAP($options);

		//Logging the variables used to help troubleshoot
		$adldap->log('ldap_account_suffix: '. var_export($ldap_account_suffix, true));
		$adldap->log('ldap_base_dn: '. var_export($ldap_base_dn, true));
		$adldap->log('ldap_domain_controllers: '. var_export($ldap_domain_controllers, true));
		$adldap->log('KAYAKO_LDAP_TEST: '. var_export(KAYAKO_LDAP_TEST, true));

		//Commented out for security
		$adldap->log('Username: '.$adldap->getUsername());
		//$adldap->log('Username: '.$adldap->getUsername().' - Password: '.$adldap->getPassword());

		//Authenticate
		$authUser = $adldap->authenticate($adldap->getUsername(), $adldap->getPassword());

		if ($authUser) {
			$adldap->log('Authenticated: '. var_export($authUser, true));
			return true;
		} else {
			throw new Exception('');
		}
	} catch (Exception $e) {
		if (is_object($adldap)) {
			$adldap->log('Could not create new Kayako_LDAP class or authentication failed ('.$ldap_account_suffix.' => '.$ldap_base_dn.').  Message: '.$e->getMessage().' -- '.$adldap->getLastError());
		} else {
			echo 'Something is seriously wrong.  Could not create new Kayako_LDAP class';
			@ob_flush();
			die();
		}
		return false;
	}

	return false;
}

/**
 * Manages any errors with ldap
 *
 * @param $errno
 * @param $errstr
 * @param $errfile
 * @param $errline
 * @return bool
 */
function ldap_error_handler($errno, $errstr, $errfile, $errline) {
	global $adldap;

	$die = false;
	switch ($errno) {
		case E_USER_ERROR:
			$out = "ERROR: [$errno] $errstr  -- line $errline in file $errfile";
			$die = true;
			break;

		case E_USER_WARNING:
			$out = "WARNING: [$errno] $errstr";
			break;

		case E_USER_NOTICE:
			$out = "NOTICE: [$errno] $errstr";
			break;

		default:
			$out = "UNKNOWN: [$errno] $errstr";
			break;
	}

	if (is_object($adldap)) {
		$adldap->log($out);
	} else if ($die) {
		//If we are going to die for some reason we need to send back a valid Kayako XML
		header('content-type: text/xml; charset=utf-8');
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		echo "<loginshare>\n";
		echo "  <result>0</result>\n";
		echo "  <message>$out</message>\n";
		echo "</loginshare>\n";
		@ob_flush();
		die();
	}

	return true;
}