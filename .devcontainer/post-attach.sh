#!/bin/bash
set -e

shownFile="/tmp/.codespaces-welcome-shown"
readyTplFile=".devcontainer/CODESPACES-READY.tpl.md"
readyFile=".devcontainer/CODESPACES-READY.md"
mauticUrl="https://${CODESPACE_NAME}-8443.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}"
phpMyAdminUrl="https://${CODESPACE_NAME}-8036.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}"
mailpitUrl="https://${CODESPACE_NAME}-8027.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}"


if [[ "${CODESPACES:-}" = "true" ]] && [[ ! -f "$shownFile" ]]; then
    touch "$shownFile"
    sed "s|{mauticUrl}|${mauticUrl}|g; s|{mailpitUrl}|${mailpitUrl}|g; s|{phpMyAdminUrl}|${phpMyAdminUrl}|g" "$readyTplFile" > "$readyFile"
    code "$readyFile"
fi
