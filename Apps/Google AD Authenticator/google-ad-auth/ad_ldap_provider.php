<?php
/**
 * Kayako_AD_LDAP — Active Directory LDAP handler for Kayako LoginShare v4
 * Extends the adLDAP library.
 *
 * Key behaviour:
 *   - Username can be a sAMAccountName (no @) or a full email
 *   - The Kayako email is always extracted from AD proxyAddresses (primary SMTP:)
 *     or from the mail attribute, depending on KAYAKO_AD_EMAIL_SOURCE
 *   - This ensures one Kayako account per person regardless of login method
 *
 * @version 2.0.0
 * @author kraloveckey
 */

include KAYAKO_ADLDAP_PATH . 'adLDAP.php';

class Kayako_AD_LDAP extends adLDAP {

    public $password       = '';
    public $userinfo       = array();
    public $username       = '';
    private $_samAccountName = ''; // original sAMAccountName, preserved for AD group lookups

    private $_attribute  = false;
    private $_attributes = array();
    private $_log        = null;

    // ── Magic attribute accessor ───────────────────────────────────────────

    function __get($name) {
        if ($name === 'attribute') {
            $this->_attribute = true;
            return $this;
        }

        if ($this->_attribute) {
            $name = strtolower($name);

            if ($name === 'telephone') { $name = 'telephonenumber'; }

            // Phone fallback chain
            if ($name === 'telephonenumber') {
                if (!empty($this->userinfo['telephonenumber'])) {
                    return $this->_xmlEncode($this->userinfo['telephonenumber']);
                }
                if (KAYAKO_LDAP_PHONE_NUMBER) {
                    if (!empty($this->userinfo['mobile'])) {
                        return $this->_xmlEncode($this->userinfo['mobile']);
                    }
                    if (!empty($this->userinfo['homephone'])) {
                        return $this->_xmlEncode($this->userinfo['homephone']);
                    }
                }
                return '';
            }

            // mail → always use the resolved Google email (set by _setUserinfo)
            if ($name === 'mail') {
                return isset($this->userinfo['_resolved_email'])
                    ? $this->_xmlEncode($this->userinfo['_resolved_email'])
                    : '';
            }

            return (isset($this->userinfo[$name]) && $this->userinfo[$name] !== '')
                ? $this->_xmlEncode($this->userinfo[$name])
                : '';
        }
    }

    function __destruct() {
        parent::__destruct();
        if (!empty($this->_log)) {
            $this->log("----------[ Session End ]----------\n");
            fclose($this->_log);
        }
        $this->close();
    }

    // ── XML output ────────────────────────────────────────────────────────

    public function displayErrorXML($message = 'Invalid Username or Password') {
        $this->_displayXMLHeader();
        $message = $this->_xmlEncode($message, true);
        $out = "<loginshare>\n  <result>0</result>\n  <message>$message</message>\n</loginshare>\n";
        if (KAYAKO_LDAP_LOG_XML) { $this->log($out); }
        echo $out;
        $this->_logOutput();
    }

    public function displayUserXML($user_group = 'Registered') {
        $this->_displayXMLHeader();
        $out  = "<loginshare>\n  <result>1</result>\n  <user>\n";
        $out .= "    <usergroup>$user_group</usergroup>\n";
        $out .= "    <fullname>"  . $this->attribute->displayname    . "</fullname>\n";
        $out .= (KAYAKO_LDAP_IMPORT_TITLE)
            ? "    <designation>" . $this->attribute->title . "</designation>\n"
            : "    <designation/>\n";
        if (KAYAKO_LDAP_IMPORT_DEPARTMENT && !empty($this->attribute->department)) {
            $out .= "    <organization>" . $this->attribute->department . "</organization>\n";
        }
        $out .= "    <emails>\n      <email>" . $this->attribute->mail . "</email>\n    </emails>\n";
        $out .= "    <phone>" . $this->attribute->telephonenumber . "</phone>\n";
        $out .= "  </user>\n</loginshare>\n";
        if (KAYAKO_LDAP_LOG_XML) { $this->log($out); }
        echo $out;
        $this->_logOutput();
    }

    public function displayStaffXML($team) {
        $this->_displayXMLHeader();
        $out  = "<loginshare>\n  <result>1</result>\n  <staff>\n";
        $out .= "    <team>$team</team>\n";
        $out .= "    <firstname>"  . $this->attribute->givenname . "</firstname>\n";
        $out .= "    <lastname>"   . $this->attribute->sn        . "</lastname>\n";
        $out .= (KAYAKO_LDAP_IMPORT_TITLE)
            ? "    <designation>" . $this->attribute->title . "</designation>\n"
            : "    <designation/>\n";
        $out .= "    <email>"        . $this->attribute->mail   . "</email>\n";
        $out .= "    <mobilenumber>" . $this->attribute->mobile . "</mobilenumber>\n";
        $out .= "    <signature></signature>\n  </staff>\n</loginshare>\n";
        if (KAYAKO_LDAP_LOG_XML) { $this->log($out); }
        echo $out;
        $this->_logOutput();
    }

