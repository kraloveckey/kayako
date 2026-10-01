<?php
/**
 * Helper functions for Dual-Provider LDAP Authenticator for Kayako LoginShare v4
 *
 * @version 2.0.1
 * @author kraloveckey
 */

/**
 * Try to instantiate Kayako_Google_LDAP and authenticate the user against Google Workspace.
 *
 * @param string $ldap_account_suffix  e.g. '@google-domain.com'
 * @param string $ldap_base_dn         e.g. 'dc=google-domain,dc=com'
 * @param array  $ldap_domain_controllers
 * @return bool
 */
function create_google_ldap($ldap_account_suffix, $ldap_base_dn, $ldap_domain_controllers) {
    global $googleLdap, $google_adldap_options, $google_nested_group_files;

    try {
        $options = array(
            'account_suffix'     => $ldap_account_suffix,
            'base_dn'            => $ldap_base_dn,
            'domain_controllers' => $ldap_domain_controllers,
            'ad_port'            => $google_adldap_options['ad_port'],
            // BUG FIX (Google): pass service account credentials so googleLDAP
            // can rebind after user authentication and run group searches
            // with read rights (users typically have no LDAP search permissions).
            'admin_user_name'    => $google_adldap_options['admin_user_name'],
            'admin_password'     => $google_adldap_options['admin_password'],
            'use_ssl'            => $google_adldap_options['use_ssl'],
            'use_tls'            => $google_adldap_options['use_tls'],
            // Pass the nested-group file map so googleLDAP::inGroup() checks a
            // pre-built membership file (instead of LDAP) for these groups.
            'nested_group_files' => isset($google_nested_group_files)
                                        ? $google_nested_group_files
                                        : array(),
        );

        $googleLdap = new Kayako_Google_LDAP($options);

        $googleLdap->log('=== Google LDAP attempt ===');
        $googleLdap->log('Suffix: '      . var_export($ldap_account_suffix, true));
        $googleLdap->log('Base DN: '     . var_export($ldap_base_dn, true));
        $googleLdap->log('Controllers: ' . var_export($ldap_domain_controllers, true));
        $googleLdap->log('Username: '    . $googleLdap->getUsername());
        // Uncomment to debug credentials:
        // $googleLdap->log('Password: ' . $googleLdap->getPassword());

        $authUser = $googleLdap->authenticate($googleLdap->getUsername(), $googleLdap->getPassword());

        if ($authUser) {
            $googleLdap->log('Google LDAP: authenticated successfully.');
            return true;
        } else {
            $googleLdap->log('Google LDAP: authentication failed.');
            throw new Exception('Invalid credentials');
        }

    } catch (Exception $e) {
        if (is_object($googleLdap)) {
            $googleLdap->log('Google LDAP error: ' . $e->getMessage() . ' -- ' . $googleLdap->getLastError());
        } else {
            _fatal_xml_error('Critical: Could not create Kayako_Google_LDAP class');
        }
        return false;
    }
}

/**
 * Try to instantiate Kayako_AD_LDAP and authenticate the user against Active Directory.
 *
 * When the username has no '@' (i.e. it's a sAMAccountName), adLDAP appends the
 * account suffix automatically during bind.
 *
 * BUG FIX (AD): The options key for the service account MUST be 'admin_username'
 * (no underscore between "user" and "name") — that is what adLDAP::__construct()
 * reads at line 565:
 *     if (array_key_exists("admin_username", $options)) { ... }
 * The old key 'admin_user_name' was silently ignored, so $this->adminUsername
 * stayed NULL and adLDAP never re-bound to the service account after checking
 * the user's password.  Group searches therefore ran under the user's credentials,
 * which typically have no LDAP read rights → group check always failed.
 *
 * @param string $ldap_account_suffix  e.g. '@ad-domain.local'
 * @param string $ldap_base_dn         e.g. 'DC=ad-domain,DC=local'
 * @param array  $ldap_domain_controllers
 * @return bool
 */
function create_ad_ldap($ldap_account_suffix, $ldap_base_dn, $ldap_domain_controllers) {
    global $adLdap, $ad_adldap_options;

    try {
        $options = array(
            'account_suffix'     => $ldap_account_suffix,
            'base_dn'            => $ldap_base_dn,
            'domain_controllers' => $ldap_domain_controllers,
            'ad_port'            => $ad_adldap_options['ad_port'],
            // FIXED: was 'admin_user_name' (wrong — adLDAP ignores it).
            // Must be 'admin_username' so adLDAP stores it in $this->adminUsername
            // and re-binds as the service account after user authentication.
            'admin_username'     => $ad_adldap_options['admin_user_name'],
            'admin_password'     => $ad_adldap_options['admin_password'],
            'use_ssl'            => $ad_adldap_options['use_ssl'],
            'use_tls'            => $ad_adldap_options['use_tls'],
        );

        $adLdap = new Kayako_AD_LDAP($options);

        $adLdap->log('=== AD LDAP attempt ===');
        $adLdap->log('Suffix: '      . var_export($ldap_account_suffix, true));
        $adLdap->log('Base DN: '     . var_export($ldap_base_dn, true));
        $adLdap->log('Controllers: ' . var_export($ldap_domain_controllers, true));
        $adLdap->log('Username: '    . $adLdap->getUsername());
        // Uncomment to debug credentials:
        // $adLdap->log('Password: ' . $adLdap->getPassword());

        $authUser = $adLdap->authenticate($adLdap->getUsername(), $adLdap->getPassword());

        if ($authUser) {
            $adLdap->log('AD LDAP: authenticated successfully.');
            return true;
        } else {
            $adLdap->log('AD LDAP: authentication failed.');
            throw new Exception('Invalid credentials');
        }

    } catch (Exception $e) {
        if (is_object($adLdap)) {
            $adLdap->log('AD LDAP error: ' . $e->getMessage() . ' -- ' . $adLdap->getLastError());
        } else {
            _fatal_xml_error('Critical: Could not create Kayako_AD_LDAP class');
        }
        return false;
    }
}

/**
 * Output a fatal XML error and die.
 * Used when no handler object is available yet.
 */
function _fatal_xml_error($message) {
    header('content-type: text/xml; charset=utf-8');
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<loginshare>\n  <result>0</result>\n";
    echo "  <message>" . htmlspecialchars($message, ENT_XML1, 'UTF-8') . "</message>\n</loginshare>\n";
    @ob_flush();
    die();
}

/**
 * Shared PHP error handler — routes to whichever handler object is alive.
 */
function ldap_error_handler($errno, $errstr, $errfile, $errline) {
    global $googleLdap, $adLdap;

    // Suppress ldap_close/ldap_unbind warnings — these occur when stunnel
    // drops the TCP connection after a failed bind (e.g. wrong username format),
    // leaving PHP holding a resource handle that is already dead at TCP level.
    // is_resource() still returns true in PHP 7.x in this state, so the check
    // in close() cannot prevent the warning — suppress it here instead.
    if (strpos($errstr, 'ldap_close') !== false || strpos($errstr, 'ldap_unbind') !== false) {
        return true;
    }

    $die = false;
    switch ($errno) {
        case E_USER_ERROR:
            $out = "ERROR: [$errno] $errstr -- line $errline in file $errfile";
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

    $handler = is_object($googleLdap) ? $googleLdap : (is_object($adLdap) ? $adLdap : null);

    if ($handler) {
        $handler->log($out);
    } else if ($die) {
        _fatal_xml_error($out);
    }

    return true;
}
