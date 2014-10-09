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

$baseDir = __DIR__;

// Preparation - Remove previous packages
echo "Preparing environment\n";
umask(022);
chdir($baseDir);
system('rm -rf packaging');
@unlink($baseDir . '/packages/mautic-head.zip');

// Preparation - Provision packaging space
mkdir(__DIR__ . '/packaging');

// Common steps
include_once __DIR__ . '/processfiles.php';

// Step 5 - ZIP it up
echo "Packaging Mautic\n";
system('zip -r ../packages/mautic-head.zip addons/ app/ assets/ bin/ themes/ vendor/ .htaccess index.php LICENSE.txt robots.txt > /dev/null');
