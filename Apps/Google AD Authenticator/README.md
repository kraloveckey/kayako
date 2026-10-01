# Google Workspace + Active Directory Dual-Provider Authenticator for Kayako v4

A single LoginShare endpoint for Kayako v4 that supports two LDAP providers simultaneously — Google Workspace and Active Directory — without creating duplicate user accounts.

---

## 1. Architecture

```
Kayako LoginShare
       │
       ▼
google-ad-auth.php              ← single entry point
       │
       ├─ username contains @   →  Google LDAP (via stunnel)  →  AD fallback
       └─ username without @    →  AD LDAP (sAMAccountName)   →  Google fallback
              │
              ├─ Kayako_Google_LDAP  (extends googleLDAP)
              │    └─ google-ad-auth/googleLDAP/googleLDAP.php
              │
              └─ Kayako_AD_LDAP  (extends adLDAP)
                   └─ google-ad-auth/adLDAP/adLDAP.php
```

**One Kayako account per person** — the canonical email is always sourced from Google Workspace:
- Google LDAP path: taken from the `mail` attribute
- AD path: taken from `proxyAddresses` (the Google email stored there), with `mail` as a fallback

A user can log in with either their Google email + Google password, or their `sAMAccountName` + domain password, and always land on the same Kayako account.

---

## 2. File Structure

```
google-ad-auth.php                    ← Kayako LoginShare entry point
google-ad-auth_user.html              ← test form for user logins
google-ad-auth_staff.html             ← test form for staff logins
sync_group_members.sh                 ← copy to /etc/stunnel/ (nested groups, see Section 6)
gbrute.sh                             ← copy to /opt/audit/ (brute force report, see Section 7)
google-ad-auth/
  bootstrap.php                       ← loads both providers
  config.php                          ← all configuration (Google + AD + nested groups)
  helpers.php                         ← create_google_ldap() + create_ad_ldap()
  google_ldap_provider.php            ← Kayako_Google_LDAP class
  ad_ldap_provider.php                ← Kayako_AD_LDAP class
  googleLDAP/
    googleLDAP.php                    ← lightweight LDAP client for Google/stunnel
  adLDAP/
    adLDAP.php                        ← adLDAP library v4.0.4
    classes/                          ← adLDAPGroups, adLDAPUsers, etc.
    collections/                      ← adLDAPCollection, etc.
  log/
    log.txt                           ← combined auth log (both providers)
/etc/stunnel/
  sync_group_members.sh               ← cron script: rebuilds nested group membership files
  nested_<group>.txt                  ← flat membership files (one email per line)
```

---

## 3. Setup

### Step 1 — Stunnel (Google LDAP only)

