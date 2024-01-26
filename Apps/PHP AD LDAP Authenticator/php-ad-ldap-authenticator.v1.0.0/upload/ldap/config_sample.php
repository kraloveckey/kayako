<?php

/**
 * Please read the README.md first!
 */
//#########################################################################################

/**
 * YOU MUST CHANGE THESE FOR THIS TO WORK
 */

/**
 * LDAP domain info
 * AD Prefix => Base DN
 * array('@mydomain.local' => 'DC=mydomain,DC=local')
 */
$ldap_domain_info = array('@mydomain.local' => 'DC=mydomain,DC=local');

/**
 * Domain controller(s).  You can use names or IPs
 * You can have more than one domain server by using
 * array('dc01.mydomain.local', 'dc02.mydomain.local', 'dc03.mydomain.local');
 */
$ldap_domain_controllers = array('dc01.mydomain.local');

/**
 * Change for AD staff groups a user must be in to login
 * AD_Group => Kayako_Staff_Team
 * Example:
 * 	$staff_groups = array('IS Group' => 'IS');
 * This is required only if you are using this for staff
 */
$staff_groups = array('Group' => 'Staff', 'Group1' => 'Staff1');

//Everything below here is optional
//#########################################################################################

/**
 * Change for valid AD user groups (see KAYAKO_LDAP_ERROR_USERGROUP as well)
 * AD_Group => Kayako_User_Group
 * Example:
 * 	$user_groups = array('Customer Service' => 'CS');
 * This is not required for a user!!
 */
$user_groups = array('Group2' => 'User', 'Group3' => 'User1');

/**
 * Allows single users to bypass the default (Registered) group
 * 'AD Username' => 'Kayako_Group'
 * Example:
 * 	$user_group_bypass = array('jdoe' => 'Technicians');
 * This is not required
 * MUST use lowercase for AD username!!
 */
$user_group_bypass = array();

/**
 * Change to include any AD groups you want the user to be in to authenticate
 * Example:
 *  $valid_user_groups = array('Customer Service', 'IT');
 * If they are not in one of these groups they will not be able to login
 */
$valid_user_groups = array();

/**
 * Change to false if you want users not in the $user_group to still be able to login
 */
define('KAYAKO_LDAP_ERROR_USERGROUP', true);

/**
 * If more than one domain controller is used adLDAP will attempt
 * to connect to one of the controllers.  If failed it will try another.
 * If set to false it will use the default behavior which is to try to connect
 * to a controller no matter what.  If failed it will NOT try another server
 */
define('KAYAKO_LDAP_VERIFY_CONTROLLER', true);

/**
 * Change to true to enable testing mode
 * If left disabled username and password is ignored
 */
define('KAYAKO_LDAP_TEST', false);

/**
 * Change to true to show all errors & warnings
 */
define('KAYAKO_LDAP_SHOW_ERRORS', true);

/**
 * Change to true to enable logging mode
 * Your ldap/log directory must be writable
 */
define('KAYAKO_LDAP_LOG', true);

/**
 * Logs the outgoing XML
 * Logging must be enable for this to work
 */
define('KAYAKO_LDAP_LOG_XML', true);

/**
 * Attempts to log everything that is displayed to the screen
 * Logging must be enable for this to work
 */
define('KAYAKO_LDAP_LOG_OUTPUT', true);

/**
 * Enter values to test with
 */
define('KAYAKO_LDAP_USERNAME', '');
define('KAYAKO_LDAP_PASSWORD', '');

/**
 * Change to false if you do not want to try to get mobile or home number if the telephone number is empty
 */
define('KAYAKO_LDAP_PHONE_NUMBER', true);

/**
 * Change to false if you do not want to import AD Department to Organization in the user's profile
 */
define('KAYAKO_LDAP_IMPORT_DEPARTMENT', true);

/**
 * Change to false if you do not want to import AD Job Title to Title/Position in the user's profile
 */
define('KAYAKO_LDAP_IMPORT_TITLE', true);

/**
 * Change to false if you do not want to strip @domain
 */
define('KAYAKO_LDAP_STRIP_EMAIL', true);

//Everything below here is optional adLDAP settings
//#########################################################################################

/**
 * Ignore these!
 */
global $use_adldap_options, $adldap_options;

/**
 * If you want to use ANY of the options below then change this to true FIRST
 * Example:
 * 	$use_adldap_options = true;
 */
$use_adldap_options = false;

/**
 * Admin Username / Password is an account with higher privileges to perform privileged operations.
 * Example
 * 	$admin_user_name = 'CIO';
 *	$admin_password = 'MyPa$$w0rd';
 * This is not required!
 */
$admin_user_name = '';
$admin_password = '';

/**
 * Use SSL
 * Example:
 * 	$use_ssl = true;
 */
$use_ssl = false;

/**
 * Use TLS
 * Example:
 * 	$use_tls = true;
 */
$use_tls = false;

/**
 * Change AD Port from default
 * Example:
 * 	$ad_port = 12345;
 */
$ad_port = 389;

/**
 * Ignore this!
 */
$adldap_options = array(
	'admin_user_name'	=> $admin_user_name,
	'admin_password'	=> $admin_password,
	'use_ssl'			=> $use_ssl,
	'use_tls'			=> $use_tls,
	'ad_port'			=> $ad_port,
);