<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
ini_set('display_errors', 'Off');
date_default_timezone_set('UTC');

define('MAUTIC_MINIMUM_PHP', '5.6.19');
define('MAUTIC_MAXIMUM_PHP', '7.1.999');

// Are we running the minimum version?
if (version_compare(PHP_VERSION, MAUTIC_MINIMUM_PHP, 'lt')) {
    echo 'Your server does not meet the minimum PHP requirements. Mautic requires PHP version '.MAUTIC_MINIMUM_PHP.' while your server has '.PHP_VERSION.'. Please contact your host to update your PHP installation.'."\n";
    exit;
}

// Are we running a version newer than what Mautic supports?
if (version_compare(PHP_VERSION, MAUTIC_MAXIMUM_PHP, 'gt')) {
    echo 'Mautic does not support PHP version '.PHP_VERSION.' at this time. To use Mautic, you will need to downgrade to an earlier version.'."\n";
    exit;
}

$standalone = (int) getVar('standalone', 0);
$task       = getVar('task');

define('IN_CLI', php_sapi_name() === 'cli');
define('MAUTIC_ROOT', (IN_CLI || $standalone || empty($task)) ? __DIR__ : dirname(__DIR__));
define('MAUTIC_UPGRADE_ERROR_LOG', MAUTIC_ROOT.'/upgrade_errors.txt');
define('MAUTIC_APP_ROOT', MAUTIC_ROOT.'/app');

if ($standalone || IN_CLI) {
    if (!file_exists(__DIR__.'/upgrade')) {
        mkdir(__DIR__.'/upgrade');
    }
    define('MAUTIC_UPGRADE_ROOT', __DIR__.'/upgrade');
} else {
    define('MAUTIC_UPGRADE_ROOT', __DIR__);
}

// Get local parameters
$localParameters = get_local_config();
if (isset($localParameters['cache_path'])) {
    $cacheDir = str_replace('%kernel.root_dir%', MAUTIC_APP_ROOT, $localParameters['cache_path'].'/prod');
} else {
    $cacheDir = MAUTIC_APP_ROOT.'/cache/prod';
}
define('MAUTIC_CACHE_DIR', $cacheDir);

// Fetch the update state out of the request if applicable
$state = json_decode(base64_decode(getVar('updateState', 'W10=')), true);

// Prime the state if it's empty
if (empty($state)) {
    $state['pluginComplete'] = false;
    $state['bundleComplete'] = false;
    $state['cacheComplete']  = false;
    $state['coreComplete']   = false;
    $state['vendorComplete'] = false;
}
$status = ['complete' => false, 'error' => false, 'updateState' => $state, 'stepStatus' => 'In Progress'];

