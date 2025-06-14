# Kayako Fusion Helpdesk

## Overview

- [Apps](./Apps/)
  - [SAML Single Sign-On](./Apps/SAML%20Single%20Sign-On/) - Using single sign-on (SSO) will permit a single action of user authentication and authorization to access all computers and systems where he has access permission, without the need to enter multiple passwords.
  - [PHP AD LDAP Authenticator](./Apps/PHP%20AD%20LDAP%20Authenticator/) - Basic Active Directory Authenticator for Kayako LoginShare v4.x. Integrates Active Directory with Kayako Helpdesk.
- [Icons](./Icons/) - Some .ico files for custom tasks.
- [Languages Custom](./Languages%20Custom/) - Customized English and Ukrainian .xml languages.
- [Scripts](./Scripts/) - Scripts for checking [AD brute-force attempts](./Scripts/brute.sh), [cleaning trash sessions](./Scripts/sessions.sh) (e.x. prometheus blackbox exporter etc.) and [enabling-disabling users and staff](./Scripts/users.sh) according to AD.
- [Source Stable](./Source%20Stable/) - Latest stable versions Kayako Fusion and GFI Helpdesks: 4.98.9 (working with PHP 7.x).
- [Templates Custom](./Templates%20Custom/) - Customized templates for cleaning up unnecessary items.

## Install and Configure MariaDB

```shell
apt install mariadb-server-10.6
mysql -u root -p
```

```sql
CREATE DATABASE helpdesk; 
USE helpdesk; 
ALTER DATABASE helpdesk DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci; 
CREATE USER 'helpdesk_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON helpdesk.* TO 'helpdesk_user'@'localhost';
FLUSH PRIVILEGES;
```

**/etc/mysql/mariadb.conf.d/50-server.cnf**
```conf
#
# These groups are read by MariaDB server.
# Use it for options that only the server (but not clients) should see

# this is read by the standalone daemon and embedded servers
[server]
tmp_table_size= 64M
max_heap_table_size= 64M
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 1

[client]
default-character-set = utf8mb4

[mysqld]
bind-address = 127.0.0.1
default-authentication-plugin = mysql_native_password
collation_server = utf8_general_ci
character_set_server = utf8
max_allowed_packet = 128M
group_concat_max_len = 2048
max_connections = 1000
sql_mode = 'NO_ENGINE_SUBSTITUTION'
expire_logs_days = 7
pid-file        = /var/run/mysqld/mysqld.pid
socket          = /var/run/mysqld/mysqld.sock
datadir         = /var/lib/mysql
log-error       = /var/log/mysql/error.log
port  = 3306
skip-external-locking
key_buffer_size = 256M
table_open_cache = 256
sort_buffer_size = 1M
read_buffer_size = 1M
read_rnd_buffer_size = 4M
myisam_sort_buffer_size = 64M
thread_cache_size = 8
open_files_limit=1000000
transaction_isolation = READ-COMMITTED
binlog_format = ROW
connect_timeout = 300
optimizer_search_depth = 7
# this is only for embedded server
[embedded]

# This group is only read by MariaDB servers, not by MySQL.
# If you use the same .cnf file for MySQL and MariaDB,
# you can put MariaDB-only options here
[mariadb]

# This group is only read by MariaDB-10.6 servers.
# If you use the same .cnf file for MariaDB of different versions,
# use this group for options that older servers don't understand
[mariadb-10.6]
```

## Install and Configure PHP

```shell
apt install php7.3 php7.3-mysql php7.3-fpm php7.3-mbstring php7.3-xml php7.3-imap php7.3-zip php7.3-gd php7.3-curl php7.3-gd php7.3-intl php7.3-ldap php7.3-dev php7.3-mcrypt
```

