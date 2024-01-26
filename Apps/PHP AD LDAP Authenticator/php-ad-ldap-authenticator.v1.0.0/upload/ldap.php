<?php
/**
 * Core functionality for PHP AD LDAP Authenticator for Kayako LoginShare v4
 *
 * @version 1.0.0
 *
 * This is a LoginShare Active Directory authenticator
 *
 */

//Start capturing data to the screen just in case
@ob_start();

//This is set here in case something goes wrong before we want XML
header('content-type: text/html; charset=utf-8');

//Get bootstrap
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ldap' . DIRECTORY_SEPARATOR . 'bootstrap.php';

global $adldap;

$authUser = false;

if (!isset($multiple_domains_controllers) || empty($multiple_domains_controllers)) {
        /**
         * This will loop through the multiple domains trying to start LDAP
         * using the credentials provided
         */
        foreach ($ldap_domain_info as $ldap_account_suffix => $ldap_base_dn) {
                if (($authUser = create_adldap($ldap_account_suffix, $ldap_base_dn, $ldap_domain_controllers)) !== false) {
                        break;
                }
        }
} else {
        foreach ($multiple_domains_controllers as $domain => $data) {
                foreach ($data['domain_info'] as $ldap_account_suffix => $ldap_base_dn) {
                        /**
                         * When the username contains an email address only try
                         * domains that match the LDAP account prefix
                         */
                        if (!KAYAKO_LDAP_STRIP_EMAIL && ($pos = strpos($_POST['username'], '@')) !== false) {
                                $email_domain = substr($_POST['username'], $pos);

                                if (strcasecmp($ldap_account_suffix, $email_domain) !== 0) {
                                        continue;
                                }
                        }

                        /**
                         * This will loop through the multiple domains trying to start LDAP
                         * using the credentials provided
                         */
                        if (($authUser = create_adldap($ldap_account_suffix, $ldap_base_dn, $data['domain_controllers'])) !== false) {
                                break;
                        }
                }
                if ($authUser) {
                        break;
                }
        }
}

global $adldap;

//Log if using a HTML test file
if (isset($_GET['test']) && !empty($_GET['test'])) {
        $adldap->log('HTML Test: ' . $_GET['test']);
}

if ($authUser) {
        //Log the type
        if (isset($_GET['type'])) {
                $adldap->log('Type: '. var_export($_GET['type'], true));
        } else {
                $adldap->log('Type: Empty (Default to user)');
        }

        //User
        if (!isset($_GET['type']) || $_GET['type'] == 'user') {
                //Setup the user info to be used below
                $adldap->getUser();

                //Check for user group bypass
                if (isset($user_group_bypass) && !empty($user_group_bypass) &&
                        isset($user_group_bypass[strtolower($adldap->getUsername())])) {
                                $adldap->log('User found in user_group bypass. User logged in');
                                $adldap->displayUserXML($user_group_bypass[strtolower($adldap->getUsername())]);
                                die();
                }

                if (!empty($user_groups) && is_array($user_groups)) {
                        $adldap->log('Usergroups are enabled');
                        foreach ($user_groups as $group => $user_group) {
                                if ($adldap->user()->inGroup($adldap->getUsername(), $group)) {
                                        $adldap->log('Usergroup found. User logged in');
                                        $adldap->displayUserXML($user_group);
                                        die();
                                }
                        }
                        if (KAYAKO_LDAP_ERROR_USERGROUP) {
                                $adldap->log('Usergroup was not found.  User error sent!');
                                $adldap->log('Usergroups user is in: '.var_export($adldap->user()->groups($adldap->getUsername()), true));
                                $adldap->displayErrorXML('Oops... Access denied. You do not have the right to access this site. Please, contact your grouplead or teamlead to request access or submit a task.');
                        } else {
                                $adldap->log('Usergroup was not found. User did logged in because of KAYAKO_LDAP_ERROR_USERGROUP');
                                $adldap->displayUserXML();
                        }
                        die();
                } else if (!empty($valid_user_groups) && is_array($valid_user_groups)) {
                        $adldap->log('Valid usergroups are enabled');
                        foreach ($valid_user_groups as $group) {
                                if ($adldap->user()->inGroup($adldap->getUsername(), $group)) {
                                        $adldap->log('User is in valid group.  User logged in');
                                        $adldap->displayUserXML();
                                        die();
                                }
                        }
                        $adldap->log('User is not in valid user group. User error sent');
                        $adldap->displayErrorXML('User is in a invalid AD usergroup');
                        die();
                } else {
                        $adldap->log('No special user restrictions.  User logged in');
                        $adldap->displayUserXML();
                        die();
                }
        //Staff
        } else if ($_GET['type'] == 'staff') {
                //Setup the staff info to be used below
                $adldap->getStaff();

                if (!empty($staff_groups) && is_array($staff_groups)) {
                        $adldap->log('Staff groups are enabled');
                        foreach ($staff_groups as $group => $team) {
                                if ($adldap->user()->inGroup($adldap->getUsername(), $group)) {
                                        $adldap->log('Staff group found.  User logged in');
                                        $adldap->displayStaffXML($team);
                                        die();
                                }
                        }
                        $adldap->log('Staff group not found.  Error message sent');
                        $adldap->displayErrorXML('Oops... Access denied. You do not have the right to access this site. Please, contact your grouplead or teamlead to request access.');
                        die();
                } else {
                        $adldap->log('Staff groups are not setup correctly.  Please double check config.php.  Error message sent');
                        $adldap->displayErrorXML('Staff groups have not been setup correctly');
                        die();
                }
        } else {
                $adldap->log('Bad GET type sent. Error message sent');
                $adldap->displayErrorXML('Unknown type method');
                die();
        }
} else {
        $adldap->log('Bad login. Error message sent');
        $adldap->displayErrorXML();
        die();
}