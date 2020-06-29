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
@ini_set('display_errors', 'Off');

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    define('IS_WINDOWS', true);
}

define('IN_CLI', php_sapi_name() === 'cli');

// Enable PHP errors only in CLI
if (IN_CLI === true) {
    @ini_set('display_errors', 'STDOUT');
}

// Try to set max_execution_time to 240 if it's lower currently. If this fails we'll throw an error later in the preUpgradeChecks.
$minExecutionTime = 240;

if (defined('IS_WINDOWS')) {
    // Cache creation is slower on Windows; set an even higher minExecutionTime.
    $minExecutionTime = 600;
}

$maxExecutionTime = (int) ini_get('max_execution_time');
if ($maxExecutionTime > 0 && $maxExecutionTime < $minExecutionTime) {
    @ini_set('max_execution_time', $minExecutionTime);
}

// Try to set the memory_limit to 256M if it's lower currently. If this fails we'll throw an error later in the preUpgradeChecks.
$minMemoryLimit = '256M';

if (defined('IS_WINDOWS')) {
    // In our testing, during CLI upgrades, 256M was not enough on Windows
    $minMemoryLimit = '512M';
}

$minMemoryLimitBytes = returnBytes($minMemoryLimit);

$memoryLimit = ini_get('memory_limit');
$memoryLimitBytes = returnBytes($memoryLimit);

if ($memoryLimitBytes > 0 && $memoryLimitBytes < $minMemoryLimitBytes) {
    @ini_set('memory_limit', $minMemoryLimit);
}

date_default_timezone_set('UTC');

define('MAUTIC_MINIMUM_PHP', '7.2.21');
define('MAUTIC_MAXIMUM_PHP', '7.3.999');

// We can only run this script in standalone mode, either in the browser or in CLI, due to extensive backwards incompatbile changes.
$standalone = 1;
$task       = getVar('task');

define('MAUTIC_ROOT', __DIR__);
define('MAUTIC_APP_ROOT', MAUTIC_ROOT . '/app');
define('MAUTIC_UPGRADE_FOLDER_NAME', 'mautic-3-temp-files');
define('MAUTIC_UPGRADE_ROOT', MAUTIC_ROOT . DIRECTORY_SEPARATOR . MAUTIC_UPGRADE_FOLDER_NAME);
// This value always needs to contain mautic-2-backup-files as we replace that with a unique hashed name later on!
define('MAUTIC_BACKUP_FOLDER_ROOT', MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'mautic-2-backup-files');
define('MAUTIC_POST_UPGRADE_BACKUP_FOLDER_ROOT', MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'mautic-2-backup-files');
define('POST_UPGRADE_BACKUP_FILES_REMOVAL_PENDING_FILE', MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'm3_upgrade_backup_files_not_removed_yet.txt');

// Get local parameters
$localParameters = get_local_config();

if (file_exists(POST_UPGRADE_BACKUP_FILES_REMOVAL_PENDING_FILE)) {
    if (IN_CLI) {
        echo "Mautic 3 upgrade complete. Do you want to remove the backup files? We recommend first checking in a browser whether Mautic 3 works as expected. Type \"yes\" to remove: ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) != "yes") {
            echo "Ok, we won't do anything for now. "
                . "Please note that upgrade_v3.php will remain (publicly) available until you choose to remove the backup files using this script! "
                . "Run this script again to be prompted to delete backup files.\n";
            exit;
        }
        fclose($handle);
        echo "\n"; 
        echo "Thank you, continuing...\n";

        removeBackupFilesAndExit();
    } else {
        if (!isset($_GET['confirmDeleteBackup'])) {
            html_body(
                "<p>Mautic 3 upgrade complete. Do you want to remove the backup files? We recommend first checking in a browser whether Mautic 3 works as expected.</p>"
                . "<p>Please note that upgrade_v3.php will remain (publicly) available until you choose to remove the backup files using this script! "
                . "Run this script again to be prompted to delete backup files.</p>
                
                <a class=\"btn btn-primary\" href=\"?confirmDeleteBackup\">Click here to remove the backup files and continue to Mautic</a>",
                true
            );
            exit;
        } else {
            removeBackupFilesAndExit();
        }
    }

    // Additional exit, just to be sure
    exit;
}

if (!file_exists(MAUTIC_UPGRADE_ROOT)) {
    mkdir(MAUTIC_UPGRADE_ROOT);
}

// This value always needs to contain upgrade_log.txt as we replace that with a unique hashed name later on!
define('UPGRADE_LOG_FILE', MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'upgrade_log.txt');
define('POST_UPGRADE_LOG_FILE', MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'upgrade_log.txt');

if (!file_exists(UPGRADE_LOG_FILE)) {
    file_put_contents(UPGRADE_LOG_FILE, '');
}

if (isset($localParameters['cache_path'])) {
    $cacheDir = str_replace('%kernel.root_dir%', MAUTIC_APP_ROOT, $localParameters['cache_path'] . '/prod');
} else {
    $cacheDir = MAUTIC_APP_ROOT . '/cache/prod';
}
define('MAUTIC_CACHE_DIR', $cacheDir);

// Data we fetch from a special JSON file to control upgrade behavior, like e.g. the download URL.
$data = make_request('https://updates.mautic.org/upgrade-configs/m2-to-m3.json', 'GET');
$updateData = json_decode($data, true);

