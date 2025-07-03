<?php

$baseDir = __DIR__;

/*
 * Build a "production" package from the current development HEAD, this should be run after a 'composer install --no-dev --no-scripts --optimize-autoloader'
 * to emulate a proper release package
 */

// Preparation - Remove previous packages
echo "Preparing environment\n";
umask(022);
chdir($baseDir);
system('rm -rf packaging');
@unlink($baseDir.'/packages/mautic-head.zip');

// Preparation - Provision packaging space
mkdir(__DIR__.'/packaging');

// Copy working files to packaging space
echo "Copying files\n";
system("rsync -az --exclude-from 'excludefiles.txt' ../ packaging > /dev/null");

// Common steps
include_once __DIR__.'/processfiles.php';

// Step 5 - ZIP it up
echo "Packaging Mautic\n";
chdir(__DIR__.'/packaging');

system('zip -r ../packages/mautic-head.zip . > /dev/null');

chdir(__DIR__.'/packaging');
echo "Packaging Mautic Update Package\n";
system('zip -r ../packages/mautic-head-update.zip . > /dev/null');
