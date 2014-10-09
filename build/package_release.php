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

// We need the version number so get the app kernel
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/app/AppKernel.php';
$version = AppKernel::MAJOR_VERSION . '.' . AppKernel::MINOR_VERSION . '.' . AppKernel::PATCH_VERSION . AppKernel::EXTRA_VERSION;

// Preparation - Remove previous packages
echo "Preparing environment\n";
umask(022);
chdir(__DIR__);
system('rm -rf packaging');
@unlink(__DIR__ . '/packages/mautic-' . $version . '.zip');

// Preparation - Provision packaging space
mkdir(__DIR__ . '/packaging');

// Common steps
include_once __DIR__ . '/processfiles.php';

// Uncomment this at 1.0.1
/*// In this step, we'll compile a list of files that may have been deleted so our update script can remove them
ob_start();
passthru('which git', $systemGit);
$systemGit = trim(ob_get_clean());

// First, get a list of git tags
ob_start();
passthru($systemGit . ' tag -l', $tags);
$tags = explode("\n", trim(ob_get_clean()));

// Get the list of modified files from the initial tag
ob_start();
passthru($systemGit . ' diff tags/' . $tags[0] . ' tags/' . $version . ' --name-status', $fileDiff);
$fileDiff = explode("\n", trim(ob_get_clean()));

// Only add deleted files to our list; new and modified files will be covered by the archive
$deletedFiles = array();

foreach ($fileDiff as $file)
{
	if (substr($file, 0, 1) == 'D')
	{
		$deletedFiles[] = substr($file, 2);
	}
}*/

// Post-processing - ZIP it up
echo "Packaging Mautic\n";
system('zip -r ../packages/mautic-' . $version . '.zip addons/ app/ assets/ bin/ themes/ vendor/ .htaccess index.php LICENSE.txt robots.txt > /dev/null');
