<?php
/**
 * Configuration for Google Workspace LDAP Authenticator
 * Optimized for stunnel4 usage
 * * @version 1.0.0
 * @author kraloveckey
 */

//#########################################################################################

/**
 * LDAP domain info for Google Workspace
 * Google Base DN usually looks like: dc=dns,dc=com
 */
$ldap_domain_info = array('@dns.com' => 'dc=dns,dc=com');

/**
 * Domain controller: Using stunnel4 on localhost
 */
$ldap_domain_controllers = array('127.0.0.1');

/**
 * Google Groups => Kayako Staff Team
 * Ensure Google Group names match exactly
 */
$staff_groups = array('cn=group_name,ou=Groups,dc=dns,dc=com' => 'Default Team');

//#########################################################################################

/**
 * Google Groups => Kayako User Group
 */
$user_groups = array(
    'cn=helpdesk.admins,ou=Groups,dc=dns,dc=com' => 'System Administrators',
    'cn=helpdesk.security,ou=Groups,dc=dns,dc=com' => 'Cybersecurity'
);

/**
 * Bypass list (lowercase email)
 */
$user_group_bypass = array();

/**
 * Valid user groups for general access
 */
$valid_user_groups = array();

/**
 * Throw error if user is not in any assigned group
 */
define('KAYAKO_LDAP_ERROR_USERGROUP', true);

/**
 * Verify connectivity to 127.0.0.1
 */
define('KAYAKO_LDAP_VERIFY_CONTROLLER', true);

define('KAYAKO_LDAP_TEST', false);
define('KAYAKO_LDAP_SHOW_ERRORS', true);
define('KAYAKO_LDAP_LOG', true);
define('KAYAKO_LDAP_LOG_XML', false);
define('KAYAKO_LDAP_LOG_OUTPUT', false);

/**
 * Credentials for Google LDAP (Service Account created in Google Admin Console)
 */
define('KAYAKO_LDAP_USERNAME', 'GOOGLE_LDAP_USERNAME'); // Replace this with your Google LDAP username
define('KAYAKO_LDAP_PASSWORD', 'GOOGLE_LDAP_PASSWORD'); // Replace with your Google LDAP password

define('KAYAKO_LDAP_PHONE_NUMBER', false);
define('KAYAKO_LDAP_IMPORT_DEPARTMENT', false);
define('KAYAKO_LDAP_IMPORT_TITLE', false);

/**
 * IMPORTANT: Set to FALSE for Google Workspace
 * We need the full email address for search (mail=...)
 */
define('KAYAKO_LDAP_STRIP_EMAIL', false);

//#########################################################################################

global $use_adldap_options, $adldap_options;

/**
 * We use these options to pass port and credentials to the Google class
 */
$use_adldap_options = true;

$admin_user_name = KAYAKO_LDAP_USERNAME;
$admin_password = KAYAKO_LDAP_PASSWORD;

/**
 * Since stunnel4 handles SSL/TLS, the PHP script connects to it via plain LDAP (389)
 */
$use_ssl = false;
$use_tls = false;
$ad_port = 389;

$adldap_options = array(
    'admin_user_name' => $admin_user_name,
    'admin_password'  => $admin_password,
    'use_ssl'         => $use_ssl,
    'use_tls'         => $use_tls,
    'ad_port'         => $ad_port,
);