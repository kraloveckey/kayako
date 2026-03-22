<?php
/**
 * Core functionality for Google Workspace LDAP Authenticator for Kayako LoginShare v4
 *
 * @version 1.0.0
 * @author kraloveckey
 *
 * This is a LoginShare Google Workspace LDAP authenticator.
 * Optimized for use with stunnel4 as a secure gateway to Google LDAP.
 */

// Start capturing data to the screen just in case
@ob_start();

// This is set here in case something goes wrong before we want XML
header('content-type: text/html; charset=utf-8');

// Get bootstrap - Pointing to the new 'google' directory
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'google' . DIRECTORY_SEPARATOR . 'bootstrap.php';

global $googleLdap;

$authUser = false;

// Google Workspace usually has a flat structure or specific DNs,
// but we keep the loop logic for compatibility with multiple domain configs.
if (!isset($multiple_domains_controllers) || empty($multiple_domains_controllers)) {
    /**
     * Loop through the domain info trying to start LDAP
     */
    foreach ($ldap_domain_info as $ldap_account_suffix => $ldap_base_dn) {
        if (($authUser = create_google_ldap($ldap_account_suffix, $ldap_base_dn, $ldap_domain_controllers)) !== false) {
            break;
        }
    }
} else {
    foreach ($multiple_domains_controllers as $domain => $data) {
        foreach ($data['domain_info'] as $ldap_account_suffix => $ldap_base_dn) {
            /**
             * For Google Workspace, email is often the primary identifier.
             */
            if (!KAYAKO_LDAP_STRIP_EMAIL && ($pos = strpos($_POST['username'], '@')) !== false) {
                $email_domain = substr($_POST['username'], $pos);

                if (strcasecmp($ldap_account_suffix, $email_domain) !== 0) {
                    continue;
                }
            }

            if (($authUser = create_google_ldap($ldap_account_suffix, $ldap_base_dn, $data['domain_controllers'])) !== false) {
                break;
            }
        }
        if ($authUser) {
            break;
        }
    }
}

// Log if using a HTML test file
if (isset($_GET['test']) && !empty($_GET['test'])) {
    $googleLdap->log('HTML Test: ' . $_GET['test']);
}

if ($authUser) {
    // Log the type
    if (isset($_GET['type'])) {
        $googleLdap->log('Type: '. var_export($_GET['type'], true));
    } else {
        $googleLdap->log('Type: Empty (Default to user)');
    }

    // User authentication
    if (!isset($_GET['type']) || $_GET['type'] == 'user') {
        $googleLdap->getUser();

        // Check for user bypass (e.g. local admins)
        if (isset($user_group_bypass) && !empty($user_group_bypass) &&
            isset($user_group_bypass[strtolower($googleLdap->getUsername())])) {
                $googleLdap->log('User found in bypass list. Access granted.');
                $googleLdap->displayUserXML($user_group_bypass[strtolower($googleLdap->getUsername())]);
                die();
        }

        if (!empty($user_groups) && is_array($user_groups)) {
            $googleLdap->log('Group restrictions enabled');
            foreach ($user_groups as $group => $user_group) {
                // Google Groups are checked via memberOf or custom logic in the class
                if ($googleLdap->user()->inGroup($googleLdap->getUsername(), $group)) {
                    $googleLdap->log('User belongs to allowed group. Access granted.');
                    $googleLdap->displayUserXML($user_group);
                    die();
                }
            }

            if (KAYAKO_LDAP_ERROR_USERGROUP) {
                $googleLdap->log('Group check failed. Access denied.');
                $googleLdap->displayErrorXML('Access denied. You do not have the required Google Group permissions.');
            } else {
                $googleLdap->log('Group not found, but login allowed by config.');
                $googleLdap->displayUserXML();
            }
            die();
        } else {
            $googleLdap->log('No group restrictions. Access granted.');
            $googleLdap->displayUserXML();
            die();
        }

    // Staff authentication
    } else if ($_GET['type'] == 'staff') {
        $googleLdap->getStaff();

        if (!empty($staff_groups) && is_array($staff_groups)) {
            $googleLdap->log('Staff groups validation enabled');
            foreach ($staff_groups as $group => $team) {
                if ($googleLdap->user()->inGroup($googleLdap->getUsername(), $group)) {
                    $googleLdap->log('Staff member verified in group.');
                    $googleLdap->displayStaffXML($team);
                    die();
                }
            }
            $googleLdap->log('Staff group not found.');
            $googleLdap->displayErrorXML('Access denied. Staff member not in an authorized Google Group.');
            die();
        } else {
            $googleLdap->log('Critical: Staff groups not configured in config.php');
            $googleLdap->displayErrorXML('Staff authentication is not properly configured.');
            die();
        }
    } else {
        $googleLdap->log('Invalid request type.');
        $googleLdap->displayErrorXML('Unknown authentication method.');
        die();
    }
} else {
    $googleLdap->log('Authentication failed for user: ' . $_POST['username']);
    $googleLdap->displayErrorXML('Invalid credentials or account issue.');
    die();
}