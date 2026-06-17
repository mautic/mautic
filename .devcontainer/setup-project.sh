#!/bin/bash
set -euo pipefail

# Wait for Docker to be ready
wait_for_docker() {
  local timeout_seconds=300
  local start_time
  start_time=$(date +%s)

  while true; do
    docker ps > /dev/null 2>&1 && break
    if (( $(date +%s) - start_time > timeout_seconds )); then
      echo "Timed out waiting for Docker after ${timeout_seconds}s" >&2
      exit 1
    fi
    sleep 1
  done
  echo "Docker is ready."
}

wait_for_docker

# This file is called in three scenarios:
# 1. fresh creation of devcontainer
# 2. rebuild
# 3. full rebuild
#
# `ddev debug download-images` is intentionally disabled by default because
# it aggressively preloads images and significantly increases Codespaces
# bootstrap time. Enable only when explicitly requested:
#   DDEV_PRELOAD_IMAGES=1 .devcontainer/setup-project.sh
if [[ "${DDEV_PRELOAD_IMAGES:-0}" == "1" ]]; then
  ddev debug download-images
fi

ddev -v

if [[ -n "${CODESPACE_NAME:-}" && -n "${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-}" ]]; then
  ddev config global --web-environment="MAUTIC_URL=https://${CODESPACE_NAME}-80.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN},PHPMYADMIN_URL=https://${CODESPACE_NAME}-8036.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN},MAILHOG_URL=https://${CODESPACE_NAME}-8025.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}"
fi

mkdir -p .ddev
cat <<EOF > .ddev/docker-compose.phpmyadmin_norouter.yaml
services:
  phpmyadmin:
    # Keep phpMyAdmin optional; enable with DDEV_START_PROFILES=tools
    # (or include tools in a comma-separated profile list).
    profiles:
      - tools
    ports:
      - 8036:80
EOF

# Start only when needed to avoid unnecessary restarts on rebuild flows.
if ! ddev describe 2>/dev/null | grep -q "OK"; then
  start_args=(-y)
  if [[ -n "${DDEV_START_PROFILES:-}" ]]; then
    start_args+=(--profiles "${DDEV_START_PROFILES}")
  fi
  ddev start "${start_args[@]}"
else
  echo "DDEV project already running; skipping ddev start."
fi