Follow the [Google Workspace Admin Guide](https://knowledge.workspace.google.com/admin/apps/connect-ldap-clients-to-the-secure-ldap-service) to install Stunnel:

```toml
# /etc/stunnel/google-ldap.conf
[ldap]
client  = yes
accept  = 127.0.0.1:389
connect = ldap.google.com:636
cert    = /etc/stunnel/google-ldap.crt
key     = /etc/stunnel/google-ldap.key
```

Verify before testing PHP:
```bash
ldapsearch -x -D "user@your-domain.com" -w yourpassword \
  -H ldap://127.0.0.1:389 -b dc=your-domain,dc=com '(mail=user@your-domain.com)'
```

### Step 2 — Configure `google-ad-auth/config.php`

**SECTION 1 — Google Workspace LDAP:**
```php
$ldap_domain_info        = array('@your-google-domain.com' => 'dc=your-google-domain,dc=com');
$ldap_domain_controllers = array('127.0.0.1');   // stunnel proxy

// Full DNs for Google groups
$google_staff_groups = array(
    'cn=helpdesk.staff,ou=Groups,dc=your-google-domain,dc=com' => 'Default Team',
);
$google_user_groups = array(
    'cn=helpdesk.admins,ou=Groups,dc=your-google-domain,dc=com' => 'System Administrators',
);

define('KAYAKO_GOOGLE_LDAP_USERNAME', 'your-google-ldap-service-account');
define('KAYAKO_GOOGLE_LDAP_PASSWORD', 'your-google-ldap-password');
```

**SECTION 2 — Active Directory:**
```php
$ad_domain_info        = array('@ad-domain.local' => 'DC=ad-domain,DC=local');
$ad_domain_controllers = array('dc01.ad-domain.local');

// Plain group names for AD (same as classic adLDAP)
$ad_staff_groups = array('Helpdesk Staff' => 'Default Team');
$ad_user_groups  = array('Helpdesk Admins' => 'System Administrators');

// Service account — sAMAccountName only, no @domain suffix
define('KAYAKO_AD_LDAP_USERNAME', 'svc-kayako');
define('KAYAKO_AD_LDAP_PASSWORD', 'service-account-password');

// Where the Google email is stored in AD:
//   'proxyaddresses' — field contains Google email directly (e.g. "user@google.com")
//   'mail'          — use the plain mail attribute
define('KAYAKO_AD_EMAIL_SOURCE', 'proxyaddresses');
```

> **Note on group format:**
> - Google groups → **full DNs**: `cn=group-name,ou=Groups,dc=domain,dc=com`
> - AD groups → **plain names**: `Helpdesk Staff`

**SECTION 4 — Nested Groups (Google LDAP only, optional):**
```php
// Groups listed here are checked via a pre-built flat file instead of LDAP queries.
// The key must match exactly the DN used in $google_staff_groups / $google_user_groups.
$google_nested_group_files = array(
    'cn=helpdesk.staff,ou=Groups,dc=your-google-domain,dc=com' => '/etc/stunnel/nested_helpdesk.staff.txt',
);
```

See Section 6 for details on when and why to use this.

### Step 3 — Kayako Admin CP

Go to `Settings → LoginShare`:

- **User LoginShare API URL**: `https://your-domain.com/google-ad-auth.php`
- **Staff LoginShare API URL**: `https://your-domain.com/google-ad-auth.php?type=staff`

### Step 4 — Kayako Database

Sync `username` with `email` in `swstaff` to prevent duplicate staff profiles:

```sql
UPDATE swstaff SET username = email WHERE username != email;
```

---

## 4. Kayako Core Patches

### Fast way

```shell
$ nano /var/www/helpdesk/__swift/apps/base/library/LoginShare/class.SWIFT_LoginShareStaff.php

// WAS:
$_SWIFT_StaffObject->UpdateLoginShare($_staffFirstName, $_staffLastName, $_staffDesignation, $_username, $_staffGroupID, $_staffEmail, $_staffMobileNumber,
                    $_staffSignature);

// NOW:
$_SWIFT_StaffObject->UpdateLoginShare($_staffFirstName, $_staffLastName, $_staffDesignation, $_staffEmail, $_staffGroupID, $_staffEmail, $_staffMobileNumber,
                    $_staffSignature);


// WAS:
$_SWIFT_StaffObject = SWIFT_Staff::Create($_staffFirstName, $_staffLastName, $_staffDesignation, $_username, substr(BuildHash(), 0, 14), $_staffGroupID,
                $_staffEmail, $_staffMobileNumber, $_staffSignature);

// NOW:
$_SWIFT_StaffObject = SWIFT_Staff::Create($_staffFirstName, $_staffLastName, $_staffDesignation, $_staffEmail, substr(BuildHash(), 0, 14), $_staffGroupID,
                $_staffEmail, $_staffMobileNumber, $_staffSignature);

###########################################################################

$ nano /var/www/helpdesk/__swift/apps/base/library/LoginShare/class.SWIFT_LoginShareStaff.php

// WAS:
$_staffContainer = SWIFT_Staff::RetrieveOnUsername($_username);

// NOW:
$_staffContainer = SWIFT_Staff::RetrieveOnUsername($_username);

// Fallback: if not found by username, try by email from LoginShare XML.
// This handles AD logins where username = sAMAccountName (e.g. "jdoe")
// but the existing Kayako account has username = email (e.g. "user@domain.com").
if (empty($_staffContainer) && !empty($_staffEmail)) {
    $_staffContainer = SWIFT_Staff::RetrieveOnEmail($_staffEmail);
}

###########################################################################

$ nano /var/www/helpdesk/__swift/locale/en-us/en-us.php

    'username'                     => 'GW Email | Domain Username',
    'password'                     => 'GW Password | Domain Password',

###########################################################################

$ nano +79 /var/www/helpdesk/__swift/themes/__cp/templates/loginform.tpl
$ sed -n '77,81p' /var/www/helpdesk/__swift/themes/__cp/templates/loginform.tpl
                                                                <td>
                                                                        <input type="submit" name="submitbutton" class="rebutton" value="<{$_language[login]}>" onfocus="blur();" />
                                                                        <a href="#" class="options" onclick="javascript:toggleLoginOptions();" onfocus="blur();" />View <{$_language[options]}> &darr;</a>
                                                                </td>
                                                        </tr>
```

### Long way

Two files in the Kayako core were patched to support email-based account matching for staff LoginShare.

### `__swift/apps/base/library/LoginShare/class.SWIFT_LoginShareStaff.php`

**What was changed:** Added email fallback lookup when `RetrieveOnUsername` returns no result, and replaced `$_username` with `$_staffEmail` in both `UpdateLoginShare` and `SWIFT_Staff::Create` calls.

**Why:** Kayako searches for existing staff by the POST `username` field (e.g. `jdoe`). When logging in via AD, the sAMAccountName is used for authentication but the existing Kayako account has `username = email`. Without this patch, Kayako would create a new duplicate account on every AD login.

**Change 1 — fallback lookup by email:**
```php
// BEFORE:
$_staffContainer = SWIFT_Staff::RetrieveOnUsername($_username);

// AFTER:
$_staffContainer = SWIFT_Staff::RetrieveOnUsername($_username);
if (empty($_staffContainer) && !empty($_staffEmail)) {
    $_staffContainer = SWIFT_Staff::RetrieveOnEmail($_staffEmail);
}
```

**Change 2 — use email as username when updating/creating:**
```php
// BEFORE (both UpdateLoginShare and SWIFT_Staff::Create):
..., $_username, ...

// AFTER:
..., $_staffEmail, ...
```

---

## 5. Provider Routing Logic

| What the user enters | First provider | Fallback |
|---|---|---|
| `user@google-domain.com` | Google LDAP | AD LDAP |
| `jdoe` (sAMAccountName) | AD LDAP | Google LDAP |

Both paths resolve to the same Kayako email (sourced from Google Workspace), so there are no duplicate accounts regardless of which provider authenticated the user.

---

## 6. Nested Groups

Both providers support nested group membership:

**AD LDAP:** Uses the `adLDAPUsers->inGroup()` method with `recursiveGroups = true` (default in adLDAP 4.0.4). The `Kayako_AD_LDAP::inGroup()` wrapper ensures the original `sAMAccountName` is used for lookups even after `$this->username` has been overridden to the Google email.

**Google LDAP:** Google Secure LDAP does not return nested groups in `memberOf`. The `inGroup()` method handles this in two ways depending on configuration:

**Option A — LDAP queries (default, one level of nesting):**
1. Fetch the user's direct `memberOf` list — check for immediate match
2. Fetch the `member` list of the target group using `(cn=groupname)` filter — check if the user's DN or any of their direct groups appears in it

**Option B — File-first (for deep nesting, faster):**

If a group is listed in `$google_nested_group_files` in `config.php`, `inGroup()` skips all LDAP queries entirely and checks a pre-built flat file instead. This is faster (~0ms vs ~3s per group) and handles unlimited nesting depth.

The flat files are maintained by a cron script. It allows only one run at a time (`flock`) and keeps the old file if `ldapsearch` fails:

```bash
# /etc/stunnel/sync_group_members.sh — runs every hour
0 * * * * /etc/stunnel/sync_group_members.sh
```

To add a group to file-based checking:

1. Add an `add_group` line in `sync_group_members.sh`:
```bash
add_group "group.name" "dc=your-domain,dc=com" "nested_group.name.txt"
```

2. Add the group to `$google_nested_group_files` in `config.php`:
```php
$google_nested_group_files = array(
    'cn=group.name,ou=Groups,dc=your-domain,dc=com' => '/etc/stunnel/nested_group.name.txt',
);
```

The key in `$google_nested_group_files` must match exactly the DN used in `$google_staff_groups` or `$google_user_groups` — that is how the Kayako group mapping is preserved.

---

## 7. Logging

Both providers write to a single file: `google-ad-auth/log/log.txt`.
Entries are tagged for easy filtering:

```
[05-25-26 - 14:30] [Google] === Google LDAP attempt ===
[05-25-26 - 14:30] [Google] Auth provider: google
[05-25-26 - 14:31] [AD]     === AD LDAP attempt ===
[05-25-26 - 14:31] [AD]     AD proxyAddresses resolved email: user@google.com
[05-25-26 - 14:31] [AD]     AD: username overridden from jdoe to user@google.com
```

To enable logging, make `log/` writable (`chmod 755`) and set `KAYAKO_LDAP_LOG = true` in `config.php`.

> [!WARNING]
> The `google-ad-auth/` folder (config with credentials, `log/log.txt` with logins) must not be reachable over HTTP. With the nginx config from the [main README](../../README.md#configure-nginx) `*.txt` files are served by the media location, so add:
>
> ```nginx
> location ^~ /google-ad-auth/ {
>         deny all;
> }
> ```
>
> Check: `curl -sk -o /dev/null -w '%{http_code}\n' https://your-domain.com/google-ad-auth/log/log.txt` must return `403`. Remove the HTML test forms from production after testing.

**POST data logging (disabled by default):** To log POST data for debugging, uncomment one of the two blocks at the top of `google-ad-auth.php` — one masks the password, the other logs it in plain text.

**Brute force monitoring:** [`gbrute.sh`](./gbrute.sh) (deployed as `/opt/audit/brute.sh`) scans the log hourly for `Authentication failed for user:` entries and sends an email report via swaks. Add to crontab:
```bash
0 * * * * /opt/audit/brute.sh
```

---

## 8. Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| AD auth OK but group check fails | `admin_username` key wrong in `helpers.php` | Key must be `'admin_username'` (no underscore between "user" and "name") — this is what adLDAP reads; `'admin_user_name'` is silently ignored |
| Google auth OK but group check fails | Service account rebind not working | Check `admin_user_name` / `admin_password` in `$google_adldap_options` in `config.php` |
| AD auth OK but email is empty | `proxyAddresses` is empty or holds old domain | Check AD attribute; switch `KAYAKO_AD_EMAIL_SOURCE` to `'mail'` |
| Duplicate Kayako staff accounts | Core patch not applied or `username` not synced | Apply patch from Section 4; run SQL from Step 4 |
| Access denied (AD) | Group name mismatch | AD groups use plain names, not DNs |
| Access denied (Google) | Group DN mismatch or nested group | Google groups require full DNs; nested groups supported one level deep via LDAP, unlimited depth via the membership file (Section 6) |
| Access denied (Google nested) | User is nested deeper than one level | Use the membership file for this group (Section 6) |
| Access denied (Google file-based) | Flat file missing or empty | Run `sync_group_members.sh` manually; check `/etc/stunnel/nested_*.txt` |
| Slow authentication (15–20s) | Each LDAP query costs ~1.5s via stunnel | Normal for LDAP path — use file-based nested groups (Section 6) to eliminate group-check queries |
| Fatal error on startup | stunnel not running | `systemctl status stunnel4` |
| AD bind fails for service account | Wrong credentials or suffix | `KAYAKO_AD_LDAP_USERNAME` must be `sAMAccountName` only, no `@domain` suffix |
| Staff username reset to sAMAccountName | Core patch `UpdateLoginShare` not applied | Re-apply Change 2 from Section 4 |
