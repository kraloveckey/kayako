# Kayako PHP AD LDAP Authenticator

This information will include how to install, setup, and configure everything

- Name: Basic Active Directory Authenticator for Kayako LoginShare v4
- Version: v1.0.0
- Description: Integrates Active Directory with Kayako LoginShare v4
- Requirements:
    - PHP 5 or 7.x
	- PHP LDAP - http://www.php.net/ldap

- You will need to know some minor information about your domain
    - Domain controller(s) name or IP
        - Example: dc1.mydomain.com or 192.168.1.1
	- Domain suffix
        - Example: @mydomain.com
    - Domain base
        - Example: DC=mydomain,DC=com

You can do staff and/or user integration. Its up to you. See the tools folder for more things to help your integration.

## FAQ

Can I have more than one domain or domain prefix: Yes. See ldap_domain_info.

Can I change what gets mapped from AD to Kayako: Yes, though this requires some PHP knowledge. In the `/ldap/kayako_ldap.php` file the `__get` is used to get attributes from the AD after they are preloaded in getUser or getStaff method. Then the displayUserXML or displayStaffXML method is what puts them together. This is where you can make those changes.

Can I import AD groups: Yes. See Tools.

Can I allow users to login locally as well: I have not tested it, but see. Remember that you are then no longer using AD to authenticate, however every AD user has a local account in Kayako. So users that are disabled or removed would still be able to login with this because they have a local account, unless you have removed them.

## Information

The purpose of this mod is to allow you to authenticate users to AD to allow access to Kayako (either as a regular user and/or staff).

- FlowThe flow of this mod is as follows after being setup correctly.
- User goes to your Kayako page.
- Enters their AD username and password.
- Kayako authenticates to AD using what the user entered for username/password.
- If the are validated they move on, if not they are presented with an error message.
- A new Kayako user account is created which mirrors their AD account.
- Depending on the settings in your config the user's phone number, name, and email address will be brought over from AD to Kayako.
- If this is the first time logging in they are presented with the new user account screen, where they can change their user profile.

## Requirements