    // ── Credential accessors ──────────────────────────────────────────────

    public function getPassword() {
        if (!empty($this->password)) { return $this->password; }
        $this->password = KAYAKO_LDAP_TEST
            ? KAYAKO_AD_LDAP_PASSWORD
            : (isset($_POST['password']) ? $_POST['password'] : '');
        return $this->password;
    }

    public function getUsername() {
        if (!empty($this->username)) { return $this->username; }
        if (KAYAKO_LDAP_TEST) {
            $this->username = KAYAKO_AD_LDAP_USERNAME;
        } else {
            $raw = isset($_POST['username']) ? $_POST['username'] : '';
            // Strip @domain if STRIP_EMAIL is on AND it's an email
            if (KAYAKO_LDAP_STRIP_EMAIL && ($pos = strpos($raw, '@')) !== false) {
                $raw = substr($raw, 0, $pos);
            }
            $this->username = $raw;
        }
        // Save original value for AD group lookups (before email override)
        if (empty($this->_samAccountName)) {
            $this->_samAccountName = $this->username;
        }
        return $this->username;
    }

    // ── User / Staff info loaders ─────────────────────────────────────────

    public function getUser($attributes = array()) {
        if (empty($attributes)) {
            $this->_attributes = array(
                'displayname', 'title', 'mail', 'proxyaddresses',
                'telephonenumber', 'givenname', 'sn', 'mobile',
                'homephone', 'department', 'company',
            );
        } else {
            $this->_attributes = array_map('strtolower', $attributes);
        }

        $raw = $this->user()->info($this->getUsername(), $this->_attributes);
        $this->_setUserinfo($raw);

        if (empty($this->userinfo['_resolved_email'])) {
            $this->log('AD: resolved email not found for ' . $this->getUsername());
            $this->displayErrorXML('User does not have a valid email address in AD');
            die();
        }
        // Override username with resolved email so Kayako matches the existing account
        // _samAccountName is preserved for AD group lookups (inGroup uses it)
        $this->log('AD: username overridden from ' . $this->_samAccountName . ' to ' . $this->userinfo['_resolved_email']);
        $this->username = $this->userinfo['_resolved_email'];
        return $this->userinfo;
    }

    public function getStaff($attributes = array()) {
        if (empty($attributes)) {
            $this->_attributes = array(
                'displayname', 'title', 'mail', 'proxyaddresses',
                'telephonenumber', 'givenname', 'sn', 'mobile',
            );
        } else {
            $this->_attributes = array_map('strtolower', $attributes);
        }

        $raw = $this->user()->info($this->getUsername(), $this->_attributes);
        $this->_setUserinfo($raw);

        if (empty($this->userinfo['_resolved_email'])) {
            $this->log('AD: resolved email not found (staff) for ' . $this->getUsername());
            $this->displayErrorXML('Staff member does not have a valid email address in AD');
            die();
        }
        // Override username with resolved email so Kayako matches the existing account
        // _samAccountName is preserved for AD group lookups (inGroup uses it)
        $this->log('AD: username overridden from ' . $this->_samAccountName . ' to ' . $this->userinfo['_resolved_email']);
        $this->username = $this->userinfo['_resolved_email'];
        return $this->userinfo;
    }

    // ── Group check — always use sAMAccountName for AD lookups ──────────
    // After getUser/getStaff, $this->username is overridden to the Google email
    // for Kayako account matching. But AD group search needs the original
    // sAMAccountName. This wrapper restores it for the duration of the check.

    public function inGroup($username, $group, $recursive = NULL, $isGUID = false) {
        $lookupName = !empty($this->_samAccountName) ? $this->_samAccountName : $username;
        $adLDAPUsers = new adLDAPUsers($this);
        return $adLDAPUsers->inGroup($lookupName, $group, $recursive, $isGUID);
    }

    // ── Logging ───────────────────────────────────────────────────────────

