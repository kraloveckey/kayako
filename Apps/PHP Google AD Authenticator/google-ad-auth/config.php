<?php
/**
 * Configuration for Dual-Provider LDAP Authenticator
 * Covers: Google Workspace LDAP (via stunnel) + Active Directory LDAP
 *
 * @version 2.0.1
 * @author kraloveckey
 */

// ══════════════════════════════════════════════════════════════════════════════
// SECTION 1 — GOOGLE WORKSPACE LDAP (via stunnel on 127.0.0.1:389)
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Google LDAP domain info
 * Format: '@domain.com' => 'dc=domain,dc=com'
 */
$ldap_domain_info = array('@your-google-domain.com' => 'dc=your-google-domain,dc=com');

/**
 * stunnel proxy address — do not change unless your stunnel listens elsewhere
 */
$ldap_domain_controllers = array('127.0.0.1');

/**
 * Google Groups => Kayako Staff Team
 * Use full DNs to avoid name conflicts between Google and AD groups
 * Example: 'cn=helpdesk.staff,ou=Groups,dc=google-domain,dc=com' => 'Default Team'
 */
$google_staff_groups = array(
    'cn=helpdesk.staff,ou=Groups,dc=your-google-domain,dc=com' => 'Default Team',
);

/**
 * Google Groups => Kayako User Group
 * Example: 'cn=helpdesk.admins,ou=Groups,dc=google-domain,dc=com' => 'System Administrators'
 */
$google_user_groups = array(
    'cn=helpdesk.admins,ou=Groups,dc=your-google-domain,dc=com' => 'System Administrators',
    'cn=helpdesk.users,ou=Groups,dc=your-google-domain,dc=com'  => 'Registered',
);

/**
 * Google LDAP Service Account credentials
 * Created in Google Admin Console → Apps → LDAP
 */
define('KAYAKO_GOOGLE_LDAP_USERNAME', 'GOOGLE_LDAP_USERNAME'); // Replace with your Google LDAP access credentials username
define('KAYAKO_GOOGLE_LDAP_PASSWORD', 'GOOGLE_LDAP_PASSWORD'); // Replace with your Google LDAP access credentials password

// stunnel handles SSL/TLS — PHP connects via plain LDAP to localhost
$google_adldap_options = array(
    'admin_user_name' => KAYAKO_GOOGLE_LDAP_USERNAME,
    'admin_password'  => KAYAKO_GOOGLE_LDAP_PASSWORD,
    'use_ssl'         => false,
    'use_tls'         => false,
    'ad_port'         => 389,
);

// ══════════════════════════════════════════════════════════════════════════════
// SECTION 2 — ACTIVE DIRECTORY LDAP
// ══════════════════════════════════════════════════════════════════════════════

/**
 * AD domain info
 * Format: '@ad-domain.local' => 'DC=ad-domain,DC=local'
 */
$ad_domain_info = array('@ad-domain.local' => 'DC=ad-domain,DC=local');

/**
 * AD domain controller(s) — name or IP
 */
$ad_domain_controllers = array('dc01.ad-domain.local');

/**
 * AD Groups => Kayako Staff Team
 * Plain group names as used by adLDAP (not full DNs)
 */
$ad_staff_groups = array(
    'Helpdesk Staff' => 'Default Team',
);

/**
 * AD Groups => Kayako User Group
 */
$ad_user_groups = array(
    'Helpdesk Admins' => 'System Administrators',
    'Helpdesk Users'  => 'Registered',
);

/**
 * AD Service Account (must have read rights to search users & groups).
 *
 * NOTE: the key name here is 'admin_user_name' (with underscore between
 * "user" and "name") — this is just a local array key used inside config.php
 * and $google_adldap_options.  helpers.php maps it to 'admin_username' (no
 * underscore) when building the options array passed to adLDAP, because that
 * is the key name adLDAP::__construct() actually reads.
 */
define('KAYAKO_AD_LDAP_USERNAME', 'AD_LDAP_USERNAME');   // sAMAccountName only, no domain suffix
define('KAYAKO_AD_LDAP_PASSWORD', 'AD_LDAP_PASSWORD');

