<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\HttpFoundation\Request;

// Cannot function on PHP 5.6 properly due to a Doctrine bug which is included in Doctrine 2.5
// See http://www.doctrine-project.org/jira/browse/DDC-3120 for more
if (version_compare(PHP_VERSION, '5.6', 'ge')) {
    echo "Mautic will not function properly on PHP 5.6 due to an issue with a third party dependency.\n";
    echo "Please downgrade to PHP 5.4 or 5.5.";
    exit;
}

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

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();
//$kernel = new AppCache($kernel);

// When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();
$request  = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
