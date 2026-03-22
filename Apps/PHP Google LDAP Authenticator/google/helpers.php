<?php
/**
 * Helper functions for Google Workspace LDAP Authenticator for Kayako LoginShare v4
 *
 * @version 1.0.0
 * @author kraloveckey
 */


/**
 * Tries to create the Google_LDAP class and authenticate to it
 *
 * @param string $ldap_account_suffix
 * @param string $ldap_base_dn
 * @param array $ldap_domain_controllers
 * @return bool
 */
function create_google_ldap($ldap_account_suffix, $ldap_base_dn, $ldap_domain_controllers) {
    global $googleLdap, $use_adldap_options, $adldap_options;

    try {
        $options = array(
            'account_suffix'     => $ldap_account_suffix,
            'base_dn'            => $ldap_base_dn,
            'domain_controllers' => $ldap_domain_controllers,
        );

        if ($use_adldap_options) {
            if ($adldap_options['admin_user_name'] === '') {
                $adldap_options['admin_user_name'] = NULL;
                $adldap_options['admin_password'] = NULL;
            }
            $options = array_merge($options, $adldap_options);
        }

        // Try to setup a new instance of our adapted class
        $googleLdap = new Kayako_Google_LDAP($options);

        // Logging for troubleshooting
        $googleLdap->log('Google LDAP Initialization:');
        $googleLdap->log('Suffix: ' . var_export($ldap_account_suffix, true));
        $googleLdap->log('Base DN: ' . var_export($ldap_base_dn, true));
        $googleLdap->log('Controllers: ' . var_export($ldap_domain_controllers, true));
        $googleLdap->log('Username: ' . $googleLdap->getUsername());

        // Commented out for security
        // $googleLdap->log('Username: ' . $googleLdap->getUsername() . ' - Password: ' . $googleLdap->getPassword());

        // Authenticate against Google LDAP (via stunnel)
        $authUser = $googleLdap->authenticate($googleLdap->getUsername(), $googleLdap->getPassword());

        if ($authUser) {
            $googleLdap->log('Authenticated successfully: ' . $googleLdap->getUsername());
            return true;
        } else {
            $googleLdap->log('Failed authorization for: ' . $googleLdap->getUsername());
            throw new Exception('Invalid credentials');
        }

    } catch (Exception $e) {
        if (is_object($googleLdap)) {
            $googleLdap->log('Error: ' . $e->getMessage() . ' -- ' . $googleLdap->getLastError());
        } else {
            // Fallback if class failed to even instantiate
            header('content-type: text/xml; charset=utf-8');
            echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<loginshare>\n  <result>0</result>\n";
            echo "  <message>Critical: Could not create Kayako_Google_LDAP class</message>\n</loginshare>\n";
            @ob_flush();
            die();
        }
        return false;
    }
}

/**
 * Manages any errors with ldap
 */
function ldap_error_handler($errno, $errstr, $errfile, $errline) {
    global $googleLdap;

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

    if (is_object($googleLdap)) {
        $googleLdap->log($out);
    } else if ($die) {
        header('content-type: text/xml; charset=utf-8');
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<loginshare>\n  <result>0</result>\n";
        echo "  <message>$out</message>\n</loginshare>\n";
        @ob_flush();
        die();
    }

    return true;
}