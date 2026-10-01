#!/bin/bash
# =============================================================================
# sync_group_members.sh
# Rebuild flat membership files for deeply nested Google Workspace LDAP groups.
#
# Run via cron (recommended: once per hour):
#   0 * * * * /etc/stunnel/sync_group_members.sh
#
# Each output file contains one email address per line (sorted, deduplicated).
# For every group listed in config.php -> $google_nested_group_files,
# googleLDAP::inGroup() checks only this file and makes no LDAP queries.
# A missing or unreadable file means "not a member".
#
# HOW TO ADD A NEW GROUP:
#   1. Add a line to the GROUPS section below:
#        add_group "group_cn" "dc=base,dc=dn" "output_filename.txt"
#   2. Uncomment the matching entry in config.php -> $google_nested_group_files
# =============================================================================

LDAP_HOST="127.0.0.1"
LDAP_PORT="389"
BIND_DN="GOOGLE_LDAP_USERNAME"
BIND_PW="GOOGLE_LDAP_PASSWORD"
OUTPUT_DIR="/etc/stunnel"
LOG_FILE="${OUTPUT_DIR}/sync_group_members.log"

# Allow only one run at a time. Two overlapping runs (e.g. a manual run and
# the cron run) write into the same temp file and corrupt the result.
exec 9>"${OUTPUT_DIR}/sync_group_members.lock"
if ! flock -n 9; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] another sync_group_members.sh run is in progress, exiting" >> "${LOG_FILE}"
    exit 0
fi

# Group registry — parallel arrays (no inline comments, bash does not allow them inside arrays)
GROUP_CNS=()
GROUP_BASE_DNS=()
GROUP_FILES=()

add_group() {
    GROUP_CNS+=("$1")
    GROUP_BASE_DNS+=("$2")
    GROUP_FILES+=("$3")
}

# --------------------------------------------------------------------------
# Register groups here — one add_group call per nested group
# --------------------------------------------------------------------------
# add_group "helpdesk.staff" "dc=your-google-domain,dc=com"  "nested_helpdesk.staff.txt"
# add_group "helpdesk.users" "dc=your-google-domain,dc=com"  "nested_helpdesk.users.txt"
# add_group "another.group"  "dc=your-google-domain,dc=com"  "nested_another.group.txt"
# --------------------------------------------------------------------------

echo "[$(date '+%Y-%m-%d %H:%M:%S')] sync_group_members.sh started" >> "${LOG_FILE}"

for i in "${!GROUP_CNS[@]}"; do
    GROUP_CN="${GROUP_CNS[$i]}"
    BASE_DN="${GROUP_BASE_DNS[$i]}"
    OUT_FILE="${OUTPUT_DIR}/${GROUP_FILES[$i]}"
    TMP_FILE="${OUT_FILE}.tmp"
    GROUP_DN="cn=${GROUP_CN},ou=Groups,${BASE_DN}"

    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Syncing group: ${GROUP_DN}" >> "${LOG_FILE}"

    # Strategy 1: query users whose memberOf includes this group.
    # On its own this does not return every nested member; Strategy 2 adds
    # the members it misses.
    ldapsearch -x \
        -H "ldap://${LDAP_HOST}:${LDAP_PORT}" \
        -D "${BIND_DN}" \
        -w "${BIND_PW}" \
        -b "${BASE_DN}" \
        "(memberOf=${GROUP_DN})" \
        mail 2>>"${LOG_FILE}" \
        | grep "^mail:" \
        | awk '{print $2}' \
        | sort -u > "${TMP_FILE}"

    # Keep the old file if ldapsearch failed (stunnel down, bind error, ...).
    # An empty result is rc=0, so a really empty group still updates the file.
    LDAP_RC=${PIPESTATUS[0]}
    if [ "${LDAP_RC}" -ne 0 ]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')]   Strategy 1 ldapsearch failed (rc=${LDAP_RC}), keeping old ${OUT_FILE}" >> "${LOG_FILE}"
        rm -f "${TMP_FILE}"
        continue
    fi

    STRAT1_COUNT=$(wc -l < "${TMP_FILE}")
    echo "[$(date '+%Y-%m-%d %H:%M:%S')]   Strategy 1 (memberOf filter): ${STRAT1_COUNT} addresses" >> "${LOG_FILE}"

    # Strategy 2: fetch direct member DNs from the group, then resolve each
    # to an email. Covers cases where Strategy 1 misses nested members.
    ldapsearch -x \
        -H "ldap://${LDAP_HOST}:${LDAP_PORT}" \
        -D "${BIND_DN}" \
        -w "${BIND_PW}" \
        -b "${BASE_DN}" \
        "(cn=${GROUP_CN})" \
        member 2>>"${LOG_FILE}" \
        | grep "^member:" \
        | awk '{print $2}' \
        | while read -r MEMBER_DN; do
            ldapsearch -x \
                -H "ldap://${LDAP_HOST}:${LDAP_PORT}" \
                -D "${BIND_DN}" \
                -w "${BIND_PW}" \
                -b "${BASE_DN}" \
                "(distinguishedName=${MEMBER_DN})" \
                mail 2>/dev/null \
                | grep "^mail:" \
                | awk '{print $2}'
        done >> "${TMP_FILE}"

    LDAP_RC=${PIPESTATUS[0]}
    if [ "${LDAP_RC}" -ne 0 ]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')]   Strategy 2 ldapsearch failed (rc=${LDAP_RC}), keeping old ${OUT_FILE}" >> "${LOG_FILE}"
        rm -f "${TMP_FILE}"
        continue
    fi

    # Deduplicate, sort, atomically replace the live file
    sort -u "${TMP_FILE}" -o "${TMP_FILE}"
    FINAL_COUNT=$(wc -l < "${TMP_FILE}")
    mv "${TMP_FILE}" "${OUT_FILE}"

    chmod 640 "${OUT_FILE}"
    chown www-data:www-data "${OUT_FILE}" 2>/dev/null || true

    echo "[$(date '+%Y-%m-%d %H:%M:%S')]   Done: ${FINAL_COUNT} unique addresses -> ${OUT_FILE}" >> "${LOG_FILE}"
done

echo "[$(date '+%Y-%m-%d %H:%M:%S')] sync_group_members.sh finished" >> "${LOG_FILE}"
