<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/*
 * Boot our upgrade app
 */

// This script will always be run from an upgrade folder under the app root
define('MAUTIC_ROOT',              dirname(__DIR__));
define('MAUTIC_UPGRADE_ROOT',      __DIR__);
define('MAUTIC_UPGRADE_ERROR_LOG', MAUTIC_ROOT . '/upgrade_errors.txt');

// Get the local config file location
/** @var $paths */
$root = MAUTIC_ROOT . '/app';
include "$root/config/paths.php";

// Include local config to get cache_path
$localConfig = str_replace('%kernel.root_dir%', $root, $paths['local_config']);

/** @var $parameters */
include $localConfig;

$localParameters = $parameters;

//check for parameter overrides
if (file_exists("$root/config/parameters_local.php")) {
    /** @var $parameters */
    include "$root/config/parameters_local.php";
    $localParameters = array_merge($localParameters, $parameters);
}

if (isset($localParameters['cache_path'])) {
    $cacheDir = str_replace('%kernel.root_dir%', $root, $localParameters['cache_path'] . '/prod');
} else {
    $cacheDir = "$root/cache/prod";
}

define('MAUTIC_CACHE_DIR', $cacheDir);

/**
 * Clears the application cache
 *
 * Since this script is being executed via web requests and standalone from the Mautic application, we don't have access to Symfony's
 * CLI suite.  So we'll go with Option B in this instance and just nuke the entire production cache and let Symfony rebuild it on the next
 * application cycle.
 *
 * @param array $status
 *
 * @return array
 */
function clear_mautic_cache(array $status)
{
    if (!recursive_remove_directory(MAUTIC_CACHE_DIR)) {
        process_error_log(array('Could not remove the application cache.  You will need to manually delete ' . MAUTIC_CACHE_DIR . '.'));
    }

    //Remove the cached update

    $status['complete']                     = true;
    $status['stepStatus']                   = 'Success';
    $status['nextStep']                     = 'Processing Database Updates';
    $status['nextStepStatus']               = 'In Progress';
    $status['updateState']['cacheComplete'] = true;

    return $status;
}

/**
 * Copy a folder.
 *
 * This function is based on \Joomla\Filesystem\Folder:copy()

 * @param string $src  The path to the source folder.
 * @param string $dest The path to the destination folder.
 *
 * @return array|string|boolean  True on success, a single error message on a "boot" fail, or an array of errors from the recursive operation
 */
function copy_directory($src, $dest)
{
    @set_time_limit(ini_get('max_execution_time'));
    $errorLog = array();

    // Eliminate trailing directory separators, if any
    $src  = rtrim($src, DIRECTORY_SEPARATOR);
    $dest = rtrim($dest, DIRECTORY_SEPARATOR);

    // Make sure the destination exists
    if (!is_dir($dest)) {
        if (!@mkdir($dest, 0755)) {
            return sprintf('Could not move files from %s to production since the folder could not be created.', str_replace(MAUTIC_UPGRADE_ROOT, '', $src));
        }
    }

    if (!($dh = @opendir($src))) {
        return sprintf('Could not read directory %s to move files.', str_replace(MAUTIC_UPGRADE_ROOT, '', $src));
    }

    // Walk through the directory copying files and recursing into folders.
    while (($file = readdir($dh)) !== false) {
        $sfid = $src . '/' . $file;
        $dfid = $dest . '/' . $file;

        switch (filetype($sfid)) {
            case 'dir':
                if ($file != '.' && $file != '..') {
                    $ret = copy_directory($sfid, $dfid);

                    if ($ret !== true) {
                        if (is_array($ret)) {
                            $errorLog += $ret;
                        } else {
                            $errorLog[] = $ret;
                        }
                    }
                }
                break;

            case 'file':
                if (!@rename($sfid, $dfid)) {
                    $errorLog[] = sprintf('Could not move file %s to production.', str_replace(MAUTIC_UPGRADE_ROOT, '', $sfid));
                }
                break;
        }
    }

    if (!empty($errorLog)) {
        return $errorLog;
    }

    return true;
}

/**
 * Fetches a request variable and returns the sanitized version of it
 *
 * @param string $name
 * @param string $default
 * @param int    $filter
 *
 * @return mixed|string
 */