- PHP 5 (doesn't require ZEND or Ioncube) or 7.x. PHP LDAP - see: http://www.php.net/ldap
- Email addresses are required in AD on every user account before they will be able - to login
- You will also need to know some minor information about your domain:
    - Domain controller(s) name or IP
        - Example: dc1.mydomain.com or 192.168.1.1
    - Domain suffix
        - Example: @mydomain.com
    - Domain base
        - Generally this is the same as your domain suffix
        - Example: DC=mydomain,DC=com

If you Google around you can find more information about these or how to find them on your domain.

## Installation

- Upload all the files in the upload folder to the root of your kayako location on your web server.
- So the ldap.php will be on the root of your site (/ldap.php).
- Rename /ldap/config_sample.php to config.php.
- Edit the /ldap/config.php and enter all required information: see Configure for more information.
- Enable LoginShare on your template.
- See Setup.

## Configure

Everything is pretty well documented in the /ldap/config_sample.php (or config.php) file however here is a more inclusive documentation

Please do one of the following:

- Required for domain(s) on the same controller(s)
- Required for multiple domains on different controllers

## Setup

This can be used for users and/or staff

### Template

No matter what capacity you use this in you must make sure you have it enabled in your template.

- Login to your admin.
- Go to Templates (left bar).
- Click on Groups.
- Select your template.
- On the window that opens, on the general tab, Use LoginShare.
    - Change it to yes.

### Users

- Login to your admin
- Go to Users
  - Its on the top grey bar under the logo and to the right
- LoginShare
  - On the bar directly under Users
- Enable LoginShare
  - Change it to Yes
- Give it a title
  - For example: Network Login
- Enter the url to your ldap.php
  - Example: http://www.mysite.com/kayako/ldap.php
- Update

### Staff

In order for you to have Staff integration you need to have a valid AD group in which the users must be in and a valid Kayako staff team for them to be a part of. The reason for this is if not setup this way then anyone could login to the staff area.

One way you can handle this is to create a AD group called Kayako Staff and put all the users you want to access the staff area into it.
Then go to insert team: http://www.yoursite.com/admin/index.php?/Base/StaffGroup/Insert.

And add a new team, for example Staff. Now when you get the $staff_groups = array('Group' => 'Staff'); part of the required configuration settings it will make more sense.

- Login to your admin
- Staff
  - Its on the top grey bar under the logo
- LoginShare
  - On the bar and to the right
- Enable LoginShare
  - Change it to Yes
- Give it a title
  - For example: Network Login
- Enter the url to your ldap.php with ?type=staff
  - Example: http://www.mysite.com/kayako/ldap.php?type=staff
- Update

### Optional

#### Logging

If you plan to use logging you must make the ldap/log directory writable (755 or 777). Honestly I would just do it just in case.

#### Disable Kayako Registration
- Login to your admin
- Widgets
- Register
- Change to disabled

#### Remove lost password from the template

- Login to your admin.
- Templates.
- Templates.
- General.
- Header.
- Find and remove (Around the middle):

```html
<div id="logintext"><a href="<{$_baseName}><{$_templateGroupPrefix}>/Base/UserLostPassword/Index"><{$_language[lostpassword]}></a></div>
```

Or you can hide it using `<!-- -->`.

- You may also want to remove the ability to change their password to do so find and remove:

```html
<div class="maitem machangepassword" onclick="javascript: Redirect('<{$_baseName}><{$_templateGroupPrefix}>/Base/UserAccount/ChangePassword');"><{$_language[machangepassword]}></div>
```

Or you can hide it using `<!-- -->`.

- Save.

#### Change the text on the login box from "Your email address"

- Login to your admin.
- Languages.
- Phrases.
- You want loginenteremail, you can scroll through until you find it or use search.
- Change to the text you want (IE Your username).
- Update.

## Tools

There is a folder in the .zip file called tools.

### ldap.hml

See Troubleshooting for more information on this tool

### Import AD Groups

Imports AD groups to user groups and/or staff teams

### Requirements

You must have your username and password, and be in testing mode in the config.php for this to work. See Troubleshooting for more information on how to enable testing mode

### Install

- Upload the import_ad_groups.php file to the root of your server.
- Run http://yoursite.tld/import_ad_groups.php.
- Select the groups you want to import.
- Run it.

## Troubleshooting

If you are having issues here are some steps to try and help you figure out what is going on.

> Please remember to UNDO all the testing changes before going live! This includes test mode since it will cause to you not be able to login.

### First step

Read and check all the Installation, Setup and Configure steps. Many of the issues people have is they didnt follow each step correctly.

### Enable logging

- First make sure that /ldap/log/ is writable.
- Open the ldap/config.php file.
- Find:
    - `define('KAYAKO_LDAP_LOG', false);`
- Change to:
    - `define('KAYAKO_LDAP_LOG', true);`

- Now logging is enabled. You can now try to login again, even if you know it will not work. However if you go to /ldap/log/ you should see log.txt file. In it there should be a log of what is going on. This should help you track down the problem.

### Enable XML logging. 

This will log the actual XML which is sent back to loginshare.

First enable logging as noted above.

- First make sure that /ldap/log/ is writable.
- Open the ldap/config.php file.
- Find:
  - `define('KAYAKO_LDAP_LOG_XML', false);`
- Change to:
  - `define('KAYAKO_LDAP_LOG_XML', true);`
- Now XML logging is enabled. You should now see the XML data in the log file.

### Enable Output logging

This will attempt to log everything that is displayed to the screen. Good for trouble shooting Type 1 errors.

Please note this might not work on all server environments.

First enable logging as noted above.

- First make sure that /ldap/log/ is writable.
- Open the ldap/config.php file.
- Find:
  - `define('KAYAKO_LDAP_LOG_OUTPUT', false);`
- Change to:
  - `define('KAYAKO_LDAP_LOG_OUTPUT', true);`
- Now output logging is enabled. You should now see it in the log file.

### Enable testing

- Open the ldap/config.php file
- Find:
  - `define('KAYAKO_LDAP_USERNAME', 'username');`
  - `define('KAYAKO_LDAP_PASSWORD', 'password');`
- Change the username and password to known good AD account (preferably your own). So - it should look like:
  - `define('KAYAKO_LDAP_USERNAME', 'administrator');`
  - `define('KAYAKO_LDAP_PASSWORD', 'thepassword');`
- Find:
  - `define('KAYAKO_LDAP_TEST', false);`
- Change to:
  - `define('KAYAKO_LDAP_TEST', true);`
- Now you are in testing mode. If you go to the ldap.php you can now test those credentials and see if they work. If they do you should get a valid XML result.

You can also enable logging of the username and password sent to AD to ensure there is no problems with that. By default its commented our for security. To enable it:

- Open /ldap/helpers.php.
- Find:
  - `//$adldap->log('Username: '.$adldap->getUsername().' - Password: '.$adldap->getPassword());`
- Change to:
    - `$adldap->log('Username: '.$adldap->getUsername().' - Password: '.$adldap->getPassword());`
    - Notice the // are now gone.

### Try the ldap.html or ldap_staff.html

In the tools folder you will find ldap.hml and ldap_staff.html. ldap.html is for testing users and ldap_staff.html is for testing staff

If you upload it and go to the url, it will allow you to test different ldap users and see if you get a valid XML.

### Remote troubleshooting

If you are going to give me the ability to remotely troubleshoot please only give me this information in a PM or direct email. Do not post this information here or on the support thread.

If you would like to have me remotely look at your issue I will need the following:

- Remote access to the server running Kayako with LDAP Authenticator already installed.
    - The preferable method would be FTP or SFTP, however SSH will work as well.
    - The account should be a temporary or throw away account.
        - Account must have read/write abilities to the files and folders in your installation.
        - Account can be locked to just the Kayako installation if on a shared server.
    - You can make up the username and password.
- AD account with the proper credentials to login (if it were working obviously).
    - The account should be a temporary or throw away account.
    - You can make up the username and password.
- Anything else required to access your system such as VPN information, ip or url to connect to, etc.

When you send me this info please make sure you are sending me everything you think I need.

I will sign a Non-Disclosure Agreement (NDA) or other legal documents if needed.

## Errors

These are some of the common errors

### Error "Invalid data provided: 1"

Means something is causing the XML to become malformed. Generally a PHP error of some sort. The best option is to use Troubleshooting to help find the problem. You will probably want to enable KAYAKO_LDAP_LOG_OUTPUT

### Error "Invalid data provided:5"

It could be a couple of things. The most common is that your trying to use the user ldap log and not the staff or vice versa. Make sure you have correctly setup the login per the Setup directions. Note that the staff url has ?type=staff at the end

If that is not the issue then see the Invalid data provided: 1 error above.

### Error: "Invalid Data Provided - No Emails" or "User does not have a email address in AD"

Means that user's AD account does not have an email address, which is required for Kayako and AD integration