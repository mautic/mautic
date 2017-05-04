#!/bin/sh

# Run this script from project root using `./scripts/git-finalize-update.sh`

# Run the commands via git to merge most recent staged release to master branch

# Get all Updates
git fetch --all --tags

# Merge into staging instead of master so you can test the release
git checkout master
git pull origin master
git merge --commit --no-ff staging
git push origin master
