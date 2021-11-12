#!/usr/bin/env bash

# Set up ddev for use on gitpod

set -eu -o pipefail

MYDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

# Generate a config.gitpod.yaml that adds the gitpod
# proxied ports so they're known to ddev.
shortgpurl="${GITPOD_WORKSPACE_URL#'https://'}"

cat <<CONFIGEND > ${MYDIR}/config.gitpod.yaml
#ddev-gitpod-generated
use_dns_when_possible: false
# Throwaway ports, otherwise Gitpod throw an error 'port needs to be > 1024'
router_http_port: "8888"
router_https_port: "8889"
additional_fqdns:
- 8888-${shortgpurl}
- 8025-${shortgpurl}
- 8036-${shortgpurl}
CONFIGEND

# We need host.docker.internal inside the container,
# So add it via docker-compose.host-docker-internal.yaml
hostip=$(awk "\$2 == \"$HOSTNAME\" { print \$1; }" /etc/hosts)

cat <<COMPOSEEND >${MYDIR}/docker-compose.host-docker-internal.yaml
#ddev-gitpod-generated
version: "3.6"
services:
  web:
    extra_hosts:
    - "host.docker.internal:${hostip}"
    # This adds 8080 on the host (bound on all interfaces)
    # It goes directly to the web container without
    # ddev-nginx
    ports:
    - 8080:80
COMPOSEEND

# Misc housekeeping before start
ddev config global --router-bind-all-interfaces
# Pass the GITPOD_WORKSPACE_URL env variable to the web container for our setup script
ddev config global --web-environment="MAUTIC_URL=$(gp url 8080),PHPMYADMIN_URL=$(gp url 8036),MAILHOG_URL=$(gp url 8025)"

echo "yes" | ddev start
