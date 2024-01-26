# SAML Single Sign-On

History

Using single sign-on (SSO) will permit a single action of user authentication and authorization to access all computers and systems where he has access permission, without the need to enter multiple passwords.

## INSTALLATION

1. Download and extract Single.
You can obtain [the latest Single Sign-On release (v1.1)](kayako-saml-sso-integration-1.1.zip) - the files are available in .zip formats and can be extracted using most compression tools.
To download and extract the files, on a typical Unix/Linux command line, use the following commands:

```shell
wget .../kayako-saml-sso-integration-1.1.zip
tar -zxvf kayako-saml-sso-integration-1.1.zip
```

This will create a new directory `samlsso/` containing all samlsso files and directories. Then, to move the contents of that directory within your helpdesk app folder, continue with this command:

```shell
mv samlsso /path/to/your/installation/__apps/
```

2. Go to Admin interface of your helpdesk and click on Apps on left hand side menu.
   
3. Now click on Single Sign On and then click on `Install` button, this will install this app.
   
4. Now click on `Settings` option from left side menu and click on Single Sign On.
   
5. You will see `Single Sign On` settings page.
   
6. First you have to enable the Single Sign On by selecting yes for first option i.e. `Enable Single Sign`.
   
7. Next you can enable sign on through twitter or facebook by enabling option `Enable Twitter Authentication` or `Enable Facebook Authentication` correspondingly but if you want to use Twitter or Facebook login then first you have to configure your Apache or Nginx.
   
8. Find the Apache configuration file for the virtual hosts where you run your helpdesk and create an alias in it. The configuration may look like this:

```shell
<VirtualHost *>
 ServerName service.example.com
 DocumentRoot /var/www/service.example.com(i.e. path to your helpdesk trunk folder)
 Alias /samlidp path to your helpdesk trunk folder/__apps/samlsso/thirdparty/samlidp/www
</VirtualHost>
```

9. For Twitter integration you need to get an API Consumer key and a Consumer secret (update it in Admin settings), by register the application at: http://twitter.com/oauth_clients.
    
10. Set the callback URL to be: http://ky.example.org/samlidp/module.php/authtwitter/linkback.php . Replace ky.example.org with your hostname.
    
11. For Facebook integration you need to get App ID (or API Key) and App Secret (update it in Admin settings), by register the application at: http://www.facebook.com/developers/.    
> Facebook needs the CURL and JSON PHP extensions.

12. If you want to login from your own Identity Provider then provides your IdP details in next few settings options (for setting up your IdP and adding our Service Provider to your IdP refer to http://simplesamlphp.org/docs/stable/simplesamlphp-idp).
    
13. Entity ID – Specify the index of your IdP metadata array, use these while setting IdP for your help desk.
    
14. SingleSignOnService URL – Specify the URL that Kayako will invoke to redirect users to your Identity Provider.
    
15. Your IdP should return Email address and Name.
    
16. Our Assertion Consumer Service (ACS) URL is http://helpdesk_url/index.php?/Samlsso/Sso/Idp/Login.
    
17. Next provide the path for your certificate which you can obtain this from your SAML identity provider.
    
18. Once you are done with all the settings then change the template.
    
19. Now click on Templates -> Templates option from left side menu and click on General.
    
20. List of templates will be shown up, click on header template.
    
21. Add the below code:

```html
After

<div id="loginsubscribebuttons"><input class="rebutton" value="<{$_language[login]}>" type="submit" /></div>

Add code
<{if isset($_twitterEnable) || isset($_facebookEnable) || isset($_ssoIdpEnable)}>
<hr class="vdivider">
 <{/if}>
<{if isset($_twitterEnable)}>
<div id="twitterlogin" class="widgetrow" style="padding-left:5px; " >
    <span onclick="javascript: window.location.href='<{$_baseName}><{$_templateGroupPrefix}>/Samlsso/Sso/Twitter/Login';">
        <a class="widgetrowitem defaultwidget" style="background-repeat: no-repeat; background-position: 5px 5px; width: 139px; font-size: 13px; padding: 14px 10px 15px 50px;background-image: URL('<{$_swiftPath}>__apps/samlsso/themes/client/images/twitter_icon.jpg');" href="<{$_baseName}><{$_templateGroupPrefix}>/Samlsso/Sso/Twitter/Login">
            <span class="widgetitemtitle">Login Using Twitter</span>
        </a>
    </span>
</div>
<{/if}>
<{if isset($_facebookEnable)}>
<div id="facebooklogin" class="widgetrow" style="padding-left:5px;" >
    <span onclick="javascript: window.location.href='<{$_baseName}><{$_templateGroupPrefix}>/Samlsso/Sso/Facebook/Login';">
        <a class="widgetrowitem defaultwidget" style="background-repeat: no-repeat; background-position: 5px 5px; width: 139px; font-size: 13px; padding: 14px 10px 15px 50px;background-image: URL('<{$_swiftPath}>__apps/samlsso/themes/client/images/facebook_icon.jpg');" href="<{$_baseName}><{$_templateGroupPrefix}>/Samlsso/Sso/Facebook/Login"> 
            <span class="widgetitemtitle">Login Using Facebook</span>
        </a>
    </span>
</div>
<{/if}>
<{if isset($_ssoIdpEnable)}>
<div id="ssoidplogin" class="widgetrow" style="padding-left:5px;" >
    <span onclick="javascript: window.location.href='<{$_baseName}><{$_templateGroupPrefix}>/Samlsso/Sso/Idp/Login';">
        <a class="widgetrowitem defaultwidget" style="background-repeat: no-repeat; background-position: 5px 5px; width: 139px; font-size: 13px; padding: 14px 10px 15px 50px;background-image: URL('<{$_swiftPath}>__apps/samlsso/themes/client/images/saml_icon.jpg');" href="<{$_baseName}><{$_templateGroupPrefix}>/Samlsso/Sso/Idp">
            <span class="widgetitemtitle">Login Using Your IdP</span>
        </a>
    </span>
</div>
<{/if}>
```

22.  After changing the template file you can see the login buttons in Support Center corresponding to remote authentications you have enabled.
    
23. Now you can login with any account.
> When you use SAML with Nginx you have to configure your SAML as nginx does not directly support PATH_INFO, so please do the following changes as given below.

1) Please add in `module.php` right after:

