#!/usr/bin/env bash

set -o nounset
set -o pipefail

mariadb --defaults-extra-file=/root/.my.cnf -e "select useragent from swsessions where ipaddress = 'IP';" | wc -l
mariadb --defaults-extra-file=/root/.my.cnf -e "delete from swsessions where ipaddress = 'IP';"
mariadb --defaults-extra-file=/root/.my.cnf -e "select useragent from swsessions where ipaddress = 'IP';" | wc -l

exit 0