**/etc/php/7.3/fpm/pool.d/www.conf**
```conf
...

pm = dynamic

; The number of child processes to be created when pm is set to 'static' and the
; maximum number of child processes when pm is set to 'dynamic' or 'ondemand'.
; This value sets the limit on the number of simultaneous requests that will be
; served. Equivalent to the ApacheMaxClients directive with mpm_prefork.
; Equivalent to the PHP_FCGI_CHILDREN environment variable in the original PHP
; CGI. The below defaults are based on a server without much resources. Don't
; forget to tweak pm.* to fit your needs.
; Note: Used when pm is set to 'static', 'dynamic' or 'ondemand'
; Note: This value is mandatory.
pm.max_children = 32

; The number of child processes created on startup.
; Note: Used only when pm is set to 'dynamic'
; Default Value: min_spare_servers + (max_spare_servers - min_spare_servers) / 2
pm.start_servers = 12

; The desired minimum number of idle server processes.
; Note: Used only when pm is set to 'dynamic'
; Note: Mandatory when pm is set to 'dynamic'
pm.min_spare_servers = 8

; The desired maximum number of idle server processes.
; Note: Used only when pm is set to 'dynamic'
; Note: Mandatory when pm is set to 'dynamic'
pm.max_spare_servers = 16

; The number of seconds after which an idle process will be killed.
; Note: Used only when pm is set to 'ondemand'
; Default Value: 10s
;pm.process_idle_timeout = 10s;

; The number of requests each child process should execute before respawning.
; This can be useful to work around memory leaks in 3rd party libraries. For
; endless request processing specify '0'. Equivalent to PHP_FCGI_MAX_REQUESTS.
; Default Value: 0
pm.max_requests = 300

...
```

**Recommended PHP/MySQL Parameters**. These settings are done in the PHP configuration file php.ini of the server on which you are installing the helpdesk:

```php
max_execution_time: 600
max_input_time: 600

memory_limit: 512M
NOTE: This varies if you plan on using the Kayako Classic import tool, or if the web server is shared, a higher limit is required.

output_buffering: 4096
file_uploads: On
upload_max_filesize: 20M
post_max_size: 20M
max_file_uploads: 20
open_basedir: Off
always_populate_raw_post_data: -1
```

## Install and Configure Helpdesk

```shell
cd /opt/
tar -xvf fusion_stable_sourceobf_4_98_9_10335_df7ca14.tar.gz
mv fusion-stable-sourceobf-4-98-9-10335-df7ca14/upload /var/www/helpdesk
rm -rf fusion-stable-sourceobf*
cd /var/www/helpdesk/__swift/config/
cp config.php.new config.php
nano config.php
```

Editing the `config.php` file.

The `config.php` file must be manually edited for the configuration of the helpdesk's database connection.

The following variables in this file need to be edited:
- `$_DB["hostname"]` – Unless you have supplied a different hostname when creating your database, this variable should be set to `localhost`.
- `$_DB["username"]` – Unless you have supplied a different user name when creating your database, this variable should be set to `kayako_user`.
- `$_DB["password"]` – The variable should be set to the password that you supplied when setting up your database user.
- `$_DB["name"]` - Unless you have supplied a different user name when creating your database, this variable should be set to `kayako_fusion`.

Upload `key.php` file with license in the root directory of helpdesk installation folder on the server.

Change the hosts file:

```shell
$ nano /etc/hosts
127.0.0.1 localhost helpdesk.dns.com
```

Changing permissions:

```shell
cd /var/www/helpdesk/
chown -R www-data: *
cd __swift/
chmod -Rf 777 files cache geoip logs library
cd ..
chmod -Rf 777 __apps
```

## Configure NGINX

```shell
cd /etc/nginx/conf.d/
```

**php-fpm.conf**
```nginx
# PHP-FPM FastCGI server
# network or unix domain socket configuration

upstream php-fpm {
        server unix:/run/php/php7.3-fpm.sock;
#        server 127.0.0.1:9000;
}
```