// Web request upgrade
if (!IN_CLI) {
    $request         = explode('?', $_SERVER['REQUEST_URI'])[0];
    $url             = "//{$_SERVER['HTTP_HOST']}{$request}";
    $isSSL           = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');
    $cookie_path     = (isset($localParameters['cookie_path'])) ? $localParameters['cookie_path'] : '/';
    $cookie_domain   = (isset($localParameters['cookie_domain'])) ? $localParameters['cookie_domain'] : '';
    $cookie_secure   = (isset($localParameters['cookie_secure'])) ? $localParameters['cookie_secure'] : $isSSL;
    $cookie_httponly = (isset($localParameters['cookie_httponly'])) ? $localParameters['cookie_httponly'] : false;

    setcookie('mautic_update', $task, time() + 300, $cookie_path, $cookie_domain, $cookie_secure, $cookie_httponly);
    $query    = '';
    $maxCount = (!empty($standalone)) ? 25 : 5;

    switch ($task) {
        case '':
            html_body("<div class='well text-center'><h3><a href='$url?task=startUpgrade&standalone=1'>Click here to start upgrade.</a></h3><br /><strong>Do not refresh or stop the process. This may take serveral minutes.</strong></div>");

        case 'startUpgrade':
            $nextTask = 'fetchUpdates';
            break;

        case 'fetchUpdates':
            list($success, $message) = fetch_updates();

            if (!$success) {
                html_body("<div alert='alert alert-danger'>$message</div>");
            }

            $query    = "version=$message&";
            $nextTask = 'extractUpdate';
            break;

        case 'extractUpdate':
            list($success, $message) = extract_package(getVar('version'));

            if (!$success) {
                html_body("<div alert='alert alert-danger'>$message</div>");
            }

            $nextTask = 'moveBundles';
            break;

        case 'moveBundles':
            $status = move_mautic_bundles($status, $maxCount);
            if (empty($status['complete'])) {
                if (!isset($state['refresh_count'])) {
                    $state['refresh_count'] = 1;
                }
                $nextTask = 'moveBundles';
                $query    = 'count='.$state['refresh_count'].'&';
                $state['refresh_count'] += 1;
            } else {
                $nextTask = 'moveCore';
                unset($state['refresh_count']);
            }
            break;

        case 'moveCore':
            $status   = move_mautic_core($status);
            $nextTask = 'moveVendors';
            break;

        case 'moveVendors':
            $status   = move_mautic_vendors($status, $maxCount);
            $nextTask = (!empty($status['complete'])) ? 'clearCache' : 'moveVendors';

            if (empty($status['complete'])) {
                if (!isset($state['refresh_count'])) {
                    $state['refresh_count'] = 1;
                }
                $nextTask = 'moveVendors';
                $query    = 'count='.$state['refresh_count'].'&';
                $state['refresh_count'] += 1;
            } else {
                $nextTask = 'clearCache';
                unset($state['refresh_count']);
            }
            break;

        case 'clearCache':
            clear_mautic_cache();
            $nextTask = 'buildCache';
            $redirect = true;
            break;

        case 'buildCache':
            build_cache();
            $nextTask = (!empty($standalone)) ? 'applyMigrations' : 'applyCriticalMigrations';
            $redirect = true;
            break;

        case 'applyCriticalMigrations':
            // Apply critical migrations
            apply_critical_migrations();
            $nextTask = 'finish';
            $redirect = true;
            break;

        case 'clearCache':
            clear_mautic_cache();
            $nextTask = 'buildCache';
            $redirect = true;
            break;

        case 'applyMigrations':
            // Apply critical migrations
            apply_migrations();
            $nextTask = 'finish';

            break;

        case 'finish':
            clear_mautic_cache();

            if (!empty($standalone)) {
                html_body("<div class='well'><h3 class='text-center'>Success!</h3><h4 class='text-danger text-center'>Remove this script!</h4></div>");
            } else {
                $status['complete']                     = true;
                $status['stepStatus']                   = 'Success';
                $status['nextStep']                     = 'Processing Database Updates';
                $status['nextStepStatus']               = 'In Progress';
                $status['updateState']['cacheComplete'] = true;
            }
            break;

        default:
            $status['error']      = true;
            $status['message']    = 'Invalid task';
            $status['stepStatus'] = 'Failed';
            break;
    }

    if ($standalone || !empty($redirect)) {
        // Standalone updater or redirecting to help prevent timeouts
        if (!empty($nextTask)) {
            if ('finish' == $nextTask) {
                header("Location: $url?task=$nextTask&standalone=$standalone");
            } else {
                header("Location: $url?{$query}task=$nextTask&standalone=$standalone&updateState=".get_state_param($state));
            }

            exit;
        }
    } else {
        // Request through Mautic's UI
        $status['updateState'] = get_state_param($status['updateState']);

        send_response($status);
    }
} else {
    // CLI upgrade
    echo 'Checking for new updates...';
    list($success, $message) = fetch_updates();
    if (!$success) {
        echo "failed. $message";
        exit;
    }
    $version = $message;
    echo "updating to $version!\n";

    echo 'Extracting the update package...';
    list($success, $message) = extract_package($version);
    if (!$success) {
        echo "failed. $message";
        exit;
    }
    echo "done!\n";

    echo 'Moving files...';
    $status = move_mautic_bundles($status, -1);
    $status = move_mautic_core($status);
    $status = move_mautic_vendors($status, -1);
    if (empty($status['complete'])) {
        echo 'failed. Review udpate errors log for details.';
        exit;
    }
    unset($status['complete']);
    echo "done!\n";

    echo 'Clearing the cache...';
    if (!clear_mautic_cache()) {
        echo 'failed. Review udpate errors log for details.';
        exit;
    }
    echo "done!\n";

    echo 'Rebuilding the cache...';
    if (!build_cache()) {
        echo 'failed. Review udpate errors log for details.';
        exit;
    }
    echo "done!\n";

    echo 'Applying migrations...';
    if (!apply_migrations()) {
        echo 'failed. Review udpate errors log for details.';
        exit;
    }
    echo "done!\n";

    echo 'Cleaning up...';
    if (!recursive_remove_directory(MAUTIC_UPGRADE_ROOT)) {
        echo "failed. Manually delete the upgrade folder.\n";
    }
    if (!clear_mautic_cache()) {
        echo 'failed. Manually delete app/cache/prod.';
    }
    echo "done!\n";

    echo "\nSuccess!";
}

