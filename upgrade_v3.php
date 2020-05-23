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
ini_set('display_errors', 'On');
date_default_timezone_set('UTC');

define('MAUTIC_MINIMUM_PHP', '7.2.21');
define('MAUTIC_MAXIMUM_PHP', '7.3.999');

$standalone = (int) getVar('standalone', 0);
$task       = getVar('task');

define('IN_CLI', php_sapi_name() === 'cli');
define('MAUTIC_ROOT', (IN_CLI || $standalone || empty($task)) ? __DIR__ : dirname(__DIR__));
define('MAUTIC_UPGRADE_ERROR_LOG', MAUTIC_ROOT . '/upgrade_errors.txt');
define('MAUTIC_APP_ROOT', MAUTIC_ROOT . '/app');

if ($standalone || IN_CLI) {
    if (!file_exists(__DIR__ . '/upgrade')) {
        mkdir(__DIR__ . '/upgrade');
    }
    define('MAUTIC_UPGRADE_ROOT', __DIR__ . '/upgrade');
} else {
    define('MAUTIC_UPGRADE_ROOT', __DIR__);
}

// Data we fetch from a special JSON file to control upgrade behavior.
// TODO replace with actual JSON file
$updateData = [
    'mautic3downloadUrl' => 'https://github.com/mautic/mautic/releases/download/3.0.0-beta2/3.0.0-beta2-update.zip',
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

    // Try to get current Mautic config
    $configFile = MAUTIC_APP_ROOT . '/config/local.php';

    if (file_exists($configFile)) {
        require_once MAUTIC_APP_ROOT . '/config/local.php';
        $config = $parameters;
        unset($parameters);
    } else {
        // Hard requirement, so we die immediately if we can't get the config
        die('Cannot find Mautic\'s config file (local.php). Please make sure that Mautic is installed and configured correctly.');
    }

    // Check database connection and database version
    if (!in_array($config['db_driver'], ['pdo_mysql', 'mysqli'])) {
        $preUpgradeErrors[] = 'Your database driver is not pdo_mysql or mysqli, which are the only drivers that Mautic supports. Please change your database driver (config/local.php)!';
    }

    $mysqli = new mysqli($config['db_host'], $config['db_user'], $config['db_password']);

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

    // Check if we have the required Mautic version 2.16.3 prior to upgrading.
    $version = file_get_contents(MAUTIC_APP_ROOT . '/version.txt');
    $version = str_replace("\n", "", $version);

    if (!version_compare($version, '2.16.3', '>=')) {
        $preUpgradeErrors[] = 'You need to have at least Mautic 2.16.3 installed, which supports upgrading to 3.0. Please update to 2.16.3 first.';
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
        $db_password = str_replace("'", "'\''", $config['db_password']);
        // Check if mysqldump command finishes by writing to /dev/null
        $command = "mysqldump -u " . $config['db_user'] . " -h " . $config['db_host'] . " -p'" . $db_password . "' " . $config['db_name'] . " > /dev/null";
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
            header("Refresh: 2; URL=$url?{$query}task=preUpgradeChecks&standalone=1");
            html_body("<div class='well text-center'><h3>Checking system requirements...</h3><br /><strong>We're checking whether your system meets the requirements for Mautic 3. This may take several minutes, do not close this window!</strong></div>");
            exit;

        case 'preUpgradeChecks':
            $preUpgradeCheckResults = runPreUpgradeChecks();
            $preUpgradeCheckErrors = $preUpgradeCheckResults['errors'];
            $preUpgradeCheckWarnings = $preUpgradeCheckResults['warnings'];
            $html = "<div class='well text-center'>";

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
                <br /><strong>Your system is compatible with Mautic 3! Do not refresh or stop the process. This may take several minutes.</strong><br><Br>
                <a class='btn btn-primary' href='$url?task=startUpgrade&standalone=1'>Start the upgrade</a>";
            }

            html_body($html);
            break;        

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
                $query    = 'count=' . $state['refresh_count'] . '&';
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
                $query    = 'count=' . $state['refresh_count'] . '&';
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
    curl_setopt($ch, CURLOPT_CAINFO, MAUTIC_ROOT.'/vendor/joomla/http/src/Transport/cacert.pem');
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
            download_package($update);
        } catch (\Exception $e) {
            return [
                false,
                "Could not automatically download the package. Please download {$update->package}, place it in the same directory as this upgrade script, and try again. ".
                "When moving the file, name it `{$update->version}-update.zip`",
            ];
        }

        return [true, $update->version];
    } catch (\Exception $exception) {
        return [false, $exception->getMessage()];
    }
}

/**
 * @param object $update
 *
 * @throws Exception
 *
 * @return bool
 */
function download_package($update)
{
    $packageName = $update->version.'-update.zip';
    $target      = __DIR__.'/'.$packageName;

    if (file_exists($target)) {
        return true;
    }

    $data = make_request($update->package);

    if (!file_put_contents($target, $data)) {
        throw new \Exception();
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
