<?php
/**
 * Kayako_Google_LDAP — Google Workspace LDAP handler for Kayako LoginShare v4
 * Extends the lightweight googleLDAP class (stunnel-based connection).
 *
 * @version 2.0.0
 * @author kraloveckey
 */

include KAYAKO_GOOGLE_CLASS_PATH . 'googleLDAP.php';

class Kayako_Google_LDAP extends googleLDAP {

    public $password  = '';
    public $userinfo  = array();
    public $username  = '';

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
            // Aliases
            if ($name === 'telephone')     { $name = 'telephonenumber'; }
            if ($name === 'proxyaddresses') { $name = 'mail'; }

            if (isset($this->userinfo[$name]) && $this->userinfo[$name] !== '') {
                return $this->_xmlEncode($this->userinfo[$name]);
            }
            return '';
        }
    }

    function __destruct() {
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
            ? KAYAKO_GOOGLE_LDAP_PASSWORD
            : (isset($_POST['password']) ? $_POST['password'] : '');
        return $this->password;
    }

    public function getUsername() {
        if (!empty($this->username)) { return $this->username; }
        $this->username = KAYAKO_LDAP_TEST
            ? KAYAKO_GOOGLE_LDAP_USERNAME
            : (isset($_POST['username']) ? $_POST['username'] : '');
        return $this->username;
    }

    // ── User / Staff info loaders ─────────────────────────────────────────

    public function getUser($attributes = array()) {
        $this->_attributes = empty($attributes)
            ? array('displayname', 'title', 'mail', 'telephonenumber', 'givenname', 'sn', 'mobile')
            : array_map('strtolower', $attributes);

        $info = $this->user()->info($this->getUsername(), $this->_attributes);
        $this->_setUserinfo($info);

        if (empty($this->userinfo['mail'])) {
            $this->log('Google: mail not found for ' . $this->getUsername());
            $this->displayErrorXML('User does not have an email address');
            die();
        }
        return $this->userinfo;
    }

    public function getStaff($attributes = array()) {
        return $this->getUser($attributes);
    }

    // ── Logging ───────────────────────────────────────────────────────────

    public function log($string) {
        if (!KAYAKO_LDAP_LOG || !$this->_openLog()) { return; }
        @fwrite($this->_log, date('[m-d-y - H:i]') . ' [Google] ' . $string . "\n");
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

    private function _setUserinfo($userinfo) {
        $this->userinfo = array();
        if (empty($userinfo) || !is_array($userinfo) || !isset($userinfo[0])) { return; }
        foreach ($userinfo[0] as $k => $v) {
            if (is_int($k) || $k === 'count') { continue; }
            $key = strtolower($k);
            $this->userinfo[$key] = is_array($v) ? (isset($v[0]) ? $v[0] : '') : $v;
        }
    }

    private function _xmlEncode($string, $encode = false) {
        if (empty($string)) { return ''; }
        if ($encode) {
            if (extension_loaded('mbstring')) {
                $string = mb_convert_encoding($string, 'UTF-8');
            }
            $string = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
        }
        return trim(htmlspecialchars($string, ENT_XML1, 'UTF-8'));
    }
}
