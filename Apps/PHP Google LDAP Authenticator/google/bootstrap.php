<?php
/**
 * Bootstrap for Google Workspace LDAP Authenticator for Kayako LoginShare v4
 *
 * @version 1.0.0
 * @author kraloveckey
 *
 * This is a LoginShare Google Workspace LDAP authenticator
 */

// Make sure there is PHP LDAP
if (!function_exists('ldap_connect')) {
    trigger_error('PHP LDAP is required.<br />See <a href="http://www.php.net/ldap">http://www.php.net/ldap</a>', E_USER_ERROR);
}

// Define the ldap paths - pointing to 'google' directory
define('KAYAKO_GOOGLE_PATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'google' . DIRECTORY_SEPARATOR);
define('KAYAKO_GOOGLE_CLASS_PATH', KAYAKO_GOOGLE_PATH . 'googleLDAP' . DIRECTORY_SEPARATOR);

// Make sure there is a config file
if (!file_exists(KAYAKO_GOOGLE_PATH . 'config.php')) {
    if (!file_exists(KAYAKO_GOOGLE_PATH . 'config_sample.php')) {
        trigger_error('You must setup and rename your config_sample.php as per the directions in the google directory', E_USER_ERROR);
    } else {
        trigger_error('A config file is required (' . KAYAKO_GOOGLE_PATH . 'config.php)', E_USER_ERROR);
    }
}

// Get the config & the Kayako Google LDAP classes
include KAYAKO_GOOGLE_PATH . 'config.php';
include KAYAKO_GOOGLE_PATH . 'kayako_google.php';
include KAYAKO_GOOGLE_PATH . 'helpers.php';

// Move errors to our error handler
set_error_handler('ldap_error_handler');

// Turn on or off showing errors/warnings
if (KAYAKO_LDAP_SHOW_ERRORS) {
    ini_set('display_errors', true);
    error_reporting(E_ALL | E_NOTICE);
} else {
    ini_set('display_errors', false);
    error_reporting(E_ALL);
}