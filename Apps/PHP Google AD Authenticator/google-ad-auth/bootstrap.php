<?php
/**
 * Bootstrap for Dual-Provider LDAP Authenticator for Kayako LoginShare v4
 * Loads Google LDAP + Active Directory LDAP support
 *
 * @version 2.0.0
 * @author kraloveckey
 */

if (!function_exists('ldap_connect')) {
    trigger_error('PHP LDAP is required. See <a href="http://www.php.net/ldap">http://www.php.net/ldap</a>', E_USER_ERROR);
}

// ── Paths ──────────────────────────────────────────────────────────────────
define('KAYAKO_GOOGLE_PATH',       dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'google-ad-auth' . DIRECTORY_SEPARATOR);
define('KAYAKO_GOOGLE_CLASS_PATH', KAYAKO_GOOGLE_PATH . 'googleLDAP' . DIRECTORY_SEPARATOR);
define('KAYAKO_ADLDAP_PATH',       KAYAKO_GOOGLE_PATH . 'adLDAP' . DIRECTORY_SEPARATOR);

// ── Config check ───────────────────────────────────────────────────────────
if (!file_exists(KAYAKO_GOOGLE_PATH . 'config.php')) {
    trigger_error('A config file is required (' . KAYAKO_GOOGLE_PATH . 'config.php)', E_USER_ERROR);
}

// ── Load everything ────────────────────────────────────────────────────────
include KAYAKO_GOOGLE_PATH . 'config.php';
include KAYAKO_GOOGLE_PATH . 'google_ldap_provider.php';   // Kayako_Google_LDAP  (extends googleLDAP)
include KAYAKO_GOOGLE_PATH . 'ad_ldap_provider.php';       // Kayako_AD_LDAP      (extends adLDAP)
include KAYAKO_GOOGLE_PATH . 'helpers.php';         // create_google_ldap() + create_ad_ldap()

set_error_handler('ldap_error_handler');

if (KAYAKO_LDAP_SHOW_ERRORS) {
    ini_set('display_errors', true);
    error_reporting(E_ALL | E_NOTICE);
} else {
    ini_set('display_errors', false);
    error_reporting(E_ALL);
}