/**
 * Get local parameters.
 *
 * @return mixed
 */
function get_local_config()
{
    static $parameters;

    if (null === $parameters) {
        // Used in paths.php
        $root = MAUTIC_APP_ROOT;

        /** @var array $paths */
        include MAUTIC_APP_ROOT.'/config/paths.php';

        // Include local config to get cache_path
        $localConfig = str_replace('%kernel.root_dir%', MAUTIC_APP_ROOT, $paths['local_config']);

        /** @var array $parameters */
        include $localConfig;

        $localParameters = $parameters;

        //check for parameter overrides
        if (file_exists(MAUTIC_APP_ROOT.'/config/parameters_local.php')) {
            /** @var $parameters */
            include MAUTIC_APP_ROOT.'/config/parameters_local.php';
            $localParameters = array_merge($localParameters, $parameters);
        }

        foreach ($localParameters as $k => &$v) {
            if (!empty($v) && is_string($v) && preg_match('/getenv\((.*?)\)/', $v, $match)) {
                $v = (string) getenv($match[1]);
            }
        }

        $parameters = $localParameters;
    }

    return $parameters;
}

/**
 * Fetch a list of updates.
 *
 * @return array
 */
function fetch_updates()
{
    global $localParameters;

    $version = file_get_contents(__DIR__.'/app/version.txt');
    try {
        // Generate a unique instance ID for the site
        $instanceId = hash('sha1', $localParameters['secret_key'].'Mautic'.$localParameters['db_driver']);

        $data = [
            'application'   => 'Mautic',
            'version'       => $version,
            'phpVersion'    => PHP_VERSION,
            'dbDriver'      => $localParameters['db_driver'],
            'serverOs'      => php_uname('s').' '.php_uname('r'),
            'instanceId'    => $instanceId,
            'installSource' => (isset($localParameters['install_source'])) ? $localParameters['install_source'] : 'Mautic',
        ];

        make_request('https://updates.mautic.org/stats/send', 'post', $data);
    } catch (\Exception $exception) {
        // Not so concerned about failures here, move along
    }

    // Get the update data
    try {
        $appData = [
            'appVersion' => $version,
            'phpVersion' => PHP_VERSION,
            'stability'  => (isset($localParameters['update_stability'])) ? $localParameters['update_stability'] : 'stable',
        ];

        $data   = make_request('https://updates.mautic.org/index.php?option=com_mauticdownload&task=checkUpdates', 'post', $appData);
        $update = json_decode($data);

        // Check if this version is up to date
        if ($update->latest_version || version_compare($version, $update->version, 'ge')) {
            return [false, 'Up to date!'];
        }

        // Fetch the package
        try {
            download_package($update->package);
        } catch (\Exception $e) {
            return [
                false,
                "Could not automatically download the package. Please download {$update->package}, place it in the same directory as this upgrade script, and try again.",
            ];
        }

        return [true, $update->version];
    } catch (\Exception $exception) {
        return [false, $exception->getMessage()];
    }
}

/**
 * @param $package
 *
 * @throws Exception
 */
function download_package($package)
{
    if (file_exists(__DIR__.'/'.basename($package))) {
        return true;
    }

    $data = make_request($package);

    // Set the filesystem target
    $target = __DIR__.'/'.basename($package);

    // Write the response to the filesystem
    if (!file_put_contents($target, $data)) {
        throw new \Exception();
    }
}

/**
 * @param $zipFile
 *
 * @return int
 */
function extract_package($version)
{
    $zipFile = __DIR__.'/'.$version.'-update.zip';

    if (!file_exists($zipFile)) {
        return [false, 'Package could not be found!'];
    }

    $zipper  = new \ZipArchive();
    $archive = $zipper->open($zipFile);

    if ($archive !== true) {
        return [false, 'Could not open or read update package.'];
    }

    if (!$zipper->extractTo(MAUTIC_UPGRADE_ROOT)) {
        return [false, 'Could not extract update package'];
    }

    $zipper->close();

    return [true, 'success'];
}