function getVar($name, $default = '', $filter = FILTER_SANITIZE_STRING)
{
    if (isset($_REQUEST[$name]))
    {
        return filter_var($_REQUEST[$name], $filter);
    }

    return $default;
}

/**
 * Moves the Mautic bundles from the upgrade directory to production
 *
 * A typical update package will only include changed files in the bundles.  However, in this script we will assume that all of
 * the bundle resources are included here and recursively iterate over the bundles in batches to update the filesystem.
 *
 * @param array $status
 *
 * @return array
 */
function move_mautic_bundles(array $status)
{
    $errorLog = array();

    // First, we will move any addon bundles into position
    if (is_dir(MAUTIC_UPGRADE_ROOT . '/addons') && !$status['updateState']['addonComplete']) {
        $iterator = new DirectoryIterator(MAUTIC_UPGRADE_ROOT . '/addons');

        // Sanity check, make sure there are actually directories here to process
        $dirs = glob(MAUTIC_UPGRADE_ROOT . '/addons/*', GLOB_ONLYDIR);

        if (count($dirs)) {
            /** @var DirectoryIterator $directory */
            foreach ($iterator as $directory) {
                // Sanity checks
                if (!$directory->isDot() && $directory->isDir()) {
                    $src  = $directory->getPath() . '/' . $directory->getFilename();
                    $dest = str_replace(MAUTIC_UPGRADE_ROOT, MAUTIC_ROOT, $src);

                    $result = copy_directory($src, $dest);

                    if ($result !== true) {
                        if (is_array($result)) {
                            $errorLog += $result;
                        } else {
                            $errorLog[] = $result;
                        }
                    }

                    $deleteDir = recursive_remove_directory($src);

                    if (!$deleteDir) {
                        $errorLog[] = sprintf('Failed to remove the upgrade directory %s folder', str_replace(MAUTIC_UPGRADE_ROOT, '', $src));
                    }
                }
            }
        }

        // At this point, there shouldn't be any addons remaining; nuke the folder
        $deleteDir = recursive_remove_directory(MAUTIC_UPGRADE_ROOT . '/addons');

        if (!$deleteDir) {
            $errorLog[] = sprintf('Failed to remove the upgrade directory %s folder', '/addons');
        }

        process_error_log($errorLog);

        $status['updateState']['addonComplete'] = true;

        // Finished with addons, get a response back to the app so we can iterate to the next part
        return $status;
    }

    // Now we move the main app bundles into production
    if (is_dir(MAUTIC_UPGRADE_ROOT . '/app/bundles') && !$status['updateState']['bundleComplete']) {
        // Initialize the bundle state if it isn't
        if (!isset($status['updateState']['completedBundles'])) {
            $status['updateState']['completedBundles'] = array();
        }

        $completed = true;
        $iterator  = new DirectoryIterator(MAUTIC_UPGRADE_ROOT . '/app/bundles');

        // Sanity check, make sure there are actually directories here to process
        $dirs = glob(MAUTIC_UPGRADE_ROOT . '/app/bundles/*', GLOB_ONLYDIR);

        if (count($dirs)) {
            $count = 0;

            /** @var DirectoryIterator $directory */
            foreach ($iterator as $directory) {
                // Exit the loop if the count has reached 5
                if ($count === 5) {
                    $completed = false;
                    break;
                }

                // Sanity checks
                if (!$directory->isDot() && $directory->isDir()) {
                    // Don't process this bundle if we've already tried it
                    if (isset($status['updateState']['completedBundles'][$directory->getFilename()])) {
                        continue;
                    }

                    $src  = $directory->getPath() . '/' . $directory->getFilename();
                    $dest = str_replace(MAUTIC_UPGRADE_ROOT, MAUTIC_ROOT, $src);

                    $result = copy_directory($src, $dest);

                    if ($result !== true) {
                        if (is_array($result)) {
                            $errorLog += $result;
                        } else {
                            $errorLog[] = $result;
                        }
                    }

                    $deleteDir = recursive_remove_directory($src);

                    if (!$deleteDir) {
                        $errorLog[] = sprintf('Failed to remove the upgrade directory %s folder', str_replace(MAUTIC_UPGRADE_ROOT, '', $src));
                    }

                    $status['updateState']['completedBundles'][$directory->getFilename()] = true;
                    $count++;
                }
            }
        }

        if ($completed) {
            $status['updateState']['bundleComplete'] = true;

            // At this point, there shouldn't be any bundles remaining; nuke the folder
            $deleteDir = recursive_remove_directory(MAUTIC_UPGRADE_ROOT . '/app/bundles');

            if (!$deleteDir) {
                $errorLog[] = sprintf('Failed to remove the upgrade directory %s folder', '/app/bundles');
            }
        }

        process_error_log($errorLog);

        // If we haven't finished the bundles yet, throw a response back to repeat the step
        if (!$status['updateState']['bundleComplete']) {
            return $status;
        }
    }

    // To get here, all of the bundle updates must have been processed (or there are literally none).  Step complete.
    $status['complete'] = true;

    return $status;
}

