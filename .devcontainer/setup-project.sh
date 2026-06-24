#!/bin/bash
set -e

# Wait for Docker to be ready
wait_for_docker() {
  while true; do
    echo "Waiting for Docker ..."
    docker ps > /dev/null 2>&1 && break
    sleep 1
  done
  echo "Docker is ready."
}

wait_for_docker

cat << 'EOF' > .ddev/config.local.yaml
web_environment:
    - CODESPACES
    - MAUTIC_URL=https://${CODESPACE_NAME}-8443.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}
    - PHPMYADMIN_URL=https://${CODESPACE_NAME}-8036.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}
    - MAILHOG_URL=https://${CODESPACE_NAME}-8025.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}
EOF

cat << 'EOF' > .ddev/docker-compose.phpmyadmin_norouter.yaml
services:
  phpmyadmin:
    ports:
      - 8036:80
EOF

ddev start -y || ddev restart -y