/**
 * Run pre-upgrade checks. Returns array with keys "warnings" (dismissable) and "errors" (block upgrading)
 * 
 * ==== PRE-UPGRADE CHECKS ====
 * 
 * To ensure a smooth upgrade to 3.0, we check a few things beforehand:
 * - PHP version >= 7.2.21 and <= 7.3999
 * - Current database driver = pdo_mysql or mysqli (get from existing Mautic config file)
 * - MySQL version > 5.7.14 or MariaDB version > 10.2
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
    global $minExecutionTime;
    global $minMemoryLimit;
    global $minMemoryLimitBytes;

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

    // Check database connection, database version and amount of contacts.
    if (!in_array($localParameters['db_driver'], ['pdo_mysql', 'mysqli'])) {
        $preUpgradeErrors[] = 'Your database driver is not pdo_mysql or mysqli, which are the only drivers that Mautic supports. Please change your database driver (config/local.php)!';
    }

    $mysqli = new mysqli($localParameters['db_host'], $localParameters['db_user'], $localParameters['db_password'], $localParameters['db_name'], $localParameters['db_port']);

    if (mysqli_connect_errno()) {
        $preUpgradeErrors[] = 'Could not connect to your database. Please try again or fix your Mautic settings.';
    } else {
        $dbVersion = $mysqli->server_version;

        if (!(($dbVersion >= 50714 && $dbVersion < 100000) || ($dbVersion >= 100200))) {
            $preUpgradeErrors[] = 'Your MySQL/MariaDB version is not supported. You need at least MySQL 5.7.14 or MariaDB 10.2 in order to run Mautic 3.';
        } else {
            $db_prefix = !empty($localParameters['db_table_prefix']) ? $localParameters['db_table_prefix'] : '';
            $result = $mysqli->query("SELECT COUNT(id) AS leads FROM " . $db_prefix . "leads");

            if (empty($result)) {
                $preUpgradeWarnings[] = 'Could not determine the amout of contacts in your system. If you have more than 10000 contacts, please use the CLI (command line) to perform the upgrade.';
            } else {
                $count_leads = intval($result->fetch_row()[0]);

                if ($count_leads >= 10000) {
                    $preUpgradeWarnings[] = 'You have 10000 or more contacts in your system. We recommend upgrading by using the CLI (command line) for the best performance and stability.';
                }
            }
        }

        $mysqli->close();
    }

    // Check if there is a custom configuration for api_rate_limiter_cache in the local.php file (see https://github.com/mautic/mautic/blob/3.x/UPGRADE-3.0.md#configuration)
    if (
        !empty($localParameters['api_rate_limiter_cache'])
        && !empty($localParameters['api_rate_limiter_cache']['type'])
        && $localParameters['api_rate_limiter_cache']['type'] !== 'file_system'
    ) {
        $preUpgradeWarnings[] = 'You seem to have a custom configuration for the api_rate_limiter_cache setting in local.php. '
            . 'This is an advanced feature and we can only support the default file_system type during migration. '
            . 'WE WILL UPDATE YOUR CUSTOM CONFIGURATION TO THE DEFAULT file_system ADAPTER TO PREVENT CONFLICTS. '
            . 'If you want to keep your custom configuration, you can manually update it after the migration. '
            . 'Please check https://github.com/mautic/mautic/blob/3.x/UPGRADE-3.0.md#configuration for details.';
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

    // Check if there are items in the spool/default folder
    $spoolFolder = str_replace(
        '%kernel.root_dir%',
        MAUTIC_APP_ROOT,
        $localParameters['mailer_spool_path']
    );
    $spoolFolder .= DIRECTORY_SEPARATOR . 'default';

    if (file_exists($spoolFolder)) {
        $data = scandir($spoolFolder);

        // We always have . and .. in the array, so only raise a warning if the count > 2
        if (count($data) > 2) {
            $preUpgradeWarnings[] = 'It looks like there are items in your spool/default foler, which might mean that there are still emails pending to be sent. If you have access to the command line, run "php /path/to/mautic/app/console mautic:email:process --env=prod" to send them before proceeding.';
        }
    }

    // Check free disk space.
    $freeDiskSpace = disk_free_space(MAUTIC_ROOT);
    $mauticRootFolderSize = 0;

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(MAUTIC_ROOT)) as $file) {
        $mauticRootFolderSize += $file->getSize();
    }

    if (empty($freeDiskSpace) || $mauticRootFolderSize === 0) {
        $preUpgradeWarnings[] = 'We cannot seem to check your free disk space or current Mautic installation folder size. Please ensure your free disk space is at least 2x of your current Mautic installation, just to be sure.';
    } else {
        if (($mauticRootFolderSize * 2) > $freeDiskSpace) {
            $preUpgradeWarnings[] = 'You don\'t seem to have enough disk space for the upgrade. Just to be sure, we\'d like to have 2x the size of the current Mautic installation. Current free disk space is ' . $freeDiskSpace . ' bytes, Mautic installation size is  ' . $mauticRootFolderSize . ' bytes.';
        }
    }

    // Check Mautic version
    if (file_exists(MAUTIC_APP_ROOT . '/release_metadata.json')) {
        $preUpgradeErrors[] = 'You already seem to be running Mautic 3.0.0 or newer, so this upgrade script is not relevant to you anymore. Aborting.';
    } elseif (file_exists(MAUTIC_APP_ROOT . '/version.txt')) {
        // Check if we have the required Mautic version 2.16.3 prior to upgrading.
        $version = file_get_contents(MAUTIC_APP_ROOT . '/version.txt');
        $version = str_replace("\n", "", $version);

        if (!version_compare($version, '2.16.2', '>')) {
            $preUpgradeErrors[] = 'You need to have at least Mautic 2.16.3 installed, which supports upgrading to 3.0. Please update to 2.16.3 first.';
        }
    } else {
        $preUpgradeErrors[] = 'We can\'t seem to detect your current Mautic version. Make sure you have a version.txt file in your app folder.';
    }

    // Check PHP's max_execution_time
    $maxExecutionTime = ini_get('max_execution_time');

    if ($maxExecutionTime > 0 && $maxExecutionTime < $minExecutionTime) {
        $preUpgradeErrors[] = 'PHP max_execution_time needs to be at least ' . $minExecutionTime . ' seconds (' . round($minExecutionTime / 60, 2) . ' minutes) to allow for a successful upgrade (current value is ' . $maxExecutionTime . ' seconds). We tried setting it to this value but weren\'t able to do so. Please contact your host to set this value to ' . $minExecutionTime . ' seconds or higher.';
    }

    // Check if mysqldump is available on the system for creating a DB backup.
    if (!function_exists('exec')) {
        $preUpgradeWarnings[] = 'We can\'t make a database backup for you due to restrictions on your system. Only continue if you have your own database backup available!';
    } else {
        if (is_mysqldump_available() === false) {
            $preUpgradeWarnings[] = 'We can\'t work with the database backup mechanism called "mysqldump" on your system. Only continue if you have your own database backup available!';
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

    // Check if there are custom plugins installed
    list($areCustomPluginsInstalled, $customPluginsString) = are_custom_plugins_installed();

    if ($areCustomPluginsInstalled === true) {
        $preUpgradeWarnings[] = 'It looks like you have one or more custom plugins installed (' . $customPluginsString . '). '
            . 'Please make sure they are compatible with Mautic 3, otherwise temporarily disable them! '
            . 'Incompatible plugins will very likely cause errors in the upgrade process.';
    }

    // Get the amount of available database migrations
    list($success, $data) = get_available_migrations();

    if ($success === false) {
        $preUpgradeWarnings[] = 'We couldn\'t reliably detect the amount of available database migrations. Please proceed with caution!';
    } else {
        if (!isset($data[2]) || !isset($data[2][0])) {
            $preUpgradeWarnings[] = 'We couldn\'t reliably detect the amount of available database migrations. Please proceed with caution!';
        }
    }
    
    // Cache creation is a lot slower on Windows; inform the user about this.
    if (defined('IS_WINDOWS')) {
        $preUpgradeWarnings[] = 'It looks like you\'re running Mautic on Windows. '
            . 'The "creating cache" step is very slow on this platform and it might look like it hangs; please be patient in this case.';
    }

    // Check PHP's memory_limit
    $memoryLimit = ini_get('memory_limit');
    $memoryLimitBytes = returnBytes($memoryLimit);

    if ($memoryLimitBytes > 0 && $memoryLimitBytes < $minMemoryLimitBytes) {
        $preUpgradeErrors[] = 'PHP memory_limit needs to be at least ' . $minMemoryLimit . ' to allow for a successful upgrade (current value is ' . $memoryLimit . '). We tried setting it to this value but weren\'t able to do so. Please contact your host to set this value to ' . $minMemoryLimit . ' or higher.';
    }

    // Check if a composer.json file is present. If it is, it might very likely be the case that the user is using a version cloned from GitHub
    if (file_exists(__DIR__ . '/composer.json')) {
        $preUpgradeWarnings[] = 'You seem to have installed Mautic by cloning from GitHub. '
            . 'We don\'t support upgrading with the upgrade script in this scenario. '
            . 'Proceed at your own risk or reinstall with the official package at <a target="_blank" href="https://github.com/mautic/mautic/releases/tag/2.16.3">https://github.com/mautic/mautic/releases/tag/2.16.3</a>';
    }

    return [
        'warnings' => $preUpgradeWarnings,
        'errors' => $preUpgradeErrors
    ];
}

// Web request upgrade
if (!IN_CLI) {
    $request         = explode('?', $_SERVER['REQUEST_URI'])[0];
    $url             = "//{$_SERVER['HTTP_HOST']}{$request}";
    $query    = '';
    $maxCount = (!empty($standalone)) ? 25 : 5;
    $apiTask = !empty($_GET['apiTask']) ? $_GET['apiTask'] : '';

    switch ($apiTask) {
        case '':
            // If no specific API task is given, we render our HTML.
            logUpdateStart();
            html_body('');
            exit;

        case 'preUpgradeChecks':
            writeToLog('Running pre-upgrade checks...');
            $preUpgradeCheckResults = runPreUpgradeChecks();
            $preUpgradeCheckErrors = $preUpgradeCheckResults['errors'];
            $preUpgradeCheckWarnings = $preUpgradeCheckResults['warnings'];

            $html = "";

            $generalRemarks = "<strong>IMPORTANT: you will need to update your cron jobs from app/console to bin/console after the upgrade.<br>
            You can already change them now if you want. For instructions, please read our <a target=\"_blank\" href=\"http://mau.tc/m3-upgrade-getting-started\">Mautic 3: Getting Started guide</a>.<br><br>
            <u>It's strongly recommended to have a backup before you start upgrading!</u></strong><br><br>";

            if (count($preUpgradeCheckErrors) > 0) {
                $logData = "One or more errors occurred in the pre-upgrade checks:";
                $html .= '<h3>Whoops! You\'re not ready for Mautic 3 (yet)</h3>
                <p>The following <strong style="color: red">errors</strong> occurred while checking system compatibility:</p>
                <ul style="text-align: left">';
                foreach ($preUpgradeCheckErrors as $error) {
                    $logData .= "\n- " . $error;
                    $html .= '<li>' . $error . '</li>';
                }
                $html .= '</ul>';

                $logData .= "\n";
                writeToLog($logData);
            }

            if (count($preUpgradeCheckWarnings) > 0) {
                $logData = "One or more warnings occurred in the pre-upgrade checks:";
                $html .= '<p>The following <strong style="color: orange">warnings</strong> occurred while checking system compatibility:</p><ul style="text-align: left">';
                foreach ($preUpgradeCheckWarnings as $warning) {
                    $logData .= "\n- " . $warning;
                    $html .= '<li>' . $warning . '</li>';
                }
                $html .= '</ul>';
                $logData .= "\n";
                writeToLog($logData);
            }

            if (count($preUpgradeCheckErrors) === 0 && count($preUpgradeCheckWarnings) > 0) {
                // The checkbox doesn't do anything, but is just there to make users aware that they are doing risky things.
                $html .= $generalRemarks . '
                <div style="text-align: left">
                    <input type="checkbox" id="forceUpgradeStart" /> <label for="forceUpgradeStart">Yes, I am aware of the warnings above and still want to proceed with the upgrade.</label>
                </div><br />
                <button class="btn btn-primary" id="startUpgradeButton">Start the upgrade</button>';
            }

            if (count($preUpgradeCheckErrors) === 0 && count($preUpgradeCheckWarnings) === 0) {
                writeToLog("All pre-upgrade checks passed successfully.");
                $html .= "<h3>Ready to upgrade ✅</h3>
                <br />Your system is compatible with Mautic 3!<br>Do not refresh or stop the process. This may take several minutes.<br><br>
                " . $generalRemarks . "
                <button class=\"btn btn-primary\" id=\"startUpgradeButton\">Start the upgrade</button>";
            }

            outputJSON('success', $html);

        case 'startUpgrade':
            writeToLog("Starting the upgrade...");
            sendUpgradeStats('started');
            outputJSON('success', 'OK', 'backupDatabase', 'Backing up the database if we can...');

        case 'backupDatabase':
            // Only do the backup if mysqldump is available, otherwise skip this step.
            if (is_mysqldump_available() === true) {
                writeToLog("Running database backup...");
                list($success, $message) = backup_database();

                if (!$success) {
                    throwErrorAndWriteToLog(
                        "ERR_DATABASE_BACKUP_FAILED",
                        "Database backup failed. Your Mautic 2 installation is intact, so you can safely restart the upgrade. Error from mysqldump: " . $message
                    );
                }
                writeToLog("Successfully backed up database.");
            } else {
                writeToLog("Skipping database backup due to mysqldump not being available.");
            }

            outputJSON('success', 'OK', 'applyV2Migrations', 'Applying Mautic 2 database migrations to ensure all migrations are in place prior to upgrade...');

        case 'applyV2Migrations':
            writeToLog("Getting the amount of available Mautic 2 database migrations...");
            // Get the amount of available database migrations
            list($success, $data) = get_available_migrations();

            if ($success === false) {
                throwErrorAndWriteToLog(
                    "ERR_MAUTIC_2_MIGRATIONS_IDENTIFICATION_FAILED",
                    'We couldn\'t reliably detect the amount of available database migrations. Please try again by refreshing this page.'
                );
            } else {
                if (isset($data[2]) && isset($data[2][0])) {
                    $availableMigrations = intval($data[2][0]);

                    if ($availableMigrations > 0) {
                        writeToLog("Applying Mautic 2 database migrations, " . $availableMigrations . " to go...");

                        // Apply migrations on the 2.x branch just so we're sure that we have all migrations in place.
                        list($success, $output) = apply_single_migration();

                        if ($success === false) {
                            throwErrorAndWriteToLog(
                                "ERR_MAUTIC_2_MIGRATIONS_FAILED",
                                "Oh no! While preparing the upgrade, the so-called 'database migrations' for Mautic 2 have failed. "
                                    . "Command output: " . $output
                            );
                        };

                        $availableMigrations--;

                        if ($availableMigrations > 0) {
                            // There are still more than 0 available migrations left, so we need to re-run this script...
                            outputJSON(
                                'success',
                                'OK',
                                'applyV2Migrations',
                                'Applying Mautic 2 database migrations to ensure all migrations are in place prior to upgrade... ' . $availableMigrations . ' to go...'
                            );
                        } else {
                            writeToLog("All Mautic 2 migrations applied successfully.");
                            outputJSON('success', 'OK', 'fetchUpdates', 'Downloading Mautic 3 upgrade package...');
                        }
                    } else {
                        writeToLog("No available database migrations found. On to the next step...");
                        outputJSON('success', 'OK', 'fetchUpdates', 'Downloading Mautic 3 upgrade package...');
                    }
                } else {
                    throwErrorAndWriteToLog(
                        "ERR_MAUTIC_2_MIGRATIONS_IDENTIFICATION_FAILED",
                        'We couldn\'t reliably detect the amount of available database migrations. Please try again by refreshing this page.'
                    );
                }
            }

            break;

        case 'fetchUpdates':
            writeToLog("Downloading Mautic 3 upgrade package...");
            list($success, $message) = fetch_updates();

            if (!$success) {
                throwErrorAndWriteToLog(
                    "ERR_DOWNLOAD_UPGRADE_PACKAGE_FAILED",
                    "Downloading the Mautic 3 upgrade package has failed: " . $message
                );
            }

            writeToLog("Successfully downloaded Mautic 3 upgrade package.");
            outputJSON('success', 'OK', 'extractUpdate', 'Extracting Mautic 3 files...');

        case 'extractUpdate':
            writeToLog("Extracting Mautic 3 files...");
            list($success, $message) = extract_package();

            if (!$success) {
                throwErrorAndWriteToLog(
                    "ERR_EXTRACT_UPGRADE_PACKAGE_FAILED",
                    "Error while extracting Mautic 3 files: " . $message
                );
            }

            writeToLog("Mautic 3 files extracted successfully.");

            outputJSON(
                'success',
                'OK',
                'moveMautic2and3Files',
                'Moving Mautic 2 files to mautic-2-backup-files folder, then moving Mautic 3 files from mautic-3-temp-files to the root folder...'
            );
            break;

        case 'moveMautic2and3Files':
            /**
             * Move current Mautic 2 files into a temporary directory called "mautic-2-backup-files",
             * then move the Mautic 3 files from "mautic-3-temp-files" to the root directory.
             */
            writeToLog("Moving Mautic 2 files to mautic-2-backup-files folder, then moving Mautic 3 files from mautic-3-temp-files to the root folder...");
            list($success, $message) = replace_mautic_2_with_mautic_3();

            if (!$success) {
                throwErrorAndWriteToLog(
                    "ERR_MOVE_MAUTIC_2_AND_3_FILES",
                    "Error while moving Mautic 2 or 3 files: " . $message
                );
            }

            writeToLog("Successfully moved Mautic 3 files into place!");

            outputJSON(
                'success',
                'OK',
                'updateLocalConfig',
                'Updating config/local.php with new configuration parameters...'
            );

        case 'updateLocalConfig':
            // Update config/local.php with updated keys.
            writeToLog("Updating config/local.php with new configuration parameters...");
            list($success, $message) = update_local_config();

            if (!$success) {
                throwErrorAndWriteToLog(
                    "ERR_UPDATE_LOCAL_CONFIG",
                    "Failed updating your configuration in config/local.php: " . $message
                );
            }

            writeToLog("Successfully updated config/local.php.");

            outputJSON('success', 'OK', 'applyMigrations', 'Applying Mautic 3 database migrations... This might take a while!');

        case 'applyMigrations':
            writeToLog("Getting the amount of available Mautic 3 database migrations... This might take a while!");
            // Get the amount of available database migrations
            list($success, $data) = get_available_migrations();

            if ($success === false) {
                throwErrorAndWriteToLog(
                    "ERR_MAUTIC_3_MIGRATIONS_IDENTIFICATION_FAILED",
                    'We couldn\'t reliably detect the amount of available database migrations. Please try again by refreshing this page.'
                );
            } else {
                if (isset($data[2]) && isset($data[2][0])) {
                    $availableMigrations = intval($data[2][0]);

                    if ($availableMigrations > 0) {
                        writeToLog("Applying Mautic 3 database migrations, " . $availableMigrations . " to go...");

                        // Apply migrations one by one.
                        list($success, $output) = apply_single_migration();

                        if ($success === false) {
                            throwErrorAndWriteToLog(
                                "ERR_MAUTIC_3_MIGRATIONS_FAILED",
                                "Oops! We couldn't run the so-called 'database migrations' for Mautic 3. These are crucial for the upgrade to finish, so we can't proceed. "
                                    . "Command output: " . $output
                            );
                        };

                        $availableMigrations--;

                        if ($availableMigrations > 0) {
                            // There are still more than 0 available migrations left, so we need to re-run this script...
                            outputJSON(
                                'success',
                                'OK',
                                'applyMigrations',
                                'Applying Mautic 3 database migrations... ' . $availableMigrations . ' to go...'
                            );
                        } else {
                            writeToLog("Successfully applied Mautic 3 database migrations");
                            outputJSON('success', 'OK', 'restoreUserData', 'Restoring user data (plugins/themes/media) from Mautic 2 installation...');
                        }
                    } else {
                        writeToLog("No available database migrations found. On to the next step...");
                        outputJSON('success', 'OK', 'restoreUserData', 'Restoring user data (plugins/themes/media) from Mautic 2 installation...');
                    }
                } else {
                    throwErrorAndWriteToLog(
                        "ERR_MAUTIC_3_MIGRATIONS_IDENTIFICATION_FAILED",
                        'We couldn\'t reliably detect the amount of available database migrations. Please try again by refreshing this page.'
                    );
                }
            }

            break;

        case 'restoreUserData':
            writeToLog("Restoring user data (plugins/themes/media) from Mautic 2 installation...");
            // Restore user data like plugins/themes/media from the original Mautic 2 installation to the "fresh" M3 installation
            list($success, $message) = restore_user_data();

            if (!$success) {
                throwErrorAndWriteToLog(
                    "ERR_RESTORE_USER_DATA_FAILED",
                    "Failed to restore user data from Mautic 2 installation: " . $message
                );
            }

            writeToLog("Successfully restored user data from Mautic 2 installation.");

            outputJSON('success', 'OK', 'cleanupFiles', 'Cleaning up after ourselves...');

        case 'cleanupFiles':
            // Cleanup some of our installation files that we no longer need.
            writeToLog("Cleanup upgrade files...");
            if (cleanup_files() === false) {
                throwErrorAndWriteToLog(
                    "ERR_CLEANUP_FILES",
                    "Oops! We tried cleaning up after ourselves, but it didn\'t work as expected. You should be able to start using Mautic 3 now, though."
                );
            }

            writeToLog("Successfully cleaned up upgrade files.");

            outputJSON('success', 'OK', 'buildCache', 'Preparing Mautic 3 cache...');

        case 'buildCache':
            writeToLog("Building cache for Mautic 3...");
            // Build fresh cache for M3.
            list($success, $output) = build_cache();
            if ($success === false) {
                throwErrorAndWriteToLog(
                    "ERR_BUILD_M3_CACHE",
                    "Failed to build cache for Mautic 3. "
                        . "All your data has been successfully migrated to Mautic 3, "
                        . "but this error very likely needs to be fixed before you can start using Mautic 3. "
                        . "<br><br>Command output: " . $output
                );
            };

            writeToLog("Successfully created cache for Mautic 3");

            outputJSON('success', 'OK', 'finished', 'Finishing up...');

            break;

        case 'finished':
            sendUpgradeStats('succeeded');

            writeToLog("We're done! Ready to use Mautic 3 :)");

            final_cleanup_files();

            outputJSON(
                'success',
                "<h3>We're done! ✅</h3>
                <p><strong>You're ready to use Mautic 3!</strong> Don't forget to <a target=\"_blank\" href=\"http://mau.tc/m3-upgrade-getting-started\">update your cron jobs</a> if you haven't done so already.</p>
                <p>One last thing: during the upgrade, we created several backup files. Please remove them as soon as possible if you are sure Mautic works as expected. "
                . "You can do that by clicking \"Open Mautic 3 without removing backup files\" below, and if everything works, come back here to remove the backup files.</p><br>
                <a href=\"" . $localParameters['site_url'] . "\" class=\"btn btn-primary\" target=\"_blank\">Open Mautic 3 without removing backup files</a><br><br>
                <a href=\"?confirmDeleteBackup\" class=\"btn btn-primary\">Remove backup files and open Mautic 3</a>"
            );

        default:
            outputJSON('error', 'unknown apiTask given');
    }
} else {
    // CLI upgrade

    // We create this file when we've moved M3 files into place. Users have to restart the script then for Symfony commands to finish successfully.
    $m3_phase_2_file = MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'm3_upgrade_pending_phase_2.txt';

    if (!file_exists($m3_phase_2_file)) {
        echo "Welcome to the Mautic 3 upgrade script! Before we start, we'll run some pre-upgrade checks to make sure your system is compatible.\n";
        echo "IMPORTANT: you will need to update your cron jobs from app/console/* to bin/console/* after the upgrade. You can already change them now if you want.\n";
        echo "Please type 'yes' if you're ready to start: ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) != 'yes') {
            echo "Alright, we'll abort the upgrade now.\n";
            exit;
        }
        fclose($handle);
        echo "\n";
        echo "Thank you, continuing...\n";

        logUpdateStart();

        writeToLog("Doing pre-upgrade checks...");
        $preUpgradeCheckResults = runPreUpgradeChecks();
        $preUpgradeCheckErrors = $preUpgradeCheckResults['errors'];
        $preUpgradeCheckWarnings = $preUpgradeCheckResults['warnings'];

        if (count($preUpgradeCheckErrors) > 0) {
            writeToLog("One or more errors occurred during pre-upgrade checks: \n- " . implode("\n- ", $preUpgradeCheckErrors));
            exit;
        }

        if (count($preUpgradeCheckWarnings) > 0) {
            writeToLog("One or more warnings occurred during pre-upgrade checks, please run this script with the --ignore-warnings flag to continue: \n- " . implode("\n- ", $preUpgradeCheckWarnings));

            $val = getopt('i', ['ignore-warnings']);
            if (empty($val)) {
                exit;
            }
        }

        writeToLog("Finished pre-upgrade checks.");
        writeToLog("Starting upgrade...");

        sendUpgradeStats('started');

        if (is_mysqldump_available() === true) {
            writeToLog("Backing up your database...");
            list($success, $message) = backup_database();

            if (!$success) {
                throwErrorAndWriteToLog(
                    "ERR_DATABASE_BACKUP_FAILED",
                    "Database backup failed. Your Mautic 2 installation is intact, so you can safely restart the upgrade. Error from mysqldump: " . $message
                );
            } else {
                writeToLog("Database backup successfully written to your Mautic root folder.");
            }
        } else {
            writeToLog("Skipping database backup because we can't find mysqldump on your system...");
        }

        // Run Mautic 2 migrations
        checkAndRunMigrationsCLI(2);

        writeToLog("Downloading Mautic 3...");
        list($success, $message) = fetch_updates();

        if (!$success) {
            throwErrorAndWriteToLog(
                "ERR_DOWNLOAD_UPGRADE_PACKAGE_FAILED",
                "Downloading the Mautic 3 upgrade package has failed: " . $message
            );
        }

        writeToLog("Extracting the update package...");

        list($success, $message) = extract_package();
        if (!$success) {
            throwErrorAndWriteToLog(
                "ERR_EXTRACT_UPGRADE_PACKAGE_FAILED",
                "Error while extracting Mautic 3 files: " . $message
            );
        }

        writeToLog("Extracting done!");

        writeToLog("Preparing for phase 2 of the upgrade...");

        $result = file_put_contents($m3_phase_2_file, 'READY FOR PHASE 2, RUN php upgrade_v3.php');

        if ($result === false) {
            sendUpgradeStats('failed');
            writeToLog("IMPORTANT: We couldn't prepare for Phase 2 of the upgrade, so we need your help. "
                . "In the same folder where upgrade_v3.php is located, create a file called m3_upgrade_pending_phase_2.txt (no contents needed), then run this script again.");
            exit;
        }

        writeToLog("IMPORTANT: NOT DONE YET! Due to the large amount of changes in Composer dependencies, we now need to restart the script to continue. "
            . "We've saved your state, so we'll continue where we left off.");
        writeToLog("PLEASE RUN php upgrade_v3.php AGAIN TO START PHASE 2 OF THE UPGRADE!");
        exit;
    }

    writeToLog("Welcome to Phase 2 of the Mautic 3 upgrade! We'll continue where we left off.");

    writeToLog("Moving Mautic 2 files into mautic-2-backup and moving the Mautic 3 files in place, this might take a while... DO NOT ABORT THE SCRIPT!!!");

    /**
     * Move current Mautic 2 files into a temporary directory called "mautic-2-backup-files",
     * then move the Mautic 3 files from "mautic-3-temp-files" to the root directory.
     */
    list($success, $message) = replace_mautic_2_with_mautic_3();

    if ($success === false) {
        throwErrorAndWriteToLog(
            "ERR_MOVE_MAUTIC_2_AND_3_FILES",
            "Error while moving Mautic 2 or 3 files: " . $message
        );
    }

    writeToLog("Done!\n");

    writeToLog("Updating your config/local.php with new settings that were changed/introduced in Mautic 3...");

    list($success, $message) = update_local_config();

    if (!$success) {
        throwErrorAndWriteToLog(
            "ERR_UPDATE_LOCAL_CONFIG",
            "Failed updating your configuration in config/local.php: " . $message
        );
    }

    writeToLog("Done! Your config file has been updated.");

    // Run Mautic 3 migrations
    checkAndRunMigrationsCLI(3);

    writeToLog("Restoring your user data like custom plugins/themes/media from the Mautic 2 installation. This might take a while... DO NOT ABORT THE SCRIPT!!!");

    list($success, $message) = restore_user_data();

    if (!$success) {
        throwErrorAndWriteToLog(
            "ERR_RESTORE_USER_DATA_FAILED",
            "Failed to restore user data from Mautic 2 installation: " . $message
        );
    }

    writeToLog("Done! Your user data has been restored. Your Mautic 3 installation is ready. Just one more thing:");

    writeToLog("Cleaning up installation files that we no longer need...");    

    if (cleanup_files() === false) {
        throwErrorAndWriteToLog(
            "ERR_CLEANUP_FILES",
            "Oops! We tried cleaning up after ourselves, but it didn\'t work as expected. You should be able to start using Mautic 3 now, though."
        );
    }

    // We only use the Phase 2 file in the CLI version of the upgrade, so we'll delete it here...
    if (file_exists($m3_phase_2_file)) {
        unlink($m3_phase_2_file);
    }
    
    writeToLog("Cleaned up successfully!");

    writeToLog("Building cache for Mautic 3...");

    list($success, $output) = build_cache();
    if ($success === false) {
        throwErrorAndWriteToLog(
            "ERR_BUILD_M3_CACHE",
            "Failed to build cache for Mautic 3. "
                . "All your data has been successfully migrated to Mautic 3, "
                . "but this error very likely needs to be fixed before you can start using Mautic 3. "
                . "\n\nCommand output: " . $output
        );
    };

    writeToLog("Done! Cache has been built.");

    sendUpgradeStats('succeeded');

    writeToLog("We're done! Enjoy using Mautic 3 :)\nDon't forget to update your cron jobs!");

    final_cleanup_files();

    echo "Do you want to remove the backup files? We recommend first checking in a browser whether Mautic 3 works as expected. Type \"yes\" to remove:\n";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim($line) != "yes") {
        echo "Ok, we won't do anything for now. "
            . "Please note that upgrade_v3.php will remain (publicly) available until you choose to remove the backup files using this script! "
            . "Run this script again to be prompted to delete backup files.\n";
        exit;
    }
    fclose($handle);
    echo "\nThank you, continuing...\n";

    removeBackupFilesAndExit();

    // Exit just to be sure.
    exit;
}

