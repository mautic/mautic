<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @see        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/*
 * Common file for preparing an installation package
 */

// Step 4 - Remove stuff that shouldn't be distro'ed
echo "Removing extra files\n";
chdir($baseDir.'/packaging');

system('rm -f app/phpunit.*');
system('rm -f app/tests.bootstrap*');
system('find app/bundles/*/Tests/* ! -path "*/Tests/DataFixtures*" -prune -exec rm -rf {} \\;');
system('rm -rf app/bundles/CoreBundle/Test');
system('rm -rf app/cache/*');
system('rm -rf app/logs/*');
system('rm -rf var/cache/*');
system('rm -rf var/logs/*');
system('rm -rf var/spool/*');
system('rm -rf var/tmp/*');
system('rm -rf media/files/*');
// Delete ElFinder's (filemanager) assets
system('rm -rf media/assets/');
system('rm -f app/config/config_dev.php');
system('rm -f app/config/config_test.php');
system('rm -f app/config/local*.php');
system('rm -f app/config/routing_dev.php');
system('rm -f app/config/security_test.php');

// Delete random files
system('find . -type f -name phpunit.xml -exec rm -f {} \\;');
system('find . -type f -name phpunit.xml.dist -exec rm -f {} \\;');
system('find . -type f -name .travis.yml -exec rm -f {} \\;');
system('find . -type f -name .hgtags -exec rm -f {} \\;');
system('find . -type f -name .coveralls.yml -exec rm -f {} \\;');
system('find . -type f -name build.properties -exec rm -f {} \\;');
system('find . -type f -name build.xml -exec rm -f {} \\;');
system('find . -type f -name Gruntfile.js -exec rm -f {} \\;');

// Find git special files
system('find . -name ".git*" -prune -exec rm -rf {} \\;');

// Find any .DS_Store files and nuke them
system('find . -name .DS_Store -exec rm -rf {} \\;');