/**
 * Moves the Mautic core files that are not part of bundles or vendors into production
 *
 * The "core" files are broken into groups for purposes of the update script: bundles, vendor, and everything else.  This step
 * will take care of the everything else.
 *
 * @param array $status
 *
 * @return array
 */
function move_mautic_core(array $status)
{
    $errorLog = array();

    // Single level directories with files only
    $fileOnlyDirectories = array(
        '/app/config',
        '/app/migrations',
        '/app',
        '/bin'
    );

    foreach ($fileOnlyDirectories as $dir) {
        if (copy_files($dir, $errorLog)) {

            // At this point, we can remove the config directory
            $deleteDir = recursive_remove_directory(MAUTIC_UPGRADE_ROOT . $dir);

            if (!$deleteDir) {
                $errorLog[] = sprintf('Failed to remove the upgrade directory %s folder', $dir);
            }
        }
    }

    // Multilevel directories
    $nestedDirectories = array(
        '/media',
        '/themes',
        '/translations'
    );

    foreach ($nestedDirectories as $dir) {
        if (is_dir(MAUTIC_UPGRADE_ROOT . $dir)) {

            copy_directories($dir, $errorLog);

            // At this point, we can remove the media directory
            $deleteDir = recursive_remove_directory(MAUTIC_UPGRADE_ROOT . $dir);

            if (!$deleteDir) {
                $errorLog[] = sprintf('Failed to remove the upgrade directory %s folder', $dir);
            }
        }
    }

    // Now move any root level files
    $iterator = new FilesystemIterator(MAUTIC_UPGRADE_ROOT);

    /** @var FilesystemIterator $file */
    foreach ($iterator as $file) {
        // Sanity checks
        if ($file->isFile() && !in_array($file->getFilename(), array('deleted_files.txt', 'upgrade.php'))) {
            $src  = $file->getPath() . '/' . $file->getFilename();
            $dest = str_replace(MAUTIC_UPGRADE_ROOT, MAUTIC_ROOT, $src);

            if (!@rename($src, $dest)) {
                $errorLog[] = sprintf('Could not move file %s to production.', str_replace(MAUTIC_UPGRADE_ROOT, '', $src));
            }
        }
    }

    process_error_log($errorLog);

    // In this step, we'll also go ahead and remove deleted files, return the results from that
    return remove_mautic_deleted_files($status);
}

/**
 * Moves the Mautic dependencies from the upgrade directory to production
 *
 * Since the /vendor folder is not stored under version control, we cannot accurately track changes in third party dependencies
 * between releases.  Therefore, this step will recursively iterate over the vendors in batches to remove each package completely
 * and replace it with the new version.
 *
 * @param array $status
 *
 * @return array
 */
