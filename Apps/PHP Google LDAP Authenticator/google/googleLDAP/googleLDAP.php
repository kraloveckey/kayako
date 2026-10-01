<?php
/**
 * Core Google LDAP Library for Kayako
 * Simple replacement for adLDAP focused on Google Secure LDAP via stunnel
 *
 * Backported from PHP Google AD Authenticator (googleLDAP 2.3.2):
 *   - service account rebind after the user bind
 *   - one level of nested groups + optional pre-built membership files
 *   - user filter values are escaped with ldap_escape()
 *
 * @version 1.1.0
 * @author kraloveckey
 */

class googleLDAP {
    protected $_conn    = null;
    protected $_options = array();
    protected $_error   = null;
    protected $_bound   = false;

    // Reused across multiple inGroup() calls in the same request
    private $_userDnCache       = null;
    private $_userMemberOfCache = null;
    private $_userCacheKey      = null;

    public function __construct($options) {
        $this->_options = $options;
        $this->connect();
    }

    public function connect() {
        $dc   = $this->_options['domain_controllers'][0];
        $port = $this->_options['ad_port'];

        $this->_conn = ldap_connect($dc, $port);

        if (!$this->_conn) {
            $this->_error = "Could not connect to LDAP server at $dc:$port";
            return false;
        }

        ldap_set_option($this->_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->_conn, LDAP_OPT_REFERRALS, 0);
        return true;
    }

    /**
     * Authenticate a user by binding with their credentials.
     *
     * BUG FIX: After verifying the user's password the connection is immediately
     * re-bound under the service account (admin_user_name / admin_password from
     * $options).  Without this rebind all subsequent ldap_search() calls in
     * inGroup() and info() run under the user's credentials, which typically
     * have no directory-read rights, so group checks always fail.
     *
     * @param string $username  Full Google email used as the bind DN
     * @param string $password  User's password
     * @return bool
     */
    public function authenticate($username, $password) {
        if (empty($username) || empty($password)) { return false; }

        // Bind as the user to verify the password
        if (!@ldap_bind($this->_conn, $username, $password)) {
            $this->_error = ldap_error($this->_conn);
            return false;
        }

        $this->_bound = true;

        // Re-bind as the service account so that subsequent LDAP searches
        // (group membership checks, user info lookups) have the necessary
        // read permissions.
        $svcUser = isset($this->_options['admin_user_name']) ? $this->_options['admin_user_name'] : '';
        $svcPass = isset($this->_options['admin_password'])  ? $this->_options['admin_password']  : '';

        if (!empty($svcUser) && !empty($svcPass)) {
            if (!@ldap_bind($this->_conn, $svcUser, $svcPass)) {
                $this->_error = ldap_error($this->_conn);
                // Service account rebind failed — authentication cannot continue
                // safely because group checks would run without proper rights.
                return false;
            }
        }

        return true;
    }

    public function user() { return $this; }

    public function info($username, $attributes) {
        $filter = '(mail=' . $this->_escapeFilter($username) . ')';
        $search = ldap_search($this->_conn, $this->_options['base_dn'], $filter, $attributes);
        if ($search) {
            return ldap_get_entries($this->_conn, $search);
        }
        return false;
    }

    /**
     * Check group membership — file-first for nested groups, then LDAP.
     *
     * Check order:
     *   1. If the group DN is listed in $options['nested_group_files'], read
     *      the pre-built flat file immediately and return — no LDAP queries
     *      are made for that group.  This saves ~3s per nested group check.
     *   2. For all other groups: two LDAP queries (Query 1 result is cached).
     *
     * Google Secure LDAP facts:
     *   - Users have 'memberOf' (direct groups only, no transitive expansion)
     *   - Groups do NOT have 'memberOf'
     *   - Groups DO have 'member' (direct members: users and nested groups)
     *
     * LDAP match logic (step 2 only):
     *   Query 1: get user DN + direct memberOf list  [cached after first call]
     *   Query 2: get member list of targetGroupDn
     *   Match if: userDN is directly in group.member            → true
     *             any of user's direct groups is in group.member → true
     *             (one level of nesting: user → groupA → targetGroup)
     *
     * Flat file format: one email address per line (UTF-8, maintained by cron).
     *
     * @param string $username      User's Google email address
     * @param string $targetGroupDn Full DN of the group to check
     * @return bool
     */
    public function inGroup($username, $targetGroupDn) {

        // ── Step 1: file-first check for designated nested groups ──────────
        // Groups listed in nested_group_files are checked via a pre-built flat
        // file only — LDAP queries are skipped entirely for these groups.
        // To mark a group as nested, add it to 'nested_group_files' in
        // $adldap_options (config.php) and keep the file up to date by cron.
        $nestedFiles = isset($this->_options['nested_group_files'])
            ? $this->_options['nested_group_files']
            : array();

        foreach ($nestedFiles as $groupDn => $filePath) {
            if (strcasecmp(trim($groupDn), trim($targetGroupDn)) !== 0) { continue; }

            // This group is flagged as nested — file is authoritative, no LDAP
            if (!is_readable($filePath)) { return false; }
            $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) { return false; }
            foreach ($lines as $line) {
                if (strcasecmp(trim($line), $username) === 0) { return true; }
            }
            return false; // file checked, user not found — do not fall through to LDAP
        }

