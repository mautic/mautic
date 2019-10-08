<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
define('MAUTIC_ROOT_DIR', __DIR__);

// Fix for hosts that do not have date.timezone set, it will be reset based on users settings
date_default_timezone_set('UTC');

require_once __DIR__.'/app/autoload.php';

use Mautic\CoreBundle\ErrorHandler\ErrorHandler;
use Mautic\Middleware\MiddlewareBuilder;
use function Stack\run;

// in some setups HTTP_X_FORWARDED_PROTO might contain
// a comma-separated list e.g. http,https
// so check for https existence
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && false !== strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https')) {
    $_SERVER['HTTPS'] = 'on';
}

ErrorHandler::register('prod');

run((new MiddlewareBuilder(new AppKernel('prod', false)))->resolve());