function move_mautic_vendors(array $status)
{
    $errorLog = array();

    // If there isn't even a vendor directory, just skip this step
    if (!is_dir(MAUTIC_UPGRADE_ROOT . '/vendor')) {
        $status['complete']                      = true;
        $status['stepStatus']                    = 'Success';
        $status['nextStep']                      = 'Clearing Application Cache';
        $status['nextStepStatus']                = 'In Progress';
        $status['updateState']['vendorComplete'] = true;

        return $status;
    }

    // Initialize the vendor state if it isn't
    if (!isset($status['updateState']['completedVendors'])) {
        $status['updateState']['completedVendors'] = array();
    }

    // Symfony is the largest of our vendors, we will process it first
    if (is_dir(MAUTIC_UPGRADE_ROOT . '/vendor/symfony') && !isset($status['updateState']['completedVendors']['symfony'])) {
        // Initialize the Symfony state if it isn't, this step will recurse
        if (!isset($status['updateState']['completedSymfony'])) {
            $status['updateState']['completedSymfony'] = array();
        }

        $completed = true;
        $iterator  = new DirectoryIterator(MAUTIC_UPGRADE_ROOT . '/vendor/symfony');

        // Sanity check, make sure there are actually directories here to process
        $dirs = glob(MAUTIC_UPGRADE_ROOT . '/vendor/symfony/*', GLOB_ONLYDIR);

        if (count($dirs)) {
            $count = 0;

            /** @var DirectoryIterator $directory */
            foreach ($iterator as $directory) {
                // Exit the loop if the count has reached 5
                if ($count === 5) {
                    $completed = false;
                    break;
                }

                // Sanity checks
                if (!$directory->isDot() && $directory->isDir()) {
                    // Don't process this directory if we've already tried it
                    if (isset($status['updateState']['completedSymfony'][$directory->getFilename()])) {
                        continue;
                    }

                    $src  = $directory->getPath() . '/' . $directory->getFilename();
                    $dest = str_replace(MAUTIC_UPGRADE_ROOT, MAUTIC_ROOT, $src);

                    // We'll need to completely remove the existing vendor first
                    recursive_remove_directory($dest);

                    $result = copy_directory($src, $dest);

                    if ($result !== true) {
                        if (is_array($result)) {
                            $errorLog += $result;
                        } else {
                            $errorLog[] = $result;
                        }
                    }

                    $deleteDir = recursive_remove_directory($src);

                    if (!$deleteDir) {
                        $errorLog[] = sprintf('Failed to remove the upgrade directory %s folder', str_replace(MAUTIC_UPGRADE_ROOT, '', $src));
                    }

                    $status['updateState']['completedSymfony'][$directory->getFilename()] = true;
                    $count++;
                }
            }
        }

        if ($completed) {
            $status['updateState']['completedVendors']['symfony'] = true;

            // At this point, there shouldn't be any Symfony code remaining; nuke the folder
            $deleteDir = recursive_remove_directory(MAUTIC_UPGRADE_ROOT . '/vendor/symfony');

            if (!$deleteDir) {
                $errorLog[] = sprintf('Failed to remove the upgrade directory %s folder', '/vendor/symfony');
            }
        }

        process_error_log($errorLog);

        // If we haven't finished Symfony yet, throw a response back to repeat the step
        if (!isset($status['updateState']['completedVendors']['symfony'])) {
            return $status;
        }
    }

    // Once we've gotten here, we can safely iterate through the rest of the vendor directory; the rest of the contents are rather small in size
    $completed = true;
    $iterator  = new DirectoryIterator(MAUTIC_UPGRADE_ROOT . '/vendor');

    // Sanity check, make sure there are actually directories here to process
    $dirs = glob(MAUTIC_UPGRADE_ROOT . '/vendor/*', GLOB_ONLYDIR);

    if (count($dirs)) {
        $count = 0;

        /** @var DirectoryIterator $directory */
        foreach ($iterator as $directory) {
            // Exit the loop if the count has reached 5
            if ($count === 5) {
                $completed = false;
                break;
            }

            // Sanity checks
            if (!$directory->isDot() && $directory->isDir()) {
                // Don't process this directory if we've already tried it
                if (isset($status['updateState']['completedVendors'][$directory->getFilename()])) {
                    continue;
                }

                $src  = $directory->getPath() . '/' . $directory->getFilename();
                $dest = str_replace(MAUTIC_UPGRADE_ROOT, MAUTIC_ROOT, $src);

                // We'll need to completely remove the existing vendor first
                recursive_remove_directory($dest);

                $result = copy_directory($src, $dest);

                if ($result !== true) {
                    if (is_array($result)) {
                        $errorLog += $result;
                    } else {
                        $errorLog[] = $result;
                    }
                }

                $deleteDir = recursive_remove_directory($src);

                if (!$deleteDir) {
                    $errorLog[] = sprintf('Failed to remove the upgrade directory %s folder', str_replace(MAUTIC_UPGRADE_ROOT, '', $src));
                }

                $status['updateState']['completedVendors'][$directory->getFilename()] = true;
                $count++;
            }
        }
    }

    if ($completed) {
        $status['updateState']['vendorComplete'] = true;

        // Move the autoload.php file over now
        if (!@rename(MAUTIC_UPGRADE_ROOT . '/vendor/autoload.php', MAUTIC_ROOT . '/vendor/autoload.php')) {
            $errorLog[] = 'Could not move file /vendor/autoload.php to production.';
        }

        // At this point, there shouldn't be any vendors remaining; nuke the folder
        $deleteDir = recursive_remove_directory(MAUTIC_UPGRADE_ROOT . '/vendor');

        if (!$deleteDir) {
            $errorLog[] = sprintf('Failed to remove the upgrade directory %s folder', '/vendor');
        }
    }

    process_error_log($errorLog);

    // If we haven't finished the vendors yet, throw a response back to repeat the step
    if (!$status['updateState']['vendorComplete']) {
        return $status;
    }

    // Once we get here, we have finished the moving files step; notifiy Mautic of this
    $status['complete']                      = true;
    $status['stepStatus']                    = 'Success';
    $status['nextStep']                      = 'Clearing Application Cache';
    $status['nextStepStatus']                = 'In Progress';
    $status['updateState']['vendorComplete'] = true;

    return $status;
}

