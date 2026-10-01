<?php
/**
 * Dual-Provider LoginShare Authenticator for Kayako v4
 * Providers: Google Workspace LDAP (via stunnel) + Active Directory LDAP
 *
 * Auth routing:
 *   username contains '@'  → try Google LDAP first, then AD as fallback
 *   username without '@'   → try AD first (sAMAccountName), then Google as fallback
 *
 * @version 2.0.1
 * @author kraloveckey
 */

@ob_start();
header('content-type: text/html; charset=utf-8');

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'google-ad-auth' . DIRECTORY_SEPARATOR . 'bootstrap.php';

// FIX: ensure log/ directory exists before any writes — bootstrap.php does not
// create it, individual provider objects do it lazily inside _openLog(), but
// file_put_contents below runs before any provider is instantiated.
$_logDir = dirname(__FILE__) . '/google-ad-auth/log/';
if (!is_dir($_logDir)) { @mkdir($_logDir, 0755, true); }

// DEBUG ONLY: uncomment one of the blocks below to log POST data.

// Option 1: log POST with password masked
// $postLog = array_merge($_POST, isset($_POST['password']) ? array('password' => '********') : array());
// file_put_contents(
//     $_logDir . 'log.txt',
//     date('[m-d-y - H:i]') . ' [POST] ' . var_export($postLog, true) . "\n",
//     FILE_APPEND
// );

// Option 2: log POST with real password visible
// file_put_contents(
//     $_logDir . 'log.txt',
//     date('[m-d-y - H:i]') . ' [POST] ' . var_export($_POST, true) . "\n",
//     FILE_APPEND
// );

// ── Determine provider order based on username format ──────────────────────
$rawUsername = isset($_POST['username']) ? trim($_POST['username']) : '';
$hasAtSign   = (strpos($rawUsername, '@') !== false);

// $authUser === false | 'google' | 'ad'
$authUser     = false;
$authProvider = null;

if ($hasAtSign) {
    // Looks like an email → try Google first
    $authUser = _try_google_provider();
    if ($authUser) {
        $authProvider = 'google';
    } else {
        $authUser = _try_ad_provider();
        if ($authUser) { $authProvider = 'ad'; }
    }
} else {
    // Looks like sAMAccountName → try AD first
    $authUser = _try_ad_provider();
    if ($authUser) {
        $authProvider = 'ad';
    } else {
        $authUser = _try_google_provider();
        if ($authUser) { $authProvider = 'google'; }
    }
}

// ── Grab the active handler object for logging/output ──────────────────────
global $googleLdap, $adLdap;
/** @var Kayako_Google_LDAP|Kayako_AD_LDAP $handler */
$handler = ($authProvider === 'ad') ? $adLdap : $googleLdap;

// Log test mode
if (isset($_GET['test']) && !empty($_GET['test'])) {
    $handler->log('HTML Test: ' . $_GET['test']);
}