/**
 * Clears the application cache.
 *
 * Since this script is being executed via web requests and standalone from the Mautic application, we don't have access to Symfony's
 * CLI suite.  So we'll go with Option B in this instance and just nuke the entire production cache and let Symfony rebuild it on the next
 * application cycle.
 *
 * @param array $status
 *
 * @return array
 */
function clear_mautic_cache()
{
    if (!recursive_remove_directory(MAUTIC_CACHE_DIR)) {
        process_error_log(['Could not remove the application cache.  You will need to manually delete '.MAUTIC_CACHE_DIR.'.']);

        return false;
    }

    return true;
}

/**
 * @param       $command
 * @param array $args
 *
 * @return array
 *
 * @throws Exception
 */
function run_symfony_command($command, array $args)
{
    static $application;

    require_once MAUTIC_APP_ROOT.'/autoload.php';
    require_once MAUTIC_APP_ROOT.'/AppKernel.php';

    $args = array_merge(
        ['console', $command],
        $args
    );

    if (null == $application) {
        $kernel      = new \AppKernel('prod', true);
        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
        $application->setAutoExit(false);
    }

    $input    = new \Symfony\Component\Console\Input\ArgvInput($args);
    $output   = new \Symfony\Component\Console\Output\NullOutput();
    $exitCode = $application->run($input, $output);

    unset($input, $output);

    return $exitCode === 0;
}

/**
 * Build the cache.
 *
 * @return array
 */
function build_cache()
{
    // Rebuild the cache
    return run_symfony_command('cache:clear', ['--no-interaction', '--env=prod', '--no-debug', '--no-warmup']);
}

/**
 * Apply critical migrations.
 */
function apply_critical_migrations()
{
    $criticalMigrations = json_decode(file_get_contents(__DIR__.'/critical_migrations.txt'), true);

    $success = true;

    if ($criticalMigrations) {
        foreach ($criticalMigrations as $version) {
            if (!run_symfony_command('doctrine:migrations:migrate', ['--no-interaction', '--env=prod', '--no-debug', $version])) {
                $success = false;
            }
        }
    }

    return $success;
}

/**
 * Apply all migrations.
 *
 * @return bool
 */
function apply_migrations()
{
    return run_symfony_command('doctrine:migrations:migrate', ['--no-interaction', '--env=prod', '--no-debug']);
}

/**
 * Copy a folder.
 *
 * This function is based on \Joomla\Filesystem\Folder:copy()
 *
 * @param string $src  The path to the source folder
 * @param string $dest The path to the destination folder
 *
 * @return array|string|bool True on success, a single error message on a "boot" fail, or an array of errors from the recursive operation
 */