    public function log($string) {
        if (!KAYAKO_LDAP_LOG || !$this->_openLog()) { return; }
        @fwrite($this->_log, date('[m-d-y - H:i]') . ' [AD]     ' . $string . "\n");
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function _displayXMLHeader() {
        if (!headers_sent()) {
            header('content-type: text/xml; charset=utf-8');
        }
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    }

    private function _logOutput() {
        if (KAYAKO_LDAP_LOG_OUTPUT && ($output = @ob_get_contents()) !== false) {
            $this->log($output);
            @ob_flush();
        }
    }

    private function _openLog() {
        if (!empty($this->_log)) { return true; }
        $logPath = KAYAKO_GOOGLE_PATH . 'log' . DIRECTORY_SEPARATOR;
        if (!is_dir($logPath)) { @mkdir($logPath, 0777, true); }
        if (!is_writable($logPath)) { return false; }
        $this->_log = fopen($logPath . 'log.txt', 'a');
        return ($this->_log !== false);
    }

    /**
     * Parse raw LDAP entry and resolve the canonical Kayako email.
     *
     * If KAYAKO_AD_EMAIL_SOURCE === 'proxyaddresses':
     *   - Scan the proxyAddresses array for the primary SMTP entry (uppercase "SMTP:")
     *   - Fallback to the first smtp: entry if no uppercase found
     *   - Final fallback: plain mail attribute
     * If KAYAKO_AD_EMAIL_SOURCE === 'mail':
     *   - Use the mail attribute directly
     */
    private function _setUserinfo($userinfo) {
        $this->userinfo = array();
        if (empty($userinfo) || !is_array($userinfo) || !isset($userinfo[0])) { return; }

        // Flatten the raw LDAP entry into our $this->userinfo array
        foreach ($userinfo[0] as $k => $v) {
            if (is_int($k) || $k === 'count') { continue; }
            $key = strtolower($k);
            if (is_array($v)) {
                // Keep raw array for proxyaddresses so we can iterate it
                $this->userinfo[$key] = $v;
            } else {
                $this->userinfo[$key] = $v;
            }
        }

        // Scalar-ify all non-proxyaddresses fields
        foreach ($this->userinfo as $k => $v) {
            if ($k === 'proxyaddresses') { continue; }
            if (is_array($v)) {
                $this->userinfo[$k] = isset($v[0]) ? $v[0] : '';
            }
        }

        // ── Resolve the canonical email ────────────────────────────────────
        $resolvedEmail = '';

        if (KAYAKO_AD_EMAIL_SOURCE === 'proxyaddresses' && isset($this->userinfo['proxyaddresses'])) {
            $raw = $this->userinfo['proxyaddresses'];

            // proxyAddresses can be:
            //   1. A plain string: "user@google.com"
            //   2. An LDAP array: ["user@google.com", ...] with a 'count' key
            //   3. An Exchange-style array: ["SMTP:user@google.com", "smtp:alias@old.com"]
            if (is_array($raw)) {
                $count = isset($raw['count']) ? (int)$raw['count'] : count($raw);
                $plain   = '';   // value without any prefix
                $primary = '';   // value with uppercase SMTP: prefix (primary address)
                $anysmtp = '';   // value with any smtp: prefix (lowercase fallback)

                for ($i = 0; $i < $count; $i++) {
                    $entry = trim(isset($raw[$i]) ? $raw[$i] : '');
                    if ($entry === '') { continue; }

                    if (strncmp($entry, 'SMTP:', 5) === 0) {
                        $primary = substr($entry, 5);
                        break; // primary address found — stop here
                    } elseif (strncasecmp($entry, 'smtp:', 5) === 0) {
                        if ($anysmtp === '') { $anysmtp = substr($entry, 5); }
                    } else {
                        // No prefix — already a plain email address
                        if ($plain === '') { $plain = $entry; }
                    }
                }

                // Priority: SMTP: (primary) > no prefix > smtp: (any)
                $resolvedEmail = $primary !== '' ? $primary
                               : ($plain   !== '' ? $plain
                               : $anysmtp);
            } else {
                // Scalar value — already a plain email address
                $resolvedEmail = trim($raw);
            }

            $this->log('AD proxyAddresses resolved email: ' . ($resolvedEmail ?: '(none)'));
        }

        // Fallback to plain mail attribute
        if ($resolvedEmail === '' && isset($this->userinfo['mail']) && $this->userinfo['mail'] !== '') {
            $resolvedEmail = $this->userinfo['mail'];
            $this->log('AD mail attribute used as fallback email: ' . $resolvedEmail);
        }

        $this->userinfo['_resolved_email'] = $resolvedEmail;

        // Stringify proxyaddresses for any code that might access it directly
        $this->userinfo['proxyaddresses'] = $resolvedEmail;
    }

    private function _xmlEncode($string, $encode = false) {
        if (empty($string)) { return ''; }
        if ($encode) {
            if (extension_loaded('mbstring')) {
                $string = mb_convert_encoding($string, 'UTF-8');
            } elseif (function_exists('utf8_encode')) {
                $string = utf8_encode($string);
            }
            $string = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
        }
        return trim(htmlspecialchars($string, ENT_XML1, 'UTF-8'));
    }
}
