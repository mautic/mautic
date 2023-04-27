<?php

define('MAUTIC_ROOT_DIR', __DIR__);

// Fix for hosts that do not have date.timezone set, it will be reset based on users settings
date_default_timezone_set('UTC');

require_once 'autoload.php';

use Mautic\CoreBundle\ErrorHandler\ErrorHandler;

ErrorHandler::register('prod');

$kernel   = new AppKernel('prod', false);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