function copy_directory($src, $dest)
{
    @set_time_limit(ini_get('max_execution_time'));
    $errorLog = [];

    // Eliminate trailing directory separators, if any
    $src  = rtrim($src, DIRECTORY_SEPARATOR);
    $dest = rtrim($dest, DIRECTORY_SEPARATOR);

    // Make sure the destination exists
    if (!is_dir($dest)) {
        if (!@mkdir($dest, 0755, true)) {
            return sprintf(
                'Could not move files from %s to production since the folder could not be created.',
                str_replace(MAUTIC_UPGRADE_ROOT, '', $src)
            );
        }
    }

    if (!($dh = @opendir($src))) {
        return sprintf('Could not read directory %s to move files.', str_replace(MAUTIC_UPGRADE_ROOT, '', $src));
    }

    // Walk through the directory copying files and recursing into folders.
    while (($file = readdir($dh)) !== false) {
        $sfid = $src.'/'.$file;
        $dfid = $dest.'/'.$file;

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
 * Fetches a request variable and returns the sanitized version of it.
 *
 * @param string $name
 * @param string $default
 * @param int    $filter
 *
 * @return mixed|string
 */
function getVar($name, $default = '', $filter = FILTER_SANITIZE_STRING)
{
    if (isset($_REQUEST[$name])) {
        return filter_var($_REQUEST[$name], $filter);
    }

    return $default;
}

/**
 * Moves the Mautic bundles from the upgrade directory to production.
 *
 * A typical update package will only include changed files in the bundles.  However, in this script we will assume that all of
 * the bundle resources are included here and recursively iterate over the bundles in batches to update the filesystem.
 *
 * @param array $status
 * @param int   $maxCount
 *
 * @return array
 */
function move_mautic_bundles(array $status, $maxCount = 5)
{
    $errorLog = [];

    // First, we will move any addon bundles into position
    if (is_dir(MAUTIC_UPGRADE_ROOT.'/plugins') && !$status['updateState']['pluginComplete']) {
        $iterator = new DirectoryIterator(MAUTIC_UPGRADE_ROOT.'/plugins');

        // Sanity check, make sure there are actually directories here to process
        $dirs = glob(MAUTIC_UPGRADE_ROOT.'/plugins/*', GLOB_ONLYDIR);

        if (count($dirs)) {
            /** @var DirectoryIterator $directory */
            foreach ($iterator as $directory) {
                // Sanity checks
                if (!$directory->isDot() && $directory->isDir()) {
                    $src  = $directory->getPath().'/'.$directory->getFilename();
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

        // At this point, there shouldn't be any plugins remaining; nuke the folder
        $deleteDir = recursive_remove_directory(MAUTIC_UPGRADE_ROOT.'/plugins');

        if (!$deleteDir) {
            $errorLog[] = sprintf('Failed to remove the upgrade directory %s folder', '/plugins');
        }

        process_error_log($errorLog);

        $status['updateState']['pluginComplete'] = true;

        if ($maxCount != -1) {
            // Finished with plugins, get a response back to the app so we can iterate to the next part
            return $status;
        }
    }

    // Now we move the main app bundles into production
    if (is_dir(MAUTIC_UPGRADE_ROOT.'/app/bundles') && !$status['updateState']['bundleComplete']) {
        // Initialize the bundle state if it isn't
        if (!isset($status['updateState']['completedBundles'])) {
            $status['updateState']['completedBundles'] = [];
        }

        $completed = true;
        $iterator  = new DirectoryIterator(MAUTIC_UPGRADE_ROOT.'/app/bundles');

        // Sanity check, make sure there are actually directories here to process
        $dirs = glob(MAUTIC_UPGRADE_ROOT.'/app/bundles/*', GLOB_ONLYDIR);

        if (count($dirs)) {
            $count = 0;

            /** @var DirectoryIterator $directory */
            foreach ($iterator as $directory) {
                // Exit the loop if the count has reached 5
                if ($maxCount != -1 && $count === $maxCount) {
                    $completed = false;
                    break;
                }

                // Sanity checks
                if (!$directory->isDot() && $directory->isDir()) {
                    // Don't process this bundle if we've already tried it
                    if (isset($status['updateState']['completedBundles'][$directory->getFilename()])) {
                        continue;
                    }

                    $src  = $directory->getPath().'/'.$directory->getFilename();
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
                    ++$count;
                }
            }
        }

        if ($completed) {
            $status['updateState']['bundleComplete'] = true;

            // At this point, there shouldn't be any bundles remaining; nuke the folder
            $deleteDir = recursive_remove_directory(MAUTIC_UPGRADE_ROOT.'/app/bundles');

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
 * Moves the Mautic core files that are not part of bundles or vendors into production.
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
    $errorLog = [];

    // Multilevel directories
    $nestedDirectories = [
        '/media',
        '/themes',
        '/translations',
        '/app/middlewares',
    ];

    foreach ($nestedDirectories as $dir) {
        if (is_dir(MAUTIC_UPGRADE_ROOT.$dir)) {
            copy_directories($dir, $errorLog);

            // At this point, we can remove the media directory
            $deleteDir = recursive_remove_directory(MAUTIC_UPGRADE_ROOT.$dir);

            if (!$deleteDir) {
                $errorLog[] = sprintf('Failed to remove the upgrade directory %s folder', $dir);
            }
        }
    }

    // Single level directories with files only
    $fileOnlyDirectories = [
        '/app/config',
        '/app/migrations',
        '/app',
        '/bin',
    ];

    foreach ($fileOnlyDirectories as $dir) {
        if (copy_files($dir, $errorLog)) {

            // At this point, we can remove the config directory
            $deleteDir = recursive_remove_directory(MAUTIC_UPGRADE_ROOT.$dir);

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
        if ($file->isFile() && !in_array($file->getFilename(), ['deleted_files.txt', 'critical_migrations.txt', 'upgrade.php'])) {
            $src  = $file->getPath().'/'.$file->getFilename();
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
 * Moves the Mautic dependencies from the upgrade directory to production.
 *
 * Since the /vendor folder is not stored under version control, we cannot accurately track changes in third party dependencies
 * between releases.  Therefore, this step will recursively iterate over the vendors in batches to remove each package completely
 * and replace it with the new version.
 *
 * @param array $status
 * @param int   $maxCount
 *
 * @return array
 */
function move_mautic_vendors(array $status, $maxCount = 5)
{
    $errorLog = [];

    // If there isn't even a vendor directory, just skip this step
    if (!is_dir(MAUTIC_UPGRADE_ROOT.'/vendor')) {
        $status['complete']                      = true;
        $status['stepStatus']                    = 'Success';
        $status['nextStep']                      = 'Clearing Application Cache';
        $status['nextStepStatus']                = 'In Progress';
        $status['updateState']['vendorComplete'] = true;

        return $status;
    }

    // Initialize the vendor state if it isn't
    if (!isset($status['updateState']['completedVendors'])) {
        $status['updateState']['completedVendors'] = [];
    }

    // Symfony is the largest of our vendors, we will process it first
    if (is_dir(MAUTIC_UPGRADE_ROOT.'/vendor/symfony') && !isset($status['updateState']['completedVendors']['symfony'])) {
        // Initialize the Symfony state if it isn't, this step will recurse
        if (!isset($status['updateState']['completedSymfony'])) {
            $status['updateState']['completedSymfony'] = [];
        }

        $completed = true;
        $iterator  = new DirectoryIterator(MAUTIC_UPGRADE_ROOT.'/vendor/symfony');

        // Sanity check, make sure there are actually directories here to process
        $dirs = glob(MAUTIC_UPGRADE_ROOT.'/vendor/symfony/*', GLOB_ONLYDIR);

        if (count($dirs)) {
            $count = 0;

            /** @var DirectoryIterator $directory */
            foreach ($iterator as $directory) {
                // Exit the loop if the count has reached 5
                if ($maxCount != -1 && $count === $maxCount) {
                    $completed = false;
                    break;
                }

                // Sanity checks
                if (!$directory->isDot() && $directory->isDir()) {
                    // Don't process this directory if we've already tried it
                    if (isset($status['updateState']['completedSymfony'][$directory->getFilename()])) {
                        continue;
                    }

                    $src  = $directory->getPath().'/'.$directory->getFilename();
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
                    ++$count;
                }
            }
        }

        if ($completed) {
            $status['updateState']['completedVendors']['symfony'] = true;

            // At this point, there shouldn't be any Symfony code remaining; nuke the folder
            $deleteDir = recursive_remove_directory(MAUTIC_UPGRADE_ROOT.'/vendor/symfony');

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
    $iterator  = new DirectoryIterator(MAUTIC_UPGRADE_ROOT.'/vendor');

    // Sanity check, make sure there are actually directories here to process
    $dirs = glob(MAUTIC_UPGRADE_ROOT.'/vendor/*', GLOB_ONLYDIR);

    if (count($dirs)) {
        $count = 0;

        /** @var DirectoryIterator $directory */
        foreach ($iterator as $directory) {
            // Exit the loop if the count has reached 5
            if ($maxCount != -1 && $count === $maxCount) {
                $completed = false;
                break;
            }

            // Sanity checks
            if (!$directory->isDot() && $directory->isDir()) {
                // Don't process this directory if we've already tried it
                if (isset($status['updateState']['completedVendors'][$directory->getFilename()])) {
                    continue;
                }

                $src  = $directory->getPath().'/'.$directory->getFilename();
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
                ++$count;
            }
        }
    }

    if ($completed) {
        $status['updateState']['vendorComplete'] = true;

        // Move the autoload.php file over now
        if (!@rename(MAUTIC_UPGRADE_ROOT.'/vendor/autoload.php', MAUTIC_ROOT.'/vendor/autoload.php')) {
            $errorLog[] = 'Could not move file /vendor/autoload.php to production.';
        }

        // At this point, there shouldn't be any vendors remaining; nuke the folder
        $deleteDir = recursive_remove_directory(MAUTIC_UPGRADE_ROOT.'/vendor');

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
 * Copy files from the directory.
 *
 * @param string $dir
 * @param array  &$errorLog
 *
 * @return bool
 */
function copy_files($dir, &$errorLog)
{
    if (is_dir(MAUTIC_UPGRADE_ROOT.$dir)) {
        $iterator = new FilesystemIterator(MAUTIC_UPGRADE_ROOT.$dir);

        /** @var FilesystemIterator $file */
        foreach ($iterator as $file) {
            // Sanity checks
            if ($file->isFile()) {
                $src  = $file->getPath().'/'.$file->getFilename();
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
 * Copy directories.
 *
 * @param string $dir
 * @param array  &$errorLog
 * @param bool   $createDest
 *
 * @return bool|void
 */
function copy_directories($dir, &$errorLog, $createDest = true)
{
    // Ensure the destination directory exists
    $exists = file_exists(MAUTIC_ROOT.$dir);
    if ($createDest && !$exists) {
        mkdir(MAUTIC_ROOT.$dir, 0755, true);
    } elseif (!$exists) {
        $errorLog[] = sprintf('%s does not exist.', MAUTIC_ROOT.$dir);

        return false;
    }

    // Copy root level files first
    copy_files($dir, $errorLog);

    $iterator = new DirectoryIterator(MAUTIC_UPGRADE_ROOT.$dir);

    /** @var DirectoryIterator $directory */
    foreach ($iterator as $directory) {
        // Sanity checks
        if (!$directory->isDot() && $directory->isDir()) {
            $src  = $directory->getPath().'/'.$directory->getFilename();
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
 * Processes the error log for each step.
 *
 * @param array $errorLog
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

        $errors .= implode(PHP_EOL, $errorLog)."\n";

        @file_put_contents(MAUTIC_UPGRADE_ERROR_LOG, $errors);
    }
}

/**
 * Tries to recursively delete a directory.
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
    if (!file_exists($directory)) {
        return true;
    } elseif (!is_dir($directory)) {
        return false;
        // ... if the path is not readable
    } elseif (!is_readable($directory)) {
        // ... we return false and exit the function
        return false;
        // ... else if the path is readable
    } else {
        // we open the directory
        $handle = opendir($directory);

        // and scan through the items inside
        while (false !== ($item = readdir($handle))) {
            // if the filepointer is not the current directory
            // or the parent directory
            if ($item != '.' && $item != '..') {
                // we build the new path to delete
                $path = $directory.'/'.$item;
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
 * Removes deleted files from the system.
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
    $errorLog = [];

    // Make sure we have a deleted_files list otherwise we can't process this step
    if (file_exists(MAUTIC_UPGRADE_ROOT.'/deleted_files.txt')) {
        $deletedFiles = json_decode(file_get_contents(MAUTIC_UPGRADE_ROOT.'/deleted_files.txt'), true);

        foreach ($deletedFiles as $file) {
            $path = MAUTIC_ROOT.'/'.$file;

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
                } else {
                    // Check to see if directory is now empty and if so, delete it
                    $dirpath = dirname($path);
                    if (file_exists($dirpath) && !glob($dirpath.'/*')) {
                        @chmod($dirpath, 0777);
                        if (!@unlink($dirpath)) {
                            // Failed to delete, reset the permissions to 0755 for safety
                            @chmod($dirpath, 0755);
                        }
                    }
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
 * @param array $state
 *
 * @return string
 */
function get_state_param(array $state)
{
    return base64_encode(json_encode($state));
}

/**
 * Send the response back to the main application.
 *
 * @param array $status
 */
function send_response(array $status)
{
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode($status);
}

/**
 * Crap means of not having issues with.
 */
function make_request($url, $method = 'GET', $data = null)
{
    $method  = strtoupper($method);
    $ch      = curl_init();
    $timeout = 15;
    if ($data && 'POST' == $method) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CAINFO, MAUTIC_ROOT.'/vendor/joomla/http/src/Transport/cacert.pem');
    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

/**
 * Wrap content in some HTML.
 *
 * @param $content
 */
function html_body($content)
{
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Upgrade Mautic</title>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
  </head>
  <body>
    <div class="container" style="padding: 25px;">
        $content
    </div>
  </body>
</html>
HTML;

    echo $html;

    exit;
}
