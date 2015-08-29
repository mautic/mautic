<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP
 * @author      Mautic
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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
//umask(0000);

// This check prevents access to debug front controllers that are deployed by accident to production servers.
// Feel free to remove this, extend it, or make something more sophisticated.
$allowedIps = array('127.0.0.1', 'fe80::1', '::1');
if (isset($_SERVER['MAUTIC_DEV_HOSTS'])) {
    $localIps   = explode(' ', $_SERVER['MAUTIC_DEV_HOSTS']);
    $allowedIps = array_merge($allowedIps, $localIps);
}

if (isset($_SERVER['HTTP_CLIENT_IP'])
    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    || !in_array(@$_SERVER['REMOTE_ADDR'], $allowedIps)
) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}

$loader = require_once __DIR__.'/app/bootstrap.php.cache';
Debug::enable();

require_once __DIR__.'/app/AppKernel.php';

$kernel = new AppKernel('dev', true);
$kernel->loadClassCache();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
