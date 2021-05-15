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
 * Build a release package, this should be run after the new version is tagged; note the tag must match the version string in AppKernel
 * so if the version string is 1.0.0-beta2 then the tag must be 1.0.0-beta2
 */

// List of critical migrations
$criticalMigrations = [];

$baseDir = __DIR__;

// Check if the version is in a branch or tag
$args              = getopt('b::', ['repackage']);
$gitSourceLocation = (isset($args['b'])) ? ' ' : ' tags/';

// We need the version number so get the app kernel
require_once dirname(__DIR__).'/vendor/autoload.php';
require_once dirname(__DIR__).'/app/AppKernel.php';

$releaseMetadata = \Mautic\CoreBundle\Release\ThisRelease::getMetadata();
$appVersion      = $releaseMetadata->getVersion();

// Use branch if applicable otherwise a version tag
$gitSource = (!empty($args['b'])) ? $args['b'] : $appVersion;

if (!isset($args['repackage'])) {
    // Preparation - Remove previous packages
    echo "Preparing environment\n";
    umask(022);
    chdir(__DIR__);

    system('rm -rf packaging');

    // Preparation - Provision packaging space
    mkdir(__DIR__.'/packaging');

    // Grab the system git path so we can process git commands
    ob_start();
    passthru('which git', $systemGit);
    $systemGit = trim(ob_get_clean());
    // Checkout the version tag into the packaging space
    chdir(dirname(__DIR__));
    system($systemGit.' archive '.$gitSource.' | tar -x -C '.__DIR__.'/packaging', $result);

    // Get a list of all files in this release
    ob_start();
    passthru($systemGit.' ls-tree -r -t --name-only '.$gitSource, $releaseFiles);
    $releaseFiles = explode("\n", trim(ob_get_clean()));

    if (0 !== $result) {
        exit;
    }

    chdir(__DIR__);
    system('cd '.__DIR__.'/packaging && composer install --no-dev --no-scripts --optimize-autoloader && cd ..', $result);
    if (0 !== $result) {
        exit;
    }

    // Generate the bootstrap.php.cache file
    system(__DIR__.'/packaging/vendor/sensio/distribution-bundle/Resources/bin/build_bootstrap.php', $result);
    if (0 !== $result) {
        exit;
    }

    // Compile prod assets
    system('cd '.__DIR__.'/packaging && php '.__DIR__.'/packaging/bin/console mautic:assets:generate -e prod', $result);
    if (0 !== $result) {
        exit;
    }

    // Common steps
    include_once __DIR__.'/processfiles.php';

    // In this step, we'll compile a list of files that may have been deleted so our update script can remove them
    // First, get a list of git tags
    ob_start();
    passthru($systemGit.' tag -l', $tags);
    $tags = explode("\n", trim(ob_get_clean()));

    // Only add deleted files to our list; new and modified files will be covered by the archive
    $deletedFiles  = [];
    $modifiedFiles = [
        'deleted_files.txt'              => true,
        'critical_migrations.txt'        => true,
        'upgrade.php'                    => true,
        // Temp fix for GrapesJs builder
        'plugins/GrapesJsBuilderBundle/' => true,
    ];

    // Create a flag to check if the vendors changed
    $vendorsChanged = false;

    // Get a list of changed files since 1.0.0
    foreach ($tags as $tag) {
        ob_start();
        passthru($systemGit.' diff tags/'.$tag.$gitSourceLocation.$gitSource.' --name-status', $fileDiff);
        $fileDiff = explode("\n", trim(ob_get_clean()));

        foreach ($fileDiff as $file) {
            $filename       = substr($file, 2);
            $folderPath     = explode('/', $filename);
            $baseFolderName = $folderPath[0];

            if (!$vendorsChanged && 'composer.lock' == $filename) {
                $vendorsChanged = true;
            }

            if ('D' == substr($file, 0, 1)) {
                if (!in_array($filename, $releaseFiles)) {
                    $deletedFiles[$filename] = true;
                }
            } elseif (in_array($filename, $releaseFiles)) {
                $modifiedFiles[$filename] = true;
            }
        }
    }

    // Include assets just in case they weren't
    $assetFiles = [
        'media/css/app.css'       => true,
        'media/css/libraries.css' => true,
        'media/js/app.js'         => true,
        'media/js/libraries.js'   => true,
        'media/js/mautic-form.js' => true,
    ];
    $modifiedFiles = $modifiedFiles + $assetFiles;

    // Package the vendor folder if the lock changed
    if ($vendorsChanged) {
        $modifiedFiles['vendor/']                 = true;
        $modifiedFiles['app/bootstrap.php.cache'] = true;
    }

    $modifiedFiles = array_keys($modifiedFiles);
    sort($modifiedFiles);

    $deletedFiles = array_keys($deletedFiles);
    sort($deletedFiles);

    // Write our files arrays into text files
    file_put_contents(__DIR__.'/packaging/deleted_files.txt', json_encode($deletedFiles));
    file_put_contents(__DIR__.'/packaging/modified_files.txt', implode("\n", $modifiedFiles));
    file_put_contents(__DIR__.'/packaging/critical_migrations.txt', json_encode($criticalMigrations));
}

// Post-processing - ZIP it up
chdir(__DIR__.'/packaging');

system("rm -f ../packages/{$appVersion}.zip ../packages/{$appVersion}-update.zip");

echo "Packaging Mautic Full Installation\n";
system('zip -r ../packages/'.$appVersion.'.zip . -x@../exclude_files.txt -x@../exclude_files_full.txt > /dev/null');

echo "Packaging Mautic Update Package\n";
system('zip -r ../packages/'.$appVersion.'-update.zip -x@../exclude_files.txt -@ < modified_files.txt > /dev/null');

// Write output to file (so that the CI pipeline can add it to the release notes), then output to console
system('cd ../packages && openssl sha1 '.$appVersion.'.zip > build-sha1-all');
system('cd ../packages && openssl sha1 '.$appVersion.'-update.zip >> build-sha1-all');
system('cat ../packages/build-sha1-all');