// ── Process authenticated user ─────────────────────────────────────────────
if ($authUser) {
    $handler->log('Auth provider: ' . $authProvider);

    if (isset($_GET['type'])) {
        $handler->log('Type: ' . var_export($_GET['type'], true));
    } else {
        $handler->log('Type: Empty (Default to user)');
    }

    // ── USER flow ──────────────────────────────────────────────────────────
    if (!isset($_GET['type']) || $_GET['type'] == 'user') {
        $handler->getUser();

        // Bypass list
        if (isset($user_group_bypass) && !empty($user_group_bypass) &&
            isset($user_group_bypass[strtolower($handler->getUsername())])) {
            $handler->log('User found in bypass list. Access granted.');
            $handler->displayUserXML($user_group_bypass[strtolower($handler->getUsername())]);
            die();
        }

        // Group-based access, provider-specific config
        $groups = ($authProvider === 'ad') ? $ad_user_groups : $google_user_groups;

        if (!empty($groups) && is_array($groups)) {
            $handler->log('Group restrictions enabled [' . $authProvider . ']');
            foreach ($groups as $group => $user_group) {
                $canInGroup = ($authProvider === 'ad') ? $handler->inGroup($handler->getUsername(), $group) : $handler->user()->inGroup($handler->getUsername(), $group);
                if ($canInGroup) {
                    $handler->log('User belongs to allowed group. Access granted.');
                    $handler->displayUserXML($user_group);
                    die();
                }
            }
            if (KAYAKO_LDAP_ERROR_USERGROUP) {
                $handler->log('Group check failed. Access denied.');
                $handler->displayErrorXML('Access denied. You do not have the required permissions.');
            } else {
                $handler->log('Group not found, but login allowed by config.');
                $handler->displayUserXML();
            }
            die();
        } else {
            $handler->log('No group restrictions. Access granted.');
            $handler->displayUserXML();
            die();
        }

    // ── STAFF flow ─────────────────────────────────────────────────────────
    } else if ($_GET['type'] == 'staff') {
        $handler->getStaff();

        $groups = ($authProvider === 'ad') ? $ad_staff_groups : $google_staff_groups;

        if (!empty($groups) && is_array($groups)) {
            $handler->log('Staff groups validation enabled [' . $authProvider . ']');
            foreach ($groups as $group => $team) {
                $canInGroup = ($authProvider === 'ad') ? $handler->inGroup($handler->getUsername(), $group) : $handler->user()->inGroup($handler->getUsername(), $group);
                if ($canInGroup) {
                    $handler->log('Staff member verified in group.');
                    $handler->displayStaffXML($team);
                    die();
                }
            }
            $handler->log('Staff group not found.');
            $handler->displayErrorXML('Access denied. Staff member not in an authorized group.');
            die();
        } else {
            $handler->log('Critical: Staff groups not configured in config.php');
            $handler->displayErrorXML('Staff authentication is not properly configured.');
            die();
        }

    } else {
        $handler->log('Invalid request type.');
        $handler->displayErrorXML('Unknown authentication method.');
        die();
    }

} else {
    // FIX: if both providers failed to instantiate at all (e.g. stunnel down
    // AND AD unreachable), both objects are null — calling a method on null
    // would cause a fatal error with no XML output to the client.
    // Output a safe XML error response directly in that case.
    if (!is_object($googleLdap) && !is_object($adLdap)) {
        header('content-type: text/xml; charset=utf-8');
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<loginshare>\n  <result>0</result>\n";
        echo "  <message>Authentication service unavailable. Please try again later.</message>\n";
        echo "</loginshare>\n";
        die();
    }
    $errHandler = is_object($googleLdap) ? $googleLdap : $adLdap;
    $errHandler->log('Authentication failed for user: ' . $rawUsername);
    $errHandler->displayErrorXML('Invalid credentials or account issue.');
    die();
}

// ── Provider attempt helpers ───────────────────────────────────────────────

/**
 * Try to authenticate via Google Workspace LDAP (stunnel)
 */
function _try_google_provider() {
    global $ldap_domain_info, $ldap_domain_controllers, $multiple_domains_controllers;

    if (!isset($multiple_domains_controllers) || empty($multiple_domains_controllers)) {
        foreach ($ldap_domain_info as $suffix => $base_dn) {
            if (($result = create_google_ldap($suffix, $base_dn, $ldap_domain_controllers)) !== false) {
                return $result;
            }
        }
    } else {
        foreach ($multiple_domains_controllers as $domain => $data) {
            foreach ($data['domain_info'] as $suffix => $base_dn) {
                if (!KAYAKO_LDAP_STRIP_EMAIL && ($pos = strpos($_POST['username'], '@')) !== false) {
                    if (strcasecmp($suffix, substr($_POST['username'], $pos)) !== 0) {
                        continue;
                    }
                }
                if (($result = create_google_ldap($suffix, $base_dn, $data['domain_controllers'])) !== false) {
                    return $result;
                }
            }
        }
    }
    return false;
}

/**
 * Try to authenticate via Active Directory LDAP
 */
function _try_ad_provider() {
    global $ad_domain_info, $ad_domain_controllers, $ad_multiple_domains_controllers;

    if (!isset($ad_multiple_domains_controllers) || empty($ad_multiple_domains_controllers)) {
        foreach ($ad_domain_info as $suffix => $base_dn) {
            if (($result = create_ad_ldap($suffix, $base_dn, $ad_domain_controllers)) !== false) {
                return $result;
            }
        }
    } else {
        foreach ($ad_multiple_domains_controllers as $domain => $data) {
            foreach ($data['domain_info'] as $suffix => $base_dn) {
                if (!KAYAKO_LDAP_STRIP_EMAIL && ($pos = strpos($_POST['username'], '@')) !== false) {
                    if (strcasecmp($suffix, substr($_POST['username'], $pos)) !== 0) {
                        continue;
                    }
                }
                if (($result = create_ad_ldap($suffix, $base_dn, $data['domain_controllers'])) !== false) {
                    return $result;
                }
            }
        }
    }
    return false;
}
