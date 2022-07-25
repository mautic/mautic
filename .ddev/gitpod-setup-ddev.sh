#!/usr/bin/env bash

# Set up ddev for use on gitpod

set -eu -o pipefail

# Misc housekeeping before start
ddev config global --router-bind-all-interfaces
# Pass the GITPOD_WORKSPACE_URL env variable to the web container for our setup script
ddev config global --web-environment="MAUTIC_URL=$(gp url 8080),PHPMYADMIN_URL=$(gp url 8036),MAILHOG_URL=$(gp url 8025),DRUSH_OPTIONS_URI=https://127.0.0.1:8080"

ddev start -y