        // ── Step 2: standard LDAP check (non-nested groups only) ──────────

        // Query 1: get user DN + direct memberOf (cached across inGroup calls)
        if ($this->_userCacheKey !== $username) {
            $search = ldap_search(
                $this->_conn,
                $this->_options['base_dn'],
                '(mail=' . $this->_escapeFilter($username) . ')',
                array('dn', 'memberof')
            );
            if (!$search) { return false; }

            $entries = ldap_get_entries($this->_conn, $search);
            if (!isset($entries[0])) { return false; }

            $this->_userDnCache  = $entries[0]['dn'];
            $this->_userCacheKey = $username;

            $memberOf = isset($entries[0]['memberof']) ? $entries[0]['memberof'] : array();
            $count    = isset($memberOf['count']) ? (int)$memberOf['count'] : 0;
            $this->_userMemberOfCache = array();
            for ($i = 0; $i < $count; $i++) {
                $this->_userMemberOfCache[] = trim($memberOf[$i]);
            }
        }

        $userDn    = $this->_userDnCache;
        $directDns = $this->_userMemberOfCache;

        // Direct membership check via memberOf attribute
        foreach ($directDns as $dn) {
            if (strcasecmp($dn, trim($targetGroupDn)) === 0) {
                return true;
            }
        }

        // Query 2: read member list of targetGroupDn.
        // The group is searched by its cn= value; the entry with the exact
        // targetGroupDn is picked below (cn may not be unique).
        $cnValue = '';
        if (preg_match('/^cn=([^,]+)/i', trim($targetGroupDn), $m)) {
            $cnValue = $m[1];
        }

        if ($cnValue !== '') {
            $search = ldap_search(
                $this->_conn,
                $this->_options['base_dn'],
                '(cn=' . $this->_escapeFilter($cnValue) . ')',
                array('dn', 'member')
            );

            if ($search) {
                $entries = ldap_get_entries($this->_conn, $search);

                // Find the entry whose DN matches targetGroupDn (cn may not be unique)
                $groupEntry = null;
                $eCount = isset($entries['count']) ? (int)$entries['count'] : 0;
                for ($i = 0; $i < $eCount; $i++) {
                    if (isset($entries[$i]['dn']) &&
                        strcasecmp(trim($entries[$i]['dn']), trim($targetGroupDn)) === 0) {
                        $groupEntry = $entries[$i];
                        break;
                    }
                }
                if ($groupEntry === null && $eCount > 0) { $groupEntry = $entries[0]; }

                if ($groupEntry !== null && isset($groupEntry['member'])) {
                    $members = $groupEntry['member'];
                    $count   = isset($members['count']) ? (int)$members['count'] : 0;

                    for ($i = 0; $i < $count; $i++) {
                        $memberDn = trim($members[$i]);

                        // User is directly in this group
                        if (strcasecmp($memberDn, $userDn) === 0) {
                            return true;
                        }

                        // One of user's direct groups is in this group (one level nesting)
                        foreach ($directDns as $userGroupDn) {
                            if (strcasecmp($memberDn, $userGroupDn) === 0) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    public function getLastError() { return $this->_error; }

    /**
     * Escape a value for use inside an LDAP search filter (RFC 4515).
     * The username comes straight from $_POST, so it must not be able to
     * change the filter structure (e.g. "*" or ")(uid=*").
     */
    protected function _escapeFilter($value) {
        if (function_exists('ldap_escape')) {
            return ldap_escape($value, '', LDAP_ESCAPE_FILTER);
        }
        return str_replace(
            array('\\', '*', '(', ')', "\0"),
            array('\\5c', '\\2a', '\\28', '\\29', '\\00'),
            $value
        );
    }

    public function close() {
        if ($this->_conn && is_resource($this->_conn)) {
            @ldap_unbind($this->_conn);
            $this->_conn = null;
        }
    }

    public function __destruct() { $this->close(); }
}
