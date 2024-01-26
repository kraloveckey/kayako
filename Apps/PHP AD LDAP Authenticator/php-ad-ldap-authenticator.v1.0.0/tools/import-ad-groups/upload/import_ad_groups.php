<?php
/**
 * Imports Active Directory groups into user and/or staff groups
 *
 * @version 1.0.0
 *
 * @requirements
 * 		Basic Active Directory Authenticator for Kayako LoginShare v4
 *
 * You must be in LDAP test mode (see config file)
 */


//Make sure there is PHP LDAP
if (!function_exists('ldap_connect')) {
	trigger_error('PHP LDAP is required.<br />See <a href="http://www.php.net/ldap">http://www.php.net/ldap</a>', E_USER_ERROR);
}

//Load bootstrap
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

if (!$authUser) {
	die('Could not authenticate');
}

if (!isset($_POST['import'])) {
	//Try to get the groups
	if (($groups = $adldap->group()->all(false)) === false || empty($groups)) {
		trigger_error('Could not retrieve AD groups', E_USER_ERROR);
	}
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Active Directory User Import</title>
</head>
<body>

<?php if (!isset($_POST['import'])) { ?>
		<form action="import_ad_groups.php" method="POST">
			<table>
				<tr>
					<th>Staff</th>
					<th>Users</th>
					<th>Group</th>
				</tr>
			<?php foreach ($groups as $group) { ?>
				<tr>
					<td><input type="checkbox" name="staff[]" value="<?php echo $group; ?>" /></td>
					<td><input type="checkbox" name="users[]" value="<?php echo $group; ?>" /></td>
					<td><?php echo $group; ?></td>
				</tr>
			<?php } ?>
			</table>
			<input type="submit" name="import" value="Import" />
		</form>
<?php } else {
	//Using CRON so we dont get things we shouldn't need
	define('SWIFT_INTERFACE', 'cron');
	define('SWIFT_INTERFACEFILE', __FILE__);

	if (defined("SWIFT_CUSTOMPATH")) {
		chdir(SWIFT_CUSTOMPATH);
	} else {
		chdir(dirname(__FILE__) . '/__swift/');
	}

	require_once ('swift.php');

	SWIFT_Loader::LoadLibrary('User:UserGroup');
	SWIFT_Loader::LoadLibrary('Staff:StaffGroup');

	if (isset($_POST['staff']) && is_array($_POST['staff'])) { ?>
		<p style="font-size: LARGE;">AD staff groups: <br />
		<?php foreach ($_POST['staff'] as $staff) {
			echo $staff;
			$group = SWIFT_StaffGroup::Insert($staff, false);
			if (is_object($group) && $group->GetIsClassLoaded()) {
				echo ' - IMPORTED';
			} else {
				echo ' - FAILED';
			}
			echo '<br />';
		}
		?> </p> <?php
	}
	if (isset($_POST['users']) && is_array($_POST['users'])) { ?>
		<p style="font-size: LARGE;">AD user groups: <br />
		<?php foreach ($_POST['users'] as $user) {
			echo $user;
			$group = SWIFT_UserGroup::Create($user, 1, true);
			if (is_object($group) && $group->GetIsClassLoaded()) {
				echo ' - IMPORTED';
			} else {
				echo ' - FAILED';
			}
			echo '<br />';
		}
		?> </p> <?php
	}
	echo 'Completed!';
} ?>
</body>
</html>