$ad_adldap_options = array(
    'admin_user_name' => KAYAKO_AD_LDAP_USERNAME,
    'admin_password'  => KAYAKO_AD_LDAP_PASSWORD,
    'use_ssl'         => false,
    'use_tls'         => false,
    'ad_port'         => 389,
);

/**
 * The AD attribute where the Google Workspace primary email is stored.
 * proxyAddresses entries look like: "SMTP:user@google-domain.com" (uppercase = primary)
 * We extract the primary SMTP address and use it as the Kayako email.
 *
 * Set to 'mail' if your AD has the Google email in the plain mail attribute instead.
 */
define('KAYAKO_AD_EMAIL_SOURCE', 'proxyaddresses');   // 'proxyaddresses' or 'mail'

// ══════════════════════════════════════════════════════════════════════════════
// SECTION 3 — SHARED SETTINGS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Bypass list — these users skip group checks entirely
 * Key: lowercase username (email or sAMAccountName)
 * Value: Kayako user group name
 */
$user_group_bypass = array();

/**
 * Throw an error when user is not in any allowed group.
 * Set to false to let ungrouped users log in as 'Registered'.
 */
define('KAYAKO_LDAP_ERROR_USERGROUP', true);

define('KAYAKO_LDAP_VERIFY_CONTROLLER', true);
define('KAYAKO_LDAP_TEST',         false);
// Show PHP fatal/parse errors in the HTTP response. Keep false in production:
// warnings and notices are always written to log/log.txt by ldap_error_handler().
define('KAYAKO_LDAP_SHOW_ERRORS',  false);
define('KAYAKO_LDAP_LOG',          true);
define('KAYAKO_LDAP_LOG_XML',      false);
define('KAYAKO_LDAP_LOG_OUTPUT',   false);

define('KAYAKO_LDAP_PHONE_NUMBER',        false);
define('KAYAKO_LDAP_IMPORT_DEPARTMENT',   false);
define('KAYAKO_LDAP_IMPORT_TITLE',        false);

/**
 * Keep STRIP_EMAIL false:
 *   - Google provider needs the full email for mail= filter
 *   - AD provider receives sAMAccountName (no @), so stripping is irrelevant
 */
define('KAYAKO_LDAP_STRIP_EMAIL', false);

// Legacy aliases expected by adLDAP's randomController()
global $use_adldap_options, $adldap_options;
$use_adldap_options = true;
$adldap_options     = $ad_adldap_options;   // adLDAP base class reads this via global

// ══════════════════════════════════════════════════════════════════════════════
// SECTION 4 — NESTED GROUP MEMBERSHIP FILES (Google LDAP only)
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Groups that require file-based membership check for deeply nested members.
 *
 * Google Secure LDAP only returns direct memberOf — it does not expand nested
 * group membership transitively.  googleLDAP::inGroup() already handles one
 * level of nesting via a second LDAP query, but for deeper trees you need a
 * pre-built flat file.
 *
 * A cron script (/etc/stunnel/sync_group_members.sh) rebuilds these files
 * periodically (recommended: every hour) using the service account
 * credentials.
 *
 * Format:
 *   Key   — full group DN, same value used in $google_staff_groups / $google_user_groups
 *   Value — absolute path to the membership file (one email per line, UTF-8)
 *
 * How it works:
 *   For a group listed here, inGroup() checks only the file and makes no LDAP
 *   queries for that group.  If the file does not exist or is not readable,
 *   the user is treated as not a member (access denied for that group).
 *
 * To add more nested groups in the future, just add entries to this array —
 * no changes to any other file are needed.
 *
 * Example crontab entry (runs every hour):
 *   0 * * * * /etc/stunnel/sync_group_members.sh
 */
$google_nested_group_files = array(
    // 'cn=helpdesk.staff,ou=Groups,dc=your-google-domain,dc=com' => '/etc/stunnel/nested_helpdesk.staff.txt',
    // 'cn=helpdesk.users,ou=Groups,dc=your-google-domain,dc=com' => '/etc/stunnel/nested_helpdesk.users.txt',
    //
    // Uncomment the lines above (and adjust paths) for groups that have members
    // via deep nesting.  Add extra lines for additional groups as needed.
);
