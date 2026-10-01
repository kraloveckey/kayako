# Google Workspace LDAP Authenticator for Kayako V4

This project provides a bridge between Google Secure LDAP and Kayako Helpdesk (v4). It replaces the legacy Microsoft AD LoginShare with a secure, certificate-based authentication flow via a local [`Stunnel`](https://knowledge.workspace.google.com/admin/apps/connect-ldap-clients-to-the-secure-ldap-service) proxy.

> [!TIP]
> If you need Google Workspace **and** Active Directory logins at the same time (one Kayako account per person), use [Google AD Authenticator](../Google%20AD%20Authenticator/).

## 1. Architectural Overview

Since PHP's native LDAP extension often struggles with Google's specific SSL/certificate requirements, we use a local tunnel:

1. **Google Cloud**: Provides LDAP over port `636` (requires CRT/Key certificates).
2. **Stunnel (Local)**: Acts as a proxy. It listens on `127.0.0.1:389`, attaches the required certificates, and forwards the encrypted request to Google on port `636`.
3. **PHP (Kayako)**: Communicates with **127.0.0.1:389** as a standard LDAP server (SSL is handled via a tunnel).

## 2. File Structure

- `google.php` — Entry Point. Handles incoming LoginShare requests from Kayako and outputs the final XML response.
- `google/config.php` — Configuration. Contains Stunnel connection parameters, Base DN, and Group mappings.
- `google/helpers.php` — Support Functions. Manages class instantiation, error handling, and session logging.
- `google/kayako_google.php` — Kayako Logic. Transforms raw LDAP data into Kayako-compliant XML (mapping attributes like `mail`, `uid`, and `displayname`).
- `google/googleLDAP/googleLDAP.php` — LDAP Core. A lightweight replacement for `adLDAP`. Handles connection, user bind + service account rebind, user search, and strict DN group membership checks (`inGroup`, including one level of nested groups).
- `google/log/log.txt` — Debug Log. Stores authentication steps, errors, and (optionally) credentials for troubleshooting.

## 3. Setup Instructions

### Step 1: Stunnel Configuration

Follow the official [Google Workspace Admin Guide](https://knowledge.workspace.google.com/admin/apps/connect-ldap-clients-to-the-secure-ldap-service) to install and configure Stunnel. Example `/etc/stunnel/google-ldap.conf`:

```toml
[ldap]
client = yes
accept = 127.0.0.1:389
connect = ldap.google.com:636
cert = /etc/stunnel/google-ldap.crt
key = /etc/stunnel/google-ldap.key
```

### Step 2: Configuring config.php

Enter your Google Workspace settings:

- `$ldap_domain_info`: account suffix => Base DN, for example `array('@dns.com' => 'dc=dns,dc=com')`.
- `$user_groups` and `$staff_groups`: Use the full DNs of the groups (for example, `cn=admins,ou=Groups,dc=dns,dc=com`) to avoid name conflicts.
- Google LDAP access credentials (Admin Console → Apps → LDAP → your client → Access credentials):

```php
define('KAYAKO_LDAP_USERNAME', 'GOOGLE_LDAP_USERNAME'); // Replace this with your Google LDAP username
define('KAYAKO_LDAP_PASSWORD', 'GOOGLE_LDAP_PASSWORD'); // Replace with your Google LDAP password
```

They are passed to the LDAP class through `$adldap_options` (`admin_user_name` / `admin_password`, `ad_port` = port mapped in stunnel). After the user's password is verified, the connection is re-bound under this service account, so user info and group lookups run with read rights. If the rebind fails, the login is rejected.

### Step 3: Verification

Before testing the PHP script, verify that the `Stunnel` is working correctly using the following command:

```bash
ldapsearch -x -D "{$username}" -w {$password} -H ldap://127.0.0.1:389 -b dc={$domain},dc=com '(mail={$user_email})'
```

> [!NOTE]
> Replace `{username}`, `{password}`, `{domain}` and `{$user_email}` with your actual Google LDAP credentials.

### Step 4: Kayako Database Preparation (Staff only)

In Microsoft AD, the `samAccountName` was often the primary identifier. In Google LDAP, we use `uid` or `email`. 

- Make sure the email address in Kayako matches the email address in Google.
- **Recommended**: to prevent duplicate Staff profiles, it is highly recommended to sync the `username` field with the `email` field in the Kayako database (`swstaff` table):

```sql
UPDATE swstaff SET username = email WHERE username != email;
```

## 4. Configuration in Kayako Admin CP

- Navigate to `Settings --> LoginShare`.
- **Staff LoginShare API URL**: `https://your-domain.com/google.php?type=staff`
- **User LoginShare API URL**: `https://your-domain.com/google.php?type=user`

## 5. Technical Details

- **User Filter**: The script searches for users using the filter `(mail=$user)`, so users log in with their full Google email. The value is escaped with `ldap_escape()` before it goes into the filter.
- **Nested Groups**: Google Secure LDAP returns only direct groups in `memberOf`. `inGroup()` checks direct membership first, then reads the `member` list of the target group (one level of nesting: user → group A → target group). For deeper nesting, list the group in `nested_group_files` inside `$adldap_options` (`config.php`) and keep the membership file updated by cron, for example with [`sync_group_members.sh`](../Google%20AD%20Authenticator/sync_group_members.sh). A listed group is checked only against its file.
- **Group Priority**: If a user belongs to multiple authorized groups, the script assigns the group listed first in the `$user_groups` array within `config.php`.
- **Case Sensitivity**: PHP LDAP returns attribute keys in lowercase. The `Kayako_Google_LDAP` class automatically handles this to ensure data consistency.
- **Encoding**: All XML output is encoded in UTF-8 and sanitized via `htmlspecialchars` to prevent rendering issues in the Kayako UI.

## 6. Testing and Debugging

To test the system without using the Kayako interface, use the following test HTML forms:

- `google_user.html` – testing login as a customer.
- `google_staff.html` – testing login as an employee.

The authorization result (success or error message) is always logged in `google/log/log.txt`.

> [!WARNING]
> The `google/` folder (config with credentials, `log/log.txt` with logins) must not be reachable over HTTP. With the nginx config from the [main README](../../README.md#configure-nginx) `*.txt` files are served by the media location, so add:
>
> ```nginx
> location ^~ /google/ {
>         deny all;
> }
> ```
>
> Check: `curl -sk -o /dev/null -w '%{http_code}\n' https://your-domain.com/google/log/log.txt` must return `403`. Remove the HTML test forms from production after testing.

## 7. Troubleshooting

> [!NOTE]
> If authentication fails, check `google/log/log.txt`. You can enable password logging in `helpers.php` temporarily to verify if the credentials being passed to the LDAP server are correct.

- **Access Denied**: Usually means the user is not in the specified Google Groups in `config.php`.
- **Authentication OK, then fails / group check fails**: Check the service account credentials (`KAYAKO_LDAP_USERNAME` / `KAYAKO_LDAP_PASSWORD`); the rebind error is written to the log.
- **Fatal Error**: Check if the `stunnel` service is running and accessible on `127.0.0.1:389`.
- **Duplicate Users**: Verify that the `Email` in Google exactly matches the `Email` in the Kayako profile.