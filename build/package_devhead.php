<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/*
 * Build a "production" package from the current development HEAD, this should be run after a 'composer install'
 */

// Step 1 - Remove previous packages
echo "Preparing environment\n";
umask(022);
chdir(__DIR__);
system('rm -rf packaging');
@unlink(__DIR__ . '/packages/mautic-head.zip');

// Step 2 - Provision packaging space
mkdir(__DIR__ . '/packaging');

// Step 3 - Copy files to packaging space
echo "Copying files\n";
system('cp -r ../addons packaging/');
system('cp -r ../app packaging/');
system('cp -r ../assets packaging/');
system('cp -r ../bin packaging/');
system('cp -r ../themes packaging/');
system('cp -r ../vendor packaging/');
system('cp ../.htaccess packaging/');
system('cp ../index.php packaging/');
system('cp ../LICENSE.txt packaging/');
system('cp ../robots.txt packaging/');

// Step 4 - Remove stuff that shouldn't be distro'ed
echo "Removing extra files\n";
system('rm packaging/app/bootstrap*');
system('rm packaging/app/phpunit.*');
system('rm packaging/app/tests.bootstrap*');
system('rm packaging/app/config/config_local.php*');
system('rm packaging/app/config/local.php*');
system('rm -rf packaging/app/cache');
system('rm -rf packaging/app/logs');

// Step 5 - ZIP it up
echo "Packaging Mautic\n";
system('zip -r packages/mautic-head.zip addons/ app/ assets/ bin/ themes/ vendor/ .htaccess index.php LICENSE.txt robots.txt');
