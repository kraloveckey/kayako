<?php
/**
 * Core Google LDAP Library for Kayako
 * Simple replacement for adLDAP focused on Google Secure LDAP via stunnel
 * * @version 1.0.1
 * @author kraloveckey
 */

class googleLDAP {
    protected $_conn = null;
    protected $_options = array();
    protected $_error = null;
    protected $_bound = false;

    public function __construct($options) {
        $this->_options = $options;
        $this->connect();
    }

    /**
     * Connect to stunnel (localhost:389)
     */
    public function connect() {
        $dc = $this->_options['domain_controllers'][0];
        $port = $this->_options['ad_port'];

        $this->_conn = ldap_connect($dc, $port);
        ldap_set_option($this->_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->_conn, LDAP_OPT_REFERRALS, 0);

        if (!$this->_conn) {
            $this->_error = "Could not connect to LDAP server at $dc:$port";
            return false;
        }
        return true;
    }

    /**
     * Authenticate (Bind) as the service account or the user
     */
    public function authenticate($username, $password) {
        if (empty($username) || empty($password)) {
            return false;
        }

        if (@ldap_bind($this->_conn, $username, $password)) {
            $this->_bound = true;
            return true;
        }

        $this->_error = ldap_error($this->_conn);
        return false;
    }

    /**
     * Search user info (replacement for $adldap->user()->info())
     */
    public function user() {
        return $this;
    }

    public function info($username, $attributes) {
        $filter = "(mail=" . $username . ")";
        $search = ldap_search($this->_conn, $this->_options['base_dn'], $filter, $attributes);

        if ($search) {
            $entries = ldap_get_entries($this->_conn, $search);
            return $entries;
        }
        return false;
    }

    /**
     * Check group membership in Google LDAP
     * Strict full DN comparison
     */
    public function inGroup($username, $targetGroupDn) {
        $filter = "(mail=$username)";
        // Querying only the memberOf attribute
        $search = ldap_search($this->_conn, $this->_options['base_dn'], $filter, array('memberof'));

        if ($search) {
            $entries = ldap_get_entries($this->_conn, $search);

            if (isset($entries[0]['memberof']) && is_array($entries[0]['memberof'])) {
                $groups = $entries[0]['memberof'];
                unset($groups['count']); // Removing the PHP service field

                foreach ($groups as $userGroupDn) {
                    if (strcasecmp(trim($userGroupDn), trim($targetGroupDn)) === 0) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function getLastError() {
        return $this->_error;
    }

    public function close() {
        if ($this->_conn) {
            @ldap_unbind($this->_conn);
        }
    }

    public function __destruct() {
        $this->close();
    }
}