<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * NOTE: This upgrade script is specifically for upgrading Mautic 2.16.3+ to Mautic 3.0.0 (or later patch releases). It can only be started in standalone mode!
 */
ini_set('display_errors', 'Off');
date_default_timezone_set('UTC');

define('MAUTIC_MINIMUM_PHP', '7.2.21');
define('MAUTIC_MAXIMUM_PHP', '7.3.999');

// We can only run this script in standalone mode, either in the browser or in CLI, due to extensive backwards incompatbile changes.
$standalone = 1;
$task       = getVar('task');

define('IN_CLI', php_sapi_name() === 'cli');
define('MAUTIC_ROOT', __DIR__);
define('MAUTIC_UPGRADE_ERROR_LOG', MAUTIC_ROOT . '/upgrade_errors.txt');
define('MAUTIC_APP_ROOT', MAUTIC_ROOT . '/app');
define('MAUTIC_UPGRADE_FOLDER_NAME', 'mautic-3-temp-files');
define('MAUTIC_UPGRADE_ROOT', MAUTIC_ROOT . DIRECTORY_SEPARATOR . MAUTIC_UPGRADE_FOLDER_NAME);
// This value always needs to contain mautic-2-backup-files as we replace that with a unique hashed name later on!
define('MAUTIC_BACKUP_FOLDER_ROOT', MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'mautic-2-backup-files');

if (!file_exists(MAUTIC_UPGRADE_ROOT)) {
    mkdir(MAUTIC_UPGRADE_ROOT);
}

// Get local parameters
$localParameters = get_local_config();
if (isset($localParameters['cache_path'])) {
    $cacheDir = str_replace('%kernel.root_dir%', MAUTIC_APP_ROOT, $localParameters['cache_path'] . '/prod');
} else {
    $cacheDir = MAUTIC_APP_ROOT . '/cache/prod';
}
define('MAUTIC_CACHE_DIR', $cacheDir);

// Data we fetch from a special JSON file to control upgrade behavior.
// TODO replace with actual JSON file
$updateData = [
    'mautic3downloadUrl' => 'https://github.com/dennisameling/mautic/releases/download/3.0.0-beta2/3.0.0-beta2-update.zip',
    'killSwitchActivated' => false,
    'statusPageUrl' => 'https://mautic.org'
];

/**
 * Run pre-upgrade checks. Returns array with keys "warnings" (dismissable) and "errors" (block upgrading)
 * 
 * ==== PRE-UPGRADE CHECKS ====
 * 
 * To ensure a smooth upgrade to 3.0, we check a few things beforehand:
 * - PHP version >= 7.2.21 and <= 7.3999
 * - Current database driver = pdo_mysql or mysqli (get from existing Mautic config file)
 * - MySQL version > 5.7.14 or MariaDB version > 10.1
 * - Mautic version > 2.16.3 (this version adds support for upgrading to 3.0)
 * - Check if Mautic's root folder is writable by creating + deleting a dummy folder
 * - Ensure PHP's max_execution_time is at least 240 (4 mins).
 * - Check if Mautic's upgrade kill switch is enabled (the Product team can activate the kill switch if many users fail upgrading)
 * 
 * @return array
 */
