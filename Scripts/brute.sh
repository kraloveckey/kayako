#!/usr/bin/env bash

set -o nounset
set -o pipefail

HOME_FOLDER="/opt/audit"

MAIL_FROM="MAIL_FROM"
MAIL_TO="MAIL_TO"
MAIL_AUTH="MAIL_AUTH"
MAIL_PASS="MAIL_PASS"
MAIL_SMTP="MAIL_SMTP"

LOG_PATH="/var/www/helpdesk/ldap/log/log.txt"
EMAIL="${HOME_FOLDER}/brute_mail.txt"
EMAIL1="${HOME_FOLDER}/brute_mail1.txt"
TMP_FILE="${HOME_FOLDER}/brute_temp.txt"

#### Main function
grep "\[$(date -d '1 hour ago' '+%m-%d-%y - %H'):" ${LOG_PATH} | grep "Failed authorization:" > ${TMP_FILE}

if [[ -e ${TMP_FILE} && ! -s ${TMP_FILE} ]]
then
    echo -e "\nNo new registrations bruteforce events.\n"
    rm ${TMP_FILE}
else
    echo -e "Bruteforce statistics:\n" >> ${EMAIL}
    cat ${TMP_FILE} | awk -F" " '{ print $6 }' > ${EMAIL1}
    sort ${EMAIL1} | uniq -c | sort -nr >> ${EMAIL}
    echo -e "" >> ${EMAIL}
    cat ${TMP_FILE} >> ${EMAIL}
    swaks -f ${MAIL_FROM} -t ${MAIL_TO} -s ${MAIL_SMTP} --auth-user=${MAIL_AUTH} --auth-password=${MAIL_PASS} -tlsc -p 465 --body ${EMAIL} \
    --header "Subject: Helpdesk Bruteforce Report" --add-header "Content-Type: text/plain; charset=UTF-8" --h-From: '"Helpdesk Server" <'${MAIL_FROM}'>'

    rm ${EMAIL} ${EMAIL1} ${TMP_FILE}

fi

exit 0