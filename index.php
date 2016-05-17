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
define('MAUTIC_ROOT_DIR', __DIR__);

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

$loader = require_once __DIR__ . '/app/autoload.php';

// Use APC for autoloading to improve performance.
// Change 'sf2' to a unique prefix in order to prevent cache key conflicts
// with other applications also using APC.
/*
$apcLoader = new ApcClassLoader('sf2', $loader);
$loader->unregister();
$apcLoader->register(true);
*/

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();

Stack\run((new \Mautic\Middleware\MiddlewareBuilder)->resolve($kernel));