function runPreUpgradeChecks()
{
    global $updateData;
    global $localParameters;

    // Errors prevent a user from updating.
    $preUpgradeErrors = [];

    // Warnings can be dismissed
    $preUpgradeWarnings = [];

    // Are we running the minimum version?
    if (version_compare(PHP_VERSION, MAUTIC_MINIMUM_PHP, 'lt')) {
        $preUpgradeErrors[] = 'Your server does not meet the minimum PHP requirements. Mautic requires PHP version ' . MAUTIC_MINIMUM_PHP . ' while your server has ' . PHP_VERSION . '. Please contact your host to update your PHP installation.' . "\n";
    }

    // Are we running a version newer than what Mautic supports?
    if (version_compare(PHP_VERSION, MAUTIC_MAXIMUM_PHP, 'gt')) {
        $preUpgradeErrors[] = 'Mautic does not support PHP version ' . PHP_VERSION . ' at this time. To use Mautic, you will need to downgrade to an earlier version.' . "\n";
    }

    // Check database connection and database version
    if (!in_array($localParameters['db_driver'], ['pdo_mysql', 'mysqli'])) {
        $preUpgradeErrors[] = 'Your database driver is not pdo_mysql or mysqli, which are the only drivers that Mautic supports. Please change your database driver (config/local.php)!';
    }

    $mysqli = new mysqli($localParameters['db_host'], $localParameters['db_user'], $localParameters['db_password']);

    if (mysqli_connect_errno()) {
        $preUpgradeErrors[] = 'Could not connect to your database. Please try again or fix your Mautic settings.';
    } else {
        $dbVersion = $mysqli->server_version;

        if (!(($dbVersion >= 50714 && $dbVersion < 100000) || ($dbVersion >= 100100))) {
            $preUpgradeErrors[] = 'Your MySQL/MariaDB version is not supported. You need at least MySQL 5.7.14 or MariaDB 10.1 in order to run Mautic 3.';
        }

        $mysqli->close();
    }

    // Check if Mautic's root folder is writable
    if (!is_writable(MAUTIC_ROOT)) {
        $preUpgradeErrors[] = 'Mautic\'s root directory is not writable. We need write access in order to update application files.';
    } else {
        $folderPermissionError = 'We tried creating and deleting a dummy folder for testing permissions on Mautic\'s root directory, but failed. Please make sure that your webserver has write access on Mautic\'s root folder.';
        $dummyFolder = MAUTIC_ROOT . '/upgrade-test-folder-permissions';

        if (!mkdir($dummyFolder)) {
            $preUpgradeErrors[] = $folderPermissionError;
        }

        if (!rmdir($dummyFolder)) {
            $preUpgradeErrors[] = $folderPermissionError;
        }
    }

    if (file_exists(MAUTIC_APP_ROOT . '/release_metadata.json')) {
        $preUpgradeErrors[] = 'You already seem to be running Mautic 3.0.0 or newer, so this upgrade script is not relevant to you anymore. Aborting.';
    } elseif (file_exists(MAUTIC_APP_ROOT . '/version.txt')) {
        // Check if we have the required Mautic version 2.16.3 prior to upgrading.
        $version = file_get_contents(MAUTIC_APP_ROOT . '/version.txt');
        $version = str_replace("\n", "", $version);

        if (!version_compare($version, '2.16.3', '>=')) {
            $preUpgradeErrors[] = 'You need to have at least Mautic 2.16.3 installed, which supports upgrading to 3.0. Please update to 2.16.3 first.';
        }
    } else {
        $preUpgradeErrors[] = 'We can\'t seem to detect your current Mautic version. Make sure you have a version.txt file in your app folder.';
    }

    // Check PHP's max_execution_time
    $maxExecutionTime = ini_get('max_execution_time');

    if ($maxExecutionTime > 0 && $maxExecutionTime < 240) {
        $preUpgradeErrors[] = 'PHP max_execution_time needs to be at least 240 seconds (4 minutes) to allow for a successful upgrade. Please contact your host to set this value to 240 seconds or higher.';
    }

    // Check if mysqldump is available on the system for creating a DB backup.
    if (!function_exists('exec')) {
        $preUpgradeErrors[] = 'We can\'t make a database backup for you due to restrictions on your system. Only continue if you have your own database backup available! Click HERE (TODO) if you have a backup available and want to continue.';
    } else {
        $return_var = null;
        $output = null;
        // Escape single quotes in DB password
        $db_password = str_replace("'", "'\''", $localParameters['db_password']);
        // Check if mysqldump command finishes by writing to /dev/null
        $command = "mysqldump -u " . $localParameters['db_user'] . " -h " . $localParameters['db_host'] . " -p'" . $db_password . "' " . $localParameters['db_name'] . " > /dev/null";
        exec($command, $output, $return_var);

        if ($return_var) {
            $preUpgradeErrors[] = 'We tried making a backup for you, but failed. Click HERE (TODO) if you have a backup available and want to continue.';
        }
    }

    if (empty($updateData['mautic3downloadUrl'])) {
        $preUpgradeErrors[] = 'There\'s no upgrade package available for download. This might indicate that the Mautic team is working on releasing a new version.
        Please see our <a href="' . $updateData['statusPageUrl'] . '" target="_blank">status page</a> for more details.';
    }

    // If our kill switch is activated, show a clear warning to users prior to upgrading.
    if ($updateData['killSwitchActivated'] === true) {
        $preUpgradeWarnings[] = '<strong>WARNING</strong>: It looks like many Mautic users are having trouble upgrading to this new version,
            please proceed with caution! For all details and to see if you\'re affected, <strong>please see our <a href="' . $updateData['statusPageUrl'] . '" target="_blank">status page</a></strong>.';
    }

    return [
        'warnings' => $preUpgradeWarnings,
        'errors' => $preUpgradeErrors
    ];
}

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
            header("Refresh: 2; URL=$url?{$query}task=preUpgradeChecks");
            html_body("<div class='card card-body bg-light text-center'><h3>Checking system requirements...</h3><br /><strong>We're checking whether your system meets the requirements for Mautic 3. This may take several minutes, do not close this window!</strong></div>");
            exit;

        case 'preUpgradeChecks':
            $preUpgradeCheckResults = runPreUpgradeChecks();
            $preUpgradeCheckErrors = $preUpgradeCheckResults['errors'];
            $preUpgradeCheckWarnings = $preUpgradeCheckResults['warnings'];
            $html = "<div class='card card-body bg-light text-center'>";

            if (count($preUpgradeCheckErrors) > 0) {
                $html .= '<h3>Whoops! You\'re not ready for Mautic 3 (yet)</h3><p>The following <strong style="color: red">errors</strong> occurred while checking system compatibility:</p><ul style="text-align: left">';
                foreach ($preUpgradeCheckErrors as $error) {
                    $html .= '<li>' . $error . '</li>';
                }
                $html .= '</ul>';
            }

            if (count($preUpgradeCheckWarnings) > 0) {
                $html .= '<p>The following <strong style="color: orange">warnings</strong> occurred while checking system compatibility:</p><ul style="text-align: left">';
                foreach ($preUpgradeCheckWarnings as $warning) {
                    $html .= '<li>' . $warning . '</li>';
                }

                // The checkbox doesn't do anything, but is just there to make users aware that they are doing risky things.
                $html .= '</ul>
                <input type="checkbox" id="forceUpgradeStart" /> <label for="forceUpgradeStart">Yes, I am aware of the warnings above and still want to proceed with the upgrade.</label><br /><br />
                <a class="btn btn-primary" href="' . $url . '?task=startUpgrade&standalone=1">Start the upgrade</a>';
            }

            if (count($preUpgradeCheckErrors) === 0 && count($preUpgradeCheckWarnings) === 0) {
                $html .= "<h3>Ready to upgrade ✅</h3>
                <br /><strong>Your system is compatible with Mautic 3!<br>Do not refresh or stop the process. This may take several minutes.<br><br><u>It's strongly recommended to have a backup before you start upgrading!</u></strong><br><Br>
                <a class='btn btn-primary' href='$url?task=startUpgrade&standalone=1' onClick='document.getElementById(\"updateInProgress\").style.display = \"block\"'>Start the upgrade</a><br><br>
                <div style='display: none' class=\"text-center\" id='updateInProgress'>
                    <div class=\"spinner-border\" role=\"status\">
                        <span class=\"sr-only\">Please wait...</span>
                    </div><br><br>
                    <div>Upgrade in progress, this might make several minutes! Do not leave this page.</div>
                </div>";
            }

            html_body($html);
            break;

        case 'startUpgrade':
            $nextTask = 'applyV2Migrations';
            sendUpgradeStats('started');
            break;

        case 'applyV2Migrations':
            // Apply migrations on the 2.x branch just so we're sure that we have all migrations in place.
            if (apply_migrations() === false) {
                sendUpgradeStats('failed');
                html_body("<div alert='alert alert-danger'>Oh no! While preparing the upgrade, the so-called 'database migrations' for Mautic 2 have failed. Please check our knowledgebase (TODO) for more info.</div>");
            };

            $nextTask = 'fetchUpdates';
            break;

        case 'fetchUpdates':
            list($success, $message) = fetch_updates();

            if (!$success) {
                sendUpgradeStats('failed');
                html_body("<div alert='alert alert-danger'>$message</div>");
            }

            $query    = "version=$message&";
            $nextTask = 'extractUpdate';
            break;

        case 'extractUpdate':
            list($success, $message) = extract_package();

            if (!$success) {
                sendUpgradeStats('failed');
                html_body("<div alert='alert alert-danger'>$message</div>");
            }

            $nextTask = 'moveMautic2and3Files';
            break;

        case 'moveMautic2and3Files':
            /**
             * Move current Mautic 2 files into a temporary directory called "mautic-2-backup-files",
             * then move the Mautic 3 files from "mautic-3-temp-files" to the root directory.
             */
            list($success, $message) = replace_mautic_2_with_mautic_3();

            if (!$success) {
                sendUpgradeStats('failed');
                html_body("<div alert='alert alert-danger'>$message</div>");
            }

            $nextTask = 'restoreUserData';
            break;

        case 'restoreUserData':
            // Restore user data like plugins/themes/media from the original Mautic 2 installation to the "fresh" M3 installation
            list($success, $message) = restore_user_data();

            if (!$success) {
                sendUpgradeStats('failed');
                html_body("<div alert='alert alert-danger'>$message</div>");
            }

            $nextTask = 'updateLocalConfig';
            break;

        case 'updateLocalConfig':
            // Update config/local.php with updated keys.
            list($success, $message) = update_local_config();

            if (!$success) {
                sendUpgradeStats('failed');
                html_body("<div alert='alert alert-danger'>$message</div>");
            }

            $nextTask = 'buildCache';
            break;

        case 'buildCache':
            // Build fresh cache for M3.
            if (build_cache() === false) {
                sendUpgradeStats('failed');
                html_body("<div alert='alert alert-danger'>Oh no! We couldn\'t build a fresh cache for Mautic 3. Please check our knowledgebase (TODO) for more info.</div>");
            };

            $nextTask = 'applyMigrations';
            break;

        case 'applyMigrations':
            // Apply Mautic 3 migrations. Almost there!!
            if (apply_migrations() === false) {
                sendUpgradeStats('failed');
                html_body("<div alert='alert alert-danger'>Oh no! We were almost there, but we couldn\'t run the so-called 'database migrations' for Mautic 3. Please check our knowledgebase (TODO) for more info.</div>");
            };

            $nextTask = 'cleanupFiles';
            break;

        case 'cleanupFiles':
            // Cleanup some of our installation files that we no longer need.
            if (cleanup_files() === false) {
                sendUpgradeStats('failed');
                html_body("<div alert='alert alert-danger'>Oops! We tried cleaning up after ourselves, but it didn\'t work as expected. Please check our knowledgebase (TODO link)</div>");
            }

            sendUpgradeStats('succeeded');

            // Destroy the upgrade script as we no longer need it.
            unlink(__FILE__);

            html_body("<div class='card card-body bg-light text-center'>
                <h3>We're done! ✅</h3>
                <br /><strong>You're ready to use Mautic 3! This script has destroyed itself, so there's nothing left to do for you. Enjoy!</strong><br><br>
                <iframe src='https://giphy.com/embed/1GrsfWBDiTN60' width='480' height='262' frameBorder='0' class='giphy-embed' allowFullScreen></iframe><p><a href='https://giphy.com/gifs/dance-long-hair-shake-1GrsfWBDiTN60'>via GIPHY</a></p>
            </div>");

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
                header("Location: $url?{$query}task=$nextTask&standalone=$standalone&updateState=" . get_state_param($state));
            }

            exit;
        }
    } else {
        // Request through Mautic's UI
        $status['updateState'] = get_state_param($status['updateState']);

        throw new \Exception('test');
        // send_response($status);
    }
} else {
    // CLI upgrade

    // We create this file when we've moved M3 files into place. Users have to restart the script then for Symfony commands to finish successfully.
    $m3_phase_2_file = MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'm3_upgrade_pending_phase_2.txt';

    if (!file_exists($m3_phase_2_file)) {
        echo "Doing pre-upgrade checks...\n";
        $preUpgradeCheckResults = runPreUpgradeChecks();
        $preUpgradeCheckErrors = $preUpgradeCheckResults['errors'];
        $preUpgradeCheckWarnings = $preUpgradeCheckResults['warnings'];

        if (count($preUpgradeCheckErrors) > 0) {
            echo "One or more errors occurred during pre-upgrade checks: \n" . implode("\n", $preUpgradeCheckErrors) . "\n";
            exit;
        }

        if (count($preUpgradeCheckWarnings) > 0) {
            echo "One or more warnings occurred during pre-upgrade checks, please run this script with the --ignore-warnings flag to continue: \n" . implode("\n", $preUpgradeCheckWarnings) . "\n";

            $val = getopt('i', ['ignore-warnings']);
            if (empty($val)) {
                exit;
            }
        }

        echo "Starting upgrade...\n";

        sendUpgradeStats('started');

        echo "Applying Mautic 2 migrations, just so we're sure your database is up to date before upgrading. This might take a while, DO NOT ABORT THE SCRIPT!!!\n";

        if (apply_migrations() === false) {
            sendUpgradeStats('failed');
            echo "Something went wrong while applying Mautic 2 migrations. Please try to run app/console doctrine:migrations:migrate --env=prod, to troubleshoot further. Then run this script again.\n";
            exit;
        };

        echo "Downloading Mautic 3...\n";
        list($success, $message) = fetch_updates();

        if (!$success) {
            sendUpgradeStats('failed');
            echo "Failed. $message";
            exit;
        }

        echo "Extracting the update package...\n";

        list($success, $message) = extract_package();
        if (!$success) {
            sendUpgradeStats('failed');
            echo "Failed. $message";
            exit;
        }

        echo "Extracting done!\n";

        echo "Moving Mautic 2 files into mautic-2-backup and moving the Mautic 3 files in place, this might take a while... DO NOT ABORT THE SCRIPT!!!\n";

        /**
         * Move current Mautic 2 files into a temporary directory called "mautic-2-backup-files",
         * then move the Mautic 3 files from "mautic-3-temp-files" to the root directory.
         */
        list($success, $message) = replace_mautic_2_with_mautic_3();

        if (!$success) {
            sendUpgradeStats('failed');
            echo "Failed. $message";
            exit;
        }

        echo "Done!\n";

        echo "Restoring your user data like custom plugins/themes/media from the Mautic 2 installation. This might take a while... DO NOT ABORT THE SCRIPT!!!\n";

        list($success, $message) = restore_user_data();

        if (!$success) {
            sendUpgradeStats('failed');
            echo "Failed. $message";
            exit;
        }

        echo "Done! Your user data has been restored.\n";

        echo "Updating your config/local.php with new settings that were changed/introduced in Mautic 3...\n";

        list($success, $message) = update_local_config();

        if (!$success) {
            sendUpgradeStats('failed');
            echo "Failed. $message";
            exit;
        }

        echo "Done! Your config file has been updated.\n";

        echo "Preparing for phase 2 of the upgrade...\n";

        $result = file_put_contents($m3_phase_2_file, 'READY FOR PHASE 2, RUN php upgrade_v3.php');

        if ($result === false) {
            sendUpgradeStats('failed');
            echo "IMPORTANT: We couldn't prepare for Phase 2 of the upgrade, so we need your help. In the same folder where upgrade_v3.php is located, create a file called m3_upgrade_pending_phase_2.txt (no contents needed), then run this script again.";
            exit;
        }

        echo "IMPORTANT: NOT DONE YET! Due to the large amount of changes in Composer dependencies, we now need to restart the script to continue. We've saved your state, so we'll continue where we left off.\n";
        echo "PLEASE RUN php upgrade_v3.php AGAIN TO START PHASE 2 OF THE UPGRADE!\n";
        exit;
    }

    echo "Welcome to Phase 2 of the Mautic 3 upgrade! We'll continue where we left off.\n";

    echo "Building cache for Mautic 3...\n";

    if (build_cache() === false) {
        sendUpgradeStats('failed');
        echo "Failed. $message";
        exit;
    };

    echo "Done! Cache has been built.\n";

    echo "Applying database migrations for Mautic 3... This might take a while, DO NOT ABORT THE SCRIPT!!!\n";

    if (apply_migrations() === false) {
        sendUpgradeStats('failed');
        echo "Database migrations failed. Please try manually with app/console doctrine:migrations:migrate --env=prod. When database migrations are done, Mautic 3 is ready to use!\n";
        exit;
    };

    echo "Done! Your Mautic 3 installation is ready. Just one more thing:\n";

    echo "Cleaning up installation files that we no longer need...\n";

    // We only use the Phase 2 file in the CLI version of the upgrade, so we'll delete it here...
    unlink($m3_phase_2_file);

    if (cleanup_files() === false) {
        sendUpgradeStats('failed');
        echo "Cleaning up failed. Probably a permissions issue. This is not a big problem; you can still start using Mautic 3 now.\n";
        exit;
    }

    echo "Cleaned up successfully!\n";

    sendUpgradeStats('succeeded');

    echo "We're done! Enjoy using Mautic 3 :)\n";

    // Destroy the upgrade script as we no longer need it.
    unlink(__FILE__);

    exit;
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
        include MAUTIC_APP_ROOT . '/config/paths.php';

        // Include local config to get cache_path
        $localConfig = str_replace('%kernel.root_dir%', MAUTIC_APP_ROOT, $paths['local_config']);

        /** @var array $parameters */
        include $localConfig;

        $localParameters = $parameters;

        //check for parameter overrides
        if (file_exists(MAUTIC_APP_ROOT . '/config/parameters_local.php')) {
            /** @var $parameters */
            include MAUTIC_APP_ROOT . '/config/parameters_local.php';
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
 * @param array $state
 *
 * @return string
 */
function get_state_param(array $state)
{
    return base64_encode(json_encode($state));
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
    // The CA file doesn't exist while we move all Mautic 2 files to a temp folder. We won't need it anyway on most servers nowadays.
    if (file_exists(MAUTIC_ROOT . '/vendor/joomla/http/src/Transport/cacert.pem')) {
        curl_setopt($ch, CURLOPT_CAINFO, MAUTIC_ROOT . '/vendor/joomla/http/src/Transport/cacert.pem');
    }
    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

/**
 * Fetch a list of updates.
 *
 * @return array
 */
function fetch_updates()
{
    global $updateData;

    // Fetch the package
    try {
        download_package();
        return [true, 'OK'];
    } catch (\Exception $e) {
        return [
            false,
            "Could not automatically download the package. Please download " . $updateData['mautic3downloadUrl'] . ", place it in the same directory as this upgrade script, and try again. " .
                "When moving the file, name it mautic-3-update-package.zip`",
        ];
    }
}

/**
 * @throws Exception
 *
 * @return bool
 */
function download_package()
{
    global $updateData;

    // Get the update package URL that we received from Mautic's update server.
    $url = $updateData['mautic3downloadUrl'];
    $target = MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'mautic-3-update-package.zip';

    if (file_exists($target)) {
        return true;
    } elseif (empty($url)) {
        throw new \Exception('Oops! There doesn\'t seem to be a URL that we can download the new Mautic version from. Please try again later.');
    }

    $data = make_request($url);

    if (!file_put_contents($target, $data)) {
        throw new \Exception('Something went wrong while trying to download the upgrade package. Please try again!');
    }

    return true;
}

/**
 * @return int
 */
function extract_package()
{
    $zipFile = MAUTIC_ROOT . '/mautic-3-update-package.zip';

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
 * Move Mautic 2 files to a folder called mautic-2-backup-files, so we keep them in case something goes wrong.
 * Then, move Mautic 3 files from mautic-3-temp-files to the root directory, so Mautic 3 becomes activee
 * 
 * IMPORTANT: needs to happen in 1 step as the script won't be able to get things like Mautic config in between backing up M2 and moving M3 files.
 * 
 * @return array
 */
function replace_mautic_2_with_mautic_3()
{
    /**
     * ==== BACKUP MAUTIC 2 FILES ====
     * We'll backup the original M2 installation in case something goes wrong.
     */
    $errorLog = [];

    if (!file_exists(MAUTIC_BACKUP_FOLDER_ROOT)) {
        mkdir(MAUTIC_BACKUP_FOLDER_ROOT);
    }

    // Only exclude the Mautic 2 backup folder, Mautic 3 upgrade files folder, the current upgrade file and the DB backup file.
    $excludedFilesAndFolders = [MAUTIC_UPGRADE_ROOT, MAUTIC_BACKUP_FOLDER_ROOT, __FILE__, MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'db_backup.sql'];

    $iterator = new DirectoryIterator(MAUTIC_ROOT);

    // Sanity check, make sure there are actually directories here to process
    $dirs = glob(MAUTIC_ROOT . '/*', GLOB_ONLYDIR);

    if (count($dirs)) {
        /** @var DirectoryIterator $directory */
        foreach ($iterator as $directory) {
            // Sanity checks
            if (
                !$directory->isDot()
                // Make sure we DON'T move our excluded files and folders!!
                && !in_array($directory->getPathname(), $excludedFilesAndFolders)
            ) {
                $src  = $directory->getPathname();
                $dest = str_replace(MAUTIC_ROOT, MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'mautic-2-backup-files', $src);

                $result = rename($src, $dest);

                if ($result !== true) {
                    $errorLog[] = $directory->getBasename();
                }
            }
        }

        if (count($errorLog) > 0) {
            return [false, 'One or more files couldn\'t be moved to the Mautic 2 temp folder. This is really, really bad. Errors: ' . implode(',', $errorLog)];
        }

        // We'll continue by moving M3 files into the root directory.
    } else {
        return [false, 'Something went wrong while we tried to move your current Mautic 2 installation to a temporary folder. Please try again.'];
    }

    /**
     * ==== ACTIVATE MAUTIC 3 INSTALLATION ====
     * Now, we'll move the Mautic 3 installation files to the root directory, which will activate the M3 installation.
     */
    $errorLog = [];
    $iterator = new DirectoryIterator(MAUTIC_UPGRADE_ROOT);

    // Sanity check, make sure there are actually directories here to process
    $dirs = glob(MAUTIC_ROOT . '/*', GLOB_ONLYDIR);

    if (count($dirs)) {
        /** @var DirectoryIterator $directory */
        foreach ($iterator as $directory) {
            // Sanity checks
            if (!$directory->isDot()) {
                $src  = $directory->getPathname();
                $dest = str_replace(MAUTIC_UPGRADE_ROOT, MAUTIC_ROOT, $src);

                $result = rename($src, $dest);

                if ($result !== true) {
                    $errorLog[] = $directory->getBasename();
                }
            }
        }

        if (count($errorLog) > 0) {
            return [false, 'One or more files couldn\'t be moved from the Mautic 3 folder to the root folder. This is really, really bad. Errors: ' . implode(',', $errorLog)];
        }

        // Temporarily restore our M2 htaccess as the M3 one doesn't include upgrade_v3.php for whitelisting
        // TODO RESTORE THIS FILE WHEN UPGRADE IS FINISHED
        rename(MAUTIC_ROOT . DIRECTORY_SEPARATOR . '/.htaccess', MAUTIC_ROOT . DIRECTORY_SEPARATOR . '/.htaccess.m3');
        copy(MAUTIC_BACKUP_FOLDER_ROOT . DIRECTORY_SEPARATOR . '/.htaccess', MAUTIC_ROOT . DIRECTORY_SEPARATOR . '/.htaccess');

        // Last step is to restore the config files (otherwise this script can't be loaded with a new step, as the local.php file won't exist)
    } else {
        return [false, 'Something went wrong while we tried to move the new Mautic 3 files to your Mautic root folder. You are in a critical state now where you need to restore things manually. Read more HERE (TODO).'];
    }

    /**
     * ==== RESTORE CONFIGURATION FILES ====
     * Get all config files with *local*.php in the name, we need to restore those in the M3 installation.
     */
    $configFiles = glob(MAUTIC_BACKUP_FOLDER_ROOT . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . '*local*.php');

    foreach ($configFiles as $configFile) {
        $src  = $configFile;
        $dest = str_replace(MAUTIC_BACKUP_FOLDER_ROOT, MAUTIC_ROOT, $src);

        $result = copy($src, $dest);

        if ($result !== true) {
            $errorLog[] = $configFile;
        }
    }

    if (count($errorLog) > 0) {
        return [false, 'The old configuration files couldn\'t be copied from the old Mautic 2 folder into the new folder. This is really, really bad. Errors: ' . implode(',', $errorLog)];
    }    

    return [true, 'OK'];
}

/**
 * Restore user data from the Mautic 2 installation in the new Mautic 3 installation.
 */
function restore_user_data()
{
    $errorLog = [];

    if (!file_exists(MAUTIC_BACKUP_FOLDER_ROOT)) {
        return [false, 'The Mautic 2 backup files folder (mautic-2-backup-files) doesn\'t seem to exist. This is really, really bad.'];
    }

    /**
     * ==== RESTORE PLUGINS AND THEMES ====
     * Move over all custom plugins/themes from the old M2 installation to the "new" M3 one.
     * We do this by checking whether a plugin/theme folder already exists, and if not, we copy it over.
     * This way we prevent conflicts from happening with Mautic's core plugins/themes.
     */
    $foldersToRestore = ['plugins', 'themes'];

    foreach ($foldersToRestore as $folder) {
        $iterator = new DirectoryIterator(MAUTIC_BACKUP_FOLDER_ROOT . DIRECTORY_SEPARATOR . $folder);

        /** @var DirectoryIterator $directory */
        foreach ($iterator as $directory) {
            // Sanity checks
            if (!$directory->isDot()) {
                $src  = $directory->getPathname();
                $dest = str_replace(MAUTIC_BACKUP_FOLDER_ROOT, MAUTIC_ROOT, $src);

                if (!file_exists($dest)) {
                    if ($directory->isDir()) {
                        $result = copy_directory($src, $dest);
                    } else {
                        $result = copy($src, $dest);
                    }

                    if ($result !== true) {
                        $errorLog[] = $directory->getBasename();
                    }
                }
            }
        }

        if (count($errorLog) > 0) {
            return [false, 'One or more plugin or themes files couldn\'t be moved from the Mautic 3 folder to the root folder. This is really, really bad. Errors: ' . implode(',', $errorLog)];
        }
    }

    /**
     * ==== RESTORE MEDIA FILES ====
     * If a user has any custom css/dashboards/files/images/js, we need to copy them over.
     * We do this by checking whether a plugin/theme folder already exists, and if not, we copy it over.
     * This way we prevent conflicts from happening with Mautic's core plugins/themes.
     */
    $mediaFoldersToRestore = ['css', 'dashboards', 'files', 'images', 'js'];

    foreach ($mediaFoldersToRestore as $folder) {
        $iterator = new DirectoryIterator(MAUTIC_BACKUP_FOLDER_ROOT . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $folder);

        /** @var DirectoryIterator $directory */
        foreach ($iterator as $directory) {
            // Sanity checks
            if (!$directory->isDot()) {
                $src  = $directory->getPathname();
                $dest = str_replace(MAUTIC_BACKUP_FOLDER_ROOT, MAUTIC_ROOT, $src);

                if (!file_exists($dest)) {
                    if ($directory->isDir()) {
                        $result = copy_directory($src, $dest);
                    } else {
                        $result = copy($src, $dest);
                    }

                    if ($result !== true) {
                        $errorLog[] = $directory->getBasename();
                    }
                }
            }
        }

        if (count($errorLog) > 0) {
            return [false, 'One or more media files couldn\'t be moved from the Mautic 3 folder to the root folder. This is really, really bad. Errors: ' . implode(',', $errorLog)];
        }
    }

    return [true, 'OK'];
}

/**
 * Updates config/local.php with new keys/values that were changed in M3:
 * https://github.com/mautic/mautic/blob/3.x/UPGRADE-3.0.md#configuration
 */
function update_local_config()
{
    $filename = MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'local.php';
    require $filename;

    if (array_key_exists('api_rate_limiter_cache', $parameters)) {
        if (array_key_exists('type', $parameters['api_rate_limiter_cache'])) {
            if ($parameters['api_rate_limiter_cache']['type'] === 'file_system') {
                unset($parameters['api_rate_limiter_cache']['type']);
                $parameters['api_rate_limiter_cache']['adapter'] = 'cache.adapter.filesystem';
            }
        }
    }

    if (array_key_exists('mailer_transport', $parameters)) {
        if ($parameters['mailer_transport'] === 'mail') {
            $parameters['mailer_transport'] = 'sendmail';
        }
    }

    if (array_key_exists('system_update_url', $parameters)) {
        if ($parameters['system_update_url'] === 'https://updates.mautic.org/index.php?option=com_mauticdownload&task=checkUpdates') {
            $parameters['system_update_url'] = 'https://api.github.com/repos/mautic/mautic/releases';
        }
    }

    // Write updated config to local.php
    $result = file_put_contents($filename, "<?php\n" . '$parameters = ' . var_export($parameters, true) . ';');

    if ($result === false) {
        return [false, 'Couldn\'t update configuration file with new api_rate_limiter_cache value.'];
    }

    return [true, 'OK'];
}

/**
 * @param       $command
 * @param array $args
 *
 * @return bool
 *
 * @throws Exception
 */
function run_symfony_command($command, array $args)
{
    static $application;

    require_once MAUTIC_APP_ROOT . '/autoload.php';
    require_once MAUTIC_APP_ROOT . '/AppKernel.php';

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
        if (!@mkdir($dest, 0777, true)) {
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
    while (false !== ($file = readdir($dh))) {
        $sfid = $src . '/' . $file;
        $dfid = $dest . '/' . $file;

        switch (filetype($sfid)) {
            case 'dir':
                if ('.' != $file && '..' != $file) {
                    $ret = copy_directory($sfid, $dfid);

                    if (true !== $ret) {
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
 * Build the cache.
 *
 * @return bool
 */
function build_cache()
{
    // Rebuild the cache
    return run_symfony_command('cache:clear', ['--no-interaction', '--env=prod', '--no-debug', '--no-warmup']);
}

/**
 * Apply all migrations.
 *
 * @return bool
 */
function apply_migrations()
{
    $minExecutionTime = 300;
    $maxExecutionTime = (int) ini_get('max_execution_time');
    if ($maxExecutionTime > 0 && $maxExecutionTime < $minExecutionTime) {
        ini_set('max_execution_time', $minExecutionTime);
    }

    return run_symfony_command('doctrine:migrations:migrate', ['--no-interaction', '--env=prod', '--no-debug']);
}

/**
 * Send Mautic 3 upgrade stats to our stats server.
 * 
 * @param string $status
 * 
 * @return void
 */
function sendUpgradeStats($status)
{
    global $localParameters;

    if (!in_array($status, ['started', 'failed', 'succeeded'])) {
        throw new \Exception('Invalid upgrade status given. Must be one of started, failed, succeeded');
    }

    try {
        if (file_exists(MAUTIC_ROOT . '/app/version.txt')) {
            // Before the 3.x upgrade, we can get the Mautic version from /app/version.txt.
            $version = file_get_contents(MAUTIC_ROOT . '/app/version.txt');
        } else if (file_exists(MAUTIC_ROOT . '/app/release_metadata.json')) {
            // After the 3.x upgrade we can get the Mautic version from the /app/release_metadata.json file.
            $data = file_get_contents(MAUTIC_ROOT . '/app/release_metadata.json');
            $version = json_decode($data, true)['version'];
        } else if (file_exists(MAUTIC_BACKUP_FOLDER_ROOT . '/app/version.txt')) {
            // If for some reason we can't get the version from one of two places above,
            // we should still be able to get the old Mautic version from /app/version.txt in the backup folder.
            $version = file_get_contents(MAUTIC_BACKUP_FOLDER_ROOT . '/app/version.txt');
        } else {
            $version = null;
        }

        // Generate a unique instance ID for the site
        $instanceId = hash('sha1', $localParameters['secret_key'] . 'Mautic' . $localParameters['db_driver']);

        $data = [
            'application'   => 'Mautic',
            'version'       => $version,
            'phpVersion'    => PHP_VERSION,
            'dbDriver'      => $localParameters['db_driver'],
            'serverOs'      => php_uname('s') . ' ' . php_uname('r'),
            'instanceId'    => $instanceId,
            'upgradeStatus' => $status,
        ];

        // TODO update this URL with the actual stats URL
        make_request('http://ddev-statsapp-web/mautic3upgrade/send', 'post', $data);
    } catch (\Exception $exception) {
        // Not so concerned about failures here, move along
    }
}

/**
 * Cleanup some of our upgrade files after the upgrade took place.
 * 
 * @return bool
 */
function cleanup_files()
{
    $failedChanges = [];

    if (file_exists(MAUTIC_ROOT . DIRECTORY_SEPARATOR . '.htaccess.m3')) {
        $status = rename(MAUTIC_ROOT . DIRECTORY_SEPARATOR . '.htaccess.m3', MAUTIC_ROOT . DIRECTORY_SEPARATOR . '.htaccess');
        if ($status === false) $failedChanges[] = 'htaccess';
    }

    if (file_exists(MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'critical_migrations.txt')) {
        $status = unlink(MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'critical_migrations.txt');
        if ($status === false) $failedChanges[] = 'critical_migrations';
    }

    if (file_exists(MAUTIC_UPGRADE_ROOT)) {
        $status = recursive_remove_directory(MAUTIC_UPGRADE_ROOT);
        if ($status === false) $failedChanges[] = 'mautic-3-temp-files';
    }

    // Rename the mautic-2-backup-files folder to one with a random hash to prevent public access
    if (file_exists(MAUTIC_BACKUP_FOLDER_ROOT)) {
        $hash = bin2hex(random_bytes(16));
        $newFolderPath = str_replace('mautic-2-backup-files', 'mautic-2-backup-files-' . $hash, MAUTIC_BACKUP_FOLDER_ROOT);
        $status = rename(MAUTIC_BACKUP_FOLDER_ROOT, $newFolderPath);
        if ($status === false) $failedChanges[] = 'mautic-2-backup-files';
    }

    // Rename the db_backup.sql file to one with a random hash to prevent public access
    if (file_exists(MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'db_backup.sql')) {
        $hash = bin2hex(random_bytes(16));
        $status = rename(MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'db_backup.sql', MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'db_backup_' . $hash . '.sql');
        if ($status === false) $failedChanges[] = 'db_backup.sql';
    }

    // NOTE: we leave the mautic-2-backup-files-$HASH folder and db_backup_$HASH.sql file as-is in case something still doesn't work as expected.

    if (count($failedChanges) === 0) {
        return true;
    }

    return false;
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
    if ('/' == substr($directory, -1)) {
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
            if ('.' != $item && '..' != $item) {
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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <!--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">-->
  </head>
  <body>
    <div class="container" style="padding: 25px;">
        $content
    </div>
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
  </body>
</html>
HTML;

    echo $html;

    exit;
}