/**
 * Copy files from the directory
 *
 * @param string $dir
 * @param array  &$errorLog
 *
 * @return bool
 */
function copy_files($dir, &$errorLog) {
    if (is_dir(MAUTIC_UPGRADE_ROOT . $dir)) {
        $iterator = new FilesystemIterator(MAUTIC_UPGRADE_ROOT . $dir);

        /** @var FilesystemIterator $file */
        foreach ($iterator as $file) {
            // Sanity checks
            if ($file->isFile()) {
                $src  = $file->getPath() . '/' . $file->getFilename();
                $dest = str_replace(MAUTIC_UPGRADE_ROOT, MAUTIC_ROOT, $src);

                if (!@rename($src, $dest)) {
                    $errorLog[] = sprintf('Could not move file %s to production.', str_replace(MAUTIC_UPGRADE_ROOT, '', $src));
                }
            }
        }

        return true;
    }

    return false;
}

/**
 * Copy directories
 *
 * @param string $dir
 * @param array  &$errorLog
 * @param bool   $createDest
 *
 * @return bool|void
 */
function copy_directories($dir, &$errorLog, $createDest = true) {
    // Ensure the destination directory exists
    $exists = file_exists(MAUTIC_ROOT . $dir);
    if ($createDest && !$exists) {
        mkdir(MAUTIC_ROOT . $dir, 0755);
    } elseif (!$exists) {
        $errorLog[] = sprintf('%s does not exist.', MAUTIC_ROOT . $dir);
        return false;
    }

    // Copy root level files first
    copy_files($dir, $errorLog);

    $iterator = new DirectoryIterator(MAUTIC_UPGRADE_ROOT . $dir);

    /** @var DirectoryIterator $directory */
    foreach ($iterator as $directory) {
        // Sanity checks
        if (!$directory->isDot() && $directory->isDir()) {
            $src  = $directory->getPath() . '/' . $directory->getFilename();
            $dest = str_replace(MAUTIC_UPGRADE_ROOT, MAUTIC_ROOT, $src);

            $result = copy_directory($src, $dest);

            if ($result !== true) {
                if (is_array($result)) {
                    $errorLog += $result;
                } else {
                    $errorLog[] = $result;
                }
            }

            $deleteDir = recursive_remove_directory($src);

            if (!$deleteDir) {
                $errorLog[] = sprintf('Failed to remove the upgrade directory %s folder', str_replace(MAUTIC_UPGRADE_ROOT, '', $src));
            }
        }
    }
}

/**
 * Processes the error log for each step
 *
 * @param array $errorLog
 *
 * @return void
 */
function process_error_log(array $errorLog)
{
    // If there were any errors, add them to the error log
    if (count($errorLog)) {
        // Check if the error log exists first
        if (file_exists(MAUTIC_UPGRADE_ERROR_LOG)) {
            $errors = file_get_contents(MAUTIC_UPGRADE_ERROR_LOG);
        } else {
            $errors = '';
        }

        $errors .= implode(PHP_EOL, $errorLog);

        @file_put_contents(MAUTIC_UPGRADE_ERROR_LOG, $errors);
    }
}