/**
 * Check if there are any migrations available and run them.
 * 
 * @return void
 */
function checkAndRunMigrationsCLI($mauticMajorVersion, $ignoreFirstLog = false)
{
    if ($ignoreFirstLog === false) {
        writeToLog("Getting the amount of available Mautic $mauticMajorVersion database migrations... This might take a while!");
    }

    // Get the amount of available database migrations
    list($success, $data) = get_available_migrations();

    if ($success === false) {
        throwErrorAndWriteToLog(
            "ERR_MAUTIC_" . $mauticMajorVersion . "_MIGRATIONS_IDENTIFICATION_FAILED",
            'We couldn\'t reliably detect the amount of available database migrations.'
        );
    } else {
        if (isset($data[2]) && isset($data[2][0])) {
            $availableMigrations = intval($data[2][0]);

            if ($availableMigrations > 0) {
                writeToLog("Applying Mautic $mauticMajorVersion database migrations, " . $availableMigrations . " migrations in total... This might take a while!");

                // Apply all migrations.
                list($success, $output) = apply_migrations();

                if ($success === false) {
                    throwErrorAndWriteToLog(
                        "ERR_MAUTIC_" . $mauticMajorVersion . "_MIGRATIONS_FAILED",
                        "Something went wrong while applying Mautic " . $mauticMajorVersion . " migrations. "
                            . "Please try to run app/console doctrine:migrations:migrate --no-interaction --env=prod --no-debug, to troubleshoot further. "
                            . "Then run this script again."
                            . "\n\nCommand output: " . $output
                    );
                };

                writeToLog("All Mautic $mauticMajorVersion migrations applied successfully.");
            } else {
                writeToLog("No available database migrations found. On to the next step...");
            }
        } else {
            throwErrorAndWriteToLog(
                "ERR_MAUTIC_" . $mauticMajorVersion . "_MIGRATIONS_IDENTIFICATION_FAILED",
                'We couldn\'t reliably detect the amount of available database migrations.'
            );
        }
    }
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
 * Throw an error. In the CLI, we echo it, in the UI we output it in the browser.
 * This function also automatically generates a link to Mautic's documentation.
 * 
 * @param string $code
 * @param string $message
 * 
 * @return void
 */
function throwErrorAndWriteToLog($code, $message)
{
    writeToLog($code . ': ' . $message);
    sendUpgradeStats('failed', $code);

    // Generate URL to docs including error code.
    $url = 'http://mau.tc/m3-upgrade-error#' . strtolower($code);

    if (!IN_CLI) {
        outputJSON(
            "error",
            "$code: $message.<br><br>Read more details about this error <a target=\"_blank\" href=\"$url\">in our documentation</a>."
        );
    } else {
        echo $code . ": " . $message . ". For more details about this message, see " . $url . "\n";
        exit;
    }
}

/**
 * Write update start data to log.
 */
function logUpdateStart()
{
    $data = "===== STARTING MAUTIC 3 UPGRADE AT " . date('Y-m-d H:i:s') . "... =====\n";

    if (file_exists(MAUTIC_APP_ROOT . '/version.txt')) {
        $version = file_get_contents(MAUTIC_APP_ROOT . '/version.txt');
        $version = str_replace("\n", "", $version);
        $data .= "Installed Mautic version:\t" . $version . "\n";
    }

    $data .= "PHP version:\t\t\t\t" . PHP_VERSION . "\n";
    $data .= "OS:\t\t\t\t\t\t\t" . PHP_OS . "\n";
    $upgradeType = IN_CLI ? 'CLI' : 'UI';
    $data .= "Upgrade type:\t\t\t\t" . $upgradeType . "\n";

    writeToLog($data);
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
 * Check if there are custom plugins installed.
 * 
 * @return array
 */
function are_custom_plugins_installed()
{
    $standardPlugins = [
        'MauticCitrixBundle',
        'MauticClearbitBundle',
        'MauticCloudStorageBundle',
        'MauticCrmBundle',
        'MauticEmailMarketingBundle',
        'MauticFocusBundle',
        'MauticFullContactBundle',
        'MauticGmailBundle',
        'MauticOutlookBundle',
        'MauticSocialBundle',
        'MauticZapierBundle'
    ];
    $customPlugins = [];
    $iterator = new DirectoryIterator(MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'plugins');

    // Sanity check, make sure there are actually directories here to process
    $dirs = glob(MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'plugins' . '/*', GLOB_ONLYDIR);

    if (count($dirs)) {
        /** @var DirectoryIterator $directory */
        foreach ($iterator as $directory) {
            // Sanity checks
            if (
                !$directory->isDot()
                && $directory->isDir()
                && !in_array($directory->getBasename(), $standardPlugins)
            ) {
                $customPlugins[] = $directory->getBasename();
            }
        }

        if (count($customPlugins) > 0) {
            return [true, implode(', ', $customPlugins)];
        }
    } else {
        return [true, 'We couldn\'t seem to scan your plugins directory. Please make sure it exists'];
    }

    // No custom plugins found
    return [false, null];
}

/**
 * Check if mysqldump is available on the system.
 * We check this by doing a mysqldump of the plugins table (which should be rather small on all installations) to /dev/null.
 * 
 * @return bool
 */
function is_mysqldump_available()
{
    global $localParameters;

    // We can only execute mysqldump if PHP's exec function is available (blocked on some hosts)
    if (!function_exists('exec')) {
        return false;
    }

    $return_var  = null;
    $output      = null;
    $db_password = '';
    $output_path = '/dev/null';

    if (defined('IS_WINDOWS')) {
        $output_path = 'NUL';
    }

    // Escape single quotes in DB password
    if (!empty($localParameters['db_password'])) {
        $db_password = str_replace("'", "'\''", $localParameters['db_password']);
        $db_password = "-p'" . $db_password . "'";
    }
    
    $db_prefix = !empty($localParameters['db_table_prefix']) ? $localParameters['db_table_prefix'] : '';
    // Do the database backup with mysqldump
    $command = "mysqldump -u " . $localParameters['db_user'] . " -h " . $localParameters['db_host'] . " " . $db_password . " " . $localParameters['db_name'] . " " . $db_prefix . "plugins > " . $output_path;
    exec($command, $output, $return_var);

    if (empty($return_var)) {
        return true;
    }

    return false;
}

/**
 * Backup the database.
 * 
 * @return array
 */
function backup_database()
{
    global $localParameters;

    $return_var  = null;
    $output      = null;
    $db_password = '';
    
    // Escape single quotes in DB password
    if (!empty($localParameters['db_password'])) {
        $db_password = str_replace("'", "'\''", $localParameters['db_password']);
        $db_password = "-p'" . $db_password . "'";
    }
    
    // Do the database backup with mysqldump
    $command = "mysqldump -u " . $localParameters['db_user'] . " -h " . $localParameters['db_host'] . " " . $db_password . " " . $localParameters['db_name'] . " > " . MAUTIC_ROOT . '/m3_upgrade_db_backup.sql';
    exec($command, $output, $return_var);

    if (empty($return_var)) {
        return [true, 'OK'];
    }

    return [false, $output];
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
    global $m3_phase_2_file;

    /**
     * ==== BACKUP MAUTIC 2 FILES ====
     * We'll backup the original M2 installation in case something goes wrong.
     */
    $errorLog = [];

    if (!file_exists(MAUTIC_BACKUP_FOLDER_ROOT)) {
        mkdir(MAUTIC_BACKUP_FOLDER_ROOT);
    }

    // Only exclude the Mautic 2 backup folder, Mautic 3 upgrade files folder, the current upgrade file and the DB backup file.
    $excludedFilesAndFolders = [
        MAUTIC_UPGRADE_ROOT,
        MAUTIC_BACKUP_FOLDER_ROOT,
        __FILE__,
        MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'm3_upgrade_db_backup.sql',
        UPGRADE_LOG_FILE
    ];

    if (!empty($m3_phase_2_file)) {
        $excludedFilesAndFolders[] = $m3_phase_2_file;
    } 

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
        rename(MAUTIC_ROOT . DIRECTORY_SEPARATOR . '/.htaccess', MAUTIC_ROOT . DIRECTORY_SEPARATOR . '/.htaccess.m3');
        copy(MAUTIC_BACKUP_FOLDER_ROOT . DIRECTORY_SEPARATOR . '/.htaccess', MAUTIC_ROOT . DIRECTORY_SEPARATOR . '/.htaccess');

        // Last step is to restore the config files (otherwise this script can't be loaded with a new step, as the local.php file won't exist)
    } else {
        return [false, 'Something went wrong while we tried to move the new Mautic 3 files to your Mautic root folder. You are in a critical state now where you need to restore things manually.'];
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

    // API rate limiter cache config changed, see https://github.com/mautic/mautic/blob/3.x/UPGRADE-3.0.md#configuration
    // Always overwrite custom configurations with the default filesystem adapter to avoid conflicts. We warn users about this during the pre-upgrade checks.
    if (!empty($parameters['api_rate_limiter_cache'])) {
        $parameters['api_rate_limiter_cache'] = array(
            'adapter' => 'cache.adapter.filesystem'
        );
    }

    // Replace "mail" transport by "sendmail" as it's removed in SwiftMailer 6 https://symfony.com/doc/3.4/email.html#configuration
    $parameters = replaceConfigValueIfExistsAndEquals($parameters, 'mailer_transport', 'mail', 'sendmail');

    // System update URL has changed in M3 to use GitHub releases
    $parameters = replaceConfigValueIfExistsAndEquals($parameters, 'system_update_url', 'https://updates.mautic.org/index.php?option=com_mauticdownload&task=checkUpdates', 'https://api.github.com/repos/mautic/mautic/releases');

    // dev_hosts has been changed from null to an empty array
    $parameters = replaceConfigValueIfExistsAndEquals($parameters, 'dev_hosts', null, array());

    // Mauve theme was removed in 3.x and replaced by blank
    $parameters = replaceConfigValueIfExistsAndEquals($parameters, 'theme', 'Mauve', 'blank');

    // Track by fingerprint was removed in 3.x
    if (array_key_exists('track_by_fingerprint', $parameters)) {
        unset($parameters['track_by_fingerprint']);
    }

    // webhook_start was removed in 3.x
    if (array_key_exists('webhook_start', $parameters)) {
        unset($parameters['webhook_start']);
    }

    // Cache path was moved from app/cache to var/cache
    if (array_key_exists('cache_path', $parameters)) {
        $parameters['cache_path'] = str_replace('/app/cache', '/app/../var/cache', $parameters['cache_path']);
    }

    // Log path was moved from app/logs to var/logs
    if (array_key_exists('log_path', $parameters)) {
        $parameters['log_path'] = str_replace('/app/logs', '/app/../var/logs', $parameters['log_path']);
    }

    // Temp path was moved from app/cache to ITS OWN FOLDER var/tmp
    if (array_key_exists('tmp_path', $parameters)) {
        $parameters['tmp_path'] = str_replace('/app/cache', '/app/../var/tmp', $parameters['tmp_path']);
    }

    // Spool path was moved from %kernel.root_dir%/spool to %kernel.root_dir%/../var/spool
    if (array_key_exists('mailer_spool_path', $parameters)) {
        $parameters['mailer_spool_path'] = str_replace('%kernel.root_dir%/spool', '%kernel.root_dir%/../var/spool', $parameters['mailer_spool_path']);
    }

    // Write updated config to local.php
    $result = file_put_contents($filename, "<?php\n" . '$parameters = ' . var_export($parameters, true) . ';');

    if ($result === false) {
        return [false, 'Couldn\'t update configuration file with new api_rate_limiter_cache value.'];
    }

    return [true, 'OK'];
}

/**
 * Replace a config value if it exists and if it equals oldValue.
 */
function replaceConfigValueIfExistsAndEquals(array $parameters, string $key, $oldValue, $newValue)
{
    if (array_key_exists($key, $parameters)) {
        if ($parameters[$key] === $oldValue) {
            $parameters[$key] = $newValue;
        }
    }

    return $parameters;
}

/**
 * Run Symfony command. Returns array of [bool $success, string $output]
 * 
 * @param       $command
 * @param array $args
 *
 * @return array
 *
 * @throws Exception
 */
function run_symfony_command($command, array $args)
{
    // Don't re-use the $application varaiable as it will cause problems with the CLI upgrade
    $application = null;

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
    $output   = new \Symfony\Component\Console\Output\BufferedOutput();
    $exitCode = $application->run($input, $output);

    $content = $output->fetch();
    
    unset($application, $input, $output);

    return [
        $exitCode === 0,
        $content
    ];
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
 * @return array
 */
function build_cache()
{
    // Build the cache
    return run_symfony_command('cache:clear', ['--no-interaction', '--env=prod', '--no-debug']);
}

/**
 * Apply all available migrations. NOTE: should only be used in CLI environments due to possible timeouts!
 *
 * @return array
 */
function apply_migrations()
{
    return run_symfony_command('doctrine:migrations:migrate', ['--no-interaction', '--env=prod', '--no-debug']);
}

/**
 * Apply the next migration. We do it one by one to prevent PHP timeouts.
 *
 * @return array
 */
function apply_single_migration()
{
    return run_symfony_command('doctrine:migrations:migrate', ['next', '--no-interaction', '--env=prod', '--no-debug']);
}

/**
 * Get the amount of available migrations.
 * Returns array with [bool $success, int $available_migrations]
 * 
 * @return array
 */
function get_available_migrations()
{
    list($success, $message) = run_symfony_command('doctrine:migrations:status', []);

    if ($success === false) {
        return [false, 'Couldn\'t determine amount of available database migrations.'];
    } else {
        preg_match_all('/>> New Migrations:( )+(\d+)/', $message, $data);
        return [true, $data];
    }
}

/**
 * Send Mautic 3 upgrade stats to our stats server.
 * 
 * @param string $status
 * @param string $errorCode
 * 
 * @return void
 */
function sendUpgradeStats($status, $errorCode = null)
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

        // Get database version if possible.
        $mysqli = new mysqli($localParameters['db_host'], $localParameters['db_user'], $localParameters['db_password'], $localParameters['db_name'], $localParameters['db_port']);
        $dbVersion = null;

        if (mysqli_connect_errno() === 0 && !empty($mysqli->server_version)) {
            // E.g. 100200 for MariaDB 10.2.0 or 50714 for MySQL 5.7.14
            // PHP uses the following calculation: main_version * 10000 + minor_version * 100 + sub_version
            $dbVersionRaw = $mysqli->server_version;

            $main = ltrim(substr($dbVersionRaw, -6, -4), '0'); // E.g. 5
            $minor = ltrim(substr($dbVersionRaw, -4, 2), '0'); // E.g. 7
            $sub = ltrim(substr($dbVersionRaw, -2, 2), '0'); // E.g. 14

            $dbVersion = implode('.', [$main, $minor, $sub]);
        }

        $data = [
            'application'   => 'Mautic',
            'version'       => $version,
            'dbVersion'     => $dbVersion,
            'phpVersion'    => PHP_VERSION,
            'dbDriver'      => $localParameters['db_driver'],
            'serverOs'      => php_uname('s') . ' ' . php_uname('r'),
            'instanceId'    => $instanceId,
            'upgradeStatus' => $status,
            'errorCode'     => $errorCode
        ];

        make_request('https://updates.mautic.org/stats/mautic3upgrade/send', 'post', $data);
    } catch (\Exception $exception) {
        // Not so concerned about failures here, move along
    }
}

/**
 * Write some text to the log.
 */
function writeToLog($content)
{
    $data = '';

    if (file_exists(UPGRADE_LOG_FILE)) {
        $data = file_get_contents(UPGRADE_LOG_FILE);
    }

    $data .= "\n[" . date('Y-m-d H:i:s') . "] $content";

    file_put_contents(UPGRADE_LOG_FILE, $data);

    if (IN_CLI) {
        echo "$content\n";
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
        $newFolderPath = str_replace('mautic-2-backup-files', 'mautic-2-backup-files-' . $hash, MAUTIC_POST_UPGRADE_BACKUP_FOLDER_ROOT);
        $status = rename(MAUTIC_BACKUP_FOLDER_ROOT, $newFolderPath);
        if ($status === false) $failedChanges[] = 'mautic-2-backup-files';
    }

    // Rename the m3_upgrade_db_backup.sql file to one with a random hash to prevent public access
    if (file_exists(MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'm3_upgrade_db_backup.sql')) {
        $hash = bin2hex(random_bytes(16));
        $status = rename(MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'm3_upgrade_db_backup.sql', MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'm3_upgrade_db_backup_' . $hash . '.sql');
        if ($status === false) $failedChanges[] = 'm3_upgrade_db_backup.sql';
    }

    // NOTE: we leave the mautic-2-backup-files-$HASH folder and m3_upgrade_db_backup_$HASH.sql file as-is in case something still doesn't work as expected.

    if (count($failedChanges) === 0) {
        return true;
    }

    return false;
}

/**
 * These files can only be deleted/changed at the very, very end.
 * 
 * @return void
 */
function final_cleanup_files()
{
    if (!file_exists(POST_UPGRADE_BACKUP_FILES_REMOVAL_PENDING_FILE)) {
        file_put_contents(
            POST_UPGRADE_BACKUP_FILES_REMOVAL_PENDING_FILE,
            "This file is removed automatically if you open upgrade_v3.php to delete backup files."
        );
    }

    // Rename the upgrade_log.txt file to one with a random hash to prevent public access
    if (file_exists(UPGRADE_LOG_FILE)) {
        $hash = bin2hex(random_bytes(16));
        $status = rename(UPGRADE_LOG_FILE, str_replace('upgrade_log.txt', 'upgrade_log_' . $hash . '.txt', POST_UPGRADE_LOG_FILE));
        if ($status === false) $failedChanges[] = 'upgrade_log.txt';
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
 * Output JSON to the browser and exit script.
 * 
 * @param string $status (success or error)
 * @param string $message
 * @param string $nextTaskCode
 * @param string $nextTaskLabel
 * 
 * @return void
 */
function outputJSON($status, $message, $nextTaskCode = null, $nextTaskLabel = null)
{
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'nextTaskCode' => $nextTaskCode,
        'nextTaskLabel' => $nextTaskLabel
    ]);
    exit;
}

/**
 * Converts shorthand memory notation value to bytes
 * From http://php.net/manual/en/function.ini-get.php
 *
 * @param $val Memory size shorthand notation string
 *
 * @return int
 */
function returnBytes($val)
{
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = substr($val, 0, -1);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

/**
 * Remove backup files.
 * 
 * @return void
 */
function removeBackupFilesAndExit()
{
    global $localParameters;

    $varFolder = MAUTIC_ROOT . DIRECTORY_SEPARATOR . 'var';

    $iterator = new GlobIterator($varFolder . DIRECTORY_SEPARATOR . 'mautic-2-backup-files-*');

    foreach ($iterator as $directory) {
        if ($directory->isDir()) {
            recursive_remove_directory($directory->getPathname());
        }
    }

    $sqlIterator = new GlobIterator($varFolder . DIRECTORY_SEPARATOR . 'm3_upgrade_db_backup_*.sql');

    foreach ($sqlIterator as $sql) {
        if ($sql->isFile()) {
            unlink($sql->getPathname());
        }
    }

    $logIterator = new GlobIterator($varFolder . DIRECTORY_SEPARATOR . 'upgrade_log_*.txt');

    foreach ($logIterator as $log) {
        if ($log->isFile()) {
            unlink($log->getPathname());
        }
    }

    // Restore the Mautic 3 .htaccess file
    if (file_exists(MAUTIC_ROOT . DIRECTORY_SEPARATOR . '.htaccess.m3')) {
        rename(MAUTIC_ROOT . DIRECTORY_SEPARATOR . '.htaccess.m3', MAUTIC_ROOT . DIRECTORY_SEPARATOR . '.htaccess');
    }

    // Remove the upgrade script file.
    unlink(__FILE__);

    if (file_exists(POST_UPGRADE_BACKUP_FILES_REMOVAL_PENDING_FILE)) {
        unlink(POST_UPGRADE_BACKUP_FILES_REMOVAL_PENDING_FILE);
    }

    if (IN_CLI) {
        echo "Backup files successfully removed, as well as the current upgrade script. Enjoy Mautic 3!\n";
        exit;
    } else {
        header("Refresh:5;url=" . $localParameters["site_url"]);
        echo "<p>Backup files successfully removed, as well as the current upgrade script. You will be redirected to Mautic in 5 seconds. Enjoy Mautic 3!</p>
        <p>If you are not redirected within 5 seconds, <a href=\"" . $localParameters["site_url"] . "\">click here to open Mautic</a>.</p>";
        exit;
    }
}

/**
 * Wrap content in some HTML.
 *
 * @param string $content The content
 * @param bool   $noJS    If this is true, we don't run JS API calls
 * 
 * @return void
 */
function html_body($content, $noJS = false)
{
    $noJSvar = $noJS === true ? 'true' : 'false';
    $html = <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Upgrade Mautic</title>
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    </head>
    <body>
        <div class="container" style="padding: 25px;">
            <div class='card card-body bg-light'>
                <div id="mainContent">$content</div>
                <div id="upgradeProgressStatus">
                    <table class="table" id="upgradeProgressStatusTable">
                    </table>
                    <div style="display: none" class="alert alert-danger" id="errorBox">
                    </div>
                    <div style="display: none" class="alert alert-success"id="successBox">
                    </div>
                </div>
            </div>
        </div>
        <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

        <script type="text/javascript">
            const apiUrl = window.location.protocol + '//' + window.location.host + window.location.pathname;
            let apiTaskCode = 'startUpgrade';
            let apiTaskLabel = 'Starting the upgrade...';
            const noJS = $noJSvar;

            $(document).ready(function(){
                if (noJS === true) {
                    return;
                } else if (window.location.hash && window.location.hash.length > 1) {
                    // If a specific upgrade step is set in the URL, use that one.
                    apiTaskCode = window.location.hash.replace('#', '');
                    apiTaskLabel = 'Picking up where we left off... Running the following task: ' + apiTaskCode;
                    executeApiTask(apiTaskCode, apiTaskLabel);
                } else {
                    runPreUpgradeChecks();
                }
            });

            function runPreUpgradeChecks() {
                $('#mainContent').html("<h3>Checking system requirements...</h3><p><strong>We're checking whether your system meets the requirements for Mautic 3. This may take several minutes, do not close this window!</strong></p><div class=\"spinner-border\" role=\"status\"><span class=\"sr-only\">Loading...</span></div>");

                $.getJSON( apiUrl + '?apiTask=preUpgradeChecks', function(data) {
                    if (data.status && data.status === 'success') {
                        if (data.message) {
                            $('#mainContent').html(data.message);
                            $('#startUpgradeButton').click(function() {
                                $('#mainContent').html('<h2>Upgrading Mautic...</h2><p>This might take several minutes.</p>');
                                executeApiTask(apiTaskCode, apiTaskLabel);
                            });
                        } else {
                            alert("We got an unexpected response from the server. Please try again");
                        }
                        
                    } else {
                        alert('We got an unknown response back from the server. Please try again or contact Mautic support!');
                    }
                });
            }

            function executeApiTask(taskCode, taskLabel, appendRow = true) {
                apiTaskCode = taskCode;
                apiTaskLabel = taskLabel;

                if (appendRow === true) {
                    $('#upgradeProgressStatusTable').append('<tr><td id="api-task-' + apiTaskCode + '-label">' + apiTaskLabel + '</td><td id="api-task-' + apiTaskCode + '-status"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></td></tr>');
                }   

                // Update URL hash so that if the user gets stuck, he can refresh the page and pick up where they left off.
                window.location.hash = '#' + apiTaskCode;

                var jqxhr = $.getJSON( apiUrl + '?apiTask=' + apiTaskCode, function(data) {
                    appendRow = true;

                    if (data.status && data.status === 'success') {
                        // Sometimes we need to re-run a task in case it takes long (to prevent timeouts), so we update the existing label.
                        if (data.nextTaskCode && data.nextTaskLabel && data.nextTaskCode === apiTaskCode) {
                            $('#api-task-' + apiTaskCode + '-label').html(data.nextTaskLabel);
                            appendRow = false;
                        } else {
                            $('#api-task-' + apiTaskCode + '-status').html('<svg class="bi bi-check2" width="2em" height="2em" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/></svg>');
                        }
                            
                        if (data.nextTaskCode && data.nextTaskLabel) {
                            executeApiTask(data.nextTaskCode, data.nextTaskLabel, appendRow);
                        } else if (data.message) {
                            $('#successBox').show();
                            $('#successBox').html(data.message);
                        } else {
                            $('#successBox').show();
                            $('#successBox').html('We\'re done, but we didn\'t get a specific success message from the server.');
                        }
                    } else if (data.status && data.status === 'error') {
                        $('#api-task-' + apiTaskCode + '-status').html('<svg class="bi bi-exclamation-triangle-fill" width="2em" height="2em" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 5zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/></svg>');

                        if (data.message) {
                            $('#errorBox').show();
                            $('#errorBox').html(data.message);
                        } else {
                            $('#errorBox').show();
                            $('#errorBox').html('Unknown error occurred');
                        }
                    } else {
                        alert('We got an unknown response back from the server. Please try again or contact Mautic support!');
                    }
                })
                .fail(function() {
                    $('#api-task-' + apiTaskCode + '-status').html('<svg class="bi bi-exclamation-triangle-fill" width="2em" height="2em" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 5zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/></svg>');

                    $('#errorBox').show();
                    $('#errorBox').html('Something went wrong on the server, but we didn\'t get any further details. This might be a timeout (the script was running for too long). Please check your server logs and refresh this page to try again.');
                })
            }
        </script>
    </body>
    </html>
HTML;

    echo $html;

    exit;
}