**/etc/nginx/conf.d/helpdesk.conf**
```nginx
server {
        listen 80;
        server_name helpdesk.dns.com;

        return 301 https://$server_name$request_uri;
}

server {
        listen 443 ssl http2;

        server_name helpdesk.dns.com;

        root /var/www/helpdesk;
        index index.php index.html index.htm;

        ssl_certificate /etc/nginx/ssl/server.crt;
        ssl_certificate_key /etc/nginx/ssl/server.key;

        include /etc/nginx/ssl.conf;

        location / {
                try_files $uri $uri/ /index.php?$query_string;
        }

        location ~ \.php$ {
                # Prevent the execution of any user-uploadable files from within Kayako:
                if ($uri !~ "^__swift/files/") {
                        fastcgi_pass php-fpm;
                }

                fastcgi_split_path_info ^(.+\.php)(/.+)$;
                try_files $uri $uri/ =404;

                fastcgi_index index.php;
                fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
                fastcgi_param PATH_INFO $fastcgi_script_name;
                fastcgi_intercept_errors on;

                include fastcgi_params;

                fastcgi_buffer_size 128k;
                fastcgi_buffers 256 16k;
                fastcgi_busy_buffers_size 256k;
                fastcgi_temp_file_write_size 256k;
                fastcgi_read_timeout 600;

                proxy_connect_timeout 600;
                proxy_send_timeout 600;
                proxy_read_timeout 600;
                send_timeout 600;
        }

        location ~ ^/(admin|cron|intranet|rss|setup|staff|visitor|winapp|__swift/cache|__swift/files|__swift/onsite|__swift/thirdparty/fusioncharts/Code/FusionCharts|__swift/thirdparty/fusioncharts/Charts|__swift/themes|__swift/thirdparty/TinyMCE/plugins)(.*)$ {
                try_files $uri $uri/ /$1/index.php?$2;
        }

        # -- Cache media images and turn off logging to access log
        location ~ \.(gif|png|swf|js|ico|cur|css|jpg|jpeg|txt|mp3|mp4|ogg|ogv|webm|wav|ttf|woff|eot|svg)$ {
                expires 30d;
                add_header Cache-Control "public";
                access_log off;
        }

        # -- Do not cache document html and data
        location ~ \.(?:manifest|appcache|html?|xml|json)$ {
                expires -1;
        }

        # -- Cache CSS and Javascript
        location ~* \.(?:css|js)$ {
                expires 2d;
                add_header Cache-Control "public";
        }

        # Make sure files with the following extensions do not get loaded by nginx because nginx would display the source code, and these files can contain PASSWORDS!
        location ~* \.(engine|inc|info|install|make|module|profile|test|po|sh|.*sql|theme|tpl(\.php)?|xtmpl)$|^(\..*|Entries.*|Repository|Root|Tag|Template)$|\.php_ {
                deny all;
        }

        # Deny access to internal files.
        location ~ ^/(__swift/config|key.php|__swift/logs/) {
                deny all;
        }

        location ~ /\. {
                deny  all;
        }

        location /robots.txt {
                return 200 "User-agent: *\nDisallow: /";
        }
}
```

```bash
systemctl restart nginx php7.3-fpm.service
```


## Installing Kayako Helpdesk

Now that your server is prepped, you're ready to start the installation itself:

- Open up your browser and go to your Kayako Classic directory `/setup`, e.g.,`https://www.yourdomain.com/support/setup`.
- Click the Setup button to begin the installation process.
- You'll be asked to agree to the license terms, and then the setup utility will check to make sure your server meets all the requirements. When it's done, click Next.
- Your next step is to create credentials for your default administrator account and supply some important details for your helpdesk. The Product URL should be the publicly accessible URL of your helpdesk, e.g., `https://yourdomain.com/support/`.

> NOTE: 
>
> You should only use the URL or domain that is registered on your account. Else, you will receive the error 'This domain name does not match the domain name in the license key file.' after the installation.

- When you've filled in the details, click Start Setup to begin the automated setup procedure.

> NOTE:
> 
> The automated portion of the setup script may take quite some time. Do not interrupt it for any reason or your installation will be corrupt and you'll have to start over! 

- Once setup has completed, you'll see a success screen, and you'll only have one more step before you're ready to start using Kayako Classic!
- Removing the `/setup` directory (**IMPORTANT!**).

```bash
cd /var/www/helpdesk/
mv setup/ ../
```

Once the setup process has completed successfully, you'll need to remove the `setup` directory or folder from your server, as a security measure. You will receive warnings if you do not delete it.
And that's it – congratulations on installing Kayako Classic Download!

## LDAP Configuration

See [PHP AD LDAP Authenticator](./Apps/PHP%20AD%20LDAP%20Authenticator/).

## Other Configuration

Set timezone:

```bash
timedatectl set-timezone Europe/CITY 
```

Also configure SSH and limits: `/etc/sysctl.conf`, `/etc/security/limits.conf`, `/etc/systemd/user.conf`, `/etc/systemd/system.conf`, `/etc/sysfs.conf`.

Set crontab for root user:

```bash
crontab -e
*/3 * * * * wget -O /dev/null --no-check-certificate https://helpdesk.dns.com/cron/index.php?/Parser/ParserMinute/POP3IMAP
0 * * * *       /bin/bash /opt/audit/brute.sh
*/15 * * * *    /bin/bash /opt/audit/sessions.sh
5 20 * * *      /bin/bash /opt/audit/users.sh
```

---

<a href="https://www.paypal.com/donate/?hosted_button_id=GWWLEXEF3XL92">
  <img src="https://raw.githubusercontent.com/kraloveckey/kraloveckey/refs/heads/main/.assets/paypal-donate-button.png" alt="Donate with PayPal" width="225" height="100"/>
</a>
