#!/bin/sh

# Run this script from project root using `./scripts/git-stage-update.sh`

# Run the commands via git to update to most recent master release.

# Mautic Origin: https://github.com/mautic/mautic
MAUTICORIGIN='git remote get-url mautic'
if [ -n "$MAUTICORIGIN" ];
  then
    MAUTICORIGIN='git@github.com:mautic/mautic.git'
    git remote add "$MAUTICORIGIN"
    echo 'Mautic origin added'
  else
    echo 'Mautic origin exists'
fi

# Get all Updates
git fetch --all --tags

# Merge into staging instead of master so you can test the release
git checkout staging
git pull origin staging
git merge --commit --no-ff -X theirs mautic/master
git push origin staging
