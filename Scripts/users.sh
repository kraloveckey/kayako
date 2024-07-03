#!/usr/bin/env bash

set -o nounset
set -o pipefail

MAIL_FROM="MAIL_FROM"
MAIL_TO="MAIL_TO"
MAIL_AUTH="MAIL_AUTH"
MAIL_PASS="MAIL_PASS"
MAIL_SMTP="MAIL_SMTP"
MAIL_PORT="MAIL_PORT"

LDAP_HOST="ldap://LDAP_IP:389"
LDAP_PASSWD="LDAP_PASSWD"
LDAP_BASE="OU=USERS,OU=MAIN,DC=dns,DC=com"
LDAP_BIND="cn=USERNAME,ou=USERS,ou=MAIN,ou=Sites,dc=dns,dc=com"

CSV_LDAP_DISABLED="${PWD}/ldapDisabled.csv"
CSV_SWSTAFF_DISABLED="${PWD}/swstaffDisabled.csv"
CSV_SWUSERS_DISABLED="${PWD}/swusersDisabled.csv"

CSV_LDAP_ENABLED="${PWD}/ldapEnabled.csv"
CSV_SWSTAFF_ENABLED="${PWD}/swstaffEnabled.csv"
CSV_SWUSERS_ENABLED="${PWD}/swusersEnabled.csv"

USERS="${PWD}/users.txt"
EMAIL="${PWD}/email.txt"

#################################################
## FIRST PART OF THE SCRIPT FOR DISABLED USERS ##
#################################################

# Get all disabled users from ldapsearch. Filtering them by emails and saving to the file.
ldapsearch -x -H "${LDAP_HOST}" -D "${LDAP_BIND}" -w "${LDAP_PASSWD}" -b "${LDAP_BASE}" "(&(objectCategory=person)(objectClass=user)(userAccountControl:1.2.840.113556.1.4.803:=2))" | \
grep -E 'proxyAddresses:' | sed -r -e 's/\b(cn: |proxyAddresses: )\b//g' > "${CSV_LDAP_DISABLED}"

# Getting enabled users from swstaff and from swusers tables with comparing by fileds from swuseremails(only for swusers) table
# for comparing with ldapsearch after.
mariadb --defaults-extra-file=/root/.my.cnf -e "SELECT swstaff.email FROM swstaff WHERE isenabled = 1;" | awk '{print $1}' > "${CSV_SWSTAFF_ENABLED}"
mariadb --defaults-extra-file=/root/.my.cnf -e "SELECT swuseremails.email FROM swuseremails, swusers WHERE swuseremails.linktypeid = swusers.userid AND swusers.isenabled = 1;" | awk '{print $1}' > "${CSV_SWUSERS_ENABLED}"

# Check if disabled users from ldapsearch are present in swstaff table
CHECK_SWSTAFF_DISABLED=$(grep -wFf "${CSV_SWSTAFF_ENABLED}" "${CSV_LDAP_DISABLED}")

# Check if disabled users from ldapsearch are present in swsusers table
CHECK_SWUSERS_DISABLED=$(grep -wFf "${CSV_SWUSERS_ENABLED}" "${CSV_LDAP_DISABLED}")

# Compare disabled users from ldapsearch and set isenabled value to 0 in swstaff table
if [ -n "${CHECK_SWSTAFF_DISABLED}" ];
then
   for LINE in $(echo "${CHECK_SWSTAFF_DISABLED}" ); do
      mariadb --defaults-extra-file=/root/.my.cnf -e "UPDATE swstaff SET isenabled = 0 WHERE swstaff.email = '${LINE}';"
      echo "- ${LINE}" >> "${USERS}"
   done
   COUNT=$(cat "${USERS}" | sed '/^\s*$/d' | wc -l)
   echo -e "Disabled staff users - ${COUNT}:" >> "${EMAIL}"
   cat "${USERS}" >> "${EMAIL}"
   echo -e "" >> "${EMAIL}"
   rm "${USERS}"
fi

# Compare disabled users from ldapsearch and set isenabled value to 0 in swusers table
if [ -n "${CHECK_SWUSERS_DISABLED}" ];
then
   for LINE in $(echo "${CHECK_SWUSERS_DISABLED}" ); do
      mariadb --defaults-extra-file=/root/.my.cnf -e "UPDATE swusers JOIN swuseremails ON swusers.userid = swuseremails.linktypeid SET swusers.isenabled = 0 WHERE swuseremails.email = '${LINE}';"
      echo "- ${LINE}" >> "${USERS}"
   done
   COUNT=$(cat "${USERS}" | sed '/^\s*$/d' | wc -l)
   echo -e "Disabled users - ${COUNT}:" >> "${EMAIL}"
   cat "${USERS}" >> "${EMAIL}"
   echo -e "" >> "${EMAIL}"
   rm "${USERS}"