/**
 * Tries to recursively delete a directory
 *
 * This code is based on the recursive_remove_directory function used by Akeeba Restore
 *
 * @param string $directory
 *
 * @return bool
 */
function recursive_remove_directory($directory)
{
    // if the path has a slash at the end we remove it here
    if (substr($directory, -1) == '/') {
        $directory = substr($directory, 0, -1);
    }

    // if the path is not valid or is not a directory ...
    if (!file_exists($directory) || !is_dir($directory)) {
        // ... we return false and exit the function
        return false;
        // ... if the path is not readable
    } elseif (!is_readable($directory)) {
        // ... we return false and exit the function
        return false;
        // ... else if the path is readable
    } else {
        // we open the directory
        $handle   = opendir($directory);

        // and scan through the items inside
        while (false !== ($item = readdir($handle))) {
            // if the filepointer is not the current directory
            // or the parent directory
            if ($item != '.' && $item != '..') {
                // we build the new path to delete
                $path = $directory . '/' . $item;
                // if the new path is a directory
                if (is_dir($path)) {
                    // we call this function with the new path
                    recursive_remove_directory($path);
                    // if the new path is a file
                } else {
                    // we remove the file
                    @unlink($path);
                }
            }
        }

        // close the directory
        closedir($handle);

        // try to delete the now empty directory
        if (!@rmdir($directory)) {
            // return false if not possible
            return false;
        }

        // return success
        return true;
    }
}

/**
 * Removes deleted files from the system
 *
 * While packaging updates, the script will generate a list of deleted files in comparison to the previous version.  In this step,
 * we will process that list to remove files which are no longer included in the application.
 *
 * @param array $status
 *
 * @return array
 */
function remove_mautic_deleted_files(array $status)
{
    $errorLog = array();

    // Make sure we have a deleted_files list otherwise we can't process this step
    if (file_exists(__DIR__ . '/deleted_files.txt')) {
        $deletedFiles = json_decode(file_get_contents(__DIR__ . '/deleted_files.txt'), true);

        foreach ($deletedFiles as $file) {
            $path = MAUTIC_ROOT . '/' . $file;

            // If it doesn't exist, don't even bother
            if (file_exists($path)) {
                // Try setting the permissions to 777 just to make sure we can get rid of the file
                @chmod($path, 0777);

                if (!@unlink($path)) {
                    // Failed to delete, reset the permissions to 644 for safety
                    @chmod($path, 0644);

                    $errorLog[] = sprintf(
                        'Failed removing the file at %s from the production path.  As this is a deleted file, you can manually remove this file.',
                        $file
                    );
                }
            }
        }
    } else {
        $errorLog[] = 'The file containing the list of deleted files was not found, could not process the deleted file list.';
    }

    process_error_log($errorLog);

    $status['complete']                    = true;
    $status['updateState']['coreComplete'] = true;

    return $status;
}

/**
 * Send the response back to the main application
 *
 * @param array $status
 *
 * @return void
 */
function send_response(array $status)
{
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode($status);
}

// Fetch the update state out of the request
$state = json_decode(base64_decode(getVar('updateState', 'W10=')), true);

// Prime the state if it's empty
if (empty($state)) {
    $state['addonComplete']  = false;
    $state['bundleComplete'] = false;
    $state['cacheComplete']  = false;
    $state['coreComplete']   = false;
    $state['vendorComplete'] = false;
}

// Grab the update task
$task = getVar('task');

// Build the base status array
// TODO - Find a way to translate the step status
$status = array('complete' => false, 'error' => false, 'updateState' => $state, 'stepStatus' => 'In Progress');

switch ($task) {
    case 'moveBundles':
        $status = move_mautic_bundles($status);
        break;

    case 'moveCore':
        $status = move_mautic_core($status);
        break;

    case 'moveVendors':
        $status = move_mautic_vendors($status);
        break;

    case 'clearCache':
        $status = clear_mautic_cache($status);
        break;

    default:
        $status['error']      = true;
        $status['message']    = 'Invalid task';
        $status['stepStatus'] = 'Failed';
        break;
}

// Encode the state for the next request
$status['updateState'] = base64_encode(json_encode($status['updateState']));

send_response($status);
