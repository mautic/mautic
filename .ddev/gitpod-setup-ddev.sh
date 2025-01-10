#!/usr/bin/env bash

# Set up ddev for use on gitpod

set -eu -o pipefail

# Misc housekeeping before start
ddev config global --omit-containers=ddev-router

# Pass the GITPOD_WORKSPACE_URL env variable to the web container for our setup script
ddev config global --web-environment="MAUTIC_URL=$(gp url 8080),PHPMYADMIN_URL=$(gp url 8036),MAILHOG_URL=$(gp url 8025)"

# Make phpmyadmin behave with ports statement
# This would normally be done by post_install_hooks
# in ddev/ddev-phpmyadmin
cat <<EOF >.ddev/docker-compose.phpmyadmin_norouter.yaml
services:
  phpmyadmin:
    ports:
      - 8036:80
EOF

ddev start -y
