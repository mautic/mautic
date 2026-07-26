<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Mautic\CoreBundle\ErrorHandler\ErrorHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

define('MAUTIC_ROOT_DIR', __DIR__);

// Fix for hosts that do not have date.timezone set; it will be reset based on user settings.
date_default_timezone_set('UTC');

require_once __DIR__.'/../autoload.php';

ErrorHandler::register('prod');

$kernel = new AppKernel('prod', false);
$kernel->boot();

/** @var ContainerInterface $container */
$container = $kernel->getContainer();

/** @var EntityManager $objectManager */
$objectManager = $container->get('doctrine')->getManager();

$eventManager = $objectManager->getEventManager();

// Workaround for https://github.com/phpstan/phpstan-doctrine/issues/98
$resolveTargetEntityListener = \current(\array_filter(
    $eventManager->getListeners(Events::loadClassMetadata),
    static fn ($listener): bool => $listener instanceof ResolveTargetEntityListener,
));

if (false !== $resolveTargetEntityListener) {
    $eventManager->removeEventListener(
        [Events::loadClassMetadata],
        $resolveTargetEntityListener
    );
}

return $objectManager;
