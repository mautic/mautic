<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Mautic\CoreBundle\ErrorHandler\ErrorHandler;

define('MAUTIC_ROOT_DIR', __DIR__);

// Fix for hosts that do not have date.timezone set, it will be reset based on users settings
date_default_timezone_set('UTC');

require_once __DIR__.'/../autoload.php';

use Symfony\Component\DependencyInjection\ContainerInterface;

ErrorHandler::register('prod');

$kernel   = new AppKernel('prod', false);
$kernel->boot();

/** @var ContainerInterface $container */
$container = $kernel->getContainer();

/** @var EntityManager $objectManager */
$objectManager = $container->get('doctrine')->getManager();

// this is a workaround for the following phpstan issue: https://github.com/phpstan/phpstan-doctrine/issues/98
$resolveTargetEntityListener = \current(\array_filter(
    $objectManager->getEventManager()->getListeners('loadClassMetadata'),
    static fn ($listener) => $listener instanceof ResolveTargetEntityListener,
));

if (false !== $resolveTargetEntityListener) {
    $objectManager->getEventManager()->removeEventListener([Events::loadClassMetadata], $resolveTargetEntityListener);
}

return $objectManager;