```php
require_once('_include.php');
$config = SimpleSAML_Configuration::getInstance();
if ($config->getBoolean('php.pathinfo_from_requesturi', TRUE)) {
    SimpleSAML_Logger::debug('!!! ATTENTION: USING REQUEST_URI TO GENERATE PATH_INFO !!!');

    // helper function to get pathinfo http://php.net/manual/en/function.strstr.php
    function strstr_after($haystack, $needle, $case_insensitive = false)
    {
        $strpos = ($case_insensitive) ? 'stripos' : 'strpos';
        $pos = $strpos($haystack, $needle);
        if (is_int($pos)) {
            return substr($haystack, $pos + strlen($needle));
        }
        // Most likely false or null
        return $pos;
    }

    $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathinfo = strstr_after($url_path,'module.php');
    SimpleSAML_Logger::debug('REQUEST_URI: '.$_SERVER['REQUEST_URI']);
    SimpleSAML_Logger::debug('URL_PATH : '.$url_path);
    SimpleSAML_Logger::debug('PATH_INFO : '.$_SERVER['PATH_INFO']);
    SimpleSAML_Logger::debug('PATH_INFO_X: '.$pathinfo);
    $_SERVER['PATH_INFO'] = $pathinfo;
}
```

2) Then append to `config.php` these lines:

```php
 /*
 * Use $_SERVER['REQUEST_URI'] to generate $_SERVER['PATH_INFO']
 * This is helpful with a fastcgi deployment, where you might have
 * problems getting $_SERVER['PATH_INFO'] in en expected way
 */
 'php.pathinfo_from_requesturi' => TRUE,
```

Now set the below configuration if you are using Nginx:

```shell
location ~ ^/samlidp/(.+\.php.*)$ {
    alias pathtoyourhelpdeskinstallation/__apps/samlsso/thirdparty/samlidp/www/$1;
    fastcgi_split_path_info ^/samlidp((?U).*\.php)(/?.*)$;
    fastcgi_pass unix:/var/lib/phpfpm.sock; # or 127.0.0.1:9000, If you have install and configure fastcgi... Start it it up at 127.0.0.1:9000
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME pathtoyourhelpdeskinstallation/__apps/samlsso/thirdparty/samlidp/www/$fastcgi_script_name;
    include fastcgi_params;
}

location ~ /samlidp(.*) {
    alias pathtoyourhelpdeskinstallation/__apps/samlsso/thirdparty/samlidp/www/$1;
}

location ~ ^/samlidp/(.*) {
    alias pathtoyourhelpdeskinstallation/__apps/samlsso/thirdparty/samlidp/www/$1;
}

location /samlidp/$ {
    alias pathtoyourhelpdeskinstallation/__apps/samlsso/thirdparty/samlidp/www;
}
```