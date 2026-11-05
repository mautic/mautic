<?php

declare(strict_types=1);

use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\ChannelBundle\Security\Permissions\ChannelPermissions;
use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'PreferenceBuilder/ChannelPreferences.php',
        'PreferenceBuilder/PreferenceBuilder.php',
    ];

    $services->load('Mautic\\ChannelBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\ChannelBundle\\Entity\\', '../Entity/*Repository.php');

    $services->set(ChannelListHelper::class)
        ->tag('twig.helper', ['alias' => 'channel']);
    $services->set(ChannelPermissions::class);
};
