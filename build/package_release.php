<?php

$criticalMigrations = []; // List of critical migrations

$baseDir = __DIR__;

// Check if the version is in a branch or tag
$args              = getopt('b::', ['repackage']);
$gitSourceLocation = (isset($args['b'])) ? ' ' : ' tags/';

/*
 * Build a release package, this should be run after the new version is tagged; note the tag must match the version string in AppKernel
 * so if the version string is 1.0.0-beta2 then the tag must be 1.0.0-beta2
 */

// We need the version number so get the app kernel
require_once dirname(__DIR__).'/vendor/autoload.php';
require_once dirname(__DIR__).'/app/AppKernel.php';

$releaseMetadata = Mautic\CoreBundle\Release\ThisRelease::getMetadata();
$appVersion      = $releaseMetadata->getVersion();
$minimalVersion  = $releaseMetadata->getMinSupportedMauticVersion();

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

    // set the diff limit to ensure we get all files
    system($systemGit.' config diff.renamelimit 8192');

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

    // Compile prod assets
    system('cd '.__DIR__.'/packaging && npm ci && npx patch-package && php bin/console mautic:assets:generate -e prod', $result);
    if (0 !== $result) {
        exit;
    }

    // Common steps
    include_once __DIR__.'/processfiles.php';

    // In this step, we'll compile a list of files that may have been deleted so our update script can remove them
    // First, get a list of git tags since the minimal version.
    ob_start();
    passthru($systemGit.' for-each-ref --sort=creatordate --format \'%(refname)\' refs/tags | cut -d\/ -f3 | sed \'/-/!{s/$/_/}\' | sort -V | sed \'s/_$//\' | sed -n \'/^'.$minimalVersion.'$/,${p;/^'.$gitSource.'$/q}\' | sed \'$d\'', $tags);
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

    // Ensure the generated media files don't end up in the deleted files by explicitly adding them to the release files.
    foreach (['css', 'js', 'libraries/ckeditor', 'libraries/ckeditor/translations'] as $dir) {
        $files = array_diff(scandir(__DIR__.'/packaging/media/'.$dir), ['..', '.']);
        array_walk($files, function (&$item) use ($dir) { $item = 'media/'.$dir.'/'.$item; });
        $releaseFiles = array_merge($releaseFiles, $files);
    }

    // Create a flag to check if the vendors changed
    $vendorsChanged = false;

    // Get a list of changed files since 1.0.0
    foreach ($tags as $tag) {
        ob_start();
        passthru($systemGit.' diff tags/'.$tag.$gitSourceLocation.$gitSource.' --name-status', $fileDiff);
        $fileDiff = explode("\n", trim(ob_get_clean()));

        foreach ($fileDiff as $fileInfo) {
            [$type, $filename, $newFileName] = explode("\t", $fileInfo."\t");
            $folderPath                      = explode('/', $filename);
            $baseFolderName                  = $folderPath[0];

            if (!$vendorsChanged && 'composer.lock' == $filename) {
                $vendorsChanged = true;
            }

            if ('D' == $type) {
                if (!in_array($filename, $releaseFiles)) {
                    $deletedFiles[$filename] = true;
                }
            } elseif (str_starts_with($type, 'R')) {
                if (!in_array($filename, $releaseFiles)) {
                    $deletedFiles[$filename] = true;
                }
                $modifiedFiles[$newFileName] = true;
            } elseif (in_array($filename, $releaseFiles)) {
                $modifiedFiles[$filename] = true;
            }
        }
    }

    // Include assets just in case they weren't
    $assetFiles = [
        'media/css/'       => true,
        'media/js/'        => true,
        'media/libraries/' => true,
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

    // Paths to vendor directories
    $oldVendorPath = __DIR__.'/mautic-minimum-version/vendor';
    $newVendorPath = __DIR__.'/packaging/vendor';

    // Verify both vendor directories exist
    if (!is_dir($oldVendorPath) || !is_dir($newVendorPath)) {
        echo "Error: Missing vendor directories\n";
        exit(1);
    }

    // Create temp files
    $oldVendorFiles = tempnam(sys_get_temp_dir(), 'old_vendor');
    $newVendorFiles = tempnam(sys_get_temp_dir(), 'new_vendor');

    // Generate file lists from parent directories
    $result = null;

    system(sprintf(
        'cd %s && find vendor -type f -print0 | sort -z | xargs -0 -I{} echo "{}" > %s',
        escapeshellarg(dirname($oldVendorPath)),
        escapeshellarg($oldVendorFiles)
    ), $result);

    if (0 !== $result) {
        echo "Failed to generate vendor file list\n";
        exit(1);
    }

    $result = null;

    system(sprintf(
        'cd %s && find vendor -type f -print0 | sort -z | xargs -0 -I{} echo "{}" > %s',
        escapeshellarg(dirname($newVendorPath)),
        escapeshellarg($newVendorFiles)
    ), $result);

    if (0 !== $result) {
        echo "Failed to generate vendor file list\n";
        exit(1);
    }

    // Compare the lists
    $result = null;

    exec(sprintf('comm -23 %s %s 2>&1',
        escapeshellarg($oldVendorFiles),
        escapeshellarg($newVendorFiles)
    ), $vendorDeletedFiles, $result);

    // Cleanup temp files
    if (file_exists($oldVendorFiles)) {
        unlink($oldVendorFiles);
    }
    if (file_exists($newVendorFiles)) {
        unlink($newVendorFiles);
    }

    // Merge results with existing deletions
    if (0 === $result) {
        $deletedFiles = array_unique(array_merge(
            $deletedFiles,
            array_filter($vendorDeletedFiles, function ($path) {
                return str_starts_with($path, 'vendor/');
            })
        ));
        sort($deletedFiles);
    }

    // Write our files arrays into text files
    file_put_contents(__DIR__.'/packaging/deleted_files.txt', json_encode($deletedFiles));
    file_put_contents(__DIR__.'/packaging/modified_files.txt', implode("\n", $modifiedFiles));
    file_put_contents(__DIR__.'/packaging/critical_migrations.txt', json_encode($criticalMigrations));
}

// Post-processing - ZIP it up
chdir(__DIR__.'/packaging');

system("rm -f ../packages/{$appVersion}.zip ../packages/{$appVersion}-update.zip");

echo "Packaging Mautic Full Installation\n";
system('zip -qr ../packages/'.$appVersion.'.zip . -x@../exclude_files.txt -x@../exclude_files_full.txt');
system('zip -qr ../packages/'.$appVersion.'.zip ./config/.gitkeep');

echo "Packaging Mautic Update Package\n";
system('zip -qr ../packages/'.$appVersion.'-update.zip -x@../exclude_files.txt -@ < modified_files.txt');
system('zip -qr ../packages/'.$appVersion.'-update.zip ./config/.gitkeep');

// Write output to file (so that the CI pipeline can add it to the release notes), then output to console
system('cd ../packages && openssl sha1 '.$appVersion.'.zip > build-sha1-all');
system('cd ../packages && openssl sha1 '.$appVersion.'-update.zip >> build-sha1-all');
system('cat ../packages/build-sha1-all');
