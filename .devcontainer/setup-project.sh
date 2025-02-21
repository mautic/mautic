#!/bin/bash
set -ex

# Wait for Docker to be ready
wait_for_docker() {
  while true; do
    docker ps > /dev/null 2>&1 && break
    sleep 1
  done
  echo "Docker is ready."
}

wait_for_docker

# This file is called in three scenarios:
# 1. fresh creation of devcontainer
# 2. rebuild
# 3. full rebuild

ddev debug download-images

ddev poweroff

ddev -v

ddev config global --web-environment="MAUTIC_URL=https://${CODESPACE_NAME}-80.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN},PHPMYADMIN_URL=https://${CODESPACE_NAME}-8036.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN},MAILHOG_URL=https://${CODESPACE_NAME}-8025.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}"

cat <<EOF >.ddev/docker-compose.phpmyadmin_norouter.yaml
services:
  phpmyadmin:
    ports:
      - 8036:80
EOF

ddev start -y