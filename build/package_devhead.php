<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/*
 * Build a "production" package from the current development HEAD, this should be run after a 'composer install --no-dev --no-scripts --optimize-autoloader'
 * to emulate a proper release package
 */

$baseDir = __DIR__;

// Preparation - Remove previous packages
echo "Preparing environment\n";
umask(022);
chdir($baseDir);
system('rm -rf packaging');
@unlink($baseDir . '/packages/mautic-head.zip');

// Preparation - Provision packaging space
mkdir(__DIR__ . '/packaging');

// Copy working files to packaging space
echo "Copying files\n";
system('cp -r ../addons packaging/');
system('cp -r ../app packaging/');
system('cp -r ../bin packaging/');
system('cp -r ../media packaging/');
system('cp -r ../themes packaging/');
system('cp -r ../translations packaging/');
system('cp -r ../vendor packaging/');
system('cp ../.htaccess packaging/');
system('cp ../index.php packaging/');
system('cp ../LICENSE.txt packaging/');
system('cp ../robots.txt packaging/');
system('cp ../favicon.ico packaging/');

// Generate the bootstrap.php.cache file
system(__DIR__ . '/packaging/vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/Resources/bin/build_bootstrap.php');

// Common steps
include_once __DIR__ . '/processfiles.php';

// Step 5 - ZIP it up
echo "Packaging Mautic\n";
system('zip -r ../packages/mautic-head.zip addons/ app/ bin/ media/ themes/ translations/ vendor/ favicon.ico .htaccess index.php LICENSE.txt robots.txt > /dev/null');
