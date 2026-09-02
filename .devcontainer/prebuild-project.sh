#!/bin/bash
set -e

curl -fsSL https://ddev.com/install.sh | bash
ddev start -y --skip-hooks
ddev composer install --no-interaction
ddev poweroff