fi

#################################################
## SECOND PART OF THE SCRIPT FOR ENABLED USERS ##
#################################################

# Get all enabled users from ldapsearch. Filtering them by emails and saving to the file.
ldapsearch -x -H "${LDAP_HOST}" -D "${LDAP_BIND}" -w "${LDAP_PASSWD}" -b "${LDAP_BASE}" "(&(objectCategory=person)(objectClass=user)(!(useraccountcontrol:1.2.840.113556.1.4.803:=2)))" | \
grep -E 'proxyAddresses:' | sed -r -e 's/\b(cn: |proxyAddresses: )\b//g' > "${CSV_LDAP_ENABLED}"

mariadb --defaults-extra-file=/root/.my.cnf -e "SELECT swstaff.email FROM swstaff WHERE isenabled = 0;" | awk '{print $1}' > "${CSV_SWSTAFF_DISABLED}"
mariadb --defaults-extra-file=/root/.my.cnf -e "SELECT swuseremails.email FROM swuseremails, swusers WHERE swuseremails.linktypeid = swusers.userid AND swusers.isenabled = 0;" | awk '{print $1}' > "${CSV_SWUSERS_DISABLED}"

# Check if enabled users from ldapsearch are present in swstaff table
CHECK_SWSTAFF_ENABLED=$(grep -wFf "${CSV_SWSTAFF_DISABLED}" "${CSV_LDAP_ENABLED}")

# Check if enabled users from ldapsearch are present in swsusers table
CHECK_SWUSERS_ENABLED=$(grep -wFf "${CSV_SWUSERS_DISABLED}" "${CSV_LDAP_ENABLED}")

# Compare enabled users from ldapsearch and set isenabled value to 1 in swstaff table
if [ -n "${CHECK_SWSTAFF_ENABLED}" ];
then
   for LINE in $(echo "${CHECK_SWSTAFF_ENABLED}" ); do
      mariadb --defaults-extra-file=/root/.my.cnf -e "UPDATE swstaff SET isenabled = 1 WHERE swstaff.email = '${LINE}';"
      echo "- ${LINE}" >> "${USERS}"
   done
   COUNT=$(cat "${USERS}" | sed '/^\s*$/d' | wc -l)
   echo -e "Enabled staff users - ${COUNT}:" >> "${EMAIL}"
   cat "${USERS}" >> "${EMAIL}"
   echo -e "" >> "${EMAIL}"
   rm "${USERS}"
fi

# Compare enabled users from ldapsearch and set isenabled value to 1 in swusers table
if [ -n "${CHECK_SWUSERS_ENABLED}" ];
then
   for LINE in $(echo "${CHECK_SWUSERS_ENABLED}" ); do
   mariadb --defaults-extra-file=/root/.my.cnf -e "UPDATE swusers JOIN swuseremails ON swusers.userid = swuseremails.linktypeid SET swusers.isenabled = 1 WHERE swuseremails.email = '${LINE}';"
   echo "- ${LINE}" >> "${USERS}"
done
   COUNT=$(cat "${USERS}" | sed '/^\s*$/d' | wc -l)
   echo -e "Enabled users - ${COUNT}:" >> "${EMAIL}"
   cat "${USERS}" >> "${EMAIL}"
   echo -e "" >> "${EMAIL}"
   rm "${USERS}"
fi

if [ -s "${EMAIL}" ];
then
   swaks -f ${MAIL_FROM} -t ${MAIL_TO} -s ${MAIL_SMTP} --auth-user=${MAIL_AUTH} --auth-password=${MAIL_PASS} -tls -p ${MAIL_PORT} --body ${EMAIL} \
   --header "Subject: xDesk Users Report" --add-header "Content-Type: text/plain; charset=UTF-8" --h-From: '"xDesk Server" <'${MAIL_FROM}'>'
   rm -r "${USERS}" "${EMAIL}"
else
   echo -e "Email list is empty letter will not be send!!!"
fi

rm -r "${CSV_LDAP_DISABLED}" "${CSV_SWSTAFF_DISABLED}" "${CSV_SWUSERS_DISABLED}" "${CSV_LDAP_ENABLED}" "${CSV_SWSTAFF_ENABLED}" "${CSV_SWUSERS_ENABLED}"

exit 0