<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// Define Mautic's supported PHP versions
define('MAUTIC_MINIMUM_PHP', '5.3.7');
define('MAUTIC_MAXIMUM_PHP', '5.6.999');

// Are we running the minimum version?
if (version_compare(PHP_VERSION, MAUTIC_MINIMUM_PHP, '<')) {
    echo 'Your server does not meet the minimum PHP requirements. Mautic requires PHP version '.MAUTIC_MINIMUM_PHP.' while your server has '.PHP_VERSION.'. Please contact your host to update your PHP installation.';

    exit;
}

// Are we running a version newer than what Mautic supports?
if (version_compare(PHP_VERSION, MAUTIC_MAXIMUM_PHP, '>')) {
    echo 'Mautic does not support PHP version '.PHP_VERSION.' at this time. To use Mautic, you will need to downgrade to an earlier version.';

    exit;
}

// Fix for hosts that do not have date.timezone set, it will be reset based on users settings
date_default_timezone_set ('UTC');

use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\HttpFoundation\Request;

$loader = require_once __DIR__ . '/app/bootstrap.php.cache';

// Use APC for autoloading to improve performance.
// Change 'sf2' to a unique prefix in order to prevent cache key conflicts
// with other applications also using APC.
/*
$apcLoader = new ApcClassLoader('sf2', $loader);
$loader->unregister();
$apcLoader->register(true);
*/

require_once __DIR__ . '/app/AppKernel.php';
//require_once __DIR__.'/mautic/app/AppCache.php';

try {
    $kernel = new AppKernel('prod', false);
    $kernel->loadClassCache();
    //$kernel = new AppCache($kernel);

    // When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
    //Request::enableHttpMethodParameterOverride();
    $request  = Request::createFromGlobals();
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);

} catch (\Mautic\CoreBundle\Exception\DatabaseConnectionException $e) {
    define('MAUTIC_OFFLINE', 1);
    $message = $e->getMessage();
    include __DIR__ . '/offline.php';
} catch (\Exception $e) {
    error_log($e);

    define('MAUTIC_OFFLINE', 1);
    $message    = 'The site is currently offline due to encountering an error. If the problem persists, please contact the system administrator.';
    $submessage = 'System administrators, check server logs for errors.';
    include __DIR__ . '/offline.php';
}
