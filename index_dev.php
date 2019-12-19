<?php

/*
 * @copyright   2014 Mautic, NP
 * @author      Mautic
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
define('MAUTIC_ROOT_DIR', __DIR__);

// Fix for hosts that do not have date.timezone set, it will be reset based on users settings
date_default_timezone_set('UTC');

require_once __DIR__.'/vendor/autoload.php';

use Mautic\CoreBundle\ErrorHandler\ErrorHandler;
use Mautic\Middleware\MiddlewareBuilder;
use function Stack\run;

ErrorHandler::register('dev');

run((new MiddlewareBuilder(new AppKernel('dev', true)))->resolve());
